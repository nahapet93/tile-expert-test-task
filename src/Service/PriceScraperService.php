<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ResourceNotFoundException;
use App\Exception\ScrapingException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

readonly class PriceScraperService
{
    private const BASE_URL = 'https://tile.expert';
    private const LOCALE = 'fr';

    public function __construct(
        private Client $httpClient,
        private CacheInterface $cache,
        private LoggerInterface $logger,
        private int $cacheTtl = 3600,
    ) {
    }

    public function scrapePrice(string $factory, string $collection, string $article): float
    {
        $cacheKey = $this->generateCacheKey($factory, $collection, $article);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($factory, $collection, $article): float {
            $item->expiresAfter($this->cacheTtl);

            $url = $this->buildUrl($factory, $collection, $article);
            $html = $this->fetchPage($url);

            return $this->extractPrice($html, $url);
        });
    }

    private function buildUrl(string $factory, string $collection, string $article): string
    {
        return sprintf(
            '%s/%s/tile/%s/%s/a/%s',
            self::BASE_URL,
            self::LOCALE,
            $factory,
            $collection,
            $article
        );
    }

    private function fetchPage(string $url): string
    {
        try {
            $this->logger->info('Fetching page', ['url' => $url]);

            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.8',
                ],
                'timeout' => 30,
                'allow_redirects' => true,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 404) {
                throw new ResourceNotFoundException('Article not found');
            }

            if ($statusCode !== 200) {
                throw new ScrapingException("Failed to fetch page, status code: {$statusCode}");
            }

            return $response->getBody()->getContents();
        } catch (GuzzleException $e) {
            $this->logger->error('Failed to fetch page', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            if (str_contains($e->getMessage(), '404')) {
                throw new ResourceNotFoundException('Article not found', 0, $e);
            }

            throw new ScrapingException('Failed to scrape price: ' . $e->getMessage(), 0, $e);
        }
    }

    private function extractPrice(string $html, string $url): float
    {
        try {
            $crawler = new Crawler($html);

            $priceNode = $crawler->filter('.js-price-tag');

            if ($priceNode->count() === 0) {
                throw new ScrapingException('Price element not found on the page');
            }

            $priceText = $priceNode->first()->text();

            $priceText = preg_replace('/[^\d,.]/', '', $priceText);
            $priceText = str_replace(',', '.', $priceText);

            $price = (float) $priceText;

            if ($price <= 0) {
                throw new ScrapingException('Invalid price value extracted');
            }

            $this->logger->info('Price extracted successfully', [
                'url' => $url,
                'price' => $price,
            ]);

            return $price;
        } catch (\Exception $e) {
            if ($e instanceof ScrapingException) {
                throw $e;
            }

            $this->logger->error('Failed to extract price', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new ScrapingException('Failed to extract price: ' . $e->getMessage(), 0, $e);
        }
    }

    private function generateCacheKey(string $factory, string $collection, string $article): string
    {
        return sprintf('price_%s_%s_%s', $factory, $collection, $article);
    }
}
