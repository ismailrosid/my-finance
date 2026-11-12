<x-layouts.app title="Settings">

    <x-page-header
        title="Settings"
        subtitle="Manage your account preferences and application settings."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Settings', 'url' => route('settings.index')],
        ]">
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6" x-data="{ section: 'profile' }">

        {{-- Settings Sidebar --}}
        <div class="lg:col-span-1">
            <div class="card p-2">
                <nav class="space-y-0.5">
                    @foreach ([
                        'profile'       => ['label' => 'Profile', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                        'currency'      => ['label' => 'Currency & Format', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        'categories'    => ['label' => 'Categories', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                        'notifications' => ['label' => 'Notifications', 'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
                        'security'      => ['label' => 'Security', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                    ] as $key => $item)
                        <button @click="section = '{{ $key }}'"
                                class="sidebar-link w-full text-left"
                                :class="section === '{{ $key }}' ? 'active' : ''">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                            </svg>
                            {{ $item['label'] }}
                        </button>
                    @endforeach
                </nav>
            </div>
        </div>

        {{-- Settings Content --}}
        <div class="lg:col-span-3 card">
            {{-- Profile --}}
            <div x-show="section === 'profile'">
                <div class="card-header">
                    <h2 class="text-sm font-semibold text-gray-900">Profile Settings</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Update your personal information</p>
                </div>
                <div class="card-body space-y-5">
                    <x-alert type="info" :dismissible="false">
                        Profile management will be available in the next update.
                    </x-alert>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-form-input label="Full Name" name="name" placeholder="Your full name"/>
                        <x-form-input label="Email Address" name="email" type="email" placeholder="your@email.com"/>
                    </div>
                    <div class="pt-2">
                        <x-button-primary type="submit">Save Changes</x-button-primary>
                    </div>
                </div>
            </div>

            {{-- Currency --}}
            <div x-show="section === 'currency'" x-cloak>
                <div class="card-header">
                    <h2 class="text-sm font-semibold text-gray-900">Currency & Format</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Configure display format for amounts</p>
                </div>
                <div class="card-body space-y-5">
                    <x-form-select
                        label="Currency"
                        name="currency"
                        :options="['IDR' => 'Indonesian Rupiah (Rp)', 'USD' => 'US Dollar ($)', 'EUR' => 'Euro (€)', 'SGD' => 'Singapore Dollar (S$)']"/>
                    <x-form-select
                        label="Number Format"
                        name="number_format"
                        :options="['1.000,00' => '1.000,00 (ID)', '1,000.00' => '1,000.00 (US)']"/>
                    <x-form-select
                        label="Date Format"
                        name="date_format"
                        :options="['d/m/Y' => 'DD/MM/YYYY', 'm/d/Y' => 'MM/DD/YYYY', 'Y-m-d' => 'YYYY-MM-DD']"/>
                    <div class="pt-2">
                        <x-button-primary type="submit">Save Changes</x-button-primary>
                    </div>
                </div>
            </div>

            {{-- Categories --}}
            <div x-show="section === 'categories'" x-cloak>
                <div class="card-header">
                    <h2 class="text-sm font-semibold text-gray-900">Transaction Categories</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Manage your income and expense categories</p>
                </div>
                <div class="card-body">
                    <x-empty-state
                        title="No categories yet"
                        description="Create categories to organize your transactions."
                        action-label="Add Category"
                        action-href="#">
                    </x-empty-state>
                </div>
            </div>

            {{-- Notifications --}}
            <div x-show="section === 'notifications'" x-cloak>
                <div class="card-header">
                    <h2 class="text-sm font-semibold text-gray-900">Notification Preferences</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Control when and how you receive alerts</p>
                </div>
                <div class="card-body">
                    <x-alert type="info" :dismissible="false">
                        Notification settings will be available in the next update.
                    </x-alert>
                </div>
            </div>

            {{-- Security --}}
            <div x-show="section === 'security'" x-cloak>
                <div class="card-header">
                    <h2 class="text-sm font-semibold text-gray-900">Security</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Manage your password and account security</p>
                </div>
                <div class="card-body space-y-5">
                    <x-form-input label="Current Password" name="current_password" type="password" placeholder="••••••••"/>
                    <x-form-input label="New Password" name="new_password" type="password" placeholder="••••••••"/>
                    <x-form-input label="Confirm New Password" name="new_password_confirmation" type="password" placeholder="••••••••"/>
                    <div class="pt-2">
                        <x-button-primary type="submit">Update Password</x-button-primary>
                    </div>
                </div>
            </div>
        </div>

    </div>

</x-layouts.app>

