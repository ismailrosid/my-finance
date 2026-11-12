<x-layouts.app title="Transactions">

    <x-page-header
        title="Transactions"
        subtitle="Record and manage all your financial transactions."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Transactions', 'url' => route('transactions.index')],
        ]">
        <x-slot:actions>
            <x-button-secondary size="sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filter
            </x-button-secondary>
            <x-button-primary size="sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add Transaction
            </x-button-primary>
        </x-slot:actions>
    </x-page-header>

    {{-- Summary row --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-summary-card label="Total Income" value="—" prefix="Rp"/>
        <x-summary-card label="Total Expenses" value="—" prefix="Rp"/>
        <x-summary-card label="Net Balance" value="—" prefix="Rp"/>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-header flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex-1">
                <h2 class="text-sm font-semibold text-gray-900">All Transactions</h2>
                <p class="text-xs text-gray-400 mt-0.5">0 transactions found</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="search" placeholder="Search transactions..." class="form-input pl-9 py-2 text-xs w-52">
                </div>
                <x-form-select name="type" placeholder="All Types" :options="['income' => 'Income', 'expense' => 'Expense']" class="w-36"/>
            </div>
        </div>
        <div class="p-0">
            <x-empty-state
                title="No transactions yet"
                description="Add your first transaction to get started tracking your finances."
                action-label="Add Transaction"
                action-href="#">
            </x-empty-state>
        </div>
    </div>

</x-layouts.app>

