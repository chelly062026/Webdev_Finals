<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiUploadController extends AbstractController
{
    #[Route('/api/upload', name: 'api_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file) {
            return new JsonResponse(['error' => 'No file provided'], 400);
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        if (!is_dir($uploadsDir)) {
            @mkdir($uploadsDir, 0777, true);
        }

        try {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
            $newName = $safeName . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadsDir, $newName);

            $publicPath = '/api/uploads/' . $newName;

            return new JsonResponse(['ok' => true, 'path' => $publicPath], 201);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to save file', 'msg' => $e->getMessage()], 500);
        }
    }
}
