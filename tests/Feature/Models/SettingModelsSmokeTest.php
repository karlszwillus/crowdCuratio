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

/*
|--------------------------------------------------------------------------
| Setting-Modelle — Smoke
|--------------------------------------------------------------------------
|
| Imprint / MailSetting / PrivacyPolicy / TermsConditions: vier
| Settings-Datensätze für den Admin-Bereich. Smoke pro Modell:
| Create + Read-back, Translatable-/Array-Cast-Verhalten.
*/

beforeEach(function () {
    app()->setLocale('de');
});

// ---------- Imprint ----------

it('Imprint speichert name/address/contact als Arrays und liest sie casted zurück', function () {
    $imprint = Imprint::create([
        'name' => ['firstname' => 'Max', 'lastname' => 'Mustermann'],
        'address' => ['address' => 'Beispielstr. 1', 'postcode' => '10115'],
        'contact' => ['phone' => '+49 30 12345', 'fax' => '', 'email' => 'kontakt@example.org'],
    ]);

    $fresh = $imprint->fresh();

    expect($fresh->name)->toBeArray();
    expect($fresh->name['firstname'])->toBe('Max');
    expect($fresh->name['lastname'])->toBe('Mustermann');
    expect($fresh->address['address'])->toBe('Beispielstr. 1');
    expect($fresh->contact['email'])->toBe('kontakt@example.org');
});

it('Imprint kann partielle Adress-/Kontakt-Daten speichern', function () {
    $imprint = Imprint::create([
        'name' => ['firstname' => 'Sole', 'lastname' => 'Vorname'],
        'address' => ['address' => 'Nur Straße'],
        'contact' => ['email' => 'nur@example.org'],
    ]);

    expect($imprint->fresh()->address['address'])->toBe('Nur Straße');
    expect($imprint->fresh()->contact['email'])->toBe('nur@example.org');
});

// ---------- MailSetting ----------

it('MailSetting speichert invitation als translatable Text', function () {
    $mail = new MailSetting;
    $mail->setTranslation('invitation', 'de', 'Willkommen bei crowdCuratio');
    $mail->setTranslation('invitation', 'en', 'Welcome to crowdCuratio');
    $mail->save();

    $fresh = $mail->fresh();

    expect($fresh->getTranslation('invitation', 'de'))->toBe('Willkommen bei crowdCuratio');
    expect($fresh->getTranslation('invitation', 'en'))->toBe('Welcome to crowdCuratio');
});

it('MailSetting liefert die aktive Locale-Übersetzung als Default-Read', function () {
    $mail = new MailSetting;
    $mail->setTranslation('invitation', 'de', 'Hallo');
    $mail->setTranslation('invitation', 'en', 'Hello');
    $mail->save();

    app()->setLocale('en');
    expect($mail->fresh()->invitation)->toBe('Hello');

    app()->setLocale('de');
    expect($mail->fresh()->invitation)->toBe('Hallo');
});

// ---------- PrivacyPolicy ----------

it('PrivacyPolicy speichert privacy_policy translatable und active als boolean', function () {
    $privacy = PrivacyPolicy::create([
        'privacy_policy' => ['de' => 'Datenschutz-Text'],
        'active' => 1,
    ]);

    $fresh = $privacy->fresh();

    expect($fresh->getTranslation('privacy_policy', 'de'))->toBe('Datenschutz-Text');
    expect($fresh->active)->toBeTrue();
});

it('PrivacyPolicy unterscheidet aktive von inaktiven Einträgen', function () {
    PrivacyPolicy::create([
        'privacy_policy' => ['de' => 'Alt'],
        'active' => 0,
    ]);
    PrivacyPolicy::create([
        'privacy_policy' => ['de' => 'Aktuell'],
        'active' => 1,
    ]);

    $aktive = PrivacyPolicy::where('active', 1)->get();
    $inaktive = PrivacyPolicy::where('active', 0)->get();

    expect($aktive)->toHaveCount(1);
    expect($inaktive)->toHaveCount(1);
    expect($aktive->first()->getTranslation('privacy_policy', 'de'))->toBe('Aktuell');
});

// ---------- TermsConditions ----------

it('TermsConditions speichert terms_conditions translatable und active als boolean', function () {
    $terms = TermsConditions::create([
        'terms_conditions' => ['de' => 'AGB-Text', 'en' => 'Terms text'],
        'active' => 1,
    ]);

    $fresh = $terms->fresh();

    expect($fresh->getTranslation('terms_conditions', 'de'))->toBe('AGB-Text');
    expect($fresh->getTranslation('terms_conditions', 'en'))->toBe('Terms text');
    expect($fresh->active)->toBeTrue();
});

it('TermsConditions::active=false wird korrekt gecasted zurückgelesen', function () {
    $terms = TermsConditions::create([
        'terms_conditions' => ['de' => 'Veraltete AGB'],
        'active' => 0,
    ]);

    expect($terms->fresh()->active)->toBeFalse();
});

it('Settings-Modelle sind voneinander unabhängig', function () {
    Imprint::create([
        'name' => ['firstname' => 'A', 'lastname' => 'B'],
        'address' => [],
        'contact' => [],
    ]);
    PrivacyPolicy::create(['privacy_policy' => ['de' => 'P'], 'active' => 1]);
    TermsConditions::create(['terms_conditions' => ['de' => 'T'], 'active' => 1]);

    expect(Imprint::count())->toBe(1);
    expect(PrivacyPolicy::count())->toBe(1);
    expect(TermsConditions::count())->toBe(1);
    expect(MailSetting::count())->toBe(0); // bewusst nicht angelegt
});
