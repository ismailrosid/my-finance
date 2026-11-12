<x-layouts.app title="Dashboard">
    <x-slot:title>Dashboard</x-slot>

    <x-page-header
        title="Dashboard"
        subtitle="Welcome back! Here's your financial overview."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')]]">
        <x-slot:actions>
            <x-button-secondary size="sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export
            </x-button-secondary>
            <x-button-primary size="sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add Transaction
            </x-button-primary>
        </x-slot:actions>
    </x-page-header>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">

        <x-summary-card
            label="Total Balance"
            value="—"
            prefix="Rp"
            changeType="neutral">
            <div class="mt-3 pt-3 border-t border-gray-100">
                <p class="text-xs text-gray-400">Across all accounts</p>
            </div>
        </x-summary-card>

        <x-summary-card
            label="Income This Month"
            value="—"
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
            value="—"
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
            value="—"
            prefix="Rp"
            iconBg="bg-blue-100"
            iconColor="text-blue-600"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\' stroke-width=\'1.75\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4\'/></svg>'">
            <div class="mt-3 pt-3 border-t border-gray-100">
                <x-status-badge type="gray">Surplus / Deficit</x-status-badge>
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
                <x-button-secondary size="sm" :href="route('transactions.index')">View All</x-button-secondary>
            </div>
            <div class="card-body p-0">
                <x-empty-state
                    title="No transactions yet"
                    description="Start recording your income and expenses to see them here."
                    class="py-12">
                </x-empty-state>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="space-y-4">
            {{-- Budget Overview --}}
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Budget Overview</h2>
                    <x-button-secondary size="sm" :href="route('budgets.index')">View</x-button-secondary>
                </div>
                <div class="card-body">
                    <x-empty-state
                        title="No budgets set"
                        description="Create a budget to track your spending."
                        class="py-6">
                    </x-empty-state>
                </div>
            </div>

            {{-- Upcoming Debts --}}
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Upcoming Payments</h2>
                    <x-button-secondary size="sm" :href="route('debts.index')">View</x-button-secondary>
                </div>
                <div class="card-body">
                    <x-empty-state
                        title="No upcoming payments"
                        description="Track your debts and installments."
                        class="py-6">
                    </x-empty-state>
                </div>
            </div>
        </div>

    </div>
</x-layouts.app>

