<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Repository\OrderRepository;

class SoapOrderService
{
    private static ?OrderRepository $orderRepository = null;

    public function __construct(
        ?OrderRepository $orderRepository = null,
    ) {
        if ($orderRepository !== null) {
            self::$orderRepository = $orderRepository;
        }
    }

    private function getRepository(): OrderRepository
    {
        if (self::$orderRepository === null) {
            throw new \RuntimeException('OrderRepository not initialized');
        }
        return self::$orderRepository;
    }

    /**
     * Create a new order
     *
     * @param string $factory Factory name
     * @param string $collection Collection name
     * @param string $article Article identifier
     * @param int $quantity Order quantity
     * @param string $customerName Customer name
     * @param string $customerEmail Customer email
     * @return int Created order ID
     */
    public function createOrder(
        string $factory,
        string $collection,
        string $article,
        int $quantity,
        string $customerName,
        string $customerEmail
    ): int {
        $order = new Order();

        $order->setHash(md5(uniqid('soap_', true)));
        $order->setToken(bin2hex(random_bytes(16)));
        $order->setNumber(sprintf('S-%s', strtoupper(substr(md5(uniqid()), 0, 6))));
        $order->setStatus(1);
        $order->setEmail($customerEmail);
        $order->setPayType(1);
        $order->setLocale('en');
        $order->setCurrency('EUR');
        $order->setName(sprintf('%s - %s - %s (Qty: %d)', $factory, $collection, $article, $quantity));
        $order->setClientName($customerName);
        $order->setDescription(sprintf(
            'SOAP Order: Factory=%s, Collection=%s, Article=%s, Quantity=%d',
            $factory,
            $collection,
            $article,
            $quantity
        ));
        $order->setCreateDate(new \DateTime());

        $this->getRepository()->save($order, true);

        return $order->getId();
    }

    /**
     * Get order by ID
     *
     * @param int $orderId Order ID
     * @return array Order details
     */
    public function getOrder(int $orderId): array
    {
        $order = $this->getRepository()->find($orderId);

        if (!$order) {
            throw new \RuntimeException('Order not found');
        }

        return [
            'id' => $order->getId(),
            'number' => $order->getNumber(),
            'name' => $order->getName(),
            'email' => $order->getEmail(),
            'status' => $order->getStatus(),
            'created_at' => $order->getCreateDate()->format('Y-m-d H:i:s'),
        ];
    }
}
