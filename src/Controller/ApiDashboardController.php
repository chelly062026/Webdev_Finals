<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class ApiDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'api_dashboard', methods: ['GET'])]
    public function dashboard(
        CustomerRepository $customerRepo,
        OrderRepository $orderRepo,
        ProductRepository $productRepo
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        $recentProducts = $productRepo->findRecent(6);

        $dashboard = [
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUserIdentifier(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'roles' => $user->getRoles(),
            ],
            'recentProducts' => array_map([$this, 'normalizeProduct'], $recentProducts),
        ];

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
            $customerCount = $customerRepo->count([]);
            $orderCount = $orderRepo->count([]);
            $productCount = $productRepo->count([]);
            $statusCounts = $orderRepo->getStatusCounts();
            $recentOrders = $orderRepo->findRecent(6);
            $totalRevenue = $orderRepo->getTotalRevenue();

            $dashboard['dashboardType'] = 'admin';
            $dashboard['customerCount'] = $customerCount;
            $dashboard['orderCount'] = $orderCount;
            $dashboard['productCount'] = $productCount;
            $dashboard['totalRevenue'] = $totalRevenue;
            $dashboard['statusCounts'] = $statusCounts;
            $dashboard['recentOrders'] = array_map([$this, 'normalizeOrder'], $recentOrders);
        } else {
            $orders = $orderRepo->findForUser($user);
            $orderCount = count($orders);
            $totalSpent = array_reduce($orders, static function (float $carry, Order $order): float {
                return $carry + ((float) $order->getTotalAmount());
            }, 0.0);

            $statusCounts = [
                'pending' => 0,
                'processing' => 0,
                'completed' => 0,
                'cancelled' => 0,
            ];
            foreach ($orders as $order) {
                $status = strtolower((string) $order->getStatus());
                if (isset($statusCounts[$status])) {
                    $statusCounts[$status]++;
                }
            }

            $dashboard['dashboardType'] = 'user';
            $dashboard['orderCount'] = $orderCount;
            $dashboard['totalSpent'] = $totalSpent;
            $dashboard['statusCounts'] = $statusCounts;
            $dashboard['recentOrders'] = array_map([$this, 'normalizeOrder'], array_slice($orders, 0, 6));
        }

        return new JsonResponse(['success' => true, 'data' => $dashboard]);
    }

    private function normalizeOrder(Order $order): array
    {
        return [
            'id' => $order->getId(),
            'description' => $order->getDescription(),
            'totalAmount' => $order->getTotalAmount() !== null ? (float) $order->getTotalAmount() : 0.0,
            'quantity' => $order->getQuantity(),
            'status' => $order->getStatus(),
            'createdAt' => $order->getCreatedAt()?->format('c'),
            'customer' => $order->getCustomer() ? [
                'id' => $order->getCustomer()->getId(),
                'name' => $order->getCustomer()->getName(),
                'email' => $order->getCustomer()->getEmail(),
            ] : null,
        ];
    }

    private function normalizeProduct(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice() !== null ? (float) $product->getPrice() : 0.0,
            'stock' => $product->getStock(),
            'image' => $product->getImage(),
            'createdAt' => $product->getCreatedAt()?->format('c'),
        ];
    }
}
