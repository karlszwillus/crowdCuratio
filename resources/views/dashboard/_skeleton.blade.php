{{--
Skelett-Ladezustand fuer das Dashboard (Phase-5-Backlog #70).
Rendered als placeholder() der Livewire-Component dashboard-sections
solange die Sektionen im ersten Round-Trip nachladen.

Muster: leere graue Karten in derselben Grid-Geometrie wie die
Ist-Sektionen, damit der Layout-Sprung beim Nachladen minimal ist.
Die animate-pulse-Klasse liefert die subtile Shimmer-Bewegung.
--}}

<div class="animate-pulse space-y-10">

    {{-- Wiederaufnahme-Zeile (Placeholder). --}}
    <div class="flex items-center gap-4 rounded-lg border border-line-200 border-l-[3px] border-l-brand-bar bg-paper-0 px-4 py-3">
        <span class="h-[34px] w-[44px] shrink-0 rounded bg-line-200" aria-hidden="true"></span>
        <div class="flex-1 space-y-2">
            <div class="h-3 w-32 rounded bg-line-200"></div>
            <div class="h-4 w-64 rounded bg-line-200"></div>
        </div>
        <span class="h-10 w-32 rounded-md bg-line-200"></span>
    </div>

    {{-- Meine Projekte (Placeholder). --}}
    <section>
        <div class="mb-3 flex items-center justify-between">
            <div class="h-5 w-40 rounded bg-line-200"></div>
        </div>
        <div class="grid grid-cols-1 gap-[14px] sm:grid-cols-2 lg:grid-cols-3">
            @for ($i = 0; $i < 3; $i++)
                <div class="overflow-hidden rounded-[11px] border border-line-200 bg-paper-0">
                    <div class="h-[66px] w-full bg-line-200"></div>
                    <div class="space-y-2 px-[14px] py-3">
                        <div class="h-4 w-3/4 rounded bg-line-200"></div>
                        <div class="h-3 w-1/2 rounded bg-line-200"></div>
                    </div>
                </div>
            @endfor
        </div>
    </section>

    {{-- Mir zugeteilt (Placeholder). --}}
    <section>
        <div class="mb-3 flex items-center justify-between">
            <div class="h-5 w-32 rounded bg-line-200"></div>
        </div>
        <div class="grid grid-cols-1 gap-[14px] sm:grid-cols-2 lg:grid-cols-3">
            @for ($i = 0; $i < 2; $i++)
                <div class="overflow-hidden rounded-[11px] border border-line-200 bg-paper-0">
                    <div class="h-[66px] w-full bg-line-200"></div>
                    <div class="space-y-2 px-[14px] py-3">
                        <div class="h-4 w-2/3 rounded bg-line-200"></div>
                        <div class="h-3 w-1/2 rounded bg-line-200"></div>
                    </div>
                </div>
            @endfor
        </div>
    </section>

    {{-- Letzte Kommentare (Placeholder). --}}
    <section>
        <div class="mb-3 flex items-center justify-between">
            <div class="h-5 w-36 rounded bg-line-200"></div>
        </div>
        <div class="overflow-hidden rounded-lg border border-line-200 bg-paper-0">
            @for ($i = 0; $i < 3; $i++)
                <div class="flex items-start gap-3 border-b border-line-100 px-5 py-3 last:border-b-0">
                    <span class="mt-0.5 size-[30px] shrink-0 rounded-full bg-line-200"></span>
                    <div class="flex-1 space-y-2">
                        <div class="flex items-center gap-2">
                            <div class="h-3 w-24 rounded bg-line-200"></div>
                            <div class="h-3 w-48 rounded bg-line-100"></div>
                        </div>
                        <div class="h-3 w-full rounded bg-line-200"></div>
                        <div class="h-3 w-4/5 rounded bg-line-200"></div>
                    </div>
                </div>
            @endfor
        </div>
    </section>

</div>
