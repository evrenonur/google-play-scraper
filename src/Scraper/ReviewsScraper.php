<?php

declare(strict_types=1);

namespace GooglePlayScraper\Scraper;

use GooglePlayScraper\Enum\Sort;
use GooglePlayScraper\Utils\HttpClient;
use GooglePlayScraper\Utils\ScriptData;

class ReviewsScraper
{
    private const BASE_URL = 'https://play.google.com';

    public function __construct(private readonly HttpClient $client) {}

    /**
     * @return array{data: array, nextPaginationToken: ?string}
     */
    public function getReviews(
        string $appId,
        Sort $sort = Sort::NEWEST,
        string $lang = 'en',
        string $country = 'us',
        int $num = 150,
        bool $paginate = false,
        ?string $nextPaginationToken = null,
    ): array {
        $requestType = $nextPaginationToken === null ? 'initial' : 'paginated';
        $token = $nextPaginationToken ?? '%token%';

        return $this->makeReviewsRequest($appId, $sort, $lang, $country, $num, $paginate, $requestType, [], $token);
    }

    private function makeReviewsRequest(
        string $appId,
        Sort $sort,
        string $lang,
        string $country,
        int $num,
        bool $paginate,
        string $requestType,
        array $savedReviews,
        string $nextToken,
    ): array {
        $body = $this->getBody($appId, $sort->value, $requestType, $nextToken);
        $url = self::BASE_URL . "/_/PlayStoreUi/data/batchexecute?rpcids=qnKhOb&f.sid=-697906427155521722&bl=boq_playuiserver_20190903.08_p0&hl={$lang}&gl={$country}&authuser&soc-app=121&soc-platform=1&soc-device=1&_reqid=1065213";

        $html = $this->client->post($url, $body);
        $input = json_decode(substr($html, 5), true);

        if (!$input || !isset($input[0][2])) {
            return $this->formatResponse($savedReviews, null, $num);
        }

        $data = json_decode($input[0][2], true);
        if ($data === null) {
            return $this->formatResponse($savedReviews, null, $num);
        }

        return $this->processReviews($data, $appId, $sort, $lang, $country, $num, $paginate, $savedReviews);
    }

    private function processReviews(
        array $data,
        string $appId,
        Sort $sort,
        string $lang,
        string $country,
        int $num,
        bool $paginate,
        array $savedReviews,
    ): array {
        if (empty($data)) {
            return $this->formatResponse($savedReviews, null, $num);
        }

        $reviewsList = $data[0] ?? [];
        $token = ScriptData::getPath($data, [1, 1]);

        $reviews = array_map(
            fn($item) => ScriptData::extractFields($this->getReviewMappings($appId), $item),
            $reviewsList
        );

        $allReviews = array_merge($savedReviews, $reviews);

        if (!$paginate && $token && count($allReviews) < $num) {
            return $this->makeReviewsRequest($appId, $sort, $lang, $country, $num, $paginate, 'paginated', $allReviews, $token);
        }

        return $this->formatResponse($allReviews, $token, $num);
    }

    private function formatResponse(array $reviews, ?string $token, int $num): array
    {
        return [
            'data' => array_slice($reviews, 0, $num),
            'nextPaginationToken' => $token,
        ];
    }

    private function getBody(string $appId, int $sort, string $requestType, string $token): string
    {
        if ($requestType === 'initial') {
            return "f.req=%5B%5B%5B%22UsvDTd%22%2C%22%5Bnull%2Cnull%2C%5B2%2C{$sort}%2C%5B150%2Cnull%2Cnull%5D%2Cnull%2C%5B%5D%5D%2C%5B%5C%22{$appId}%5C%22%2C7%5D%5D%22%2Cnull%2C%22generic%22%5D%5D%5D";
        }

        return "f.req=%5B%5B%5B%22UsvDTd%22%2C%22%5Bnull%2Cnull%2C%5B2%2C{$sort}%2C%5B150%2Cnull%2C%5C%22{$token}%5C%22%5D%2Cnull%2C%5B%5D%5D%2C%5B%5C%22{$appId}%5C%22%2C7%5D%5D%22%2Cnull%2C%22generic%22%5D%5D%5D";
    }

    private function getReviewMappings(string $appId): array
    {
        return [
            'id' => [0],
            'userName' => [1, 0],
            'userImage' => [1, 1, 3, 2],
            'date' => [
                'path' => [5],
                'fun' => fn($dateArray) => self::generateDate($dateArray),
            ],
            'score' => [2],
            'scoreText' => [
                'path' => [2],
                'fun' => fn($score) => (string) ($score ?? ''),
            ],
            'url' => [
                'path' => [0],
                'fun' => fn($reviewId) => self::BASE_URL . "/store/apps/details?id={$appId}&reviewId={$reviewId}",
            ],
            'text' => [4],
            'replyDate' => [
                'path' => [7, 2],
                'fun' => fn($dateArray) => self::generateDate($dateArray),
            ],
            'replyText' => [
                'path' => [7, 1],
                'fun' => fn($text) => $text ?: null,
            ],
            'version' => [
                'path' => [10],
                'fun' => fn($version) => $version ?: null,
            ],
            'thumbsUp' => [6],
            'criterias' => [
                'path' => [12, 0],
                'fun' => fn($criterias) => is_array($criterias)
                    ? array_map(fn($c) => [
                        'criteria' => $c[0] ?? null,
                        'rating' => isset($c[1][0]) ? $c[1][0] : null,
                    ], $criterias)
                    : [],
            ],
        ];
    }

    private static function generateDate(mixed $dateArray): ?string
    {
        if (!is_array($dateArray)) {
            return null;
        }

        $seconds = $dateArray[0] ?? 0;
        $millisLastDigits = (string) ($dateArray[1] ?? '000');
        $totalMs = $seconds . substr($millisLastDigits, 0, 3);

        try {
            $timestamp = (int) $totalMs / 1000;
            $date = new \DateTimeImmutable('@' . (int) $timestamp);
            return $date->format('Y-m-d\TH:i:s.v\Z');
        } catch (\Exception) {
            return null;
        }
    }
}
