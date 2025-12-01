<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class StatisticsControllerTest extends WebTestCase
{

    public function testGetStatisticsWithValidParameters(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/stats', [
            'group_by' => 'month',
            'page' => 1,
            'limit' => 12,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('meta', $responseData);
        $this->assertArrayHasKey('data', $responseData);

        $this->assertArrayHasKey('page', $responseData['meta']);
        $this->assertArrayHasKey('limit', $responseData['meta']);
        $this->assertArrayHasKey('total_pages', $responseData['meta']);
        $this->assertArrayHasKey('total_items', $responseData['meta']);

        $this->assertEquals(1, $responseData['meta']['page']);
        $this->assertEquals(12, $responseData['meta']['limit']);

        $this->assertIsArray($responseData['data']);
    }

    public function testGetStatisticsWithDayGrouping(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/stats', [
            'group_by' => 'day',
            'page' => 1,
            'limit' => 20,
        ]);

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('meta', $responseData);
        $this->assertArrayHasKey('data', $responseData);

        if (!empty($responseData['data'])) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $responseData['data'][0]['date']);
        }
    }

    public function testGetStatisticsWithYearGrouping(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/stats', [
            'group_by' => 'year',
            'page' => 1,
            'limit' => 10,
        ]);

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);

        if (!empty($responseData['data'])) {
            $this->assertMatchesRegularExpression('/^\d{4}$/', $responseData['data'][0]['date']);
        }
    }

    public function testGetStatisticsWithInvalidGroupBy(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/stats', [
            'group_by' => 'invalid',
            'page' => 1,
            'limit' => 10,
        ]);

        $this->assertResponseStatusCodeSame(400);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Validation failed', $responseData['error']);
    }

    public function testGetStatisticsWithDefaultParameters(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/stats');

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(1, $responseData['meta']['page']);
        $this->assertEquals(20, $responseData['meta']['limit']);
    }

    public function testGetStatisticsDataStructure(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/stats', [
            'group_by' => 'month',
        ]);

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);

        if (!empty($responseData['data'])) {
            $firstItem = $responseData['data'][0];

            $this->assertArrayHasKey('date', $firstItem);
            $this->assertArrayHasKey('count', $firstItem);
            $this->assertIsString($firstItem['date']);
            $this->assertIsInt($firstItem['count']);
        }
    }

    public function testGetStatisticsPagination(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/stats', [
            'group_by' => 'month',
            'page' => 1,
            'limit' => 5,
        ]);

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $firstPageCount = count($responseData['data']);

        $client->request('GET', '/api/orders/stats', [
            'group_by' => 'month',
            'page' => 2,
            'limit' => 5,
        ]);

        $this->assertResponseIsSuccessful();

        $responseData2 = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(2, $responseData2['meta']['page']);

        if ($firstPageCount > 0 && !empty($responseData2['data'])) {
            $this->assertNotEquals($responseData['data'][0]['date'], $responseData2['data'][0]['date']);
        }
    }
}
