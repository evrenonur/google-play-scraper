<?php

declare(strict_types=1);

namespace GooglePlayScraper\Utils;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;

class HttpClient
{
    private Client $client;
    private CookieJar $cookieJar;
    private ?string $proxy;
    private int $throttleLimit;
    private int $throttleInterval;
    private int $requestCount = 0;
    private float $windowStart = 0;

    public function __construct(
        ?string $proxy = null,
        int $throttleLimit = 0,
        int $throttleInterval = 1000,
        array $guzzleOptions = [],
    ) {
        $this->proxy = $proxy;
        $this->throttleLimit = $throttleLimit;
        $this->throttleInterval = $throttleInterval;
        $this->cookieJar = new CookieJar();

        $defaultOptions = [
            RequestOptions::COOKIES => $this->cookieJar,
            RequestOptions::TIMEOUT => 30,
            RequestOptions::CONNECT_TIMEOUT => 10,
            RequestOptions::ALLOW_REDIRECTS => true,
            RequestOptions::HEADERS => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept-Language' => 'en-US,en;q=0.9',
            ],
        ];

        if ($this->proxy !== null) {
            $defaultOptions[RequestOptions::PROXY] = $this->proxy;
        }

        $this->client = new Client(array_merge($defaultOptions, $guzzleOptions));
    }

    public function get(string $url, array $options = []): string
    {
        $this->throttle();
        $response = $this->client->get($url, $options);
        return (string) $response->getBody();
    }

    public function post(string $url, string $body, array $headers = []): string
    {
        $this->throttle();

        $options = [
            RequestOptions::BODY => $body,
            RequestOptions::HEADERS => array_merge([
                'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
            ], $headers),
        ];

        $response = $this->client->post($url, $options);
        return (string) $response->getBody();
    }

    private function throttle(): void
    {
        if ($this->throttleLimit <= 0) {
            return;
        }

        $now = microtime(true) * 1000;

        if ($this->windowStart === 0.0) {
            $this->windowStart = $now;
        }

        if ($this->requestCount >= $this->throttleLimit) {
            $elapsed = $now - $this->windowStart;
            if ($elapsed < $this->throttleInterval) {
                $sleepMs = (int) ($this->throttleInterval - $elapsed);
                usleep($sleepMs * 1000);
            }
            $this->requestCount = 0;
            $this->windowStart = microtime(true) * 1000;
        }

        $this->requestCount++;
    }

    public function getProxy(): ?string
    {
        return $this->proxy;
    }

    public function setProxy(?string $proxy): void
    {
        $this->proxy = $proxy;
        // Recreate client with new proxy
        $config = $this->client->getConfig();
        if ($proxy !== null) {
            $config['proxy'] = $proxy;
        } else {
            unset($config['proxy']);
        }
        $this->client = new Client($config);
    }
}
