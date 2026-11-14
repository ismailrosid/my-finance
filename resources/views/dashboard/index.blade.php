<x-layouts.app :title="__('dashboard.title')">
    <x-slot:title>{{ __('dashboard.title') }}</x-slot>

    <x-page-header
        :title="__('dashboard.title')"
        :subtitle="__('dashboard.welcome_back', ['name' => auth()->user()?->name ?? 'User'])"
        :breadcrumbs="[['label' => __('common.dashboard'), 'url' => route('dashboard')]]">
        <x-slot:actions>
            <x-button variant="outline" size="sm" data-action-loading="true" :href="route('reports.export', ['year' => now()->year, 'month' => now()->month])">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                {{ __('common.export') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">

        <x-summary-card
            :label="__('dashboard.total_balance')"
            :value="\App\Support\FormatHelper::number($totalBalance ?? 0)"
            prefix="Rp"
            changeType="neutral">
            <div class="mt-3 pt-3 border-t border-gray-100">
                <p class="text-xs text-gray-400">{{ __('accounts.title') }}</p>
            </div>
        </x-summary-card>

        <x-summary-card
            :label="__('dashboard.monthly_income')"
            :value="\App\Support\FormatHelper::number($incomeThisMonth ?? 0)"
            prefix="Rp"
            iconBg="bg-emerald-100"
            iconColor="text-emerald-600"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\' stroke-width=\'1.75\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M7 11l5-5m0 0l5 5m-5-5v12\'/></svg>'">
            <div class="mt-3 pt-3 border-t border-gray-100">
                <p class="text-xs text-gray-400">{{ \App\Support\FormatHelper::date(now(), 'F Y') }}</p>
            </div>
        </x-summary-card>

        <x-summary-card
            :label="__('dashboard.monthly_expenses')"
            :value="\App\Support\FormatHelper::number($expensesThisMonth ?? 0)"
            prefix="Rp"
            iconBg="bg-red-100"
            iconColor="text-red-600"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\' stroke-width=\'1.75\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M17 13l-5 5m0 0l-5-5m5 5V6\'/></svg>'">
            <div class="mt-3 pt-3 border-t border-gray-100">
                <p class="text-xs text-gray-400">{{ \App\Support\FormatHelper::date(now(), 'F Y') }}</p>
            </div>
        </x-summary-card>

        <x-summary-card
            :label="__('dashboard.cash_flow')"
            :value="\App\Support\FormatHelper::number($netCashFlow ?? 0)"
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
                    <h2 class="text-sm font-semibold text-gray-900">{{ __('dashboard.recent_transactions') }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ __('transactions.subtitle') }}</p>
                </div>
                <x-button variant="ghost" size="sm" :href="route('transactions.index')">
                    {{ __('dashboard.view_all') }}
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
                                    <th class="px-5 py-3">{{ __('transactions.date') }}</th>
                                    <th class="px-5 py-3">{{ __('transactions.description') }}</th>
                                    <th class="px-5 py-3">{{ __('transactions.category') }}</th>
                                    <th class="px-5 py-3">{{ __('transactions.account') }}</th>
                                    <th class="px-5 py-3 text-right">{{ __('transactions.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($recentTransactions as $txn)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3.5 whitespace-nowrap text-gray-500">
                                            {{ \App\Support\FormatHelper::date($txn->transaction_date) }}
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
                                            <span class="privacy-blur">{{ $txn->transaction_type === 'income' ? '+' : '-' }} {{ \App\Support\FormatHelper::currency($txn->amount) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-empty-state
                        :title="__('dashboard.no_recent_transactions')"
                        :description="__('transactions.no_transactions')"
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
                    <h2 class="text-sm font-semibold text-gray-900">{{ __('budgets.title') }}</h2>
                    <x-button variant="ghost" size="sm" :href="route('budgets.index')">
                        {{ __('common.view') }}
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
                                        <span class="text-gray-500"><span class="privacy-blur">{{ \App\Support\FormatHelper::currency($b->spent_amount) }} / {{ \App\Support\FormatHelper::number($b->amount_limit) }}</span></span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="h-1.5 rounded-full {{ $b->isOverBudget() ? 'bg-red-500' : ($b->isAlertThresholdReached() ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min(100, $b->usage_percentage) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-empty-state
                            :title="__('budgets.no_budgets')"
                            :description="__('budgets.subtitle')"
                            class="py-6">
                        </x-empty-state>
                    @endif
                </div>
            </div>

            {{-- Upcoming Debts --}}
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">{{ __('dashboard.upcoming_debts') }}</h2>
                    <x-button variant="ghost" size="sm" :href="route('debts.index')">
                        {{ __('common.view') }}
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
                                        <p class="text-[11px] text-gray-400">{{ __('debts.due_date') }}: {{ \App\Support\FormatHelper::date($inst->due_date) }}</p>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-900 privacy-blur">
                                        {{ \App\Support\FormatHelper::currency($inst->amount) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-empty-state
                            :title="__('dashboard.no_upcoming_debts')"
                            :description="__('debts.subtitle')"
                            class="py-6">
                        </x-empty-state>
                    @endif
                </div>
            </div>
        </div>

    </div>
</x-layouts.app>
