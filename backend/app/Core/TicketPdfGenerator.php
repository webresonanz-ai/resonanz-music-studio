<?php

namespace App\Core;

use TCPDF;

/**
 * Generates a PDF invitation ticket by overlaying a guest name and QR code
 * on top of the concert invitation template image.
 *
 * Template: backend/public/assets/images/invitation_ticket-soli_deo_gratia.png
 * Image size: 1054 × 1492 px  →  rendered as A4 (210 × 297 mm) in the PDF
 *
 * Coordinate reference (all values in mm, origin = top-left corner of page):
 *   px → mm  :  x_mm = px * (210 / 1054)  ≈  px * 0.1992
 *               y_mm = px * (297 / 1492)  ≈  px * 0.1991
 *
 * ┌──────────────────────────────────────────────────┐
 * │  To reposition elements, change the constants   │
 * │  NAME_X / NAME_Y / QR_X / QR_Y / QR_SIZE below │
 * └──────────────────────────────────────────────────┘
 */
class TicketPdfGenerator
{
    private string $templatePath;
    private QrCodeGenerator $qrGenerator;

    // ── Layout constants (mm on A4 210 × 297 mm) ─────────────────────────────

    /** Guest name — left edge X (mm) */
    private const NAME_X = 55;

    /** Guest name — baseline Y (mm).  Tweak until it lands in the name field. */
    private const NAME_Y = 228;

    /** Guest name — cell width (mm); text is centred within this width */
    private const NAME_W = 100;

    /** Guest name — font size (pt) */
    private const NAME_FONT_SIZE = 18;

    /** QR code — top-left X (mm) */
    private const QR_X = 79;

    /** QR code — top-left Y (mm) */
    private const QR_Y = 158;

    /** QR code — width = height (mm) */
    private const QR_SIZE = 50;

    // ─────────────────────────────────────────────────────────────────────────

    public function __construct()
    {
        $this->templatePath = __DIR__ . '/../../public/assets/images/invitation_ticket-soli_deo_gratia.png';
        $this->qrGenerator = new QrCodeGenerator();
    }

    /**
     * Generate the ticket PDF and return it as a binary string.
     *
     * @param  array $data  Must contain 'name'; optionally 'id', 'email',
     *                      'concert_title', 'ticket_quantity'.
     * @return string  Raw PDF binary.
     * @throws \RuntimeException if template image is missing.
     */
    public function generate(array $data): string
    {
        if (!file_exists($this->templatePath)) {
            throw new \RuntimeException(
                'Ticket template not found: ' . $this->templatePath
            );
        }

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Resonanz Music Studio');
        $pdf->SetAuthor('Resonanz Music Studio');
        $pdf->SetTitle('Invitation Ticket – Soli Deo Gratia');
        $pdf->SetSubject($data['concert_title'] ?? 'Concert Ticket');
        $pdf->SetKeywords('ticket, resonanz, concert');
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        // ── 1. Full-page template background ─────────────────────────────────
        $pdf->Image($this->templatePath, 0, 0, 210, 297, 'PNG');

        // ── 2. QR code ────────────────────────────────────────────────────────
        // Use the stored qr_code identifier if available, otherwise fall back to a JSON payload
        $qrPayload = !empty($data['qr_code']) ? $data['qr_code'] : $this->buildQrPayload($data);
        $qrPng = $this->qrGenerator->generate($qrPayload, 400);

        $tempQrPath = tempnam(sys_get_temp_dir(), 'trms_qr_') . '.png';
        file_put_contents($tempQrPath, $qrPng);

        try {
            $pdf->Image($tempQrPath, self::QR_X, self::QR_Y, self::QR_SIZE, self::QR_SIZE, 'PNG');
        } finally {
            @unlink($tempQrPath);
        }

        // ── 3. Guest name ─────────────────────────────────────────────────────
        $guestName = mb_strtoupper(trim($data['name'] ?? ''), 'UTF-8');

        $pdf->SetFont('helvetica', 'B', self::NAME_FONT_SIZE);
        $pdf->SetTextColor(0, 0, 0); // black
        $pdf->SetXY(self::NAME_X, self::NAME_Y);
        $pdf->Cell(self::NAME_W, 8, $guestName, 0, 0, 'C');

        return $pdf->Output('ticket.pdf', 'S');
    }

    /**
     * Generate the PDF and write it directly to disk.
     */
    public function generateAndSave(array $data, string $outputPath): bool
    {
        return file_put_contents($outputPath, $this->generate($data)) !== false;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Build a compact JSON payload for the QR code.
     * Kept intentionally short to keep the QR code density low.
     */
    private function buildQrPayload(array $data): string
    {
        return json_encode([
            'id' => $data['id'] ?? null,
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'concert' => $data['concert_title'] ?? '',
            'qty' => $data['ticket_quantity'] ?? 1,
            'ts' => time(),
        ], JSON_UNESCAPED_UNICODE);
    }
}
