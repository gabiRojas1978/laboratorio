<?php

declare(strict_types=1);

$anormalidades = [];
try {
    $statement = db()->prepare('SELECT id_anormalidad, codigo, etiqueta, grupo_tipo, categoria_principal, subcategoria FROM anormalidades WHERE id_empresa = :id_empresa ORDER BY codigo');
    $statement->execute([':id_empresa' => current_empresa_id()]);
    $anormalidades = $statement->fetchAll();

    if (isset($_GET['edit']) && $anormalidadEditId === null && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $editRow = null;
        foreach ($anormalidades as $anormalidad) {
            if ((int) $anormalidad['id_anormalidad'] === $editId) {
                $editRow = $anormalidad;
                break;
            }
        }
        if ($editRow === null) {
            $errors[] = 'La anormalidad a editar no existe o no pertenece a la empresa activa.';
        } else {
            $anormalidadEditId = (int) $editRow['id_anormalidad'];
            foreach (array_keys($anormalidadForm) as $campo) {
                $anormalidadForm[$campo] = (string) $editRow[$campo];
            }
        }
    }
} catch (PDOException $exception) {
    $errors[] = 'No se pudo cargar el catálogo de anormalidades. Verifica la base de datos y la migración de anormalidades.';
}
$escapeAnormalidad = static fn(string $valor): string => htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
$camposAnormalidad = [
    'codigo' => ['Código', 30, 'col-md-3'],
    'etiqueta' => ['Anormalidad (Etiqueta Completa)', 255, 'col-md-9'],
    'grupo_tipo' => ['Grupo / Tipo', 255, 'col-md-4'],
    'categoria_principal' => ['Categoría Principal', 255, 'col-md-4'],
    'subcategoria' => ['Subcategoría', 255, 'col-md-4'],
];
?>
<section class="section-panel">
    <h2><?php echo $anormalidadEditId !== null ? 'Editar anormalidad' : 'Alta de anormalidad'; ?></h2>
    <p>Administrá el catálogo de anormalidades de este laboratorio.</p>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo $escapeAnormalidad($error); ?></div>
    <?php endforeach; ?>
    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?php echo $escapeAnormalidad($successMessage); ?></div>
    <?php endif; ?>
    <form method="post" action="/index.php?page=anormalidades" class="row g-3 mt-1">
        <?php if ($anormalidadEditId !== null): ?>
            <input type="hidden" name="update_anormalidad" value="1">
            <input type="hidden" name="anormalidad_edit_id" value="<?php echo $anormalidadEditId; ?>">
        <?php else: ?>
            <input type="hidden" name="save_anormalidad" value="1">
        <?php endif; ?>
        <?php foreach ($camposAnormalidad as $campo => [$nombre, $maximo, $clase]): ?>
            <div class="<?php echo $clase; ?>">
                <label for="anormalidad_<?php echo $campo; ?>" class="form-label"><?php echo $escapeAnormalidad($nombre); ?> *</label>
                <input type="text" class="form-control" id="anormalidad_<?php echo $campo; ?>" name="<?php echo $campo; ?>" maxlength="<?php echo $maximo; ?>" value="<?php echo $escapeAnormalidad($anormalidadForm[$campo]); ?>" required>
            </div>
        <?php endforeach; ?>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-brand"><?php echo $anormalidadEditId !== null ? 'Actualizar anormalidad' : 'Guardar anormalidad'; ?></button>
            <a href="/index.php?page=anormalidades" class="btn btn-outline-brand">Cancelar</a>
            <a href="/index.php?page=configuracion" class="btn btn-outline-brand">Volver a configuración</a>
        </div>
    </form>
</section>

<section class="section-panel">
    <h2>Anormalidades cargadas</h2>
    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle mb-0">
            <thead><tr><th>Código</th><th>Anormalidad</th><th>Grupo / Tipo</th><th>Categoría Principal</th><th>Subcategoría</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php if ($anormalidades === []): ?>
                    <tr><td colspan="6" class="text-center text-muted">No hay anormalidades cargadas.</td></tr>
                <?php else: ?>
                    <?php foreach ($anormalidades as $anormalidad): ?>
                        <tr>
                            <?php foreach (array_keys($camposAnormalidad) as $campo): ?>
                                <td><?php echo $escapeAnormalidad((string) $anormalidad[$campo]); ?></td>
                            <?php endforeach; ?>
                            <td><a href="/index.php?page=anormalidades&amp;edit=<?php echo (int) $anormalidad['id_anormalidad']; ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-pencil"></i> Editar</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
