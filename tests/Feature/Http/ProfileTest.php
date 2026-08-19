<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 *
 * Pest-Tests fuer Phase 5ac (Profil-Redesign Screen 17A).
 *
 * Deckt ab:
 *  - PATCH /profile schreibt Name, Kuerzel, Farbe und Benachrichtigungen
 *  - Kuerzel-Sperrliste liefert 302 zurueck mit Fehler
 *  - Avatar-Upload speichert File und avatar_path, remove_avatar
 *    leert das Feld und entfernt die Datei
 *  - PATCH /profile/password wechselt bei korrektem old_password
 *  - PATCH /profile/password wird bei falschem old_password abgewiesen
 *  - POST /profile/locale und /profile/theme akzeptieren nur Whitelist
 */

use App\Models\NotificationPreference;
use App\Models\User;
use App\Support\PermissionName;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());

    Storage::fake('public');
});

it('updateProfile — schreibt Name, Kuerzel, Farbe und Notification-Preferences', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create(['name' => 'Alt', 'last_name' => 'Nachname']);
    $user->assignRole('Admin');
    $this->actingAs($user);

    $response = $this->patch(route('profile.update'), [
        'firstName' => 'Neu',
        'lastName' => 'Person',
        'initials' => 'nP',
        'initials_color' => 'success',
        'notify_comments' => '1',
        'notify_publish' => '1',
    ]);

    $response->assertStatus(302);
    $fresh = $user->fresh();
    expect($fresh->name)->toBe('Neu')
        ->and($fresh->last_name)->toBe('Person')
        ->and($fresh->initials)->toBe('NP')
        ->and($fresh->initials_color)->toBe('success');

    $prefs = NotificationPreference::query()->where('user_id', $user->id)->first();
    expect($prefs->notify_comments)->toBeTrue()
        ->and($prefs->notify_publish)->toBeTrue()
        ->and($prefs->notify_weekly_digest)->toBeFalse();
});

it('updateProfile — verweigert Kuerzel aus der Sperrliste', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('Admin');
    $this->actingAs($user);

    $response = $this->patch(route('profile.update'), [
        'firstName' => 'Test',
        'lastName' => 'User',
        'initials' => 'SS', // aus config/kuerzel_blocklist.php
    ]);

    $response->assertSessionHasErrors('initials');
    expect($user->fresh()->initials)->toBeNull();
});

it('updateProfile — Avatar-Upload legt Datei ab und schreibt avatar_path', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('Admin');
    $this->actingAs($user);

    $file = UploadedFile::fake()->image('me.png', 200, 200);

    // Laravel PATCH-Tests transportieren multipart-Files nicht sauber —
    // Method-Spoofing via POST + _method=PATCH ist der bewaehrte Weg.
    $this->post(route('profile.update'), [
        '_method' => 'PATCH',
        'firstName' => $user->name,
        'lastName' => $user->last_name,
        'avatar' => $file,
    ]);

    $stored = $user->fresh()->avatar_path;
    expect($stored)->not->toBeNull();
    Storage::disk('public')->assertExists('uploads/avatars/'.$stored);
});

it('updateProfile — remove_avatar loescht Datei und leert avatar_path', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create(['avatar_path' => 'old.png']);
    $user->assignRole('Admin');
    $this->actingAs($user);
    Storage::disk('public')->put('uploads/avatars/old.png', 'x');

    $this->patch(route('profile.update'), [
        'firstName' => $user->name,
        'lastName' => $user->last_name,
        'remove_avatar' => '1',
    ]);

    expect($user->fresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing('uploads/avatars/old.png');
});

it('updatePassword — schreibt neues Passwort bei richtigem old_password', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create(['password' => Hash::make('geheim-alt-1')]);
    $user->assignRole('Admin');
    $this->actingAs($user);

    $response = $this->patch(route('profile.password'), [
        'old_password' => 'geheim-alt-1',
        'new_password' => 'ganz-neues-passwort',
        'confirm_password' => 'ganz-neues-passwort',
    ]);

    $response->assertStatus(302);
    expect(Hash::check('ganz-neues-passwort', $user->fresh()->password))->toBeTrue();
});

it('updatePassword — verweigert bei falschem old_password', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create(['password' => Hash::make('geheim')]);
    $user->assignRole('Admin');
    $this->actingAs($user);

    $response = $this->patch(route('profile.password'), [
        'old_password' => 'FALSCH',
        'new_password' => 'ganz-neues-passwort',
        'confirm_password' => 'ganz-neues-passwort',
    ]);

    $response->assertSessionHasErrors('old_password');
    expect(Hash::check('geheim', $user->fresh()->password))->toBeTrue();
});

it('updateLocale — nur Whitelisten Codes werden akzeptiert', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('Admin');
    $this->actingAs($user);

    $this->postJson(route('profile.locale'), ['locale' => 'de'])->assertStatus(200);
    expect($user->fresh()->locale)->toBe('de');

    $this->postJson(route('profile.locale'), ['locale' => 'kl-ingon'])->assertStatus(422);
});

it('updateTheme — nur crowdCuratio und aktivesMuseum werden akzeptiert', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('Admin');
    $this->actingAs($user);

    $this->postJson(route('profile.theme'), ['theme' => 'aktivesMuseum'])->assertStatus(200);
    expect($user->fresh()->theme)->toBe('aktivesMuseum');

    $this->postJson(route('profile.theme'), ['theme' => 'neondisco'])->assertStatus(422);
});
