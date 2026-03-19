<?php

declare(strict_types=1);

namespace GooglePlayScraper\Tests\Scraper;

use GooglePlayScraper\Scraper\CategoriesScraper;
use GooglePlayScraper\Enum\Category;
use GooglePlayScraper\Utils\HttpClient;
use PHPUnit\Framework\TestCase;

class CategoriesScraperTest extends TestCase
{
    public function testGetCategoriesFromHtml(): void
    {
        $html = '<html><body>'
            . '<a href="/store/apps/category/FINANCE">Finance</a>'
            . '<a href="/store/apps/category/EDUCATION">Education</a>'
            . '<a href="/store/apps/category/GAME_ACTION">Action Games</a>'
            . '<a href="/store/apps/category/COMMUNICATION">Communication</a>'
            . '<a href="/store/apps/category/PHOTOGRAPHY">Photography</a>'
            . '<a href="/store/apps/category/SOCIAL">Social</a>'
            . '</body></html>';

        $client = $this->createMock(HttpClient::class);
        $client->method('get')->willReturn($html);

        $scraper = new CategoriesScraper($client);
        $result = $scraper->getCategories();

        // 6 scraped + APPLICATION + GAME = 8 unique
        $this->assertContains('FINANCE', $result);
        $this->assertContains('EDUCATION', $result);
        $this->assertContains('GAME_ACTION', $result);
        $this->assertContains('APPLICATION', $result);
        $this->assertContains('GAME', $result);
        $this->assertGreaterThan(5, count($result));
    }

    public function testGetCategoriesFallsBackToEnum(): void
    {
        // Return HTML with fewer than 6 categories (including APPLICATION & GAME)
        $html = '<html><body><a href="/store/apps/category/FINANCE">Finance</a></body></html>';

        $client = $this->createMock(HttpClient::class);
        $client->method('get')->willReturn($html);

        $scraper = new CategoriesScraper($client);
        $result = $scraper->getCategories();

        // Falls back to enum — should contain all Category enum values
        $this->assertCount(count(Category::cases()), $result);
        $this->assertContains('GAME', $result);
        $this->assertContains('APPLICATION', $result);
        $this->assertContains('FINANCE', $result);
    }

    public function testGetCategoriesReturnsUniqueValues(): void
    {
        $html = '<html><body>'
            . '<a href="/store/apps/category/FINANCE">Finance</a>'
            . '<a href="/store/apps/category/FINANCE">Finance Again</a>'
            . '<a href="/store/apps/category/EDUCATION">Education</a>'
            . '<a href="/store/apps/category/GAME">Game</a>'
            . '<a href="/store/apps/category/SOCIAL">Social</a>'
            . '<a href="/store/apps/category/COMMUNICATION">Comm</a>'
            . '<a href="/store/apps/category/PHOTOGRAPHY">Photo</a>'
            . '</body></html>';

        $client = $this->createMock(HttpClient::class);
        $client->method('get')->willReturn($html);

        $scraper = new CategoriesScraper($client);
        $result = $scraper->getCategories();

        $this->assertEquals(count(array_unique($result)), count($result));
    }
}
