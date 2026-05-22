<?php

namespace App\Controller;

use App\Entity\Product;
use App\Security\Voter\CustomerVoter;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart')]
#[IsGranted(CustomerVoter::IS_CUSTOMER, message: 'Please log in as a customer to use the cart.')]
final class CartController extends AbstractController
{
    #[Route('', name: 'app_cart', methods: ['GET'])]
    public function index(CartService $cartService): Response
    {
        return $this->render('cart/index.html.twig', [
            'items' => $cartService->getLineItems(),
            'subtotal' => $cartService->getSubtotal(),
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Request $request, Product $product, CartService $cartService): Response
    {
        if (!$this->isCsrfTokenValid('cart_add'.$product->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid request. Please try again.');

            return $this->redirectToRoute('app_shop');
        }

        $quantity = max(1, $request->request->getInt('quantity', 1));

        try {
            $cartService->add($product, $quantity);
            $this->addFlash('success', sprintf('"%s" added to your cart.', $product->getName()));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        $redirect = $request->request->getString('redirect');
        if ($redirect !== '' && str_starts_with($redirect, '/')) {
            return $this->redirect($redirect);
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/update', name: 'app_cart_update', methods: ['POST'])]
    public function update(Request $request, CartService $cartService): Response
    {
        if (!$this->isCsrfTokenValid('cart_update', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid request. Please try again.');

            return $this->redirectToRoute('app_cart');
        }

        $quantities = $request->request->all('quantities');

        foreach ($cartService->getLineItems() as $item) {
            $product = $item['product'];
            $productId = (string) $product->getId();
            if (!isset($quantities[$productId])) {
                continue;
            }

            $quantity = (int) $quantities[$productId];

            try {
                $cartService->setQuantity($product, $quantity);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        $this->addFlash('success', 'Cart updated.');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(Request $request, Product $product, CartService $cartService): Response
    {
        if (!$this->isCsrfTokenValid('cart_remove'.$product->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid request. Please try again.');

            return $this->redirectToRoute('app_cart');
        }

        $cartService->remove($product->getId());
        $this->addFlash('success', sprintf('"%s" removed from your cart.', $product->getName()));

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(Request $request, CartService $cartService): Response
    {
        if (!$this->isCsrfTokenValid('cart_clear', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid request. Please try again.');

            return $this->redirectToRoute('app_cart');
        }

        $cartService->clear();
        $this->addFlash('success', 'Your cart has been cleared.');

        return $this->redirectToRoute('app_cart');
    }
}
