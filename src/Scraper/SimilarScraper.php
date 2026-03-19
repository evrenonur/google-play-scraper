<?php

declare(strict_types=1);

namespace GooglePlayScraper\Scraper;

use GooglePlayScraper\Utils\HttpClient;
use GooglePlayScraper\Utils\ScriptData;

class SimilarScraper
{
    private const BASE_URL = 'https://play.google.com';

    private const CLUSTER_MAPPING = [
        'title' => [21, 1, 0],
        'url' => [21, 1, 2, 4, 2],
    ];

    public function __construct(
        private readonly HttpClient $client,
        private readonly AppScraper $appScraper,
    ) {}

    public function similar(
        string $appId,
        string $lang = 'en',
        string $country = 'us',
        bool $fullDetail = false,
    ): array {
        $params = http_build_query([
            'id' => $appId,
            'hl' => $lang,
            'gl' => $country,
        ]);

        $url = self::BASE_URL . "/store/apps/details?{$params}";
        $html = $this->client->get($url);
        $parsed = ScriptData::parse($html);

        // Search all ds: keys for app lists that represent similar apps
        $appsList = null;
        foreach ($parsed as $dsKey => $dsVal) {
            if (!str_starts_with($dsKey, 'ds:') || !is_array($dsVal)) {
                continue;
            }

            // Check [1][1][0][21][0] path - where similar apps are stored
            $candidate = ScriptData::getPath($dsVal, [1, 1, 0, 21, 0]);
            if (is_array($candidate) && count($candidate) > 1) {
                // Verify this is the "Similar apps" cluster by checking cluster title
                $clusterTitle = ScriptData::getPath($dsVal, [1, 1, 0, 21, 1, 0]);
                if ($clusterTitle !== null && (
                    str_contains((string) $clusterTitle, 'Similar') ||
                    str_contains((string) $clusterTitle, 'similar')
                )) {
                    $appsList = $candidate;
                    break;
                }
                // Keep as fallback if no "Similar" title found yet
                $appsList ??= $candidate;
            }
        }

        if ($appsList === null || empty($appsList)) {
            return [];
        }

        $apps = array_map(fn($item) => ScriptData::extractFields($this->getAppMappings(), $item), $appsList);
        $apps = array_filter($apps, fn($app) => !empty($app['appId']));
        $apps = array_values($apps);

        if ($fullDetail) {
            $apps = array_map(
                fn($app) => $this->appScraper->getApp($app['appId'], $lang, $country),
                $apps
            );
        }

        return $apps;
    }

    private function getAppMappings(): array
    {
        return [
            'title' => [3],
            'appId' => [0, 0],
            'url' => [
                'path' => [10, 4, 2],
                'fun' => fn($path) => $path ? self::BASE_URL . $path : null,
            ],
            'icon' => [1, 3, 2],
            'developer' => [14],
            'currency' => [8, 1, 0, 1],
            'price' => [
                'path' => [8, 1, 0, 0],
                'fun' => fn($price) => $price !== null ? $price / 1000000 : 0,
            ],
            'free' => [
                'path' => [8, 1, 0, 0],
                'fun' => fn($price) => $price === 0,
            ],
            'summary' => [13, 1],
            'scoreText' => [4, 0],
            'score' => [4, 1],
        ];
    }
}
