<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductImageUploader
{
    private readonly string $targetDirectory;

    public function __construct(string $projectDir)
    {
        $this->targetDirectory = $projectDir.'/public/uploads/products';
        if (!is_dir($this->targetDirectory)) {
            mkdir($this->targetDirectory, 0755, true);
        }
    }

    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($originalFilename));
        $safeFilename = trim($safeFilename, '-') ?: 'product';
        $extension = $file->guessExtension() ?? $file->getClientOriginalExtension() ?? 'jpg';
        $newFilename = sprintf('%s-%s.%s', $safeFilename, uniqid(), $extension);

        try {
            $file->move($this->targetDirectory, $newFilename);
        } catch (FileException $e) {
            throw new FileException('Could not upload the product image.');
        }

        return $newFilename;
    }

    public function remove(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }

        $path = $this->targetDirectory.'/'.$filename;
        if (is_file($path)) {
            unlink($path);
        }
    }
}
