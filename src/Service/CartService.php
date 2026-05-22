<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    private const SESSION_KEY = 'cart';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ProductRepository $productRepository,
    ) {
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    /**
     * @return array<int, int> productId => quantity
     */
    private function getCartData(): array
    {
        $cart = $this->getSession()->get(self::SESSION_KEY, []);

        return is_array($cart) ? $cart : [];
    }

    private function saveCartData(array $cart): void
    {
        $this->getSession()->set(self::SESSION_KEY, $cart);
    }

    public function add(Product $product, int $quantity = 1): void
    {
        if ($quantity < 1) {
            return;
        }

        $cart = $this->getCartData();
        $productId = $product->getId();
        $currentQty = $cart[$productId] ?? 0;
        $newQty = $currentQty + $quantity;

        $this->assertStockAvailable($product, $newQty);

        $cart[$productId] = $newQty;
        $this->saveCartData($cart);
    }

    public function setQuantity(Product $product, int $quantity): void
    {
        $cart = $this->getCartData();
        $productId = $product->getId();

        if ($quantity < 1) {
            unset($cart[$productId]);
            $this->saveCartData($cart);

            return;
        }

        $this->assertStockAvailable($product, $quantity);
        $cart[$productId] = $quantity;
        $this->saveCartData($cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->getCartData();
        unset($cart[$productId]);
        $this->saveCartData($cart);
    }

    public function clear(): void
    {
        $this->getSession()->remove(self::SESSION_KEY);
    }

    public function getTotalQuantity(): int
    {
        return array_sum($this->getCartData());
    }

    public function isEmpty(): bool
    {
        return $this->getCartData() === [];
    }

    /**
     * @return list<array{product: Product, quantity: int, subtotal: float}>
     */
    public function getLineItems(): array
    {
        $cart = $this->getCartData();
        if ($cart === []) {
            return [];
        }

        $products = $this->productRepository->findBy(['id' => array_keys($cart)]);
        $productsById = [];
        foreach ($products as $product) {
            $productsById[$product->getId()] = $product;
        }

        $items = [];
        foreach ($cart as $productId => $quantity) {
            if (!isset($productsById[$productId])) {
                continue;
            }

            $product = $productsById[$productId];
            $price = (float) $product->getPrice();
            $items[] = [
                'product' => $product,
                'quantity' => $quantity,
                'subtotal' => $price * $quantity,
            ];
        }

        return $items;
    }

    public function getSubtotal(): float
    {
        $total = 0.0;
        foreach ($this->getLineItems() as $item) {
            $total += $item['subtotal'];
        }

        return $total;
    }

    private function assertStockAvailable(Product $product, int $requestedQty): void
    {
        $stock = $product->getStock();
        if ($stock !== null && $requestedQty > $stock) {
            throw new \InvalidArgumentException(sprintf(
                'Only %d unit(s) of "%s" are available.',
                $stock,
                $product->getName()
            ));
        }
    }
}
