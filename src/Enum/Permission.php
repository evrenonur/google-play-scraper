<?php

declare(strict_types=1);

namespace GooglePlayScraper\Enum;

enum Permission: int
{
    case COMMON = 0;
    case OTHER = 1;
}
