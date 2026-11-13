<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ReportBatch;
use App\Models\RevisionInfo;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
        'audit_revisions' => [
            'name'        => 'Report Audit Snapshot Temporal (Shadow Tables / Envers)',
            'description' => 'Mencatat snapshot riwayat komprehensif dari tabel shadow (_aud) dengan revision_type: 0 (CREATE), 1 (UPDATE), 2 (DELETE).',
            'tables'      => ['transactions_aud', 'accounts_aud', 'debts_aud', 'budgets_aud', 'categories_aud'],
            'actions'     => [],
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

            $params = $batch->parameters ?? [];

            if ($batch->report_type === 'audit_revisions') {
                $totalRecords = $this->generateShadowAuditCsv($fileHandle, $params);
            } else {
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
                    'Field Yang Diedit (Fields Modified)',
                    'Sebelum (Before / Old Value)',
                    'Sesudah (After / New Value)',
                    'Perubahan (Before ➔ After)',
                    'IP Address',
                    'User Agent',
                ];
                fputcsv($fileHandle, $headers);

                $totalRecords = 0;
                $query->chunk(500, function ($logs) use ($fileHandle, &$totalRecords) {
                    foreach ($logs as $log) {
                        $totalRecords++;
                        $diff = self::parseDiff($log->old_values, $log->new_values, $log->action);

                        fputcsv($fileHandle, [
                            $log->id,
                            $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '—',
                            $log->action,
                            $log->table_name,
                            $log->record_id,
                            $log->user_id ?? '—',
                            $log->user?->name ?? 'System',
                            $log->user?->email ?? '—',
                            $diff['fields_changed'],
                            $diff['before_text'],
                            $diff['after_text'],
                            $diff['changes_summary'],
                            $log->ip_address ?? '—',
                            $log->user_agent ?? '—',
                        ]);
                    }
                });
            }

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

    /**
     * Generate comprehensive temporal report from Envers shadow tables (_aud).
     */
    protected function generateShadowAuditCsv($fileHandle, array $params): int
    {
        $headers = [
            'Revision ID',
            'Audit ID',
            'Timestamp',
            'Tipe Revisi (Revision Type)',
            'Tabel Shadow',
            'ID Rekaman (Record ID)',
            'Aktor ID',
            'Nama Aktor',
            'Email Aktor',
            'IP Address',
            'User Agent',
            'Ringkasan Snapshot Data',
        ];
        fputcsv($fileHandle, $headers);

        $tables = [
            'transactions_aud' => 'Transaksi',
            'accounts_aud'     => 'Akun / Rekening',
            'debts_aud'        => 'Hutang / Piutang',
            'budgets_aud'      => 'Anggaran / Budget',
            'categories_aud'   => 'Kategori',
        ];

        $totalRecords = 0;

        foreach ($tables as $table => $tableLabel) {
            $query = DB::table($table)
                ->join('revision_info', "{$table}.revision_id", '=', 'revision_info.id')
                ->leftJoin('users', 'revision_info.user_id', '=', 'users.id')
                ->select([
                    "{$table}.*",
                    'revision_info.id as rev_id',
                    'revision_info.user_id as rev_user_id',
                    'revision_info.ip_address as rev_ip_address',
                    'revision_info.user_agent as rev_user_agent',
                    'revision_info.created_at as rev_created_at',
                    'users.name as rev_user_name',
                    'users.email as rev_user_email',
                ]);

            if (!empty($params['date_from'])) {
                $query->whereDate('revision_info.created_at', '>=', $params['date_from']);
            }
            if (!empty($params['date_to'])) {
                $query->whereDate('revision_info.created_at', '<=', $params['date_to']);
            }
            if (!empty($params['target_user_id'])) {
                $query->where('revision_info.user_id', $params['target_user_id']);
            }

            $rows = $query->orderBy('revision_info.created_at', 'desc')->get();

            foreach ($rows as $row) {
                $totalRecords++;
                $typeStr = match ((int) $row->revision_type) {
                    RevisionInfo::TYPE_CREATE => '0 - CREATE (Tambah)',
                    RevisionInfo::TYPE_UPDATE => '1 - UPDATE (Ubah)',
                    RevisionInfo::TYPE_DELETE => '2 - DELETE (Hapus)',
                    default                   => "{$row->revision_type} - UNKNOWN",
                };

                $rowArray = (array) $row;
                $snapshot = array_diff_key($rowArray, array_flip([
                    'audit_id', 'revision_id', 'revision_type',
                    'rev_id', 'rev_user_id', 'rev_ip_address', 'rev_user_agent',
                    'rev_created_at', 'rev_user_name', 'rev_user_email'
                ]));

                $snapshotSummary = collect($snapshot)
                    ->filter(fn($v) => !is_null($v) && $v !== '')
                    ->map(fn($v, $k) => "{$k}: {$v}")
                    ->implode(' | ');

                fputcsv($fileHandle, [
                    $row->revision_id,
                    $row->audit_id,
                    $row->rev_created_at ?? '—',
                    $typeStr,
                    $tableLabel . " ({$table})",
                    $row->id,
                    $row->rev_user_id ?? '—',
                    $row->rev_user_name ?? 'System',
                    $row->rev_user_email ?? '—',
                    $row->rev_ip_address ?? '—',
                    $row->rev_user_agent ?? '—',
                    $snapshotSummary,
                ]);
            }
        }

        return $totalRecords;
    }

    public static function parseDiff(?array $old, ?array $new, string $action): array
    {
        $ignoredFields = ['updated_at', 'created_at', 'remember_token', 'password'];

        $cleanOld = is_array($old) ? array_diff_key($old, array_flip($ignoredFields)) : [];
        $cleanNew = is_array($new) ? array_diff_key($new, array_flip($ignoredFields)) : [];

        $fieldList = [];
        $beforeList = [];
        $afterList = [];
        $changesList = [];
        $items = [];

        $upperAction = strtoupper($action);

        if ($upperAction === 'UPDATE') {
            $allKeys = array_unique(array_merge(array_keys($cleanOld), array_keys($cleanNew)));

            foreach ($allKeys as $key) {
                $oldVal = $cleanOld[$key] ?? null;
                $newVal = $cleanNew[$key] ?? null;

                if (serialize($oldVal) !== serialize($newVal)) {
                    $oldFormatted = self::formatValue($oldVal);
                    $newFormatted = self::formatValue($newVal);

                    $fieldList[] = $key;
                    $beforeList[] = "{$key}: {$oldFormatted}";
                    $afterList[] = "{$key}: {$newFormatted}";
                    $changesList[] = "{$key}: [{$oldFormatted}] ➔ [{$newFormatted}]";

                    $items[] = [
                        'field'  => $key,
                        'before' => $oldFormatted,
                        'after'  => $newFormatted,
                    ];
                }
            }

            if (empty($fieldList)) {
                $fieldsChanged = '(No specific field changed)';
                $beforeText = '—';
                $afterText = '—';
                $changesSummary = 'Record updated (values identical or timestamps only)';
            } else {
                $fieldsChanged = implode(', ', $fieldList);
                $beforeText = implode(' | ', $beforeList);
                $afterText = implode(' | ', $afterList);
                $changesSummary = implode(' | ', $changesList);
            }

        } elseif ($upperAction === 'CREATE' || $upperAction === 'CREATE_ROLE') {
            $fieldsChanged = 'All Fields (New Entry)';
            $beforeText = '[None - New Record]';

            foreach ($cleanNew as $key => $val) {
                $valFormatted = self::formatValue($val);
                $afterList[] = "{$key}: {$valFormatted}";
                $items[] = [
                    'field'  => $key,
                    'before' => '[None]',
                    'after'  => $valFormatted,
                ];
            }

            $afterText = !empty($afterList) ? implode(' | ', $afterList) : 'Record created';
            $changesSummary = 'New record created with ' . count($cleanNew) . ' initial field(s)';

        } elseif ($upperAction === 'DELETE' || $upperAction === 'DELETE_ROLE') {
            $fieldsChanged = 'All Fields (Deleted)';
            $afterText = '[None - Record Removed]';

            foreach ($cleanOld as $key => $val) {
                $valFormatted = self::formatValue($val);
                $beforeList[] = "{$key}: {$valFormatted}";
                $items[] = [
                    'field'  => $key,
                    'before' => $valFormatted,
                    'after'  => '[Deleted]',
                ];
            }

            $beforeText = !empty($beforeList) ? implode(' | ', $beforeList) : 'Record deleted';
            $changesSummary = 'Record permanently deleted (Original state preserved)';

        } else {
            $fieldsChanged = 'Security / ' . $action;
            $beforeText = '—';
            $afterText = !empty($cleanNew) ? self::formatArrayInline($cleanNew) : 'Status: Recorded';
            $changesSummary = 'Event: ' . $action;
        }

        return [
            'fields_changed'  => $fieldsChanged,
            'before_text'     => $beforeText,
            'after_text'      => $afterText,
            'changes_summary' => $changesSummary,
            'items'           => $items,
        ];
    }

    public static function formatValue(mixed $val): string
    {
        if (is_null($val)) {
            return 'null';
        }
        if (is_bool($val)) {
            return $val ? 'true' : 'false';
        }
        if (is_array($val)) {
            return json_encode($val, JSON_UNESCAPED_UNICODE);
        }
        return (string) $val;
    }

    public static function formatArrayInline(array $arr): string
    {
        $res = [];
        foreach ($arr as $k => $v) {
            $res[] = "{$k}: " . self::formatValue($v);
        }
        return implode(' | ', $res);
    }
}
