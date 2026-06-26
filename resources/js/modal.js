/*
 * crowdCuratio — Modal-Manager
 *
 * Vanilla-JS-Ersatz für Bootstrap-3-Modal. Bedient das im Bestand
 * etablierte Markup ohne Refactor:
 *
 *   <div class="modal fade" id="xxx" role="dialog">
 *     <div class="modal-dialog">
 *       <div class="modal-content"> ... </div>
 *     </div>
 *   </div>
 *
 * Trigger:
 *   - `[data-toggle="modal"][data-target="#xxx"]`  — Klick öffnet
 *   - `[data-dismiss="modal"]`                     — Klick schließt
 *   - Esc-Taste                                    — schließt aktuelles
 *   - Klick auf den Modal-Hintergrund              — schließt
 *
 * Programmatic (jQuery-Shim für den Bestand):
 *   - `$('#xxx').modal('show'|'hide'|'toggle')`
 *
 * Das jQuery-Shim wird nur installiert, wenn jQuery vorhanden ist und
 * `jQuery.fn.modal` noch nicht von einer anderen Library belegt wurde
 * (z. B. Bootstrap-3-JS). In 5a.IV.b ist Bootstrap-3-JS aus dem Stack;
 * der Shim greift hier ohne Konflikt.
 */

const OPEN_ATTR = 'data-modal-open';
const BODY_LOCK_CLASS = 'modal-open';
const BACKDROP_ID = 'app-modal-backdrop';

function ensureBackdrop() {
    let bd = document.getElementById(BACKDROP_ID);
    if (!bd) {
        bd = document.createElement('div');
        bd.id = BACKDROP_ID;
        bd.className = 'modal-backdrop fade';
        bd.addEventListener('click', closeTopMostModal);
        document.body.appendChild(bd);
    }
    return bd;
}

function openStack() {
    return Array.from(document.querySelectorAll('.modal.in'));
}

function showBackdrop(zIndex) {
    const bd = ensureBackdrop();
    bd.style.zIndex = String(zIndex);
    // requestAnimationFrame, damit der Browser den Frame mit
    // `display: none/block`-Wechsel vom Frame mit `.in`-Trigger
    // trennt — sonst keine sichtbare Opacity-Transition.
    bd.style.display = 'block';
    requestAnimationFrame(() => bd.classList.add('in', 'show'));
}

function hideBackdropIfEmpty() {
    if (openStack().length > 0) return;
    const bd = document.getElementById(BACKDROP_ID);
    if (!bd) return;
    bd.classList.remove('in', 'show');
    bd.style.display = 'none';
}

function openModal(modal) {
    if (!modal || modal.classList.contains('in')) return;

    const stackDepth = openStack().length;
    const zIndex = 1050 + stackDepth * 20;

    showBackdrop(zIndex + 10 - 20);

    modal.style.display = 'block';
    modal.style.zIndex = String(zIndex);
    modal.setAttribute(OPEN_ATTR, '');
    modal.setAttribute('aria-hidden', 'false');
    modal.removeAttribute('aria-modal');
    modal.setAttribute('aria-modal', 'true');

    requestAnimationFrame(() => modal.classList.add('in', 'show'));

    document.body.classList.add(BODY_LOCK_CLASS);

    modal.dispatchEvent(new CustomEvent('shown.modal', { bubbles: true }));

    // Bootstrap-3-API-Kompatibilität — diverse Inline-Handler hängen
    // an `shown.bs.modal`. Wir feuern beide Events; jQuery-trigger
    // wird mitgenommen, wenn vorhanden.
    if (window.jQuery) {
        window.jQuery(modal).trigger('shown.bs.modal');
    }

    focusFirst(modal);
}

function closeModal(modal) {
    if (!modal || !modal.classList.contains('in')) return;

    modal.classList.remove('in', 'show');
    modal.removeAttribute(OPEN_ATTR);
    modal.setAttribute('aria-hidden', 'true');
    modal.removeAttribute('aria-modal');

    // Kleinerer Timeout, damit die opacity-Transition (0.15s) durchläuft,
    // bevor wir auf display:none springen.
    window.setTimeout(() => {
        modal.style.display = 'none';
        hideBackdropIfEmpty();
        if (openStack().length === 0) {
            document.body.classList.remove(BODY_LOCK_CLASS);
        }
        modal.dispatchEvent(new CustomEvent('hidden.modal', { bubbles: true }));
        if (window.jQuery) {
            window.jQuery(modal).trigger('hidden.bs.modal');
        }
    }, 160);
}

function closeTopMostModal() {
    const stack = openStack();
    if (stack.length === 0) return;
    closeModal(stack[stack.length - 1]);
}

function focusFirst(modal) {
    const focusable = modal.querySelector(
        'input:not([type="hidden"]):not([disabled]),'
        + 'select:not([disabled]),'
        + 'textarea:not([disabled]),'
        + 'button:not([disabled]),'
        + '[tabindex]:not([tabindex="-1"])'
    );
    if (focusable) {
        try { focusable.focus({ preventScroll: false }); } catch (e) { /* noop */ }
    }
}

function handleToggleClick(event) {
    const trigger = event.target.closest('[data-toggle="modal"]');
    if (!trigger) return;

    const targetSel = trigger.getAttribute('data-target');
    if (!targetSel) return;

    event.preventDefault();
    const modal = document.querySelector(targetSel);
    if (!modal) return;

    openModal(modal);
}

function handleDismissClick(event) {
    const trigger = event.target.closest('[data-dismiss="modal"]');
    if (!trigger) return;

    event.preventDefault();
    const modal = trigger.closest('.modal');
    if (modal) closeModal(modal);
}

function handleBackdropClick(event) {
    // Direkt-Klick auf `.modal` (nicht `.modal-dialog` oder Kinder)
    // gilt als Backdrop-Click in Bootstrap-3-Konvention.
    if (event.target.classList.contains('modal')) {
        closeModal(event.target);
    }
}

function handleEscape(event) {
    if (event.key !== 'Escape') return;
    closeTopMostModal();
}

function installJQueryShim() {
    if (!window.jQuery) return;
    if (window.jQuery.fn.modal) return; // Bootstrap-JS hat schon installiert

    window.jQuery.fn.modal = function (action, options) {
        return this.each(function () {
            const modal = this;
            if (action === 'show') openModal(modal);
            else if (action === 'hide') closeModal(modal);
            else if (action === 'toggle') {
                modal.classList.contains('in') ? closeModal(modal) : openModal(modal);
            } else if (!action || typeof action === 'object') {
                // Bestandsmuster: `$('#xxx').modal()` ohne Argument
                // initialisiert nur; show passiert bei data-show=true.
                if (modal.getAttribute('data-show') === 'true') openModal(modal);
            }
        });
    };
}

document.addEventListener('click', handleToggleClick);
document.addEventListener('click', handleDismissClick);
document.addEventListener('click', handleBackdropClick);
document.addEventListener('keydown', handleEscape);

// jQuery-Shim installieren — verzögert, damit andere Skripte (BS3-JS
// solange noch im Stack) eine Chance hatten, sich zu registrieren.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', installJQueryShim);
} else {
    installJQueryShim();
}

// Public-API für Tests / externe Aufrufe
window.crowdCuratioModal = {
    open: (idOrEl) => openModal(typeof idOrEl === 'string' ? document.querySelector(idOrEl) : idOrEl),
    close: (idOrEl) => closeModal(typeof idOrEl === 'string' ? document.querySelector(idOrEl) : idOrEl),
    closeTop: closeTopMostModal,
};
