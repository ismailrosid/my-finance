<x-layouts.app :title="__('settings.title')">
    @php
        $catTypeOptions = [
            'expense' => __('transactions.expense'),
            'income'  => __('transactions.income'),
        ];

        $catErrors = $errors->hasAny(['category_name', 'category_type', 'color', 'icon']);
        $secErrors = $errors->hasAny(['current_password', 'password', 'password_confirmation']);
        $profileErrors = $errors->hasAny(['name', 'phone_number']);

        if ($catErrors) {
            $initialTab = 'categories';
        } elseif ($secErrors) {
            $initialTab = 'security';
        } elseif ($profileErrors) {
            $initialTab = 'profile';
        } else {
            $initialTab = request()->query('tab', session('active_tab', 'profile'));
        }

        if (!in_array($initialTab, ['profile', 'categories', 'security'])) {
            $initialTab = 'profile';
        }
    @endphp

    <div x-data="{
        activeTab: @js($initialTab),
        openCatModal: @js($catErrors),
        setTab(tab) {
            this.activeTab = tab;
            if (window.history && window.history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url);
            }
        }
    }">

        <x-page-header
            :title="__('settings.title')"
            :subtitle="__('settings.subtitle')"
            :breadcrumbs="[
                ['label' => __('common.dashboard'), 'url' => route('dashboard')],
                ['label' => __('settings.title'), 'url' => route('settings.index')],
            ]">
        </x-page-header>

        {{-- Tabs --}}
        <div class="border-b border-gray-200 mb-6">
            <nav class="flex space-x-6 text-sm font-medium">
                <button
                    @click="setTab('profile')"
                    :class="activeTab === 'profile' ? 'border-emerald-600 text-emerald-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-1 border-b-2 transition cursor-pointer">
                    {{ __('settings.profile_settings') }}
                </button>
                <button
                    @click="setTab('categories')"
                    :class="activeTab === 'categories' ? 'border-emerald-600 text-emerald-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-1 border-b-2 transition cursor-pointer">
                    {{ __('settings.categories') }}
                </button>
                <button
                    @click="setTab('security')"
                    :class="activeTab === 'security' ? 'border-emerald-600 text-emerald-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-1 border-b-2 transition cursor-pointer">
                    {!! __('settings.security_password') !!}
                </button>
            </nav>
        </div>

        {{-- Tab 1: Profile & Preferences --}}
        <div x-show="activeTab === 'profile'" @if($initialTab !== 'profile') x-cloak @endif class="max-w-2xl space-y-6">
            {{-- Personal Information --}}
            <div class="card p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('settings.personal_information') }}</h3>
                <form action="{{ route('settings.update-profile') }}" method="POST" class="space-y-4" novalidate>
                    @csrf
                    @method('PUT')

                    <x-form-input
                        name="name"
                        :label="__('settings.full_name')"
                        :value="old('name', $user?->name)"
                        required />

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ __('settings.email_address') }}</label>
                        <input
                            type="email"
                            value="{{ $user?->email }}"
                            disabled
                            class="w-full h-10.5 px-3.5 text-xs bg-gray-50 border border-gray-200 rounded-xl text-gray-500 cursor-not-allowed shadow-sm">
                        <p class="mt-1 text-[11px] text-gray-400">{{ __('settings.email_cannot_change') }}</p>
                    </div>

                    <x-form-input
                        name="phone_number"
                        :label="__('settings.phone_number')"
                        :value="old('phone_number', $user?->phone_number)"
                        :placeholder="__('settings.phone_placeholder')" />

                    <div class="pt-4 border-t border-gray-100 flex justify-end">
                        <x-button type="submit" variant="primary" size="md">
                            {{ __('common.save') }}
                        </x-button>
                    </div>
                </form>
            </div>

            {{-- Language Preference Card --}}
            <div class="card p-6">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('settings.language_preference') }}</h3>
                    <p class="text-xs text-gray-500 mt-1">{{ __('settings.language_description') }}</p>
                </div>

                <form action="{{ route('settings.update-locale') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                        {{-- English Option --}}
                        <label class="relative flex items-center justify-between p-3.5 rounded-xl border cursor-pointer transition {{ app()->getLocale() === 'en' ? 'border-emerald-600 bg-emerald-50/40 ring-1 ring-emerald-600' : 'border-gray-200 hover:bg-gray-50' }}">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="locale" value="en" class="text-emerald-600 focus:ring-emerald-500" {{ app()->getLocale() === 'en' ? 'checked' : '' }} onchange="this.form.submit()">
                                <div>
                                    <p class="text-xs font-bold text-gray-900">{{ __('common.language_en') }}</p>
                                    <p class="text-[11px] text-gray-400">English (US)</p>
                                </div>
                            </div>
                            @if(app()->getLocale() === 'en')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">{{ __('common.active') }}</span>
                            @endif
                        </label>

                        {{-- Indonesian Option --}}
                        <label class="relative flex items-center justify-between p-3.5 rounded-xl border cursor-pointer transition {{ app()->getLocale() === 'id' ? 'border-emerald-600 bg-emerald-50/40 ring-1 ring-emerald-600' : 'border-gray-200 hover:bg-gray-50' }}">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="locale" value="id" class="text-emerald-600 focus:ring-emerald-500" {{ app()->getLocale() === 'id' ? 'checked' : '' }} onchange="this.form.submit()">
                                <div>
                                    <p class="text-xs font-bold text-gray-900">{{ __('common.language_id') }}</p>
                                    <p class="text-[11px] text-gray-400">Bahasa Indonesia</p>
                                </div>
                            </div>
                            @if(app()->getLocale() === 'id')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">{{ __('common.active') }}</span>
                            @endif
                        </label>
                    </div>

                    <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-xs text-gray-500">{{ __('settings.current_language') }}: <strong class="text-gray-800 font-semibold">{{ app()->getLocale() === 'id' ? __('common.language_id') : __('common.language_en') }}</strong></span>
                        <x-button type="submit" variant="primary" size="sm">
                            {{ __('common.save') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tab 2: Categories --}}
        <div x-show="activeTab === 'categories'" @if($initialTab !== 'categories') x-cloak @endif class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">{{ __('settings.financial_categories') }}</h3>
                    <p class="text-xs text-gray-400">{{ __('settings.categories_subtitle') }}</p>
                </div>
                <x-button type="button" variant="primary" size="sm" @click="openCatModal = true">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('settings.new_category') }}
                </x-button>
            </div>

            <div class="card overflow-hidden">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50/75 border-b border-gray-100 text-gray-500 font-semibold">
                        <tr>
                            <th class="px-5 py-3">{{ __('settings.category_name') }}</th>
                            <th class="px-5 py-3">{{ __('settings.category_type') }}</th>
                            <th class="px-5 py-3">{{ __('settings.category_scope') }}</th>
                            <th class="px-5 py-3 text-right">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($categories as $cat)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-5 py-3.5 font-medium text-gray-900 flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $cat->color }}"></span>
                                    {{ $cat->category_name }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold {{ $cat->category_type === 'income' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $cat->category_type === 'income' ? __('transactions.income') : __('transactions.expense') }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-gray-500">
                                    @if($cat->is_system)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-gray-100 text-gray-600 font-medium">{{ __('settings.system_scope') }}</span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-blue-50 text-blue-700 font-medium">{{ __('settings.custom_scope') }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    @if(!$cat->is_system)
                                        <form action="{{ route('settings.destroy-category', $cat->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="button"
                                                @click="$confirm({
                                                    title: @js(__('common.confirm_delete_title')),
                                                    description: @js(__('settings.delete_category_confirm')),
                                                    confirmText: @js(__('common.delete')),
                                                    cancelText: @js(__('common.cancel')),
                                                    variant: 'danger',
                                                    form: $el.closest('form')
                                                })"
                                                class="text-red-500 hover:text-red-700 text-xs font-semibold cursor-pointer">
                                                {{ __('common.delete') }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-300 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Modal: Create Custom Category --}}
            <x-modal
                show="openCatModal"
                :title="__('settings.create_custom_category')"
                :subtitle="__('settings.create_category_subtitle')"
                maxWidth="md">

                <x-slot:icon>
                    <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>
                    </svg>
                </x-slot:icon>

                <form action="{{ route('settings.store-category') }}" method="POST" class="space-y-4" novalidate>
                    @csrf

                    <x-form-input
                        name="category_name"
                        :label="__('settings.category_name')"
                        placeholder="e.g. Side Hustle, Crypto Staking, Pet Care"
                        required />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-form-select
                            name="category_type"
                            :label="__('settings.category_type')"
                            :options="$catTypeOptions"
                            value="expense"
                            placeholder="{{ __('common.select_option') }}..."
                            searchPlaceholder="{{ __('common.search') }}..."
                            required />

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ __('settings.color_badge') }}</label>
                            <div class="flex items-center gap-2">
                                <input
                                    type="color"
                                    name="color"
                                    value="#10B981"
                                    class="h-10.5 w-14 p-1 rounded-xl border border-gray-200 cursor-pointer shadow-sm">
                                <span class="text-xs text-gray-400">{{ __('settings.tag_accent') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 sm:gap-3">
                        <x-button type="button" variant="secondary" size="md" class="w-full sm:w-auto" @click="openCatModal = false">
                            {{ __('common.cancel') }}
                        </x-button>
                        <x-button type="submit" variant="primary" size="md" class="w-full sm:w-auto">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ __('settings.save_category') }}
                        </x-button>
                    </div>
                </form>
            </x-modal>
        </div>

        {{-- Tab 3: Security --}}
        <div x-show="activeTab === 'security'" @if($initialTab !== 'security') x-cloak @endif class="max-w-2xl">
            <div class="card p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('settings.change_password') }}</h3>
                <form action="{{ route('settings.update-password') }}" method="POST" class="space-y-4" novalidate>
                    @csrf
                    @method('PUT')

                    <x-form-input
                        type="password"
                        name="current_password"
                        :label="__('settings.current_password')"
                        placeholder="••••••••"
                        required />

                    <x-form-input
                        type="password"
                        name="password"
                        :label="__('settings.new_password')"
                        placeholder="••••••••"
                        required />

                    <x-form-input
                        type="password"
                        name="password_confirmation"
                        :label="__('settings.confirm_new_password')"
                        placeholder="••••••••"
                        required />

                    <div class="pt-4 border-t border-gray-100 flex justify-end">
                        <x-button type="submit" variant="primary" size="md">
                            {{ __('settings.update_password') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-layouts.app>
