<?php

namespace App\Core;

class Mail
{
    private string $from;
    private string $fromName;
    private string $to;
    private string $subject;
    private string $textBody;
    private string $htmlBody;
    private ?string $attachment;       // raw binary content of the PDF
    private string $attachmentName;    // filename shown to recipient

    public function __construct(
        string  $to,
        string  $subject,
        string  $textBody,
        ?string $attachment     = null,
        string  $attachmentName = 'ticket.pdf',
        string  $htmlBody       = ''
    ) {
        $this->from           = $_ENV['MAIL_FROM']      ?? 'noreply@resonanz.com';
        $this->fromName       = $_ENV['MAIL_FROM_NAME'] ?? 'Resonanz Music Studio';
        $this->to             = $to;
        $this->subject        = $subject;
        $this->textBody       = $textBody;
        $this->htmlBody       = $htmlBody;
        $this->attachment     = $attachment;
        $this->attachmentName = $attachmentName ?: 'ticket.pdf';
    }

    public function send(): bool
    {
        return $this->attachment
            ? $this->sendWithAttachment()
            : $this->sendPlain();
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function baseHeaders(): string
    {
        $encodedName = mb_encode_mimeheader($this->fromName, 'UTF-8', 'B');
        return "From: {$encodedName} <{$this->from}>\r\n" .
               "Reply-To: {$this->from}\r\n" .
               "X-Mailer: PHP/" . phpversion() . "\r\n" .
               "MIME-Version: 1.0";
    }

    private function sendPlain(): bool
    {
        $headers = $this->baseHeaders() . "\r\n" .
                   "Content-Type: text/plain; charset=UTF-8\r\n" .
                   "Content-Transfer-Encoding: 8bit";

        return mail($this->to, $this->subject, $this->textBody, $headers);
    }

    private function sendWithAttachment(): bool
    {
        $boundary    = 'RMS_' . md5(uniqid((string) time(), true));
        $altBoundary = 'RMS_ALT_' . md5(uniqid((string) time(), true));

        $headers = $this->baseHeaders() . "\r\n" .
                   "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";

        $message = '';

        // ── Part 1: text + HTML alternative ──────────────────────────────────
        $message .= "--{$boundary}\r\n";

        if ($this->htmlBody !== '') {
            // Wrap plain + HTML in a multipart/alternative block
            $message .= "Content-Type: multipart/alternative; boundary=\"{$altBoundary}\"\r\n\r\n";

            $message .= "--{$altBoundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $message .= $this->textBody . "\r\n\r\n";

            $message .= "--{$altBoundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $message .= $this->htmlBody . "\r\n\r\n";

            $message .= "--{$altBoundary}--\r\n\r\n";
        } else {
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $message .= $this->textBody . "\r\n\r\n";
        }

        // ── Part 2: PDF attachment ─────────────────────────────────────────
        $safeName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $this->attachmentName);

        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: application/pdf; name=\"{$safeName}\"\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n";
        $message .= "Content-Disposition: attachment; filename=\"{$safeName}\"\r\n\r\n";
        $message .= chunk_split(base64_encode($this->attachment)) . "\r\n";

        $message .= "--{$boundary}--";

        return mail($this->to, $this->subject, $message, $headers);
    }
}
