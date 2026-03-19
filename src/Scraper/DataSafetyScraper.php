<?php

declare(strict_types=1);

namespace GooglePlayScraper\Scraper;

use GooglePlayScraper\Utils\HttpClient;
use GooglePlayScraper\Utils\ScriptData;

class DataSafetyScraper
{
    private const BASE_URL = 'https://play.google.com';

    public function __construct(private readonly HttpClient $client) {}

    public function getDataSafety(string $appId, string $lang = 'en'): array
    {
        $params = http_build_query([
            'id' => $appId,
            'hl' => $lang,
        ]);

        $url = self::BASE_URL . "/store/apps/datasafety?{$params}";
        $html = $this->client->get($url);
        $parsed = ScriptData::parse($html);

        return ScriptData::extractFields($this->getMappings(), $parsed);
    }

    private function getMappings(): array
    {
        return [
            'sharedData' => [
                'path' => ['ds:3', 1, 2, 1, 138, 4, 0, 0],
                'fun' => fn($entries) => self::mapDataEntries($entries),
            ],
            'collectedData' => [
                'path' => ['ds:3', 1, 2, 1, 138, 4, 1, 0],
                'fun' => fn($entries) => self::mapDataEntries($entries),
            ],
            'securityPractices' => [
                'path' => ['ds:3', 1, 2, 1, 138, 9, 2],
                'fun' => fn($practices) => self::mapSecurityPractices($practices),
            ],
            'privacyPolicyUrl' => ['ds:3', 1, 2, 1, 100, 0, 5, 2],
        ];
    }

    private static function mapDataEntries(mixed $dataEntries): array
    {
        if (!is_array($dataEntries)) {
            return [];
        }

        $result = [];
        foreach ($dataEntries as $data) {
            $type = ScriptData::getPath($data, [0, 1]);
            $details = ScriptData::getPath($data, [4]);

            if (!is_array($details)) {
                continue;
            }

            foreach ($details as $detail) {
                $result[] = [
                    'data' => $detail[0] ?? null,
                    'optional' => (bool) ($detail[1] ?? false),
                    'purpose' => $detail[2] ?? null,
                    'type' => $type,
                ];
            }
        }

        return $result;
    }

    private static function mapSecurityPractices(mixed $practices): array
    {
        if (!is_array($practices)) {
            return [];
        }

        return array_map(fn($practice) => [
            'practice' => $practice[1] ?? null,
            'description' => ScriptData::getPath($practice, [2, 1]),
        ], $practices);
    }
}
