<?php

declare(strict_types=1);

namespace GooglePlayScraper\Scraper;

use GooglePlayScraper\Enum\Category;
use GooglePlayScraper\Enum\Collection;
use GooglePlayScraper\Utils\HttpClient;
use GooglePlayScraper\Utils\ScriptData;

class ListScraper
{
    private const BASE_URL = 'https://play.google.com';

    private const CLUSTER_NAMES = [
        'TOP_FREE' => 'topselling_free',
        'TOP_PAID' => 'topselling_paid',
        'GROSSING' => 'topgrossing',
    ];

    public function __construct(
        private readonly HttpClient $client,
        private readonly AppScraper $appScraper,
    ) {}

    public function list(
        Collection $collection = Collection::TOP_FREE,
        Category $category = Category::APPLICATION,
        int $num = 500,
        string $lang = 'en',
        string $country = 'us',
        bool $fullDetail = false,
    ): array {
        $clusterName = self::CLUSTER_NAMES[$collection->value];
        $body = $this->getBody($num, $clusterName, $category->value);

        $queryString = http_build_query(['hl' => $lang, 'gl' => $country]);
        $url = self::BASE_URL . "/_/PlayStoreUi/data/batchexecute?rpcids=vyAe2&source-path=%2Fstore%2Fapps&f.sid=-4178618388443751758&bl=boq_playuiserver_20220612.08_p0&authuser=0&soc-app=121&soc-platform=1&soc-device=1&_reqid=82003&rt=c&{$queryString}";

        $html = $this->client->post($url, $body);
        $lines = explode("\n", $html);

        if (!isset($lines[3])) {
            return [];
        }

        $input = json_decode($lines[3], true);
        if (!$input || !isset($input[0][2])) {
            return [];
        }

        $data = json_decode($input[0][2], true);
        if ($data === null) {
            return [];
        }

        $appsPath = [0, 1, 0, 28, 0];
        $appsList = ScriptData::getPath($data, $appsPath) ?? [];

        $apps = array_map(fn($item) => ScriptData::extractFields($this->getAppMappings(), $item), $appsList);

        if ($fullDetail) {
            $apps = array_map(
                fn($app) => $this->appScraper->getApp($app['appId'], $lang, $country),
                $apps
            );
        }

        return array_slice($apps, 0, $num);
    }

    private function getBody(int $num, string $collection, string $category): string
    {
        return "f.req=%5B%5B%5B%22vyAe2%22%2C%22%5B%5Bnull%2C%5B%5B8%2C%5B20%2C{$num}%5D%5D%2Ctrue%2Cnull%2C%5B64%2C1%2C195%2C71%2C8%2C72%2C9%2C10%2C11%2C139%2C12%2C16%2C145%2C148%2C150%2C151%2C152%2C27%2C30%2C31%2C96%2C32%2C34%2C163%2C100%2C165%2C104%2C169%2C108%2C110%2C113%2C55%2C56%2C57%2C122%5D%5D%2C%5B2%2C%5C%22{$collection}%5C%22%2C%5C%22{$category}%5C%22%5D%5D%5D%22%2Cnull%2C%22generic%22%5D%5D%5D&";
    }

    private function getAppMappings(): array
    {
        return [
            'title' => [0, 3],
            'appId' => [0, 0, 0],
            'url' => [
                'path' => [0, 10, 4, 2],
                'fun' => fn($path) => $path ? self::BASE_URL . $path : null,
            ],
            'icon' => [0, 1, 3, 2],
            'developer' => [0, 14],
            'currency' => [0, 8, 1, 0, 1],
            'price' => [
                'path' => [0, 8, 1, 0, 0],
                'fun' => fn($price) => $price !== null ? $price / 1000000 : 0,
            ],
            'free' => [
                'path' => [0, 8, 1, 0, 0],
                'fun' => fn($price) => $price === 0,
            ],
            'summary' => [0, 13, 1],
            'scoreText' => [0, 4, 0],
            'score' => [0, 4, 1],
        ];
    }
}
