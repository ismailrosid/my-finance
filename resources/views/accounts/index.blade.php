<x-layouts.app title="Accounts">
    @php
        $accountTypeOptions = [
            'bank'        => 'Bank Account (Savings / Checking)',
            'e_wallet'    => 'E-Wallet (GoPay, OVO, DANA)',
            'cash'        => 'Cash / Physical Wallet',
            'credit_card' => 'Credit Card',
            'investment'  => 'Investment Portfolio',
            'other'       => 'Other Account',
        ];
    @endphp

    <div x-data="{ openModal: @js($errors->any()) }">

        <x-page-header
            title="Accounts"
            subtitle="Manage your bank accounts, e-wallets, and cash."
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Accounts', 'url' => route('accounts.index')],
            ]">
            <x-slot:actions>
                <x-button variant="primary" size="sm" @click="openModal = true">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Account
                </x-button>
            </x-slot:actions>
        </x-page-header>

        {{-- Total Balance Summary --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-summary-card
                label="Total Net Worth"
                :value="number_format($totalBalance ?? 0, 0, ',', '.')"
                prefix="Rp"
                changeType="neutral">
                <div class="mt-2 text-xs text-gray-400">All active accounts</div>
            </x-summary-card>

            <x-summary-card
                label="Bank Accounts"
                :value="number_format($bankBalance ?? 0, 0, ',', '.')"
                prefix="Rp"
                iconBg="bg-blue-100"
                iconColor="text-blue-600">
                <div class="mt-2 text-xs text-gray-400">Savings & checking</div>
            </x-summary-card>

            <x-summary-card
                label="E-Wallets"
                :value="number_format($walletBalance ?? 0, 0, ',', '.')"
                prefix="Rp"
                iconBg="bg-purple-100"
                iconColor="text-purple-600">
                <div class="mt-2 text-xs text-gray-400">Digital balances</div>
            </x-summary-card>

            <x-summary-card
                label="Cash"
                :value="number_format($cashBalance ?? 0, 0, ',', '.')"
                prefix="Rp"
                iconBg="bg-emerald-100"
                iconColor="text-emerald-600">
                <div class="mt-2 text-xs text-gray-400">Physical cash</div>
            </x-summary-card>
        </div>

        {{-- Accounts Grid / List --}}
        @if(isset($accounts) && $accounts->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
                @foreach($accounts as $acc)
                    <div class="card p-5 relative overflow-hidden transition hover:shadow-md border-l-4" style="border-left-color: {{ $acc->color }}">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="inline-block px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider rounded bg-gray-100 text-gray-600 mb-1">
                                    {{ $acc->getAccountTypeLabel() }}
                                </span>
                                <h3 class="text-base font-semibold text-gray-900">{{ $acc->account_name }}</h3>
                                <p class="text-xs text-gray-400">{{ $acc->bank_name ?: $acc->account_number ?: 'Standard account' }}</p>
                            </div>
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold" style="background-color: {{ $acc->color }}">
                                {{ strtoupper(substr($acc->account_name, 0, 2)) }}
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-gray-100 flex items-end justify-between">
                            <div>
                                <p class="text-xs text-gray-400">Current Balance</p>
                                <p class="text-lg font-bold text-gray-900"><span class="privacy-blur">{{ $acc->formatted_balance }}</span></p>
                            </div>
                            <div class="flex items-center gap-1">
                                <form action="{{ route('accounts.destroy', $acc->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="button"
                                        @click="$confirm({
                                            title: 'Delete this account?',
                                            description: 'This action cannot be undone. All transactions associated with this account will also be affected.',
                                            confirmText: 'Delete Account',
                                            cancelText: 'Cancel',
                                            variant: 'danger',
                                            form: $el.closest('form')
                                        })"
                                        class="p-1.5 text-gray-400 hover:text-red-600 transition rounded-lg hover:bg-gray-100 cursor-pointer"
                                        title="Delete">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <x-empty-state
                title="No accounts yet"
                description="Add your first account to start tracking your balances.">
                <x-slot:icon>
                    <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </x-slot:icon>
            </x-empty-state>
        @endif

        {{-- Modal: Add New Account --}}
        <x-modal
            show="openModal"
            title="Add New Account"
            subtitle="Register a bank account, e-wallet, or physical cash balance"
            maxWidth="md">

            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
            </x-slot:icon>

            <form action="{{ route('accounts.store') }}" method="POST" class="space-y-4" novalidate>
                @csrf

                <x-form-input
                    name="account_name"
                    label="Account Name"
                    placeholder="e.g. BCA Tabungan, GoPay, Cash Dompet"
                    required />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form-select
                        name="account_type"
                        label="Account Type"
                        :options="$accountTypeOptions"
                        value="bank"
                        placeholder="Select type..."
                        searchPlaceholder="Search type..."
                        required />

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Account Color</label>
                        <div class="flex items-center gap-2">
                            <input
                                type="color"
                                name="color"
                                value="#10B981"
                                class="h-10.5 w-14 p-1 rounded-xl border border-gray-200 cursor-pointer shadow-sm">
                            <span class="text-xs text-gray-400">Card accent</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form-input
                        name="bank_name"
                        label="Bank / Provider"
                        placeholder="e.g. Bank Central Asia" />

                    <x-form-input
                        name="account_number"
                        label="Account Number"
                        placeholder="e.g. 1234567890" />
                </div>

                <x-form-input
                    type="number"
                    step="0.01"
                    name="initial_balance"
                    label="Initial Balance"
                    prefix="Rp"
                    placeholder="0"
                    required />

                <x-form-textarea
                    name="notes"
                    label="Notes"
                    rows="2"
                    placeholder="Optional notes..." />

                <div class="pt-4 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 sm:gap-3">
                    <x-button type="button" variant="secondary" size="md" class="w-full sm:w-auto" @click="openModal = false">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="primary" size="md" class="w-full sm:w-auto">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Account
                    </x-button>
                </div>
            </form>
        </x-modal>

    </div>
</x-layouts.app>
