<x-layouts.app title="Dashboard">
    <x-slot:title>Dashboard</x-slot>

    <x-page-header
        title="Dashboard"
        subtitle="Welcome back! Here's your financial overview."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')]]">
        <x-slot:actions>
            <x-button variant="outline" size="sm" data-action-loading="true" :href="route('reports.export', ['year' => now()->year, 'month' => now()->month])">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">

        <x-summary-card
            label="Total Balance"
            :value="number_format($totalBalance ?? 0, 0, ',', '.')"
            prefix="Rp"
            changeType="neutral">
            <div class="mt-3 pt-3 border-t border-gray-100">
                <p class="text-xs text-gray-400">Across all active accounts</p>
            </div>
        </x-summary-card>

        <x-summary-card
            label="Income This Month"
            :value="number_format($incomeThisMonth ?? 0, 0, ',', '.')"
            prefix="Rp"
            iconBg="bg-emerald-100"
            iconColor="text-emerald-600"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\' stroke-width=\'1.75\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M7 11l5-5m0 0l5 5m-5-5v12\'/></svg>'">
            <div class="mt-3 pt-3 border-t border-gray-100">
                <p class="text-xs text-gray-400">{{ now()->format('F Y') }}</p>
            </div>
        </x-summary-card>

        <x-summary-card
            label="Expenses This Month"
            :value="number_format($expensesThisMonth ?? 0, 0, ',', '.')"
            prefix="Rp"
            iconBg="bg-red-100"
            iconColor="text-red-600"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\' stroke-width=\'1.75\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M17 13l-5 5m0 0l-5-5m5 5V6\'/></svg>'">
            <div class="mt-3 pt-3 border-t border-gray-100">
                <p class="text-xs text-gray-400">{{ now()->format('F Y') }}</p>
            </div>
        </x-summary-card>

        <x-summary-card
            label="Net Cash Flow"
            :value="number_format($netCashFlow ?? 0, 0, ',', '.')"
            prefix="Rp"
            iconBg="bg-blue-100"
            iconColor="text-blue-600"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\' stroke-width=\'1.75\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4\'/></svg>'">
            <div class="mt-3 pt-3 border-t border-gray-100">
                @if(($netCashFlow ?? 0) >= 0)
                    <x-status-badge type="success">Surplus</x-status-badge>
                @else
                    <x-status-badge type="danger">Deficit</x-status-badge>
                @endif
            </div>
        </x-summary-card>

    </div>

    {{-- Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Recent Transactions --}}
        <div class="lg:col-span-2 card">
            <div class="card-header flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Recent Transactions</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Your latest financial activity</p>
                </div>
                <x-button variant="ghost" size="sm" :href="route('transactions.index')">
                    View All
                    <svg class="w-3.5 h-3.5 shrink-0 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </x-button>
            </div>
            <div class="card-body p-0">
                @if(isset($recentTransactions) && $recentTransactions->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50/75 border-b border-gray-100 text-gray-500 font-semibold">
                                <tr>
                                    <th class="px-5 py-3">Date</th>
                                    <th class="px-5 py-3">Description</th>
                                    <th class="px-5 py-3">Category</th>
                                    <th class="px-5 py-3">Account</th>
                                    <th class="px-5 py-3 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($recentTransactions as $txn)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3.5 whitespace-nowrap text-gray-500">
                                            {{ $txn->transaction_date->format('d M Y') }}
                                        </td>
                                        <td class="px-5 py-3.5 font-medium text-gray-900">
                                            {{ $txn->description }}
                                        </td>
                                        <td class="px-5 py-3.5 whitespace-nowrap">
                                            @if($txn->category)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" style="background-color: {{ $txn->category->color }}15; color: {{ $txn->category->color }}">
                                                    {{ $txn->category->category_name }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5 whitespace-nowrap text-gray-600">
                                            {{ $txn->sourceAccount?->account_name ?? '—' }}
                                        </td>
                                        <td class="px-5 py-3.5 whitespace-nowrap text-right font-semibold {{ $txn->transaction_type === 'income' ? 'text-emerald-600' : 'text-gray-900' }}">
                                            {{ $txn->transaction_type === 'income' ? '+' : '-' }} Rp {{ number_format($txn->amount, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-empty-state
                        title="No transactions yet"
                        description="Start recording your income and expenses to see them here."
                        class="py-12">
                    </x-empty-state>
                @endif
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="space-y-4">
            {{-- Budget Overview --}}
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Budget Overview</h2>
                    <x-button variant="ghost" size="sm" :href="route('budgets.index')">
                        View
                        <svg class="w-3.5 h-3.5 shrink-0 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </x-button>
                </div>
                <div class="card-body">
                    @if(isset($budgets) && $budgets->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($budgets as $b)
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="font-medium text-gray-700">{{ $b->category?->category_name }}</span>
                                        <span class="text-gray-500">Rp {{ number_format($b->spent_amount, 0, ',', '.') }} / {{ number_format($b->amount_limit, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="h-1.5 rounded-full {{ $b->isOverBudget() ? 'bg-red-500' : ($b->isAlertThresholdReached() ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min(100, $b->usage_percentage) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-empty-state
                            title="No budgets set"
                            description="Create a budget to track your spending."
                            class="py-6">
                        </x-empty-state>
                    @endif
                </div>
            </div>

            {{-- Upcoming Debts --}}
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Upcoming Payments</h2>
                    <x-button variant="ghost" size="sm" :href="route('debts.index')">
                        View
                        <svg class="w-3.5 h-3.5 shrink-0 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </x-button>
                </div>
                <div class="card-body">
                    @if(isset($upcomingInstallments) && $upcomingInstallments->isNotEmpty())
                        <div class="divide-y divide-gray-100">
                            @foreach($upcomingInstallments as $inst)
                                <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-medium text-gray-900">{{ $inst->debt?->creditor_name }}</p>
                                        <p class="text-[11px] text-gray-400">Due: {{ $inst->due_date->format('d M Y') }}</p>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-900">
                                        Rp {{ number_format($inst->amount, 0, ',', '.') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-empty-state
                            title="No upcoming payments"
                            description="Track your debts and installments."
                            class="py-6">
                        </x-empty-state>
                    @endif
                </div>
            </div>
        </div>

    </div>
</x-layouts.app>
