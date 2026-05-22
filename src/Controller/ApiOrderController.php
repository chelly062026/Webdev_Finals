<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\CheckoutService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ApiOrderController extends AbstractController
{
    public function __construct(private readonly CheckoutService $checkoutService)
    {
    }

    #[Route('/orders', name: 'api_orders_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->resolveJsonPayload($request);

        if (!isset($data['items']) || !is_array($data['items']) || count($data['items']) === 0) {
            return $this->json([
                'success' => false,
                'message' => 'Order items are required and must be provided as a non-empty array.',
            ], 400);
        }

        if (!isset($data['phoneNumber']) || !is_string($data['phoneNumber']) || trim($data['phoneNumber']) === '') {
            return $this->json([
                'success' => false,
                'message' => 'A valid phone number is required for order processing.',
            ], 400);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        try {
            $order = $this->checkoutService->placeOrderFromItems(
                $user,
                $data['items'],
                trim($data['phoneNumber']),
                isset($data['notes']) ? trim((string) $data['notes']) : null
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 400);
        }

        return $this->json([
            'success' => true,
            'message' => 'Order placed successfully.',
            'order' => [
                'id' => $order->getId(),
                'description' => $order->getDescription(),
                'totalAmount' => $order->getTotalAmount(),
                'quantity' => $order->getQuantity(),
                'status' => $order->getStatus(),
                'createdAt' => $order->getCreatedAt()?->format('c'),
            ],
        ], 201);
    }

    private function resolveJsonPayload(Request $request): array
    {
        $content = $request->getContent();
        if ($content !== '') {
            try {
                $payload = $request->toArray();
                if (is_array($payload)) {
                    return $payload;
                }
            } catch (\Throwable) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return array_merge($request->request->all(), $request->query->all());
    }
}
