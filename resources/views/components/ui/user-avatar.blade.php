{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

Phase 5ac.2-Followup: Ein Avatar rendert entweder das hochgeladene Bild
oder ein Kürzel-Fallback mit der Farbe, die der Nutzer im Profil gewählt
hat. Wird überall dort verwendet, wo bisher aus dem Namen manuell ein
Buchstabe zusammengebaut wurde (Rail, Verlauf-Panel, Kommentar-Panel,
„Erarbeitet von"-Zeile im Dashboard, …).

Props:
- `:user` — App\Models\User (default: auth()->user())
- `size`  — Tailwind size-Suffix als String ('6', '8', '11', '24').
            Default '9' (36 px) — das übliche Chip-Format.
- `text`  — optionaler Font-Utility für den Kürzel-Text
--}}

@props([
    'user' => null,
    'size' => '9',
    'text' => 'text-caption font-semibold',
])

@php
    $u = $user ?? auth()->user();

    // Defensive Getter: der Prop akzeptiert Model UND Array (z. B. das
    // Fingerprint-Array aus livewire/project-permissions). Bei Models
    // laeuft der Zugriff via getAttribute() im try/catch, damit
    // MissingAttribute unter shouldBeStrict() nicht crasht. Bei Arrays
    // fallen wir auf 'first_name'/'last_name' zurueck, weil dort keine
    // Model-Konvention gilt.
    $getAttr = static function ($src, string $key) {
        if ($src === null) {
            return null;
        }
        if (is_array($src)) {
            if ($key === 'name' && ! array_key_exists('name', $src)) {
                return $src['first_name'] ?? null;
            }

            return $src[$key] ?? null;
        }
        // Eloquent-Model: getAttribute() im try/catch (shouldBeStrict).
        if (method_exists($src, 'getAttribute')) {
            try {
                return $src->getAttribute($key);
            } catch (\Throwable $e) {
                return null;
            }
        }
        // stdClass (z. B. DB::table()->get()): direkter Property-Zugriff.
        return $src->{$key} ?? null;
    };

    $firstName = (string) ($getAttr($u, 'first_name') ?? $getAttr($u, 'name') ?? '');
    $lastName = (string) ($getAttr($u, 'last_name') ?? '');
    $storedInitials = (string) ($getAttr($u, 'initials') ?? '');
    $initials = $storedInitials !== ''
        ? mb_strtoupper($storedInitials)
        : mb_strtoupper(
            (mb_substr(trim($firstName), 0, 1) ?: '?')
            .(mb_substr(trim($lastName), 0, 1) ?: '')
        );
    $storedColor = (string) ($getAttr($u, 'initials_color') ?? '');
    $color = $storedColor !== ''
        ? $storedColor
        : App\Support\ProfilePalette::defaultFor($firstName, $lastName);
    $avatarPath = $getAttr($u, 'avatar_path');
    $fullName = trim("{$firstName} {$lastName}");

    // Tailwind purged dynamische `size-{$n}`-Klassen aus dem Bundle,
    // wenn sie nicht als statischer String im Code stehen. Deshalb hier
    // eine kleine Whitelist: alle Groessen, die die Aufrufer aktuell
    // uebergeben (5,6,7,8,9,10,11,14,24) sind hier wortwoertlich da,
    // Tailwind sieht sie und liefert die Utility-Klasse aus.
    $sizeMap = [
        '5' => 'size-5', '6' => 'size-6', '7' => 'size-7', '8' => 'size-8',
        '9' => 'size-9', '10' => 'size-10', '11' => 'size-11', '12' => 'size-12',
        '14' => 'size-14', '16' => 'size-16', '20' => 'size-20', '24' => 'size-24',
    ];
    $sizeClass = $sizeMap[(string) $size] ?? 'size-9';
@endphp

<span
    {{ $attributes->merge(['class' => 'relative inline-flex '.$sizeClass.' items-center justify-center overflow-hidden rounded-full text-paper-0 shrink-0']) }}
    @if (! $avatarPath) style="background-color: var(--color-{{ $color }})" @endif
    role="img"
    aria-label="{{ $fullName }}"
    title="{{ $fullName }}"
>
    @if ($avatarPath)
        <img src="{{ asset('storage/uploads/avatars/'.$avatarPath) }}"
             alt=""
             class="absolute inset-0 size-full object-cover"/>
    @else
        <span class="{{ $text }}">{{ $initials }}</span>
    @endif
</span>
