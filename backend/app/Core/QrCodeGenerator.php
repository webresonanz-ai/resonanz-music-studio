<?php

namespace App\Core;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Color\Color;

class QrCodeGenerator
{
    public function generate(string $data, int $size = 256): string
    {
        $qrCode = new QrCode(
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 0,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return $result->getString();
    }

    public function generateAndSave(string $data, string $filepath, int $size = 256): bool
    {
        $content = $this->generate($data, $size);
        return file_put_contents($filepath, $content) !== false;
    }
}