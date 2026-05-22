<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Update existing products with stock values
        $products = $manager->getRepository(Product::class)->findAll();
        
        $stockValues = [50, 25, 100, 75, 30];
        $i = 0;
        
        foreach ($products as $product) {
            if (isset($stockValues[$i])) {
                $product->setStock($stockValues[$i]);
            }
            $i++;
        }

        $manager->flush();
    }
}
