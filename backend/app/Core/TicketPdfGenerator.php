<?php

namespace App\Core;

use TCPDF;

class TicketPdfGenerator
{
    private string $templatePath;
    private QrCodeGenerator $qrGenerator;

    // ── Layout constants (mm on A4 210×297) ──────────────────────────────────
    // Adjust these values to match where the name / QR areas are on your template.

    /** QR code: top-left X position (mm) */
    private const QR_X = 79;
    /** QR code: top-left Y position (mm) */
    private const QR_Y = 230;
    /** QR code: width & height (mm) */
    private const QR_SIZE = 52;

    /** Guest name: X position (mm) */
    private const NAME_X = 20;
    /** Guest name: Y position (mm) */
    private const NAME_Y = 222;

    // ─────────────────────────────────────────────────────────────────────────

    public function __construct()
    {
        $this->templatePath = __DIR__ . '/../public/assets/images/invitation_ticket-soli_deo_gratia.png';
        $this->qrGenerator  = new QrCodeGenerator();
    }

    public function generate(array $data): string
    {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Resonanz Music Studio');
        $pdf->SetAuthor('Resonanz Music Studio');
        $pdf->SetTitle('Concert Ticket – ' . ($data['concert_title'] ?? ''));
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        // ── 1. Background template ────────────────────────────────────────────
        if (file_exists($this->templatePath)) {
            $pdf->Image($this->templatePath, 0, 0, 210, 297, 'PNG');
        }

        // ── 2. QR code ────────────────────────────────────────────────────────
        // Encode attendee identity so it can be scanned at the door
        $qrPayload  = $this->buildQrPayload($data);
        $qrPng      = $this->qrGenerator->generate($qrPayload, 300);

        $tempQrPath = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
        file_put_contents($tempQrPath, $qrPng);

        $pdf->Image($tempQrPath, self::QR_X, self::QR_Y, self::QR_SIZE, self::QR_SIZE, 'PNG');
        unlink($tempQrPath);

        // ── 3. Guest name ─────────────────────────────────────────────────────
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(255, 255, 255); // white — adjust if template bg is light
        $pdf->Text(self::NAME_X, self::NAME_Y, strtoupper($data['name']));

        return $pdf->Output('ticket.pdf', 'S');
    }

    public function generateAndSave(array $data, string $outputPath): bool
    {
        return file_put_contents($outputPath, $this->generate($data)) !== false;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Build a compact JSON string that gets encoded into the QR code.
     * Keep it short — QR codes become denser with more data.
     */
    private function buildQrPayload(array $data): string
    {
        return json_encode([
            'id'      => $data['id']            ?? null,
            'name'    => $data['name']           ?? '',
            'email'   => $data['email']          ?? '',
            'concert' => $data['concert_title']  ?? '',
            'qty'     => $data['ticket_quantity'] ?? 1,
            'ts'      => time(),
        ], JSON_UNESCAPED_UNICODE);
    }
}
