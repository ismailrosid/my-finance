<x-layouts.app :title="__('debts.title')">
    @php
        $directionOptions = [
            'payable'    => __('debts.i_owe'),
            'receivable' => __('debts.owed_to_me'),
        ];

        $debtTypeOptions = [
            'platform_online' => __('debts.type_online_platform'),
            'bank_loan'       => __('debts.type_bank'),
            'personal'        => __('debts.type_personal'),
            'credit_card'     => __('debts.type_credit_card'),
            'other'           => __('debts.type_other'),
        ];

        $accountOptions = [];
        foreach ($accounts as $acc) {
            $accountOptions[$acc->id] = [
                'label'    => $acc->account_name,
                'sublabel' => $acc->formatted_balance,
                'badge'    => $acc->getAccountTypeLabel(),
            ];
        }
    @endphp

    <div x-data="debtManager()">

        <x-page-header
            :title="__('debts.title')"
            :subtitle="__('debts.subtitle')"
            :breadcrumbs="[
                ['label' => __('common.dashboard'), 'url' => route('dashboard')],
                ['label' => __('debts.title'), 'url' => route('debts.index')],
            ]">
            <x-slot:actions>
                <x-button variant="primary" size="sm" @click="openModal = true">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('debts.record_new') }}
                </x-button>
            </x-slot:actions>
        </x-page-header>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <x-summary-card
                :label="__('debts.total_payable')"
                :value="\App\Support\FormatHelper::number($totalPayable ?? 0)"
                prefix="Rp"
                iconBg="bg-red-100"
                iconColor="text-red-600"/>

            <x-summary-card
                :label="__('debts.total_receivable')"
                :value="\App\Support\FormatHelper::number($totalReceivable ?? 0)"
                prefix="Rp"
                iconBg="bg-emerald-100"
                iconColor="text-emerald-600"/>

            <x-summary-card
                :label="__('debts.title')"
                :value="$activeDebtsCount ?? 0"
                iconBg="bg-blue-100"
                iconColor="text-blue-600"/>
        </div>

        {{-- Main Debts List --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 space-y-4">
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-900">{{ __('debts.title') }}</h2>
                        <span class="text-xs text-gray-400">{{ count($debts) }} {{ strtolower(__('debts.title')) }}</span>
                    </div>

                    <div class="card-body p-0">
                        @if(isset($debts) && $debts->isNotEmpty())
                            <div class="divide-y divide-gray-100">
                                @foreach($debts as $debt)
                                    <div class="p-5 hover:bg-gray-50/50 transition">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <h3 class="text-sm font-semibold text-gray-900">{{ $debt->creditor_name }}</h3>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold {{ $debt->direction === 'payable' ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">
                                                        {{ $debt->direction === 'payable' ? __('debts.payable') : __('debts.receivable') }}
                                                    </span>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">
                                                        {{ $debtTypeOptions[$debt->debt_type] ?? str_replace('_', ' ', ucfirst($debt->debt_type)) }}
                                                    </span>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700">
                                                        {{ $debt->repayment_frequency_label }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="text-right shrink-0">
                                                <p class="text-xs text-gray-400">{{ __('debts.outstanding_balance') }}</p>
                                                <p class="text-base font-bold text-gray-900"><span class="privacy-blur">{{ \App\Support\FormatHelper::currency($debt->remaining_amount) }}</span></p>
                                                <p class="text-[11px] text-gray-400">{{ __('debts.total_repayment') }}: <span class="privacy-blur">{{ \App\Support\FormatHelper::currency($debt->total_amount) }}</span></p>
                                            </div>
                                        </div>

                                        {{-- Mini Table: Loan Configuration & Terms --}}
                                        <div class="mt-3.5 overflow-x-auto rounded-lg border border-gray-100 bg-gray-50/50">
                                            <table class="w-full text-xs text-left">
                                                <thead class="bg-gray-50/80 border-b border-gray-100 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                                                    <tr>
                                                        <th scope="col" class="px-3 py-2 whitespace-nowrap">{{ __('debts.due_date') }}</th>
                                                        <th scope="col" class="px-3 py-2 whitespace-nowrap">{{ __('debts.tenor') }}</th>
                                                        <th scope="col" class="px-3 py-2 whitespace-nowrap">{{ __('debts.interest_rate') }}</th>
                                                        <th scope="col" class="px-3 py-2 whitespace-nowrap">{{ __('debts.admin_fee') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-50 bg-white">
                                                    <tr class="text-gray-700">
                                                        <td class="px-3 py-2 whitespace-nowrap font-medium text-gray-900">
                                                            {{ \App\Support\FormatHelper::date($debt->due_date) }}
                                                        </td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700 font-medium">
                                                            {{ $debt->formatted_tenor }}
                                                        </td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700">
                                                            {{ $debt->formatted_interest }}
                                                        </td>
                                                        <td class="px-3 py-2 whitespace-nowrap font-medium">
                                                            <span class="{{ (float) $debt->fee_amount > 0 ? 'text-purple-700' : 'text-gray-500' }}">
                                                                {{ $debt->formatted_admin_fee }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        {{-- Progress Bar --}}
                                        <div class="mt-3">
                                            <div class="flex items-center justify-between text-[11px] text-gray-500 mb-1">
                                                <span>{{ __('budgets.progress') }}</span>
                                                <span>{{ $debt->progress_percentage }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                                <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $debt->progress_percentage }}%"></div>
                                            </div>
                                        </div>

                                        {{-- Installments Table Accordion / Preview --}}
                                        @if($debt->installments->isNotEmpty())
                                            <div class="mt-4 pt-3 border-t border-gray-100">
                                                <p class="text-xs font-semibold text-gray-700 mb-2">{{ __('debts.installment_schedule') }} ({{ $debt->installments->count() }} termin)</p>
                                                <div class="space-y-1.5">
                                                    @foreach($debt->installments as $inst)
                                                        <div class="flex items-center justify-between text-xs py-2 px-3 rounded-lg bg-gray-50 hover:bg-gray-100/70 transition">
                                                            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                                                                <span class="font-semibold text-gray-800 w-7 sm:w-8 shrink-0">#{{ $inst->installment_number }}</span>
                                                                <span class="text-gray-500 w-20 sm:w-[84px] shrink-0 whitespace-nowrap">{{ \App\Support\FormatHelper::date($inst->due_date) }}</span>
                                                                @if($inst->status === 'paid')
                                                                    <span class="w-14 text-center px-1.5 py-0.5 rounded text-[10px] bg-emerald-100 text-emerald-700 font-semibold shrink-0">{{ __('common.paid') }}</span>
                                                                @elseif($inst->isOverdue())
                                                                    <span class="w-14 text-center px-1.5 py-0.5 rounded text-[10px] bg-red-100 text-red-700 font-semibold shrink-0">{{ __('common.overdue') }}</span>
                                                                @else
                                                                    <span class="w-14 text-center px-1.5 py-0.5 rounded text-[10px] bg-amber-100 text-amber-700 font-semibold shrink-0">{{ __('common.unpaid') }}</span>
                                                                @endif
                                                            </div>
                                                            <div class="flex items-center gap-3">
                                                                <div class="text-right">
                                                                    <span class="font-bold text-gray-900 privacy-blur block">{{ \App\Support\FormatHelper::currency($inst->amount) }}</span>
                                                                    <span class="text-[10px] text-gray-400 block">
                                                                        {{ __('debts.principal_amount') }}: {{ \App\Support\FormatHelper::currency($inst->principal_amount) }}
                                                                        @if((float) $inst->interest_amount > 0)
                                                                            &bull; {{ __('debts.total_interest') }}: {{ \App\Support\FormatHelper::currency($inst->interest_amount) }}
                                                                        @endif
                                                                        @if((float) $inst->fee_amount > 0)
                                                                            &bull; {{ __('debts.admin_fee') }}: {{ \App\Support\FormatHelper::currency($inst->fee_amount) }}
                                                                        @endif
                                                                    </span>
                                                                </div>
                                                                @if($inst->status !== 'paid')
                                                                    <button
                                                                        type="button"
                                                                        @click="selectedDebtId = {{ $debt->id }}; selectedInstallment = {{ json_encode($inst) }}; payModal = true"
                                                                        class="px-2.5 py-1 text-[11px] font-semibold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition cursor-pointer">
                                                                        {{ __('debts.pay_installment') }}
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <x-empty-state
                                :title="__('debts.no_debts')"
                                :description="__('debts.subtitle')">
                            </x-empty-state>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Upcoming Payments Sidebar --}}
            <div class="space-y-4">
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-sm font-semibold text-gray-900">{{ __('dashboard.upcoming_debts') }}</h2>
                    </div>
                    <div class="card-body">
                        @if(isset($upcomingInstallments) && $upcomingInstallments->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($upcomingInstallments as $inst)
                                    <div class="p-3.5 rounded-xl border border-gray-100 bg-gray-50/60">
                                        <div class="flex items-center justify-between">
                                            <p class="text-xs font-semibold text-gray-900">{{ $inst->debt?->creditor_name }}</p>
                                            <span class="text-xs font-bold text-red-600 privacy-blur">{{ \App\Support\FormatHelper::currency($inst->amount) }}</span>
                                        </div>
                                        <p class="text-[11px] text-gray-400 mt-1">{{ __('debts.due_date') }}: {{ \App\Support\FormatHelper::date($inst->due_date) }} &bull; {{ __('debts.installment') }} #{{ $inst->installment_number }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400 py-4 text-center">{{ __('dashboard.no_upcoming_debts') }}</p>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        {{-- Modal: Record New Loan / Debt --}}
        <x-modal
            show="openModal"
            :title="__('debts.record_new')"
            :subtitle="__('debts.subtitle')"
            maxWidth="2xl">

            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot:icon>

            <form action="{{ route('debts.store') }}" method="POST" class="space-y-5" novalidate>
                @csrf

                {{-- Hidden fallback for backward compatibility --}}
                <input type="hidden" name="tenor_months" :value="computedTenorMonths">

                {{-- 1. Loan Information --}}
                <div class="space-y-3 pb-4 border-b border-gray-100">
                    <h4 class="text-xs font-bold text-gray-900">{{ __('debts.loan_information') }}</h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-form-select
                            name="direction"
                            :label="__('debts.direction')"
                            :options="$directionOptions"
                            value="payable"
                            :placeholder="__('common.select_option')"
                            :searchPlaceholder="__('common.search')"
                            required />

                        <x-form-select
                            name="debt_type"
                            :label="__('debts.debt_type')"
                            :options="$debtTypeOptions"
                            value="platform_online"
                            :placeholder="__('common.select_option')"
                            :searchPlaceholder="__('common.search')"
                            required />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-form-input
                            name="creditor_name"
                            :label="__('debts.lender_borrower')"
                            placeholder="e.g. Bank Mandiri, Kredivo, John Doe"
                            required />

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                {{ __('debts.principal_amount') }} <span class="text-red-500">*</span>
                            </label>
                            <div class="relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 text-xs font-medium">
                                    Rp
                                </div>
                                <input
                                    type="number"
                                    step="0.01"
                                    name="principal_amount"
                                    x-model.number="principal"
                                    @input="recalculate()"
                                    placeholder="0"
                                    required
                                    class="w-full h-10.5 pl-9 pr-3.5 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 transition-all duration-150 shadow-sm focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. Loan Terms --}}
                <div class="space-y-3 pb-4 border-b border-gray-100">
                    <h4 class="text-xs font-bold text-gray-900">{{ __('debts.loan_terms') }}</h4>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                {{ __('debts.tenor_value') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="number"
                                min="1"
                                name="tenor_value"
                                x-model.number="tenorValue"
                                @input="onTenorChange()"
                                required
                                class="w-full h-10.5 px-3.5 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 transition-all duration-150 shadow-sm focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                {{ __('debts.tenor_unit') }} <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select
                                    name="tenor_unit"
                                    x-model="tenorUnit"
                                    @change="onTenorChange()"
                                    class="w-full h-10.5 pl-3.5 pr-9 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 transition-all duration-150 shadow-sm appearance-none focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300">
                                    <option value="days">{{ __('debts.unit_days') }}</option>
                                    <option value="weeks">{{ __('debts.unit_weeks') }}</option>
                                    <option value="months">{{ __('debts.unit_months') }}</option>
                                    <option value="years">{{ __('debts.unit_years') }}</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                {{ __('debts.repayment_frequency') }}
                            </label>
                            <div class="relative">
                                <select
                                    name="repayment_frequency"
                                    x-model="repaymentFreq"
                                    @change="recalculate()"
                                    class="w-full h-10.5 pl-3.5 pr-9 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 transition-all duration-150 shadow-sm appearance-none focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300">
                                    <option value="monthly">{{ __('debts.frequency_monthly') }}</option>
                                    <option value="single">{{ __('debts.frequency_lump_sum') }}</option>
                                    <option value="weekly">{{ __('debts.frequency_weekly') }}</option>
                                    <option value="daily">{{ __('debts.period_daily') }}</option>
                                    <option value="annually">{{ __('debts.period_annual') }}</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                {{ __('debts.start_date') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="date"
                                name="start_date"
                                x-model="startDate"
                                @change="onStartDateChange()"
                                required
                                class="w-full h-10.5 px-3.5 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 transition-all duration-150 shadow-sm focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold text-gray-700">
                                    {{ __('debts.final_due_date') }} <span class="text-red-500">*</span>
                                </label>
                            </div>
                            <input
                                type="date"
                                name="due_date"
                                x-model="dueDate"
                                @input="manualDueDate = true; recalculate()"
                                required
                                class="w-full h-10.5 px-3.5 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 transition-all duration-150 shadow-sm focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300">
                        </div>
                    </div>
                </div>

                {{-- 3. Interest Configuration --}}
                <div class="space-y-3 pb-4 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-gray-900">{{ __('debts.interest_configuration') }}</h4>
                        <span class="text-[11px] text-gray-400">0% = {{ __('common.no') }}</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ __('debts.interest_rate') }}</label>
                            <div class="relative rounded-xl shadow-sm">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="interest_rate"
                                    x-model.number="interestRate"
                                    @input="recalculate()"
                                    placeholder="0"
                                    class="w-full h-10.5 pl-3.5 pr-8 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 transition-all duration-150 shadow-sm focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300">
                                <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400 text-xs font-medium">
                                    %
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ __('debts.interest_period') }}</label>
                            <div class="relative">
                                <select
                                    name="interest_period"
                                    x-model="interestPeriod"
                                    @change="recalculate()"
                                    class="w-full h-10.5 pl-3.5 pr-9 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 transition-all duration-150 shadow-sm appearance-none focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300">
                                    <option value="daily">{{ __('debts.period_daily') }}</option>
                                    <option value="monthly">{{ __('debts.period_monthly') }}</option>
                                    <option value="annual">{{ __('debts.period_annual') }}</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ __('debts.interest_method') }}</label>
                            <div class="relative">
                                <select
                                    name="interest_method"
                                    x-model="interestMethod"
                                    @change="recalculate()"
                                    class="w-full h-10.5 pl-3.5 pr-9 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 transition-all duration-150 shadow-sm appearance-none focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300">
                                    <option value="flat">{{ __('debts.method_flat') }}</option>
                                    <option value="effective">{{ __('debts.method_effective') }}</option>
                                    <option value="compound">{{ __('debts.method_compound') }}</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. Additional Fees (Loan Fees) --}}
                <div class="space-y-3 pb-4 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-gray-900">{{ __('debts.additional_fees') }}</h4>
                        <span class="text-[11px] text-gray-400">One-time fee</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ __('debts.fee_type') }}</label>
                            <div class="relative">
                                <select
                                    name="admin_fee_type"
                                    x-model="adminFeeType"
                                    @change="recalculate()"
                                    class="w-full h-10.5 pl-3.5 pr-9 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 transition-all duration-150 shadow-sm appearance-none focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300">
                                    <option value="fixed">{{ __('debts.fixed_amount') }} (Rp)</option>
                                    <option value="percentage">{{ __('debts.percentage') }} (%)</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                {{ __('debts.fee_value') }}
                                <span class="text-gray-400 font-normal" x-text="adminFeeType === 'percentage' ? '(%)' : '(Rp)'"></span>
                            </label>
                            <div class="relative rounded-xl shadow-sm">
                                <div x-show="adminFeeType === 'fixed'" class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 text-xs font-medium">
                                    Rp
                                </div>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="admin_fee_value"
                                    x-model.number="adminFeeValue"
                                    @input="recalculate()"
                                    placeholder="0"
                                    :class="adminFeeType === 'fixed' ? 'pl-9 pr-3.5' : 'pl-3.5 pr-8'"
                                    class="w-full h-10.5 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 transition-all duration-150 shadow-sm focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300">
                                <span x-show="adminFeeType === 'percentage'" class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400 text-xs font-medium">
                                    %
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs px-3.5 py-2 bg-purple-50/60 border border-purple-100 rounded-xl text-purple-900">
                        <span>{{ __('debts.administration_fee') }}:</span>
                        <strong class="font-bold text-purple-900" x-text="'Rp ' + formatRupiah(adminFeeAmount)"></strong>
                    </div>
                </div>

                {{-- 5. Notes / Description --}}
                <div class="space-y-1.5 pb-2">
                    <label class="block text-xs font-semibold text-gray-700">{{ __('debts.notes_description') }}</label>
                    <textarea
                        name="notes"
                        rows="2"
                        placeholder="..."
                        class="w-full px-3.5 py-2.5 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 transition-all duration-150 shadow-sm focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300"></textarea>
                </div>

                {{-- 6. Live Calculation Preview Card --}}
                <div class="bg-gray-50 border border-gray-200/80 rounded-2xl p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <h4 class="text-xs font-bold text-gray-900">{{ __('debts.live_calculation_preview') }}</h4>
                        </div>
                        <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-100/80 px-2.5 py-0.5 rounded-full" x-text="installmentsCount + 'x termin'"></span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                        <div class="p-3 bg-white rounded-xl border border-gray-100 shadow-2xs">
                            <span class="text-gray-400 block text-[10px] mb-0.5">{{ __('debts.principal_amount') }}</span>
                            <span class="font-bold text-gray-900" x-text="'Rp ' + formatRupiah(principal)"></span>
                        </div>
                        <div class="p-3 bg-white rounded-xl border border-gray-100 shadow-2xs">
                            <span class="text-gray-400 block text-[10px] mb-0.5">{{ __('debts.total_interest') }}</span>
                            <span class="font-bold text-amber-700" x-text="'Rp ' + formatRupiah(totalInterest)"></span>
                        </div>
                        <div class="p-3 bg-white rounded-xl border border-gray-100 shadow-2xs">
                            <span class="text-gray-400 block text-[10px] mb-0.5">{{ __('debts.administration_fee') }}</span>
                            <span class="font-bold text-purple-700" x-text="'Rp ' + formatRupiah(adminFeeAmount)"></span>
                        </div>
                        <div class="col-span-2 sm:col-span-1 p-3 bg-emerald-500 text-white rounded-xl shadow-2xs">
                            <span class="text-emerald-100 block text-[10px] font-medium mb-0.5">{{ __('debts.total_repayment') }}</span>
                            <span class="font-black text-sm" x-text="'Rp ' + formatRupiah(totalRepayment)"></span>
                        </div>
                        <div class="col-span-2 sm:col-span-2 p-3 bg-white rounded-xl border border-gray-100 shadow-2xs flex items-center justify-between">
                            <div>
                                <span class="text-gray-400 block text-[10px] mb-0.5">{{ __('debts.estimated_payment_amount') }}</span>
                                <span class="text-xs font-bold text-gray-900" x-text="'Rp ' + formatRupiah(estimatedPayment)"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
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

        {{-- Modal: Pay Installment --}}
        <x-modal
            show="payModal"
            :title="__('debts.pay_installment')"
            :subtitle="__('debts.pay_from_account')"
            maxWidth="md">

            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
                </svg>
            </x-slot:icon>

            <form :action="'/debts/' + selectedDebtId + '/pay/' + selectedInstallment.id" method="POST" class="space-y-4" novalidate>
                @csrf

                <x-form-select
                    name="account_id"
                    :label="__('debts.pay_from_account')"
                    :options="$accountOptions"
                    :placeholder="__('common.select_option')"
                    :searchPlaceholder="__('common.search')"
                    required />

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ __('debts.payment_amount') }} *</label>
                    <div class="relative rounded-xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 text-xs font-medium">
                            Rp
                        </div>
                        <input
                            type="number"
                            step="0.01"
                            name="amount_paid"
                            :value="selectedInstallment.amount"
                            required
                            class="w-full h-10.5 pl-9 pr-3.5 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 transition-all duration-150 shadow-sm
                                   focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300">
                    </div>
                </div>

                <x-form-input
                    name="notes"
                    :label="__('debts.notes')"
                    placeholder="..." />

                <div class="pt-4 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 sm:gap-3">
                    <x-button type="button" variant="secondary" size="md" class="w-full sm:w-auto" @click="payModal = false">
                        {{ __('common.cancel') }}
                    </x-button>
                    <x-button type="submit" variant="primary" size="md" class="w-full sm:w-auto">
                        {{ __('debts.pay_installment') }}
                    </x-button>
                </div>
            </form>
        </x-modal>

    </div>

    <script>
    function debtManager() {
        return {
            openModal: @js($errors->any()),
            payModal: false,
            selectedInstallment: {},
            selectedDebtId: null,

            principal: {{ old('principal_amount', 0) }},
            tenorValue: {{ old('tenor_value', old('tenor_months', 1)) }},
            tenorUnit: '{{ old('tenor_unit', 'months') }}',
            startDate: '{{ old('start_date', now()->toDateString()) }}',
            dueDate: '{{ old('due_date', now()->addMonth()->toDateString()) }}',
            manualDueDate: false,
            repaymentFreq: '{{ old('repayment_frequency', 'monthly') }}',
            interestRate: {{ old('interest_rate', 0) }},
            interestPeriod: '{{ old('interest_period', 'annual') }}',
            interestMethod: '{{ old('interest_method', 'flat') }}',
            adminFeeType: '{{ old('admin_fee_type', 'fixed') }}',
            adminFeeValue: {{ old('admin_fee_value', old('fee_amount', 0)) }},

            init() {
                this.updateDueDate();
            },

            onTenorChange() {
                if (this.tenorUnit === 'days' && this.tenorValue <= 31 && this.repaymentFreq === 'monthly') {
                    this.repaymentFreq = 'single';
                } else if (this.tenorUnit === 'months' && this.repaymentFreq === 'single' && this.tenorValue > 1) {
                    this.repaymentFreq = 'monthly';
                }
                this.updateDueDate();
            },

            onStartDateChange() {
                this.updateDueDate();
            },

            updateDueDate() {
                if (this.manualDueDate) return;
                if (!this.startDate) return;
                const parts = this.startDate.split('-');
                if (parts.length !== 3) return;
                const y = parseInt(parts[0], 10);
                const m = parseInt(parts[1], 10) - 1;
                const d = parseInt(parts[2], 10);
                const dt = new Date(Date.UTC(y, m, d));
                const val = parseInt(this.tenorValue, 10) || 1;

                if (this.tenorUnit === 'days') {
                    dt.setUTCDate(dt.getUTCDate() + val);
                } else if (this.tenorUnit === 'weeks') {
                    dt.setUTCDate(dt.getUTCDate() + (val * 7));
                } else if (this.tenorUnit === 'months') {
                    dt.setUTCMonth(dt.getUTCMonth() + val);
                    if (dt.getUTCDate() !== d) {
                        dt.setUTCDate(0);
                    }
                } else if (this.tenorUnit === 'years') {
                    dt.setUTCFullYear(dt.getUTCFullYear() + val);
                    if (dt.getUTCDate() !== d) {
                        dt.setUTCDate(0);
                    }
                }

                const resY = dt.getUTCFullYear();
                const resM = String(dt.getUTCMonth() + 1).padStart(2, '0');
                const resD = String(dt.getUTCDate()).padStart(2, '0');
                this.dueDate = `${resY}-${resM}-${resD}`;
            },

            recalculate() {
            },

            get computedTenorMonths() {
                const val = parseInt(this.tenorValue, 10) || 1;
                if (this.tenorUnit === 'months') return val;
                if (this.tenorUnit === 'years') return val * 12;
                if (this.tenorUnit === 'weeks') return Math.max(1, Math.round((val * 7) / 30));
                return Math.max(1, Math.round(val / 30));
            },

            get adminFeeAmount() {
                const p = parseFloat(this.principal) || 0;
                const feeVal = parseFloat(this.adminFeeValue) || 0;
                if (feeVal <= 0 || p <= 0) return 0;
                if (this.adminFeeType === 'percentage') {
                    return Math.round(p * (feeVal / 100) * 100) / 100;
                }
                return Math.round(feeVal * 100) / 100;
            },

            get durationInPeriod() {
                const val = parseInt(this.tenorValue, 10) || 1;
                const unit = this.tenorUnit;
                const period = this.interestPeriod;

                if (period === 'daily') {
                    if (unit === 'days') return val;
                    if (unit === 'weeks') return val * 7;
                    if (unit === 'years') return val * 365;
                    return val * 30;
                }
                if (period === 'monthly') {
                    if (unit === 'months') return val;
                    if (unit === 'years') return val * 12;
                    if (unit === 'weeks') return (val * 7) / 30;
                    return val / 30;
                }
                if (unit === 'years') return val;
                if (unit === 'months') return val / 12;
                if (unit === 'weeks') return (val * 7) / 365;
                return val / 365;
            },

            get installmentsCount() {
                const val = parseInt(this.tenorValue, 10) || 1;
                const unit = this.tenorUnit;
                const freq = this.repaymentFreq;

                if (freq === 'single') return 1;
                if (freq === 'daily') {
                    if (unit === 'days') return val;
                    if (unit === 'weeks') return val * 7;
                    if (unit === 'months') return val * 30;
                    return val * 365;
                }
                if (freq === 'weekly') {
                    if (unit === 'weeks') return val;
                    if (unit === 'days') return Math.max(1, Math.ceil(val / 7));
                    if (unit === 'months') return Math.max(1, Math.round((val * 30) / 7));
                    return Math.max(1, val * 52);
                }
                if (freq === 'annually') {
                    if (unit === 'years') return val;
                    return 1;
                }

                if (unit === 'months') return val;
                if (unit === 'years') return val * 12;
                if (unit === 'weeks') return Math.max(1, Math.round((val * 7) / 30));
                return Math.max(1, Math.round(val / 30));
            },

            get totalInterest() {
                const p = parseFloat(this.principal) || 0;
                const r = parseFloat(this.interestRate) || 0;
                if (p <= 0 || r <= 0) return 0;
                const dur = this.durationInPeriod;

                if (this.interestMethod === 'compound') {
                    const rDec = r / 100;
                    return Math.round((p * Math.pow(1 + rDec, dur) - p) * 100) / 100;
                }

                if (this.interestMethod === 'effective') {
                    const n = this.installmentsCount;
                    if (n === 1) return Math.round(p * (r / 100) * dur * 100) / 100;
                    const periodicRate = (r / 100) * (dur / n);
                    if (periodicRate <= 0) return 0;
                    const factor = Math.pow(1 + periodicRate, n);
                    const pmt = p * (periodicRate * factor) / (factor - 1);
                    let bal = p;
                    let totI = 0;
                    for (let k = 1; k <= n; k++) {
                        const interest = Math.round(bal * periodicRate * 100) / 100;
                        const principalPaid = (k === n) ? bal : Math.round((pmt - interest) * 100) / 100;
                        bal = Math.max(0, bal - principalPaid);
                        totI += interest;
                    }
                    return Math.round(totI * 100) / 100;
                }

                return Math.round(p * (r / 100) * dur * 100) / 100;
            },

            get totalRepayment() {
                const p = parseFloat(this.principal) || 0;
                return Math.round((p + this.totalInterest + this.adminFeeAmount) * 100) / 100;
            },

            get estimatedPayment() {
                const n = this.installmentsCount;
                if (n <= 1) return this.totalRepayment;
                return Math.round((this.totalRepayment / n) * 100) / 100;
            },

            formatRupiah(num) {
                if (typeof window.formatNumber === 'function') {
                    return window.formatNumber(Math.round(num || 0), 0);
                }
                return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Math.round(num || 0));
            }
        };
    }
    </script>
</x-layouts.app>
