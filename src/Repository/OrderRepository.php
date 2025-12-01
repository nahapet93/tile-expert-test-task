<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function save(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getStatistics(string $groupBy, int $page, int $limit): array
    {
        $dateFormat = match($groupBy) {
            'year' => '%Y',
            'day' => '%Y-%m-%d',
            default => '%Y-%m',
        };

        $offset = ($page - 1) * $limit;

        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT
                DATE_FORMAT(create_date, :format) as date,
                COUNT(id) as count
            FROM orders
            WHERE deleted_at IS NULL
            GROUP BY date
            ORDER BY date DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('format', $dateFormat);
        $stmt->bindValue('limit', $limit, ParameterType::INTEGER);
        $stmt->bindValue('offset', $offset, ParameterType::INTEGER);

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function getTotalStatisticsCount(string $groupBy): int
    {
        $dateFormat = match($groupBy) {
            'year' => '%Y',
            'day' => '%Y-%m-%d',
            default => '%Y-%m',
        };

        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT COUNT(DISTINCT DATE_FORMAT(create_date, :format)) as total
            FROM orders
            WHERE deleted_at IS NULL
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('format', $dateFormat);

        return (int) $stmt->executeQuery()->fetchOne();
    }
}
