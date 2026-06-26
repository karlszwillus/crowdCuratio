/*
 * crowdCuratio — Typeahead-Manager
 *
 * Vanilla-JS-Ersatz für bootstrap-3-typeahead.js. Stellt einen
 * jQuery-Shim `$('#xxx').typeahead({...})` bereit, der die im
 * Bestand verwendete API exakt nachbildet:
 *
 *   {
 *     source: function(query, process) { ... },   // Pflicht
 *     displayText: function(item) { ... },        // optional
 *     afterSelect: function(item) { ... },        // optional
 *     fitToElement: true,                         // optional
 *     minLength: 1,                               // optional, Default 1
 *   }
 *
 * `source` ruft `process(data)` mit den Vorschlägen auf. `displayText`
 * formatiert ein Item für die Anzeige in der Liste. `afterSelect`
 * läuft beim Klick auf ein Item.
 *
 * Tastatur: ↑↓ navigieren, Enter wählt, Esc schließt. Klick außerhalb
 * schließt ebenfalls.
 */

const ACTIVE_CLASS = 'active';

function defaultDisplayText(item) {
    if (typeof item === 'string') return item;
    if (item && typeof item === 'object') {
        if ('name' in item) return String(item.name);
        return Object.values(item).join(' ');
    }
    return String(item);
}

function createMenu(input) {
    const menu = document.createElement('ul');
    menu.className = 'dropdown-menu typeahead-menu';
    menu.setAttribute('role', 'listbox');
    menu.style.position = 'absolute';
    menu.style.display = 'none';
    menu.style.zIndex = '1100';
    // Direkt nach dem Input einhängen, damit Layout-Verschiebung
    // klein bleibt und der Selektor `input ~ ul.typeahead-menu` greift.
    input.insertAdjacentElement('afterend', menu);
    return menu;
}

function positionMenu(input, menu, fitToElement) {
    const rect = input.getBoundingClientRect();
    const docTop = window.scrollY + rect.bottom;
    const docLeft = window.scrollX + rect.left;
    menu.style.top = docTop + 'px';
    menu.style.left = docLeft + 'px';
    if (fitToElement) {
        menu.style.minWidth = rect.width + 'px';
    }
}

function renderItems(menu, items, displayTextFn) {
    menu.innerHTML = '';
    items.forEach((item, idx) => {
        const li = document.createElement('li');
        li.setAttribute('role', 'option');
        li.dataset.idx = String(idx);

        const a = document.createElement('a');
        a.className = 'dropdown-item';
        a.href = '#';
        a.textContent = displayTextFn(item);
        a.addEventListener('click', (e) => {
            e.preventDefault();
            li.dispatchEvent(new CustomEvent('typeahead:select', { bubbles: true, detail: item }));
        });

        li.appendChild(a);
        menu.appendChild(li);
    });
}

function highlight(menu, idx) {
    const items = menu.querySelectorAll('li');
    items.forEach((li, i) => {
        if (i === idx) li.classList.add(ACTIVE_CLASS);
        else li.classList.remove(ACTIVE_CLASS);
    });
}

function attach(input, options) {
    if (input.__typeahead) return; // schon angehängt
    const opts = Object.assign({
        source: null,
        displayText: defaultDisplayText,
        afterSelect: () => {},
        fitToElement: false,
        minLength: 1,
    }, options || {});

    if (typeof opts.source !== 'function') {
        // Ohne Source kein Sinn — wir ignorieren still.
        return;
    }

    const menu = createMenu(input);
    let currentItems = [];
    let activeIdx = -1;

    const showMenu = () => {
        positionMenu(input, menu, opts.fitToElement);
        menu.style.display = 'block';
    };
    const hideMenu = () => {
        menu.style.display = 'none';
        activeIdx = -1;
    };

    const process = (data) => {
        const items = Array.isArray(data) ? data : (data && Array.isArray(data.data) ? data.data : []);
        currentItems = items;
        renderItems(menu, items, opts.displayText);
        if (items.length > 0) showMenu();
        else hideMenu();
    };

    input.addEventListener('input', () => {
        const query = input.value;
        if (query.length < opts.minLength) {
            hideMenu();
            return;
        }
        try {
            opts.source(query, process);
        } catch (err) {
            // Source-Funktion darf werfen, ohne den Editor zu killen.
            console.error('typeahead source error', err);
            hideMenu();
        }
    });

    input.addEventListener('keydown', (event) => {
        if (menu.style.display === 'none') return;

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            activeIdx = Math.min(activeIdx + 1, currentItems.length - 1);
            highlight(menu, activeIdx);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            activeIdx = Math.max(activeIdx - 1, 0);
            highlight(menu, activeIdx);
        } else if (event.key === 'Enter') {
            if (activeIdx >= 0 && currentItems[activeIdx] !== undefined) {
                event.preventDefault();
                selectItem(currentItems[activeIdx]);
            }
        } else if (event.key === 'Escape') {
            hideMenu();
        }
    });

    const selectItem = (item) => {
        try { opts.afterSelect(item); } catch (e) { console.error('typeahead afterSelect error', e); }
        hideMenu();
    };

    menu.addEventListener('typeahead:select', (event) => {
        selectItem(event.detail);
    });

    document.addEventListener('click', (event) => {
        if (event.target !== input && !menu.contains(event.target)) {
            hideMenu();
        }
    });

    window.addEventListener('scroll', () => {
        if (menu.style.display !== 'none') positionMenu(input, menu, opts.fitToElement);
    }, true);

    input.__typeahead = {
        destroy: () => {
            menu.remove();
            delete input.__typeahead;
        },
    };
}

function installJQueryShim() {
    if (!window.jQuery) return;
    if (window.jQuery.fn.typeahead && window.jQuery.fn.typeahead.__crowdCuratio) return;

    window.jQuery.fn.typeahead = function (options) {
        return this.each(function () {
            attach(this, options);
        });
    };
    window.jQuery.fn.typeahead.__crowdCuratio = true;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', installJQueryShim);
} else {
    installJQueryShim();
}

window.crowdCuratioTypeahead = { attach };
