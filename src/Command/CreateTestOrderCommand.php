<?php

namespace App\Command;

use App\Entity\Order;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'app:create-test-order')]
final class CreateTestOrderCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // create a product
        $product = new Product();
        $product->setName('Test Product');
        $product->setPrice('123.45');
        $product->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($product);

        // create an order with the product
        $order = new Order();
        $order->setDescription('Order for test');
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->addProduct($product);
        $order->setPrice('123.45');
        $order->setQuantity(1);

        $this->em->persist($order);
        $this->em->flush();

        $output->writeln('Created order id: ' . $order->getId());
        return Command::SUCCESS;
    }
}
