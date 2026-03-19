<?php

declare(strict_types=1);

namespace GooglePlayScraper\Tests;

use GooglePlayScraper\Enum\Category;
use GooglePlayScraper\Enum\Collection;
use GooglePlayScraper\Enum\Price;
use GooglePlayScraper\Enum\Sort;
use GooglePlayScraper\Enum\Age;
use GooglePlayScraper\Enum\Permission;
use PHPUnit\Framework\TestCase;

class EnumTest extends TestCase
{
    public function testCategoryValues(): void
    {
        $this->assertEquals('APPLICATION', Category::APPLICATION->value);
        $this->assertEquals('GAME', Category::GAME->value);
        $this->assertEquals('FINANCE', Category::FINANCE->value);
        $this->assertEquals('GAME_ACTION', Category::GAME_ACTION->value);
    }

    public function testCollectionValues(): void
    {
        $this->assertEquals('TOP_FREE', Collection::TOP_FREE->value);
        $this->assertEquals('TOP_PAID', Collection::TOP_PAID->value);
        $this->assertEquals('GROSSING', Collection::GROSSING->value);
    }

    public function testSortValues(): void
    {
        $this->assertEquals(1, Sort::HELPFULNESS->value);
        $this->assertEquals(2, Sort::NEWEST->value);
        $this->assertEquals(3, Sort::RATING->value);
    }

    public function testPriceValues(): void
    {
        $this->assertEquals('all', Price::ALL->value);
        $this->assertEquals('free', Price::FREE->value);
        $this->assertEquals('paid', Price::PAID->value);
    }

    public function testAgeValues(): void
    {
        $this->assertEquals('AGE_RANGE1', Age::FIVE_UNDER->value);
        $this->assertEquals('AGE_RANGE2', Age::SIX_EIGHT->value);
        $this->assertEquals('AGE_RANGE3', Age::NINE_UP->value);
    }

    public function testPermissionValues(): void
    {
        $this->assertEquals(0, Permission::COMMON->value);
        $this->assertEquals(1, Permission::OTHER->value);
    }

    public function testCategoryFromString(): void
    {
        $category = Category::from('GAME');
        $this->assertEquals(Category::GAME, $category);
    }

    public function testCollectionFromString(): void
    {
        $collection = Collection::from('TOP_FREE');
        $this->assertEquals(Collection::TOP_FREE, $collection);
    }

    public function testInvalidCategoryThrows(): void
    {
        $this->expectException(\ValueError::class);
        Category::from('INVALID_CATEGORY');
    }
}
