<?php

declare(strict_types=1);

$determinacionesLibro = [];
try {
    $statement = db()->prepare('SELECT id_determinacion_libro, nombre_determinacion_libro, costo, created_at_determinacion_libro FROM determinaciones_libro_ingreso WHERE id_empresa = :id_empresa ORDER BY id_determinacion_libro');
    $statement->execute([':id_empresa' => current_empresa_id()]);
    $determinacionesLibro = $statement->fetchAll();

    if (isset($_GET['edit']) && $determinacionLibroEditId === null && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $editRow = null;
        foreach ($determinacionesLibro as $determinacion) {
            if ((int) $determinacion['id_determinacion_libro'] === $editId) {
                $editRow = $determinacion;
                break;
            }
        }
        if ($editRow === null) {
            $errors[] = 'La determinación a editar no existe o no pertenece a la empresa activa.';
        } else {
            $determinacionLibroEditId = (int) $editRow['id_determinacion_libro'];
            $determinacionLibroForm['nombre_determinacion_libro'] = (string) $editRow['nombre_determinacion_libro'];
            $determinacionLibroForm['costo'] = (string) $editRow['costo'];
        }
    }
} catch (PDOException $exception) {
    $errors[] = 'No se pudo cargar el listado de determinaciones. Verifica la base de datos y la migración de costos.';
}
?>
<section class="section-panel">
    <h2><?php echo $determinacionLibroEditId !== null ? 'Editar determinación' : 'Alta de determinación'; ?></h2>
    <p>Administra las determinaciones del Libro de Ingreso y sus costos para futuros presupuestos.</p>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>
    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="/index.php?page=determinaciones" class="row g-3 mt-1">
        <?php if ($determinacionLibroEditId !== null): ?>
            <input type="hidden" name="update_determinacion_libro" value="1">
            <input type="hidden" name="determinacion_libro_edit_id" value="<?php echo $determinacionLibroEditId; ?>">
        <?php else: ?>
            <input type="hidden" name="save_determinacion_libro" value="1">
        <?php endif; ?>
        <div class="col-md-9">
            <label for="nombre_determinacion_libro" class="form-label">Nombre de la determinación *</label>
            <input type="text" class="form-control" id="nombre_determinacion_libro" name="nombre_determinacion_libro" maxlength="255" value="<?php echo htmlspecialchars($determinacionLibroForm['nombre_determinacion_libro'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="col-md-3">
            <label for="costo" class="form-label">Costo</label>
            <input type="text" inputmode="decimal" class="form-control" id="costo" name="costo" maxlength="13" value="<?php echo htmlspecialchars($determinacionLibroForm['costo'], ENT_QUOTES, 'UTF-8'); ?>" aria-describedby="costo-ayuda">
            <div class="form-text" id="costo-ayuda">Hasta dos decimales, con coma o punto. Vacío se guarda como 0,00.</div>
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-brand"><?php echo $determinacionLibroEditId !== null ? 'Actualizar determinación' : 'Guardar determinación'; ?></button>
            <a href="/index.php?page=determinaciones" class="btn btn-outline-brand">Cancelar</a>
        </div>
    </form>
</section>

<section class="section-panel">
    <h2>Determinaciones cargadas</h2>
    <p>Listado de determinaciones de la empresa activa.</p>
    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Costo</th>
                    <th>Alta</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($determinacionesLibro === []): ?>
                    <tr><td colspan="5" class="text-center text-muted">No hay determinaciones cargadas aún.</td></tr>
                <?php else: ?>
                    <?php foreach ($determinacionesLibro as $determinacion): ?>
                        <tr>
                            <td><?php echo (int) $determinacion['id_determinacion_libro']; ?></td>
                            <td><?php echo htmlspecialchars((string) $determinacion['nombre_determinacion_libro'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo number_format((float) $determinacion['costo'], 2, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars((string) $determinacion['created_at_determinacion_libro'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-nowrap">
                                <a href="/index.php?page=determinaciones&amp;edit=<?php echo (int) $determinacion['id_determinacion_libro']; ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-pencil"></i> Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
