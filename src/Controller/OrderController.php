<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/order')]
final class OrderController extends AbstractController
{
    #[Route(name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
                $order->setCreatedBy($this->getUser());

                // compute total from products if products selected
                $products = $order->getProduct();
                if ($products && count($products) > 0) {
                    $descriptions = [];
                    $sum = 0.0;

                    // Check stock availability before processing
                    $insufficientStockProducts = [];
                    if ($order->getQuantity() !== null && $order->getQuantity() > 0) {
                        foreach ($products as $product) {
                            $currentStock = $product->getStock();
                            if ($currentStock !== null && $currentStock < $order->getQuantity()) {
                                $insufficientStockProducts[] = $product->getName() . ' (available: ' . $currentStock . ')';
                            }
                        }
                    }

                    if (!empty($insufficientStockProducts)) {
                        $this->addFlash('error', 'Insufficient stock for: ' . implode(', ', $insufficientStockProducts));
                        return $this->redirectToRoute('app_order_new', [], Response::HTTP_SEE_OTHER);
                    }

                    foreach ($products as $p) {
                        if ($p->getDescription() !== null && trim($p->getDescription()) !== '') {
                            $descriptions[] = $p->getName() . ': ' . trim($p->getDescription());
                        } else {
                            $descriptions[] = $p->getName();
                        }

                        if ($p->getPrice() !== null) {
                            $sum += (float) $p->getPrice();
                        }
                    }

                    if (count($descriptions) > 0) {
                        $order->setDescription(implode(' | ', array_unique($descriptions)));
                    }

                    $order->setTotalAmount(number_format($sum, 2, '.', ''));

                    // Decrease stock for each product by the order quantity
                    if ($order->getQuantity() !== null && $order->getQuantity() > 0) {
                        foreach ($products as $product) {
                            $currentStock = $product->getStock();
                            if ($currentStock !== null) {
                                $newStock = max(0, $currentStock - $order->getQuantity());
                                $product->setStock($newStock);
                            }
                        }
                    }
                } elseif ($order->getPrice() !== null && $order->getQuantity() !== null) {
                    $total = (float) $order->getPrice() * (int) $order->getQuantity();
                    $order->setTotalAmount(number_format($total, 2, '.', ''));
                }

                $entityManager->persist($order);
            $entityManager->flush();

            $this->addFlash('success', 'Order created successfully!');
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        $this->checkOrderOwnership($order);

        // Completed or cancelled orders cannot be edited by anyone
        if (in_array($order->getStatus(), ['completed', 'cancelled'], true)) {
            throw $this->createAccessDeniedException('Completed or cancelled orders cannot be edited.');
        }

        // Store original values for stock adjustment
        $originalQuantity = $order->getQuantity();
        $originalProducts = new \Doctrine\Common\Collections\ArrayCollection();
        foreach ($order->getProduct() as $product) {
            $originalProducts->add($product);
        }
        
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
                // recompute if products changed, else price*quantity
                $products = $order->getProduct();
                if ($products && count($products) > 0) {
                    $descriptions = [];
                    $sum = 0.0;

                    // Check stock availability for quantity changes
                    $newQuantity = $order->getQuantity() ?? 0;
                    $quantityDifference = $newQuantity - ($originalQuantity ?? 0);

                    if ($quantityDifference > 0) {
                        $insufficientStockProducts = [];
                        foreach ($products as $product) {
                            $currentStock = $product->getStock();
                            if ($currentStock !== null && $currentStock < $quantityDifference) {
                                $insufficientStockProducts[] = $product->getName() . ' (available: ' . $currentStock . ')';
                            }
                        }

                        if (!empty($insufficientStockProducts)) {
                            $this->addFlash('error', 'Insufficient stock for quantity increase: ' . implode(', ', $insufficientStockProducts));
                            return $this->redirectToRoute('app_order_edit', ['id' => $order->getId()], Response::HTTP_SEE_OTHER);
                        }
                    }

                    foreach ($products as $p) {
                        if ($p->getDescription() !== null && trim($p->getDescription()) !== '') {
                            $descriptions[] = $p->getName() . ': ' . trim($p->getDescription());
                        } else {
                            $descriptions[] = $p->getName();
                        }

                        if ($p->getPrice() !== null) {
                            $sum += (float) $p->getPrice();
                        }
                    }

                    if (count($descriptions) > 0) {
                        $order->setDescription(implode(' | ', array_unique($descriptions)));
                    }

                    $order->setTotalAmount(number_format($sum, 2, '.', ''));

                    // Adjust stock based on quantity changes
                    if ($quantityDifference != 0) {
                        foreach ($products as $product) {
                            $currentStock = $product->getStock();
                            if ($currentStock !== null) {
                                $newStock = max(0, $currentStock - $quantityDifference);
                                $product->setStock($newStock);
                            }
                        }
                    }
                } elseif ($order->getPrice() !== null && $order->getQuantity() !== null) {
                    $total = (float) $order->getPrice() * (int) $order->getQuantity();
                    $order->setTotalAmount(number_format($total, 2, '.', ''));
                }

                $entityManager->flush();

            $this->addFlash('success', 'Order updated successfully!');
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        $this->checkOrderOwnership($order);
        
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($order);
            $entityManager->flush();
            $this->addFlash('success', 'Order deleted successfully!');
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }

    private function checkOrderOwnership(Order $order): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        $createdBy = $order->getCreatedBy();
        if ($this->isGranted('ROLE_STAFF') && $createdBy !== null && in_array('ROLE_ADMIN', $createdBy->getRoles(), true)) {
            return;
        }

        if ($createdBy !== $this->getUser()) {
            throw $this->createAccessDeniedException('You do not have permission to access this order.');
        }
    }
}

