<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\ResourceNotFoundException;
use App\Exception\ScrapingException;
use App\Service\PriceScraperService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class PriceScraperServiceTest extends TestCase
{
    private Client $httpClient;
    private CacheInterface $cache;
    private LoggerInterface $logger;
    private PriceScraperService $service;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(Client::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new PriceScraperService(
            $this->httpClient,
            $this->cache,
            $this->logger,
            3600
        );
    }

    public function testScrapePriceSuccessfully(): void
    {
        $factory = 'monopole';
        $collection = 'martinica';
        $article = '2344-martinicacoral7-s000628197';

        $html = '<html><body><div class="js-price-tag">33,50 €</div></body></html>';

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function (string $key, callable $callback) use ($html) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $response = new Response(200, [], $html);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://tile.expert/fr/tile/monopole/martinica/a/2344-martinicacoral7-s000628197')
            ->willReturn($response);

        $price = $this->service->scrapePrice($factory, $collection, $article);

        $this->assertEquals(33.50, $price);
    }

    public function testScrapePriceThrowsResourceNotFoundExceptionOn404(): void
    {
        $factory = 'nonexistent';
        $collection = 'collection';
        $article = 'article';

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function (string $key, callable $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $response = new Response(404);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Article not found');

        $this->service->scrapePrice($factory, $collection, $article);
    }

    public function testScrapePriceThrowsScrapingExceptionOnNetworkError(): void
    {
        $factory = 'test';
        $collection = 'test';
        $article = 'test';

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function (string $key, callable $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $request = new Request('GET', 'https://tile.expert/fr/tile/test/test/a/test');
        $exception = new RequestException('Connection error', $request);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        $this->expectException(ScrapingException::class);

        $this->service->scrapePrice($factory, $collection, $article);
    }

    public function testScrapePriceThrowsScrapingExceptionWhenPriceElementNotFound(): void
    {
        $factory = 'test';
        $collection = 'test';
        $article = 'test';

        $html = '<html><body><div class="no-price">Text</div></body></html>';

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function (string $key, callable $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $response = new Response(200, [], $html);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->expectException(ScrapingException::class);
        $this->expectExceptionMessage('Price element not found on the page');

        $this->service->scrapePrice($factory, $collection, $article);
    }
}
