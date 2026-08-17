/**
 * crowdCuratio - Curating together virtually
 * Copyright (C)2026 - berlinHistory e.V.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.
 */

/**
 * Phase 5aa.3-Followup: Auto-Save-on-Blur für die Übersetzen-Sicht.
 *
 * Auf jedem Input/Textarea im `#translate_form` horchen wir auf `change`
 * (feuert bei Input beim Verlassen des Feldes, bei Textarea auch bei
 * Enter). Sobald der Wert seit dem letzten Save geändert wurde, geht ein
 * schlanker POST an `translate.save` mit genau einem Feld. Wenn der Save
 * durch ist, markieren wir das Feld als übersetzt, aktualisieren den
 * Rahmen (warning → line-200) und rechnen den Section-Counter neu.
 *
 * Fällt der Save durch, bleibt der bestehende Wert stehen und wir
 * schreiben eine Zeile in die Live-Region — der Nutzer verliert nichts.
 *
 * Der große „Alle Änderungen speichern"-Button bleibt als Notfall-
 * Sicherheit stehen: wer offline war oder eine Feld-Änderung verpasst
 * hat, kann so alles auf einmal wegschreiben.
 */

function setFieldStatus(input, isTranslated) {
    const row = input.closest('[data-translated]');
    if (!row) return;
    row.setAttribute('data-translated', isTranslated ? '1' : '0');
    if (isTranslated) {
        input.classList.remove('border-warning');
        input.classList.add('border-line-200');
    } else {
        input.classList.remove('border-line-200');
        input.classList.add('border-warning');
    }
    updateSectionCounter(row);
    updateOverallProgress();
}

function updateSectionCounter(row) {
    // Der Counter sitzt im header der umgebenden section.
    const section = row.closest('section');
    if (!section) return;
    const rows = section.querySelectorAll('[data-translated]');
    const total = rows.length;
    const done = Array.from(rows).filter(
        (r) => r.getAttribute('data-translated') === '1'
    ).length;
    const counter = section.querySelector('[data-section-counter]');
    if (!counter) return;
    const isDone = done === total;
    counter.classList.toggle('text-success', isDone);
    counter.classList.toggle('text-warning', !isDone);
    counter.textContent = `${isDone ? '✓' : '⚠'} ${done} von ${total} Feldern übersetzt`;
}

function updateOverallProgress() {
    const bar = document.querySelector('[data-progress-bar]');
    const label = document.querySelector('[data-progress-label]');
    if (!bar || !label) return;
    const rows = document.querySelectorAll('[data-translated]');
    if (!rows.length) return;
    const done = Array.from(rows).filter(
        (r) => r.getAttribute('data-translated') === '1'
    ).length;
    const pct = Math.round((done / rows.length) * 100);
    bar.style.width = pct + '%';
    label.textContent = pct + '%';
}

async function saveField(input) {
    const name = input.name;
    if (!name || !name.startsWith('translations[')) return;
    const value = input.value;
    if (input.dataset.lastSaved === value) return;

    const form = input.form;
    if (!form) return;
    const url = form.action;
    const token = document.querySelector('meta[name=csrf-token]')?.content;

    const fd = new FormData();
    fd.append('_token', token || '');
    fd.append(name, value);

    input.classList.add('opacity-60');
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: fd,
        });
        if (!res.ok) throw new Error('save failed');
        input.dataset.lastSaved = value;
        setFieldStatus(input, value.trim() !== '');
        window.ccAnnounce?.('Übersetzung gespeichert.');
    } catch (e) {
        window.ccToast?.('Speichern fehlgeschlagen — der Wert bleibt stehen.', 'error');
    } finally {
        input.classList.remove('opacity-60');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('translate_form');
    if (!form) return;
    form.querySelectorAll('input, textarea').forEach((el) => {
        if (!el.name || !el.name.startsWith('translations[')) return;
        el.dataset.lastSaved = el.value;
        el.addEventListener('change', () => saveField(el));
        el.addEventListener('blur', () => saveField(el));
    });
});
