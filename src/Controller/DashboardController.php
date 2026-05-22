<?php

namespace App\Controller;

use App\Repository\CustomerRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    #[Route('/user/dashboard', name: 'app_user_dashboard')]
    public function index(
        CustomerRepository $customerRepo,
        OrderRepository $orderRepo,
        ProductRepository $productRepo
    ): Response {
        if ($this->isGranted('ROLE_USER') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            return $this->redirectToRoute('app_shop');
        }

        $customerCount = 0;
        $orderCount = 0;
        $productCount = 0;
        $statusCounts = [];
        $recentOrders = [];
        $recentProducts = [];
        $totalRevenue = 0.0;

        $customerCount = $customerRepo->count([]);
        $orderCount = $orderRepo->count([]);
        $productCount = $productRepo->count([]);

        $statusCounts = $orderRepo->getStatusCounts();
        $recentOrders = $orderRepo->findRecent(6);
        $recentProducts = $productRepo->findRecent(6);
        $totalRevenue = $orderRepo->getTotalRevenue();

        return $this->render('dashboard/index.html.twig', [
            'customerCount' => $customerCount,
            'orderCount' => $orderCount,
            'productCount' => $productCount,
            'statusCounts' => $statusCounts,
            'recentOrders' => $recentOrders,
            'recentProducts' => $recentProducts,
            'totalRevenue' => $totalRevenue,
        ]);
    }
}
