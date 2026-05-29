<?php
/**
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program in the file LICENSE.

If not, see <https://www.gnu.org/licenses/>.
 */
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        \App\Models\Project::class => \App\Policies\ProjectPolicy::class,
        \App\Models\Chapter::class => \App\Policies\ChapterPolicy::class,
        \App\Models\Entry::class   => \App\Policies\EntryPolicy::class,
        // Text, Image, Gallery, Comment kommen in Phase 4 zusammen mit
        // ADR-0012 (media_content vs. direct entry binding) und der
        // CommentTrait-Auflösung (F-ARCH-002).
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Die vorherigen Gate-Closures (edit-project, add-project,
        // delete-project, publish-project, comment-project) sind im
        // Cleanup-Schritt nach Phase 1 entfernt. Sie liefen schon
        // semantisch schief — verglichen $user->id === $project gegen
        // einen User-Id-Wert, der je nach Caller mal das Project-Modell,
        // mal eine User-Id war — und wurden nach D.9 nur noch vom
        // chapters/index.blade.php konsumiert. Authorization läuft jetzt
        // ausschließlich über die Policies oben.
    }
}
