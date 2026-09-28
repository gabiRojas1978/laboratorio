<?php

declare(strict_types=1);

function certificate_logo_path(int $idEmpresa): ?string
{
    $directory = empresa_storage_dir($idEmpresa);
    foreach (['png', 'jpg', 'jpeg', 'webp'] as $extension) {
        $path = $directory . DIRECTORY_SEPARATOR . 'logo_certificado.' . $extension;
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}

function remove_certificate_logo(int $idEmpresa): void
{
    $directory = empresa_storage_dir($idEmpresa);
    foreach (['png', 'jpg', 'jpeg', 'webp'] as $extension) {
        $path = $directory . DIRECTORY_SEPARATOR . 'logo_certificado.' . $extension;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

function certificate_settings_path(int $idEmpresa): string
{
    return empresa_storage_dir($idEmpresa) . DIRECTORY_SEPARATOR . 'certificado_config.json';
}

function get_certificate_settings(int $idEmpresa): array
{
    $settings = [
        'nombre_laboratorio' => '',
        'localidad_pais' => '',
        'firma_director_tecnico' => '',
    ];
    $contents = @file_get_contents(certificate_settings_path($idEmpresa));
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

function save_certificate_settings(int $idEmpresa, array $settings): bool
{
    $encodedSettings = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $encodedSettings !== false
        && file_put_contents(certificate_settings_path($idEmpresa), $encodedSettings, LOCK_EX) !== false;
}
