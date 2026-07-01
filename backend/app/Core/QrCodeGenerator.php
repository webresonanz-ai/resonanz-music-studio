<?php

namespace App\Core;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Color\Color;

class QrCodeGenerator
{
    public function generate(string $data, int $size = 256): string
    {
        // endroid/qr-code v4 uses named constructors and immutable value objects
        $qrCode = QrCode::create($data)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->setSize($size)
            ->setMargin(0)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

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