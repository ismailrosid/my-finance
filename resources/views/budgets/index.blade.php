<x-layouts.app :title="__('budgets.title')">
    @php
        $catOptions = [];
        foreach ($categories as $cat) {
            $catOptions[$cat->id] = [
                'label' => $cat->category_name,
                'badge' => __('transactions.expense'),
            ];
        }

        $monthOptions = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthOptions[$m] = \Carbon\Carbon::create(2026, $m, 1)->locale(app()->getLocale())->translatedFormat('F');
        }

        $yearOptions = [];
        for ($y = now()->year - 1; $y <= now()->year + 2; $y++) {
            $yearOptions[$y] = (string) $y;
        }
    @endphp

    <div x-data="{ openModal: @js($errors->any()) }">

        <x-page-header
            :title="__('budgets.title')"
            :subtitle="__('budgets.subtitle')"
            :breadcrumbs="[
                ['label' => __('common.dashboard'), 'url' => route('dashboard')],
                ['label' => __('budgets.title'), 'url' => route('budgets.index')],
            ]">
            <x-slot:actions>
                <x-button variant="primary" size="sm" @click="openModal = true">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('budgets.add_budget') }}
                </x-button>
            </x-slot:actions>
        </x-page-header>

        {{-- Month Selector Bar --}}
        <div class="card p-4 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <span class="text-xs text-gray-400">{{ __('budgets.period') }}</span>
                <h3 class="text-base font-bold text-gray-900">{{ \Carbon\Carbon::create(2026, $month, 1)->locale(app()->getLocale())->translatedFormat('F') }} {{ $year }}</h3>
            </div>
            <form method="GET" action="{{ route('budgets.index') }}" class="flex items-center gap-2">
                <x-form-select
                    name="month"
                    :options="$monthOptions"
                    :value="$month"
                    size="sm"
                    autoSubmit />
                <x-form-select
                    name="year"
                    :options="$yearOptions"
                    :value="$year"
                    size="sm"
                    align="right"
                    autoSubmit />
            </form>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <x-summary-card
                :label="__('budgets.monthly_limit')"
                :value="\App\Support\FormatHelper::number($totalBudget ?? 0)"
                prefix="Rp"
                iconBg="bg-blue-100"
                iconColor="text-blue-600"/>

            <x-summary-card
                :label="__('budgets.spent')"
                :value="\App\Support\FormatHelper::number($totalSpent ?? 0)"
                prefix="Rp"
                iconBg="bg-red-100"
                iconColor="text-red-600"/>

            <x-summary-card
                :label="__('budgets.remaining')"
                :value="\App\Support\FormatHelper::number($totalRemaining ?? 0)"
                prefix="Rp"
                iconBg="bg-emerald-100"
                iconColor="text-emerald-600"/>
        </div>

        {{-- Budgets List --}}
        @if(isset($budgets) && $budgets->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
                @foreach($budgets as $b)
                    <div class="card p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <span class="inline-block px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider rounded" style="background-color: {{ $b->category?->color }}20; color: {{ $b->category?->color }}">
                                    {{ $b->category?->category_name }}
                                </span>
                                <h3 class="text-base font-bold text-gray-900 mt-1"><span class="privacy-blur">{{ \App\Support\FormatHelper::currency($b->amount_limit) }}</span></h3>
                            </div>
                            <div class="text-right">
                                @if($b->isOverBudget())
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-red-100 text-red-700 font-bold">{{ __('budgets.over_budget') }}</span>
                                @elseif($b->isAlertThresholdReached())
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-amber-100 text-amber-700 font-semibold">{{ __('common.warning') }}</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-100 text-emerald-700 font-semibold">{{ __('common.active') }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Progress Bar --}}
                        <div class="space-y-1 mb-3">
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>{{ __('budgets.spent') }}: <span class="privacy-blur">{{ \App\Support\FormatHelper::currency($b->spent_amount) }}</span></span>
                                <span class="font-semibold">{{ $b->usage_percentage }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full {{ $b->isOverBudget() ? 'bg-red-500' : ($b->isAlertThresholdReached() ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min(100, $b->usage_percentage) }}%"></div>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                            <span class="text-gray-400">{{ __('budgets.remaining') }}: <strong class="text-gray-700 font-semibold"><span class="privacy-blur">{{ \App\Support\FormatHelper::currency($b->remaining_amount) }}</span></strong></span>
                            <form action="{{ route('budgets.destroy', $b->id) }}" method="POST">
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
                                    class="text-gray-400 hover:text-red-600 transition cursor-pointer"
                                    :title="__('common.delete')">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <x-empty-state
                :title="__('budgets.no_budgets')"
                :description="__('budgets.subtitle')">
            </x-empty-state>
        @endif

        {{-- Modal: Add Monthly Budget --}}
        <x-modal
            show="openModal"
            :title="__('budgets.add_budget')"
            :subtitle="__('budgets.subtitle')"
            maxWidth="md">

            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot:icon>

            <form action="{{ route('budgets.store') }}" method="POST" class="space-y-4" novalidate>
                @csrf

                <x-form-select
                    name="category_id"
                    :label="__('budgets.category')"
                    :options="$catOptions"
                    :placeholder="__('common.select_option')"
                    :searchPlaceholder="__('common.search')"
                    required />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form-select
                        name="period_month"
                        :label="__('budgets.month')"
                        :options="$monthOptions"
                        :value="$month"
                        required />

                    <x-form-input
                        type="number"
                        name="period_year"
                        :label="__('budgets.year')"
                        :value="$year"
                        required />
                </div>

                <x-form-input
                    type="number"
                    step="0.01"
                    name="amount_limit"
                    :label="__('budgets.monthly_limit')"
                    prefix="Rp"
                    placeholder="0"
                    required />

                <x-form-input
                    type="number"
                    name="alert_threshold_percentage"
                    :label="__('budgets.threshold')"
                    suffix="%"
                    value="80"
                    min="1"
                    max="100" />

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
