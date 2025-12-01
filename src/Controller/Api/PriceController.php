<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\PriceRequestDto;
use App\Exception\ResourceNotFoundException;
use App\Exception\ScrapingException;
use App\Service\PriceScraperService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PriceController extends AbstractController
{
    public function __construct(
        private readonly PriceScraperService $priceScraperService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/prices', name: 'api_prices', methods: ['GET'])]
    public function getPrices(Request $request): JsonResponse
    {
        $factory = $request->query->get('factory', '');
        $collection = $request->query->get('collection', '');
        $article = $request->query->get('article', '');

        $dto = new PriceRequestDto(
            factory: $factory,
            collection: $collection,
            article: $article,
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
            $price = $this->priceScraperService->scrapePrice(
                $dto->factory,
                $dto->collection,
                $dto->article
            );

            return $this->json([
                'price' => $price,
                'factory' => $dto->factory,
                'collection' => $dto->collection,
                'article' => $dto->article,
            ]);
        } catch (ResourceNotFoundException $e) {
            return $this->json([
                'error' => 'Article not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (ScrapingException $e) {
            return $this->json([
                'error' => 'Failed to scrape price',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
