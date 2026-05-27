<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\ProductImageUploader;
use App\Entity\StockLog;
use App\Repository\StockLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product')]
final class ProductController extends AbstractController
{
    #[Route('/browse', name: 'app_product_browse', methods: ['GET'])]
    public function browse(ProductRepository $productRepository): Response
    {
        return $this->render('product/browse.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();
        $lowStockThreshold = 5;

        $lowStockProducts = array_filter(
            $products,
            fn (Product $product): bool => $product->getStock() !== null && $product->getStock() <= $lowStockThreshold
        );

        if (count($lowStockProducts) > 0) {
            $this->addFlash(
                'warning',
                sprintf(
                    'Low stock alert: %d product(s) are at or below %d units.',
                    count($lowStockProducts),
                    $lowStockThreshold
                )
            );
        }

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'lowStockThreshold' => $lowStockThreshold,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ProductImageUploader $imageUploader,
    ): Response {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product, ['require_image' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $product, $imageUploader);
            $product->setCreatedBy($this->getUser());
            $entityManager->persist($product);
            $entityManager->flush();

            if ($product->getStock() !== null) {
                $this->appendStockLog($entityManager, $product, null, $product->getStock(), 'STOCK_CREATE');
                $entityManager->flush();
            }

            $this->addFlash('success', 'Product created successfully!');

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager,
        ProductImageUploader $imageUploader,
    ): Response {
        $this->checkProductOwnership($product);

        $originalStock = $product->getStock();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $product, $imageUploader);
            $entityManager->flush();

            if ($originalStock !== $product->getStock()) {
                $this->appendStockLog($entityManager, $product, $originalStock, $product->getStock(), 'STOCK_UPDATE');
                $entityManager->flush();
            }

            $this->addFlash('success', 'Product updated successfully!');

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager,
        ProductImageUploader $imageUploader,
        StockLogRepository $stockLogRepository,
    ): Response {
        $this->checkProductOwnership($product);

        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            $imageUploader->remove($product->getImage());
            $stockLogRepository->deleteByProduct($product);
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', 'Product deleted successfully!');
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    private function handleImageUpload(
        FormInterface $form,
        Product $product,
        ProductImageUploader $imageUploader,
    ): void {
        /** @var UploadedFile|null $imageFile */
        $imageFile = $form->get('imageFile')->getData();
        if ($imageFile === null) {
            return;
        }

        $imageUploader->remove($product->getImage());
        $product->setImage($imageUploader->upload($imageFile));
    }

    private function appendStockLog(
        EntityManagerInterface $entityManager,
        Product $product,
        ?int $quantityBefore,
        ?int $quantityAfter,
        string $action,
    ): void {
        if ($quantityBefore === $quantityAfter) {
            return;
        }

        $user = $this->getUser();
        $role = $this->isGranted('ROLE_ADMIN') ? 'ROLE_ADMIN' : ($this->isGranted('ROLE_STAFF') ? 'ROLE_STAFF' : null);
        $note = $role === 'ROLE_ADMIN'
            ? 'Admin stock update'
            : ($role === 'ROLE_STAFF' ? 'Staff stock update' : 'Stock update');

        $stockLog = new StockLog();
        $stockLog
            ->setProduct($product)
            ->setUser($user)
            ->setUsername($user?->getUserIdentifier())
            ->setRole($role)
            ->setAction($action)
            ->setQuantityBefore($quantityBefore)
            ->setQuantityAfter($quantityAfter)
            ->setChangeAmount($quantityAfter !== null && $quantityBefore !== null ? $quantityAfter - $quantityBefore : null)
            ->setNote($note);

        $entityManager->persist($stockLog);
    }

    private function checkProductOwnership(Product $product): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        $createdBy = $product->getCreatedBy();

        if ($this->isGranted('ROLE_STAFF') && $createdBy !== null && in_array('ROLE_ADMIN', $createdBy->getRoles(), true)) {
            return;
        }

        if ($createdBy !== $this->getUser()) {
            throw $this->createAccessDeniedException('You do not have permission to access this product.');
        }
    }
}
