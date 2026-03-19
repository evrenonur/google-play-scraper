<?php

declare(strict_types=1);

namespace GooglePlayScraper\Tests\Scraper;

use GooglePlayScraper\Scraper\PermissionsScraper;
use GooglePlayScraper\Utils\HttpClient;
use PHPUnit\Framework\TestCase;

class PermissionsScraperTest extends TestCase
{
    private function buildBatchResponse(mixed $data): string
    {
        $inner = json_encode($data, JSON_UNESCAPED_UNICODE);
        $outer = json_encode([[$inner, null, $inner]], JSON_UNESCAPED_UNICODE);
        return ")]}'\n{$outer}";
    }

    public function testGetPermissionsReturnsFull(): void
    {
        // index 0 = COMMON permissions, index 1 = OTHER permissions
        $data = [
            // COMMON (index 0)
            [
                ['Identity', null, [
                    [null, 'find accounts on device'],
                    [null, 'add or remove accounts'],
                ]],
                ['Contacts', null, [
                    [null, 'read your contacts'],
                ]],
            ],
            // OTHER (index 1)
            [
                ['Other', null, [
                    [null, 'full network access'],
                ]],
            ],
        ];

        $client = $this->createMock(HttpClient::class);
        $client->method('post')->willReturn($this->buildBatchResponse($data));

        $scraper = new PermissionsScraper($client);
        $result = $scraper->getPermissions('com.test.app');

        $this->assertCount(4, $result);
        $this->assertEquals('find accounts on device', $result[0]['permission']);
        $this->assertEquals('Identity', $result[0]['type']);
        $this->assertEquals('read your contacts', $result[2]['permission']);
        $this->assertEquals('Contacts', $result[2]['type']);
        $this->assertEquals('full network access', $result[3]['permission']);
        $this->assertEquals('Other', $result[3]['type']);
    }

    public function testGetPermissionsReturnsShort(): void
    {
        $data = [
            // COMMON (index 0)
            [
                ['Identity', null, [[null, 'find accounts']]],
                ['Contacts', null, [[null, 'read contacts']]],
                ['Camera', null, [[null, 'take pictures']]],
            ],
        ];

        $client = $this->createMock(HttpClient::class);
        $client->method('post')->willReturn($this->buildBatchResponse($data));

        $scraper = new PermissionsScraper($client);
        $result = $scraper->getPermissions('com.test.app', short: true);

        $this->assertCount(3, $result);
        $this->assertEquals('Identity', $result[0]);
        $this->assertEquals('Contacts', $result[1]);
        $this->assertEquals('Camera', $result[2]);
    }

    public function testGetPermissionsReturnsEmptyOnNoData(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->method('post')->willReturn(')]}\'invalid');

        $scraper = new PermissionsScraper($client);
        $result = $scraper->getPermissions('com.test.app');

        $this->assertEmpty($result);
    }

    public function testGetPermissionsPassesLanguageParams(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->expects($this->once())
            ->method('post')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('hl=tr'),
                    $this->stringContains('gl=tr')
                ),
                $this->anything()
            )
            ->willReturn(')]}\'[]');

        $scraper = new PermissionsScraper($client);
        $scraper->getPermissions('com.test.app', lang: 'tr', country: 'tr');
    }
}
