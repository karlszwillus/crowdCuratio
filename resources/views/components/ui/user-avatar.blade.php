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

    // Defensive Getter: mit shouldBeStrict() wirft direkter Property-
    // Zugriff auf ein nicht-eager-geladenes Feld (z. B. wenn ein User
    // nur mit id+name geladen wurde) MissingAttributeException. Ueber
    // getAttribute() bleibt es null — die Komponente muss ueberall
    // rendern, auch wenn der Aufrufer die Avatar-Felder vergessen hat.
    $getAttr = static function ($model, string $key) {
        if ($model === null) {
            return null;
        }
        try {
            return $model->getAttribute($key);
        } catch (\Throwable $e) {
            return null;
        }
    };

    $firstName = (string) ($getAttr($u, 'name') ?? '');
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
@endphp

<span
    {{ $attributes->merge(['class' => 'relative inline-flex size-'.$size.' items-center justify-center overflow-hidden rounded-full text-paper-0 shrink-0']) }}
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
