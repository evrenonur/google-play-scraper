<?php

declare(strict_types=1);

namespace GooglePlayScraper\Utils;

use Symfony\Component\DomCrawler\Crawler;

class MappingHelper
{
    public static function descriptionHtmlLocalized(mixed $searchArray): ?string
    {
        $translation = ScriptData::getPath($searchArray, [12, 0, 0, 1]);
        $original = ScriptData::getPath($searchArray, [72, 0, 1]);
        return $translation ?? $original;
    }

    public static function descriptionText(?string $description): ?string
    {
        if ($description === null) {
            return null;
        }
        $html = str_replace('<br>', "\r\n", $description);
        return strip_tags($html);
    }

    public static function priceText(?string $priceText): string
    {
        return $priceText ?? 'Free';
    }

    public static function normalizeAndroidVersion(?string $version): string
    {
        if ($version === null) {
            return 'VARY';
        }
        $parts = explode(' ', $version);
        $number = $parts[0] ?? '';
        if (is_numeric($number)) {
            return $number;
        }
        return 'VARY';
    }

    public static function buildHistogram(?array $container): array
    {
        if ($container === null) {
            return [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        }
        return [
            1 => $container[1][1] ?? 0,
            2 => $container[2][1] ?? 0,
            3 => $container[3][1] ?? 0,
            4 => $container[4][1] ?? 0,
            5 => $container[5][1] ?? 0,
        ];
    }

    public static function extractComments(mixed $data): array
    {
        $comments = [];

        foreach (['ds:8', 'ds:9'] as $path) {
            $author = ScriptData::getPath($data, [$path, 0, 0, 1, 0]);
            $version = ScriptData::getPath($data, [$path, 0, 0, 10]);
            $date = ScriptData::getPath($data, [$path, 0, 0, 5, 0]);

            if ($author && $version && $date) {
                $comments = $data[$path][0] ?? [];
                break;
            }
        }

        if (count($comments) > 0) {
            $comments = array_slice(
                array_map(fn($c) => $c[4] ?? null, $comments),
                0,
                5
            );
        }

        return $comments;
    }

    public static function extractCategories(?array $searchArray, array &$categories = []): array
    {
        if (!is_array($searchArray) || empty($searchArray)) {
            return $categories;
        }

        if (count($searchArray) >= 4 && is_string($searchArray[0] ?? null)) {
            $categories[] = [
                'name' => $searchArray[0],
                'id' => $searchArray[2] ?? null,
            ];
        } else {
            foreach ($searchArray as $sub) {
                if (is_array($sub)) {
                    self::extractCategories($sub, $categories);
                }
            }
        }

        return $categories;
    }
}
