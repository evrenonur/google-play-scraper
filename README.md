# Google Play Scraper for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/evrenonur/google-play-scraper)](https://packagist.org/packages/evrenonur/google-play-scraper)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/evrenonur/google-play-scraper)](https://packagist.org/packages/evrenonur/google-play-scraper)
[![Tests](https://github.com/evrenonur/google-play-scraper/actions/workflows/tests.yml/badge.svg)](https://github.com/evrenonur/google-play-scraper/actions/workflows/tests.yml)

## Google Play Scraper: Easily Scrape Google Play Store Data

Scrape detailed app information, search results, reviews, and more from the Google Play Store with a single PHP library.

> This package is a PHP port of [facundoolano/google-play-scraper](https://github.com/facundoolano/google-play-scraper) (Node.js).

## Features

+ **10 Scraping Methods:** app, search, list, developer, suggest, reviews, similar, permissions, dataSafety, categories
+ **Proxy Support:** HTTP, HTTPS, SOCKS5 proxies with authentication
+ **Throttling:** Built-in request rate limiting
+ **Pagination:** Token-based review pagination
+ **Modern PHP:** PHP 8.2+, Enums, named arguments, readonly properties
+ **Customizable HTTP:** Full HTTP client control with custom Guzzle options

## Requirements

- PHP >= 8.2
- Composer
- ext-json
- ext-mbstring

## Installation

Install via Composer:

```bash
composer require evrenonur/google-play-scraper
```

## Usage

Available methods:

- [app](#app): Retrieves the full detail of an application.
- [list](#list): Retrieves a list of applications from one of the collections at Google Play.
- [search](#search): Retrieves a list of apps that results of searching by the given term.
- [developer](#developer): Returns the list of applications by the given developer name.
- [suggest](#suggest): Given a string returns up to five suggestions to complete a search query term.
- [reviews](#reviews): Retrieves a page of reviews for a specific application.
- [similar](#similar): Returns a list of similar apps to the one specified.
- [permissions](#permissions): Returns the list of permissions an app has access to.
- [dataSafety](#datasafety): Returns the data safety information of an app.
- [categories](#categories): Retrieve a full list of categories present from dropdown menu on Google Play.

### app

Retrieves the full detail of an application. Parameters:

- `appId`: the Google Play id of the application (the `?id=` parameter on the url).
- `lang` (optional, defaults to `'en'`): the two letter language code in which to fetch the app page.
- `country` (optional, defaults to `'us'`): the two letter country code used to retrieve the applications.

Example:

```php
use GooglePlayScraper\GooglePlayScraper;

$scraper = new GooglePlayScraper();
$app = $scraper->app('com.google.android.apps.translate');
```

Results:

```php
[
    'title' => 'Google Translate',
    'description' => 'Translate between 103 languages by typing...',
    'summary' => 'The world is closer than ever with over 100 languages',
    'installs' => '500,000,000+',
    'minInstalls' => 500000000,
    'maxInstalls' => 898626813,
    'score' => 4.482483,
    'scoreText' => '4.5',
    'ratings' => 6811669,
    'reviews' => 1614618,
    'histogram' => ['1' => 370042, '2' => 145558, '3' => 375720, '4' => 856865, '5' => 5063481],
    'price' => 0,
    'free' => true,
    'currency' => 'USD',
    'offersIAP' => false,
    'developer' => 'Google LLC',
    'developerId' => '5700313618786177705',
    'developerEmail' => 'translate-android-support@google.com',
    'developerWebsite' => 'http://support.google.com/translate',
    'privacyPolicy' => 'http://www.google.com/policies/privacy/',
    'genre' => 'Tools',
    'genreId' => 'TOOLS',
    'categories' => [
        ['name' => 'Tools', 'id' => 'TOOLS'],
    ],
    'icon' => 'https://lh3.googleusercontent.com/...',
    'headerImage' => 'https://lh3.googleusercontent.com/...',
    'screenshots' => ['https://lh3.googleusercontent.com/...'],
    'contentRating' => 'Everyone',
    'adSupported' => false,
    'updated' => 1576868577000,
    'version' => 'Varies with device',
    'appId' => 'com.google.android.apps.translate',
    'url' => 'https://play.google.com/store/apps/details?id=com.google.android.apps.translate&hl=en&gl=us',
]
```

### list

Retrieves a list of applications from one of the collections at Google Play. Parameters:

- `collection` (optional, defaults to `Collection::TOP_FREE`): the Google Play collection to retrieve. Options: `TOP_FREE`, `TOP_PAID`, `GROSSING`.
- `category` (optional, defaults to `Category::APPLICATION`): the category to filter by. Use [Category enum](#category) values.
- `num` (optional, defaults to `500`): the number of apps to retrieve.
- `lang` (optional, defaults to `'en'`): the two letter language code.
- `country` (optional, defaults to `'us'`): the two letter country code.
- `fullDetail` (optional, defaults to `false`): if `true`, an extra request will be made for every resulting app to fetch its full detail.

Example:

```php
use GooglePlayScraper\GooglePlayScraper;
use GooglePlayScraper\Enum\Collection;
use GooglePlayScraper\Enum\Category;

$scraper = new GooglePlayScraper();
$apps = $scraper->list(
    collection: Collection::TOP_FREE,
    category: Category::GAME_ACTION,
    num: 2,
);
```

Results:

```php
[
    [
        'url' => 'https://play.google.com/store/apps/details?id=com.example.game',
        'appId' => 'com.example.game',
        'title' => 'Example Game',
        'summary' => 'An amazing game!',
        'developer' => 'Example Dev',
        'developerId' => '1234567890',
        'icon' => 'https://lh3.googleusercontent.com/...',
        'score' => 4.2,
        'scoreText' => '4.2',
        'price' => 0,
        'free' => true,
    ],
    // ...
]
```

### search

Retrieves a list of apps that results of searching by the given term. Parameters:

- `term`: the term to search by.
- `num` (optional, defaults to `20`, max `250`): the number of apps to retrieve.
- `lang` (optional, defaults to `'en'`): the two letter language code.
- `country` (optional, defaults to `'us'`): the two letter country code.
- `fullDetail` (optional, defaults to `false`): if `true`, an extra request will be made for every resulting app to fetch its full detail.
- `price` (optional, defaults to `Price::ALL`): price filter.
  - `Price::ALL`: free and paid
  - `Price::FREE`: free apps only
  - `Price::PAID`: paid apps only

Example:

```php
use GooglePlayScraper\GooglePlayScraper;
use GooglePlayScraper\Enum\Price;

$scraper = new GooglePlayScraper();
$results = $scraper->search(
    term: 'whatsapp',
    num: 5,
    price: Price::FREE,
);
```

Results:

```php
[
    [
        'url' => 'https://play.google.com/store/apps/details?id=com.whatsapp',
        'appId' => 'com.whatsapp',
        'title' => 'WhatsApp Messenger',
        'summary' => 'Simple. Reliable. Private.',
        'developer' => 'WhatsApp LLC',
        'developerId' => 'WhatsApp+LLC',
        'icon' => 'https://lh3.googleusercontent.com/...',
        'score' => 4.2,
        'scoreText' => '4.2',
        'price' => 0,
        'free' => true,
    ],
    // ...
]
```

### developer

Returns the list of applications by the given developer name or ID. Parameters:

- `devId`: the developer name or the numeric developer ID.
- `lang` (optional, defaults to `'en'`): the two letter language code.
- `country` (optional, defaults to `'us'`): the two letter country code.
- `num` (optional, defaults to `60`): the number of apps to retrieve.
- `fullDetail` (optional, defaults to `false`): if `true`, an extra request will be made for every resulting app to fetch its full detail.

Example:

```php
$scraper = new GooglePlayScraper();

// By developer name
$apps = $scraper->developer(devId: 'Google LLC');

// By numeric ID
$apps = $scraper->developer(devId: '5700313618786177705');
```

Results:

```php
[
    [
        'url' => 'https://play.google.com/store/apps/details?id=com.google.android.apps.translate',
        'appId' => 'com.google.android.apps.translate',
        'title' => 'Google Translate',
        'developer' => 'Google LLC',
        'icon' => 'https://lh3.googleusercontent.com/...',
        'score' => 4.5,
        'scoreText' => '4.5',
        'price' => 0,
        'free' => true,
    ],
    // ...
]
```

### suggest

Given a string returns up to five suggestions to complete a search query term. Parameters:

- `term`: the term to get suggestions for.
- `lang` (optional, defaults to `'en'`): the two letter language code.
- `country` (optional, defaults to `'us'`): the two letter country code.

Example:

```php
$scraper = new GooglePlayScraper();
$suggestions = $scraper->suggest('inst');
```

Results:

```php
['instagram', 'instagram lite', 'instreet', 'instant apps', 'instapay']
```

### reviews

Retrieves a page of reviews for a specific application. Parameters:

- `appId`: the Google Play id of the application.
- `sort` (optional, defaults to `Sort::NEWEST`): the ordering of the reviews.
  - `Sort::HELPFULNESS`: most helpful first
  - `Sort::NEWEST`: newest first
  - `Sort::RATING`: highest rated first
- `lang` (optional, defaults to `'en'`): the two letter language code.
- `country` (optional, defaults to `'us'`): the two letter country code.
- `num` (optional, defaults to `150`): the number of reviews to retrieve.
- `paginate` (optional, defaults to `false`): enable pagination.
- `nextPaginationToken` (optional, defaults to `null`): token for the next page.

Example:

```php
use GooglePlayScraper\GooglePlayScraper;
use GooglePlayScraper\Enum\Sort;

$scraper = new GooglePlayScraper();

// Basic usage
$result = $scraper->reviews('com.whatsapp', sort: Sort::NEWEST, num: 10);

// Pagination
$page1 = $scraper->reviews('com.whatsapp', num: 10, paginate: true);
$page2 = $scraper->reviews(
    'com.whatsapp',
    num: 10,
    paginate: true,
    nextPaginationToken: $page1['nextPaginationToken'],
);
```

Results:

```php
[
    'data' => [
        [
            'id' => 'gp:AOqpTOF...',
            'userName' => 'John Doe',
            'userImage' => 'https://lh3.googleusercontent.com/...',
            'date' => '2024-01-15T12:00:00.000Z',
            'score' => 5,
            'scoreText' => '5',
            'url' => 'https://play.google.com/store/apps/details?id=com.whatsapp&reviewId=gp:AOqpTOF...',
            'title' => 'Great app',
            'text' => 'Very reliable messaging application.',
            'replyDate' => '2024-01-16T10:00:00.000Z',
            'replyText' => 'Thanks for your feedback!',
            'version' => '2.24.1.6',
            'thumbsUp' => 42,
            'criterias' => [],
        ],
        // ...
    ],
    'nextPaginationToken' => 'CpEBCo4BCi...',
]
```

### similar

Returns a list of similar apps to the one specified. Parameters:

- `appId`: the Google Play id of the application to get similar apps for.
- `lang` (optional, defaults to `'en'`): the two letter language code.
- `country` (optional, defaults to `'us'`): the two letter country code.
- `fullDetail` (optional, defaults to `false`): if `true`, an extra request will be made for every resulting app to fetch its full detail.

Example:

```php
$scraper = new GooglePlayScraper();
$apps = $scraper->similar('com.whatsapp');
```

Results:

```php
[
    [
        'url' => 'https://play.google.com/store/apps/details?id=org.telegram.messenger',
        'appId' => 'org.telegram.messenger',
        'title' => 'Telegram',
        'developer' => 'Telegram FZ-LLC',
        'icon' => 'https://lh3.googleusercontent.com/...',
        'score' => 4.3,
        'scoreText' => '4.3',
        'price' => 0,
        'free' => true,
    ],
    // ...
]
```

### permissions

Returns the list of permissions an app has access to. Parameters:

- `appId`: the Google Play id of the application.
- `lang` (optional, defaults to `'en'`): the two letter language code.
- `country` (optional, defaults to `'us'`): the two letter country code.
- `short` (optional, defaults to `false`): if `true`, returns only the permission group names.

Example:

```php
$scraper = new GooglePlayScraper();

// Detailed permissions
$permissions = $scraper->permissions('com.google.android.apps.translate');

// Short format
$short = $scraper->permissions('com.google.android.apps.translate', short: true);
```

Results (detailed):

```php
[
    ['type' => 'Identity', 'permission' => 'find accounts on the device'],
    ['type' => 'Contacts', 'permission' => 'read your contacts'],
    ['type' => 'Photos/Media/Files', 'permission' => 'read the contents of your USB storage'],
    // ...
]
```

Results (short):

```php
['Identity', 'Contacts', 'Photos/Media/Files', 'Camera', 'Microphone', 'Other']
```

### dataSafety

Returns the data safety information of an app. Parameters:

- `appId`: the Google Play id of the application.
- `lang` (optional, defaults to `'en'`): the two letter language code.

Example:

```php
$scraper = new GooglePlayScraper();
$safety = $scraper->dataSafety('com.google.android.apps.translate');
```

Results:

```php
[
    'sharedData' => [
        [
            'data' => 'App activity',
            'optional' => false,
            'purpose' => 'App functionality, Analytics',
            'type' => 'App interactions',
        ],
        // ...
    ],
    'collectedData' => [
        [
            'data' => 'Personal info',
            'optional' => false,
            'purpose' => 'App functionality',
            'type' => 'Name',
        ],
        // ...
    ],
    'securityPractices' => [
        [
            'practice' => 'Data is encrypted in transit',
            'description' => 'Your data is transferred over a secure connection',
        ],
        // ...
    ],
    'privacyPolicyUrl' => 'http://www.google.com/policies/privacy/',
]
```

### categories

Retrieves a full list of categories present from dropdown menu on Google Play.

Example:

```php
$scraper = new GooglePlayScraper();
$categories = $scraper->categories();
```

Results:

```php
['APPLICATION', 'GAME', 'FAMILY', 'PHOTOGRAPHY', 'ENTERTAINMENT', 'COMMUNICATION', ...]
```

## Configuration

### Proxy Support

```php
// HTTP proxy
$scraper = new GooglePlayScraper(proxy: 'http://proxy.example.com:8080');

// SOCKS5 proxy
$scraper = new GooglePlayScraper(proxy: 'socks5://proxy.example.com:1080');

// Authenticated proxy
$scraper = new GooglePlayScraper(proxy: 'http://user:pass@proxy.example.com:8080');

// Change proxy at runtime
$scraper->setProxy('http://new-proxy.example.com:3128');
$scraper->setProxy(null); // Remove proxy
```

### Throttling

```php
// Max 10 requests per 2 seconds
$scraper = new GooglePlayScraper(
    throttleLimit: 10,
    throttleInterval: 2000,
);
```

### Custom Guzzle Options

```php
$scraper = new GooglePlayScraper(
    guzzleOptions: [
        'timeout' => 60,
        'verify' => false,
        'headers' => ['Accept-Language' => 'tr-TR'],
    ],
);
```

## Enums

### Collection

| Value | Description |
|-------|-------------|
| `Collection::TOP_FREE` | Top free apps |
| `Collection::TOP_PAID` | Top paid apps |
| `Collection::GROSSING` | Top grossing apps |

### Category

53 category values:

| App Categories | Game Categories |
|----------------|-----------------|
| `APPLICATION`, `COMMUNICATION`, `EDUCATION`, `ENTERTAINMENT`, `FINANCE`, `HEALTH_AND_FITNESS`, `LIFESTYLE`, `MUSIC_AND_AUDIO`, `NEWS_AND_MAGAZINES`, `PHOTOGRAPHY`, `PRODUCTIVITY`, `SHOPPING`, `SOCIAL`, `SPORTS`, `TOOLS`, `TRAVEL_AND_LOCAL`, `VIDEO_PLAYERS`, `WEATHER`, ... | `GAME`, `GAME_ACTION`, `GAME_ADVENTURE`, `GAME_ARCADE`, `GAME_BOARD`, `GAME_CARD`, `GAME_CASINO`, `GAME_CASUAL`, `GAME_EDUCATIONAL`, `GAME_MUSIC`, `GAME_PUZZLE`, `GAME_RACING`, `GAME_ROLE_PLAYING`, `GAME_SIMULATION`, `GAME_SPORTS`, `GAME_STRATEGY`, `GAME_TRIVIA`, `GAME_WORD` |

### Sort

| Value | Description |
|-------|-------------|
| `Sort::HELPFULNESS` | Most helpful first |
| `Sort::NEWEST` | Newest first |
| `Sort::RATING` | Highest rated first |

### Price

| Value | Description |
|-------|-------------|
| `Price::ALL` | All apps |
| `Price::FREE` | Free apps only |
| `Price::PAID` | Paid apps only |

### Age

| Value | Description |
|-------|-------------|
| `Age::FIVE_UNDER` | Ages 5 & under |
| `Age::SIX_EIGHT` | Ages 6-8 |
| `Age::NINE_UP` | Ages 9 & up |

## Project Structure

```
src/
├── GooglePlayScraper.php          # Main facade class
├── Enum/
│   ├── Age.php
│   ├── Category.php               # 53 app/game categories
│   ├── Collection.php             # TOP_FREE, TOP_PAID, GROSSING
│   ├── Permission.php
│   ├── Price.php                  # ALL, FREE, PAID
│   └── Sort.php                   # HELPFULNESS, NEWEST, RATING
├── Scraper/
│   ├── AppScraper.php             # App details
│   ├── CategoriesScraper.php      # Category listing
│   ├── DataSafetyScraper.php      # Data safety info
│   ├── DeveloperScraper.php       # Developer apps
│   ├── ListScraper.php            # Collection lists
│   ├── PermissionsScraper.php     # App permissions
│   ├── ReviewsScraper.php         # Reviews with pagination
│   ├── SearchScraper.php          # Search
│   ├── SimilarScraper.php         # Similar apps
│   └── SuggestScraper.php         # Search suggestions
└── Utils/
    ├── HttpClient.php             # Guzzle wrapper (proxy + throttle)
    ├── MappingHelper.php          # Data transformation helpers
    └── ScriptData.php             # HTML script tag parser
```

## Testing

```bash
# Run all unit tests (84 tests)
composer test

# Run with PHPUnit directly
vendor/bin/phpunit

# Integration tests (requires network access)
php tests/integration.php
```

### Test Structure

| Suite | Files | Description |
|-------|-------|-------------|
| **Unit Tests** | `tests/` | Mock-based, no network access required |
| **Scraper Tests** | `tests/Scraper/` | One test file per scraper (10 files) |
| **Utility Tests** | `tests/` | ScriptData, MappingHelper, HttpClient |
| **Enum Tests** | `tests/EnumTest.php` | Category, Collection, Sort, Price, Age |
| **Facade Tests** | `tests/GooglePlayScraperTest.php` | Main facade class |
| **Integration Tests** | `tests/integration.php` | Real API tests |

## Dependencies

| Package | Version | Purpose |
|---------|---------|----------|
| `guzzlehttp/guzzle` | ^7.9 | HTTP client |
| `symfony/dom-crawler` | ^7.2 | HTML parsing |
| `symfony/css-selector` | ^7.2 | CSS selector support |
| `psr/log` | ^3.0 | Logging interface |
| `phpunit/phpunit` | ^12.5 | Testing (dev) |

## License

MIT License. See [LICENSE](LICENSE) for details.

## Reference

This PHP package was developed based on the architecture and workflows of [facundoolano/google-play-scraper](https://github.com/facundoolano/google-play-scraper) (Node.js).
