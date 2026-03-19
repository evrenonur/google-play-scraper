<?php

declare(strict_types=1);

namespace GooglePlayScraper\Tests\Scraper;

use GooglePlayScraper\Scraper\ReviewsScraper;
use GooglePlayScraper\Enum\Sort;
use GooglePlayScraper\Utils\HttpClient;
use PHPUnit\Framework\TestCase;

class ReviewsScraperTest extends TestCase
{
    private function buildBatchResponse(mixed $data): string
    {
        $inner = json_encode($data, JSON_UNESCAPED_UNICODE);
        $outer = json_encode([[$inner, null, $inner]], JSON_UNESCAPED_UNICODE);
        return ")]}'\n{$outer}";
    }

    private function makeReviewItem(string $id, string $name, int $score, string $text): array
    {
        return [
            $id,                                    // 0 => id
            [$name, [null, null, null, 'avatar.jpg']], // 1 => user
            $score,                                 // 2 => score
            null,
            $text,                                  // 4 => text
            [1700000000, 0],                        // 5 => date
            42,                                     // 6 => thumbsUp
            null,
            null,
            null,
            '1.0.0',                                // 10 => version
            null,
            [[[['gameplay', [4]]]]],                // 12 => criterias
        ];
    }

    public function testGetReviewsReturnsReviews(): void
    {
        $reviews = [
            $this->makeReviewItem('rev1', 'Ali', 5, 'Harika uygulama'),
            $this->makeReviewItem('rev2', 'Veli', 3, 'Orta kalite'),
        ];
        $data = [$reviews, [null, 'next_token_abc']];

        $client = $this->createMock(HttpClient::class);
        $client->method('post')->willReturn($this->buildBatchResponse($data));

        $scraper = new ReviewsScraper($client);
        $result = $scraper->getReviews('com.test.app', Sort::NEWEST, num: 2, paginate: true);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('nextPaginationToken', $result);
        $this->assertCount(2, $result['data']);
        $this->assertEquals('next_token_abc', $result['nextPaginationToken']);

        $review = $result['data'][0];
        $this->assertEquals('rev1', $review['id']);
        $this->assertEquals('Ali', $review['userName']);
        $this->assertEquals(5, $review['score']);
        $this->assertEquals('Harika uygulama', $review['text']);
        $this->assertEquals(42, $review['thumbsUp']);
        $this->assertEquals('1.0.0', $review['version']);
    }

    public function testGetReviewsReturnsEmptyOnNoData(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->method('post')->willReturn(')]}\'invalid');

        $scraper = new ReviewsScraper($client);
        $result = $scraper->getReviews('com.noreviews.app', paginate: true);

        $this->assertEmpty($result['data']);
        $this->assertNull($result['nextPaginationToken']);
    }

    public function testGetReviewsRespectsNumLimit(): void
    {
        $reviews = [];
        for ($i = 0; $i < 10; $i++) {
            $reviews[] = $this->makeReviewItem("rev{$i}", "User{$i}", 4, "Review {$i}");
        }
        $data = [$reviews, null];

        $client = $this->createMock(HttpClient::class);
        $client->method('post')->willReturn($this->buildBatchResponse($data));

        $scraper = new ReviewsScraper($client);
        $result = $scraper->getReviews('com.test.app', num: 3, paginate: true);

        $this->assertCount(3, $result['data']);
    }

    public function testGetReviewsWithPaginationToken(): void
    {
        $reviews = [$this->makeReviewItem('rev1', 'Ali', 5, 'Next page')];
        $data = [$reviews, [null, 'next_token_2']];

        $client = $this->createMock(HttpClient::class);
        $client->expects($this->once())
            ->method('post')
            ->with(
                $this->anything(),
                $this->stringContains('existing_token')
            )
            ->willReturn($this->buildBatchResponse($data));

        $scraper = new ReviewsScraper($client);
        $result = $scraper->getReviews('com.test.app', paginate: true, nextPaginationToken: 'existing_token');

        $this->assertCount(1, $result['data']);
        $this->assertEquals('Next page', $result['data'][0]['text']);
    }

    public function testGetReviewsUrlContainsReviewId(): void
    {
        $reviews = [$this->makeReviewItem('rev123', 'Ali', 5, 'Test')];
        $data = [$reviews, null];

        $client = $this->createMock(HttpClient::class);
        $client->method('post')->willReturn($this->buildBatchResponse($data));

        $scraper = new ReviewsScraper($client);
        $result = $scraper->getReviews('com.test.app', paginate: true, num: 1);

        $this->assertStringContainsString('com.test.app', $result['data'][0]['url']);
        $this->assertStringContainsString('rev123', $result['data'][0]['url']);
    }
}
