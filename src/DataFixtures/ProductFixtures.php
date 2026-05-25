<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    /**
     * @var list<array{name: string, description: string, price: string, stock: int}>
     */
    private const PRODUCTS = [
        [
            'name' => 'Plain Shirt',
            'description' => 'A versatile everyday shirt with a soft cotton feel.',
            'price' => '49.90',
            'stock' => 40,
        ],
        [
            'name' => 'Printed Shirt',
            'description' => 'A casual printed shirt for a relaxed, modern look.',
            'price' => '59.90',
            'stock' => 28,
        ],
        [
            'name' => 'Customized Shirt',
            'description' => 'A lightweight customized shirt that pairs well with any outfit.',
            'price' => '89.90',
            'stock' => 18,
        ],
        [
            'name' => 'Classic Tee',
            'description' => 'A breathable classic tee designed for everyday comfort.',
            'price' => '29.90',
            'stock' => 55,
        ],
        [
            'name' => 'Summer Shirt',
            'description' => 'A flattering summer shirt ideal for warm-weather days.',
            'price' => '79.90',
            'stock' => 22,
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        $createdAt = new \DateTimeImmutable();

        foreach (self::PRODUCTS as $productData) {
            $product = $manager->getRepository(Product::class)->findOneBy(['name' => $productData['name']]);

            if (!$product) {
                $product = new Product();
                $product->setName($productData['name']);
                $product->setCreatedAt($createdAt);
            }

            $product->setDescription($productData['description']);
            $product->setPrice($productData['price']);
            $product->setStock($productData['stock']);

            $manager->persist($product);
        }

        $manager->flush();
    }
}
