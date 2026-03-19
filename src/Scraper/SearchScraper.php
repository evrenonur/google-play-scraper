<?php

declare(strict_types=1);

namespace GooglePlayScraper\Scraper;

use GooglePlayScraper\Enum\Price;
use GooglePlayScraper\Utils\HttpClient;
use GooglePlayScraper\Utils\ScriptData;

class SearchScraper
{
    private const BASE_URL = 'https://play.google.com';

    public function __construct(
        private readonly HttpClient $client,
        private readonly AppScraper $appScraper,
    ) {}

    public function search(
        string $term,
        string $lang = 'en',
        string $country = 'us',
        int $num = 20,
        Price $price = Price::ALL,
        bool $fullDetail = false,
    ): array {
        if ($num > 250) {
            throw new \InvalidArgumentException("The number of results can't exceed 250");
        }

        $priceValue = match ($price) {
            Price::FREE => 1,
            Price::PAID => 2,
            Price::ALL => 0,
        };

        $encodedTerm = urlencode($term);
        $url = self::BASE_URL . "/work/search?q={$encodedTerm}&hl={$lang}&gl={$country}&price={$priceValue}";

        $html = $this->client->get($url);
        $parsed = ScriptData::parse($html);

        $appsSection = ScriptData::getPath($parsed, ['ds:1', 0, 1, 0, 0, 0]);
        $sections = ScriptData::getPath($parsed, ['ds:1', 0, 1, 0, 0]) ?? [];

        if (empty($sections)) {
            return [];
        }

        $apps = $this->processApps($appsSection);

        // Find pagination token
        $token = null;
        foreach ($sections as $section) {
            if (is_array($section) && isset($section[1]) && is_string($section[1])) {
                $token = $section[1];
                break;
            }
        }

        $apps = $this->checkFinished($apps, $num, $token, $lang, $country);

        if ($fullDetail) {
            $apps = array_map(
                fn($app) => $this->appScraper->getApp($app['appId'], $lang, $country),
                $apps
            );
        }

        return array_slice($apps, 0, $num);
    }

    private function processApps(?array $appsData): array
    {
        if (empty($appsData)) {
            return [];
        }

        return array_map(fn($item) => ScriptData::extractFields($this->getAppMappings(), $item), $appsData);
    }

    private function checkFinished(array $savedApps, int $num, ?string $token, string $lang, string $country): array
    {
        if (count($savedApps) >= $num || $token === null) {
            return array_slice($savedApps, 0, $num);
        }

        $body = $this->getPaginationBody($token);
        $url = self::BASE_URL . "/_/PlayStoreUi/data/batchexecute?rpcids=qnKhOb&f.sid=-697906427155521722&bl=boq_playuiserver_20190903.08_p0&hl={$lang}&gl={$country}&authuser&soc-app=121&soc-platform=1&soc-device=1&_reqid=1065213";

        $html = $this->client->post($url, $body);
        $input = json_decode(substr($html, 5), true);

        if (!$input || !isset($input[0][2])) {
            return $savedApps;
        }

        $data = json_decode($input[0][2], true);
        if ($data === null) {
            return $savedApps;
        }

        $newApps = $this->extractPaginatedApps($data);
        $nextToken = ScriptData::getPath($data, [0, 0, 7, 1]);
        $allApps = array_merge($savedApps, $newApps);

        return $this->checkFinished($allApps, $num, $nextToken, $lang, $country);
    }

    private function extractPaginatedApps(array $data): array
    {
        $appsList = ScriptData::getPath($data, [0, 0, 0]) ?? [];
        return $this->processApps($appsList);
    }

    private function getPaginationBody(string $token): string
    {
        return "f.req=%5B%5B%5B%22qnKhOb%22%2C%22%5B%5Bnull%2C%5B%5B10%2C%5B10%2C50%5D%5D%2Ctrue%2Cnull%2C%5B96%2C27%2C4%2C8%2C57%2C30%2C110%2C79%2C11%2C16%2C49%2C1%2C3%2C9%2C12%2C104%2C55%2C56%2C51%2C10%2C34%2C77%5D%5D%2Cnull%2C%5C%22{$token}%5C%22%5D%5D%22%2Cnull%2C%22generic%22%5D%5D%5D";
    }

    private function getAppMappings(): array
    {
        return [
            'title' => [2],
            'appId' => [12, 0],
            'url' => [
                'path' => [9, 4, 2],
                'fun' => fn($path) => $path ? self::BASE_URL . $path : null,
            ],
            'icon' => [1, 1, 0, 3, 2],
            'developer' => [4, 0, 0, 0],
            'developerId' => [
                'path' => [4, 0, 0, 1, 4, 2],
                'fun' => fn($link) => $link ? explode('?id=', $link)[1] ?? null : null,
            ],
            'currency' => [7, 0, 3, 2, 1, 0, 1],
            'price' => [
                'path' => [7, 0, 3, 2, 1, 0, 0],
                'fun' => fn($price) => $price !== null ? $price / 1000000 : 0,
            ],
            'free' => [
                'path' => [7, 0, 3, 2, 1, 0, 0],
                'fun' => fn($price) => $price === 0,
            ],
            'summary' => [4, 1, 1, 1, 1],
            'scoreText' => [6, 0, 2, 1, 0],
            'score' => [6, 0, 2, 1, 1],
        ];
    }
}
