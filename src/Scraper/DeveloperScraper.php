<?php

declare(strict_types=1);

namespace GooglePlayScraper\Scraper;

use GooglePlayScraper\Utils\HttpClient;
use GooglePlayScraper\Utils\ScriptData;

class DeveloperScraper
{
    private const BASE_URL = 'https://play.google.com';

    public function __construct(
        private readonly HttpClient $client,
        private readonly AppScraper $appScraper,
    ) {}

    public function getApps(
        string $devId,
        string $lang = 'en',
        string $country = 'us',
        int $num = 60,
        bool $fullDetail = false,
    ): array {
        $isNumeric = is_numeric($devId);
        $path = $isNumeric ? '/dev' : '/developer';

        $params = http_build_query([
            'id' => $devId,
            'hl' => $lang,
            'gl' => $country,
        ]);

        $url = self::BASE_URL . "/store/apps{$path}?{$params}";
        $html = $this->client->get($url);
        $parsed = ScriptData::parse($html);

        $mappings = $isNumeric
            ? ['apps' => ['ds:3', 0, 1, 0, 21, 0], 'token' => ['ds:3', 0, 1, 0, 21, 1, 3, 1]]
            : ['apps' => ['ds:3', 0, 1, 0, 22, 0], 'token' => ['ds:3', 0, 1, 0, 22, 1, 3, 1]];

        $appsMappings = $this->getAppMappings($isNumeric);
        $appsList = ScriptData::getPath($parsed, $mappings['apps']) ?? [];
        $apps = array_map(fn($item) => ScriptData::extractFields($appsMappings, $item), $appsList);

        if ($fullDetail) {
            $apps = array_map(
                fn($app) => $this->appScraper->getApp($app['appId'], $lang, $country),
                $apps
            );
        }

        return array_slice($apps, 0, $num);
    }

    private function getAppMappings(bool $isNumeric): array
    {
        if ($isNumeric) {
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

        return [
            'title' => [0, 3],
            'appId' => [0, 0, 0],
            'url' => [
                'path' => [0, 10, 4, 2],
                'fun' => fn($path) => $path ? self::BASE_URL . $path : null,
            ],
            'icon' => [0, 1, 3, 2],
            'developer' => [0, 14],
            'currency' => [0, 8, 1, 0, 1],
            'price' => [
                'path' => [0, 8, 1, 0, 0],
                'fun' => fn($price) => $price !== null ? $price / 1000000 : 0,
            ],
            'free' => [
                'path' => [0, 8, 1, 0, 0],
                'fun' => fn($price) => $price === 0,
            ],
            'summary' => [0, 13, 1],
            'scoreText' => [0, 4, 0],
            'score' => [0, 4, 1],
        ];
    }
}
