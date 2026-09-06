<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

use App\Http\Middleware\LogContext;
use App\Models\User;
use App\Support\RoleName;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Q4-Etappe 1 / I10 (2026-08-27): LogContext-Middleware
 * reichert jeden Log-Eintrag mit `request_id` + `user_id` an.
 *
 * Der Test benutzt eine schmale Testroute, die selbst `Log::info()`
 * ruft — der letzte Log-Eintrag muss die Kontext-Keys enthalten.
 */
beforeEach(function () {
    Role::firstOrCreate(['name' => RoleName::READER->value, 'guard_name' => 'web']);

    // Testroute: gibt die aktuellen Log-Context-Keys als JSON zurueck.
    Route::middleware(['web', LogContext::class])
        ->get('/__test/log-context', function () {
            $context = [];
            Log::withContext([]); // no-op, forces channel init
            Log::listen(function ($msg) use (&$context) {
                $context = $msg->context;
            });
            Log::info('test.log_context.smoke');

            return response()->json([
                'has_request_id' => isset($context['request_id']),
                'has_user_id' => isset($context['user_id']),
                'request_id' => $context['request_id'] ?? null,
                'user_id' => $context['user_id'] ?? null,
            ]);
        });
});

it('LogContext: setzt request_id im Log-Context ohne Auth', function () {
    /** @var TestCase $this */
    $response = $this->get('/__test/log-context');

    $response->assertOk();
    $payload = $response->json();
    expect($payload['has_request_id'])->toBeTrue();
    expect($payload['request_id'])->toBeString();
    expect($payload['has_user_id'])->toBeFalse();
});

it('LogContext: ergaenzt user_id, wenn eingeloggt', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole(RoleName::READER->value);
    $this->actingAs($user);

    $response = $this->get('/__test/log-context');

    $response->assertOk();
    $payload = $response->json();
    expect($payload['has_user_id'])->toBeTrue();
    expect((int) $payload['user_id'])->toBe($user->id);
});

it('LogContext: uebernimmt Client-Header X-Request-Id, wenn vorhanden', function () {
    /** @var TestCase $this */
    $incoming = 'test-request-id-abc-123';

    $response = $this->withHeaders(['X-Request-Id' => $incoming])
        ->get('/__test/log-context');

    $response->assertOk();
    expect($response->headers->get('X-Request-Id'))->toBe($incoming);
    expect($response->json('request_id'))->toBe($incoming);
});
