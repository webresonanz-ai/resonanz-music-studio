<?php

namespace App\Http\Controllers\Trms;

class UploadController
{
    // Saves to backend/public/uploads/banners/ — the PHP doc root on Hostinger
    // is backend/public/, so this folder is directly web-accessible at /uploads/banners/
    private const UPLOAD_DIR = __DIR__ . '/../../../../public/uploads/banners/';
    private const ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    private const MAX_SIZE = 3 * 1024 * 1024; // 3 MB

    public function bannerUpload(): void
    {
        header('Content-Type: application/json');

        if (empty($_FILES['banner']) || $_FILES['banner']['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES['banner']['error'] ?? -1;
            http_response_code(422);
            echo json_encode(['error' => 'No file uploaded or upload error: ' . $this->uploadErrorMessage($errCode)]);
            return;
        }

        $file = $_FILES['banner'];

        // Validate MIME type using finfo (not just the extension)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_TYPES, true)) {
            http_response_code(422);
            echo json_encode(['error' => 'Only JPEG, PNG, and WebP images are allowed.']);
            return;
        }

        // Validate file size
        if ($file['size'] > self::MAX_SIZE) {
            http_response_code(422);
            echo json_encode(['error' => 'File size exceeds the 3 MB limit.']);
            return;
        }

        // Create upload directory if it doesn't exist
        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }

        // Generate a unique filename with the correct extension
        $ext = match ($mime) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
        $filename = 'banner_' . uniqid('', true) . '.' . $ext;
        $destination = self::UPLOAD_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save the uploaded file.']);
            return;
        }

        // Build the public URL
        $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost:8000', '/');
        $publicUrl = $appUrl . '/backend/public/uploads/banners/' . $filename;

        echo json_encode([
            'url' => $publicUrl,
            'debug_saved_to' => $destination,
            'debug_doc_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
        ]);
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File too large.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension.',
            default => 'Unknown upload error.',
        };
    }
}
