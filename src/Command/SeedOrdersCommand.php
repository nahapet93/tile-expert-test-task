<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-orders',
    description: 'Seed random orders for testing',
)]
final class SeedOrdersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('count', InputArgument::OPTIONAL, 'Number of orders to create', 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = (int) $input->getArgument('count');

        $io->info(sprintf('Creating %d random orders...', $count));

        $statuses = [1, 2, 3, 4, 5];
        $payTypes = [1, 2, 3];
        $locales = ['en', 'fr', 'de', 'es'];
        $currencies = ['EUR', 'USD', 'GBP'];

        $startDate = new \DateTime('2022-01-01');
        $endDate = new \DateTime('now');
        $dateDiff = $endDate->getTimestamp() - $startDate->getTimestamp();

        for ($i = 0; $i < $count; $i++) {
            $order = new Order();

            $randomTimestamp = $startDate->getTimestamp() + random_int(0, $dateDiff);
            $createDate = (new \DateTime())->setTimestamp($randomTimestamp);

            $order->setHash(md5(uniqid((string) $i, true)));
            $order->setToken(bin2hex(random_bytes(32)));
            $order->setNumber(sprintf('ORD-%06d', $i + 1));
            $order->setStatus($statuses[array_rand($statuses)]);
            $order->setEmail(sprintf('customer%d@example.com', $i + 1));
            $order->setVatType(random_int(0, 1));
            $order->setPayType($payTypes[array_rand($payTypes)]);
            $order->setLocale($locales[array_rand($locales)]);
            $order->setCurrency($currencies[array_rand($currencies)]);
            $order->setName(sprintf('Order %d', $i + 1));
            $order->setCreateDate($createDate);

            $this->entityManager->persist($order);

            if (($i + 1) % 50 === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $io->note(sprintf('Created %d orders...', $i + 1));
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $io->success(sprintf('Successfully created %d orders!', $count));

        return Command::SUCCESS;
    }
}
