<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Form\CheckoutType;
use App\Repository\OrderRepository;
use App\Security\Voter\CustomerVoter;
use App\Service\CartService;
use App\Service\CheckoutService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checkout')]
#[IsGranted(CustomerVoter::IS_CUSTOMER, message: 'Please log in as a customer to checkout.')]
final class CheckoutController extends AbstractController
{
    #[Route('', name: 'app_checkout', methods: ['GET', 'POST'])]
    public function checkout(
        Request $request,
        CartService $cartService,
        CheckoutService $checkoutService,
        OrderRepository $orderRepository,
    ): Response {
        if ($cartService->isEmpty()) {
            $this->addFlash('warning', 'Your cart is empty. Add items before checking out.');

            return $this->redirectToRoute('app_cart');
        }

        /** @var User $user */
        $user = $this->getUser();
        $existingPhone = null;
        $recentOrder = $orderRepository->findLatestForUser($user);
        if ($recentOrder?->getCustomer() !== null) {
            $existingPhone = $recentOrder->getCustomer()->getPhoneNumber();
        }

        $form = $this->createForm(CheckoutType::class);
        if ($existingPhone) {
            $form->get('phoneNumber')->setData($existingPhone);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $order = $checkoutService->placeOrder(
                    $user,
                    $data['phoneNumber'],
                    $data['notes'] ?? null,
                );
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->redirectToRoute('app_cart');
            }

            return $this->redirectToRoute('app_checkout_success', ['id' => $order->getId()]);
        }

        return $this->render('checkout/index.html.twig', [
            'form' => $form,
            'items' => $cartService->getLineItems(),
            'subtotal' => $cartService->getSubtotal(),
        ]);
    }

    #[Route('/success/{id}', name: 'app_checkout_success', methods: ['GET'])]
    public function success(Order $order): Response
    {
        $this->assertOrderBelongsToCustomer($order);

        return $this->render('checkout/success.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/orders', name: 'app_my_orders', methods: ['GET'])]
    public function myOrders(OrderRepository $orderRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('checkout/orders.html.twig', [
            'orders' => $orderRepository->findForUser($user),
        ]);
    }

    #[Route('/orders/{id}', name: 'app_my_order_show', methods: ['GET'])]
    public function showOrder(Order $order): Response
    {
        $this->assertOrderBelongsToCustomer($order);

        return $this->render('checkout/order_show.html.twig', [
            'order' => $order,
        ]);
    }

    private function assertOrderBelongsToCustomer(Order $order): void
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($order->getCreatedBy() === $user) {
            return;
        }

        $customer = $order->getCustomer();
        if ($customer !== null && $customer->getEmail() === $user->getEmail()) {
            return;
        }

        throw $this->createAccessDeniedException('You do not have access to this order.');
    }
}
