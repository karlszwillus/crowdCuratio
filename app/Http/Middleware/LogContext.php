<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Q4-Etappe 1 / I10 (2026-08-27): Reichert jeden Log-Eintrag mit
 * `request_id` (UUID pro Request) und — falls eingeloggt — `user_id`
 * an. Damit lassen sich die frisch strukturierten
 * `account.deletion.*`-Events (B2) und alle künftigen App-Log-Events
 * im Prod-Log gruppieren.
 *
 * Der Request-ID-Header `X-Request-Id` wird respektiert, wenn der
 * Client (z. B. Reverse-Proxy) schon einen liefert; sonst wird eine
 * UUIDv4 vergeben. Die ID wandert im Response-Header zurueck, damit
 * ein Support-Case direkt eine Korrelations-ID mitbringt.
 */
final class LogContext
{
    private const HEADER = 'X-Request-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) $request->headers->get(self::HEADER, (string) Str::uuid());

        $context = ['request_id' => $requestId];
        $user = $request->user();
        if ($user !== null) {
            $context['user_id'] = (int) $user->getAuthIdentifier();
        }

        Log::withContext($context);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set(self::HEADER, $requestId);

        return $response;
    }
}
