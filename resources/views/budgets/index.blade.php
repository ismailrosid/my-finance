<x-layouts.app title="Budgets">

    <x-page-header
        title="Budgets"
        subtitle="Set monthly spending limits and track your budget progress."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Budgets', 'url' => route('budgets.index')],
        ]">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                {{-- Period selector --}}
                <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                    <button class="px-3 py-1 text-xs font-medium rounded-md bg-white shadow-xs text-gray-900">
                        {{ now()->format('M Y') }}
                    </button>
                    <button class="p-1 text-gray-500 hover:text-gray-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <button class="p-1 text-gray-500 hover:text-gray-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
                <x-button-primary size="sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Budget
                </x-button-primary>
            </div>
        </x-slot:actions>
    </x-page-header>

    {{-- Overall Progress --}}
    <div class="card mb-6 p-6">
        <div class="flex items-center justify-between mb-3">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Budget Used</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">Rp — <span class="text-gray-400 text-base font-normal">/ Rp —</span></p>
            </div>
            <x-status-badge type="gray">0% used</x-status-badge>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2">
            <div class="bg-emerald-500 h-2 rounded-full" style="width: 0%"></div>
        </div>
        <p class="text-xs text-gray-400 mt-2">{{ now()->format('F Y') }} budget period</p>
    </div>

    {{-- Budget list --}}
    <x-empty-state
        title="No budgets set"
        description="Create spending categories and set monthly limits to control your finances."
        action-label="Create Budget"
        action-href="#">
        <x-slot:icon>
            <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
        </x-slot:icon>
    </x-empty-state>

</x-layouts.app>

