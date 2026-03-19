<?php

declare(strict_types=1);

namespace GooglePlayScraper\Tests\Scraper;

use GooglePlayScraper\Scraper\DeveloperScraper;
use GooglePlayScraper\Scraper\AppScraper;
use GooglePlayScraper\Utils\HttpClient;
use PHPUnit\Framework\TestCase;

class DeveloperScraperTest extends TestCase
{
    /**
     * Build app item matching DeveloperScraper::getAppMappings().
     * For string devId: title => [0,3], appId => [0,0,0]
     * For numeric devId: title => [3], appId => [0,0]
     */
    private function makeAppItem(string $appId, string $title, bool $numeric = false): array
    {
        if ($numeric) {
            $item = array_fill(0, 15, null);
            $item[0] = [$appId];
            $item[3] = $title;
            return $item;
        }

        $inner = array_fill(0, 15, null);
        $inner[0] = [$appId];
        $inner[3] = $title;
        return [$inner];
    }

    /**
     * Build HTML with ds:3.
     * String devId: apps at parsed['ds:3'][0][1][0][22][0]
     * Numeric devId: apps at parsed['ds:3'][0][1][0][21][0]
     */
    private function buildDeveloperHtml(array $apps, bool $numeric = false): string
    {
        $index = $numeric ? 21 : 22;
        $slot = array_fill(0, $index + 1, null);
        $slot[$index] = [$apps];

        $ds3 = [[null, [$slot]]];
        $json = json_encode($ds3, JSON_UNESCAPED_UNICODE);
        return "<html><script nonce=\"x\">AF_initDataCallback({key: 'ds:3', hash: '1', data:{$json}, sideChannel: {}});</script></html>";
    }

    public function testGetAppsWithStringDevId(): void
    {
        $apps = [
            $this->makeAppItem('com.google.chrome', 'Chrome'),
            $this->makeAppItem('com.google.youtube', 'YouTube'),
        ];

        $client = $this->createMock(HttpClient::class);
        $client->method('get')->willReturn($this->buildDeveloperHtml($apps, false));

        $appScraper = $this->createMock(AppScraper::class);
        $scraper = new DeveloperScraper($client, $appScraper);
        $result = $scraper->getApps('Google LLC');

        $this->assertCount(2, $result);
        $this->assertEquals('com.google.chrome', $result[0]['appId']);
        $this->assertEquals('Chrome', $result[0]['title']);
    }

    public function testGetAppsRespectsNum(): void
    {
        $apps = [];
        for ($i = 0; $i < 10; $i++) {
            $apps[] = $this->makeAppItem("com.app{$i}", "App {$i}");
        }

        $client = $this->createMock(HttpClient::class);
        $client->method('get')->willReturn($this->buildDeveloperHtml($apps));

        $appScraper = $this->createMock(AppScraper::class);
        $scraper = new DeveloperScraper($client, $appScraper);
        $result = $scraper->getApps('Dev', num: 3);

        $this->assertCount(3, $result);
    }

    public function testGetAppsReturnsEmptyOnNoData(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->method('get')->willReturn('<html></html>');

        $appScraper = $this->createMock(AppScraper::class);
        $scraper = new DeveloperScraper($client, $appScraper);
        $result = $scraper->getApps('Unknown Dev');

        $this->assertEmpty($result);
    }

    public function testGetAppsUsesCorrectPathForStringDevId(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->expects($this->once())
            ->method('get')
            ->with($this->logicalAnd(
                $this->stringContains('/developer'),
                $this->stringContains('id=Google+LLC')
            ))
            ->willReturn('<html></html>');

        $appScraper = $this->createMock(AppScraper::class);
        $scraper = new DeveloperScraper($client, $appScraper);
        $scraper->getApps('Google LLC');
    }

    public function testGetAppsUsesCorrectPathForNumericDevId(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->expects($this->once())
            ->method('get')
            ->with($this->logicalAnd(
                $this->stringContains('/dev'),
                $this->stringContains('id=5700313618786177705')
            ))
            ->willReturn('<html></html>');

        $appScraper = $this->createMock(AppScraper::class);
        $scraper = new DeveloperScraper($client, $appScraper);
        $scraper->getApps('5700313618786177705');
    }
}
