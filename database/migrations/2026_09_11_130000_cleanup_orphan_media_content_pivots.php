<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

use App\Models\Audiovisual;
use App\Models\DataFactBlock;
use App\Models\Gallery;
use App\Models\QuoteBlock;
use App\Models\Text;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Q4-Etappe 7 · E7-Followup (2026-09-11): Aufraeumen der verwaisten
 * MediaContent-Pivots, die entstanden sind bevor der neue
 * `CascadesToMediaContent`-Trait an den Block-Models haengt.
 *
 * Ein Pivot ist verwaist, wenn seine `content_id` auf einen Block
 * zeigt, der selbst schon soft-deleted ist (deleted_at gesetzt) oder
 * gar nicht mehr existiert. Solche Pivots stehen im Canvas als
 * unsichtbare Item-Zeile — der Block-View skipt via `@isset`, die
 * Add-Bar dahinter rendert weiter, es entstehen zwei identische
 * Add-Bars in Folge.
 *
 * Wir soft-loeschen die verwaisten Pivots (keine Force-Delete): der
 * neue Trait arbeitet ebenfalls per Soft-Delete, damit bleibt der
 * Zustand konsistent und ein spaeter geplantes Restore koennte
 * beides zurueckholen.
 */
return new class extends Migration
{
    private array $blockTables = [
        Text::class => 'texts',
        Audiovisual::class => 'audiovisuals',
        Gallery::class => 'galleries',
        QuoteBlock::class => 'quote_blocks',
        DataFactBlock::class => 'data_fact_blocks',
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->blockTables as $class => $table) {
            // Pivots, deren Ziel-Block soft-deleted oder gar nicht
            // mehr vorhanden ist. LEFT JOIN + IS NULL fasst beide
            // Faelle in einer Query zusammen.
            DB::table('media_content')
                ->leftJoin($table, function ($join) use ($table) {
                    $join->on('media_content.content_id', '=', $table.'.id')
                        ->whereNull($table.'.deleted_at');
                })
                ->where('media_content.content_type', $class)
                ->whereNull('media_content.deleted_at')
                ->whereNull($table.'.id')
                ->update(['media_content.deleted_at' => $now]);
        }
    }

    public function down(): void
    {
        // Kein Rollback: der Datenverlust ist schon in den Blocks,
        // die Pivot-Zeilen alleine wiederherzustellen wuerde die
        // urspruengliche verwaiste Situation zurueckbringen.
    }
};
