@props([
    'label' => null,
    'name',
    'id' => null,
    'options' => [],
    'value' => null,
    'placeholder' => 'Select an option',
    'searchPlaceholder' => 'Search option...',
    'hint' => null,
    'required' => false,
    'disabled' => false,
    'clearable' => false,
    'loading' => false,
    'searchable' => true,
    'autoSubmit' => false,
    'size' => 'md', // sm, md
    'align' => 'left', // left, right
    'minWidth' => null, // e.g. min-w-[180px]
])

@php
    $id = $id ?? $name;
    $hasError = isset($errors) && $errors->has($name);
    $selectedVal = old($name, $value ?? request($name, ''));

    // Normalize options into list of objects: [['value' => ..., 'label' => ..., 'sublabel' => ..., 'badge' => ...]]
    $normalized = [];
    foreach ($options as $key => $opt) {
        if (is_array($opt)) {
            $normalized[] = [
                'value'    => (string) ($opt['value'] ?? $key),
                'label'    => (string) ($opt['label'] ?? $opt['name'] ?? $key),
                'sublabel' => (string) ($opt['sublabel'] ?? ''),
                'badge'    => (string) ($opt['badge'] ?? ''),
            ];
        } else {
            $normalized[] = [
                'value'    => (string) $key,
                'label'    => (string) $opt,
                'sublabel' => '',
                'badge'    => '',
            ];
        }
    }

    $isSmall = $size === 'sm';
    $showSearch = $searchable && count($normalized) > 4;

    // Detect if caller passed explicit w-* class
    $hasExplicitWidth = $attributes->has('class') && preg_match('/\bw-\S+/', (string) $attributes->get('class'));
    $widthClass = $hasExplicitWidth ? '' : ($isSmall ? 'w-auto' : 'w-full');
@endphp

<div
    x-data="{
        instanceId: 'dd_' + Math.random().toString(36).substring(2, 9),
        open: false,
        search: '',
        selected: @js((string) $selectedVal),
        initialSelected: @js((string) $selectedVal),
        options: @js($normalized),
        disabled: @js($disabled),
        loading: @js($loading),
        autoSubmit: @js($autoSubmit),
        highlightedIndex: -1,

        init() {
            const parentForm = this.$el.closest('form');
            if (parentForm) {
                parentForm.addEventListener('reset', () => {
                    this.$nextTick(() => this.reset());
                });
            }
        },

        reset() {
            this.selected = this.initialSelected;
            this.search = '';
            this.open = false;
            this.highlightedIndex = -1;
            this.$dispatch('input', this.selected);
            this.$dispatch('change', this.selected);
        },

        get filteredOptions() {
            if (!this.search.trim()) return this.options;
            const q = this.search.toLowerCase();
            return this.options.filter(opt =>
                String(opt.label).toLowerCase().includes(q) ||
                (opt.sublabel && String(opt.sublabel).toLowerCase().includes(q))
            );
        },

        get selectedOption() {
            return this.options.find(opt => String(opt.value) === String(this.selected)) || null;
        },

        get displayLabel() {
            return this.selectedOption ? this.selectedOption.label : '';
        },

        toggle() {
            if (this.disabled) return;
            this.open = !this.open;
            if (this.open) {
                window.dispatchEvent(new CustomEvent('dropdown-opened', { detail: { id: this.instanceId } }));
                this.search = '';
                this.highlightedIndex = -1;
                this.$nextTick(() => {
                    if (this.$refs.searchInput) {
                        this.$refs.searchInput.focus();
                    }
                });
            }
        },

        select(opt) {
            this.selected = opt.value;
            this.open = false;
            this.search = '';
            this.$dispatch('input', this.selected);
            this.$dispatch('change', this.selected);

            if (this.autoSubmit) {
                this.$nextTick(() => {
                    const form = this.$el.closest('form');
                    if (form) form.submit();
                });
            }
        },

        clear(e) {
            e.stopPropagation();
            this.selected = '';
            this.search = '';
            this.$dispatch('input', '');
            this.$dispatch('change', '');

            if (this.autoSubmit) {
                this.$nextTick(() => {
                    const form = this.$el.closest('form');
                    if (form) form.submit();
                });
            }
        },

        onKeyDown(e) {
            if (!this.open) {
                if (e.key === 'Enter' || e.key === 'ArrowDown' || e.key === ' ') {
                    e.preventDefault();
                    this.toggle();
                }
                return;
            }

            const total = this.filteredOptions.length;
            if (total === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.highlightedIndex = (this.highlightedIndex + 1) % total;
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.highlightedIndex = (this.highlightedIndex - 1 + total) % total;
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (this.highlightedIndex >= 0 && this.highlightedIndex < total) {
                    this.select(this.filteredOptions[this.highlightedIndex]);
                }
            } else if (e.key === 'Escape') {
                this.open = false;
            }
        }
    }"
    class="relative inline-block text-left {{ $widthClass }}"
    data-form-component="true"
    data-select-searchable="true"
    @dropdown-opened.window="if ($event.detail.id !== instanceId) open = false"
    @reset-select.window="if (!$event.detail || !$event.detail.form || $event.detail.form.contains($el)) reset()"
    @click.outside="open = false"
    @focusin.window="if (open && !$el.contains($event.target)) open = false"
    @keydown="onKeyDown($event)"
    {{ $attributes->only('class') }}>

    {{-- Label (if provided) --}}
    @if ($label)
        <label for="{{ $id }}_btn" class="block text-xs font-semibold text-gray-700 mb-1.5">
            {{ $label }}
            @if ($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif

    {{-- Hidden Input for native form submission --}}
    <input
        type="hidden"
        name="{{ $name }}"
        id="{{ $id }}"
        :value="selected"
        data-select-input="true"
        {{ $required ? 'data-required=true' : '' }}>

    {{-- Trigger Button --}}
    <button
        type="button"
        id="{{ $id }}_btn"
        @click="toggle()"
        :disabled="disabled"
        aria-haspopup="listbox"
        :aria-expanded="open"
        class="w-full text-left bg-white border flex items-center justify-between transition-all duration-150 cursor-pointer shadow-sm
               focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600
               disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed
               {{ $isSmall ? 'h-8.5 px-3 py-1.5 text-xs rounded-lg gap-2' : 'h-10.5 px-3.5 py-2 text-xs rounded-xl gap-2.5' }}
               {{ $hasError ? 'border-red-400 text-red-900 focus:border-red-500 focus:ring-red-500/10' : 'border-slate-200 text-gray-800 hover:border-slate-300' }}"
        :class="{
            'ring-4 ring-emerald-500/10 border-emerald-600': open,
            'bg-emerald-50/30 border-emerald-300 font-medium text-emerald-900': selected && @js($isSmall)
        }">

        <div class="flex items-center gap-1.5 min-w-0 flex-1">
            <template x-if="selectedOption">
                <div class="flex items-center gap-2 min-w-0 flex-1">
                    <span x-text="selectedOption.label" class="font-medium text-gray-900 truncate"></span>
                    <span x-show="selectedOption.badge" x-text="selectedOption.badge" class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-600 shrink-0"></span>
                </div>
            </template>
            <template x-if="!selectedOption">
                <span class="text-gray-400 truncate">{{ $placeholder }}</span>
            </template>
        </div>

        <div class="flex items-center gap-1 shrink-0 text-gray-400">
            @if ($clearable)
                <button
                    type="button"
                    x-show="selected && !disabled"
                    @click="clear($event)"
                    class="p-0.5 rounded hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition cursor-pointer"
                    title="Clear selection">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            @endif

            {{-- Chevron indicator with smooth rotation --}}
            <svg
                class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200"
                :class="{ 'rotate-180 text-emerald-600': open }"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </button>

    {{-- Modern Dropdown Menu (Banking & Linear Style) --}}
    <div
        x-show="open"
        x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-150"
        x-transition:enter-start="opacity-0 scale-95 translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-1"
        class="absolute mt-1.5 bg-white rounded-xl border border-slate-200/80 shadow-[0_12px_30px_-4px_rgba(0,0,0,0.08),0_4px_12px_-2px_rgba(0,0,0,0.04)] z-50 overflow-hidden {{ $align === 'right' ? 'right-0' : 'left-0' }} {{ $minWidth ?? ($isSmall ? 'min-w-[180px]' : 'min-w-full') }}"
        style="display: none;"
        x-cloak>

        {{-- Embedded Search Box (shown if options > 4 or explicitly requested) --}}
        @if ($showSearch)
            <div class="p-2 border-b border-slate-100 bg-slate-50/60">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                    </div>
                    <input
                        type="search"
                        x-ref="searchInput"
                        x-model="search"
                        @keydown.escape.stop="open = false"
                        placeholder="{{ $searchPlaceholder }}"
                        class="w-full pl-8 pr-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg text-gray-800 placeholder-gray-400
                               focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition">
                </div>
            </div>
        @endif

        {{-- Loading State --}}
        <div x-show="loading" class="py-5 px-4 text-center flex items-center justify-center gap-2 text-xs text-gray-500">
            <svg class="animate-spin w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span class="text-xs font-medium">Loading...</span>
        </div>

        {{-- Options List --}}
        <div x-show="!loading" class="max-h-60 overflow-y-auto p-1.5 space-y-0.5">
            <template x-for="(opt, idx) in filteredOptions" :key="opt.value">
                <div
                    @click="select(opt)"
                    @mouseenter="highlightedIndex = idx"
                    :class="{
                        'bg-emerald-50 text-emerald-800 font-semibold': String(selected) === String(opt.value),
                        'bg-slate-50 text-gray-900': highlightedIndex === idx && String(selected) !== String(opt.value),
                        'text-gray-700': String(selected) !== String(opt.value) && highlightedIndex !== idx
                    }"
                    class="px-3 py-2 rounded-lg text-xs flex items-center justify-between cursor-pointer transition-colors duration-100 select-none">

                    <div class="flex flex-col min-w-0 pr-2">
                        <span x-text="opt.label" class="truncate"></span>
                        <span x-show="opt.sublabel" x-text="opt.sublabel" class="text-[10px] text-gray-400 truncate mt-0.5"></span>
                    </div>

                    <div class="flex items-center gap-1.5 shrink-0">
                        <span x-show="opt.badge" x-text="opt.badge" class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-600"></span>

                        <span x-show="String(selected) === String(opt.value)" class="text-emerald-600 shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                        </span>
                    </div>
                </div>
            </template>

            {{-- Empty State / Data Not Found --}}
            <div x-show="filteredOptions.length === 0" class="py-6 px-4 text-center">
                <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                </div>
                <p class="text-xs font-semibold text-slate-700">Data not found</p>
                <p class="text-[11px] text-slate-400 mt-0.5" x-show="search">No results matching "<span x-text="search" class="text-slate-600 font-medium"></span>"</p>
            </div>
        </div>

    </div>

    {{-- Hint & Error --}}
    @if ($hint && !$hasError)
        <p class="mt-1.5 text-[11px] text-gray-400">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1.5 text-[11px] text-red-500 font-medium">{{ $message }}</p>
    @enderror
</div>
