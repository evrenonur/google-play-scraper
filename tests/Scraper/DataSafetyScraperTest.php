<?php

declare(strict_types=1);

namespace GooglePlayScraper\Tests\Scraper;

use GooglePlayScraper\Scraper\DataSafetyScraper;
use GooglePlayScraper\Utils\HttpClient;
use PHPUnit\Framework\TestCase;

class DataSafetyScraperTest extends TestCase
{
    private function buildHtml(array $dsEntries): string
    {
        $scripts = '';
        foreach ($dsEntries as $key => $data) {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            $scripts .= "<script nonce=\"x\">AF_initDataCallback({key: '{$key}', hash: '1', data:{$json}, sideChannel: {}});</script>\n";
        }
        return "<html><body>{$scripts}</body></html>";
    }

    public function testGetDataSafetyReturnsPrivacyUrl(): void
    {
        // privacyPolicyUrl path: ['ds:3', 1, 2, 1, 100, 0, 5, 2]
        $innerData = array_fill(0, 101, null);
        $innerData[100] = [[null, null, null, null, null, [null, null, 'https://example.com/privacy']]];
        $ds3 = [null, [null, null, [null, $innerData]]];

        $html = $this->buildHtml(['ds:3' => $ds3]);
        $client = $this->createMock(HttpClient::class);
        $client->method('get')->willReturn($html);

        $scraper = new DataSafetyScraper($client);
        $result = $scraper->getDataSafety('com.test.app');

        $this->assertEquals('https://example.com/privacy', $result['privacyPolicyUrl']);
    }

    public function testGetDataSafetyReturnsEmptyOnNoData(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->method('get')->willReturn('<html></html>');

        $scraper = new DataSafetyScraper($client);
        $result = $scraper->getDataSafety('com.empty.app');

        $this->assertIsArray($result);
    }

    public function testGetDataSafetyPassesCorrectUrl(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->expects($this->once())
            ->method('get')
            ->with($this->logicalAnd(
                $this->stringContains('id=com.test.app'),
                $this->stringContains('hl=tr'),
                $this->stringContains('datasafety')
            ))
            ->willReturn('<html></html>');

        $scraper = new DataSafetyScraper($client);
        $scraper->getDataSafety('com.test.app', lang: 'tr');
    }
}
