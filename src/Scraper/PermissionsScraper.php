<?php

declare(strict_types=1);

namespace GooglePlayScraper\Scraper;

use GooglePlayScraper\Enum\Permission as PermissionEnum;
use GooglePlayScraper\Utils\HttpClient;
use GooglePlayScraper\Utils\ScriptData;

class PermissionsScraper
{
    private const BASE_URL = 'https://play.google.com';

    public function __construct(private readonly HttpClient $client) {}

    /**
     * @param bool $short If true, returns only permission names (short format)
     */
    public function getPermissions(
        string $appId,
        string $lang = 'en',
        string $country = 'us',
        bool $short = false,
    ): array {
        $body = "f.req=%5B%5B%5B%22xdSrCf%22%2C%22%5B%5Bnull%2C%5B%5C%22{$appId}%5C%22%2C7%5D%2C%5B%5D%5D%5D%22%2Cnull%2C%221%22%5D%5D%5D";
        $url = self::BASE_URL . "/_/PlayStoreUi/data/batchexecute?rpcids=qnKhOb&f.sid=-697906427155521722&bl=boq_playuiserver_20190903.08_p0&hl={$lang}&gl={$country}&authuser&soc-app=121&soc-platform=1&soc-device=1&_reqid=1065213";

        $html = $this->client->post($url, $body);
        $input = json_decode(substr($html, 5), true);

        if (!$input || !isset($input[0][2])) {
            return [];
        }

        $data = json_decode($input[0][2], true);
        if ($data === null) {
            return [];
        }

        return $short ? $this->processShort($data) : $this->processFull($data);
    }

    private function processShort(array $data): array
    {
        $commonPermissions = $data[PermissionEnum::COMMON->value] ?? null;

        if (!is_array($commonPermissions)) {
            return [];
        }

        $names = [];
        foreach ($commonPermissions as $permission) {
            if (is_array($permission) && !empty($permission)) {
                if (isset($permission[0]) && is_string($permission[0])) {
                    $names[] = $permission[0];
                }
            }
        }

        return $names;
    }

    private function processFull(array $data): array
    {
        $permissions = [];

        foreach ([PermissionEnum::COMMON->value, PermissionEnum::OTHER->value] as $permissionType) {
            if (!isset($data[$permissionType]) || !is_array($data[$permissionType])) {
                continue;
            }

            foreach ($data[$permissionType] as $permissionGroup) {
                if (!is_array($permissionGroup) || empty($permissionGroup)) {
                    continue;
                }

                $type = $permissionGroup[0] ?? null;
                $permissionItems = $permissionGroup[2] ?? [];

                if (!is_array($permissionItems)) {
                    continue;
                }

                foreach ($permissionItems as $item) {
                    $permissions[] = [
                        'permission' => $item[1] ?? null,
                        'type' => $type,
                    ];
                }
            }
        }

        return $permissions;
    }
}
