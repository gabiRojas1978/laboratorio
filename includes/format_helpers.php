<?php

declare(strict_types=1);

function format_date_short(?string $date): string
{
    if ($date === null || $date === '') {
        return '----------------';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '----------------';
    }

    return date('d/m/Y', $timestamp);
}

function format_date_long_spanish(?string $date): string
{
    if ($date === null || $date === '') {
        return '----------------';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '----------------';
    }

    $months = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre',
    ];

    $day = date('j', $timestamp);
    $month = $months[(int) date('n', $timestamp)] ?? '';
    $year = date('Y', $timestamp);

    return $day . ' de ' . $month . ' de ' . $year;
}

function format_percent_value($value): string
{
    if ($value === null) {
        return 'N';
    }

    $raw = trim((string) $value);
    if ($raw === '' || !is_numeric($raw)) {
        return 'N';
    }

    $number = (float) $raw;
    if ($number === 0.0) {
        return '0';
    }

    return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
}

function format_text_value($value, string $fallback = 'N'): string
{
    $text = trim((string) ($value ?? ''));
    return $text !== '' ? $text : $fallback;
}

function normalize_client_emails($rawEmails): array
{
    if (!is_array($rawEmails)) {
        return [];
    }

    $emails = [];
    foreach ($rawEmails as $rawEmail) {
        $email = trim((string) $rawEmail);
        if ($email === '') {
            continue;
        }

        $emails[] = $email;
    }

    return array_values(array_unique($emails));
}

function client_emails_table_exists(): bool
{
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }

    try {
        db()->query('SELECT 1 FROM cliente_emails LIMIT 1');
        $exists = true;
    } catch (Throwable $throwable) {
        $exists = false;
    }

    return $exists;
}

function get_cliente_emails(int $idCliente): array
{
    if (client_emails_table_exists()) {
        $statement = db()->prepare(
            'SELECT email_cliente FROM cliente_emails
             WHERE id_cliente = :id_cliente AND id_empresa = :id_empresa AND activo_email = 1
             ORDER BY principal_email DESC, id_email_cliente ASC'
        );
        $statement->execute([':id_cliente' => $idCliente, ':id_empresa' => current_empresa_id()]);
        $emails = array_map(static fn($email) => trim((string) $email), $statement->fetchAll(PDO::FETCH_COLUMN));
    } else {
        $statement = db()->prepare('SELECT mail_cliente FROM clientes WHERE id_cliente = :id_cliente AND id_empresa = :id_empresa LIMIT 1');
        $statement->execute([':id_cliente' => $idCliente, ':id_empresa' => current_empresa_id()]);
        $singleEmail = trim((string) ($statement->fetchColumn() ?: ''));
        $emails = $singleEmail !== '' ? [$singleEmail] : [];
    }

    return array_values(array_filter($emails, static fn($email) => $email !== ''));
}

function is_active_page(string $key, string $currentPage): string
{
    return $key === $currentPage ? 'active' : '';
}

function render_card(string $title, string $text): string
{
    return sprintf(
        '<div class="info-card"><h3>%s</h3><p>%s</p></div>',
        htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
    );
}
