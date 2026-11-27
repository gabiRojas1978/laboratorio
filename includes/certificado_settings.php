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

function certificate_signature_path(int $idEmpresa): ?string
{
    $path = empresa_storage_dir($idEmpresa) . DIRECTORY_SEPARATOR . 'firma_director_tecnico';
    return is_file($path) ? $path : null;
}

function save_certificate_signature(int $idEmpresa, array $upload): ?string
{
    $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        if ($error === UPLOAD_ERR_NO_FILE) {
            return 'Debes seleccionar una imagen para la firma.';
        }
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return 'El servidor rechazó la firma por tamaño. Reduce la imagen o aumenta upload_max_filesize/post_max_size en PHP.';
        }
        return 'No fue posible recibir la firma (código de carga: ' . $error . ').';
    }
    if ((int) ($upload['size'] ?? 0) > 5 * 1024 * 1024) {
        return 'La firma no puede superar los 5 MB.';
    }
    $uploadedPath = (string) ($upload['tmp_name'] ?? '');
    if (!is_uploaded_file($uploadedPath)) {
        return 'No fue posible validar el archivo de la firma.';
    }
    $imageInfo = @getimagesize($uploadedPath);
    if (!is_array($imageInfo) || !in_array($imageInfo['mime'] ?? '', ['image/png', 'image/jpeg'], true)) {
        return 'La firma debe ser una imagen PNG o JPG válida.';
    }

    $directory = empresa_storage_dir($idEmpresa);
    if (!is_dir($directory) || !is_writable($directory)) {
        return 'No fue posible preparar el archivo de la firma. Verifica los permisos de la carpeta de almacenamiento.';
    }
    $temporaryPath = @tempnam($directory, '.firma_upload_');
    if ($temporaryPath === false) {
        return 'No fue posible preparar el archivo de la firma. Verifica los permisos de la carpeta de almacenamiento.';
    }
    if (!move_uploaded_file($uploadedPath, $temporaryPath)) {
        @unlink($temporaryPath);
        return 'No fue posible guardar la firma. Verifica los permisos de la carpeta de almacenamiento.';
    }
    if (!@rename($temporaryPath, $directory . DIRECTORY_SEPARATOR . 'firma_director_tecnico')) {
        @unlink($temporaryPath);
        return 'No fue posible activar la firma cargada.';
    }

    return null;
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
        'direccion_laboratorio' => '',
        'telefono_laboratorio' => '',
        'email_laboratorio' => '',
        'cuit_laboratorio' => '',
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
