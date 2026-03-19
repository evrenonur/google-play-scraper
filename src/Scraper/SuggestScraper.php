<?php

declare(strict_types=1);

namespace GooglePlayScraper\Scraper;

use GooglePlayScraper\Utils\HttpClient;

class SuggestScraper
{
    private const BASE_URL = 'https://play.google.com';

    public function __construct(private readonly HttpClient $client) {}

    public function suggest(string $term, string $lang = 'en', string $country = 'us'): array
    {
        $url = self::BASE_URL . "/_/PlayStoreUi/data/batchexecute?rpcids=IJ4APc&f.sid=-697906427155521722&bl=boq_playuiserver_20190903.08_p0&hl={$lang}&gl={$country}&authuser&soc-app=121&soc-platform=1&soc-device=1&_reqid=1065213";

        $encodedTerm = urlencode($term);
        $body = "f.req=%5B%5B%5B%22IJ4APc%22%2C%22%5B%5Bnull%2C%5B%5C%22{$encodedTerm}%5C%22%5D%2C%5B10%5D%2C%5B2%5D%2C4%5D%5D%22%5D%5D%5D";

        $html = $this->client->post($url, $body);
        $input = json_decode(substr($html, 5), true);

        if (!$input || !isset($input[0][2])) {
            return [];
        }

        $data = json_decode($input[0][2], true);
        if ($data === null) {
            return [];
        }

        return array_map(fn($s) => $s[0], $data[0][0] ?? []);
    }
}
