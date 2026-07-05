<?php

namespace App\Core;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mail
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;
    private string $from;
    private string $fromName;
    private string $to;
    private string $subject;
    private string $textBody;
    private string $htmlBody;
    private ?string $attachment;
    private string $attachmentName;

    public function __construct(
        string  $to,
        string  $subject,
        string  $textBody,
        ?string $attachment     = null,
        string  $attachmentName = 'ticket.pdf',
        string  $htmlBody       = ''
    ) {
        $this->host           = $this->env('MAIL_HOST', 'smtp.gmail.com');
        $this->port           = (int) $this->env('MAIL_PORT', '587');
        $this->username       = $this->env('MAIL_USERNAME');
        $this->password       = $this->env('MAIL_PASSWORD');
        $this->encryption     = strtolower($this->env('MAIL_ENCRYPTION', PHPMailer::ENCRYPTION_STARTTLS));
        $this->from           = $this->env('MAIL_FROM', $this->username ?: 'noreply@resonanz.com');
        $this->fromName       = $this->env('MAIL_FROM_NAME', 'Resonanz Music Studio');
        $this->to             = $to;
        $this->subject        = $subject;
        $this->textBody       = $textBody;
        $this->htmlBody       = $htmlBody;
        $this->attachment     = $attachment;
        $this->attachmentName = $attachmentName ?: 'ticket.pdf';
    }

    public function send(): bool
    {
        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->SMTPSecure = $this->encryption;
            $mail->Port       = $this->port;
            $mail->CharSet    = PHPMailer::CHARSET_UTF8;
            $mail->Encoding   = PHPMailer::ENCODING_BASE64;

            $mail->setFrom($this->from, $this->fromName);
            $mail->addReplyTo($this->from, $this->fromName);
            $mail->addAddress($this->to);

            $mail->Subject = $this->subject;

            if ($this->htmlBody !== '') {
                $mail->isHTML(true);
                $mail->Body    = $this->htmlBody;
                $mail->AltBody = $this->textBody;
            } else {
                $mail->isHTML(false);
                $mail->Body = $this->textBody;
            }

            if ($this->attachment !== null && $this->attachment !== '') {
                $safeName = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $this->attachmentName) ?: 'ticket.pdf';
                $mail->addStringAttachment($this->attachment, $safeName, PHPMailer::ENCODING_BASE64, 'application/pdf');
            }

            return $mail->send();
        } catch (Exception $error) {
            error_log('PHPMailer error: ' . $error->getMessage());
            return false;
        }
    }

    private function env(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }
}
