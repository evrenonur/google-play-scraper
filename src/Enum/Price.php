<?php

declare(strict_types=1);

namespace GooglePlayScraper\Enum;

enum Price: string
{
    case ALL = 'all';
    case FREE = 'free';
    case PAID = 'paid';
}
