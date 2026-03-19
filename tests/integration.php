<?php

/**
 * Integration tests — requires network access to Google Play Store.
 *
 * Run manually:
 *   php tests/integration.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use GooglePlayScraper\GooglePlayScraper;
use GooglePlayScraper\Enum\Collection;
use GooglePlayScraper\Enum\Category;
use GooglePlayScraper\Enum\Sort;
use GooglePlayScraper\Enum\Price;

$scraper = new GooglePlayScraper();
$passed = 0;
$failed = 0;
$total = 0;

function test(string $name, callable $fn): void
{
    global $passed, $failed, $total;
    $total++;
    try {
        $fn();
        echo "  ✓ {$name}\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "  ✗ {$name}\n    ↳ {$e->getMessage()}\n";
        $failed++;
    }
}

function assert_true(bool $condition, string $msg = ''): void
{
    if (!$condition) {
        throw new \RuntimeException($msg ?: 'Assertion failed');
    }
}

function assert_not_empty(mixed $value, string $field): void
{
    if (empty($value)) {
        throw new \RuntimeException("{$field} is empty");
    }
}

function assert_array_key(array $arr, string $key): void
{
    if (!array_key_exists($key, $arr)) {
        throw new \RuntimeException("Missing key: {$key}");
    }
}

echo "\n🔍 Google Play Scraper - Integration Tests\n";
echo str_repeat('─', 50) . "\n\n";

// ─── app() ───────────────────────────────────────
echo "📦 app()\n";

test('returns app details for Google Translate', function () use ($scraper) {
    $app = $scraper->app('com.google.android.apps.translate');
    assert_array_key($app, 'title');
    assert_array_key($app, 'appId');
    assert_array_key($app, 'developer');
    assert_array_key($app, 'score');
    assert_array_key($app, 'installs');
    assert_array_key($app, 'url');
    assert_not_empty($app['title'], 'title');
    assert_true($app['appId'] === 'com.google.android.apps.translate', 'appId mismatch');
    assert_true(str_contains($app['developer'], 'Google'), 'developer should be Google');
    assert_true($app['free'] === true, 'should be free');
});

test('returns app details with Turkish locale', function () use ($scraper) {
    $app = $scraper->app('com.google.android.apps.translate', lang: 'tr', country: 'tr');
    assert_not_empty($app['title'], 'title');
    assert_array_key($app, 'description');
    assert_array_key($app, 'installs');
});

test('returns paid app info', function () use ($scraper) {
    $app = $scraper->app('com.mojang.minecraftpe');
    assert_array_key($app, 'price');
    assert_array_key($app, 'free');
});

// ─── search() ────────────────────────────────────
echo "\n🔎 search()\n";

test('returns search results', function () use ($scraper) {
    $results = $scraper->search('whatsapp', num: 5);
    assert_true(count($results) > 0, 'no results');
    assert_true(count($results) <= 5, 'too many results');
    assert_array_key($results[0], 'appId');
    assert_array_key($results[0], 'title');
});

test('search with price filter', function () use ($scraper) {
    $results = $scraper->search('weather', num: 5, price: Price::FREE);
    assert_true(count($results) > 0, 'no free results');
});

test('search returns empty for nonsense query', function () use ($scraper) {
    $results = $scraper->search('xyznonexistentapp999888777', num: 5);
    assert_true(is_array($results), 'should be array');
});

// ─── suggest() ───────────────────────────────────
echo "\n💡 suggest()\n";

test('returns suggestions', function () use ($scraper) {
    $suggestions = $scraper->suggest('inst');
    assert_true(count($suggestions) > 0, 'no suggestions');
    assert_true(is_string($suggestions[0]), 'suggestion should be string');
});

// ─── list() ──────────────────────────────────────
echo "\n📋 list()\n";

test('returns top free apps', function () use ($scraper) {
    $apps = $scraper->list(Collection::TOP_FREE, Category::APPLICATION, num: 5);
    assert_true(count($apps) > 0, 'no apps');
    assert_array_key($apps[0], 'appId');
    assert_array_key($apps[0], 'title');
});

test('returns top paid games', function () use ($scraper) {
    $apps = $scraper->list(Collection::TOP_PAID, Category::GAME, num: 5);
    assert_true(is_array($apps), 'should be array');
});

// ─── developer() ─────────────────────────────────
echo "\n👨‍💻 developer()\n";

test('returns developer apps by name', function () use ($scraper) {
    $apps = $scraper->developer(devId: 'Google LLC', num: 5);
    assert_true(count($apps) > 0, 'no apps');
    assert_array_key($apps[0], 'appId');
    assert_array_key($apps[0], 'title');
});

test('returns developer apps by numeric ID', function () use ($scraper) {
    $apps = $scraper->developer(devId: '5700313618786177705', num: 5);
    assert_true(count($apps) > 0, 'no apps');
});

// ─── reviews() ───────────────────────────────────
echo "\n⭐ reviews()\n";

test('returns reviews with data and token', function () use ($scraper) {
    $result = $scraper->reviews('com.whatsapp', num: 5, paginate: true);
    assert_array_key($result, 'data');
    assert_array_key($result, 'nextPaginationToken');
    assert_true(count($result['data']) > 0, 'no reviews');

    $review = $result['data'][0];
    assert_array_key($review, 'id');
    assert_array_key($review, 'userName');
    assert_array_key($review, 'score');
    assert_array_key($review, 'text');
});

test('reviews pagination works', function () use ($scraper) {
    $page1 = $scraper->reviews('com.whatsapp', num: 3, paginate: true);
    assert_not_empty($page1['nextPaginationToken'], 'nextPaginationToken');

    $page2 = $scraper->reviews(
        'com.whatsapp',
        num: 3,
        paginate: true,
        nextPaginationToken: $page1['nextPaginationToken'],
    );
    assert_true(count($page2['data']) > 0, 'page 2 empty');
    assert_true($page1['data'][0]['id'] !== $page2['data'][0]['id'], 'pages should differ');
});

test('reviews sorted', function () use ($scraper) {
    $result = $scraper->reviews('com.whatsapp', sort: Sort::RATING, num: 5, paginate: true);
    assert_true(count($result['data']) > 0, 'no sorted reviews');
});

// ─── similar() ───────────────────────────────────
echo "\n🔗 similar()\n";

test('returns similar apps', function () use ($scraper) {
    $apps = $scraper->similar('com.whatsapp');
    assert_true(count($apps) > 0, 'no similar apps');
    assert_array_key($apps[0], 'appId');
    assert_array_key($apps[0], 'title');
});

// ─── permissions() ───────────────────────────────
echo "\n🔐 permissions()\n";

test('returns full permissions', function () use ($scraper) {
    $perms = $scraper->permissions('com.google.android.apps.translate');
    assert_true(count($perms) > 0, 'no permissions');
    assert_array_key($perms[0], 'permission');
    assert_array_key($perms[0], 'type');
});

test('returns short permissions', function () use ($scraper) {
    $perms = $scraper->permissions('com.google.android.apps.translate', short: true);
    assert_true(count($perms) > 0, 'no short permissions');
    assert_true(is_string($perms[0]), 'should be string');
});

// ─── dataSafety() ────────────────────────────────
echo "\n🛡️  dataSafety()\n";

test('returns data safety info', function () use ($scraper) {
    $safety = $scraper->dataSafety('com.google.android.apps.translate');
    assert_array_key($safety, 'sharedData');
    assert_array_key($safety, 'collectedData');
    assert_array_key($safety, 'securityPractices');
});

// ─── categories() ────────────────────────────────
echo "\n📁 categories()\n";

test('returns categories', function () use ($scraper) {
    $cats = $scraper->categories();
    assert_true(count($cats) > 0, 'no categories');
    assert_true(is_string($cats[0]), 'should be string');
});

// ─── Summary ─────────────────────────────────────
echo "\n" . str_repeat('─', 50) . "\n";
echo "Sonuç: {$passed}/{$total} başarılı";
if ($failed > 0) {
    echo ", {$failed} başarısız";
}
echo "\n\n";

exit($failed > 0 ? 1 : 0);
