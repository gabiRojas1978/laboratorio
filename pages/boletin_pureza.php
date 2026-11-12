<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/boletin_edicion.php';
[$boletinEditId, $boletinEdicionInvalida] = boletin_edicion_cargar('pureza', $boletinPurezaForm, $errors);

$clientesOptions = [];
$especiesOptions = [];
$determinacionesOptions = [];
$boletinesPureza = [];

try {
    $statement = db()->prepare('SELECT id_cliente, nombre_cliente FROM clientes WHERE id_empresa = :id_empresa ORDER BY nombre_cliente ASC');
    $statement->execute([':id_empresa' => current_empresa_id()]);
    $clientesOptions = $statement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el selector de clientes.';
}

try {
    $statement = db()->prepare('SELECT id_especie, nombre_especie, cientifico_especie FROM especies WHERE id_empresa = :id_empresa ORDER BY nombre_especie ASC');
    $statement->execute([':id_empresa' => current_empresa_id()]);
    $especiesOptions = $statement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el selector de especies.';
}

try {
    $statement = db()->prepare('SELECT id_determinacion, nombre_determinacion FROM otras_determinaciones WHERE id_empresa = :id_empresa ORDER BY nombre_determinacion ASC');
    $statement->execute([':id_empresa' => current_empresa_id()]);
    $determinacionesOptions = $statement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el catálogo de otras determinaciones.';
}

$filtroCliente = trim((string) ($_GET['filtro_cliente'] ?? ''));
$filtroClienteId = filter_var($filtroCliente, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$conditions = ['b.id_empresa = :id_empresa'];
$params = [':id_empresa' => current_empresa_id()];
if ($filtroClienteId !== false) {
    $conditions[] = 'b.id_cliente = :id_cliente';
    $params[':id_cliente'] = $filtroClienteId;
}

try {
    $statement = db()->prepare(
        'SELECT b.id_boletin_pureza, b.analisis_nro, b.fecha_analisis, b.analista, b.porcentaje_semilla_pura, b.porcentaje_materia_inerte, b.porcentaje_otras_semillas, cl.nombre_cliente, e.nombre_especie
         FROM boletines_pureza b
         INNER JOIN clientes cl ON cl.id_cliente = b.id_cliente AND cl.id_empresa = b.id_empresa
         LEFT JOIN especies e ON e.id_especie = b.id_especie AND e.id_empresa = b.id_empresa
         WHERE ' . implode(' AND ', $conditions) . '
         ORDER BY b.id_boletin_pureza DESC'
    );
    $statement->execute($params);
    $boletinesPureza = $statement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el listado de boletines de Pureza física-botánica.';
}

$pesoInicialForm = is_numeric($boletinPurezaForm['peso_inicial']) ? (float) $boletinPurezaForm['peso_inicial'] : 0.0;
$decimalesForm = $pesoInicialForm > 0 ? decimales_boletin_pureza($pesoInicialForm) : null;
?>
<section class="section-panel">
    <h2>Boletín de Pureza física-botánica</h2>
    <?php if ($boletinEditId !== null): ?>
        <p>Editando boletín #<?php echo $boletinEditId; ?>. <a href="/index.php?page=boletin_pureza">Cancelar edición</a></p>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>
    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" class="row g-3 mt-1" id="boletin-form">
        <input type="hidden" name="save_boletin_pureza" value="1">
        <?php if (isset($_GET['ingreso_id']) && filter_var($_GET['ingreso_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false): ?>
            <input type="hidden" name="ingreso_id" value="<?php echo (int) $_GET['ingreso_id']; ?>">
        <?php endif; ?>
        <?php if ($boletinEditId !== null): ?>
            <input type="hidden" name="id_boletin" value="<?php echo $boletinEditId; ?>">
        <?php endif; ?>
        <div class="col-md-4">
            <label for="pureza_cliente" class="form-label">Cliente *</label>
            <select class="form-select" id="pureza_cliente" name="id_cliente" required>
                <option value="">Selecciona un cliente</option>
                <?php foreach ($clientesOptions as $cliente): ?>
                    <option value="<?php echo (int) $cliente['id_cliente']; ?>" <?php echo $boletinPurezaForm['id_cliente'] === (string) $cliente['id_cliente'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $cliente['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label for="pureza_analisis" class="form-label">Análisis N° *</label>
            <input class="form-control" id="pureza_analisis" name="analisis_nro" maxlength="100" value="<?php echo htmlspecialchars($boletinPurezaForm['analisis_nro'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="col-md-4">
            <label for="pureza_especie" class="form-label">Especie remitente *</label>
            <select class="form-select" id="pureza_especie" name="id_especie" data-scientific-target="pureza_especie_latin" required>
                <option value="">Selecciona una especie</option>
                <?php foreach ($especiesOptions as $especie): ?>
                    <option value="<?php echo (int) $especie['id_especie']; ?>" data-scientific="<?php echo htmlspecialchars((string) ($especie['cientifico_especie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo $boletinPurezaForm['id_especie'] === (string) $especie['id_especie'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $especie['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label for="pureza_especie_latin" class="form-label">Especie (Latín)</label>
            <input class="form-control" id="pureza_especie_latin" readonly aria-readonly="true" placeholder="Se completa al seleccionar la especie">
        </div>
        <div class="col-md-4">
            <label for="pureza_fecha" class="form-label">Fecha</label>
            <input type="date" class="form-control" id="pureza_fecha" name="fecha_analisis" value="<?php echo htmlspecialchars($boletinPurezaForm['fecha_analisis'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-4">
            <label for="pureza_analista" class="form-label">Analista</label>
            <input class="form-control" id="pureza_analista" name="analista" maxlength="150" value="<?php echo htmlspecialchars($boletinPurezaForm['analista'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-4">
            <label for="peso_inicial" class="form-label">Peso inicial (g) *</label>
            <input type="number" class="form-control" id="peso_inicial" name="peso_inicial" min="0.0001" step="0.0001" value="<?php echo htmlspecialchars($boletinPurezaForm['peso_inicial'], ENT_QUOTES, 'UTF-8'); ?>" required>
            <?php if ($decimalesForm !== null): ?>
                <div class="form-text">Precisión aplicable según el peso: <?php echo $decimalesForm; ?> decimales.</div>
            <?php endif; ?>
        </div>
        <div class="col-md-4">
            <label for="peso_semilla_pura" class="form-label">Semilla pura (g) *</label>
            <input type="number" class="form-control" id="peso_semilla_pura" name="peso_semilla_pura" min="0" step="0.0001" value="<?php echo htmlspecialchars($boletinPurezaForm['peso_semilla_pura'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="col-12">
            <h3 class="h6 mb-0">Materia inerte</h3>
        </div>
        <div class="col-12" id="pureza-materia-inerte">
            <?php foreach ($boletinPurezaForm['componentes_materia_inerte'] as $index => $componente): ?>
                <div class="row g-2 mb-2 pureza-component-row">
                    <div class="col-md-8"><input class="form-control" name="componentes[materia_inerte][<?php echo (int) $index; ?>][nombre]" maxlength="255" placeholder="Componente" value="<?php echo htmlspecialchars((string) $componente['nombre'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="col-md-3"><input type="number" class="form-control" name="componentes[materia_inerte][<?php echo (int) $index; ?>][peso]" min="0" step="0.0001" placeholder="Gramos" value="<?php echo htmlspecialchars((string) $componente['peso'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 pureza-remove-row" aria-label="Quitar componente"><i class="bi bi-x-lg"></i></button></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="col-12"><button type="button" class="btn btn-sm btn-outline-brand" data-add-pureza="materia_inerte" data-target="pureza-materia-inerte"><i class="bi bi-plus-lg"></i> Agregar componente</button></div>

        <div class="col-12">
            <h3 class="h6 mb-0">Otras semillas</h3>
        </div>
        <div class="col-12" id="pureza-otras-semillas">
            <?php foreach ($boletinPurezaForm['componentes_otras_semillas'] as $index => $componente): ?>
                <div class="row g-2 mb-2 pureza-component-row">
                    <div class="col-md-8"><input class="form-control" name="componentes[otras_semillas][<?php echo (int) $index; ?>][nombre]" maxlength="255" placeholder="Especie / componente" value="<?php echo htmlspecialchars((string) $componente['nombre'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="col-md-3"><input type="number" class="form-control" name="componentes[otras_semillas][<?php echo (int) $index; ?>][peso]" min="0" step="0.0001" placeholder="Gramos" value="<?php echo htmlspecialchars((string) $componente['peso'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 pureza-remove-row" aria-label="Quitar componente"><i class="bi bi-x-lg"></i></button></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="col-12"><button type="button" class="btn btn-sm btn-outline-brand" data-add-pureza="otras_semillas" data-target="pureza-otras-semillas"><i class="bi bi-plus-lg"></i> Agregar especie</button></div>

        <div class="col-12">
            <label for="pureza_determinaciones" class="form-label">Otras determinaciones</label>
            <select class="form-select" id="pureza_determinaciones" name="id_otras_determinaciones[]" multiple size="5">
                <?php foreach ($determinacionesOptions as $determinacion): ?>
                    <option value="<?php echo (int) $determinacion['id_determinacion']; ?>" <?php echo in_array((int) $determinacion['id_determinacion'], $boletinPurezaForm['id_otras_determinaciones'], true) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $determinacion['nombre_determinacion'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Podés seleccionar más de una determinación del catálogo.</div>
        </div>
        <div class="col-12">
            <label for="pureza_determinacion_libre" class="form-label">Otra determinación (si no está en el catálogo)</label>
            <input class="form-control" id="pureza_determinacion_libre" name="otras_determinaciones_texto" maxlength="255" value="<?php echo htmlspecialchars($boletinPurezaForm['otras_determinaciones_texto'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-12">
            <label for="pureza_observaciones" class="form-label">Observaciones</label>
            <textarea class="form-control" id="pureza_observaciones" name="observaciones" rows="3"><?php echo htmlspecialchars($boletinPurezaForm['observaciones'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
        <div class="col-12"><button type="submit" class="btn btn-brand" <?php echo $boletinEdicionInvalida ? 'disabled' : ''; ?>><?php echo $boletinEditId !== null ? 'Actualizar boletín' : 'Guardar boletín'; ?></button></div>
    </form>
</section>

<section class="section-panel" id="pureza-listado">
    <h2>Boletines cargados</h2>
    <form method="get" class="row g-3 mb-3" action="/index.php#pureza-listado">
        <input type="hidden" name="page" value="boletin_pureza">
        <div class="col-md-4">
            <label for="pureza_filtro_cliente" class="form-label">Filtrar por cliente</label>
            <select class="form-select" id="pureza_filtro_cliente" name="filtro_cliente">
                <option value="">Todos</option>
                <?php foreach ($clientesOptions as $cliente): ?>
                    <option value="<?php echo (int) $cliente['id_cliente']; ?>" <?php echo $filtroCliente === (string) $cliente['id_cliente'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $cliente['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-brand w-100"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="/index.php?page=boletin_pureza#pureza-listado" class="btn btn-outline-brand" title="Limpiar filtro"><i class="bi bi-x-circle"></i></a>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Análisis</th>
                    <th>Cliente</th>
                    <th>Especie</th>
                    <th>Fecha</th>
                    <th>Analista</th>
                    <th>S.P. (%)</th>
                    <th>M.I. (%)</th>
                    <th>O.S. (%)</th>
                    <th>PDF</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($boletinesPureza === []): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">No hay boletines cargados aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($boletinesPureza as $boletin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $boletin['analisis_nro'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $boletin['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($boletin['nombre_especie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(format_date_short($boletin['fecha_analisis'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($boletin['analista'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(number_format((float) $boletin['porcentaje_semilla_pura'], 2, ',', '.') . ' %', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(number_format((float) $boletin['porcentaje_materia_inerte'], 2, ',', '.') . ' %', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(number_format((float) $boletin['porcentaje_otras_semillas'], 2, ',', '.') . ' %', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><a class="btn btn-sm btn-outline-light" href="/index.php?page=boletin_pureza&amp;editar=<?php echo (int) $boletin['id_boletin_pureza']; ?>#boletin-form">Editar</a> <a class="btn btn-sm btn-outline-light" href="/index.php?boletin_pureza_pdf=<?php echo urlencode((string) $boletin['id_boletin_pureza']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Abrir PDF del análisis <?php echo htmlspecialchars((string) $boletin['analisis_nro'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-file-earmark-pdf"></i> PDF</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<template id="pureza-component-template">
    <div class="row g-2 mb-2 pureza-component-row">
        <div class="col-md-8"><input class="form-control" data-field="nombre" maxlength="255" placeholder="Componente"></div>
        <div class="col-md-3"><input type="number" class="form-control" data-field="peso" min="0" step="0.0001" placeholder="Gramos"></div>
        <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 pureza-remove-row" aria-label="Quitar componente"><i class="bi bi-x-lg"></i></button></div>
    </div>
</template>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-add-pureza]').forEach(function(button) {
            button.addEventListener('click', function() {
                const target = document.getElementById(button.dataset.target);
                const type = button.dataset.addPureza;
                const row = document.getElementById('pureza-component-template').content.firstElementChild.cloneNode(true);
                const index = Date.now().toString() + Math.floor(Math.random() * 1000);
                row.querySelector('[data-field="nombre"]').name = 'componentes[' + type + '][' + index + '][nombre]';
                row.querySelector('[data-field="peso"]').name = 'componentes[' + type + '][' + index + '][peso]';
                target.appendChild(row);
            });
        });

        document.addEventListener('click', function(event) {
            const removeButton = event.target.closest('.pureza-remove-row');
            if (removeButton) removeButton.closest('.pureza-component-row').remove();
        });
    });
</script>