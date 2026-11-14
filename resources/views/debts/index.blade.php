<x-layouts.app title="Debts & Installments">
    @php
        $directionOptions = [
            'payable'    => 'I Owe (Payable / Debt)',
            'receivable' => 'Someone Owes Me (Receivable)',
        ];

        $debtTypeOptions = [
            'platform_online' => 'Platform / Pinjaman Online (Pinjol)',
            'bank_loan'       => 'Bank Loan / KPR / Kredit',
            'personal'        => 'Personal / Friends & Family',
            'credit_card'     => 'Credit Card Installment',
            'other'           => 'Other Liabilities',
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
            title="Debts & Installments"
            subtitle="Track your loans, debts, and installment payment schedules."
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Debts', 'url' => route('debts.index')],
            ]">
            <x-slot:actions>
                <x-button variant="primary" size="sm" @click="openModal = true">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Record New Loan / Debt
                </x-button>
            </x-slot:actions>
        </x-page-header>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <x-summary-card
                label="Total Outstanding Debt (Payable)"
                :value="number_format($totalPayable ?? 0, 0, ',', '.')"
                prefix="Rp"
                iconBg="bg-red-100"
                iconColor="text-red-600"/>

            <x-summary-card
                label="Total Receivables (Owed to You)"
                :value="number_format($totalReceivable ?? 0, 0, ',', '.')"
                prefix="Rp"
                iconBg="bg-emerald-100"
                iconColor="text-emerald-600"/>

            <x-summary-card
                label="Active Loans / Debts"
                :value="$activeDebtsCount ?? 0"
                iconBg="bg-blue-100"
                iconColor="text-blue-600"/>
        </div>

        {{-- Main Debts List --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 space-y-4">
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-900">Your Debts & Loans</h2>
                        <span class="text-xs text-gray-400">{{ count($debts) }} record(s)</span>
                    </div>

                    <div class="card-body p-0">
                        @if(isset($debts) && $debts->isNotEmpty())
                            <div class="divide-y divide-gray-100">
                                @foreach($debts as $debt)
                                    <div class="p-5 hover:bg-gray-50/50 transition">
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <h3 class="text-sm font-semibold text-gray-900">{{ $debt->creditor_name }}</h3>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold {{ $debt->direction === 'payable' ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">
                                                        {{ ucfirst($debt->direction) }}
                                                    </span>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">
                                                        {{ str_replace('_', ' ', ucfirst($debt->debt_type)) }}
                                                    </span>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700">
                                                        {{ $debt->repayment_frequency_label }}
                                                    </span>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                                    <span>Due: <strong class="text-gray-700">{{ $debt->due_date->format('d M Y') }}</strong></span>
                                                    <span>&bull;</span>
                                                    <span>Tenor: <strong class="text-gray-700">{{ $debt->formatted_tenor }}</strong></span>
                                                    <span>&bull;</span>
                                                    <span>Interest: <strong class="text-gray-700">{{ $debt->formatted_interest }}</strong></span>
                                                    @if((float) $debt->fee_amount > 0)
                                                        <span>&bull;</span>
                                                        <span>Admin Fee: <strong class="text-purple-700">{{ $debt->formatted_admin_fee }}</strong></span>
                                                    @endif
                                                </p>
                                            </div>

                                            <div class="text-right">
                                                <p class="text-xs text-gray-400">Remaining</p>
                                                <p class="text-base font-bold text-gray-900"><span class="privacy-blur">{{ $debt->formatted_remaining }}</span></p>
                                                <p class="text-[11px] text-gray-400">Total: <span class="privacy-blur">Rp {{ number_format($debt->total_amount, 0, ',', '.') }}</span></p>
                                            </div>
                                        </div>

                                        {{-- Progress Bar --}}
                                        <div class="mt-3">
                                            <div class="flex items-center justify-between text-[11px] text-gray-500 mb-1">
                                                <span>Repayment Progress</span>
                                                <span>{{ $debt->progress_percentage }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                                <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $debt->progress_percentage }}%"></div>
                                            </div>
                                        </div>

                                        {{-- Installments Table Accordion / Preview --}}
                                        @if($debt->installments->isNotEmpty())
                                            <div class="mt-4 pt-3 border-t border-gray-100">
                                                <p class="text-xs font-semibold text-gray-700 mb-2">Installment Schedule ({{ $debt->installments->count() }} termin)</p>
                                                <div class="space-y-1.5">
                                                    @foreach($debt->installments as $inst)
                                                        <div class="flex items-center justify-between text-xs py-2 px-3 rounded-lg bg-gray-50 hover:bg-gray-100/70 transition">
                                                            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                                                                <span class="font-semibold text-gray-800 w-7 sm:w-8 shrink-0">#{{ $inst->installment_number }}</span>
                                                                <span class="text-gray-500 w-20 sm:w-[84px] shrink-0 whitespace-nowrap">{{ $inst->due_date->format('d M Y') }}</span>
                                                                @if($inst->status === 'paid')
                                                                    <span class="w-14 text-center px-1.5 py-0.5 rounded text-[10px] bg-emerald-100 text-emerald-700 font-semibold shrink-0">Paid</span>
                                                                @elseif($inst->isOverdue())
                                                                    <span class="w-14 text-center px-1.5 py-0.5 rounded text-[10px] bg-red-100 text-red-700 font-semibold shrink-0">Overdue</span>
                                                                @else
                                                                    <span class="w-14 text-center px-1.5 py-0.5 rounded text-[10px] bg-amber-100 text-amber-700 font-semibold shrink-0">Unpaid</span>
                                                                @endif
                                                            </div>
                                                            <div class="flex items-center gap-3">
                                                                <div class="text-right">
                                                                    <span class="font-bold text-gray-900 privacy-blur block">Rp {{ number_format($inst->amount, 0, ',', '.') }}</span>
                                                                    <span class="text-[10px] text-gray-400 block">
                                                                        Pokok: Rp {{ number_format($inst->principal_amount, 0, ',', '.') }}
                                                                        @if((float) $inst->interest_amount > 0)
                                                                            &bull; Bunga: Rp {{ number_format($inst->interest_amount, 0, ',', '.') }}
                                                                        @endif
                                                                        @if((float) $inst->fee_amount > 0)
                                                                            &bull; Admin: Rp {{ number_format($inst->fee_amount, 0, ',', '.') }}
                                                                        @endif
                                                                    </span>
                                                                </div>
                                                                @if($inst->status !== 'paid')
                                                                    <button
                                                                        type="button"
                                                                        @click="selectedDebtId = {{ $debt->id }}; selectedInstallment = {{ json_encode($inst) }}; payModal = true"
                                                                        class="px-2.5 py-1 text-[11px] font-semibold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition cursor-pointer">
                                                                        Pay
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
                                title="No debts recorded"
                                description="Record your loans and installments to track repayment schedules.">
                            </x-empty-state>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Upcoming Payments Sidebar --}}
            <div class="space-y-4">
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-sm font-semibold text-gray-900">Next Upcoming Due Dates</h2>
                    </div>
                    <div class="card-body">
                        @if(isset($upcomingInstallments) && $upcomingInstallments->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($upcomingInstallments as $inst)
                                    <div class="p-3.5 rounded-xl border border-gray-100 bg-gray-50/60">
                                        <div class="flex items-center justify-between">
                                            <p class="text-xs font-semibold text-gray-900">{{ $inst->debt?->creditor_name }}</p>
                                            <span class="text-xs font-bold text-red-600 privacy-blur">Rp {{ number_format($inst->amount, 0, ',', '.') }}</span>
                                        </div>
                                        <p class="text-[11px] text-gray-400 mt-1">Due: {{ $inst->due_date->format('d M Y') }} &bull; Installment #{{ $inst->installment_number }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400 py-4 text-center">No pending upcoming installments.</p>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        {{-- Modal: Record New Loan / Debt --}}
        <x-modal
            show="openModal"
            title="Record New Loan / Debt"
            subtitle="Register personal or platform debt and generate automatic installment plans"
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
                    <h4 class="text-xs font-bold text-gray-900">Loan Information</h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-form-select
                            name="direction"
                            label="Direction"
                            :options="$directionOptions"
                            value="payable"
                            placeholder="Choose direction..."
                            searchPlaceholder="Search direction..."
                            required />

                        <x-form-select
                            name="debt_type"
                            label="Debt Type"
                            :options="$debtTypeOptions"
                            value="platform_online"
                            placeholder="Select debt type..."
                            searchPlaceholder="Search type..."
                            required />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-form-input
                            name="creditor_name"
                            label="Lender / Borrower (Creditor Name)"
                            placeholder="e.g. Bank Mandiri KTA, Kredivo, John Doe"
                            required />

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Principal Amount <span class="text-red-500">*</span>
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
                    <h4 class="text-xs font-bold text-gray-900">Loan Terms</h4>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Tenor Value <span class="text-red-500">*</span>
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
                                Tenor Unit <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select
                                    name="tenor_unit"
                                    x-model="tenorUnit"
                                    @change="onTenorChange()"
                                    class="w-full h-10.5 pl-3.5 pr-9 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 transition-all duration-150 shadow-sm appearance-none focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300">
                                    <option value="days">Days</option>
                                    <option value="weeks">Weeks</option>
                                    <option value="months">Months</option>
                                    <option value="years">Years</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Repayment Frequency
                            </label>
                            <div class="relative">
                                <select
                                    name="repayment_frequency"
                                    x-model="repaymentFreq"
                                    @change="recalculate()"
                                    class="w-full h-10.5 pl-3.5 pr-9 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 transition-all duration-150 shadow-sm appearance-none focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300">
                                    <option value="monthly">Monthly (Bulanan)</option>
                                    <option value="single">Lump Sum (Pelunasan Sekaligus)</option>
                                    <option value="weekly">Weekly (Mingguan)</option>
                                    <option value="daily">Daily (Harian)</option>
                                    <option value="annually">Annually (Tahunan)</option>
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
                                Start Date <span class="text-red-500">*</span>
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
                                    Final Due Date <span class="text-red-500">*</span>
                                </label>
                                <span class="text-[10px] text-gray-400" x-show="!manualDueDate">Otomatis dihitung</span>
                                <span class="text-[10px] text-amber-600 font-semibold" x-show="manualDueDate">Manual disesuaikan</span>
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
                        <h4 class="text-xs font-bold text-gray-900">Interest Configuration</h4>
                        <span class="text-[11px] text-gray-400">Optional (default 0%)</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Interest Rate</label>
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
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Interest Period</label>
                            <div class="relative">
                                <select
                                    name="interest_period"
                                    x-model="interestPeriod"
                                    @change="recalculate()"
                                    class="w-full h-10.5 pl-3.5 pr-9 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 transition-all duration-150 shadow-sm appearance-none focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300">
                                    <option value="daily">Daily (Harian)</option>
                                    <option value="monthly">Monthly (Bulanan)</option>
                                    <option value="annual">Annual (Tahunan)</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Interest Method</label>
                            <div class="relative">
                                <select
                                    name="interest_method"
                                    x-model="interestMethod"
                                    @change="recalculate()"
                                    class="w-full h-10.5 pl-3.5 pr-9 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 transition-all duration-150 shadow-sm appearance-none focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300">
                                    <option value="flat">Flat</option>
                                    <option value="effective">Effective / Annuity</option>
                                    <option value="compound">Compound</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Info badge when 0% interest --}}
                    <div x-show="interestRate <= 0" class="flex items-center gap-2 p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-100 text-emerald-800 text-xs">
                        <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Pinjaman tidak memiliki bunga (0%). Biaya administrasi tetap dapat dikenakan secara independen di bawah ini.</span>
                    </div>
                </div>

                {{-- 4. Additional Fees (Loan Fees) --}}
                <div class="space-y-3 pb-4 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-gray-900">Additional Fees</h4>
                        <span class="text-[11px] text-gray-400">One-time fee</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Administration Fee Type</label>
                            <div class="relative">
                                <select
                                    name="admin_fee_type"
                                    x-model="adminFeeType"
                                    @change="recalculate()"
                                    class="w-full h-10.5 pl-3.5 pr-9 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 transition-all duration-150 shadow-sm appearance-none focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300">
                                    <option value="fixed">Fixed Amount (Nominal Rp)</option>
                                    <option value="percentage">Percentage (% dari Pokok)</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Administration Fee Value
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
                        <span>Nominal Biaya Administrasi (Satu Kali):</span>
                        <strong class="font-bold text-purple-900" x-text="'Rp ' + formatRupiah(adminFeeAmount)"></strong>
                    </div>
                </div>

                {{-- 5. Notes / Description --}}
                <div class="space-y-1.5 pb-2">
                    <label class="block text-xs font-semibold text-gray-700">Notes / Agreement Details</label>
                    <textarea
                        name="notes"
                        rows="2"
                        placeholder="Optional notes or repayment agreement details..."
                        class="w-full px-3.5 py-2.5 text-xs bg-white border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 transition-all duration-150 shadow-sm focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 hover:border-gray-300"></textarea>
                </div>

                {{-- 6. Live Calculation Preview Card --}}
                <div class="bg-gray-50 border border-gray-200/80 rounded-2xl p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <h4 class="text-xs font-bold text-gray-900">Live Calculation Preview</h4>
                        </div>
                        <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-100/80 px-2.5 py-0.5 rounded-full" x-text="installmentsCount + 'x Pembayaran (' + (repaymentFreq.charAt(0).toUpperCase() + repaymentFreq.slice(1)) + ')'"></span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                        <div class="p-3 bg-white rounded-xl border border-gray-100 shadow-2xs">
                            <span class="text-gray-400 block text-[10px] mb-0.5">Principal Amount</span>
                            <span class="font-bold text-gray-900" x-text="'Rp ' + formatRupiah(principal)"></span>
                        </div>
                        <div class="p-3 bg-white rounded-xl border border-gray-100 shadow-2xs">
                            <span class="text-gray-400 block text-[10px] mb-0.5">Total Interest</span>
                            <span class="font-bold text-amber-700" x-text="'Rp ' + formatRupiah(totalInterest)"></span>
                        </div>
                        <div class="p-3 bg-white rounded-xl border border-gray-100 shadow-2xs">
                            <span class="text-gray-400 block text-[10px] mb-0.5">Administration Fee</span>
                            <span class="font-bold text-purple-700" x-text="'Rp ' + formatRupiah(adminFeeAmount)"></span>
                        </div>
                        <div class="col-span-2 sm:col-span-1 p-3 bg-emerald-500 text-white rounded-xl shadow-2xs">
                            <span class="text-emerald-100 block text-[10px] font-medium mb-0.5">Total Repayment</span>
                            <span class="font-black text-sm" x-text="'Rp ' + formatRupiah(totalRepayment)"></span>
                        </div>
                        <div class="col-span-2 sm:col-span-2 p-3 bg-white rounded-xl border border-gray-100 shadow-2xs flex items-center justify-between">
                            <div>
                                <span class="text-gray-400 block text-[10px] mb-0.5">Estimated Payment Amount</span>
                                <span class="text-xs font-bold text-gray-900" x-text="'Rp ' + formatRupiah(estimatedPayment) + ' / cicilan'"></span>
                            </div>
                            <span class="text-[10px] text-gray-400 text-right">Selisih pembulatan masuk ke cicilan terakhir</span>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="pt-4 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 sm:gap-3">
                    <x-button type="button" variant="secondary" size="md" class="w-full sm:w-auto" @click="openModal = false">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="primary" size="md" class="w-full sm:w-auto">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save & Generate Schedule
                    </x-button>
                </div>
            </form>
        </x-modal>

        {{-- Modal: Pay Installment --}}
        <x-modal
            show="payModal"
            title="Pay Installment"
            subtitle="Settle debt installment and deduct balance from account"
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
                    label="Pay From Account"
                    :options="$accountOptions"
                    placeholder="Select payment source..."
                    searchPlaceholder="Search account..."
                    required />

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Amount to Pay *</label>
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
                    label="Payment Notes"
                    placeholder="e.g. Paid via BCA Mobile Transfer..." />

                <div class="pt-4 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 sm:gap-3">
                    <x-button type="button" variant="secondary" size="md" class="w-full sm:w-auto" @click="payModal = false">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="primary" size="md" class="w-full sm:w-auto">
                        Confirm Payment
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
                // If switching to short days, suggest single repayment frequency if not manually customized
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
                        dt.setUTCDate(0); // Snap to last day of target month (e.g. Feb 28/29)
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
                // Alpine reactivity triggers computed getters
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
                // annual
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

                // Monthly default
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

                // Flat default
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
                return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Math.round(num || 0));
            }
        };
    }
    </script>
</x-layouts.app>
