<?php

use PHPMailer\PHPMailer\PHPMailer;

const SMTP_AUTOLOAD_PATH = '/home/c/cx314477/public_html/.private/vendor/autoload.php';
const SMTP_PASSWORD_PATH = '/home/c/cx314477/public_html/.private/smtp_pass';

function sendConfiguredMail(string $to, string $subject, string $htmlBody): bool
{
    if (!is_readable(SMTP_AUTOLOAD_PATH) || !is_readable(SMTP_PASSWORD_PATH)) {
        error_log('SMTP configuration is not available');
        return false;
    }

    require_once SMTP_AUTOLOAD_PATH;

    $password = trim((string)file_get_contents(SMTP_PASSWORD_PATH));
    if ($password === '') {
        error_log('SMTP password is empty');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.timeweb.ru';
        $mail->SMTPAuth = true;
        $mail->Username = 'info@rclsmo.ru';
        $mail->Password = $password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Timeout = 15;

        $mail->setFrom('info@rclsmo.ru', 'Референс-центр лабораторной службы МО');
        $mail->addReplyTo('info@rclsmo.ru', 'Референс-центр лабораторной службы МО');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = str_starts_with($subject, '=?') ? mb_decode_mimeheader($subject) : $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], ["\n", "\n", "\n", "\n", "\n"], $htmlBody)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $mail->send();
    } catch (Throwable $e) {
        error_log('SMTP send failed: ' . $e->getMessage());
        return false;
    }
}
