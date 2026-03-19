<?php

declare(strict_types=1);

namespace GooglePlayScraper\Enum;

enum Sort: int
{
    case HELPFULNESS = 1;
    case NEWEST = 2;
    case RATING = 3;
}
