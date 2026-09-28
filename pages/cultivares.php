<?php

declare(strict_types=1);

$speciesOptions = [];
try {
    $speciesOptionsStatement = db()->prepare('SELECT id_especie, nombre_especie FROM especies WHERE id_empresa = :id_empresa ORDER BY nombre_especie ASC');
    $speciesOptionsStatement->execute([':id_empresa' => current_empresa_id()]);
    $speciesOptions = $speciesOptionsStatement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el selector de especies. Verifica que la tabla especies exista.';
}

$cultivares = [];
try {
    $cultivaresStatement = db()->prepare('SELECT c.id_cultivar, c.nombre_cultivar, c.created_at_cultivar, e.id_especie, e.nombre_especie FROM cultivares c INNER JOIN especies e ON e.id_especie = c.id_especie WHERE c.id_empresa = :id_empresa ORDER BY c.id_cultivar DESC');
    $cultivaresStatement->execute([':id_empresa' => current_empresa_id()]);
    $cultivares = $cultivaresStatement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el listado de cultivares. Verifica que la tabla cultivares exista.';
}

if (isset($_GET['edit']) && $cultivarEditId === null) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($editId !== false) {
        try {
            $editStatement = db()->prepare('SELECT id_cultivar, id_especie, nombre_cultivar FROM cultivares WHERE id_cultivar = :id AND id_empresa = :id_empresa LIMIT 1');
            $editStatement->execute([':id' => $editId, ':id_empresa' => current_empresa_id()]);
            $editRow = $editStatement->fetch();
            if ($editRow !== false) {
                $cultivarEditId = (int) $editRow['id_cultivar'];
                $cultivarForm['id_especie'] = (string) $editRow['id_especie'];
                $cultivarForm['nombre_cultivar'] = (string) $editRow['nombre_cultivar'];
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No se pudo cargar el cultivar para edición.';
        }
    }
}
?>
<section class="section-panel">
    <?php if ($cultivarEditId !== null): ?>
        <h2>Editar cultivar <span class="badge bg-secondary fs-6">#<?php echo $cultivarEditId; ?></span></h2>
        <p>Modificá la especie o el nombre del cultivar y guardá los cambios.</p>
    <?php else: ?>
        <h2>Alta de cultivar</h2>
        <p>Selecciona una especie y registra el cultivar en la tabla <strong>cultivares</strong>.</p>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($speciesOptions === []): ?>
        <div class="alert alert-warning mb-0">Primero debes cargar al menos una especie para poder dar de alta cultivares.</div>
    <?php else: ?>
        <form method="post" class="row g-3 mt-1">
            <?php if ($cultivarEditId !== null): ?>
                <input type="hidden" name="update_cultivar" value="1">
                <input type="hidden" name="cultivar_edit_id" value="<?php echo $cultivarEditId; ?>">
            <?php else: ?>
                <input type="hidden" name="save_cultivar" value="1">
            <?php endif; ?>

            <div class="col-md-5">
                <label for="id_especie" class="form-label">Especie *</label>
                <select class="form-select" id="id_especie" name="id_especie" required>
                    <option value="">Selecciona una especie</option>
                    <?php foreach ($speciesOptions as $speciesOption): ?>
                        <option value="<?php echo htmlspecialchars((string) $speciesOption['id_especie'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $cultivarForm['id_especie'] === (string) $speciesOption['id_especie'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) $speciesOption['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-7">
                <label for="nombre_cultivar" class="form-label">Nombre del cultivar *</label>
                <input type="text" class="form-control" id="nombre_cultivar" name="nombre_cultivar" maxlength="150" value="<?php echo htmlspecialchars($cultivarForm['nombre_cultivar'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-brand"><?php echo $cultivarEditId !== null ? 'Actualizar cultivar' : 'Guardar cultivar'; ?></button>
                <a href="/index.php?page=cultivares" class="btn btn-outline-brand">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</section>

<section class="section-panel">
    <h2>Cultivares cargados</h2>
    <p>Listado de cultivares vinculados a su especie.</p>

    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Especie</th>
                    <th>Cultivar</th>
                    <th>Alta</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($cultivares === []): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">No hay cultivares cargados aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cultivares as $cultivar): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $cultivar['id_cultivar'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $cultivar['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $cultivar['nombre_cultivar'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($cultivar['created_at_cultivar'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-nowrap">
                                <a href="/index.php?page=cultivares&edit=<?php echo urlencode((string) $cultivar['id_cultivar']); ?>" class="btn btn-sm btn-outline-light me-1"><i class="bi bi-pencil"></i> Editar</a>
                                <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar el cultivar &quot;<?php echo htmlspecialchars(addslashes((string) $cultivar['nombre_cultivar']), ENT_QUOTES, 'UTF-8'); ?>&quot;? Esta acción no se puede deshacer.')">
                                    <input type="hidden" name="delete_cultivar" value="1">
                                    <input type="hidden" name="cultivar_delete_id" value="<?php echo htmlspecialchars((string) $cultivar['id_cultivar'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>