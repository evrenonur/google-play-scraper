<?php

declare(strict_types=1);

namespace GooglePlayScraper\Scraper;

use GooglePlayScraper\Enum\Category;
use GooglePlayScraper\Utils\HttpClient;

class CategoriesScraper
{
    private const BASE_URL = 'https://play.google.com';
    private const CATEGORY_URL_PREFIX = '/store/apps/category/';

    public function __construct(private readonly HttpClient $client) {}

    public function getCategories(): array
    {
        // Primary: scrape from Play Store HTML
        $scraped = $this->scrapeFromHtml();
        if (count($scraped) > 5) {
            return $scraped;
        }

        // Fallback: return all known category IDs from the Category enum
        return array_map(fn(Category $c) => $c->value, Category::cases());
    }

    private function scrapeFromHtml(): array
    {
        $url = self::BASE_URL . '/store/apps';
        $html = $this->client->get($url);

        $categoryIds = [];

        // Extract category IDs from all /store/apps/category/ links
        if (preg_match_all('/\/store\/apps\/category\/([A-Z][A-Z_0-9]+)/', $html, $matches)) {
            foreach ($matches[1] as $catId) {
                $categoryIds[] = $catId;
            }
        }

        $categoryIds[] = 'APPLICATION';
        $categoryIds[] = 'GAME';

        return array_values(array_unique($categoryIds));
    }
}
