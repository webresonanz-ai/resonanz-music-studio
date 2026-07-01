<?php

namespace App\Http\Controllers\Trms;

use App\Core\Mail;
use App\Core\TicketPdfGenerator;
use App\Models\ConcertAudience;

class ConcertAudienceController
{
    private ConcertAudience $model;

    public function __construct()
    {
        $this->model = new ConcertAudience();
    }

    public function index(): void
    {
        header('Content-Type: application/json');

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 10)));

        $result = $this->model->paginate($perPage, $page);

        echo json_encode([
            'data' => $result['items'],
            'total' => $result['total'],
            'per_page' => $result['per_page'],
            'current_page' => $result['current_page'],
            'last_page' => $result['last_page'],
        ]);
    }

    public function store(): void
    {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $required = ['name', 'email', 'phone', 'concert_title', 'ticket_quantity'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(422);
                echo json_encode(['error' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
                return;
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['error' => 'A valid email address is required']);
            return;
        }

        try {
            $id = $this->model->create([
                'program_id' => 'trms',
                'name' => trim($data['name']),
                'email' => trim($data['email']),
                'phone' => trim($data['phone']),
                'concert_title' => trim($data['concert_title']),
                'ticket_quantity' => 1,
                'notes' => trim($data['notes'] ?? 'Guest')
            ]);
        } catch (\Throwable $error) {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to save registration. Please check the database setup.']);
            return;
        }

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Registration submitted successfully',
            'id' => $id,
            'ticket_pdf_url' => '/api/trms/concert/ticket/' . $id
        ]);

        // Merge the inserted ID so the QR code contains the correct record ID
        $registrationData = array_merge($data, ['id' => $id]);

        // Generate PDF once and reuse it for the email attachment
        $pdfContent = $this->generateTicketPdf($registrationData);
        $this->sendRegistrationEmail($registrationData, $pdfContent);
    }

    private function sendRegistrationEmail(array $data, ?string $pdfContent): void
    {
        $to             = trim($data['email']);
        $name           = trim($data['name']);
        $concertTitle   = trim($data['concert_title']);
        $notes          = trim($data['notes'] ?? 'Guest');
        $qty            = (int) ($data['ticket_quantity'] ?? 1);
        $subject        = "Concert Ticket – {$concertTitle}";

        // ── Plain-text version ─────────────────────────────────────────────
        $textBody = "Dear {$name},\n\n" .
                    "Thank you for registering for \"{$concertTitle}\".\n\n" .
                    "Registration Details:\n" .
                    "  Name            : {$name}\n" .
                    "  Email           : {$data['email']}\n" .
                    "  Phone           : {$data['phone']}\n" .
                    "  Ticket Quantity : {$qty}\n" .
                    "  Notes           : {$notes}\n\n" .
                    "Your ticket (PDF) is attached to this email.\n" .
                    "Please present it at the entrance — the QR code will be scanned.\n\n" .
                    "We look forward to seeing you at the event!\n\n" .
                    "Best regards,\n" .
                    "Resonanz Music Studio";

        // ── HTML version ───────────────────────────────────────────────────
        $htmlBody = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
        <body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;color:#222;">
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:32px 0;">
            <tr><td align="center">
              <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);">

                <!-- Header -->
                <tr>
                  <td style="background:#1a1a2e;padding:28px 32px;text-align:center;">
                    <h1 style="margin:0;color:#e2b96f;font-size:22px;letter-spacing:2px;">RESONANZ MUSIC STUDIO</h1>
                    <p style="margin:6px 0 0;color:#aaa;font-size:13px;letter-spacing:1px;">CONCERT TICKET CONFIRMATION</p>
                  </td>
                </tr>

                <!-- Body -->
                <tr>
                  <td style="padding:32px;">
                    <p style="font-size:16px;">Dear <strong>{$name}</strong>,</p>
                    <p style="font-size:14px;line-height:1.6;color:#444;">
                      Thank you for registering for <strong>&ldquo;{$concertTitle}&rdquo;</strong>.
                      Your ticket is attached as a PDF to this email.
                      Please present it at the entrance &mdash; the QR code will be scanned for verification.
                    </p>

                    <!-- Details table -->
                    <table width="100%" cellpadding="8" cellspacing="0" style="margin:24px 0;border-collapse:collapse;font-size:14px;">
                      <tr style="background:#f9f9f9;">
                        <td style="border:1px solid #e0e0e0;color:#888;width:40%;">Name</td>
                        <td style="border:1px solid #e0e0e0;"><strong>{$name}</strong></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #e0e0e0;color:#888;">Email</td>
                        <td style="border:1px solid #e0e0e0;">{$data['email']}</td>
                      </tr>
                      <tr style="background:#f9f9f9;">
                        <td style="border:1px solid #e0e0e0;color:#888;">Phone</td>
                        <td style="border:1px solid #e0e0e0;">{$data['phone']}</td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #e0e0e0;color:#888;">Concert</td>
                        <td style="border:1px solid #e0e0e0;"><strong>{$concertTitle}</strong></td>
                      </tr>
                      <tr style="background:#f9f9f9;">
                        <td style="border:1px solid #e0e0e0;color:#888;">Ticket Qty</td>
                        <td style="border:1px solid #e0e0e0;">{$qty}</td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #e0e0e0;color:#888;">Notes</td>
                        <td style="border:1px solid #e0e0e0;">{$notes}</td>
                      </tr>
                    </table>

                    <p style="font-size:13px;color:#666;">
                      We look forward to seeing you at the event!
                    </p>
                  </td>
                </tr>

                <!-- Footer -->
                <tr>
                  <td style="background:#1a1a2e;padding:16px 32px;text-align:center;">
                    <p style="margin:0;color:#888;font-size:12px;">&copy; Resonanz Music Studio. All rights reserved.</p>
                  </td>
                </tr>

              </table>
            </td></tr>
          </table>
        </body>
        </html>
        HTML;

        // Filename: e.g. "ticket_John_Doe.pdf"
        $safeName = 'ticket_' . preg_replace('/\s+/', '_', $name) . '.pdf';

        $mail = new Mail($to, $subject, $textBody, $pdfContent, $safeName, $htmlBody);
        $mail->send();
    }

    private function generateTicketPdf(array $data): ?string
    {
        try {
            return (new TicketPdfGenerator())->generate($data);
        } catch (\Throwable $e) {
            // Log but don't crash the registration flow
            error_log('TicketPdfGenerator error: ' . $e->getMessage());
            return null;
        }
    }

    public function downloadTicket(int $id): void
    {
        $audience = $this->model->find($id);
        
        if (!$audience) {
            http_response_code(404);
            echo json_encode(['error' => 'Registration not found']);
            return;
        }

        $pdfContent = (new TicketPdfGenerator())->generate($audience);
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="ticket_' . $id . '.pdf"');
        header('Content-Length: ' . strlen($pdfContent));
        echo $pdfContent;
    }
}
