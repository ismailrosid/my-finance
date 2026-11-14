<x-layouts.app :title="__('transactions.title')">
    @php
        $typeFilterOptions = [
            ''             => __('transactions.all_types'),
            'income'       => __('transactions.income'),
            'expense'      => __('transactions.expense'),
            'transfer'     => __('transactions.transfer'),
            'debt_payment' => __('debts.pay_installment'),
        ];

        $accountFilterOptions = [
            '' => __('transactions.all_accounts'),
        ];
        foreach ($accounts as $acc) {
            $accountFilterOptions[$acc->id] = $acc->account_name;
        }

        $accountOptions = [];
        foreach ($accounts as $acc) {
            $accountOptions[$acc->id] = [
                'label'    => $acc->account_name,
                'sublabel' => $acc->formatted_balance,
                'badge'    => $acc->getAccountTypeLabel(),
            ];
        }

        $categoryOptions = [];
        foreach ($categories as $cat) {
            $categoryOptions[$cat->id] = [
                'label' => $cat->category_name,
                'badge' => $cat->category_type === 'income' ? __('transactions.income') : __('transactions.expense'),
            ];
        }
    @endphp

    <div x-data="{ openModal: @js($errors->any()), txnType: @js(old('transaction_type', 'expense')) }" @modal-closed="txnType = 'expense'">

        <x-page-header
            :title="__('transactions.title')"
            :subtitle="__('transactions.subtitle')"
            :breadcrumbs="[
                ['label' => __('common.dashboard'), 'url' => route('dashboard')],
                ['label' => __('transactions.title'), 'url' => route('transactions.index')],
            ]">
            <x-slot:actions>
                <x-button variant="primary" size="sm" @click="txnType = 'expense'; openModal = true">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('transactions.add_transaction') }}
                </x-button>
            </x-slot:actions>
        </x-page-header>

        {{-- Summary row --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <x-summary-card
                :label="__('transactions.total_income')"
                :value="\App\Support\FormatHelper::number($totalIncome ?? 0)"
                prefix="Rp"
                iconBg="bg-emerald-100"
                iconColor="text-emerald-600"/>

            <x-summary-card
                :label="__('transactions.total_expense')"
                :value="\App\Support\FormatHelper::number($totalExpense ?? 0)"
                prefix="Rp"
                iconBg="bg-red-100"
                iconColor="text-red-600"/>

            <x-summary-card
                :label="__('transactions.net_balance')"
                :value="\App\Support\FormatHelper::number($netBalance ?? 0)"
                prefix="Rp"
                iconBg="bg-blue-100"
                iconColor="text-blue-600"/>
        </div>

        {{-- Transactions Table Card --}}
        <div class="card">
            {{-- Filter Bar --}}
            <form method="GET" action="{{ route('transactions.index') }}" class="card-header flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">{{ __('transactions.title') }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $transactions->total() }} {{ strtolower(__('transactions.title')) }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('transactions.search_placeholder') }}" class="form-input pl-9 py-1.5 text-xs w-44 sm:w-48">
                    </div>

                    <x-form-select
                        name="type"
                        :options="$typeFilterOptions"
                        :value="request('type', '')"
                        :placeholder="__('transactions.all_types')"
                        size="sm"
                        autoSubmit />

                    <x-form-select
                        name="account_id"
                        :options="$accountFilterOptions"
                        :value="request('account_id', '')"
                        :placeholder="__('transactions.all_accounts')"
                        size="sm"
                        align="right"
                        autoSubmit />

                    @if(request()->hasAny(['search', 'type', 'account_id']))
                        <x-button variant="ghost" size="sm" :href="route('transactions.index')">{{ __('common.reset') }}</x-button>
                    @endif
                </div>
            </form>

            <div class="card-body p-0">
                @if(isset($transactions) && $transactions->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50/75 border-b border-gray-100 text-gray-500 font-semibold">
                                <tr>
                                    <th class="px-5 py-3">{{ __('transactions.date') }}</th>
                                    <th class="px-5 py-3">Code / Ref</th>
                                    <th class="px-5 py-3">{{ __('transactions.type') }}</th>
                                    <th class="px-5 py-3">{{ __('transactions.description') }}</th>
                                    <th class="px-5 py-3">{{ __('transactions.category') }}</th>
                                    <th class="px-5 py-3">{{ __('transactions.account') }}</th>
                                    <th class="px-5 py-3 text-right">{{ __('transactions.amount') }}</th>
                                    <th class="px-5 py-3 text-center">{{ __('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($transactions as $txn)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3.5 whitespace-nowrap text-gray-500">
                                            {{ \App\Support\FormatHelper::date($txn->transaction_date) }}
                                        </td>
                                        <td class="px-5 py-3.5 whitespace-nowrap font-mono text-[11px] text-gray-400">
                                            {{ $txn->transaction_code }}
                                        </td>
                                        <td class="px-5 py-3.5 whitespace-nowrap">
                                            @if($txn->transaction_type === 'income')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700">{{ __('transactions.income') }}</span>
                                            @elseif($txn->transaction_type === 'expense')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-red-50 text-red-700">{{ __('transactions.expense') }}</span>
                                            @elseif($txn->transaction_type === 'transfer')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700">{{ __('transactions.transfer') }}</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-700">{{ __('debts.pay_installment') }}</span>
                                            @endif
                                            @if($txn->is_expected)
                                                <span class="inline-flex items-center ml-1 px-1.5 py-0.5 rounded text-[9px] bg-amber-50 text-amber-700">{{ __('reports.expected_vs_actual') }}</span>
                                            @endif
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
                                            @if($txn->transaction_type === 'transfer')
                                                {{ $txn->sourceAccount?->account_name }} &rarr; {{ $txn->destinationAccount?->account_name }}
                                            @else
                                                {{ $txn->sourceAccount?->account_name ?? '—' }}
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5 whitespace-nowrap text-right font-semibold {{ $txn->transaction_type === 'income' ? 'text-emerald-600' : ($txn->transaction_type === 'expense' ? 'text-red-600' : 'text-gray-900') }}">
                                            <span class="privacy-blur">{{ $txn->transaction_type === 'income' ? '+' : '-' }} {{ \App\Support\FormatHelper::currency($txn->amount) }}</span>
                                        </td>
                                        <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                            <form action="{{ route('transactions.destroy', $txn->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="button"
                                                    @click="$confirm({
                                                        title: @js(__('common.confirm_delete_title')),
                                                        description: @js(__('common.confirm_delete_message')),
                                                        confirmText: @js(__('common.delete')),
                                                        cancelText: @js(__('common.cancel')),
                                                        variant: 'danger',
                                                        form: $el.closest('form')
                                                    })"
                                                    class="p-1 text-gray-400 hover:text-red-600 transition rounded hover:bg-gray-100 cursor-pointer"
                                                    :title="__('common.delete')">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if($transactions->hasPages())
                        <div class="px-5 py-4 border-t border-gray-100">
                            {{ $transactions->links() }}
                        </div>
                    @endif
                @else
                    <x-empty-state
                        :title="__('transactions.no_transactions')"
                        :description="__('transactions.subtitle')"
                        @click.prevent="txnType = 'expense'; openModal = true">
                    </x-empty-state>
                @endif
            </div>
        </div>

        {{-- Modal: Record Transaction --}}
        <x-modal
            show="openModal"
            :title="__('transactions.add_transaction')"
            :subtitle="__('transactions.subtitle')"
            maxWidth="lg">

            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot:icon>

            <form action="{{ route('transactions.store') }}" method="POST" class="space-y-4" novalidate>
                @csrf

                {{-- Type selector tabs --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ __('transactions.type') }}</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            type="button"
                            @click="txnType = 'expense'"
                            :class="txnType === 'expense' ? 'bg-red-50 text-red-700 border-red-200 font-bold shadow-sm' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'"
                            class="py-2.5 px-3 text-xs border rounded-xl text-center transition cursor-pointer">
                            {{ __('transactions.expense') }}
                        </button>
                        <button
                            type="button"
                            @click="txnType = 'income'"
                            :class="txnType === 'income' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold shadow-sm' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'"
                            class="py-2.5 px-3 text-xs border rounded-xl text-center transition cursor-pointer">
                            {{ __('transactions.income') }}
                        </button>
                        <button
                            type="button"
                            @click="txnType = 'transfer'"
                            :class="txnType === 'transfer' ? 'bg-blue-50 text-blue-700 border-blue-200 font-bold shadow-sm' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'"
                            class="py-2.5 px-3 text-xs border rounded-xl text-center transition cursor-pointer">
                            {{ __('transactions.transfer') }}
                        </button>
                    </div>
                    <input type="hidden" name="transaction_type" :value="txnType">
                </div>

                {{-- Amount & Date --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form-input
                        type="number"
                        step="0.01"
                        name="amount"
                        :label="__('transactions.amount')"
                        prefix="Rp"
                        placeholder="0"
                        required />

                    <x-form-date
                        name="transaction_date"
                        :label="__('transactions.date')"
                        :value="now()->toDateString()"
                        required />
                </div>

                {{-- Description --}}
                <x-form-input
                    name="description"
                    :label="__('transactions.description')"
                    placeholder="e.g. Lunch, Salary, Rent"
                    required />

                {{-- Account(s) & Category --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-form-select
                            name="source_account_id"
                            :label="__('transactions.account')"
                            :options="$accountOptions"
                            :placeholder="__('common.select_option')"
                            :searchPlaceholder="__('common.search')"
                            required />
                    </div>

                    <div x-show="txnType === 'transfer'">
                        <x-form-select
                            name="destination_account_id"
                            :label="__('transactions.to_account')"
                            :options="$accountOptions"
                            :placeholder="__('common.select_option')"
                            :searchPlaceholder="__('common.search')" />
                    </div>

                    <div x-show="txnType !== 'transfer'">
                        <x-form-select
                            name="category_id"
                            :label="__('transactions.category')"
                            :options="$categoryOptions"
                            :placeholder="__('common.select_option')"
                            :searchPlaceholder="__('common.search')" />
                    </div>
                </div>

                {{-- Expected checkbox --}}
                <div class="flex items-center gap-2 pt-1">
                    <input
                        type="checkbox"
                        name="is_expected"
                        value="1"
                        id="is_expected"
                        class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                    <label for="is_expected" class="text-xs text-gray-600 select-none cursor-pointer">
                        {{ __('reports.expected_vs_actual') }}
                    </label>
                </div>

                {{-- Footer actions --}}
                <div class="pt-4 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 sm:gap-3">
                    <x-button type="button" variant="secondary" size="md" class="w-full sm:w-auto" @click="openModal = false">
                        {{ __('common.cancel') }}
                    </x-button>
                    <x-button type="submit" variant="primary" size="md" class="w-full sm:w-auto">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ __('common.save') }}
                    </x-button>
                </div>
            </form>
        </x-modal>

    </div>
</x-layouts.app>
