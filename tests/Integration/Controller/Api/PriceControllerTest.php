<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api;

use App\Service\PriceScraperService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PriceControllerTest extends WebTestCase
{
    public function testGetPricesWithValidParameters(): void
    {
        $client = static::createClient();

        $priceScraperService = $this->createMock(PriceScraperService::class);
        $priceScraperService->expects($this->once())
            ->method('scrapePrice')
            ->with('monopole', 'martinica', '2344-martinicacoral7-s000628197')
            ->willReturn(33.50);

        $client->getContainer()->set(PriceScraperService::class, $priceScraperService);

        $client->request('GET', '/api/prices', [
            'factory' => 'monopole',
            'collection' => 'martinica',
            'article' => '2344-martinicacoral7-s000628197',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(33.50, $responseData['price']);
        $this->assertEquals('monopole', $responseData['factory']);
        $this->assertEquals('martinica', $responseData['collection']);
        $this->assertEquals('2344-martinicacoral7-s000628197', $responseData['article']);
    }

    public function testGetPricesWithMissingParameters(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/prices', [
            'factory' => 'test',
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Validation failed', $responseData['error']);
    }

    public function testGetPricesWithEmptyParameters(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/prices');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testGetPricesReturns404WhenArticleNotFound(): void
    {
        $client = static::createClient();

        $priceScraperService = $this->createMock(PriceScraperService::class);
        $priceScraperService->expects($this->once())
            ->method('scrapePrice')
            ->willThrowException(new \App\Exception\ResourceNotFoundException('Article not found'));

        $client->getContainer()->set(PriceScraperService::class, $priceScraperService);

        $client->request('GET', '/api/prices', [
            'factory' => 'nonexistent',
            'collection' => 'nonexistent',
            'article' => 'nonexistent',
        ]);

        $this->assertResponseStatusCodeSame(404);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Article not found', $responseData['error']);
    }
}
