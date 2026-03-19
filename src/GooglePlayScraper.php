<?php

declare(strict_types=1);

namespace GooglePlayScraper;

use GooglePlayScraper\Enum\Category;
use GooglePlayScraper\Enum\Collection;
use GooglePlayScraper\Enum\Price;
use GooglePlayScraper\Enum\Sort;
use GooglePlayScraper\Scraper\AppScraper;
use GooglePlayScraper\Scraper\CategoriesScraper;
use GooglePlayScraper\Scraper\DataSafetyScraper;
use GooglePlayScraper\Scraper\DeveloperScraper;
use GooglePlayScraper\Scraper\ListScraper;
use GooglePlayScraper\Scraper\PermissionsScraper;
use GooglePlayScraper\Scraper\ReviewsScraper;
use GooglePlayScraper\Scraper\SearchScraper;
use GooglePlayScraper\Scraper\SimilarScraper;
use GooglePlayScraper\Scraper\SuggestScraper;
use GooglePlayScraper\Utils\HttpClient;

class GooglePlayScraper
{
    private HttpClient $client;
    private AppScraper $appScraper;
    private SearchScraper $searchScraper;
    private ListScraper $listScraper;
    private DeveloperScraper $developerScraper;
    private SuggestScraper $suggestScraper;
    private ReviewsScraper $reviewsScraper;
    private SimilarScraper $similarScraper;
    private PermissionsScraper $permissionsScraper;
    private DataSafetyScraper $dataSafetyScraper;
    private CategoriesScraper $categoriesScraper;

    public function __construct(
        ?string $proxy = null,
        int $throttleLimit = 0,
        int $throttleInterval = 1000,
        array $guzzleOptions = [],
    ) {
        $this->client = new HttpClient($proxy, $throttleLimit, $throttleInterval, $guzzleOptions);
        $this->appScraper = new AppScraper($this->client);
        $this->searchScraper = new SearchScraper($this->client, $this->appScraper);
        $this->listScraper = new ListScraper($this->client, $this->appScraper);
        $this->developerScraper = new DeveloperScraper($this->client, $this->appScraper);
        $this->suggestScraper = new SuggestScraper($this->client);
        $this->reviewsScraper = new ReviewsScraper($this->client);
        $this->similarScraper = new SimilarScraper($this->client, $this->appScraper);
        $this->permissionsScraper = new PermissionsScraper($this->client);
        $this->dataSafetyScraper = new DataSafetyScraper($this->client);
        $this->categoriesScraper = new CategoriesScraper($this->client);
    }

    /**
     * Get detailed information about a specific app.
     */
    public function app(string $appId, string $lang = 'en', string $country = 'us'): array
    {
        return $this->appScraper->getApp($appId, $lang, $country);
    }

    /**
     * Search for apps on Google Play.
     */
    public function search(
        string $term,
        string $lang = 'en',
        string $country = 'us',
        int $num = 20,
        Price $price = Price::ALL,
        bool $fullDetail = false,
    ): array {
        return $this->searchScraper->search($term, $lang, $country, $num, $price, $fullDetail);
    }

    /**
     * Retrieve a list of apps from a specific collection and category.
     */
    public function list(
        Collection $collection = Collection::TOP_FREE,
        Category $category = Category::APPLICATION,
        int $num = 500,
        string $lang = 'en',
        string $country = 'us',
        bool $fullDetail = false,
    ): array {
        return $this->listScraper->list($collection, $category, $num, $lang, $country, $fullDetail);
    }

    /**
     * Get apps from a specific developer.
     */
    public function developer(
        string $devId,
        string $lang = 'en',
        string $country = 'us',
        int $num = 60,
        bool $fullDetail = false,
    ): array {
        return $this->developerScraper->getApps($devId, $lang, $country, $num, $fullDetail);
    }

    /**
     * Get search suggestions for a term.
     */
    public function suggest(string $term, string $lang = 'en', string $country = 'us'): array
    {
        return $this->suggestScraper->suggest($term, $lang, $country);
    }

    /**
     * Get reviews for an app.
     *
     * @return array{data: array, nextPaginationToken: ?string}
     */
    public function reviews(
        string $appId,
        Sort $sort = Sort::NEWEST,
        string $lang = 'en',
        string $country = 'us',
        int $num = 150,
        bool $paginate = false,
        ?string $nextPaginationToken = null,
    ): array {
        return $this->reviewsScraper->getReviews($appId, $sort, $lang, $country, $num, $paginate, $nextPaginationToken);
    }

    /**
     * Get similar apps for a specific app.
     */
    public function similar(
        string $appId,
        string $lang = 'en',
        string $country = 'us',
        bool $fullDetail = false,
    ): array {
        return $this->similarScraper->similar($appId, $lang, $country, $fullDetail);
    }

    /**
     * Get permissions requested by an app.
     */
    public function permissions(
        string $appId,
        string $lang = 'en',
        string $country = 'us',
        bool $short = false,
    ): array {
        return $this->permissionsScraper->getPermissions($appId, $lang, $country, $short);
    }

    /**
     * Get data safety information for an app.
     */
    public function dataSafety(string $appId, string $lang = 'en'): array
    {
        return $this->dataSafetyScraper->getDataSafety($appId, $lang);
    }

    /**
     * Get available categories from Google Play Store.
     */
    public function categories(): array
    {
        return $this->categoriesScraper->getCategories();
    }

    /**
     * Get the underlying HTTP client for advanced configuration.
     */
    public function getHttpClient(): HttpClient
    {
        return $this->client;
    }

    /**
     * Set proxy for future requests.
     */
    public function setProxy(?string $proxy): void
    {
        $this->client->setProxy($proxy);
    }
}
