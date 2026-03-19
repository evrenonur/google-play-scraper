<?php

declare(strict_types=1);

namespace GooglePlayScraper\Enum;

enum Collection: string
{
    case TOP_FREE = 'TOP_FREE';
    case TOP_PAID = 'TOP_PAID';
    case GROSSING = 'GROSSING';
}
