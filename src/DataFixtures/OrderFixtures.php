<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Order;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class OrderFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $statuses = [1, 2, 3, 4, 5];
        $payTypes = [1, 2, 3];
        $locales = ['en', 'fr', 'de', 'es'];
        $currencies = ['EUR', 'USD', 'GBP'];

        $startDate = new \DateTime('2022-01-01');
        $endDate = new \DateTime('now');
        $dateDiff = $endDate->getTimestamp() - $startDate->getTimestamp();

        for ($i = 0; $i < 200; $i++) {
            $order = new Order();

            $randomTimestamp = $startDate->getTimestamp() + random_int(0, $dateDiff);
            $createDate = (new \DateTime())->setTimestamp($randomTimestamp);

            $order->setHash(md5(uniqid((string) $i, true)));
            $order->setToken(bin2hex(random_bytes(16)));
            $order->setNumber(sprintf('ORD-%06d', $i + 1));
            $order->setStatus($statuses[array_rand($statuses)]);
            $order->setEmail(sprintf('customer%d@example.com', $i + 1));
            $order->setVatType(random_int(0, 1));
            $order->setPayType($payTypes[array_rand($payTypes)]);
            $order->setLocale($locales[array_rand($locales)]);
            $order->setCurrency($currencies[array_rand($currencies)]);
            $order->setName(sprintf('Order %d', $i + 1));
            $order->setCreateDate($createDate);

            $manager->persist($order);

            if (($i + 1) % 50 === 0) {
                $manager->flush();
                $manager->clear();
            }
        }

        $manager->flush();
    }
}
