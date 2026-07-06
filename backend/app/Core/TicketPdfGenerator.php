<?php

namespace App\Core;

use TCPDF;

/**
 * Generates a PDF ticket by overlaying a QR code (and optionally a guest name)
 * on top of the appropriate concert ticket template image.
 *
 * Templates:
 *   Guest      : backend/public/assets/images/guest_ticket-soli_deo_gratia.png
 *   Invitation : backend/public/assets/images/invitation_ticket-soli_deo_gratia.png
 *
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
    private string $guestTemplatePath;
    private string $invitationTemplatePath;
    private QrCodeGenerator $qrGenerator;

    // ── Layout constants (mm on A4 210 × 297 mm) ─────────────────────────────

    /** Guest name — left edge X (mm) */
    private const NAME_X = 5;

    /** Guest name — baseline Y (mm).  Tweak until it lands in the name field. */
    private const NAME_Y = 231;

    /** Guest name — cell width (mm); text is centred within this width */
    private const NAME_W = 100;

    /** Guest name — font size (pt) */
    private const NAME_FONT_SIZE = 18;

    /** QR code — top-left X (mm) */
    private const QR_X = 22;

    /** QR code — top-left Y (mm) */
    private const QR_Y = 165;

    /** QR code — width = height (mm) */
    private const QR_SIZE = 62;

    // ─────────────────────────────────────────────────────────────────────────

    public function __construct()
    {
        $this->guestTemplatePath = __DIR__ . '/../../public/assets/images/guest_ticket-soli_deo_gratia.png';
        $this->invitationTemplatePath = __DIR__ . '/../../public/assets/images/invitation_ticket-soli_deo_gratia.png';
        $this->qrGenerator = new QrCodeGenerator();
    }

    /**
     * Generate the ticket PDF and return it as a binary string.
     *
     * @param  array $data  Must contain 'name' (for Guest tickets); optionally
     *                      'notes', 'id', 'email', 'concert_title', 'ticket_quantity'.
     *                      When notes === 'Invitation' the invitation template is used
     *                      and no name is printed on the ticket.
     * @return string  Raw PDF binary.
     * @throws \RuntimeException if the required template image is missing.
     */
    public function generate(array $data): string
    {
        $isInvitation = (trim($data['notes'] ?? '') === 'Invitation');

        $templatePath = $isInvitation
            ? $this->invitationTemplatePath
            : $this->guestTemplatePath;

        if (!file_exists($templatePath)) {
            throw new \RuntimeException(
                'Ticket template not found: ' . $templatePath
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
        $pdf->Image($templatePath, 0, 0, 210, 297, 'PNG');

        // ── 2. QR code ────────────────────────────────────────────────────────
        $qrPayload = !empty($data['qr_code']) ? $data['qr_code'] : $this->buildQrPayload($data);
        $qrPng = $this->qrGenerator->generate($qrPayload, 400);

        $tempQrPath = tempnam(sys_get_temp_dir(), 'trms_qr_') . '.png';
        file_put_contents($tempQrPath, $qrPng);

        try {
            $pdf->Image($tempQrPath, self::QR_X, self::QR_Y, self::QR_SIZE, self::QR_SIZE, 'PNG');
        } finally {
            @unlink($tempQrPath);
        }

        // ── 3. Guest name ────────────────────────────────
        $guestName = mb_strtoupper(trim($data['name'] ?? ''), 'UTF-8');

        $pdf->SetFont('helvetica', 'B', self::NAME_FONT_SIZE);
        $pdf->SetTextColor(0, 0, 0);

        $nameLen = mb_strlen($guestName, 'UTF-8');

        if ($nameLen > 23) {
            $splitPos = $this->findNearestSpaceSplit($guestName, 23);

            $line1 = mb_substr($guestName, 0, $splitPos, 'UTF-8');
            $line2 = trim(mb_substr($guestName, $splitPos + 1, null, 'UTF-8'));

            $y = self::NAME_Y - 3;
            $pdf->SetXY(self::NAME_X, $y);
            $pdf->Cell(self::NAME_W, 8, $line1, 0, 2, 'C');
            $pdf->SetX(self::NAME_X);
            $pdf->Cell(self::NAME_W, 8, $line2, 0, 0, 'C');
        } else {
            $pdf->SetXY(self::NAME_X, self::NAME_Y);
            $pdf->Cell(self::NAME_W, 8, $guestName, 0, 0, 'C');
        }

        return $pdf->Output('ticket.pdf', 'S');
    }

    /**
     * Generate a single multi-page PDF containing one ticket page per entry.
     *
     * @param  array[] $rows  Each element is a $data array accepted by generate().
     *                        All rows should share the same notes/type so the correct
     *                        template is used consistently.
     * @return string  Raw PDF binary.
     */
    public function generateMulti(array $rows): string
    {
        if (empty($rows)) {
            throw new \InvalidArgumentException('generateMulti requires at least one row.');
        }

        // Single ticket — no need for the multi-page path
        if (count($rows) === 1) {
            return $this->generate($rows[0]);
        }

        $isInvitation = (trim($rows[0]['notes'] ?? '') === 'Invitation');
        $templatePath = $isInvitation
            ? $this->invitationTemplatePath
            : $this->guestTemplatePath;

        if (!file_exists($templatePath)) {
            throw new \RuntimeException('Ticket template not found: ' . $templatePath);
        }

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Resonanz Music Studio');
        $pdf->SetAuthor('Resonanz Music Studio');
        $pdf->SetTitle('Tickets – ' . ($rows[0]['concert_title'] ?? 'Concert'));
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        foreach ($rows as $data) {
            $pdf->AddPage();

            // Background
            $pdf->Image($templatePath, 0, 0, 210, 297, 'PNG');

            // QR code
            $qrPayload = !empty($data['qr_code']) ? $data['qr_code'] : $this->buildQrPayload($data);
            $qrPng     = $this->qrGenerator->generate($qrPayload, 400);

            $tempQrPath = tempnam(sys_get_temp_dir(), 'trms_qr_') . '.png';
            file_put_contents($tempQrPath, $qrPng);

            try {
                $pdf->Image($tempQrPath, self::QR_X, self::QR_Y, self::QR_SIZE, self::QR_SIZE, 'PNG');
            } finally {
                @unlink($tempQrPath);
            }

            // Name (Guest only)
            if (!$isInvitation) {
                $guestName = mb_strtoupper(trim($data['name'] ?? ''), 'UTF-8');
                $pdf->SetFont('helvetica', 'B', self::NAME_FONT_SIZE);
                $pdf->SetTextColor(0, 0, 0);

                if (mb_strlen($guestName, 'UTF-8') > 23) {
                    $splitPos = $this->findNearestSpaceSplit($guestName, 23);
                    $line1    = mb_substr($guestName, 0, $splitPos, 'UTF-8');
                    $line2    = trim(mb_substr($guestName, $splitPos + 1, null, 'UTF-8'));

                    $pdf->SetXY(self::NAME_X, self::NAME_Y - 3);
                    $pdf->Cell(self::NAME_W, 8, $line1, 0, 2, 'C');
                    $pdf->SetX(self::NAME_X);
                    $pdf->Cell(self::NAME_W, 8, $line2, 0, 0, 'C');
                } else {
                    $pdf->SetXY(self::NAME_X, self::NAME_Y);
                    $pdf->Cell(self::NAME_W, 8, $guestName, 0, 0, 'C');
                }
            }
        }

        return $pdf->Output('tickets.pdf', 'S');
    }


    public function generateAndSave(array $data, string $outputPath): bool
    {
        return file_put_contents($outputPath, $this->generate($data)) !== false;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Find the nearest space character to the given position and return its index.
     * Used to split long guest names across two lines without cutting words badly.
     */
    private function findNearestSpaceSplit(string $text, int $position): int
    {
        $len = mb_strlen($text, 'UTF-8');
        if ($position >= $len) {
            return $len;
        }

        $before = mb_strrpos(mb_substr($text, 0, $position + 1, 'UTF-8'), ' ', 0, 'UTF-8');
        $after = mb_strpos($text, ' ', $position, 'UTF-8');

        if ($before !== false && $after !== false) {
            return ($position - $before <= $after - $position) ? $before : $after;
        }

        if ($before !== false) {
            return $before;
        }

        if ($after !== false) {
            return $after;
        }

        return $position;
    }

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
