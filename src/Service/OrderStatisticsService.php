<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\OrderRepository;

final readonly class OrderStatisticsService
{
    public function __construct(
        private OrderRepository $orderRepository,
    ) {
    }

    public function getStatistics(string $groupBy, int $page, int $limit): array
    {
        $data = $this->orderRepository->getStatistics($groupBy, $page, $limit);
        $totalItems = $this->orderRepository->getTotalStatisticsCount($groupBy);
        $totalPages = (int) ceil($totalItems / $limit);

        $formattedData = array_map(function (array $row) {
            return [
                'date' => $row['date'],
                'count' => (int) $row['count'],
            ];
        }, $data);

        return [
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
            ],
            'data' => $formattedData,
        ];
    }
}
