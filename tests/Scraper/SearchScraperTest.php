<?php

declare(strict_types=1);

namespace GooglePlayScraper\Tests\Scraper;

use GooglePlayScraper\Scraper\SearchScraper;
use GooglePlayScraper\Scraper\AppScraper;
use GooglePlayScraper\Enum\Price;
use GooglePlayScraper\Utils\HttpClient;
use PHPUnit\Framework\TestCase;

class SearchScraperTest extends TestCase
{
    /**
     * Build HTML with AF_initDataCallback for ds:1.
     * SearchScraper paths:
     *   appsSection = parsed['ds:1'][0][1][0][0][0]
     *   sections    = parsed['ds:1'][0][1][0][0]
     */
    private function buildSearchHtml(array $apps): string
    {
        // sections[0] = apps array
        $sections = [$apps];
        // ds1[0][1][0][0] = sections
        $ds1 = [[null, [[$sections]]]];
        $json = json_encode($ds1, JSON_UNESCAPED_UNICODE);
        return "<html><script nonce=\"x\">AF_initDataCallback({key: 'ds:1', hash: '1', data:{$json}, sideChannel: {}});</script></html>";
    }

    /**
     * Build a search app item matching SearchScraper::getAppMappings().
     * title => [2], appId => [12, 0]
     */
    private function makeAppItem(string $appId, string $title): array
    {
        $item = array_fill(0, 13, null);
        $item[2] = $title;
        $item[12] = [$appId];
        return $item;
    }

    public function testSearchReturnsApps(): void
    {
        $apps = [
            $this->makeAppItem('com.app1', 'App One'),
            $this->makeAppItem('com.app2', 'App Two'),
        ];

        $client = $this->createMock(HttpClient::class);
        $client->method('get')->willReturn($this->buildSearchHtml($apps));

        $appScraper = $this->createMock(AppScraper::class);
        $scraper = new SearchScraper($client, $appScraper);
        $results = $scraper->search('test', num: 2);

        $this->assertCount(2, $results);
        $this->assertEquals('App One', $results[0]['title']);
        $this->assertEquals('com.app1', $results[0]['appId']);
        $this->assertEquals('App Two', $results[1]['title']);
    }

    public function testSearchRespectsNumLimit(): void
    {
        $apps = [];
        for ($i = 0; $i < 10; $i++) {
            $apps[] = $this->makeAppItem("com.app{$i}", "App {$i}");
        }

        $client = $this->createMock(HttpClient::class);
        $client->method('get')->willReturn($this->buildSearchHtml($apps));

        $appScraper = $this->createMock(AppScraper::class);
        $scraper = new SearchScraper($client, $appScraper);
        $results = $scraper->search('test', num: 3);

        $this->assertCount(3, $results);
    }

    public function testSearchThrowsOnNumOver250(): void
    {
        $client = $this->createMock(HttpClient::class);
        $appScraper = $this->createMock(AppScraper::class);
        $scraper = new SearchScraper($client, $appScraper);

        $this->expectException(\InvalidArgumentException::class);
        $scraper->search('test', num: 251);
    }

    public function testSearchReturnsEmptyForNoResults(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->method('get')->willReturn('<html></html>');

        $appScraper = $this->createMock(AppScraper::class);
        $scraper = new SearchScraper($client, $appScraper);
        $results = $scraper->search('nonexistent_app_xyz');

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testSearchWithFullDetailCallsAppScraper(): void
    {
        $apps = [$this->makeAppItem('com.app1', 'App One')];

        $client = $this->createMock(HttpClient::class);
        $client->method('get')->willReturn($this->buildSearchHtml($apps));

        $appScraper = $this->createMock(AppScraper::class);
        $appScraper->expects($this->once())
            ->method('getApp')
            ->with('com.app1', 'en', 'us')
            ->willReturn(['appId' => 'com.app1', 'title' => 'App One Full']);

        $scraper = new SearchScraper($client, $appScraper);
        $results = $scraper->search('test', num: 1, fullDetail: true);

        $this->assertEquals('App One Full', $results[0]['title']);
    }

    public function testSearchPassesCorrectUrlParams(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->expects($this->once())
            ->method('get')
            ->with($this->logicalAnd(
                $this->stringContains('hl=tr'),
                $this->stringContains('gl=tr'),
                $this->stringContains('price=1')
            ))
            ->willReturn('<html></html>');

        $appScraper = $this->createMock(AppScraper::class);
        $scraper = new SearchScraper($client, $appScraper);
        $scraper->search('test', lang: 'tr', country: 'tr', price: Price::FREE);
    }
}
