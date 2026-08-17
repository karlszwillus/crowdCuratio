<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

use App\Support\VideoLink;

it('extracts YouTube IDs from watch URLs', function () {
    expect(VideoLink::extractYouTubeId('https://www.youtube.com/watch?v=sWXiWYaUrfs'))
        ->toBe('sWXiWYaUrfs');
});

it('extracts YouTube IDs from youtu.be URLs', function () {
    expect(VideoLink::extractYouTubeId('https://youtu.be/sWXiWYaUrfs'))
        ->toBe('sWXiWYaUrfs');
});

it('extracts YouTube IDs from embed URLs', function () {
    expect(VideoLink::extractYouTubeId('https://www.youtube.com/embed/sWXiWYaUrfs?si=abc'))
        ->toBe('sWXiWYaUrfs');
});

it('extracts YouTube IDs from shorts URLs', function () {
    expect(VideoLink::extractYouTubeId('https://www.youtube.com/shorts/sWXiWYaUrfs'))
        ->toBe('sWXiWYaUrfs');
});

it('returns null for non-YouTube URLs', function () {
    expect(VideoLink::extractYouTubeId('https://vimeo.com/123456789'))->toBeNull();
    expect(VideoLink::extractYouTubeId(''))->toBeNull();
    expect(VideoLink::extractYouTubeId('not-a-url'))->toBeNull();
});

it('normalizes watch URLs to embed URLs', function () {
    expect(VideoLink::toEmbedUrl('https://www.youtube.com/watch?v=sWXiWYaUrfs'))
        ->toBe('https://www.youtube.com/embed/sWXiWYaUrfs');
});

it('normalizes youtu.be URLs to embed URLs', function () {
    expect(VideoLink::toEmbedUrl('https://youtu.be/sWXiWYaUrfs?t=42'))
        ->toBe('https://www.youtube.com/embed/sWXiWYaUrfs');
});

it('is idempotent on already-embed URLs', function () {
    expect(VideoLink::toEmbedUrl('https://www.youtube.com/embed/sWXiWYaUrfs'))
        ->toBe('https://www.youtube.com/embed/sWXiWYaUrfs');
});

it('returns null for unsupported providers', function () {
    expect(VideoLink::toEmbedUrl('https://vimeo.com/123456789'))->toBeNull();
});

it('builds YouTube poster URLs', function () {
    expect(VideoLink::youTubePoster('sWXiWYaUrfs'))
        ->toBe('https://i.ytimg.com/vi/sWXiWYaUrfs/hqdefault.jpg');
});
