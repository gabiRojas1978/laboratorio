<?php

declare(strict_types=1);

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_logo_certificado'])) {
    $logoUpload = $_FILES['logo_certificado'] ?? null;
    $uploadError = is_array($logoUpload) ? (int) ($logoUpload['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;

    if (!is_array($logoUpload) || $uploadError !== UPLOAD_ERR_OK) {
        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Debes seleccionar una imagen para el logo.';
        } elseif ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
            $errors[] = 'El servidor rechazó el archivo por tamaño. Reduce la imagen o aumenta upload_max_filesize/post_max_size en PHP.';
        } else {
            $errors[] = 'No fue posible recibir el archivo (código de carga: ' . $uploadError . ').';
        }
    } elseif ((int) ($logoUpload['size'] ?? 0) > 5 * 1024 * 1024) {
        $errors[] = 'El logo no puede superar los 5 MB.';
    } elseif (!is_uploaded_file((string) ($logoUpload['tmp_name'] ?? ''))) {
        $errors[] = 'No fue posible validar el archivo cargado.';
    } else {
        $imageInfo = @getimagesize((string) $logoUpload['tmp_name']);
        $extensionByMime = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $extension = is_array($imageInfo) ? ($extensionByMime[$imageInfo['mime'] ?? ''] ?? null) : null;

        if ($extension === null) {
            $errors[] = 'El logo debe ser una imagen PNG, JPG o WebP válida.';
        } else {
            $logoDirectory = empresa_storage_dir(current_empresa_id());
            $temporaryLogoPath = $logoDirectory . DIRECTORY_SEPARATOR . '.logo_certificado_upload.' . $extension;
            $logoPath = $logoDirectory . DIRECTORY_SEPARATOR . 'logo_certificado.' . $extension;

            if (!move_uploaded_file((string) $logoUpload['tmp_name'], $temporaryLogoPath)) {
                $errors[] = 'No fue posible guardar el logo. Verifica los permisos de la carpeta de almacenamiento.';
            } else {
                remove_certificate_logo(current_empresa_id());
                if (!rename($temporaryLogoPath, $logoPath)) {
                    @unlink($temporaryLogoPath);
                    $errors[] = 'No fue posible activar el logo cargado.';
                } else {
                    $_SESSION['success_message'] = 'Logo del certificado actualizado correctamente.';
                    header('Location: /index.php?page=configuracion');
                    exit;
                }
            }
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_logo_certificado'])) {
    remove_certificate_logo(current_empresa_id());
    $_SESSION['success_message'] = 'Logo personalizado eliminado. Se utilizará el membrete predeterminado.';
    header('Location: /index.php?page=configuracion');
    exit;
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_firma_certificado'])) {
    $signatureUpload = $_FILES['imagen_firma_director_tecnico'] ?? [];
    $signatureError = save_certificate_signature(current_empresa_id(), is_array($signatureUpload) ? $signatureUpload : []);
    if ($signatureError !== null) {
        $errors[] = $signatureError;
    } else {
        $_SESSION['success_message'] = 'Imagen de firma del director técnico actualizada correctamente.';
        header('Location: /index.php?page=configuracion');
        exit;
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_firma_certificado'])) {
    $signaturePath = certificate_signature_path(current_empresa_id());
    if ($signaturePath !== null && !@unlink($signaturePath)) {
        $errors[] = 'No fue posible quitar la imagen de la firma.';
    } else {
        $_SESSION['success_message'] = 'Imagen de firma del director técnico eliminada.';
        header('Location: /index.php?page=configuracion');
        exit;
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_datos_certificado'])) {
    $certificateSettings = [
        'nombre_laboratorio' => trim((string) ($_POST['nombre_laboratorio'] ?? '')),
        'localidad_pais' => trim((string) ($_POST['localidad_pais_certificado'] ?? '')),
        'firma_director_tecnico' => trim((string) ($_POST['firma_director_tecnico'] ?? '')),
    ];

    if ($certificateSettings['nombre_laboratorio'] === '') {
        $errors[] = 'El nombre del laboratorio es obligatorio.';
    } elseif (strlen($certificateSettings['nombre_laboratorio']) > 150 || strlen($certificateSettings['localidad_pais']) > 255 || strlen($certificateSettings['firma_director_tecnico']) > 255) {
        $errors[] = 'El nombre del laboratorio puede tener hasta 150 caracteres; la localidad y la firma, hasta 255.';
    } elseif (!save_certificate_settings(current_empresa_id(), $certificateSettings)) {
        $errors[] = 'No fue posible guardar los datos del certificado. Verifica los permisos de la carpeta de almacenamiento.';
    } else {
        $_SESSION['success_message'] = 'Datos del pie de certificado actualizados correctamente.';
        header('Location: /index.php?page=configuracion');
        exit;
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_smtp_config'])) {
    $existingSmtpSettings = get_smtp_settings(current_empresa_id());
    $smtpPasswordInput = (string) ($_POST['smtp_password'] ?? '');
    $smtpSettings = [
        'host' => trim((string) ($_POST['smtp_host'] ?? '')),
        'port' => trim((string) ($_POST['smtp_port'] ?? '587')),
        'usuario' => trim((string) ($_POST['smtp_usuario'] ?? '')),
        'password' => $smtpPasswordInput !== '' ? $smtpPasswordInput : $existingSmtpSettings['password'],
        'seguridad' => in_array($_POST['smtp_seguridad'] ?? 'tls', ['tls', 'ssl', 'none'], true) ? (string) $_POST['smtp_seguridad'] : 'tls',
        'remitente_email' => trim((string) ($_POST['smtp_remitente_email'] ?? '')),
        'remitente_nombre' => trim((string) ($_POST['smtp_remitente_nombre'] ?? '')),
    ];

    if ($smtpSettings['remitente_email'] !== '' && !filter_var($smtpSettings['remitente_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email remitente no es válido.';
    } elseif (!ctype_digit($smtpSettings['port']) || (int) $smtpSettings['port'] < 1 || (int) $smtpSettings['port'] > 65535) {
        $errors[] = 'El puerto SMTP no es válido.';
    } elseif (!save_smtp_settings(current_empresa_id(), $smtpSettings)) {
        $errors[] = 'No fue posible guardar la configuración de correo.';
    } else {
        $_SESSION['success_message'] = 'Configuración de correo actualizada correctamente.';
        header('Location: /index.php?page=configuracion');
        exit;
    }
}
