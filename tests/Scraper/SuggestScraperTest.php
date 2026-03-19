<?php

declare(strict_types=1);

namespace GooglePlayScraper\Tests\Scraper;

use GooglePlayScraper\Scraper\SuggestScraper;
use GooglePlayScraper\Utils\HttpClient;
use PHPUnit\Framework\TestCase;

class SuggestScraperTest extends TestCase
{
    private function buildBatchResponse(mixed $data): string
    {
        $inner = json_encode($data, JSON_UNESCAPED_UNICODE);
        $outer = json_encode([[$inner, null, $inner]], JSON_UNESCAPED_UNICODE);
        return ")]}'\n{$outer}";
    }

    public function testSuggestReturnsSuggestions(): void
    {
        $data = [[
            [['instagram'], ['instagram lite'], ['instapay']],
        ]];

        $client = $this->createMock(HttpClient::class);
        $client->method('post')->willReturn($this->buildBatchResponse($data));

        $scraper = new SuggestScraper($client);
        $result = $scraper->suggest('inst');

        $this->assertCount(3, $result);
        $this->assertEquals('instagram', $result[0]);
        $this->assertEquals('instagram lite', $result[1]);
        $this->assertEquals('instapay', $result[2]);
    }

    public function testSuggestReturnsEmptyOnNoData(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->method('post')->willReturn(')]}\'invalid');

        $scraper = new SuggestScraper($client);
        $result = $scraper->suggest('zzzzz');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testSuggestPassesCorrectParams(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->expects($this->once())
            ->method('post')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('hl=tr'),
                    $this->stringContains('gl=tr')
                ),
                $this->stringContains('inst')
            )
            ->willReturn(')]}\'[]');

        $scraper = new SuggestScraper($client);
        $scraper->suggest('inst', lang: 'tr', country: 'tr');
    }
}
