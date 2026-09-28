<?php

declare(strict_types=1);

$empresas = [];
try {
    $empresas = db()->query('SELECT id_empresa, nombre_empresa, activo_empresa, created_at_empresa FROM empresas ORDER BY id_empresa DESC')->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el listado de empresas.';
}

if (isset($_GET['edit']) && $empresaEditId === null) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($editId !== false) {
        try {
            $editStatement = db()->prepare('SELECT id_empresa, nombre_empresa, activo_empresa FROM empresas WHERE id_empresa = :id LIMIT 1');
            $editStatement->execute([':id' => $editId]);
            $editRow = $editStatement->fetch();
            if ($editRow !== false) {
                $empresaEditId = (int) $editRow['id_empresa'];
                $empresaForm['nombre_empresa'] = (string) ($editRow['nombre_empresa'] ?? '');
                $empresaForm['activo_empresa'] = (string) ($editRow['activo_empresa'] ?? '1');
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No se pudo cargar la empresa para edición.';
        }
    }
}
?>
<section class="section-panel">
    <?php if ($empresaEditId !== null): ?>
        <h2>Editar empresa <span class="badge bg-secondary fs-6">#<?php echo $empresaEditId; ?></span></h2>
        <p>Modificá el nombre o el estado de la empresa.</p>
    <?php else: ?>
        <h2>Alta de empresa</h2>
        <p>Creá un nuevo laboratorio (tenant) junto con su primer usuario administrador.</p>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($empresaEditId !== null): ?>
        <form method="post" class="row g-3 mt-1">
            <input type="hidden" name="update_empresa" value="1">
            <input type="hidden" name="empresa_edit_id" value="<?php echo $empresaEditId; ?>">

            <div class="col-md-8">
                <label for="nombre_empresa" class="form-label">Nombre de la empresa *</label>
                <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" maxlength="150" value="<?php echo htmlspecialchars($empresaForm['nombre_empresa'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="activo_empresa" name="activo_empresa" value="1" <?php echo $empresaForm['activo_empresa'] === '1' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="activo_empresa">Empresa activa</label>
                </div>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-brand">Actualizar empresa</button>
                <a href="/index.php?page=empresas" class="btn btn-outline-brand">Cancelar</a>
            </div>
        </form>
    <?php else: ?>
        <form method="post" class="row g-3 mt-1">
            <input type="hidden" name="save_empresa" value="1">

            <div class="col-12">
                <label for="nombre_empresa" class="form-label">Nombre de la empresa *</label>
                <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" maxlength="150" value="<?php echo htmlspecialchars($empresaForm['nombre_empresa'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="col-12">
                <hr>
                <h3 class="h6">Primer usuario administrador</h3>
            </div>

            <div class="col-md-6">
                <label for="admin_nombre_usuario" class="form-label">Nombre completo *</label>
                <input type="text" class="form-control" id="admin_nombre_usuario" name="admin_nombre_usuario" maxlength="150" value="<?php echo htmlspecialchars($empresaAdminForm['nombre_usuario'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="col-md-6">
                <label for="admin_username_usuario" class="form-label">Usuario *</label>
                <input type="text" class="form-control" id="admin_username_usuario" name="admin_username_usuario" maxlength="60" value="<?php echo htmlspecialchars($empresaAdminForm['username_usuario'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="col-md-6">
                <label for="admin_email_usuario" class="form-label">Email</label>
                <input type="email" class="form-control" id="admin_email_usuario" name="admin_email_usuario" maxlength="150" value="<?php echo htmlspecialchars($empresaAdminForm['email_usuario'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-6">
                <label for="admin_password_usuario" class="form-label">Contraseña *</label>
                <input type="password" class="form-control" id="admin_password_usuario" name="admin_password_usuario" autocomplete="new-password" required>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-brand">Crear empresa</button>
                <a href="/index.php?page=empresas" class="btn btn-outline-brand">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</section>

<section class="section-panel">
    <h2>Empresas cargadas</h2>
    <p>Listado de laboratorios (tenants) registrados en el sistema.</p>

    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Alta</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($empresas === []): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">No hay empresas cargadas aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($empresas as $empresa): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $empresa['id_empresa'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $empresa['nombre_empresa'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo ((int) $empresa['activo_empresa'] === 1) ? '<span class="badge bg-success">Activa</span>' : '<span class="badge bg-secondary">Inactiva</span>'; ?></td>
                            <td><?php echo htmlspecialchars((string) ($empresa['created_at_empresa'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-nowrap">
                                <a href="/index.php?page=empresas&edit=<?php echo urlencode((string) $empresa['id_empresa']); ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-pencil"></i> Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>