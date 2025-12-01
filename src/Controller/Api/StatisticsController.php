<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\StatisticsRequestDto;
use App\Service\OrderStatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class StatisticsController extends AbstractController
{
    public function __construct(
        private readonly OrderStatisticsService $statisticsService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/orders/stats', name: 'api_orders_stats', methods: ['GET'])]
    public function getStatistics(Request $request): JsonResponse
    {
        $groupBy = $request->query->get('group_by', 'month');
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        $dto = new StatisticsRequestDto(
            groupBy: $groupBy,
            page: $page,
            limit: $limit,
        );

        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'error' => 'Validation failed',
                'details' => $errorMessages,
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $statistics = $this->statisticsService->getStatistics(
                $dto->groupBy,
                $dto->page,
                $dto->limit
            );

            return $this->json($statistics);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to retrieve statistics',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
