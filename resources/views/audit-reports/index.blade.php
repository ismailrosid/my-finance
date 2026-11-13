<x-layouts.app title="Audit Reports">

    <x-page-header
        title="Audit Reports"
        subtitle="Generate and download batch audit reports for financial movements, account balances, and security logs."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Audit Reports', 'url' => route('audit-reports.index')],
        ]">
    </x-page-header>

    {{-- Generate Batch Report Card --}}
    <div class="card p-5 mb-8">
        <div class="mb-4">
            <h2 class="text-sm font-bold text-gray-900">Generate New Audit Report</h2>
            <p class="text-xs text-gray-500 mt-0.5">Select a report category and configure filtering parameters to produce a downloadable CSV archive.</p>
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
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Batch Report History</h2>
                <p class="text-xs text-gray-500 mt-0.5">Securely generated CSV audit archives available for download.</p>
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

</x-layouts.app>
