<?php

declare(strict_types=1);

namespace GooglePlayScraper\Tests\Scraper;

use GooglePlayScraper\Scraper\SimilarScraper;
use GooglePlayScraper\Scraper\AppScraper;
use GooglePlayScraper\Utils\HttpClient;
use PHPUnit\Framework\TestCase;

class SimilarScraperTest extends TestCase
{
    /**
     * Build app item matching SimilarScraper::getAppMappings().
     * appId => [0,0], title => [3], developer => [14]
     */
    private function makeAppItem(string $appId, string $title): array
    {
        $item = array_fill(0, 15, null);
        $item[0] = [$appId];
        $item[3] = $title;
        $item[14] = 'Developer';
        return $item;
    }

    /**
     * Build HTML with ds:7 so SimilarScraper finds apps at [1][1][0][21][0]
     * and cluster title at [1][1][0][21][1][0].
     */
    private function buildSimilarHtml(array $apps, string $clusterTitle = 'Similar apps'): string
    {
        $slot = array_fill(0, 22, null);
        $slot[21] = [$apps, [$clusterTitle]];
        $ds = [null, [null, [$slot]]];

        $json = json_encode($ds, JSON_UNESCAPED_UNICODE);
        return "<html><script nonce=\"x\">AF_initDataCallback({key: 'ds:7', hash: '1', data:{$json}, sideChannel: {}});</script></html>";
    }

    public function testSimilarReturnsApps(): void
    {
        $apps = [
            $this->makeAppItem('com.sim1', 'Similar App 1'),
            $this->makeAppItem('com.sim2', 'Similar App 2'),
        ];

        $client = $this->createMock(HttpClient::class);
        $client->method('get')->willReturn($this->buildSimilarHtml($apps));

        $appScraper = $this->createMock(AppScraper::class);
        $scraper = new SimilarScraper($client, $appScraper);
        $result = $scraper->similar('com.test.app');

        $this->assertCount(2, $result);
        $this->assertEquals('com.sim1', $result[0]['appId']);
        $this->assertEquals('Similar App 1', $result[0]['title']);
        $this->assertEquals('com.sim2', $result[1]['appId']);
    }

    public function testSimilarReturnsEmptyOnNoData(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->method('get')->willReturn('<html></html>');

        $appScraper = $this->createMock(AppScraper::class);
        $scraper = new SimilarScraper($client, $appScraper);
        $result = $scraper->similar('com.test.app');

        $this->assertEmpty($result);
    }

    public function testSimilarWithFullDetail(): void
    {
        $apps = [
            $this->makeAppItem('com.sim1', 'Sim 1'),
            $this->makeAppItem('com.sim2', 'Sim 2'),
        ];

        $client = $this->createMock(HttpClient::class);
        $client->method('get')->willReturn($this->buildSimilarHtml($apps));

        $appScraper = $this->createMock(AppScraper::class);
        $appScraper->expects($this->exactly(2))
            ->method('getApp')
            ->willReturnCallback(fn($id) => ['appId' => $id, 'title' => $id . ' Full']);

        $scraper = new SimilarScraper($client, $appScraper);
        $result = $scraper->similar('com.test.app', fullDetail: true);

        $this->assertEquals('com.sim1 Full', $result[0]['title']);
        $this->assertEquals('com.sim2 Full', $result[1]['title']);
    }

    public function testSimilarPassesCorrectUrlParams(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->expects($this->once())
            ->method('get')
            ->with($this->logicalAnd(
                $this->stringContains('id=com.test.app'),
                $this->stringContains('hl=tr'),
                $this->stringContains('gl=tr')
            ))
            ->willReturn('<html></html>');

        $appScraper = $this->createMock(AppScraper::class);
        $scraper = new SimilarScraper($client, $appScraper);
        $scraper->similar('com.test.app', lang: 'tr', country: 'tr');
    }
}
