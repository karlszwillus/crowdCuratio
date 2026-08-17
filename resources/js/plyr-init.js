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
 * Plyr-Init (Phase 5z.7). Vereinheitlicht den nativen `<audio controls>`
 * und den YouTube-`<iframe>` unter dem Plyr-Chrome: Play, Wellenform-
 * Fortschritt, Zeit, Speed, Lautstärke — sowie Zwei-Klick-Einbettung
 * für YouTube (keine Drittanbieter-Requests vor Klick).
 *
 * Wir horchen auf `livewire:init` und danach auf `livewire:navigated`
 * bzw. `livewire:updated`, damit auch dynamisch neu gerenderte Player
 * (Type-Wechsel im Audiovisual, neuer Block ohne Full-Page-Reload)
 * automatisch initialisiert werden. Bereits geplyrde Elemente tragen
 * `data-plyr-ready="1"` und werden übersprungen.
 */

import Plyr from 'plyr';

function initAll(root = document) {
    const audios = root.querySelectorAll(
        'audio.cc-plyr:not([data-plyr-ready])'
    );
    audios.forEach((el) => {
        el.setAttribute('data-plyr-ready', '1');
        new Plyr(el, {
            controls: [
                'play',
                'progress',
                'current-time',
                'duration',
                'mute',
                'volume',
                'settings',
            ],
            settings: ['speed'],
            speed: { selected: 1, options: [0.75, 1, 1.25, 1.5] },
        });
    });

    const videos = root.querySelectorAll(
        'div.cc-plyr-video:not([data-plyr-ready])'
    );
    videos.forEach((el) => {
        el.setAttribute('data-plyr-ready', '1');
        new Plyr(el, {
            controls: [
                'play-large',
                'play',
                'progress',
                'current-time',
                'mute',
                'volume',
                'settings',
                'fullscreen',
            ],
            settings: ['speed'],
            speed: { selected: 1, options: [0.75, 1, 1.25, 1.5, 2] },
            youtube: { noCookie: true, rel: 0, showinfo: 0, modestbranding: 1 },
            loadSprite: true,
        });
    });
}

document.addEventListener('DOMContentLoaded', () => initAll());
document.addEventListener('livewire:init', () => initAll());
document.addEventListener('livewire:navigated', () => initAll());
window.addEventListener('cc-plyr-refresh', (e) => {
    const root = e?.detail?.root instanceof HTMLElement ? e.detail.root : document;
    initAll(root);
});

// Für Livewire-Component-Updates: nach jedem Morph nachziehen.
document.addEventListener('livewire:update', () => initAll());
