<?php

namespace App\Http\Controllers\Trms;

use App\Core\Mail;
use App\Core\TicketPdfGenerator;
use App\Models\ConcertAudience;
use App\Models\Schedule;

class ConcertAudienceController
{
    private ConcertAudience $model;
    private Schedule $scheduleModel;

    public function __construct()
    {
        $this->model = new ConcertAudience();
        $this->scheduleModel = new Schedule();
    }

public function index(): void
     {
         header('Content-Type: application/json');

         $page = max(1, (int) ($_GET['page'] ?? 1));
         $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 10)));
         $search = trim($_GET['search'] ?? '');

         $result = $this->model->paginate($perPage, $page, $search);

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

        // ── Resolve schedule and enforce open-register + capacity ──────────
        $scheduleId = !empty($data['schedule_id']) ? (int) $data['schedule_id'] : null;
        $schedule   = $scheduleId ? $this->scheduleModel->find($scheduleId) : null;

        if ($schedule) {
            // Check registration is open
            if (empty($schedule['is_open_register'])) {
                http_response_code(409);
                echo json_encode(['error' => 'Registration for this concert is currently closed.']);
                return;
            }

            // Check per-concert capacity
            if (!empty($schedule['audience_capacity'])) {
                $registered = $this->model->countBySchedule($scheduleId);
                if ($registered >= (int) $schedule['audience_capacity']) {
                    http_response_code(409);
                    echo json_encode(['error' => 'We\'re sorry, the maximum capacity for this concert has been reached. Registration is now closed.']);
                    return;
                }
            }
        } else {
            // Fallback: no schedule_id sent — use legacy global cap of 600
            $maxCapacity = 600;
            if ($this->model->count() >= $maxCapacity) {
                http_response_code(409);
                echo json_encode(['error' => 'We\'re sorry, the maximum capacity for this concert has been reached. Registration is now closed.']);
                return;
            }
        }

        try {
            $createData = [
                'program_id'    => 'trms',
                'schedule_id'   => $scheduleId,
                'name'          => trim($data['name']),
                'email'         => trim($data['email']),
                'phone'         => trim($data['phone']),
                'concert_title' => trim($data['concert_title']),
                'ticket_quantity' => 1,
                'notes'         => trim($data['notes'] ?? 'Guest')
            ];

            if (!empty($data['created_at'])) {
                $createData['created_at'] = $data['created_at'];
            }

            $id = $this->model->create($createData);
        } catch (\Throwable $error) {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to save registration. Please check the database setup.']);
            return;
        }

        // Generate the unique QR code string from the schedule concert_code and persist it.
        $concertCode = trim((string) ($schedule['concert_code'] ?? ''));
        $qrCode = ConcertAudience::buildQrCode($concertCode, $id);
        $this->model->updateQrCode($id, $qrCode);

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Registration submitted successfully',
            'id' => $id,
            'qr_code' => $qrCode,
            'ticket_pdf_url' => '/api/trms/concert/ticket/' . $id
        ]);

        // Merge the inserted ID and qr_code so the PDF/email have full data
        $registrationData = array_merge($data, ['id' => $id, 'qr_code' => $qrCode]);

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

    public function show(string $id): void
    {
        header('Content-Type: application/json');
        $id = (int) $id;

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid ID']);
            return;
        }

        $audience = $this->model->find($id);

        if (!$audience) {
            http_response_code(404);
            echo json_encode(['error' => 'Registration not found']);
            return;
        }

        echo json_encode(['data' => $audience]);
    }

    public function update(string $id): void
    {
        header('Content-Type: application/json');
        $id = (int) $id;

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid ID']);
            return;
        }

        $audience = $this->model->find($id);
        if (!$audience) {
            http_response_code(404);
            echo json_encode(['error' => 'Registration not found']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $allowed = ['name', 'email', 'phone', 'concert_title', 'ticket_quantity', 'notes'];
        $updateData = [];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = trim((string) $data[$field]);
            }
        }

        if (isset($updateData['email']) && !filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['error' => 'A valid email address is required']);
            return;
        }

        if (empty($updateData)) {
            http_response_code(422);
            echo json_encode(['error' => 'No valid fields provided for update']);
            return;
        }

        $this->model->update($id, $updateData);
        $updated = $this->model->find($id);

        echo json_encode(['success' => true, 'data' => $updated]);
    }

    public function destroy(string $id): void
    {
        header('Content-Type: application/json');
        $id = (int) $id;

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid ID']);
            return;
        }

        $audience = $this->model->find($id);
        if (!$audience) {
            http_response_code(404);
            echo json_encode(['error' => 'Registration not found']);
            return;
        }

        $this->model->delete($id);
        echo json_encode(['success' => true, 'message' => 'Registration deleted successfully']);
    }

    public function resendEmail(string $id): void
    {
        header('Content-Type: application/json');
        $id = (int) $id;

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid ID']);
            return;
        }

        $audience = $this->model->find($id);
        if (!$audience) {
            http_response_code(404);
            echo json_encode(['error' => 'Registration not found']);
            return;
        }

        try {
            $pdfContent = $this->generateTicketPdf($audience);
            $this->sendRegistrationEmail($audience, $pdfContent);
            echo json_encode(['success' => true, 'message' => 'Email resent successfully']);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to resend email: ' . $e->getMessage()]);
        }
    }

    /**
     * POST /api/trms/concert/scan
     *
     * Accepts { "qr_code": "SDG_42_..." } OR { "reg_number": "42" }
     * Looks up the registration, marks attended_at on first scan, and returns the record.
     *
     * Response shape:
     *   { success: true, already_attended: false, data: {...} }  — first check-in
     *   { success: true, already_attended: true,  data: {...} }  — duplicate scan
     */
    public function scan(): void
    {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $qrCode    = trim($data['qr_code'] ?? '');
        $regNumber = trim($data['reg_number'] ?? '');

        if ($qrCode === '' && $regNumber === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Provide either qr_code or reg_number']);
            return;
        }

        if ($qrCode !== '') {
            $audience = $this->model->findByQrCode($qrCode);
        } else {
            $id = (int) $regNumber;
            $audience = $id > 0 ? $this->model->find($id) : false;
        }

        if (!$audience) {
            http_response_code(404);
            echo json_encode(['error' => 'Registration not found']);
            return;
        }

        // Already checked in before this scan?
        $alreadyAttended = !empty($audience['attended_at']);

        if (!$alreadyAttended) {
            $localAttendedAt = trim((string) ($data['attended_at'] ?? ''));

            if ($localAttendedAt !== '') {
                $this->model->markAttended((int) $audience['id'], $localAttendedAt);
            } else {
                $this->model->markAttended((int) $audience['id']);
            }

            $audience = $this->model->find((int) $audience['id']);
        }

        echo json_encode([
            'success'          => true,
            'already_attended' => $alreadyAttended,
            'data'             => $audience,
        ]);
    }

    public function downloadTicket(string $id): void
    {
        $id = (int) $id;

        if ($id <= 0) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid ticket ID']);
            return;
        }

        $audience = $this->model->find($id);

        if (!$audience) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Registration not found']);
            return;
        }

        try {
            $pdfContent = (new TicketPdfGenerator())->generate($audience);
        } catch (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to generate ticket PDF']);
            error_log('TicketPdfGenerator error: ' . $e->getMessage());
            return;
        }

        $safeName = 'ticket_' . preg_replace('/[^a-z0-9_\-]/i', '_', $audience['name'] ?? $id) . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $safeName . '"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: private, no-cache');
        echo $pdfContent;
    }
}
