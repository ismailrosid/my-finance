<x-layouts.app title="Debts & Installments">

    <x-page-header
        title="Debts & Installments"
        subtitle="Track your loans, debts, and monthly installments."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Debts & Installments', 'url' => route('debts.index')],
        ]">
        <x-slot:actions>
            <x-button-primary size="sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add Debt
            </x-button-primary>
        </x-slot:actions>
    </x-page-header>

    {{-- Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-summary-card
            label="Total Outstanding"
            value="—"
            prefix="Rp"
            iconBg="bg-red-100"
            iconColor="text-red-600"/>
        <x-summary-card
            label="Monthly Payments"
            value="—"
            prefix="Rp"
            iconBg="bg-amber-100"
            iconColor="text-amber-600"/>
        <x-summary-card
            label="Paid This Month"
            value="—"
            prefix="Rp"
            iconBg="bg-emerald-100"
            iconColor="text-emerald-600"/>
    </div>

    {{-- Tabs --}}
    <div class="card" x-data="{ tab: 'active' }">
        <div class="card-header">
            <div class="flex items-center gap-1 border-b -mb-4 -mx-6 px-6">
                <button @click="tab = 'active'"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px"
                        :class="tab === 'active' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-700'">
                    Active
                </button>
                <button @click="tab = 'completed'"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px"
                        :class="tab === 'completed' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-700'">
                    Completed
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div x-show="tab === 'active'">
                <x-empty-state
                    title="No active debts"
                    description="Add your loans and installments to track your financial obligations."
                    action-label="Add Debt"
                    action-href="#">
                </x-empty-state>
            </div>
            <div x-show="tab === 'completed'" x-cloak>
                <x-empty-state
                    title="No completed debts"
                    description="Completed debts will appear here.">
                </x-empty-state>
            </div>
        </div>
    </div>

</x-layouts.app>

