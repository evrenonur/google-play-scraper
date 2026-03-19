<?php

declare(strict_types=1);

namespace GooglePlayScraper\Tests;

use GooglePlayScraper\GooglePlayScraper;
use GooglePlayScraper\Utils\HttpClient;
use PHPUnit\Framework\TestCase;

class GooglePlayScraperTest extends TestCase
{
    public function testConstructorCreatesInstance(): void
    {
        $scraper = new GooglePlayScraper();
        $this->assertInstanceOf(GooglePlayScraper::class, $scraper);
    }

    public function testConstructorWithProxy(): void
    {
        $scraper = new GooglePlayScraper(proxy: 'http://proxy.example.com:8080');
        $client = $scraper->getHttpClient();
        $this->assertEquals('http://proxy.example.com:8080', $client->getProxy());
    }

    public function testConstructorWithSocksProxy(): void
    {
        $scraper = new GooglePlayScraper(proxy: 'socks5://proxy.example.com:1080');
        $client = $scraper->getHttpClient();
        $this->assertEquals('socks5://proxy.example.com:1080', $client->getProxy());
    }

    public function testSetProxy(): void
    {
        $scraper = new GooglePlayScraper();
        $this->assertNull($scraper->getHttpClient()->getProxy());

        $scraper->setProxy('http://newproxy.example.com:3128');
        $this->assertEquals('http://newproxy.example.com:3128', $scraper->getHttpClient()->getProxy());
    }

    public function testSetProxyToNull(): void
    {
        $scraper = new GooglePlayScraper(proxy: 'http://proxy.example.com:8080');
        $scraper->setProxy(null);
        $this->assertNull($scraper->getHttpClient()->getProxy());
    }

    public function testGetHttpClientReturnsInstance(): void
    {
        $scraper = new GooglePlayScraper();
        $this->assertInstanceOf(HttpClient::class, $scraper->getHttpClient());
    }

    public function testConstructorWithThrottle(): void
    {
        $scraper = new GooglePlayScraper(
            throttleLimit: 10,
            throttleInterval: 2000,
        );
        $this->assertInstanceOf(GooglePlayScraper::class, $scraper);
    }

    public function testConstructorWithGuzzleOptions(): void
    {
        $scraper = new GooglePlayScraper(
            guzzleOptions: [
                'timeout' => 60,
                'verify' => false,
            ],
        );
        $this->assertInstanceOf(GooglePlayScraper::class, $scraper);
    }
}
