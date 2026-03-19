<?php

declare(strict_types=1);

namespace GooglePlayScraper\Tests\Scraper;

use GooglePlayScraper\Scraper\ListScraper;
use GooglePlayScraper\Scraper\AppScraper;
use GooglePlayScraper\Enum\Category;
use GooglePlayScraper\Enum\Collection;
use GooglePlayScraper\Utils\HttpClient;
use PHPUnit\Framework\TestCase;

class ListScraperTest extends TestCase
{
    /**
     * Build app item matching ListScraper::getAppMappings().
     * title => [0,3], appId => [0,0,0]
     */
    private function makeAppItem(string $appId, string $title): array
    {
        $inner = array_fill(0, 15, null);
        $inner[0] = [$appId];
        $inner[3] = $title;
        $inner[14] = 'Developer';
        return [$inner];
    }

    /**
     * ListScraper::list() does:
     *   $lines = explode("\n", $html);
     *   $input = json_decode($lines[3], true);
     *   $data = json_decode($input[0][2], true);
     *   $appsList = ScriptData::getPath($data, [0, 1, 0, 28, 0]);
     */
    private function buildListResponse(array $apps): string
    {
        // $data[0][1][0] needs index 28, and [28][0] = $apps
        $slot = array_fill(0, 29, null);
        $slot[28] = [$apps];

        $data = [[null, [$slot]]];

        $inner = json_encode($data, JSON_UNESCAPED_UNICODE);
        $outer = [[null, null, $inner]];

        return "line0\nline1\nline2\n" . json_encode($outer, JSON_UNESCAPED_UNICODE) . "\n";
    }

    public function testListReturnsApps(): void
    {
        $apps = [
            $this->makeAppItem('com.app1', 'App One'),
            $this->makeAppItem('com.app2', 'App Two'),
        ];

        $client = $this->createMock(HttpClient::class);
        $client->method('post')->willReturn($this->buildListResponse($apps));

        $appScraper = $this->createMock(AppScraper::class);
        $scraper = new ListScraper($client, $appScraper);
        $result = $scraper->list();

        $this->assertCount(2, $result);
        $this->assertEquals('com.app1', $result[0]['appId']);
        $this->assertEquals('App One', $result[0]['title']);
    }

    public function testListRespectsNumLimit(): void
    {
        $apps = [];
        for ($i = 0; $i < 10; $i++) {
            $apps[] = $this->makeAppItem("com.app{$i}", "App {$i}");
        }

        $client = $this->createMock(HttpClient::class);
        $client->method('post')->willReturn($this->buildListResponse($apps));

        $appScraper = $this->createMock(AppScraper::class);
        $scraper = new ListScraper($client, $appScraper);
        $result = $scraper->list(num: 5);

        $this->assertCount(5, $result);
    }

    public function testListReturnsEmptyOnNoData(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->method('post')->willReturn("line0\nline1\nline2\n[]\n");

        $appScraper = $this->createMock(AppScraper::class);
        $scraper = new ListScraper($client, $appScraper);
        $result = $scraper->list();

        $this->assertEmpty($result);
    }

    public function testListPassesCorrectParams(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->expects($this->once())
            ->method('post')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('hl=tr'),
                    $this->stringContains('gl=tr')
                ),
                $this->logicalAnd(
                    $this->stringContains('topselling_paid'),
                    $this->stringContains('GAME')
                )
            )
            ->willReturn("l0\nl1\nl2\n[]\n");

        $appScraper = $this->createMock(AppScraper::class);
        $scraper = new ListScraper($client, $appScraper);
        $scraper->list(Collection::TOP_PAID, Category::GAME, lang: 'tr', country: 'tr');
    }

    public function testListWithFullDetailCallsAppScraper(): void
    {
        $apps = [$this->makeAppItem('com.app1', 'App 1')];

        $client = $this->createMock(HttpClient::class);
        $client->method('post')->willReturn($this->buildListResponse($apps));

        $appScraper = $this->createMock(AppScraper::class);
        $appScraper->expects($this->once())
            ->method('getApp')
            ->with('com.app1', 'en', 'us')
            ->willReturn(['appId' => 'com.app1', 'title' => 'App 1 Full']);

        $scraper = new ListScraper($client, $appScraper);
        $result = $scraper->list(fullDetail: true, num: 1);

        $this->assertEquals('App 1 Full', $result[0]['title']);
    }
}
