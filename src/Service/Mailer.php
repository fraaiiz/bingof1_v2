<?php

namespace App\Service;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    public function send(string $to, string $subject, string $body, ?string $bcc = null): bool
    {
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $this->setting('MAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = $this->setting('MAIL_USERNAME');
            $mail->Password = $this->setting('MAIL_PASSWORD');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int) $this->setting('MAIL_PORT', '587');
            $mail->Timeout = 15;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($this->setting('MAIL_FROM', 'dev@bingof1.fr'), $this->setting('MAIL_FROM_NAME', 'Bingof1 Support'));
            $mail->addAddress($to);
            $mail->addReplyTo($this->setting('MAIL_FROM', 'dev@bingof1.fr'));
            if (is_string($bcc) && filter_var($bcc, FILTER_VALIDATE_EMAIL) !== false && strcasecmp($bcc, $to) !== 0) {
                $mail->addBCC($bcc);
            }
            $mail->Subject = str_replace(["\r", "\n"], '', $subject);
            $mail->Body = $body;
            $mail->isHTML(false);
            $mail->send();

            return true;
        } catch (Exception $exception) {
            error_log('Erreur mail: ' . $mail->ErrorInfo);

            return false;
        }
    }

    private function setting(string $name, string $default = ''): string
    {
        $value = getenv($name);

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
