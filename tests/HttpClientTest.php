<?php

declare(strict_types=1);

namespace GooglePlayScraper\Tests;

use GooglePlayScraper\Utils\HttpClient;
use PHPUnit\Framework\TestCase;

class HttpClientTest extends TestCase
{
    public function testConstructorWithoutProxy(): void
    {
        $client = new HttpClient();
        $this->assertNull($client->getProxy());
    }

    public function testConstructorWithProxy(): void
    {
        $client = new HttpClient(proxy: 'http://proxy.example.com:8080');
        $this->assertEquals('http://proxy.example.com:8080', $client->getProxy());
    }

    public function testSetProxy(): void
    {
        $client = new HttpClient();
        $client->setProxy('http://proxy.example.com:3128');
        $this->assertEquals('http://proxy.example.com:3128', $client->getProxy());
    }

    public function testSetProxyToNull(): void
    {
        $client = new HttpClient(proxy: 'http://proxy.example.com:8080');
        $client->setProxy(null);
        $this->assertNull($client->getProxy());
    }

    public function testConstructorWithSocksProxy(): void
    {
        $client = new HttpClient(proxy: 'socks5://proxy.example.com:1080');
        $this->assertEquals('socks5://proxy.example.com:1080', $client->getProxy());
    }

    public function testConstructorWithHttpsProxy(): void
    {
        $client = new HttpClient(proxy: 'https://secure-proxy.example.com:443');
        $this->assertEquals('https://secure-proxy.example.com:443', $client->getProxy());
    }

    public function testConstructorWithThrottleOptions(): void
    {
        $client = new HttpClient(
            throttleLimit: 5,
            throttleInterval: 2000,
        );
        $this->assertNull($client->getProxy());
    }
}
