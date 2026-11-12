<x-layouts.app title="Financial Reports">

    <x-page-header
        title="Financial Reports"
        subtitle="Analyze your financial health with detailed insights."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Financial Reports', 'url' => route('reports.index')],
        ]">
        <x-slot:actions>
            <x-button-secondary size="sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export PDF
            </x-button-secondary>
        </x-slot:actions>
    </x-page-header>

    {{-- Report Type Tabs --}}
    <div x-data="{ tab: 'overview' }" class="space-y-6">

        {{-- Tab Nav --}}
        <div class="flex gap-1 bg-gray-100 p-1 rounded-xl w-fit">
            @foreach (['overview' => 'Overview', 'cashflow' => 'Cash Flow', 'category' => 'By Category', 'comparison' => 'Comparison'] as $key => $label)
                <button @click="tab = '{{ $key }}'"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-all"
                        :class="tab === '{{ $key }}' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-500 hover:text-gray-700'">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Summary row --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <x-summary-card label="Total Income" value="—" prefix="Rp"/>
            <x-summary-card label="Total Expenses" value="—" prefix="Rp"/>
            <x-summary-card label="Net Savings" value="—" prefix="Rp"/>
            <x-summary-card label="Savings Rate" value="—" suffix="%"/>
        </div>

        {{-- Chart area --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-sm font-semibold text-gray-900">Financial Overview</h2>
                <p class="text-xs text-gray-400 mt-0.5">Monthly income vs expenses</p>
            </div>
            <div class="card-body">
                <x-empty-state
                    title="No data to display"
                    description="Add transactions to generate financial reports and charts."
                    class="py-12">
                    <x-slot:icon>
                        <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </x-slot:icon>
                </x-empty-state>
            </div>
        </div>

    </div>

</x-layouts.app>

