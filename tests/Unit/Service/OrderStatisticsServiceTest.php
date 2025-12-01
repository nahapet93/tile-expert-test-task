<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Repository\OrderRepository;
use App\Service\OrderStatisticsService;
use PHPUnit\Framework\TestCase;

final class OrderStatisticsServiceTest extends TestCase
{
    private OrderRepository $orderRepository;
    private OrderStatisticsService $service;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->service = new OrderStatisticsService($this->orderRepository);
    }

    public function testGetStatisticsReturnsFormattedData(): void
    {
        $mockData = [
            ['date' => '2023-12', 'count' => '150'],
            ['date' => '2023-11', 'count' => '120'],
            ['date' => '2023-10', 'count' => '90'],
        ];

        $this->orderRepository->expects($this->once())
            ->method('getStatistics')
            ->with('month', 1, 12)
            ->willReturn($mockData);

        $this->orderRepository->expects($this->once())
            ->method('getTotalStatisticsCount')
            ->with('month')
            ->willReturn(58);

        $result = $this->service->getStatistics('month', 1, 12);

        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('data', $result);

        $this->assertEquals(1, $result['meta']['page']);
        $this->assertEquals(12, $result['meta']['limit']);
        $this->assertEquals(5, $result['meta']['total_pages']);
        $this->assertEquals(58, $result['meta']['total_items']);

        $this->assertCount(3, $result['data']);
        $this->assertEquals('2023-12', $result['data'][0]['date']);
        $this->assertEquals(150, $result['data'][0]['count']);
        $this->assertIsInt($result['data'][0]['count']);
    }

    public function testGetStatisticsWithDayGrouping(): void
    {
        $mockData = [
            ['date' => '2023-12-25', 'count' => '10'],
            ['date' => '2023-12-24', 'count' => '8'],
        ];

        $this->orderRepository->expects($this->once())
            ->method('getStatistics')
            ->with('day', 1, 20)
            ->willReturn($mockData);

        $this->orderRepository->expects($this->once())
            ->method('getTotalStatisticsCount')
            ->with('day')
            ->willReturn(100);

        $result = $this->service->getStatistics('day', 1, 20);

        $this->assertEquals(5, $result['meta']['total_pages']);
        $this->assertCount(2, $result['data']);
    }

    public function testGetStatisticsWithYearGrouping(): void
    {
        $mockData = [
            ['date' => '2023', 'count' => '1500'],
            ['date' => '2022', 'count' => '1200'],
        ];

        $this->orderRepository->expects($this->once())
            ->method('getStatistics')
            ->with('year', 1, 10)
            ->willReturn($mockData);

        $this->orderRepository->expects($this->once())
            ->method('getTotalStatisticsCount')
            ->with('year')
            ->willReturn(5);

        $result = $this->service->getStatistics('year', 1, 10);

        $this->assertEquals(1, $result['meta']['total_pages']);
        $this->assertCount(2, $result['data']);
        $this->assertEquals('2023', $result['data'][0]['date']);
    }

    public function testGetStatisticsCalculatesTotalPagesCorrectly(): void
    {
        $this->orderRepository->expects($this->once())
            ->method('getStatistics')
            ->willReturn([]);

        $this->orderRepository->expects($this->once())
            ->method('getTotalStatisticsCount')
            ->willReturn(25);

        $result = $this->service->getStatistics('month', 1, 10);

        $this->assertEquals(3, $result['meta']['total_pages']);
    }
}
