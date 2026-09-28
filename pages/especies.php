<?php

declare(strict_types=1);

$especies = [];
try {
    $especiesStatement = db()->prepare('SELECT id_especie, nombre_especie, cientifico_especie, created_at_especie FROM especies WHERE id_empresa = :id_empresa ORDER BY id_especie DESC');
    $especiesStatement->execute([':id_empresa' => current_empresa_id()]);
    $especies = $especiesStatement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el listado de especies. Verifica que la base de datos y la tabla existan.';
}

if (isset($_GET['edit']) && $speciesEditId === null) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($editId !== false) {
        try {
            $editStatement = db()->prepare('SELECT id_especie, nombre_especie, cientifico_especie FROM especies WHERE id_especie = :id AND id_empresa = :id_empresa LIMIT 1');
            $editStatement->execute([':id' => $editId, ':id_empresa' => current_empresa_id()]);
            $editRow = $editStatement->fetch();
            if ($editRow !== false) {
                $speciesEditId = (int) $editRow['id_especie'];
                $speciesForm['nombre_especie'] = (string) ($editRow['nombre_especie'] ?? '');
                $speciesForm['cientifico_especie'] = (string) ($editRow['cientifico_especie'] ?? '');
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No se pudo cargar la especie para edición.';
        }
    }
}
?>
<section class="section-panel">
    <?php if ($speciesEditId !== null): ?>
        <h2>Editar especie <span class="badge bg-secondary fs-6">#<?php echo $speciesEditId; ?></span></h2>
        <p>Modificá los datos de la especie y guardá los cambios.</p>
    <?php else: ?>
        <h2>Alta de especie</h2>
        <p>Completa los datos de la especie y guarda el registro en la tabla <strong>especies</strong>.</p>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" class="row g-3 mt-1">
        <?php if ($speciesEditId !== null): ?>
            <input type="hidden" name="update_especie" value="1">
            <input type="hidden" name="especie_edit_id" value="<?php echo $speciesEditId; ?>">
        <?php else: ?>
            <input type="hidden" name="save_especie" value="1">
        <?php endif; ?>

        <div class="col-12">
            <label for="nombre_especie" class="form-label">Nombre de la especie *</label>
            <input type="text" class="form-control" id="nombre_especie" name="nombre_especie" maxlength="150" value="<?php echo htmlspecialchars($speciesForm['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>

        <div class="col-12">
            <label for="cientifico_especie" class="form-label">Nombre cientifico</label>
            <input type="text" class="form-control" id="cientifico_especie" name="cientifico_especie" maxlength="255" value="<?php echo htmlspecialchars($speciesForm['cientifico_especie'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-brand"><?php echo $speciesEditId !== null ? 'Actualizar especie' : 'Guardar especie'; ?></button>
            <a href="/index.php?page=especies" class="btn btn-outline-brand">Cancelar</a>
        </div>
    </form>
</section>

<section class="section-panel">
    <h2>Especies cargadas</h2>
    <p>Listado de registros existentes en la tabla especies.</p>

    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Nombre cientifico</th>
                    <th>Alta</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($especies === []): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">No hay especies cargadas aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($especies as $especie): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $especie['id_especie'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $especie['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($especie['cientifico_especie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($especie['created_at_especie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-nowrap">
                                <a href="/index.php?page=especies&edit=<?php echo urlencode((string) $especie['id_especie']); ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-pencil"></i> Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>