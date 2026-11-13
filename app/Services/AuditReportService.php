<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ReportBatch;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AuditReportService
{
    public const REPORT_TYPES = [
        'audit_transactions' => [
            'name'        => 'Report Audit Transaksi Keuangan',
            'description' => 'Mencatat seluruh penambahan, pengubahan, dan penghapusan transaksi beserta nominal dan akun.',
            'tables'      => ['transactions'],
            'actions'     => [],
        ],
        'audit_accounts' => [
            'name'        => 'Report Audit Saldo & Rekening',
            'description' => 'Mencatat mutasi saldo awal, penyesuaian saldo, serta pembuatan dan penghapusan akun bank/e-wallet.',
            'tables'      => ['accounts'],
            'actions'     => [],
        ],
        'audit_security' => [
            'name'        => 'Report Audit Keamanan & Akses User',
            'description' => 'Mencatat riwayat login, logout, IP address, jenis perangkat/browser, dan aktivitas sesi.',
            'tables'      => ['users'],
            'actions'     => ['LOGIN', 'LOGOUT', 'REGISTER', 'PASSWORD_RESET'],
        ],
        'audit_profile' => [
            'name'        => 'Report Audit Perubahan Password & Akun',
            'description' => 'Mencatat penggantian password, reset password, dan pembaruan data profil pengguna.',
            'tables'      => ['users'],
            'actions'     => ['UPDATE', 'UPDATE_PASSWORD'],
        ],
        'audit_debts' => [
            'name'        => 'Report Audit Hutang & Cicilan',
            'description' => 'Mencatat pencatatan hutang/piutang baru, riwayat pelunasan cicilan, dan penghapusan data kewajiban.',
            'tables'      => ['debts', 'installments', 'installment_payments'],
            'actions'     => [],
        ],
        'audit_budgets' => [
            'name'        => 'Report Audit Anggaran (Budgets)',
            'description' => 'Mencatat perubahan limit anggaran bulanan, pembuatan anggaran baru, dan penghapusan budget.',
            'tables'      => ['budgets'],
            'actions'     => [],
        ],
        'audit_roles' => [
            'name'        => 'Report Audit Role & Hak Akses (RBAC)',
            'description' => 'Mencatat penambahan/penghapusan role, perubahan matriks permission menu, dan penugasan role ke user.',
            'tables'      => ['md_role', 'md_role_menu', 'user_roles'],
            'actions'     => ['CREATE_ROLE', 'DELETE_ROLE', 'ASSIGN_ROLE', 'UPDATE_PERMISSIONS'],
        ],
    ];

    public static function getReportTypes(): array
    {
        return self::REPORT_TYPES;
    }

    public function generate(ReportBatch $batch): void
    {
        $batch->update([
            'status'     => 'processing',
            'started_at' => now(),
        ]);

        try {
            $config = self::REPORT_TYPES[$batch->report_type] ?? null;
            if (!$config) {
                throw new \InvalidArgumentException("Invalid report type: {$batch->report_type}");
            }

            $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $batch->report_name);
            $fileName = strtolower($sanitizedName) . '_' . date('Ymd_His') . '_' . $batch->id . '.csv';
            $relativeFilePath = 'reports/' . $fileName;
            $fullFilePath = Storage::disk('local')->path($relativeFilePath);

            $storageDir = dirname($fullFilePath);
            if (!File::exists($storageDir)) {
                File::makeDirectory($storageDir, 0755, true);
            }

            $fileHandle = fopen($fullFilePath, 'w');
            if ($fileHandle === false) {
                throw new \RuntimeException("Unable to open file for writing: {$fullFilePath}");
            }

            // Write UTF-8 BOM for Microsoft Excel compatibility
            fprintf($fileHandle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Query audit logs
            $query = AuditLog::with('user')->orderBy('created_at', 'desc');

            if (!empty($config['tables']) && empty($config['actions'])) {
                $query->whereIn('table_name', $config['tables']);
            } elseif (!empty($config['actions']) && !empty($config['tables'])) {
                $query->where(function ($q) use ($config) {
                    $q->whereIn('table_name', $config['tables'])
                      ->orWhereIn('action', $config['actions']);
                });
            } elseif (!empty($config['actions'])) {
                $query->whereIn('action', $config['actions']);
            }

            $params = $batch->parameters ?? [];
            if (!empty($params['date_from'])) {
                $query->whereDate('created_at', '>=', $params['date_from']);
            }
            if (!empty($params['date_to'])) {
                $query->whereDate('created_at', '<=', $params['date_to']);
            }
            if (!empty($params['target_user_id'])) {
                $query->where('user_id', $params['target_user_id']);
            }

            $headers = [
                'Log ID',
                'Timestamp',
                'Action',
                'Module / Table',
                'Record ID',
                'Actor ID',
                'Actor Name',
                'Actor Email',
                'IP Address',
                'User Agent',
                'Old Values (Snapshot)',
                'New Values (Snapshot)',
            ];
            fputcsv($fileHandle, $headers);

            $totalRecords = 0;
            $query->chunk(500, function ($logs) use ($fileHandle, &$totalRecords) {
                foreach ($logs as $log) {
                    $totalRecords++;
                    fputcsv($fileHandle, [
                        $log->id,
                        $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '—',
                        $log->action,
                        $log->table_name,
                        $log->record_id,
                        $log->user_id ?? '—',
                        $log->user?->name ?? 'System',
                        $log->user?->email ?? '—',
                        $log->ip_address ?? '—',
                        $log->user_agent ?? '—',
                        $log->old_values ? json_encode($log->old_values, JSON_UNESCAPED_UNICODE) : '—',
                        $log->new_values ? json_encode($log->new_values, JSON_UNESCAPED_UNICODE) : '—',
                    ]);
                }
            });

            fclose($fileHandle);

            $fileSize = File::exists($fullFilePath) ? File::size($fullFilePath) : 0;

            $batch->update([
                'status'        => 'completed',
                'file_path'     => $relativeFilePath,
                'file_size'     => $fileSize,
                'total_records' => $totalRecords,
                'completed_at'  => now(),
            ]);

        } catch (\Throwable $e) {
            $batch->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);
            throw $e;
        }
    }
}
