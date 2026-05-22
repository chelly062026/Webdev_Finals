<?php

namespace App\Controller;

use App\Repository\StockLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stock_log')]
final class StockLogController extends AbstractController
{
    #[Route(name: 'app_stock_log_index', methods: ['GET'])]
    public function index(StockLogRepository $stockLogRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('stock_log/index.html.twig', [
            'logs' => $stockLogRepository->findAllOrdered(),
        ]);
    }
}
