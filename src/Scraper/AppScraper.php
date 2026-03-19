<?php

declare(strict_types=1);

namespace GooglePlayScraper\Scraper;

use GooglePlayScraper\Utils\HttpClient;
use GooglePlayScraper\Utils\MappingHelper;
use GooglePlayScraper\Utils\ScriptData;

class AppScraper
{
    private const BASE_URL = 'https://play.google.com';

    public function __construct(private readonly HttpClient $client) {}

    public function getApp(string $appId, string $lang = 'en', string $country = 'us'): array
    {
        $params = http_build_query([
            'id' => $appId,
            'hl' => $lang,
            'gl' => $country,
        ]);

        $url = self::BASE_URL . "/store/apps/details?{$params}";
        $html = $this->client->get($url);
        $parsed = ScriptData::parse($html);
        $result = ScriptData::extractFields($this->getMappings(), $parsed);
        $result['appId'] = $appId;
        $result['url'] = $url;

        return $result;
    }

    private function getMappings(): array
    {
        return [
            'title' => ['ds:5', 1, 2, 0, 0],
            'description' => [
                'path' => ['ds:5', 1, 2],
                'fun' => fn($val) => MappingHelper::descriptionText(MappingHelper::descriptionHtmlLocalized($val)),
            ],
            'descriptionHTML' => [
                'path' => ['ds:5', 1, 2],
                'fun' => fn($val) => MappingHelper::descriptionHtmlLocalized($val),
            ],
            'summary' => ['ds:5', 1, 2, 73, 0, 1],
            'installs' => ['ds:5', 1, 2, 13, 0],
            'minInstalls' => ['ds:5', 1, 2, 13, 1],
            'maxInstalls' => ['ds:5', 1, 2, 13, 2],
            'score' => ['ds:5', 1, 2, 51, 0, 1],
            'scoreText' => ['ds:5', 1, 2, 51, 0, 0],
            'ratings' => ['ds:5', 1, 2, 51, 2, 1],
            'reviews' => ['ds:5', 1, 2, 51, 3, 1],
            'histogram' => [
                'path' => ['ds:5', 1, 2, 51, 1],
                'fun' => fn($val) => MappingHelper::buildHistogram($val),
            ],
            'price' => [
                'path' => ['ds:5', 1, 2, 57, 0, 0, 0, 0, 1, 0, 0],
                'fun' => fn($val) => ($val / 1000000) ?: 0,
            ],
            'originalPrice' => [
                'path' => ['ds:5', 1, 2, 57, 0, 0, 0, 0, 1, 1, 0],
                'fun' => fn($price) => $price ? $price / 1000000 : null,
            ],
            'discountEndDate' => ['ds:5', 1, 2, 57, 0, 0, 0, 0, 14, 1],
            'free' => [
                'path' => ['ds:5', 1, 2, 57, 0, 0, 0, 0, 1, 0, 0],
                'fun' => fn($val) => $val === 0,
            ],
            'currency' => ['ds:5', 1, 2, 57, 0, 0, 0, 0, 1, 0, 1],
            'priceText' => [
                'path' => ['ds:5', 1, 2, 57, 0, 0, 0, 0, 1, 0, 2],
                'fun' => fn($val) => MappingHelper::priceText($val),
            ],
            'available' => [
                'path' => ['ds:5', 1, 2, 18, 0],
                'fun' => fn($val) => (bool) $val,
            ],
            'offersIAP' => [
                'path' => ['ds:5', 1, 2, 19, 0],
                'fun' => fn($val) => (bool) $val,
            ],
            'IAPRange' => ['ds:5', 1, 2, 19, 0],
            'androidVersion' => [
                'path' => ['ds:5', 1, 2, 140, 1, 1, 0, 0, 1],
                'fallbackPath' => ['ds:5', 1, 2, -1, '141', 1, 1, 0, 0, 1],
                'fun' => fn($v) => MappingHelper::normalizeAndroidVersion($v),
            ],
            'androidVersionText' => [
                'path' => ['ds:5', 1, 2, 140, 1, 1, 0, 0, 1],
                'fallbackPath' => ['ds:5', 1, 2, -1, '141', 1, 1, 0, 0, 1],
                'fun' => fn($v) => $v ?: 'Varies with device',
            ],
            'androidMaxVersion' => [
                'path' => ['ds:5', 1, 2, 140, 1, 1, 0, 1, 1],
                'fallbackPath' => ['ds:5', 1, 2, -1, '141', 1, 1, 0, 1, 1],
                'fun' => fn($v) => MappingHelper::normalizeAndroidVersion($v),
            ],
            'developer' => ['ds:5', 1, 2, 68, 0],
            'developerId' => [
                'path' => ['ds:5', 1, 2, 68, 1, 4, 2],
                'fun' => fn($devUrl) => $devUrl ? explode('id=', $devUrl)[1] ?? null : null,
            ],
            'developerEmail' => ['ds:5', 1, 2, 69, 1, 0],
            'developerWebsite' => ['ds:5', 1, 2, 69, 0, 5, 2],
            'developerAddress' => ['ds:5', 1, 2, 69, 2, 0],
            'developerLegalName' => ['ds:5', 1, 2, 69, 4, 0],
            'developerLegalEmail' => ['ds:5', 1, 2, 69, 4, 1, 0],
            'developerLegalAddress' => [
                'path' => ['ds:5', 1, 2, 69],
                'fun' => function ($searchArray) {
                    $val = ScriptData::getPath($searchArray, [4, 2, 0]);
                    return $val ? str_replace("\n", ', ', (string) $val) : null;
                },
            ],
            'developerLegalPhoneNumber' => ['ds:5', 1, 2, 69, 4, 3],
            'privacyPolicy' => ['ds:5', 1, 2, 99, 0, 5, 2],
            'developerInternalID' => [
                'path' => ['ds:5', 1, 2, 68, 1, 4, 2],
                'fun' => fn($devUrl) => $devUrl ? explode('id=', $devUrl)[1] ?? null : null,
            ],
            'genre' => ['ds:5', 1, 2, 79, 0, 0, 0],
            'genreId' => ['ds:5', 1, 2, 79, 0, 0, 2],
            'categories' => [
                'path' => ['ds:5', 1, 2],
                'fun' => function ($searchArray) {
                    $cats = MappingHelper::extractCategories(
                        ScriptData::getPath($searchArray, [118])
                    );
                    if (empty($cats)) {
                        $cats[] = [
                            'name' => ScriptData::getPath($searchArray, [79, 0, 0, 0]),
                            'id' => ScriptData::getPath($searchArray, [79, 0, 0, 2]),
                        ];
                    }
                    return $cats;
                },
            ],
            'icon' => ['ds:5', 1, 2, 95, 0, 3, 2],
            'headerImage' => ['ds:5', 1, 2, 96, 0, 3, 2],
            'screenshots' => [
                'path' => ['ds:5', 1, 2, 78, 0],
                'fun' => function ($screenshots) {
                    if (!is_array($screenshots)) {
                        return [];
                    }
                    return array_map(
                        fn($s) => ScriptData::getPath($s, [3, 2]),
                        $screenshots
                    );
                },
            ],
            'video' => ['ds:5', 1, 2, 100, 0, 0, 3, 2],
            'videoImage' => ['ds:5', 1, 2, 100, 1, 0, 3, 2],
            'previewVideo' => ['ds:5', 1, 2, 100, 1, 2, 0, 2],
            'contentRating' => ['ds:5', 1, 2, 9, 0],
            'contentRatingDescription' => ['ds:5', 1, 2, 9, 2, 1],
            'adSupported' => [
                'path' => ['ds:5', 1, 2, 48],
                'fun' => fn($val) => (bool) $val,
            ],
            'released' => ['ds:5', 1, 2, 10, 0],
            'updated' => [
                'path' => ['ds:5', 1, 2, 145, 0, 1, 0],
                'fallbackPath' => ['ds:5', 1, 2, -1, '146', 0, 1, 0],
                'fun' => fn($ts) => $ts ? $ts * 1000 : null,
            ],
            'version' => [
                'path' => ['ds:5', 1, 2, 140, 0, 0, 0],
                'fallbackPath' => ['ds:5', 1, 2, -1, '141', 0, 0, 0],
                'fun' => fn($val) => $val ?: 'VARY',
            ],
            'recentChanges' => [
                'path' => ['ds:5', 1, 2, 144, 1, 1],
                'fallbackPath' => ['ds:5', 1, 2, -1, '145', 1, 1],
                'fun' => fn($val) => $val,
            ],
            'comments' => [
                'path' => [],
                'isArray' => true,
                'fun' => fn($data) => MappingHelper::extractComments($data),
            ],
            'preregister' => [
                'path' => ['ds:5', 1, 2, 18, 0],
                'fun' => fn($val) => $val === 1,
            ],
            'earlyAccessEnabled' => [
                'path' => ['ds:5', 1, 2, 18, 2],
                'fun' => fn($val) => is_string($val),
            ],
            'isAvailableInPlayPass' => [
                'path' => ['ds:5', 1, 2, 62],
                'fun' => fn($field) => !empty($field),
            ],
        ];
    }
}
