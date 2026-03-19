<?php

declare(strict_types=1);

namespace GooglePlayScraper\Utils;

class ScriptData
{
    /**
     * Parse AF_initDataCallback script tags from Google Play HTML response.
     */
    public static function parse(string $html): array
    {
        preg_match_all('/>AF_initDataCallback[\s\S]*?<\/script/s', $html, $matches);

        if (empty($matches[0])) {
            return [];
        }

        $parsedData = [];
        foreach ($matches[0] as $match) {
            if (
                preg_match("/(ds:.*?)'/", $match, $keyMatch) &&
                preg_match('/data:([\s\S]*?), sideChannel: \{\}\}\);<\//s', $match, $valueMatch)
            ) {
                $key = $keyMatch[1];
                $value = json_decode($valueMatch[1], true);
                if ($value !== null) {
                    $parsedData[$key] = $value;
                }
            }
        }

        $parsedData['serviceRequestData'] = self::parseServiceRequests($html);

        return $parsedData;
    }

    /**
     * Parse AF_dataServiceRequests from HTML.
     */
    private static function parseServiceRequests(string $html): array
    {
        preg_match_all("/>AF_dataServiceRequests[\s\S]*?<\/script/s", $html, $matches);

        if (empty($matches[0])) {
            return [];
        }

        $result = [];
        foreach ($matches[0] as $match) {
            if (preg_match("/data:([\s\S]*?), sideChannel: \{\}\}\);<\//s", $match, $valueMatch)) {
                $data = json_decode($valueMatch[1], true);
                if (is_array($data)) {
                    foreach ($data as $key => $value) {
                        $result[$key] = $value;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Extract a value from nested data using a path array.
     */
    public static function getPath(mixed $data, array $path): mixed
    {
        $current = $data;
        foreach ($path as $key) {
            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } else {
                return null;
            }
        }
        return $current;
    }

    /**
     * Apply mapping spec to parsed data.
     */
    public static function extractFields(array $mappings, mixed $parsedData): array
    {
        $result = [];
        foreach ($mappings as $field => $spec) {
            if (is_array($spec) && !self::isAssocArray($spec)) {
                // Simple path array
                $result[$field] = self::getPath($parsedData, $spec);
            } elseif (is_array($spec) && self::isAssocArray($spec)) {
                // Object spec with path and fun
                $input = null;

                if (isset($spec['useServiceRequestId'])) {
                    $input = self::extractDataWithServiceRequestId($parsedData, $spec);
                } else {
                    $input = self::getPath($parsedData, $spec['path'] ?? []);
                    if ($input === null && isset($spec['fallbackPath'])) {
                        $input = self::getPath($parsedData, $spec['fallbackPath']);
                    }
                }

                if (isset($spec['fun'])) {
                    if (isset($spec['isArray']) && $spec['isArray']) {
                        $result[$field] = ($spec['fun'])($parsedData);
                    } else {
                        $result[$field] = ($spec['fun'])($input, $parsedData);
                    }
                } else {
                    $result[$field] = $input;
                }
            }
        }
        return $result;
    }

    /**
     * Extract data using service request ID mapping.
     */
    public static function extractDataWithServiceRequestId(array $parsedData, array $spec): mixed
    {
        $serviceRequestData = $parsedData['serviceRequestData'] ?? [];
        $filteredPath = null;

        foreach ($serviceRequestData as $key => $value) {
            if (isset($value['id']) && $value['id'] === $spec['useServiceRequestId']) {
                $filteredPath = $key;
                break;
            }
        }

        $path = $spec['path'] ?? [];
        if ($filteredPath !== null) {
            $fullPath = array_merge([$filteredPath], $path);
        } else {
            $fullPath = $path;
        }

        return self::getPath($parsedData, $fullPath);
    }

    private static function isAssocArray(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
