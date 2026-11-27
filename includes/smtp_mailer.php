<?php

declare(strict_types=1);

function smtp_create_mailer(array $settings): \PHPMailer\PHPMailer\PHPMailer
{
    $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = $settings['host'];
    $mailer->Port = (int) ($settings['port'] !== '' ? $settings['port'] : 587);
    $mailer->SMTPAuth = true;
    $mailer->Username = $settings['usuario'];
    $mailer->Password = $settings['password'];
    if ($settings['seguridad'] === 'ssl') {
        $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($settings['seguridad'] === 'tls') {
        $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mailer->SMTPAutoTLS = false;
    }
    $mailer->CharSet = 'UTF-8';
    $mailer->setFrom($settings['remitente_email'], $settings['remitente_nombre'] !== '' ? $settings['remitente_nombre'] : APP_NAME);
    return $mailer;
}
