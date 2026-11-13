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

    <div x-data="{ openModal: @js($errors->any()), payModal: false, selectedInstallment: {}, selectedDebtId: null }">

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
                                                </div>
                                                <p class="text-xs text-gray-400 mt-1">Due Date: {{ $debt->due_date->format('d M Y') }} &bull; Tenor: {{ $debt->tenor_months }} month(s)</p>
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
                                                <p class="text-xs font-semibold text-gray-700 mb-2">Installment Schedule</p>
                                                <div class="space-y-1.5">
                                                    @foreach($debt->installments as $inst)
                                                        <div class="flex items-center justify-between text-xs py-1.5 px-3 rounded-lg bg-gray-50">
                                                            <div class="flex items-center gap-2">
                                                                <span class="font-semibold text-gray-800">#{{ $inst->installment_number }}</span>
                                                                <span class="text-gray-400">{{ $inst->due_date->format('d M Y') }}</span>
                                                                @if($inst->status === 'paid')
                                                                    <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-100 text-emerald-700 font-semibold">Paid</span>
                                                                @elseif($inst->isOverdue())
                                                                    <span class="px-2 py-0.5 rounded text-[10px] bg-red-100 text-red-700 font-semibold">Overdue</span>
                                                                @else
                                                                    <span class="px-2 py-0.5 rounded text-[10px] bg-amber-100 text-amber-700 font-semibold">Unpaid</span>
                                                                @endif
                                                            </div>
                                                            <div class="flex items-center gap-3">
                                                                <span class="font-bold text-gray-900 privacy-blur">Rp {{ number_format($inst->amount, 0, ',', '.') }}</span>
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
            maxWidth="xl">

            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot:icon>

            <form action="{{ route('debts.store') }}" method="POST" class="space-y-4" novalidate>
                @csrf

                {{-- Row 1: Direction & Debt Type (Searchable Dropdowns) --}}
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

                {{-- Row 2: Creditor Name --}}
                <x-form-input
                    name="creditor_name"
                    label="Creditor / Platform Name"
                    placeholder="e.g. Bank Mandiri KTA, Kredivo, John Doe"
                    required />

                {{-- Row 3: Principal Amount & Interest Rate --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form-input
                        type="number"
                        step="0.01"
                        name="principal_amount"
                        label="Principal Amount"
                        prefix="Rp"
                        placeholder="0"
                        required />

                    <x-form-input
                        type="number"
                        step="0.01"
                        name="interest_rate"
                        label="Annual Interest Rate"
                        suffix="%"
                        value="0"
                        placeholder="0" />
                </div>

                {{-- Row 4: Tenor, Start Date, Due Date --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-form-input
                        type="number"
                        name="tenor_months"
                        label="Tenor (Months)"
                        suffix="Mo"
                        value="12"
                        min="1"
                        max="120"
                        required />

                    <x-form-date
                        name="start_date"
                        label="Start Date"
                        :value="now()->toDateString()"
                        required />

                    <x-form-date
                        name="due_date"
                        label="Final Due Date"
                        :value="now()->addYear()->toDateString()"
                        required />
                </div>

                {{-- Row 5: Notes --}}
                <x-form-textarea
                    name="notes"
                    label="Notes / Description"
                    rows="2"
                    placeholder="Optional notes or repayment agreement details..." />

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
</x-layouts.app>
