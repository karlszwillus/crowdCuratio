# Glossar — crowdCuratio

Kurz-Definitionen der Kern-Begriffe, an Nutzer:innen und
Weiterentwickelnde gerichtet.

**Für UI-Wording, Ton, Erfolgs-/Fehler-Muster:** interne Wording-
Regel (nicht öffentlich).

## Hierarchie-Ebenen

- **Projekt** (`Project`) — Das ganze Vorhaben, z. B. eine
  Ausstellung, eine Sammlung, ein redaktioneller Bestand.
- **Kapitel** (`Chapter`) — Thematischer Hauptabschnitt innerhalb
  eines Projekts.
- **Abschnitt** (`Entry`) — Strukturelle Untereinheit eines Kapitels.
- **Inhalt** (`Content`) — Konkreter Baustein innerhalb eines
  Abschnitts. Sub-Typen siehe unten.

**Tiefenstruktur:** Projekt → Kapitel → Abschnitt → Inhalt.

## Inhalts-Typen

- **Text** — Geschriebener Rich-Text.
- **Bild** — Einzelnes Bild mit Urheberrecht und Quelle.
- **Bildergalerie** — Mehrere Bilder zusammen.
- **Audio/Video** — Audio- und Video-Inhalte.

## Rollen

- **Admin** — Systemweite Verwaltung.
- **Projekt-Inhaber:in** (Owner) — hat das Projekt angelegt.
- **Editor:in** — darf Inhalte bearbeiten.
- **Reviewer:in** — darf lesen und kommentieren.
- **Leser:in** — darf lesen.

**Sammelbegriff für Team-Mitglieder eines Projekts:**
**Mitarbeitende**.

## Content-Struktur-Begriffe für Entwickler:innen

- **Source** — Quellen-/Copyright-Eintrag, referenziert von Bildern
  und Texten.
- **Revision** — polymorphe Fassung eines Content-Objekts (Historie
  mit Diff und Wiederherstellen).
- **Permission (projekt-scoped)** — projekt-bezogene Rechte-Vergabe
  ergänzt das systemweite Rollen-Modell.

## Hinweis für Code-Ebene

Die interne Code-Ebene bleibt englisch — `Project`, `Chapter`,
`Entry`, `Content`, `Source`. Die deutschen Begriffe oben gelten nur
für UI-Strings, Dokumentation und Kunden-Kommunikation.
