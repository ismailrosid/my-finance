<x-layouts.app :title="__('audit.title')">

    <div x-data="{ diffModal: false, activeLog: null }">

        <x-page-header
            :title="__('audit.title')"
            :subtitle="__('audit.subtitle')"
            :breadcrumbs="[
                ['label' => __('common.dashboard'), 'url' => route('dashboard')],
                ['label' => __('audit.title'), 'url' => route('audit-reports.index')],
            ]">
        </x-page-header>

        {{-- Generate Batch Report Card --}}
        <div class="card p-5 mb-8">
            <div class="mb-4">
                <h2 class="text-sm font-bold text-gray-900">Generate New Audit Report</h2>
                <p class="text-xs text-gray-500 mt-0.5">Select an audit report category and configure date filtering to produce a downloadable CSV archive containing before-and-after diff details.</p>
            </div>

            <form action="{{ route('audit-reports.generate') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    {{-- Report Type --}}
                    <div class="lg:col-span-2">
                        <label for="report_type" class="form-label">Report Category <span class="text-red-500">*</span></label>
                        <select name="report_type" id="report_type" class="form-select" required>
                            @foreach($reportTypes as $typeKey => $typeConfig)
                                <option value="{{ $typeKey }}" {{ old('report_type') === $typeKey ? 'selected' : '' }}>
                                    {{ $typeConfig['name'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('report_type')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Date From --}}
                    <div>
                        <label for="date_from" class="form-label">Date From</label>
                        <input
                            type="date"
                            name="date_from"
                            id="date_from"
                            value="{{ old('date_from', now()->startOfMonth()->toDateString()) }}"
                            class="form-input">
                        @error('date_from')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Date To --}}
                    <div>
                        <label for="date_to" class="form-label">Date To</label>
                        <input
                            type="date"
                            name="date_to"
                            id="date_to"
                            value="{{ old('date_to', now()->toDateString()) }}"
                            class="form-input">
                        @error('date_to')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end pt-2 border-t border-gray-100">
                    {{-- Filter Target User (Optional) --}}
                    <div class="lg:col-span-2">
                        <label for="target_user_id" class="form-label">Target User (Optional)</label>
                        <select name="target_user_id" id="target_user_id" class="form-select">
                            <option value="">All Users (System-wide)</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ old('target_user_id') == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }} ({{ $u->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('target_user_id')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="lg:col-span-2 flex justify-end">
                        <x-button type="submit" variant="primary" size="md" class="w-full sm:w-auto">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                            </svg>
                            Generate Batch Report
                        </x-button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Batch Report History Table Card --}}
        <div class="card mb-8">
            <div class="card-header flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Batch Report History</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Securely generated CSV audit archives containing full field diffs.</p>
                </div>
                <span class="text-xs text-gray-500 font-medium">
                    Total: {{ $batches->total() }} file(s)
                </span>
            </div>

            <div class="card-body p-0">
                @if($batches->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50/75 border-b border-gray-100 text-gray-500 font-semibold">
                                <tr>
                                    <th class="px-5 py-3.5 text-center w-12">#</th>
                                    <th class="px-5 py-3.5">Report Name</th>
                                    <th class="px-5 py-3.5">Parameters / Filter</th>
                                    <th class="px-5 py-3.5">Generated By</th>
                                    <th class="px-5 py-3.5">Started At</th>
                                    <th class="px-5 py-3.5">Completed At</th>
                                    <th class="px-5 py-3.5 text-center">Status</th>
                                    <th class="px-5 py-3.5 text-right">Records / Size</th>
                                    <th class="px-5 py-3.5 text-center w-28">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($batches as $index => $batch)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        {{-- No --}}
                                        <td class="px-5 py-3.5 text-center text-gray-400 font-medium">
                                            {{ $batches->firstItem() + $index }}
                                        </td>

                                        {{-- Report Name --}}
                                        <td class="px-5 py-3.5">
                                            <p class="font-semibold text-gray-900">{{ $batch->report_name }}</p>
                                            <span class="inline-block mt-0.5 text-[10px] font-mono text-gray-400">
                                                {{ $batch->report_type }}
                                            </span>
                                        </td>

                                        {{-- Parameters --}}
                                        <td class="px-5 py-3.5 text-gray-600">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] bg-gray-100 text-gray-700">
                                                {{ $batch->parameters_summary }}
                                            </span>
                                        </td>

                                        {{-- Generated By --}}
                                        <td class="px-5 py-3.5 text-gray-600">
                                            <p class="font-medium text-gray-900">{{ $batch->user?->name ?? 'System' }}</p>
                                            <p class="text-[10px] text-gray-400">{{ $batch->user?->email ?? '—' }}</p>
                                        </td>

                                        {{-- Started At --}}
                                        <td class="px-5 py-3.5 whitespace-nowrap text-gray-500">
                                            {{ $batch->started_at ? $batch->started_at->format('d M Y, H:i') : ($batch->created_at ? $batch->created_at->format('d M Y, H:i') : '—') }}
                                        </td>

                                        {{-- Completed At --}}
                                        <td class="px-5 py-3.5 whitespace-nowrap text-gray-500">
                                            {{ $batch->completed_at ? $batch->completed_at->format('d M Y, H:i') : '—' }}
                                        </td>

                                        {{-- Status Badge --}}
                                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                            @if($batch->status === 'completed')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    Completed
                                                </span>
                                            @elseif($batch->status === 'processing')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 animate-pulse">
                                                    Processing
                                                </span>
                                            @elseif($batch->status === 'failed')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-red-50 text-red-700 border border-red-200" title="{{ $batch->error_message }}">
                                                    Failed
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                                                    Pending
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Records / Size --}}
                                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                            <p class="font-semibold text-gray-900">{{ number_format($batch->total_records) }} rows</p>
                                            <p class="text-[10px] text-gray-400">{{ $batch->formatted_file_size }}</p>
                                        </td>

                                        {{-- Actions --}}
                                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-1.5">
                                                {{-- Download Button --}}
                                                @if($batch->status === 'completed' && $batch->file_path)
                                                    <a
                                                        href="{{ route('audit-reports.download', $batch->id) }}"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition cursor-pointer"
                                                        title="Download CSV">
                                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                                        </svg>
                                                        CSV
                                                    </a>
                                                @endif

                                                {{-- Delete Button --}}
                                                <form action="{{ route('audit-reports.destroy', $batch->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="button"
                                                        @click="$confirm({
                                                            title: 'Delete this report batch?',
                                                            description: 'Are you sure you want to permanently delete this report archive? The CSV file will also be deleted from local storage.',
                                                            confirmText: 'Delete Report',
                                                            cancelText: 'Cancel',
                                                            variant: 'danger',
                                                            form: $el.closest('form')
                                                        })"
                                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-gray-100 rounded-lg transition cursor-pointer"
                                                        title="Delete Batch Archive">
                                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($batches->hasPages())
                        <div class="px-5 py-4 border-t border-gray-100">
                            {{ $batches->links() }}
                        </div>
                    @endif
                @else
                    <x-empty-state
                        title="No batch reports generated yet"
                        description="Select an audit report category and date range above, then click 'Generate Batch Report' to create your first report archive."
                        class="py-12">
                    </x-empty-state>
                @endif
            </div>
        </div>

        {{-- Live Audit Trail & Diff Inspector (Recent Activities) --}}
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Recent Audit Activities & Field Diffs</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Real-time inspection of fields edited (Before vs After) across system modules.</p>
                </div>
                <span class="text-xs text-gray-500 font-medium">
                    Showing latest {{ $recentLogs->count() }} activities
                </span>
            </div>

            <div class="card-body p-0">
                @if($recentLogs->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50/75 border-b border-gray-100 text-gray-500 font-semibold">
                                <tr>
                                    <th class="px-5 py-3.5">Timestamp</th>
                                    <th class="px-5 py-3.5">Actor</th>
                                    <th class="px-5 py-3.5">Action</th>
                                    <th class="px-5 py-3.5">Module / ID</th>
                                    <th class="px-5 py-3.5">Field Yang Diedit</th>
                                    <th class="px-5 py-3.5">Perubahan (Before ➔ After)</th>
                                    <th class="px-5 py-3.5 text-center w-28">Detail Diff</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($recentLogs as $log)
                                    @php
                                        $diff = $log->diff;
                                    @endphp
                                    <tr class="hover:bg-gray-50/50 transition">
                                        {{-- Timestamp --}}
                                        <td class="px-5 py-3.5 whitespace-nowrap text-gray-500">
                                            {{ $log->created_at ? $log->created_at->format('d M Y, H:i:s') : '—' }}
                                        </td>

                                        {{-- Actor --}}
                                        <td class="px-5 py-3.5 whitespace-nowrap">
                                            <p class="font-medium text-gray-900">{{ $log->user?->name ?? 'System' }}</p>
                                            <p class="text-[10px] text-gray-400">{{ $log->user?->email ?? '—' }}</p>
                                        </td>

                                        {{-- Action Badge --}}
                                        <td class="px-5 py-3.5 whitespace-nowrap">
                                            @if($log->action === 'CREATE' || $log->action === 'CREATE_ROLE')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700">
                                                    {{ $log->action }}
                                                </span>
                                            @elseif($log->action === 'UPDATE')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700">
                                                    {{ $log->action }}
                                                </span>
                                            @elseif($log->action === 'DELETE' || $log->action === 'DELETE_ROLE')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-red-50 text-red-700">
                                                    {{ $log->action }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700">
                                                    {{ $log->action }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Module / ID --}}
                                        <td class="px-5 py-3.5 whitespace-nowrap">
                                            <span class="font-mono text-xs font-semibold text-gray-800">{{ $log->table_name }}</span>
                                            <span class="text-[10px] text-gray-400 block font-mono">#{{ $log->record_id }}</span>
                                        </td>

                                        {{-- Fields Modified --}}
                                        <td class="px-5 py-3.5 max-w-[200px]">
                                            @if($log->action === 'UPDATE' && !empty($diff['items']))
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($diff['items'] as $item)
                                                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-mono font-medium bg-amber-50 text-amber-800 border border-amber-200">
                                                            {{ $item['field'] }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-600">{{ $diff['fields_changed'] }}</span>
                                            @endif
                                        </td>

                                        {{-- Change Summary --}}
                                        <td class="px-5 py-3.5 max-w-[280px]">
                                            <p class="text-xs text-gray-700 line-clamp-2" title="{{ $diff['changes_summary'] }}">
                                                {{ $diff['changes_summary'] }}
                                            </p>
                                        </td>

                                        {{-- Detail Button --}}
                                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                            <button
                                                type="button"
                                                @click="activeLog = {{ json_encode([
                                                    'id' => $log->id,
                                                    'action' => $log->action,
                                                    'table' => $log->table_name,
                                                    'record_id' => $log->record_id,
                                                    'actor' => $log->user?->name ?? 'System',
                                                    'email' => $log->user?->email ?? '—',
                                                    'timestamp' => $log->created_at ? $log->created_at->format('d M Y, H:i:s') : '—',
                                                    'ip' => $log->ip_address ?? '—',
                                                    'items' => $diff['items'],
                                                    'before_text' => $diff['before_text'],
                                                    'after_text' => $diff['after_text'],
                                                ]) }}; diffModal = true"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition cursor-pointer">
                                                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                                Inspect
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-empty-state
                        title="No audit logs recorded yet"
                        description="Audit activities will automatically appear here as changes occur in the system."
                        class="py-10">
                    </x-empty-state>
                @endif
            </div>
        </div>

        {{-- Modal: Diff Inspector (Before vs After) --}}
        <div
            x-show="diffModal"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;"
            x-cloak>

            {{-- Backdrop --}}
            <div
                x-show="diffModal"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="diffModal = false"
                class="fixed inset-0 bg-gray-900/50 backdrop-blur-xs transition-opacity">
            </div>

            {{-- Dialog Box --}}
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    x-show="diffModal"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">

                    {{-- Modal Header --}}
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-gray-200 text-gray-800" x-text="activeLog?.action"></span>
                                <h3 class="text-sm font-bold text-gray-900">
                                    <span x-text="activeLog?.table"></span> #<span x-text="activeLog?.record_id"></span>
                                </h3>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                Actor: <strong class="text-gray-700" x-text="activeLog?.actor"></strong> (<span x-text="activeLog?.email"></span>) &bull; <span x-text="activeLog?.timestamp"></span> &bull; IP: <span class="font-mono text-[11px]" x-text="activeLog?.ip"></span>
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="diffModal = false"
                            class="p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Modal Body: Field Diff Comparison Table --}}
                    <div class="p-6 max-h-[60vh] overflow-y-auto">
                        <template x-if="activeLog?.items && activeLog.items.length > 0">
                            <div class="overflow-x-auto rounded-xl border border-gray-200">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-gray-50 text-gray-500 font-semibold border-b border-gray-200">
                                        <tr>
                                            <th class="px-4 py-2.5 w-1/3">Field Name</th>
                                            <th class="px-4 py-2.5 w-1/3 bg-red-50/50 text-red-700">Before (Nilai Sebelum)</th>
                                            <th class="px-4 py-2.5 w-1/3 bg-emerald-50/50 text-emerald-700">After (Nilai Sesudah)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(item, idx) in activeLog.items" :key="idx">
                                            <tr class="hover:bg-gray-50/50">
                                                <td class="px-4 py-3 font-mono font-semibold text-gray-800" x-text="item.field"></td>
                                                <td class="px-4 py-3 bg-red-50/30 text-red-700 font-mono text-[11px] break-all" x-text="item.before"></td>
                                                <td class="px-4 py-3 bg-emerald-50/30 text-emerald-700 font-mono text-[11px] break-all" x-text="item.after"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>

                        <template x-if="!activeLog?.items || activeLog.items.length === 0">
                            <div class="space-y-3">
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 mb-1">Before (Nilai Sebelum):</p>
                                    <div class="p-3 bg-gray-50 rounded-lg text-xs font-mono text-gray-700 break-all" x-text="activeLog?.before_text || '—'"></div>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 mb-1">After (Nilai Sesudah):</p>
                                    <div class="p-3 bg-gray-50 rounded-lg text-xs font-mono text-gray-700 break-all" x-text="activeLog?.after_text || '—'"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-6 py-3.5 bg-gray-50 border-t border-gray-100 flex justify-end">
                        <button
                            type="button"
                            @click="diffModal = false"
                            class="btn btn-secondary btn-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

</x-layouts.app>
