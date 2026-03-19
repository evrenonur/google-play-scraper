<?php

declare(strict_types=1);

namespace GooglePlayScraper\Tests;

use GooglePlayScraper\Utils\MappingHelper;
use PHPUnit\Framework\TestCase;

class MappingHelperTest extends TestCase
{
    public function testDescriptionTextStripsHtml(): void
    {
        $html = '<b>Bold</b> text with <br>line break and <a href="#">link</a>';
        $result = MappingHelper::descriptionText($html);

        $this->assertStringNotContainsString('<b>', $result);
        $this->assertStringNotContainsString('<a', $result);
        $this->assertStringContainsString('Bold', $result);
        $this->assertStringContainsString('link', $result);
    }

    public function testDescriptionTextConvertsBreaks(): void
    {
        $html = 'Line one<br>Line two';
        $result = MappingHelper::descriptionText($html);

        $this->assertStringContainsString("Line one\r\n", $result);
    }

    public function testDescriptionTextReturnsNullForNull(): void
    {
        $this->assertNull(MappingHelper::descriptionText(null));
    }

    public function testPriceTextReturnsFreeForNull(): void
    {
        $this->assertEquals('Free', MappingHelper::priceText(null));
    }

    public function testPriceTextReturnsPrice(): void
    {
        $this->assertEquals('$1.99', MappingHelper::priceText('$1.99'));
    }

    public function testNormalizeAndroidVersionReturnsVaryForNull(): void
    {
        $this->assertEquals('VARY', MappingHelper::normalizeAndroidVersion(null));
    }

    public function testNormalizeAndroidVersionExtractsNumber(): void
    {
        $this->assertEquals('5.0', MappingHelper::normalizeAndroidVersion('5.0 and up'));
    }

    public function testNormalizeAndroidVersionReturnsVaryForText(): void
    {
        $this->assertEquals('VARY', MappingHelper::normalizeAndroidVersion('Varies with device'));
    }

    public function testBuildHistogramWithData(): void
    {
        $container = [
            null, // index 0
            [null, 100],   // 1 star
            [null, 200],   // 2 star
            [null, 300],   // 3 star
            [null, 400],   // 4 star
            [null, 500],   // 5 star
        ];

        $result = MappingHelper::buildHistogram($container);

        $this->assertEquals(100, $result[1]);
        $this->assertEquals(200, $result[2]);
        $this->assertEquals(500, $result[5]);
    }

    public function testBuildHistogramWithNull(): void
    {
        $result = MappingHelper::buildHistogram(null);

        $this->assertEquals([1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0], $result);
    }
}
