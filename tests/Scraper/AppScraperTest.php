<?php

declare(strict_types=1);

namespace GooglePlayScraper\Tests\Scraper;

use GooglePlayScraper\Scraper\AppScraper;
use GooglePlayScraper\Utils\HttpClient;
use PHPUnit\Framework\TestCase;

class AppScraperTest extends TestCase
{
    private function createMockClient(string $html): HttpClient
    {
        $client = $this->createMock(HttpClient::class);
        $client->method('get')->willReturn($html);
        return $client;
    }

    private function buildHtml(array $dsEntries): string
    {
        $scripts = '';
        foreach ($dsEntries as $key => $data) {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            $scripts .= "<script nonce=\"x\">AF_initDataCallback({key: '{$key}', hash: '1', data:{$json}, sideChannel: {}});</script>\n";
        }
        return "<html><body>{$scripts}</body></html>";
    }

    public function testGetAppReturnsBasicFields(): void
    {
        // Build $ds5 so that $ds5[1][2] = app fields array
        $appFields = array_fill(0, 80, null);
        $appFields[0] = ['Test App'];                                    // title at [0][0]
        $appFields[13] = ['1M+', 1000000, 5000000];                     // installs
        $appFields[51] = [['4.5', 4.5], null, [null, 100000], [null, 50000]]; // score
        $appFields[68] = ['Google LLC'];                                  // developer
        $appFields[79] = [[['Tools', null, 'TOOLS']]];                   // genre

        $ds5 = [null, [null, null, $appFields]];

        $html = $this->buildHtml(['ds:5' => $ds5]);
        $scraper = new AppScraper($this->createMockClient($html));
        $result = $scraper->getApp('com.test.app', 'en', 'us');

        $this->assertEquals('com.test.app', $result['appId']);
        $this->assertStringContainsString('play.google.com', $result['url']);
        $this->assertEquals('Test App', $result['title']);
        $this->assertEquals('1M+', $result['installs']);
        $this->assertEquals(1000000, $result['minInstalls']);
        $this->assertEquals(4.5, $result['score']);
        $this->assertEquals('4.5', $result['scoreText']);
        $this->assertEquals(100000, $result['ratings']);
        $this->assertEquals('Google LLC', $result['developer']);
        $this->assertEquals('Tools', $result['genre']);
        $this->assertEquals('TOOLS', $result['genreId']);
    }

    public function testGetAppSetsUrlWithParams(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->expects($this->once())
            ->method('get')
            ->with($this->stringContains('id=com.test.app&hl=tr&gl=tr'))
            ->willReturn('<html></html>');

        $scraper = new AppScraper($client);
        $scraper->getApp('com.test.app', 'tr', 'tr');
    }

    public function testGetAppHandlesEmptyResponse(): void
    {
        $scraper = new AppScraper($this->createMockClient('<html></html>'));
        $result = $scraper->getApp('com.empty.app');

        $this->assertEquals('com.empty.app', $result['appId']);
        $this->assertNull($result['title']);
        $this->assertNull($result['developer']);
    }
}
