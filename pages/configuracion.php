<?php

declare(strict_types=1);

$certificateLogoPath = certificate_logo_path(current_empresa_id());
$certificateSignaturePath = certificate_signature_path(current_empresa_id());
$certificateSettings = $laboratorySettings;
$smtpSettings = get_smtp_settings(current_empresa_id());
?>
<section class="section-panel">
    <h2>Configuración</h2>
    <p>Administrá los datos, el logo y la firma que se imprimen en los certificados de este laboratorio.</p>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="mt-3">
        <a href="/index.php?page=anormalidades" class="btn btn-outline-brand">Administrar anormalidades</a>
        <p class="form-text">Alta y edición del catálogo de anormalidades de este laboratorio.</p>
    </div>

    <form method="post" enctype="multipart/form-data" class="row g-3 mt-1">
        <input type="hidden" name="save_logo_certificado" value="1">
        <div class="col-md-8">
            <label for="logo_certificado" class="form-label">Logo para certificados</label>
            <input class="form-control" type="file" id="logo_certificado" name="logo_certificado" accept="image/png,image/jpeg,image/webp" required>
            <div class="form-text">Formatos admitidos: PNG, JPG o WebP. Tamaño máximo: 5 MB.</div>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-brand">Guardar logo</button>
        </div>
    </form>

    <?php if ($certificateLogoPath !== null): ?>
        <div class="d-flex align-items-center gap-2 mt-3">
            <span class="badge bg-success">Logo personalizado activo</span>
            <form method="post" onsubmit="return confirm('¿Quitar el logo personalizado del certificado?')">
                <input type="hidden" name="delete_logo_certificado" value="1">
                <button type="submit" class="btn btn-sm btn-outline-danger">Quitar logo</button>
            </form>
        </div>
    <?php else: ?>
        <p class="text-muted small mt-3 mb-0">No hay logo configurado. El certificado se emitirá sin logo.</p>
    <?php endif; ?>

    <form method="post" class="row g-3 mt-4">
        <input type="hidden" name="save_datos_certificado" value="1">
        <div class="col-12">
            <label for="nombre_laboratorio" class="form-label">Nombre del laboratorio *</label>
            <input type="text" class="form-control" id="nombre_laboratorio" name="nombre_laboratorio" maxlength="150" value="<?php echo htmlspecialchars($certificateSettings['nombre_laboratorio'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="col-md-6">
            <label for="localidad_pais_certificado" class="form-label">Localidad y País</label>
            <input type="text" class="form-control" id="localidad_pais_certificado" name="localidad_pais_certificado" maxlength="255" value="<?php echo htmlspecialchars($certificateSettings['localidad_pais'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-6">
            <label for="firma_director_tecnico" class="form-label">Firma del Director Técnico</label>
            <input type="text" class="form-control" id="firma_director_tecnico" name="firma_director_tecnico" maxlength="255" value="<?php echo htmlspecialchars($certificateSettings['firma_director_tecnico'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-brand">Guardar datos del certificado</button>
        </div>
    </form>

    <form method="post" enctype="multipart/form-data" class="row g-3 mt-1">
        <input type="hidden" name="save_firma_certificado" value="1">
        <div class="col-md-8">
            <label for="imagen_firma_director_tecnico" class="form-label">Imagen de la firma del Director Técnico</label>
            <input class="form-control" type="file" id="imagen_firma_director_tecnico" name="imagen_firma_director_tecnico" accept="image/png,image/jpeg" required>
            <div class="form-text">Formatos admitidos: PNG o JPG. Tamaño máximo: 5 MB. Se imprime sobre el texto de la firma.</div>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-brand">Guardar imagen de firma</button>
        </div>
    </form>
    <?php if ($certificateSignaturePath !== null): ?>
        <div class="d-flex align-items-center gap-2 mt-3">
            <span class="badge bg-success">Imagen de firma activa</span>
            <form method="post" onsubmit="return confirm('¿Quitar la imagen de firma del certificado?')">
                <input type="hidden" name="delete_firma_certificado" value="1">
                <button type="submit" class="btn btn-sm btn-outline-danger">Quitar imagen</button>
            </form>
        </div>
    <?php endif; ?>

    <dl class="row mt-4 mb-0">
        <dt class="col-sm-4">Carpeta de guardado de certificados</dt>
        <dd class="col-sm-8"><?php echo htmlspecialchars(empresa_storage_dir(current_empresa_id()), ENT_QUOTES, 'UTF-8'); ?></dd>
    </dl>
    <p class="text-muted small mb-0">Cada laboratorio tiene su propia carpeta de almacenamiento asignada automáticamente; no es configurable manualmente por razones de seguridad.</p>

    <hr class="my-4">
    <h3 class="h5">Configuración de correo (SMTP)</h3>
    <p class="text-muted small">Usada para enviar certificados por email. Si tu hosting incluye una casilla de correo del dominio (ej. DonWeb), usá su servidor SMTP autenticado.</p>
    <form method="post" class="row g-3 mt-1">
        <input type="hidden" name="save_smtp_config" value="1">
        <div class="col-md-6">
            <label for="smtp_host" class="form-label">Servidor SMTP</label>
            <input type="text" class="form-control" id="smtp_host" name="smtp_host" maxlength="255" placeholder="mail.tudominio.com" value="<?php echo htmlspecialchars($smtpSettings['host'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-2">
            <label for="smtp_port" class="form-label">Puerto</label>
            <input type="number" class="form-control" id="smtp_port" name="smtp_port" min="1" max="65535" value="<?php echo htmlspecialchars($smtpSettings['port'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-4">
            <label for="smtp_seguridad" class="form-label">Seguridad</label>
            <select class="form-select" id="smtp_seguridad" name="smtp_seguridad">
                <option value="tls" <?php echo $smtpSettings['seguridad'] === 'tls' ? 'selected' : ''; ?>>STARTTLS (587)</option>
                <option value="ssl" <?php echo $smtpSettings['seguridad'] === 'ssl' ? 'selected' : ''; ?>>SSL/TLS (465)</option>
                <option value="none" <?php echo $smtpSettings['seguridad'] === 'none' ? 'selected' : ''; ?>>Ninguna</option>
            </select>
        </div>
        <div class="col-md-6">
            <label for="smtp_usuario" class="form-label">Usuario SMTP</label>
            <input type="text" class="form-control" id="smtp_usuario" name="smtp_usuario" maxlength="255" placeholder="certificados@tudominio.com" value="<?php echo htmlspecialchars($smtpSettings['usuario'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-6">
            <label for="smtp_password" class="form-label">Contraseña <?php echo $smtpSettings['password'] !== '' ? '(dejar en blanco para no cambiarla)' : ''; ?></label>
            <input type="password" class="form-control" id="smtp_password" name="smtp_password" autocomplete="new-password">
        </div>
        <div class="col-md-6">
            <label for="smtp_remitente_email" class="form-label">Email remitente</label>
            <input type="email" class="form-control" id="smtp_remitente_email" name="smtp_remitente_email" maxlength="255" value="<?php echo htmlspecialchars($smtpSettings['remitente_email'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-6">
            <label for="smtp_remitente_nombre" class="form-label">Nombre remitente</label>
            <input type="text" class="form-control" id="smtp_remitente_nombre" name="smtp_remitente_nombre" maxlength="150" placeholder="<?php echo htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($smtpSettings['remitente_nombre'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-brand">Guardar configuración de correo</button>
        </div>
    </form>
</section>