<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 *
 * Phase 5ab.1 (Design v6 § 6): Fassungs-Speicher fuer den Verlauf-Panel.
 *
 * Jede Aenderung an einem Content-Model erzeugt eine Revision-Zeile mit
 * einem JSON-Snapshot des vorher-nachher-Deltas. Das Panel liest die
 * Revisions polymorphisch pro Subject (Chapter, Entry, Text, Gallery,
 * Image, Audiovisual) und rendert die Fassungs-Karten aus § 6.
 *
 * Bewusst neben Spatie Activitylog gefuehrt: der Log dient dem
 * Admin-Debug, der Verlauf ist ein Produkt-Feature. Aufraeumen des
 * einen darf das andere nicht kaputt machen.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisions', function (Blueprint $table) {
            $table->id();

            // Polymorphes Subject: welche Ressource wurde geaendert?
            // subject_type haelt den FQCN (z. B. App\Models\Chapter).
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');

            // Actor: wer hat geaendert. Nullable fuer System-Aenderungen
            // (z. B. spaeteres Cleanup-Job) und weil Users soft-deletable
            // sind — eine geloeschte User-ID darf den Verlauf nicht mit-
            // reissen.
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Art der Aenderung (§ 6 Chip): content | facts | reorder |
            // translation. Wir speichern es als schlichten String und
            // pflegen die Whitelist im App\Support\RevisionKind-Enum,
            // damit Bestandsdaten bei einer neuen Kategorie nicht
            // migriert werden muessen.
            $table->string('kind', 32);

            // Kurzfassung fuer die Karte (eine Zeile, deutsch). Der
            // ausfuehrliche Diff steht im snapshot.
            $table->string('summary', 255)->nullable();

            // Delta als JSON: { changes: { field: { old, new } },
            // meta: { … } }. LONGTEXT-Cast reicht fuer die Blade-
            // Description-Felder mit Rich-Text-Content.
            $table->json('snapshot');

            // Version-Nummer pro Subject (v1, v2, …). Wird bei der
            // Erstellung aus MAX(version)+1 abgeleitet.
            $table->unsignedInteger('version')->default(1);

            $table->timestamps();

            // Hauptindex fuer den Panel-Feed (§ 6): pro Subject
            // absteigend nach Zeit.
            $table->index(['subject_type', 'subject_id', 'created_at'], 'revisions_subject_created_idx');

            // Coalescing-Lookup (§ 8.2): letzte Revision desselben
            // Actors an demselben Subject binnen 5 Min.
            $table->index(['subject_type', 'subject_id', 'actor_id', 'created_at'], 'revisions_coalesce_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisions');
    }
};
