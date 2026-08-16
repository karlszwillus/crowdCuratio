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

declare(strict_types=1);

use App\Support\CommentStatus;

/*
|--------------------------------------------------------------------------
| CommentStatus (Phase 5x.4)
|--------------------------------------------------------------------------
|
| Pinnt das Enum-Vertrag + das Legacy-Int-Mapping aus der Migration.
| Wenn wir den `veraltet`-Zweig oder eine andere int-Belegung
| verschieben, faellt der Test.
*/

it('mappt Legacy-Integer korrekt auf den neuen Enum', function () {
    expect(CommentStatus::fromLegacyInt(1))->toBe(CommentStatus::OPEN);
    // 2 (accepted) → in_progress: „jemand hat angenommen und arbeitet dran"
    expect(CommentStatus::fromLegacyInt(2))->toBe(CommentStatus::IN_PROGRESS);
    expect(CommentStatus::fromLegacyInt(3))->toBe(CommentStatus::REJECTED);
    // 4 (cancel) und 3 (decline) landen beide auf rejected — der
    // cancel-Nuancenverlust ist bewusst.
    expect(CommentStatus::fromLegacyInt(4))->toBe(CommentStatus::REJECTED);
    expect(CommentStatus::fromLegacyInt(5))->toBe(CommentStatus::RESOLVED);
});

it('faellt bei null oder unbekanntem Integer auf OPEN', function () {
    expect(CommentStatus::fromLegacyInt(null))->toBe(CommentStatus::OPEN);
    expect(CommentStatus::fromLegacyInt(0))->toBe(CommentStatus::OPEN);
    expect(CommentStatus::fromLegacyInt(999))->toBe(CommentStatus::OPEN);
});

it('liefert die vier semantischen Token-Varianten', function () {
    expect(CommentStatus::OPEN->tokenVariant())->toBe('info');
    expect(CommentStatus::IN_PROGRESS->tokenVariant())->toBe('warning');
    expect(CommentStatus::RESOLVED->tokenVariant())->toBe('success');
    // rejected ist NICHT danger — danger bleibt fuer Loeschen.
    expect(CommentStatus::REJECTED->tokenVariant())->toBe('neutral');
});

it('markiert erledigt und abgelehnt als per Default ausgeblendet', function () {
    expect(CommentStatus::OPEN->isHiddenByDefault())->toBeFalse();
    expect(CommentStatus::IN_PROGRESS->isHiddenByDefault())->toBeFalse();
    expect(CommentStatus::RESOLVED->isHiddenByDefault())->toBeTrue();
    expect(CommentStatus::REJECTED->isHiddenByDefault())->toBeTrue();
});

it('gibt alle vier String-Werte in ::all() zurueck', function () {
    expect(CommentStatus::all())
        ->toBe(['open', 'in_progress', 'resolved', 'rejected']);
});
