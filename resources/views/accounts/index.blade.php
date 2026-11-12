<x-layouts.app title="Accounts">

    <x-page-header
        title="Accounts"
        subtitle="Manage your bank accounts, e-wallets, and cash."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Accounts', 'url' => route('accounts.index')],
        ]">
        <x-slot:actions>
            <x-button-primary size="sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add Account
            </x-button-primary>
        </x-slot:actions>
    </x-page-header>

    {{-- Total Balance --}}
    <div class="card mb-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-medium">Total Net Worth</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">Rp —</p>
                <p class="text-xs text-gray-400 mt-1">Across all active accounts</p>
            </div>
            <div class="w-16 h-16 rounded-2xl bg-emerald-100 flex items-center justify-center">
                <svg class="w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Accounts Grid --}}
    <x-empty-state
        title="No accounts yet"
        description="Add your first account to start tracking your balances."
        action-label="Add Account"
        action-href="#">
        <x-slot:icon>
            <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
        </x-slot:icon>
    </x-empty-state>

</x-layouts.app>

