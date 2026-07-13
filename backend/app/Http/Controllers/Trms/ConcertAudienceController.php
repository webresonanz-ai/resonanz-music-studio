<?php

namespace App\Http\Controllers\Trms;

use App\Core\Mail;
use App\Core\TicketPdfGenerator;
use App\Models\ConcertAudience;
use App\Models\Schedule;
use App\Models\SeatHold;

class ConcertAudienceController
{
    private ConcertAudience $model;
    private Schedule $scheduleModel;
    private SeatHold $holdModel;

    public function __construct()
    {
        $this->model = new ConcertAudience();
        $this->scheduleModel = new Schedule();
        $this->holdModel = new SeatHold();
    }

    public function seats(string $scheduleId): void
    {
        header('Content-Type: application/json');
        $scheduleId = (int) $scheduleId;

        if ($scheduleId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid schedule ID']);
            return;
        }

        // Confirmed registrations
        $taken = $this->model->getTakenSeats($scheduleId);
        // Currently held (by any user, non-expired)
        $held  = $this->holdModel->getHeldSeats($scheduleId);

        // Merge and deduplicate
        $blocked = array_values(array_unique(array_merge($taken, $held)));

        echo json_encode(['data' => $blocked]);
    }

    public function index(): void
     {
         header('Content-Type: application/json');

         $page    = max(1, (int) ($_GET['page'] ?? 1));
         $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 10)));
         $search  = trim($_GET['search'] ?? '');
         $concert = trim($_GET['concert'] ?? '');
         $notes   = trim($_GET['notes'] ?? '');

         $result = $this->model->paginate($perPage, $page, $search, $concert, $notes);

        echo json_encode([
            'data' => $result['items'],
            'total' => $result['total'],
            'per_page' => $result['per_page'],
            'current_page' => $result['current_page'],
            'last_page' => $result['last_page'],
        ]);
    }

    /**
     * GET /api/trms/concert/audiences/concerts
     * Returns a list of distinct concert_title values for filter dropdowns.
     */
    public function concerts(): void
    {
        header('Content-Type: application/json');
        echo json_encode(['data' => $this->model->getDistinctConcerts()]);
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

            // Check per-concert capacity — only Guest tickets count toward the cap;
            // Invitation registrations are exempt from capacity limits.
            $incomingNotes = trim($data['notes'] ?? 'Guest');
            if (!empty($schedule['audience_capacity']) && $incomingNotes === 'Guest') {
                $registered = $this->model->countGuestBySchedule($scheduleId);
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

        // ── Duplicate registration check ───────────────────────────────────
        if ($scheduleId && $this->model->existsByNameEmailAndSchedule(
            trim($data['name']),
            trim($data['email']),
            $scheduleId
        )) {
            http_response_code(409);
            echo json_encode(['error' => 'You have already registered for this concert with the same name and email.']);
            return;
        }

        // ── Seat assignment enforcement ────────────────────────────────────
        $seatNumber = null;
        if ($schedule && !empty($schedule['is_seat_assign'])) {
            $seatNumber = trim($data['seat_number'] ?? '');
            if ($seatNumber === '') {
                http_response_code(422);
                echo json_encode(['error' => 'Please choose a seat before registering.']);
                return;
            }
            // Check seat is not already taken
            if ($this->model->isSeatTaken($scheduleId, $seatNumber)) {
                http_response_code(409);
                echo json_encode(['error' => 'Seat ' . $seatNumber . ' is already taken. Please choose another seat.']);
                return;
            }
        }

        $concertCode   = trim((string) ($schedule['concert_code'] ?? ''));
        $qty           = max(1, (int) ($data['ticket_quantity'] ?? 1));
        $baseCreateData = [
            'program_id'      => 'trms',
            'schedule_id'     => $scheduleId,
            'name'            => trim($data['name']),
            'email'           => trim($data['email']),
            'phone'           => trim($data['phone']),
            'concert_title'   => trim($data['concert_title']),
            'ticket_quantity' => 1,          // always 1 row = 1 physical ticket
            'notes'           => $incomingNotes,
            'seat_number'     => $seatNumber,
        ];

        if (!empty($data['created_at'])) {
            $baseCreateData['created_at'] = $data['created_at'];
        }

        // ── For Invitation with qty > 1: one DB row per ticket, each with its own QR ──
        // ── For Guest or qty = 1: single row as before ────────────────────────────────
        $isInvitation   = ($incomingNotes === 'Invitation');
        $ticketCount    = ($isInvitation && $qty > 1) ? $qty : 1;
        $createdRecords = [];   // ['id' => ..., 'qr_code' => ...]

        try {
            for ($i = 0; $i < $ticketCount; $i++) {
                $rowData = $baseCreateData;
                // For Guest keep the original requested qty on the single row
                if (!$isInvitation) {
                    $rowData['ticket_quantity'] = $qty;
                }

                $id     = $this->model->create($rowData);
                $qrCode = ConcertAudience::buildQrCode($concertCode, $id);
                $this->model->updateQrCode($id, $qrCode);

                $createdRecords[] = ['id' => $id, 'qr_code' => $qrCode];
            }
        } catch (\Throwable $error) {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to save registration. Please check the database setup.']);
            return;
        }

        $firstId      = $createdRecords[0]['id'];
        $firstQrCode  = $createdRecords[0]['qr_code'];

        // ── Release seat hold for this user ───────────────────────────────────
        if ($scheduleId) {
            $this->holdModel->releaseAll($scheduleId, $this->getAuthUserId() ?? 0);
        }

        http_response_code(201);
        echo json_encode([
            'success'          => true,
            'message'          => 'Registration submitted successfully',
            'id'               => $firstId,
            'qr_code'          => $firstQrCode,
            'tickets_created'  => $ticketCount,
            'ticket_pdf_url'   => '/api/trms/concert/ticket/' . $firstId,
        ]);

        // ── Flush the HTTP response to the client immediately ─────────────────
        // PDF generation + SMTP can be slow; the user should not wait for it.
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            // Works with PHP built-in server and most SAPI setups
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            flush();
        }

        // Remove execution time limit for the background PDF + email work
        @set_time_limit(0);

        // ── Build ticket rows for PDF generation ──────────────────────────────
        $baseForPdf = array_merge($data, [
            'notes'           => $incomingNotes,
            'ticket_quantity' => 1,
        ]);

        // Generate one individual PDF per created record
        $ticketPdfs = [];   // [['pdf' => string, 'name' => string], ...]
        foreach ($createdRecords as $index => $record) {
            $ticketData = array_merge($baseForPdf, [
                'id'      => $record['id'],
                'qr_code' => $record['qr_code'],
            ]);
            $pdf = $this->generateTicketPdf($ticketData);
            if ($pdf !== null) {
                $ticketNumber = $index + 1;
                $ticketPdfs[] = [
                    'pdf'  => $pdf,
                    'name' => "ticket_{$ticketNumber}_of_{$ticketCount}.pdf",
                ];
            }
        }

        $emailData = array_merge($baseForPdf, [
            'id'              => $firstId,
            'qr_code'         => $firstQrCode,
            'ticket_quantity' => $qty,
        ]);
        $allIds = array_column($createdRecords, 'id');
        if ($incomingNotes !== 'Invitation') {
            $this->sendRegistrationEmail($emailData, $ticketPdfs, $allIds);
        }
    }

    /**
     * Send registration confirmation email.
     *
     * @param array $data        Email recipient and body data
     * @param array $ticketPdfs  Array of ['pdf' => string, 'name' => string] for each ticket PDF
     * @param array $allIds      All ticket IDs to mark as sent/failed
     * @return bool
     */
    private function sendRegistrationEmail(array $data, array $ticketPdfs, array $allIds = []): bool
    {
        $id             = (int) ($data['id'] ?? 0);
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

        // First ticket is the primary attachment; the rest are added via addAttachment()
        $firstPdf  = !empty($ticketPdfs) ? $ticketPdfs[0]['pdf']  : null;
        $firstName = !empty($ticketPdfs) ? $ticketPdfs[0]['name'] : $safeName;

        $mail = new Mail($to, $subject, $textBody, $firstPdf, $firstName, $htmlBody);

        // Attach remaining tickets (ticket 2, 3, 4 …)
        for ($i = 1; $i < count($ticketPdfs); $i++) {
            $mail->addAttachment($ticketPdfs[$i]['pdf'], $ticketPdfs[$i]['name']);
        }

        $sent = $mail->send();

        if ($id > 0) {
            // Update send_email_status for all created ticket rows
            $idsToUpdate = !empty($allIds) ? $allIds : [$id];
            foreach ($idsToUpdate as $ticketId) {
                try {
                    $this->model->updateSendEmailStatus((int) $ticketId, $sent ? 'sent' : 'failed');
                } catch (\Throwable $error) {
                    error_log('Unable to update send_email_status for id ' . $ticketId . ': ' . $error->getMessage());
                }
            }
        }

        return $sent;
    }

    private function generateTicketPdf(array $data): ?string
    {
        try {
            return (new TicketPdfGenerator())->generate($data);
        } catch (\Throwable $e) {
            error_log('TicketPdfGenerator error: ' . $e->getMessage());
            return null;
        }
    }

    private function getAuthUserId(): ?int
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token  = '';
        if (preg_match('/Bearer\s+(.+)$/i', $header, $m)) {
            $token = $m[1];
        }
        if ($token === '') return null;
        $stmt = \App\Core\Database::getInstance()->prepare(
            'SELECT id FROM users WHERE api_token = :t AND api_token_exp > NOW() LIMIT 1'
        );
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch();
        return $row ? (int) $row['id'] : null;
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
            $ticketPdfs = $pdfContent !== null
                ? [['pdf' => $pdfContent, 'name' => 'ticket_' . preg_replace('/\s+/', '_', $audience['name'] ?? 'resend') . '.pdf']]
                : [];
            $sent = $this->sendRegistrationEmail($audience, $ticketPdfs);

            if (!$sent) {
                http_response_code(502);
                echo json_encode(['success' => false, 'message' => 'Email resend failed']);
                return;
            }

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

    public function sendBulkEmail(): void
    {
        header('Content-Type: application/json');

        $pendingAudiences = $this->model->findPendingEmail(10);

        if (empty($pendingAudiences)) {
            echo json_encode(['success' => true, 'message' => 'No pending emails to send', 'sent_count' => 0]);
            return;
        }

        $sentCount = 0;
        $failedCount = 0;

        foreach ($pendingAudiences as $audience) {
            $pdfContent = $this->generateTicketPdf($audience);
            $ticketPdfs = $pdfContent !== null
                ? [['pdf' => $pdfContent, 'name' => 'ticket_' . preg_replace('/\s+/', '_', $audience['name'] ?? 'bulk') . '.pdf']]
                : [];
            $sent = $this->sendRegistrationEmail($audience, $ticketPdfs);

            if ($sent) {
                $sentCount++;
            } else {
                $failedCount++;
            }
        }

        echo json_encode([
            'success' => true,
            'message' => "Bulk email sent",
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'total_processed' => count($pendingAudiences)
        ]);
    }
}
