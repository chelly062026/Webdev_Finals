<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ApiProductController extends AbstractController
{
    public function __construct(private readonly ProductRepository $productRepository)
    {
    }

    #[Route('/products', name: 'api_products_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $products = $this->productRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->json([
            'success' => true,
            'data' => array_map([$this, 'normalizeProduct'], $products),
        ]);
    }

    #[Route('/products/{id}', name: 'api_products_show', methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => $this->normalizeProduct($product),
        ]);
    }

    private function normalizeProduct(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice() !== null ? (float) $product->getPrice() : 0.0,
            'stock' => $product->getStock(),
            'image' => $product->getImage(),
            'createdAt' => $product->getCreatedAt()?->format('c'),
        ];
    }
}
