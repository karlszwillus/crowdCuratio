<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

use App\Models\Project;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Support\RoleName;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * B2 (2026-08-21) / DSGVO: Konto-Loeschung mit 30-Tage-Frist.
 *
 * Deckt die drei operativen Punkte ab:
 * - Schedule mit Owner-Uebergabe
 * - Cancel innerhalb der Frist (Login-Reaktivierung)
 * - Purge-Command nach Ablauf
 */

it('Schedule: User meldet Konto-Loeschung an und laeuft in die 30-Tage-Frist', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole(RoleName::READER->value);
    $this->actingAs($user);

    $response = $this->post(route('profile.schedule_deletion'), [
        'confirm' => 1,
        'reason' => 'Ausprobieren',
    ]);

    $response->assertRedirect(route('profile'));
    $user->refresh();
    expect($user->deletion_scheduled_at)->not->toBeNull();
    expect($user->deletion_reason)->toBe('Ausprobieren');
    expect($user->deletionDaysRemaining())->toBeGreaterThanOrEqual(29);
});

it('Schedule: verweigert Owner-Loeschung ohne Uebergabe', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    Project::factory()->create(['user_id' => $owner->id]);
    $this->actingAs($owner);

    $response = $this->post(route('profile.schedule_deletion'), [
        'confirm' => 1,
    ]);

    $response->assertRedirect(route('profile'));
    $response->assertSessionHasErrors('handovers');
    $owner->refresh();
    expect($owner->deletion_scheduled_at)->toBeNull();
});

it('Schedule: Owner-Loeschung mit Uebergabe transferiert Projekte und meldet Loeschung an', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $successor */
    $successor = User::factory()->create();
    $successor->assignRole(RoleName::READER->value);
    /** @var Project $project */
    $project = Project::factory()->create(['user_id' => $owner->id]);
    $this->actingAs($owner);

    $response = $this->post(route('profile.schedule_deletion'), [
        'confirm' => 1,
        'handovers' => [$project->id => $successor->id],
    ]);

    $response->assertRedirect(route('profile'));
    $response->assertSessionHasNoErrors();
    $owner->refresh();
    $project->refresh();
    expect($owner->deletion_scheduled_at)->not->toBeNull();
    expect((int) $project->user_id)->toBe($successor->id);
});

it('Cancel: User nimmt geplante Loeschung wieder zurueck', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole(RoleName::READER->value);
    $user->deletion_scheduled_at = now();
    $user->deletion_reason = 'Ausprobieren';
    $user->save();
    $this->actingAs($user);

    $response = $this->post(route('profile.cancel_deletion'));

    $response->assertRedirect(route('profile'));
    $user->refresh();
    expect($user->deletion_scheduled_at)->toBeNull();
    expect($user->deletion_reason)->toBeNull();
});

it('Purge: Command soft-loescht Konten mit abgelaufener Grace-Period', function () {
    /** @var TestCase $this */
    /** @var User $due */
    $due = User::factory()->create();
    $due->assignRole(RoleName::READER->value);
    $due->deletion_scheduled_at = now()->subDays(User::DELETION_GRACE_DAYS + 1);
    $due->save();

    /** @var User $fresh */
    $fresh = User::factory()->create();
    $fresh->assignRole(RoleName::READER->value);
    $fresh->deletion_scheduled_at = now();
    $fresh->save();

    Artisan::call('users:purge-scheduled');

    // due wurde SoftDeleted; fresh bleibt.
    expect(User::withTrashed()->find($due->id)->trashed())->toBeTrue();
    expect(User::withTrashed()->find($fresh->id)->trashed())->toBeFalse();
});

it('Purge: dry-run ändert keinen Zustand, meldet aber Zahl', function () {
    /** @var TestCase $this */
    /** @var User $due */
    $due = User::factory()->create();
    $due->assignRole(RoleName::READER->value);
    $due->deletion_scheduled_at = now()->subDays(User::DELETION_GRACE_DAYS + 1);
    $due->save();

    Artisan::call('users:purge-scheduled', ['--dry-run' => true]);

    expect(User::withTrashed()->find($due->id)->trashed())->toBeFalse();
});
