import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// 1. Globally suppress browser native validation popup bubbles across all browsers & forms
window.addEventListener('invalid', (e) => {
    e.preventDefault();
}, true);

// 2. Global form mandatory validation (red border + inline error message underneath)
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!form || form.tagName !== 'FORM') return;

        let firstInvalid = null;
        let hasErrors = false;

        const clearFieldError = (targetInput, container) => {
            if (!container) container = targetInput.closest('[data-form-component]') || targetInput.parentElement;
            targetInput.classList.remove('border-red-400', 'ring-4', 'ring-red-500/10', 'text-red-900');
            const btn = container.querySelector('button[aria-haspopup="listbox"]');
            if (btn) {
                btn.classList.remove('border-red-400', 'ring-4', 'ring-red-500/10', 'text-red-900');
            }
            const existingMsg = container.querySelector('.form-client-error');
            if (existingMsg) existingMsg.remove();
        };

        const showFieldError = (targetInput, container, message) => {
            hasErrors = true;
            if (!container) container = targetInput.closest('[data-form-component]') || targetInput.parentElement;

            const btn = container.querySelector('button[aria-haspopup="listbox"]');
            const highlightTarget = btn || targetInput;

            highlightTarget.classList.add('border-red-400', 'ring-4', 'ring-red-500/10');
            if (btn) highlightTarget.classList.add('text-red-900');

            let errorP = container.querySelector('.form-client-error');
            if (!errorP) {
                errorP = document.createElement('p');
                errorP.className = 'form-client-error mt-1.5 text-[11px] text-red-500 font-medium';
                container.appendChild(errorP);
            }
            errorP.textContent = message;

            if (!firstInvalid) {
                firstInvalid = highlightTarget;
            }

            const cleanup = () => {
                clearFieldError(targetInput, container);
                targetInput.removeEventListener('input', cleanup);
                targetInput.removeEventListener('change', cleanup);
                if (btn) btn.removeEventListener('click', cleanup);
            };

            targetInput.addEventListener('input', cleanup);
            targetInput.addEventListener('change', cleanup);
            if (btn) btn.addEventListener('click', cleanup);
        };

        // Validate native inputs & textareas
        const inputs = form.querySelectorAll('input:not([type="hidden"]), textarea, select');
        inputs.forEach(el => {
            // Ignore elements inside hidden containers (e.g. x-show="false" or display: none)
            if (el.offsetParent === null) return;

            const isRequired = el.hasAttribute('required') || el.dataset.required === 'true';
            if (!isRequired) return;

            const val = el.value ? el.value.trim() : '';
            const isZeroNumber = el.type === 'number' && parseFloat(val) <= 0 && el.step !== 'any' && (!el.hasAttribute('min') || parseFloat(el.min) > 0);

            if (val === '' || isZeroNumber) {
                const container = el.closest('[data-form-component]') || el.closest('div.relative')?.parentElement || el.parentElement;
                const label = container.querySelector('label')?.textContent?.replace('*', '')?.trim() || 'Field';
                showFieldError(el, container, `${label} wajib diisi`);
            }
        });

        // Validate custom searchable dropdowns with data-required="true"
        const searchableSelects = form.querySelectorAll('[data-select-searchable="true"]');
        searchableSelects.forEach(selectContainer => {
            if (selectContainer.offsetParent === null) return; // Hidden in x-show

            const hiddenInput = selectContainer.querySelector('input[data-select-input="true"]');
            if (!hiddenInput) return;

            const isRequired = hiddenInput.dataset.required === 'true' || hiddenInput.hasAttribute('required');
            if (!isRequired) return;

            const val = hiddenInput.value ? hiddenInput.value.trim() : '';
            if (val === '') {
                const label = selectContainer.querySelector('label')?.textContent?.replace('*', '')?.trim() || 'Pilihan';
                showFieldError(hiddenInput, selectContainer, `${label} wajib dipilih`);
            }
        });

        if (hasErrors) {
            e.preventDefault();
            e.stopPropagation();
            if (firstInvalid) {
                firstInvalid.focus();
                if (typeof firstInvalid.scrollIntoView === 'function') {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
            return;
        }

        // Prevent duplicate submit if already in progress
        if (form.dataset.submitting === 'true') {
            e.preventDefault();
            return;
        }
        form.dataset.submitting = 'true';

        // Start top progress bar
        window.startTopProgress();

        // Find target submit button and show spinner
        const submitBtn = (lastClickedSubmitBtn && form.contains(lastClickedSubmitBtn))
            ? lastClickedSubmitBtn
            : form.querySelector('button[type="submit"]:not([disabled]), input[type="submit"]:not([disabled])');

        if (submitBtn && !submitBtn.hasAttribute('data-no-spinner')) {
            window.startLoading(submitBtn);
        }

        // Safety fallback: restore if navigation or response does not complete in 12s
        setTimeout(() => {
            if (submitBtn) window.stopLoading(submitBtn);
            window.finishTopProgress();
            form.dataset.submitting = 'false';
        }, 12000);
    });

    // Track clicked submit / action buttons
    let lastClickedSubmitBtn = null;
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('button[type="submit"], input[type="submit"], [data-action-loading="true"]');
        if (btn) {
            if (btn.hasAttribute('data-action-loading')) {
                window.startLoading(btn);
                window.startTopProgress();
                setTimeout(() => {
                    window.stopLoading(btn);
                    window.finishTopProgress();
                }, 2500);
                return;
            }
            lastClickedSubmitBtn = btn;
            return;
        }

        // Automatic Top Progress Bar for Internal Page Navigations
        const link = e.target.closest('a[href]');
        if (!link) return;

        // Skip if modified click (new tab, new window, context menu)
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
            return;
        }

        // Skip non-navigational links
        if (link.target && link.target !== '_self') return;
        if (link.hasAttribute('download')) return;
        if (link.getAttribute('data-no-progress') === 'true') return;

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
            return;
        }

        try {
            const url = new URL(link.href, window.location.origin);
            // Must be same origin (internal page navigation)
            if (url.origin !== window.location.origin) return;

            // Ignore if clicking the exact current page
            if (url.href === window.location.href) return;

            // Subtle immediate feedback for sidebar nav links
            if (link.classList.contains('sidebar-link')) {
                link.classList.add('opacity-75');
            }

            // Fire progress bar
            window.startTopProgress();

            // Safety timeout in case navigation is interrupted
            setTimeout(() => {
                window.finishTopProgress();
                if (link.classList.contains('sidebar-link')) {
                    link.classList.remove('opacity-75');
                }
            }, 8000);
        } catch (_) {
            // Ignore malformed URLs
        }
    }, true);

    // Handle browser back/forward (bfcache) navigation
    window.addEventListener('pageshow', () => {
        window.finishTopProgress();
        document.querySelectorAll('form[data-submitting="true"]').forEach(f => {
            f.dataset.submitting = 'false';
            const btn = f.querySelector('button.is-submitting');
            if (btn) window.stopLoading(btn);
        });
        document.querySelectorAll('.sidebar-link.opacity-75').forEach(l => l.classList.remove('opacity-75'));
    });

    // Reset validation errors on form reset
    document.addEventListener('reset', (e) => {
        const form = e.target;
        if (!form || form.tagName !== 'FORM') return;
        form.dataset.submitting = 'false';
        form.querySelectorAll('.form-client-error').forEach(el => el.remove());
        form.querySelectorAll('.border-red-400').forEach(el => {
            el.classList.remove('border-red-400', 'ring-4', 'ring-red-500/10', 'text-red-900');
        });
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) window.stopLoading(submitBtn);
    });
});

// Sleek Top Progress Bar System
let topProgressEl = null;
let topProgressTimer = null;

function getTopProgressBar() {
    if (!topProgressEl) {
        topProgressEl = document.getElementById('app-top-progress');
        if (!topProgressEl && document.body) {
            topProgressEl = document.createElement('div');
            topProgressEl.id = 'app-top-progress';
            document.body.appendChild(topProgressEl);
        }
    }
    return topProgressEl;
}

window.startTopProgress = function() {
    const bar = getTopProgressBar();
    if (!bar) return;
    clearTimeout(topProgressTimer);

    // Reset bar cleanly to 0
    bar.style.transition = 'none';
    bar.style.width = '0%';
    bar.style.opacity = '1';

    // Force browser reflow to apply reset
    void bar.offsetWidth;

    // Smooth ramp up
    bar.style.transition = 'width 0.35s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease-in-out';
    bar.style.width = '35%';

    topProgressTimer = setTimeout(() => {
        bar.style.transition = 'width 0.6s cubic-bezier(0.16, 1, 0.3, 1)';
        bar.style.width = '70%';
        topProgressTimer = setTimeout(() => {
            bar.style.transition = 'width 1.2s cubic-bezier(0.16, 1, 0.3, 1)';
            bar.style.width = '88%';
        }, 550);
    }, 200);
};

window.finishTopProgress = function() {
    const bar = getTopProgressBar();
    if (!bar) return;
    clearTimeout(topProgressTimer);
    bar.style.transition = 'width 0.2s ease-out, opacity 0.25s ease-in-out';
    bar.style.width = '100%';
    topProgressTimer = setTimeout(() => {
        bar.style.opacity = '0';
        setTimeout(() => {
            bar.style.width = '0%';
        }, 250);
    }, 180);
};

// Sleek Button Loading & Dynamic Spinner System
function createSpinnerSvg(isSmall = false) {
    const size = isSmall ? 'w-3.5 h-3.5' : 'w-4 h-4';
    const span = document.createElement('span');
    span.className = 'button-dynamic-spinner inline-flex items-center justify-center shrink-0 mr-2 text-current';
    span.innerHTML = `<svg class="animate-spin ${size} text-current" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3.5"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;
    return span;
}

window.startLoading = function(el) {
    if (!el || el.classList.contains('is-submitting')) return;

    // Preserve computed width to prevent layout jump
    const rect = el.getBoundingClientRect();
    if (rect.width > 0) {
        el.style.minWidth = `${rect.width}px`;
    }

    el.classList.add('is-submitting');
    el.setAttribute('aria-busy', 'true');

    // If button has an existing icon SVG, hide it and swap with spinner
    const existingIcon = el.querySelector('svg:not(.animate-spin)');
    if (existingIcon) {
        existingIcon.setAttribute('data-hidden-for-spinner', 'true');
        existingIcon.style.display = 'none';
        const isSm = existingIcon.classList.contains('w-3.5') || existingIcon.classList.contains('w-3');
        const spinner = createSpinnerSvg(isSm);
        existingIcon.parentNode.insertBefore(spinner, existingIcon);
    } else {
        // Prepend spinner before the text
        const isSm = el.classList.contains('text-xs') || el.classList.contains('h-8');
        const spinner = createSpinnerSvg(isSm);
        el.insertBefore(spinner, el.firstChild);
    }
};

window.stopLoading = function(el) {
    if (!el) return;
    el.classList.remove('is-submitting');
    el.removeAttribute('aria-busy');
    el.style.minWidth = '';

    el.querySelectorAll('.button-dynamic-spinner').forEach(s => s.remove());
    el.querySelectorAll('[data-hidden-for-spinner="true"]').forEach(icon => {
        icon.removeAttribute('data-hidden-for-spinner');
        icon.style.display = '';
    });
};

Alpine.start();
