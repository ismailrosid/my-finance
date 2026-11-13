<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ReportBatch;
use App\Models\User;
use App\Services\AuditReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditReportController extends Controller
{
    public function index(Request $request)
    {
        $reportTypes = AuditReportService::getReportTypes();
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        $batches = ReportBatch::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $recentLogs = AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->take(15)
            ->get();

        return view('audit-reports.index', compact('reportTypes', 'users', 'batches', 'recentLogs'));
    }

    public function generate(Request $request, AuditReportService $service): RedirectResponse
    {
        $validTypes = implode(',', array_keys(AuditReportService::REPORT_TYPES));

        $validated = $request->validate([
            'report_type'    => "required|string|in:{$validTypes}",
            'date_from'      => 'nullable|date',
            'date_to'        => 'nullable|date|after_or_equal:date_from',
            'target_user_id' => 'nullable|exists:users,id',
        ]);

        $config = AuditReportService::REPORT_TYPES[$validated['report_type']];
        $targetUserName = null;
        if (!empty($validated['target_user_id'])) {
            $targetUserName = User::find($validated['target_user_id'])?->name;
        }

        $batch = ReportBatch::create([
            'user_id'       => $this->getUserId(),
            'report_type'   => $validated['report_type'],
            'report_name'   => $config['name'],
            'parameters'    => [
                'date_from'        => $validated['date_from'] ?? null,
                'date_to'          => $validated['date_to'] ?? null,
                'target_user_id'   => $validated['target_user_id'] ?? null,
                'target_user_name' => $targetUserName,
            ],
            'status'        => 'pending',
        ]);

        try {
            $service->generate($batch);

            return redirect()
                ->route('audit-reports.index')
                ->with('success', "{$config['name']} batch generated successfully ({$batch->total_records} records).");
        } catch (\Throwable $e) {
            return redirect()
                ->route('audit-reports.index')
                ->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    public function download(int $id): StreamedResponse
    {
        $batch = ReportBatch::findOrFail($id);

        if (!$batch->file_path || !Storage::disk('local')->exists($batch->file_path)) {
            abort(404, 'Report CSV file not found or has been removed.');
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $batch->report_name);
        $downloadName = $safeName . '_' . ($batch->created_at ? $batch->created_at->format('Ymd_His') : date('Ymd')) . '.csv';

        return Storage::disk('local')->download($batch->file_path, $downloadName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        $batch = ReportBatch::findOrFail($id);

        if ($batch->file_path && Storage::disk('local')->exists($batch->file_path)) {
            Storage::disk('local')->delete($batch->file_path);
        }

        $batch->delete();

        return redirect()
            ->route('audit-reports.index')
            ->with('success', 'Report batch archive deleted successfully.');
    }
}
