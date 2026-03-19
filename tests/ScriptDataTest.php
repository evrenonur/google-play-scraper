<?php

declare(strict_types=1);

namespace GooglePlayScraper\Tests;

use GooglePlayScraper\Utils\ScriptData;
use PHPUnit\Framework\TestCase;

class ScriptDataTest extends TestCase
{
    public function testParseExtractsDataFromScript(): void
    {
        $html = <<<'HTML'
<script nonce="abc">AF_initDataCallback({key: 'ds:5', hash: '123', data:["hello","world"], sideChannel: {}});</script>
HTML;

        $result = ScriptData::parse($html);

        $this->assertArrayHasKey('ds:5', $result);
        $this->assertEquals(['hello', 'world'], $result['ds:5']);
    }

    public function testParseHandlesMultipleScriptTags(): void
    {
        $html = <<<'HTML'
<script nonce="a">AF_initDataCallback({key: 'ds:3', hash: '1', data:[1,2,3], sideChannel: {}});</script>
<script nonce="b">AF_initDataCallback({key: 'ds:5', hash: '2', data:{"name":"test"}, sideChannel: {}});</script>
HTML;

        $result = ScriptData::parse($html);

        $this->assertArrayHasKey('ds:3', $result);
        $this->assertArrayHasKey('ds:5', $result);
        $this->assertEquals([1, 2, 3], $result['ds:3']);
        $this->assertEquals(['name' => 'test'], $result['ds:5']);
    }

    public function testParseReturnsEmptyArrayForNoScripts(): void
    {
        $html = '<html><body>No scripts here</body></html>';
        $result = ScriptData::parse($html);

        $this->assertEmpty($result);
    }

    public function testGetPathReturnsValueAtPath(): void
    {
        $data = [
            'level1' => [
                'level2' => [
                    'level3' => 'found',
                ],
            ],
        ];

        $this->assertEquals('found', ScriptData::getPath($data, ['level1', 'level2', 'level3']));
    }

    public function testGetPathReturnsNullForMissingPath(): void
    {
        $data = ['a' => ['b' => 'c']];

        $this->assertNull(ScriptData::getPath($data, ['a', 'x', 'y']));
    }

    public function testGetPathWithNumericIndices(): void
    {
        $data = [
            ['foo', 'bar'],
            ['baz', 'qux'],
        ];

        $this->assertEquals('bar', ScriptData::getPath($data, [0, 1]));
        $this->assertEquals('baz', ScriptData::getPath($data, [1, 0]));
    }

    public function testExtractFieldsWithSimplePaths(): void
    {
        $data = [
            'name' => 'Test App',
            'details' => [
                'version' => '1.0',
                'size' => '5MB',
            ],
        ];

        $mappings = [
            'appName' => ['name'],
            'version' => ['details', 'version'],
        ];

        $result = ScriptData::extractFields($mappings, $data);

        $this->assertEquals('Test App', $result['appName']);
        $this->assertEquals('1.0', $result['version']);
    }

    public function testExtractFieldsWithFunTransform(): void
    {
        $data = [
            'price' => 5000000,
        ];

        $mappings = [
            'price' => [
                'path' => ['price'],
                'fun' => fn($val) => $val / 1000000,
            ],
        ];

        $result = ScriptData::extractFields($mappings, $data);

        $this->assertEquals(5.0, $result['price']);
    }

    public function testExtractFieldsWithMissingData(): void
    {
        $data = ['a' => 'b'];

        $mappings = [
            'missing' => ['x', 'y', 'z'],
        ];

        $result = ScriptData::extractFields($mappings, $data);

        $this->assertNull($result['missing']);
    }
}
