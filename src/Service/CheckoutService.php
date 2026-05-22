<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CustomerRepository $customerRepository,
        private readonly ProductRepository $productRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws \InvalidArgumentException when cart is empty or stock is insufficient
     */
    public function placeOrder(User $user, string $phoneNumber, ?string $notes = null): Order
    {
        $lineItems = $this->cartService->getLineItems();
        if ($lineItems === []) {
            throw new \InvalidArgumentException('Your cart is empty.');
        }

        return $this->buildOrder($user, $lineItems, $phoneNumber, $notes, true);
    }

    public function placeOrderFromItems(User $user, array $items, string $phoneNumber, ?string $notes = null): Order
    {
        if ($items === []) {
            throw new \InvalidArgumentException('Your order items are empty.');
        }

        $quantities = [];
        foreach ($items as $item) {
            $productId = $item['productId'] ?? $item['id'] ?? null;
            $quantity = $item['quantity'] ?? 1;

            if (!is_int($productId) && !ctype_digit((string) $productId)) {
                throw new \InvalidArgumentException('Each order item must include a valid productId.');
            }
            $productId = (int) $productId;
            $quantity = (int) $quantity;

            if ($productId <= 0 || $quantity < 1) {
                throw new \InvalidArgumentException('Each order item must include a valid productId and quantity >= 1.');
            }

            $quantities[$productId] = ($quantities[$productId] ?? 0) + $quantity;
        }

        $products = $this->productRepository->findBy(['id' => array_keys($quantities)]);
        if (count($products) !== count($quantities)) {
            $missing = array_diff(array_keys($quantities), array_map(fn(Product $product) => $product->getId(), $products));
            throw new \InvalidArgumentException(sprintf('Product(s) not found: %s', implode(', ', $missing)));
        }

        $lineItems = [];
        foreach ($products as $product) {
            $lineItems[] = [
                'product' => $product,
                'quantity' => $quantities[$product->getId()],
            ];
        }

        return $this->buildOrder($user, $lineItems, $phoneNumber, $notes, false);
    }

    private function buildOrder(User $user, array $lineItems, string $phoneNumber, ?string $notes, bool $clearCart): Order
    {
        foreach ($lineItems as $item) {
            $product = $item['product'];
            $stock = $product->getStock();
            if ($stock !== null && $item['quantity'] > $stock) {
                throw new \InvalidArgumentException(sprintf(
                    'Only %d unit(s) of "%s" are available.',
                    $stock,
                    $product->getName()
                ));
            }
        }

        $customer = $this->resolveCustomer($user, $phoneNumber);
        $subtotal = array_reduce($lineItems, static fn(float $carry, array $item): float => $carry + ((float) $item['product']->getPrice() * $item['quantity']), 0.0);

        $order = new Order();
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setCreatedBy($user);
        $order->setCustomer($customer);
        $order->setStatus('pending');

        $descriptions = [];
        $totalQuantity = 0;

        foreach ($lineItems as $item) {
            $product = $item['product'];
            $quantity = $item['quantity'];
            $order->addProduct($product);
            $totalQuantity += $quantity;

            $descriptions[] = sprintf(
                '%s x%d (₱%s)',
                $product->getName(),
                $quantity,
                number_format((float) $product->getPrice(), 2, '.', ',')
            );

            $stock = $product->getStock();
            if ($stock !== null) {
                $product->setStock(max(0, $stock - $quantity));
            }
        }

        $description = implode(' | ', $descriptions);
        if ($notes !== null && trim($notes) !== '') {
            $description .= ' — Notes: ' . trim($notes);
        }

        $order->setDescription($description);
        $order->setQuantity($totalQuantity);
        $formattedTotal = number_format($subtotal, 2, '.', '');
        $order->setPrice($formattedTotal);
        $order->setTotalAmount($formattedTotal);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        if ($clearCart) {
            $this->cartService->clear();
        }

        return $order;
    }

    private function resolveCustomer(User $user, string $phoneNumber): Customer
    {
        $email = $user->getEmail();
        $customer = $email ? $this->customerRepository->findOneBy(['email' => $email]) : null;

        if ($customer === null) {
            $customer = new Customer();
            $customer->setEmail($email ?? $user->getUserIdentifier().'@customer.local');
            $customer->setCreatedBy($user);
        }

        $customer->setName($this->buildCustomerName($user));
        $customer->setPhoneNumber($phoneNumber);
        $customer->setEmail($email ?? $customer->getEmail());

        $this->entityManager->persist($customer);

        return $customer;
    }

    private function buildCustomerName(User $user): string
    {
        $parts = array_filter([
            $user->getFirstName(),
            $user->getLastName(),
        ]);

        if ($parts !== []) {
            return implode(' ', $parts);
        }

        return $user->getUsername() ?? $user->getUserIdentifier();
    }
}
