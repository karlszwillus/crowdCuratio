@props(['item', 'entry', 'chapter', 'project', 'listPermissions'])

{{--
    Q4-Etappe 2 / I5 (2026-08-27): Gallery-Block-Rendering aus
    resources/views/chapters/_canvas.blade.php extrahiert. Die zwei
    Bild-Iterationen (Kachel-Grid und Detail-Editor) rufen jeweils
    Sub-Sub-Components (components/content/gallery/*):
      - <x-content.gallery.image-tile>
      - <x-content.gallery.image-detail>
    Alpine-State und Methoden fuer Drag&Drop und Detail-Wechsel leben
    weiter im x-data der Card weiter unten — die Sub-Components sitzen
    im DOM-Scope davon.

    Der Gallery-Header (Card-Actions, Metadaten oben) bleibt hier
    inline — feinere Extraktion ist im Q4-Backlog als I5a notiert,
    wenn der aktuelle LoC-Stand noch weiter reduziert werden soll.
--}}

@if(isset($item->gallery))
    <li class="item gallery content" data-content="{{$item->id}}" data-entry="{{$entry->id}}" data-history-subject="Gallery:{{$item->gallery->id}}" id="{{$item->id}}" @can('update', $project) tabindex="0" aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown" title="{{ __('reorder_hint') }}" @endcan>
        <x-ui.block-card type="gallery" class="mb-4" :save-slot="'Gallery-'.$item->gallery->id">
            {{-- Phase 5y.1: Block-Aktionen wandern in den
                 `actions`-Slot der Block-Card. Vorher standen
                 Rueckgaengig/Kommentare/Bild-hinzufuegen/Loeschen
                 als eigene Row unter dem Beschreibungs-Editor —
                 daraus entstand die 250-px-Leerflaeche zwischen
                 Beschreibung und Bild-Raster (Briefing § 2). --}}
            <x-slot:actions>
                <x-ui.history-trigger
                    subjectType="Gallery"
                    :subjectId="$item->gallery->id"
                />

                @if(in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                    <x-comment.trigger
                        commentableType="App\Models\Gallery"
                        :commentableId="$item->gallery->id"
                        :count="isset($item->gallery->comments) ? count($item->gallery->comments) : 0"
                    />
                @endif

                {{-- 5y.4: „+ Bilder"-Zugang lebt jetzt im Rasterkopf und als Drop-Zone im Raster. --}}

                @if(in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                    <form action="{{ route('gallery.delete',$item->gallery->id) }}"
                          method="POST"
                          class="inline-flex">
                        @csrf
                        <input type="hidden" name="project" value="{!! $project->id !!}"/>
                        @method('DELETE')
                        <button type="submit"
                                onclick="return confirm('{{__('message_delete_confirm')}}')"
                                title="{{__('delete_block')}}"
                                class="inline-flex size-11 items-center justify-center rounded-md text-ink-500 hover:bg-danger-bg hover:text-danger">
                            <x-icon name="trash-2" size="4"/>
                        </button>
                    </form>
                @endif
            </x-slot:actions>

            <div>
                @can('update', $project)
                    {{-- A7 (2026-08-21): data-history-field-Wrapper
                         damit history-diff.js die Wort-Diffs an
                         die richtigen Gallery-Felder haengen kann. --}}
                    <div data-history-field="title">
                        <livewire:inline-editor
                            :model="$item->gallery"
                            field="title"
                            rules="nullable|string|max:255"
                            :label="__('title')"
                            :variant="'heading'"
                            :key="'gallery-title-'.$item->gallery->id"
                        />
                    </div>
                    <div data-history-field="subtitle">
                        <livewire:inline-editor
                            :model="$item->gallery"
                            field="subtitle"
                            rules="nullable|string|max:255"
                            :variant="'subtitle'"
                            :key="'gallery-subtitle-'.$item->gallery->id"
                        />
                    </div>
                    {{-- Phase 5y.1: der Rich-Text-Editor bleibt
                         fuer Editor:innen dauerhaft anzeigbar
                         (er ist die Bearbeitungsflaeche); dort
                         verursacht seine Mindesthoehe keine
                         Leerflaeche mehr, weil die Aktionen jetzt
                         im Kopf liegen und nicht mehr darunter. --}}
                    <div data-history-field="description">
                        <livewire:rich-text-editor
                            :model="$item->gallery"
                            field="description"
                            rules="nullable|string"
                            :label="__('gallery_description')"
                            :key="'gallery-description-'.$item->gallery->id"
                        />
                    </div>
                @else
                    <h4 class="text-heading font-semibold text-ink-900">{{$item->gallery->title}}</h4>
                    @if (! empty(trim($item->gallery->subtitle ?? '')))
                        <p class="text-body text-ink-600">{{$item->gallery->subtitle}}</p>
                    @endif
                    {{-- Phase 5y.1: leere Beschreibung darf keine
                         Zeile reservieren (Briefing § 2 Punkt 4). --}}
                    @if (! empty(trim(strip_tags((string) $item->gallery->description))))
                        <div class="text-body text-ink-800">
                            {!! $item->gallery->description !!}
                        </div>
                    @endif
                @endcan
            </div>
            {{-- public/css/crowdcuratio.css wird seit dem
                 Vite-Umbau nicht mehr geladen — die alten
                 .gallery_container-Grid-Regeln greifen nicht.
                 Wir setzen das Grid komplett per Tailwind-
                 Utilities direkt am Element. --}}
            @if ($item->gallery->images->isEmpty())
                @can('update', $project)
                    {{-- 5y.4 § 7 leer: Drop-Zone ueber volle Blockbreite, kein leeres Raster.
                         Q4-Etappe 4 / F-Nachreview (2026-09-08): früher Button mit
                         data-target="#imageModal" — der öffnete das Legacy-Add-Image-Modal
                         und akzeptierte kein Drop. Jetzt echte Dropzone mit direktem
                         XHR-Upload gegen gallery.images.drop, identisch zur letzten Kachel
                         im gefüllten Grid. --}}
                    <div x-data="{
                            dragging: false,
                            uploads: [],
                            rejected: [],
                            dropUrl: '{{ route('gallery.images.drop', $item->gallery->id) }}',
                            handleFiles(fileList) {
                                if (!fileList || !fileList.length) return;
                                const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                                const maxBytes = 4 * 1024 * 1024;
                                const queue = [];
                                for (const file of fileList) {
                                    if (!allowed.includes(file.type)) { this.rejected.push({name: file.name, reason: '{{ __('gallery_rejected_reason_type') }}'}); continue; }
                                    if (file.size > maxBytes) { this.rejected.push({name: file.name, reason: '{{ __('gallery_rejected_reason_size') }}'}); continue; }
                                    queue.push(file);
                                }
                                if (!queue.length) return;
                                let pending = queue.length;
                                const totalAtStart = queue.length;
                                const uploadedIds = [];
                                for (const file of queue) {
                                    const ghostId = 'ghost-' + Math.random().toString(36).slice(2, 9);
                                    const entry = { id: ghostId, name: file.name, progress: 0, status: 'uploading' };
                                    this.uploads.push(entry);
                                    const token = document.querySelector('meta[name=csrf-token]')?.content;
                                    const xhr = new XMLHttpRequest();
                                    xhr.open('POST', this.dropUrl);
                                    xhr.setRequestHeader('X-CSRF-TOKEN', token);
                                    xhr.setRequestHeader('Accept', 'application/json');
                                    xhr.upload.onprogress = (e) => { if (e.lengthComputable) entry.progress = Math.round((e.loaded / e.total) * 100); };
                                    xhr.onload = () => {
                                        if (xhr.status >= 200 && xhr.status < 300) {
                                            entry.status = 'done';
                                            try { const p = JSON.parse(xhr.responseText); if (p && p.image && p.image.id) uploadedIds.push(p.image.id); } catch (e) {}
                                        } else {
                                            entry.status = 'error';
                                            this.rejected.push({name: entry.name, reason: '{{ __('gallery_rejected_reason_server') }}'});
                                        }
                                        pending -= 1;
                                        if (pending <= 0) {
                                            const url = new URL(window.location.href);
                                            if (totalAtStart === 1 && uploadedIds.length === 1) url.searchParams.set('editImage', uploadedIds[0]);
                                            // Q4-Etappe 4 / F-Nachreview (2026-09-08): Reload
                                            // mit Fragment auf den Galerie-Block, damit der
                                            // Browser nicht an den Seitenanfang scrollt.
                                            url.hash = 'anchor_MediaContent_{{ $item->id }}';
                                            setTimeout(() => { window.location.href = url.toString(); }, 600);
                                        }
                                    };
                                    xhr.onerror = () => {
                                        entry.status = 'error';
                                        this.rejected.push({name: entry.name, reason: '{{ __('gallery_rejected_reason_server') }}'});
                                        pending -= 1;
                                        if (pending <= 0) setTimeout(() => {
                                            const url = new URL(window.location.href);
                                            url.hash = 'anchor_MediaContent_{{ $item->id }}';
                                            window.location.href = url.toString();
                                        }, 400);
                                    };
                                    const fd = new FormData();
                                    fd.append('file', file);
                                    xhr.send(fd);
                                }
                            }
                         }"
                         @dragover.prevent="dragging = true"
                         @dragenter.prevent="dragging = true"
                         @dragleave.prevent="dragging = false"
                         @drop.prevent="dragging = false; handleFiles($event.dataTransfer.files)"
                         :class="dragging ? 'border-primary bg-primary/10 text-primary' : 'border-line-200'"
                         class="mt-4 rounded-md border-2 border-dashed bg-transparent text-body text-ink-500 hover:border-ink-400 hover:bg-line-100/40 hover:text-ink-700 focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-primary">
                        <label class="flex w-full cursor-pointer flex-col items-center justify-center gap-2 px-4 py-8"
                               aria-label="{{ __('gallery_header_add') }}">
                            <x-icon name="image-plus" size="5"/>
                            <span class="text-body font-medium text-ink-700">{{ __('gallery_dropzone_title') }}</span>
                            <span class="text-caption text-ink-500">{{ __('gallery_dropzone_hint') }}</span>
                            <span class="mt-1 inline-flex items-center gap-1 rounded-md border border-line-200 bg-paper-0 px-3 py-1 text-caption font-medium text-ink-900">
                                {{ __('gallery_dropzone_button') }}
                            </span>
                            <input type="file" class="sr-only" multiple accept="image/jpeg,image/png,image/gif,image/webp"
                                   @change="handleFiles($event.target.files); $event.target.value = ''"/>
                        </label>
                        {{-- Progress-Zeile pro Upload — kompakt, damit
                             die leere-Galerie-Zone nicht plötzlich hoch springt. --}}
                        <template x-for="entry in uploads" :key="entry.id">
                            <div class="mx-4 mb-3 flex items-center gap-2 text-caption text-ink-500">
                                <span x-text="entry.name" class="min-w-0 flex-1 truncate"></span>
                                <span x-text="entry.status === 'done' ? '{{ __('gallery_upload_done') }}' : '{{ __('gallery_upload_progress') }}'"></span>
                                <span x-text="entry.progress + '%'"></span>
                            </div>
                        </template>
                    </div>
                @else
                    <p class="mt-4 rounded-md border border-line-200 bg-paper-50 px-4 py-3 text-body text-ink-500">
                        {{ __('placeholder_gallery_hint') }}
                    </p>
                @endcan
            @else
            {{-- Phase 5y.2 + 5y.3 + 5y.5 + 5y.7: Kachel-Raster ODER Detailzeile.
                 Alpine-State `editingImageId` schaltet die beiden Ansichten um; die
                 Detailzeile ersetzt das Raster im selben Block, ohne Modal (Briefing
                 § 5). Papierkorb-Kaskade aus der alten Meta-Reihe unter der Kachel
                 entfaellt komplett — Bild entfernen sitzt jetzt als Overlay unten
                 auf der Kachel (permanent Touch, hover/focus Desktop). --}}
            <div x-ref="body" x-data="{
                editingImageId: null,
                pickedId: null,
                originalOrder: [],
                announcement: '',
                hadEdits: false,
                uploads: [],
                rejected: [],
                dropUrl: '{{ route('gallery.images.drop', $item->gallery->id) }}',
                handleFiles(fileList) {
                    if (!fileList || !fileList.length) return;
                    const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    const maxBytes = 4 * 1024 * 1024;
                    const queue = [];
                    for (const file of fileList) {
                        if (!allowed.includes(file.type)) {
                            this.rejected.push({ name: file.name, reason: '{{ __('gallery_rejected_reason_type') }}' });
                            continue;
                        }
                        if (file.size > maxBytes) {
                            this.rejected.push({ name: file.name, reason: '{{ __('gallery_rejected_reason_size') }}' });
                            continue;
                        }
                        queue.push(file);
                    }
                    if (!queue.length) return;
                    let pending = queue.length;
                    const totalAtStart = queue.length;
                    const uploadedIds = [];
                    for (const file of queue) {
                        const ghostId = 'ghost-' + Math.random().toString(36).slice(2, 9);
                        const previewUrl = URL.createObjectURL(file);
                        const entry = { id: ghostId, name: file.name, previewUrl, progress: 0, status: 'uploading', xhr: null };
                        this.uploads.push(entry);
                        this.uploadOne(entry, file, (newId) => {
                            if (newId) uploadedIds.push(newId);
                            pending -= 1;
                            if (pending <= 0) {
                                const url = new URL(window.location.href);
                                // 5y.9: bei genau einer erfolgreich hochgeladenen Datei
                                // die Detailzeile direkt oeffnen, damit der Nutzer
                                // Copyright/Quelle in einem Rutsch nachpflegt.
                                if (totalAtStart === 1 && uploadedIds.length === 1) {
                                    url.searchParams.set('editImage', uploadedIds[0]);
                                }
                                // Q4-Etappe 4 / F-Nachreview (2026-09-08): Fragment
                                // auf den Galerie-Block, damit der Browser nicht an
                                // den Seitenanfang scrollt.
                                url.hash = 'anchor_MediaContent_{{ $item->id }}';
                                setTimeout(() => { window.location.href = url.toString(); }, 600);
                            }
                        });
                    }
                },
                uploadOne(entry, file, done) {
                    const token = document.querySelector('meta[name=csrf-token]')?.content;
                    const xhr = new XMLHttpRequest();
                    entry.xhr = xhr;
                    xhr.open('POST', this.dropUrl);
                    xhr.setRequestHeader('X-CSRF-TOKEN', token);
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) {
                            entry.progress = Math.round((e.loaded / e.total) * 100);
                        }
                    };
                    xhr.onload = () => {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            entry.status = 'done';
                            entry.progress = 100;
                            let newId = null;
                            try {
                                const payload = JSON.parse(xhr.responseText);
                                newId = payload && payload.image && payload.image.id ? payload.image.id : null;
                            } catch (e) { /* ignore */ }
                            done(newId);
                        } else {
                            entry.status = 'error';
                            this.rejected.push({ name: entry.name, reason: '{{ __('gallery_rejected_reason_server') }}' });
                            this.uploads = this.uploads.filter(u => u.id !== entry.id);
                            done(null);
                        }
                    };
                    xhr.onerror = () => {
                        entry.status = 'error';
                        this.rejected.push({ name: entry.name, reason: '{{ __('gallery_rejected_reason_server') }}' });
                        this.uploads = this.uploads.filter(u => u.id !== entry.id);
                        done(null);
                    };
                    const fd = new FormData();
                    fd.append('file', file);
                    xhr.send(fd);
                },
                cancelUpload(id) {
                    const entry = this.uploads.find(u => u.id === id);
                    if (entry && entry.xhr) entry.xhr.abort();
                    this.uploads = this.uploads.filter(u => u.id !== id);
                },
                prefersReducedMotion() {
                    return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                },
                interpolateHeight(fromH, toH, duration) {
                    const body = this.$refs.body;
                    if (!body) return;
                    body.style.overflow = 'hidden';
                    body.style.height = fromH + 'px';
                    body.style.transition = 'height ' + duration + 'ms ease-out';
                    requestAnimationFrame(() => {
                        body.style.height = toH + 'px';
                    });
                    setTimeout(() => {
                        body.style.height = '';
                        body.style.overflow = '';
                        body.style.transition = '';
                    }, duration + 30);
                },
                flipElement(el, from, to, duration, delay) {
                    if (!el) return;
                    const dx = from.left - to.left;
                    const dy = from.top - to.top;
                    const sx = to.width === 0 ? 1 : from.width / to.width;
                    const sy = to.height === 0 ? 1 : from.height / to.height;
                    el.style.transformOrigin = 'top left';
                    el.style.transition = 'none';
                    el.style.transform = 'translate(' + dx + 'px, ' + dy + 'px) scale(' + sx + ', ' + sy + ')';
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            el.style.transition = 'transform ' + duration + 'ms cubic-bezier(0.2, 0.7, 0.3, 1) ' + delay + 'ms';
                            el.style.transform = '';
                        });
                    });
                    setTimeout(() => {
                        el.style.transition = '';
                        el.style.transform = '';
                        el.style.transformOrigin = '';
                    }, duration + delay + 50);
                },
                focusFirstField(imageId) {
                    const row = Array.from(this.$refs.body.querySelectorAll('.gallery-detail-row')).find(el => String(el.dataset.imageId) === String(imageId));
                    if (!row) return;
                    const field = row.querySelector('input:not([type=hidden]), textarea, [contenteditable], button:not([data-flip-skip])');
                    if (field) field.focus({ preventScroll: true });
                },
                enterDetail(imageId) {
                    if (this.prefersReducedMotion()) {
                        this.editingImageId = imageId;
                        this.$nextTick(() => this.focusFirstField(imageId));
                        return;
                    }
                    const body = this.$refs.body;
                    const tileFrame = Array.from(body.querySelectorAll('.gallery-tile-frame')).find(el => String(el.dataset.imageId) === String(imageId));
                    const fromRect = tileFrame ? tileFrame.getBoundingClientRect() : null;
                    const fromHeight = body.getBoundingClientRect().height;
                    this.editingImageId = imageId;
                    this.$nextTick(() => {
                        const toHeight = body.getBoundingClientRect().height;
                        this.interpolateHeight(fromHeight, toHeight, 200);
                        const detailPreview = Array.from(body.querySelectorAll('.gallery-detail-preview')).find(el => String(el.dataset.imageId) === String(imageId));
                        if (detailPreview && fromRect) {
                            const toRect = detailPreview.getBoundingClientRect();
                            this.flipElement(detailPreview, fromRect, toRect, 180, 0);
                        }
                        setTimeout(() => this.focusFirstField(imageId), 240);
                    });
                },
                exitDetail() {
                    if (this.hadEdits) { window.location.reload(); return; }
                    if (this.prefersReducedMotion()) {
                        this.editingImageId = null;
                        return;
                    }
                    const body = this.$refs.body;
                    const imageId = this.editingImageId;
                    const detailPreview = Array.from(body.querySelectorAll('.gallery-detail-preview')).find(el => String(el.dataset.imageId) === String(imageId));
                    const fromRect = detailPreview ? detailPreview.getBoundingClientRect() : null;
                    const fromHeight = body.getBoundingClientRect().height;
                    this.editingImageId = null;
                    this.$nextTick(() => {
                        const toHeight = body.getBoundingClientRect().height;
                        this.interpolateHeight(fromHeight, toHeight, 200);
                        const tileFrame = Array.from(body.querySelectorAll('.gallery-tile-frame')).find(el => String(el.dataset.imageId) === String(imageId));
                        if (tileFrame && fromRect) {
                            const toRect = tileFrame.getBoundingClientRect();
                            this.flipElement(tileFrame, fromRect, toRect, 180, 0);
                        }
                    });
                },
                reorderUrl: '{{ route('gallery.images.reorder', $item->gallery->id) }}',
                announce(text) {
                    this.announcement = '';
                    this.$nextTick(() => { this.announcement = text; });
                },
                snapshotOrder() {
                    this.originalOrder = Array.from(this.$refs.grid.children)
                        .map(el => el.dataset.imageId)
                        .filter(Boolean);
                },
                currentIndex(id) {
                    const items = Array.from(this.$refs.grid.children);
                    return items.findIndex(el => el.dataset.imageId === String(id));
                },
                pick(id) {
                    this.pickedId = id;
                    this.snapshotOrder();
                    const pos = this.currentIndex(id) + 1;
                    const total = this.$refs.grid.children.length;
                    this.announce(`Bild ${pos} von ${total} aufgenommen. Pfeiltasten verschieben, Leertaste ablegen, Esc abbrechen.`);
                },
                drop() {
                    const pos = this.currentIndex(this.pickedId) + 1;
                    const total = this.$refs.grid.children.length;
                    this.announce(`Bild an Position ${pos} von ${total} abgelegt.`);
                    this.pickedId = null;
                    this.persistOrder();
                },
                cancel() {
                    const grid = this.$refs.grid;
                    const byId = {};
                    Array.from(grid.children).forEach(el => { if (el.dataset.imageId) byId[el.dataset.imageId] = el; });
                    this.originalOrder.forEach(id => { if (byId[id]) grid.appendChild(byId[id]); });
                    this.pickedId = null;
                    this.announce('Sortierung abgebrochen.');
                },
                moveBy(id, delta) {
                    const grid = this.$refs.grid;
                    const items = Array.from(grid.children);
                    const from = this.currentIndex(id);
                    const to = from + delta;
                    if (from < 0 || to < 0 || to >= items.length) return;
                    const target = items[to];
                    if (delta > 0) {
                        grid.insertBefore(items[from], target.nextSibling);
                    } else {
                        grid.insertBefore(items[from], target);
                    }
                    const pos = this.currentIndex(id) + 1;
                    this.announce(`Bild an Position ${pos} von ${items.length} verschoben.`);
                },
                initSortable() {
                    const grid = this.$refs.grid;
                    if (!grid || typeof Sortable === 'undefined') return;
                    Sortable.create(grid, {
                        handle: '.gallery-drag-handle',
                        animation: 150,
                        ghostClass: 'opacity-40',
                        group: { name: 'gallery-images-' + this.reorderUrl, pull: false, put: false },
                        onEnd: () => this.persistOrder(),
                    });
                },
                renumberPositions() {
                    const grid = this.$refs.grid;
                    if (!grid) return;
                    Array.from(grid.children).forEach((el, index) => {
                        if (!el.dataset || !el.dataset.imageId) return;
                        const num = el.querySelector('[data-image-position]');
                        if (num) num.textContent = index + 1;
                    });
                },
                persistOrder() {
                    this.renumberPositions();
                    const ids = Array.from(this.$refs.grid.children)
                        .map(el => el.dataset.imageId)
                        .filter(Boolean);
                    const token = document.querySelector('meta[name=csrf-token]')?.content;
                    Alpine.store('saveStatus')?.set?.('saving');
                    fetch(this.reorderUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ ids }),
                    })
                        .then(r => r.ok
                            ? Alpine.store('saveStatus')?.set?.('saved')
                            : Alpine.store('saveStatus')?.set?.('error'))
                        .catch(() => Alpine.store('saveStatus')?.set?.('error'));
                },
            }" x-init="initSortable(); window.addEventListener('saved', (e) => { if (e && e.detail && e.detail.model === 'Image') { hadEdits = true; } }); { const params = new URLSearchParams(window.location.search); const editParam = params.get('editImage'); if (editParam) { const parsed = parseInt(editParam, 10); if (!isNaN(parsed)) { $nextTick(() => { editingImageId = parsed; setTimeout(() => focusFirstField(parsed), 100); /* Q4-Etappe 4 / F-Nachreview (2026-09-08): nach dem Umschalten auf die Detail-View verschiebt sich die Y-Position — der initiale Browser-Scroll zum #anchor_MediaContent trifft dann ins Leere. Deshalb hier ein zweites scrollIntoView, nachdem Alpine die Detail-View gerendert hat. */ requestAnimationFrame(() => { const anchor = document.getElementById('anchor_MediaContent_{{ $item->id }}'); if (anchor) anchor.scrollIntoView({ block: 'start', behavior: 'instant' }); }); }); } params.delete('editImage'); /* Fragment beim URL-Cleanup mit-erhalten, sonst verliert der Browser den Anchor bei späteren Aktionen. */ const clean = window.location.pathname + (params.toString() ? '?' + params.toString() : '') + window.location.hash; window.history.replaceState({}, '', clean); } }">
            <div
                role="status"
                aria-live="polite"
                class="sr-only"
                x-text="announcement"
            ></div>

                {{-- Raster --}}
                {{-- 5y.4: Rasterkopf mit Anzahl, Sammelwarnung, Reihenfolge-Hinweis und Add-Button. --}}
                <div x-show="editingImageId === null"
                     class="mt-5 flex flex-wrap items-center justify-between gap-3 border-b border-line-200 pb-2">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-caption font-semibold uppercase tracking-wider text-ink-500">{{ __('gallery_header_label') }}</span>
                        <span class="text-body text-ink-700">{{ trans_choice('gallery_header_count', $item->gallery->images->count(), ['count' => $item->gallery->images->count()]) }}</span>
                        @php
                            $missingCount = $item->gallery->images->filter(fn($img) =>
                                empty(trim(strip_tags((string) $img->description)))
                                || ! $img->copyrightImage
                                || ! $img->originImage
                            )->count();
                        @endphp
                        @if ($missingCount > 0)
                            <span class="inline-flex items-center gap-1 rounded-md bg-warning-bg px-2 py-0.5 text-caption text-warning">
                                {{ trans_choice('gallery_header_missing', $missingCount, ['count' => $missingCount]) }}
                            </span>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        @can('update', $project)
                            <span class="text-caption text-ink-500">{{ __('gallery_header_order_hint') }}</span>
                            <button type="button"
                                    class="addImage inline-flex items-center gap-1 rounded-md bg-primary px-3 py-1.5 text-caption font-medium text-primary-on hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                                    data-chapter="{{ $chapter->name }}"
                                    data-entry="{{ $entry->name }}"
                                    data-id="{{ $item->gallery->id }}"
                                    data-entryId="{{ $entry->id }}"
                                    data-toggle="modal"
                                    data-target="#imageModal">
                                <x-icon name="plus" size="3"/>
                                <span>{{ __('gallery_header_add') }}</span>
                            </button>
                        @endcan
                    </div>
                </div>

                @cannot('update', $project)
                    <p class="mt-2 rounded-md bg-info-bg px-3 py-2 text-caption text-info">
                        {{ __('gallery_reader_hint') }}
                    </p>
                @endcannot

                {{-- 5y.9: Banner mit abgewiesenen Dateien. --}}
                <div x-show="rejected.length > 0" x-cloak
                     class="mt-3 rounded-md border border-warning-bg bg-warning-bg/40 px-3 py-2 text-caption text-ink-700">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-warning">⚠ {{ __('gallery_rejected_title') }}</span>
                        <button type="button" @click="rejected = []" class="text-caption underline decoration-dotted underline-offset-2">
                            {{ __('gallery_rejected_dismiss') }}
                        </button>
                    </div>
                    <ul class="mt-1 space-y-0.5">
                        <template x-for="(r, i) in rejected" :key="i">
                            <li><span class="font-medium text-ink-900" x-text="r.name"></span> — <span x-text="r.reason"></span></li>
                        </template>
                    </ul>
                </div>

                <div x-show="editingImageId === null"
                     x-ref="grid"
                     class="mt-4 grid gap-[14px]"
                     style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));">
                    @foreach($item->gallery->images as $image)
                        <x-content.gallery.image-tile :image="$image" :item="$item" :project="$project" :list-permissions="$listPermissions" :position="$loop->iteration"/>
                    @endforeach

                    @can('update', $project)
                        {{-- 5y.9: Optimistische Ghost-Kacheln waehrend Upload. --}}
                        <template x-for="entry in uploads" :key="entry.id">
                            <div class="relative">
                                <div class="relative flex aspect-video items-center justify-center overflow-hidden rounded-md bg-line-100">
                                    <img :src="entry.previewUrl" alt="" class="max-h-full max-w-full object-contain opacity-70"/>
                                    <div class="absolute inset-x-0 bottom-0 flex flex-col gap-1 bg-ink-900/70 px-2 py-1.5 text-caption text-white">
                                        <div class="flex items-center justify-between">
                                            <span x-text="entry.status === 'done' ? '{{ __('gallery_upload_done') }}' : '{{ __('gallery_upload_progress') }}'"></span>
                                            <button type="button"
                                                    x-show="entry.status !== 'done'"
                                                    @click="cancelUpload(entry.id)"
                                                    class="text-caption underline decoration-dotted underline-offset-2">
                                                {{ __('gallery_upload_cancel') }}
                                            </button>
                                        </div>
                                        <div class="h-1 w-full overflow-hidden rounded bg-white/20">
                                            <div class="h-full bg-info" :style="'width: ' + entry.progress + '%'"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-1.5 truncate text-body text-ink-500" x-text="entry.name"></div>
                            </div>
                        </template>
                        {{-- 5y.4 + 5y.9: Drop-Zone als letzte Kachel — Klick UND echtes File-Drop.
                             Beim Drop landen die Dateien im imageModal-Input, der Klick oeffnet
                             das Modal ueber den bestehenden .addImage-Handler. --}}
                        {{-- 5y.9: Drop-Zone mit optimistischem Upload.
                             Klick oeffnet den nativen File-Picker,
                             Drop laesst die Dateien direkt hochladen. --}}
                        <div x-data="{ dragging: false }"
                             @dragover.prevent="dragging = true"
                             @dragenter.prevent="dragging = true"
                             @dragleave.prevent="dragging = false"
                             @drop.prevent="dragging = false; handleFiles($event.dataTransfer.files)"
                             :class="dragging ? 'border-primary bg-primary/10 text-primary' : 'border-line-200'"
                             class="group relative flex aspect-video w-full flex-col items-center justify-center gap-1 rounded-md border-2 border-dashed bg-transparent text-caption text-ink-500 hover:border-ink-400 hover:bg-line-100/40 hover:text-ink-700 focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-primary">
                            <label class="flex h-full w-full cursor-pointer flex-col items-center justify-center gap-1" aria-label="{{ __('gallery_header_add') }}">
                                <x-icon name="image-plus" size="5"/>
                                <span class="text-caption font-medium">{{ __('gallery_dropzone_title') }}</span>
                                <span class="text-caption text-ink-500">{{ __('gallery_dropzone_hint') }}</span>
                                <input type="file" class="sr-only" multiple accept="image/jpeg,image/png,image/gif,image/webp"
                                       @change="handleFiles($event.target.files); $event.target.value = ''"/>
                            </label>
                        </div>
                    @endcan
                </div>

                {{-- Detailzeile: ersetzt das Raster fuer eine einzelne Kachel.
                     Jedes Bild rendert seine eigene, per x-show gefiltert. --}}
                @can('update', $project)
                    @foreach($item->gallery->images as $image)
                        <x-content.gallery.image-detail :image="$image" :item="$item" :project="$project" :list-permissions="$listPermissions" :position="$loop->iteration" :total="$item->gallery->images->count()"/>
                    @endforeach
                @endcan
            </div>
            @endif
        </x-ui.block-card>
    </li>
@endif
