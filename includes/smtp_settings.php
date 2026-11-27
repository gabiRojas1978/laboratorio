<?php

declare(strict_types=1);

require_once __DIR__ . '/smtp_mailer.php';

function smtp_settings_path(int $idEmpresa): string
{
    return empresa_storage_dir($idEmpresa) . DIRECTORY_SEPARATOR . 'smtp_config.json';
}

function get_smtp_settings(int $idEmpresa): array
{
    $settings = [
        'host' => '',
        'port' => '587',
        'usuario' => '',
        'password' => '',
        'seguridad' => 'tls',
        'remitente_email' => '',
        'remitente_nombre' => '',
    ];
    $contents = @file_get_contents(smtp_settings_path($idEmpresa));
    $savedSettings = json_decode((string) $contents, true);

    if (!is_array($savedSettings)) {
        return $settings;
    }

    foreach (array_keys($settings) as $key) {
        if (isset($savedSettings[$key])) {
            $settings[$key] = trim((string) $savedSettings[$key]);
        }
    }

    return $settings;
}

function save_smtp_settings(int $idEmpresa, array $settings): bool
{
    $encodedSettings = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $encodedSettings !== false
        && file_put_contents(smtp_settings_path($idEmpresa), $encodedSettings, LOCK_EX) !== false;
}

function smtp_is_configured(array $settings): bool
{
    return $settings['host'] !== '' && $settings['usuario'] !== '' && $settings['remitente_email'] !== '';
}
