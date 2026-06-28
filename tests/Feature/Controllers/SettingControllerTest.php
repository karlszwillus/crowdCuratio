<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

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

use App\Models\Imprint;
use App\Models\MailSetting;
use App\Models\PrivacyPolicy;
use App\Models\TermsConditions;
use App\Models\User;
use App\Support\RoleName;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| SettingController — Admin-Settings (Terms/Privacy/Imprint/Invitation-Mail)
|--------------------------------------------------------------------------
|
| Routes hinter auth+role:Admin. index() rendert die Settings-View mit
| den jeweils ersten Datensätzen (oder null). store() ist ein Polymorph
| über vier Request-Felder: termsConditions, privacyPolicy, firstname,
| invitation — je nach gesetztem Feld wird in eines der vier Modelle
| geschrieben.
*/

beforeEach(function () {
    Role::firstOrCreate(['name' => RoleName::ADMIN->value, 'guard_name' => 'web']);
    app()->setLocale('de');
});

/**
 * Helper für die Admin-User-Anlage. Inline-Funktion statt Property auf
 * $this, weil Pest-Test-Properties Larastan-Annotationen brauchen, die
 * pro Case sowieso geschrieben werden müssen — also direkt lokal.
 */
function makeAdmin(): User
{
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);

    return $admin;
}

it('rendert die Settings-View leer, wenn noch keine Datensätze existieren', function () {
    /** @var TestCase $this */
    $admin = makeAdmin();
    $response = $this->actingAs($admin)
        ->get(route('settings.index'));

    $response->assertOk();
    $response->assertViewIs('settings.index');
    $response->assertViewHas('terms', null);
    $response->assertViewHas('privacy', null);
    $response->assertViewHas('mail', null);
    $response->assertViewHas('imprint', null);
});

it('rendert die Settings-View mit den ersten Datensätzen', function () {
    TermsConditions::create([
        'terms_conditions' => ['de' => 'AGB-Text'],
        'active' => 1,
    ]);
    PrivacyPolicy::create([
        'privacy_policy' => ['de' => 'Datenschutz-Text'],
        'active' => 1,
    ]);

    /** @var TestCase $this */
    $admin = makeAdmin();
    $response = $this->actingAs($admin)
        ->get(route('settings.index'));

    $response->assertOk();
    $response->assertViewHas('terms');
    $response->assertViewHas('privacy');
});

it('legt neue Terms-Conditions an', function () {
    /** @var TestCase $this */
    $admin = makeAdmin();
    $response = $this->actingAs($admin)
        ->from(route('settings.index'))
        ->post(route('settings.store'), [
            'termsConditions' => 'Neuer AGB-Entwurf',
        ]);

    $response->assertRedirect(route('settings.index'));
    $response->assertSessionHas('success');
    expect(TermsConditions::count())->toBe(1);
    expect(TermsConditions::first()->getTranslation('terms_conditions', 'de'))
        ->toBe('Neuer AGB-Entwurf');
});

it('aktualisiert existierende Terms-Conditions via idTerms', function () {
    $terms = TermsConditions::create([
        'terms_conditions' => ['de' => 'Alter Stand'],
        'active' => 1,
    ]);

    /** @var TestCase $this */
    $admin = makeAdmin();
    $this->actingAs($admin)
        ->from(route('settings.index'))
        ->post(route('settings.store'), [
            'idTerms' => $terms->id,
            'termsConditions' => 'Aktualisierter Stand',
        ]);

    expect(TermsConditions::count())->toBe(1);
    expect($terms->fresh()->getTranslation('terms_conditions', 'de'))
        ->toBe('Aktualisierter Stand');
});

it('legt eine neue Privacy-Policy an', function () {
    /** @var TestCase $this */
    $admin = makeAdmin();
    $this->actingAs($admin)
        ->from(route('settings.index'))
        ->post(route('settings.store'), [
            'privacyPolicy' => 'Neuer Datenschutz-Text',
        ]);

    expect(PrivacyPolicy::count())->toBe(1);
    expect(PrivacyPolicy::first()->getTranslation('privacy_policy', 'de'))
        ->toBe('Neuer Datenschutz-Text');
});

it('aktualisiert ein existierendes Imprint via firstname-Feld', function () {
    // Update-Pfad: das Settings-UI bietet nur "Imprint editieren", nicht
    // "Imprint neu anlegen" — der erste Imprint kommt aus dem Seeder.
    // Der Create-Pfad im Controller (`updateOrCreate(['id' => null], …)`)
    // ist eine schmierige Konstruktion und wird hier nicht getestet;
    // siehe Hygiene-Note in werkbank/TODO.md.
    $imprint = Imprint::create([
        'name' => ['firstname' => 'Alt', 'lastname' => 'Vorname'],
        'address' => ['address' => 'Alt-Str. 0', 'postcode' => '00000'],
        'contact' => ['phone' => '', 'fax' => '', 'email' => 'alt@example.org'],
    ]);

    /** @var TestCase $this */
    $admin = makeAdmin();
    $this->actingAs($admin)
        ->from(route('settings.index'))
        ->post(route('settings.store'), [
            'IdImprint' => $imprint->id,
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'address' => 'Beispielstr. 1',
            'postcode' => '10115',
            'phone' => '+49 30 12345',
            'fax' => '',
            'email' => 'kontakt@example.org',
        ]);

    expect(Imprint::count())->toBe(1);
    $updated = $imprint->fresh();
    expect($updated->name)->toMatchArray(['firstname' => 'Max', 'lastname' => 'Mustermann']);
    expect($updated->address)->toMatchArray(['address' => 'Beispielstr. 1', 'postcode' => '10115']);
    expect($updated->contact)->toMatchArray(['phone' => '+49 30 12345', 'email' => 'kontakt@example.org']);
});

it('legt eine Invitation-Mail an', function () {
    /** @var TestCase $this */
    $admin = makeAdmin();
    $this->actingAs($admin)
        ->from(route('settings.index'))
        ->post(route('settings.store'), [
            'invitation' => 'Willkommen bei crowdCuratio',
        ]);

    expect(MailSetting::count())->toBe(1);
    expect(MailSetting::first()->getTranslation('invitation', 'de'))
        ->toBe('Willkommen bei crowdCuratio');
});

it('macht einen leeren store-Aufruf zu einem stillen Redirect', function () {
    /** @var TestCase $this */
    $admin = makeAdmin();
    $response = $this->actingAs($admin)
        ->from(route('settings.index'))
        ->post(route('settings.store'), []);

    $response->assertRedirect(route('settings.index'));
    expect(TermsConditions::count())->toBe(0);
    expect(PrivacyPolicy::count())->toBe(0);
    expect(Imprint::count())->toBe(0);
    expect(MailSetting::count())->toBe(0);
});

it('verbietet Nicht-Admins den Zugriff auf settings.index', function () {
    /** @var TestCase $this */
    /** @var User $regular */
    $regular = User::factory()->create();

    $response = $this->actingAs($regular)
        ->get(route('settings.index'));

    $response->assertForbidden();
});
