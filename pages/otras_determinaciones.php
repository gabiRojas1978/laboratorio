<?php

declare(strict_types=1);

$detList = [];
try {
    $detListStatement = db()->prepare('SELECT id_determinacion, nombre_determinacion, created_at_determinacion FROM otras_determinaciones WHERE id_empresa = :id_empresa ORDER BY id_determinacion DESC');
    $detListStatement->execute([':id_empresa' => current_empresa_id()]);
    $detList = $detListStatement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el listado de determinaciones. Verifica que la tabla exista.';
}

if (isset($_GET['edit']) && $detEditId === null) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($editId !== false) {
        try {
            $editStatement = db()->prepare('SELECT id_determinacion, nombre_determinacion FROM otras_determinaciones WHERE id_determinacion = :id AND id_empresa = :id_empresa LIMIT 1');
            $editStatement->execute([':id' => $editId, ':id_empresa' => current_empresa_id()]);
            $editRow = $editStatement->fetch();
            if ($editRow !== false) {
                $detEditId = (int) $editRow['id_determinacion'];
                $detForm['nombre_determinacion'] = (string) ($editRow['nombre_determinacion'] ?? '');
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No se pudo cargar la determinación para edición.';
        }
    }
}
?>
<section class="section-panel">
    <?php if ($detEditId !== null): ?>
        <h2>Editar determinación <span class="badge bg-secondary fs-6">#<?php echo $detEditId; ?></span></h2>
        <p>Modificá el nombre de la determinación y guardá los cambios.</p>
    <?php else: ?>
        <h2>Alta de determinación</h2>
        <p>Registrá una nueva determinación en la tabla <strong>otras_determinaciones</strong>.</p>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" class="row g-3 mt-1">
        <?php if ($detEditId !== null): ?>
            <input type="hidden" name="update_determinacion" value="1">
            <input type="hidden" name="det_edit_id" value="<?php echo $detEditId; ?>">
        <?php else: ?>
            <input type="hidden" name="save_determinacion" value="1">
        <?php endif; ?>

        <div class="col-12">
            <label for="nombre_determinacion" class="form-label">Nombre de la determinación *</label>
            <input type="text" class="form-control" id="nombre_determinacion" name="nombre_determinacion" maxlength="255" value="<?php echo htmlspecialchars($detForm['nombre_determinacion'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-brand"><?php echo $detEditId !== null ? 'Actualizar determinación' : 'Guardar determinación'; ?></button>
            <a href="/index.php?page=otras_determinaciones" class="btn btn-outline-brand">Cancelar</a>
        </div>
    </form>
</section>

<section class="section-panel">
    <h2>Determinaciones cargadas</h2>
    <p>Listado de registros existentes en la tabla otras_determinaciones.</p>

    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Alta</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($detList === []): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">No hay determinaciones cargadas aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($detList as $det): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $det['id_determinacion'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $det['nombre_determinacion'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($det['created_at_determinacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-nowrap">
                                <a href="/index.php?page=otras_determinaciones&edit=<?php echo urlencode((string) $det['id_determinacion']); ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-pencil"></i> Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>