<x-layouts.app title="Financial Reports">
    @php
        $monthOptions = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthOptions[$m] = DateTime::createFromFormat('!m', $m)->format('F');
        }

        $yearOptions = [];
        for ($y = now()->year - 1; $y <= now()->year + 2; $y++) {
            $yearOptions[$y] = (string) $y;
        }
    @endphp

    <x-page-header
        title="Financial Reports"
        subtitle="Cashflow analysis, income vs expenses, and financial projection."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Reports', 'url' => route('reports.index')],
        ]">
        <x-slot:actions>
            <x-button variant="outline" size="sm" data-action-loading="true" :href="route('reports.export', ['year' => $year, 'month' => $month])">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export CSV
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Period Filter --}}
    <div class="card p-4 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <span class="text-xs text-gray-400">Reporting Period</span>
            <h3 class="text-base font-bold text-gray-900">{{ DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}</h3>
        </div>
        <form method="GET" action="{{ route('reports.index') }}" class="flex items-center gap-2">
            <x-form-select
                name="month"
                :options="$monthOptions"
                :value="$month"
                size="sm"
                autoSubmit />
            <x-form-select
                name="year"
                :options="$yearOptions"
                :value="$year"
                size="sm"
                align="right"
                autoSubmit />
        </form>
    </div>

    {{-- Cashflow Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-summary-card
            label="Actual Income"
            :value="number_format($actualIncome ?? 0, 0, ',', '.')"
            prefix="Rp"
            iconBg="bg-emerald-100"
            iconColor="text-emerald-600">
            <div class="mt-2 text-xs text-gray-400">Received & cleared</div>
        </x-summary-card>

        <x-summary-card
            label="Actual Expense"
            :value="number_format($actualExpense ?? 0, 0, ',', '.')"
            prefix="Rp"
            iconBg="bg-red-100"
            iconColor="text-red-600">
            <div class="mt-2 text-xs text-gray-400">Operational spending</div>
        </x-summary-card>

        <x-summary-card
            label="Debt Payments"
            :value="number_format($debtPayments ?? 0, 0, ',', '.')"
            prefix="Rp"
            iconBg="bg-purple-100"
            iconColor="text-purple-600">
            <div class="mt-2 text-xs text-gray-400">Excluded from expenses</div>
        </x-summary-card>

        <x-summary-card
            label="Actual Cashflow"
            :value="number_format($actualCashflow ?? 0, 0, ',', '.')"
            prefix="Rp"
            iconBg="bg-blue-100"
            iconColor="text-blue-600">
            <div class="mt-2 text-xs text-gray-400">
                @if(($actualCashflow ?? 0) >= 0)
                    <span class="text-emerald-600 font-semibold">&uarr; Surplus</span>
                @else
                    <span class="text-red-600 font-semibold">&darr; Deficit</span>
                @endif
            </div>
        </x-summary-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        {{-- Cashflow Statement Table --}}
        <div class="lg:col-span-2 card">
            <div class="card-header">
                <h2 class="text-sm font-semibold text-gray-900">Cashflow Reconciliation Statement</h2>
                <p class="text-xs text-gray-400 mt-0.5">Strict multi-tier breakdown ensuring no double-counting</p>
            </div>
            <div class="card-body p-0">
                <table class="w-full text-left text-xs">
                    <tbody class="divide-y divide-gray-100">
                        <tr class="bg-emerald-50/30">
                            <td class="px-5 py-3 font-semibold text-gray-900">A. Actual Inflows (Received)</td>
                            <td class="px-5 py-3 text-right font-bold text-emerald-700">Rp {{ number_format($actualIncome, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-2.5 pl-9 text-gray-600">Expected / Pending Inflow</td>
                            <td class="px-5 py-2.5 text-right text-gray-500">Rp {{ number_format($expectedIncome, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="bg-red-50/30">
                            <td class="px-5 py-3 font-semibold text-gray-900">B. Operating Outflows (Expenses)</td>
                            <td class="px-5 py-3 text-right font-bold text-red-700">- Rp {{ number_format($actualExpense, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="bg-purple-50/30">
                            <td class="px-5 py-3 font-semibold text-gray-900">C. Debt & Liability Payments</td>
                            <td class="px-5 py-3 text-right font-bold text-purple-700">- Rp {{ number_format($debtPayments, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="bg-gray-100 font-bold">
                            <td class="px-5 py-3.5 text-gray-900">Net Actual Cashflow (A - B - C)</td>
                            <td class="px-5 py-3.5 text-right {{ $actualCashflow >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                Rp {{ number_format($actualCashflow, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="border-t-2 border-gray-200">
                            <td class="px-5 py-3.5 text-gray-700 font-semibold">Projected Cashflow (including Expected Income)</td>
                            <td class="px-5 py-3.5 text-right font-bold text-blue-700">
                                Rp {{ number_format($projectedCashflow, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Breakdown by category --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-sm font-semibold text-gray-900">Expense by Category</h2>
                <p class="text-xs text-gray-400 mt-0.5">Top spending categories</p>
            </div>
            <div class="card-body">
                @if(isset($expenseCategories) && $expenseCategories->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($expenseCategories as $ec)
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-medium text-gray-700">{{ $ec->category?->category_name ?? 'Uncategorized' }}</span>
                                    <span class="font-semibold text-gray-900">Rp {{ number_format($ec->total, 0, ',', '.') }}</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $actualExpense > 0 ? min(100, round(($ec->total / $actualExpense) * 100)) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-gray-400 py-6 text-center">No expense recorded for this period.</p>
                @endif
            </div>
        </div>

    </div>

</x-layouts.app>
