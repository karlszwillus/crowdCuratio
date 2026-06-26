/*
 * crowdCuratio — Sortable-Shim
 *
 * jQuery-UI `.sortable(opts)` läuft über SortableJS (das ist über
 * `Sortable.min.js` schon im Stack). Damit fällt jQuery-UI als
 * Abhängigkeit, ohne dass die drei Init-Aufrufe in chapters/index
 * (`.sortable_list_chapter`, `.sortable_list_entry`,
 * `.sortable_list_content`) angefasst werden müssen.
 *
 * Unterstützt:
 *   $list.sortable({ connectWith, placeholder, update })
 *   $list.sortable('toArray', { attribute: 'data-xxx' })
 *
 * Nicht unterstützt (im Bestand nicht genutzt): start/stop-Callbacks,
 * handle-Constraint, axis-Beschränkung, items-Selektor.
 */

function groupNameFromConnectWith(connectWith) {
    if (!connectWith || typeof connectWith !== 'string') return 'default';
    return connectWith.replace(/^[#.]/, '').trim() || 'default';
}

function attach(element, options) {
    if (!window.Sortable) {
        console.warn('SortableJS nicht geladen; sortable-Shim macht nichts.');
        return;
    }
    if (element.__crowdCuratioSortable) return;

    const group = groupNameFromConnectWith(options.connectWith);

    element.__crowdCuratioSortable = window.Sortable.create(element, {
        group: { name: group, pull: true, put: true },
        animation: 150,
        ghostClass: options.placeholder || 'sortable-placeholder',
        onEnd: function (event) {
            if (typeof options.update === 'function') {
                // jQuery-UI-API: update.call(this, event, ui) — wir
                // reichen die Liste (event.from) als `this`, plus ein
                // schlankes ui-Objekt mit dem bewegten Item.
                options.update.call(event.from, event, { item: event.item });
            }
        },
    });
}

function toArray(element, attribute) {
    return Array.from(element.children)
        .map((child) => child.getAttribute(attribute || 'id'))
        .filter((v) => v !== null);
}

function installJQueryShim() {
    if (!window.jQuery) return;
    if (window.jQuery.fn.sortable && window.jQuery.fn.sortable.__crowdCuratio) return;

    window.jQuery.fn.sortable = function (opts, more) {
        // Read-API: $list.sortable('toArray', { attribute: '...' })
        if (typeof opts === 'string') {
            if (opts === 'toArray') {
                const attr = (more && more.attribute) || 'id';
                if (this.length === 0) return [];
                return toArray(this[0], attr);
            }
            if (opts === 'destroy') {
                this.each(function () {
                    if (this.__crowdCuratioSortable) {
                        this.__crowdCuratioSortable.destroy();
                        delete this.__crowdCuratioSortable;
                    }
                });
                return this;
            }
            return this;
        }

        // Init-API: $list.sortable({ connectWith, placeholder, update })
        this.each(function () { attach(this, opts || {}); });
        return this;
    };
    window.jQuery.fn.sortable.__crowdCuratio = true;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', installJQueryShim);
} else {
    installJQueryShim();
}

window.crowdCuratioSortable = { attach, toArray };
