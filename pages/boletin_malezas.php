<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/boletin_edicion.php';
[$boletinEditId, $boletinEdicionInvalida] = boletin_edicion_cargar('malezas', $boletinMalezasForm, $errors);

$clientesOptions = [];
$especiesOptions = [];
$especiesLatinasOptions = [];
$determinacionesOptions = [];
$boletinesMalezas = [];
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
    $especiesLatinasOptions = array_values(array_unique(array_filter(array_map(
        static fn(array $especie): string => trim((string) ($especie['cientifico_especie'] ?? '')),
        $especiesOptions
    ), static fn(string $nombre): bool => $nombre !== '')));
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el selector de especies.';
}

try {
    $statement = db()->prepare('SELECT id_determinacion, nombre_determinacion FROM otras_determinaciones WHERE id_empresa = :id_empresa ORDER BY nombre_determinacion ASC');
    $statement->execute([':id_empresa' => current_empresa_id()]);
    $determinacionesOptions = $statement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el selector de determinaciones.';
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
        'SELECT b.id_boletin_malezas, b.analisis_nro, b.muestra_nro, b.fecha_analisis, COALESCE(e.nombre_especie, b.especie_remitente) AS especie_remitente, COALESCE(e.cientifico_especie, b.especie_latin) AS especie_latin, b.tipo_analisis, cl.nombre_cliente
         FROM boletines_malezas b
         INNER JOIN clientes cl ON cl.id_cliente = b.id_cliente AND cl.id_empresa = b.id_empresa
         LEFT JOIN especies e ON e.id_especie = b.id_especie AND e.id_empresa = b.id_empresa
         WHERE ' . implode(' AND ', $conditions) . '
         ORDER BY b.id_boletin_malezas DESC'
    );
    $statement->execute($params);
    $boletinesMalezas = $statement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el listado de análisis de malezas.';
}

$tiposAnalisis = [
    'completo' => '* Completo',
    'limitado' => '** Limitado',
    'reducido' => '*** Reducido',
    'reducido_limitado' => '**** Limitado y reducido',
];
?>
<section class="section-panel">
    <h2>Otras especies en número</h2>
    <?php if ($boletinEditId !== null): ?>
        <p>Editando boletín #<?php echo $boletinEditId; ?>. <a href="/index.php?page=boletin_malezas">Cancelar edición</a></p>
    <?php endif; ?>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>
    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" class="row g-3 mt-1" id="boletin-form">
        <input type="hidden" name="save_boletin_malezas" value="1">
        <?php if (isset($_GET['ingreso_id']) && filter_var($_GET['ingreso_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false): ?>
            <input type="hidden" name="ingreso_id" value="<?php echo (int) $_GET['ingreso_id']; ?>">
        <?php endif; ?>
        <?php if ($boletinEditId !== null): ?>
            <input type="hidden" name="id_boletin" value="<?php echo $boletinEditId; ?>">
        <?php endif; ?>
        <div class="col-md-4">
            <label for="malezas_cliente" class="form-label">Cliente *</label>
            <select class="form-select" id="malezas_cliente" name="id_cliente" required>
                <option value="">Selecciona un cliente</option>
                <?php foreach ($clientesOptions as $cliente): ?>
                    <option value="<?php echo (int) $cliente['id_cliente']; ?>" <?php echo $boletinMalezasForm['id_cliente'] === (string) $cliente['id_cliente'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $cliente['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label for="malezas_analisis" class="form-label">Análisis Nº *</label>
            <input class="form-control" id="malezas_analisis" name="analisis_nro" maxlength="100" value="<?php echo htmlspecialchars($boletinMalezasForm['analisis_nro'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="col-md-4">
            <label for="malezas_muestra" class="form-label">Muestra Nº</label>
            <input class="form-control" id="malezas_muestra" name="muestra_nro" maxlength="100" value="<?php echo htmlspecialchars($boletinMalezasForm['muestra_nro'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-6">
            <label for="malezas_especie" class="form-label">Especie remitente *</label>
            <select class="form-select" id="malezas_especie" name="id_especie" data-scientific-target="malezas_especie_latin" required>
                <option value="">Selecciona una especie</option>
                <?php foreach ($especiesOptions as $especie): ?>
                    <option value="<?php echo (int) $especie['id_especie']; ?>" data-scientific="<?php echo htmlspecialchars((string) ($especie['cientifico_especie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo $boletinMalezasForm['id_especie'] === (string) $especie['id_especie'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $especie['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label for="malezas_especie_latin" class="form-label">Especie (Latín)</label>
            <input class="form-control" id="malezas_especie_latin" readonly aria-readonly="true" placeholder="Se completa al seleccionar la especie">
        </div>
        <div class="col-md-4">
            <label for="malezas_lote" class="form-label">Lote</label>
            <input class="form-control" id="malezas_lote" name="lote" maxlength="100" value="<?php echo htmlspecialchars($boletinMalezasForm['lote'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-4">
            <label for="malezas_fecha" class="form-label">Fecha</label>
            <input type="date" class="form-control" id="malezas_fecha" name="fecha_analisis" value="<?php echo htmlspecialchars($boletinMalezasForm['fecha_analisis'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-4">
            <label for="malezas_analista" class="form-label">Analista</label>
            <input class="form-control" id="malezas_analista" name="analista" maxlength="150" value="<?php echo htmlspecialchars($boletinMalezasForm['analista'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-12">
            <h3 class="h6 mb-0">Tipo de análisis</h3>
        </div>
        <?php foreach ($tiposAnalisis as $tipo => $label): ?>
            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="radio" id="tipo_malezas_<?php echo $tipo; ?>" name="tipo_analisis" value="<?php echo $tipo; ?>" <?php echo $boletinMalezasForm['tipo_analisis'] === $tipo ? 'checked' : ''; ?> required>
                    <label class="form-check-label" for="tipo_malezas_<?php echo $tipo; ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></label>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="col-md-4">
            <label for="peso_analisis" class="form-label">Análisis realizado en (g)</label>
            <input type="number" class="form-control" id="peso_analisis" name="peso_analisis" min="0" step="0.0001" value="<?php echo htmlspecialchars($boletinMalezasForm['peso_analisis'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3 class="h6 mb-0">Otras especies en número</h3>
            <button type="button" class="btn btn-sm btn-outline-brand" id="malezas-add-especie"><i class="bi bi-plus-lg"></i> Agregar especie</button>
        </div>
        <div class="col-12 table-responsive">
            <table class="table table-dark table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>Otras especies en número</th>
                        <th style="width: 180px">Nº semillas</th>
                        <th style="width: 56px"></th>
                    </tr>
                </thead>
                <tbody id="malezas-especies-rows">
                    <?php foreach ($boletinMalezasForm['especies_halladas'] as $index => $row): ?>
                        <tr>
                            <td><input class="form-control" name="especies_halladas[<?php echo (int) $index; ?>][especie_latin]" list="malezas-especies-latin-options" maxlength="255" value="<?php echo htmlspecialchars((string) $row['especie_latin'], ENT_QUOTES, 'UTF-8'); ?>"></td>
                            <td><input type="number" class="form-control" name="especies_halladas[<?php echo (int) $index; ?>][numero_semillas]" min="0" step="1" value="<?php echo htmlspecialchars((string) $row['numero_semillas'], ENT_QUOTES, 'UTF-8'); ?>"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger malezas-remove-row" aria-label="Quitar especie"><i class="bi bi-x-lg"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="col-12">
            <label for="malezas_determinaciones" class="form-label">Otras determinaciones</label>
            <select class="form-select" id="malezas_determinaciones" name="id_otras_determinaciones[]" multiple size="5">
                <?php foreach ($determinacionesOptions as $determinacion): ?>
                    <option value="<?php echo (int) $determinacion['id_determinacion']; ?>" <?php echo in_array((int) $determinacion['id_determinacion'], $boletinMalezasForm['id_otras_determinaciones'], true) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $determinacion['nombre_determinacion'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Puedes seleccionar varias opciones.</div>
        </div>
        <div class="col-12">
            <label for="malezas_determinacion_texto" class="form-label">Otra determinación (si no está en la lista)</label>
            <input class="form-control" id="malezas_determinacion_texto" name="otras_determinaciones_texto" maxlength="255" value="<?php echo htmlspecialchars($boletinMalezasForm['otras_determinaciones_texto'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-12">
            <label for="malezas_observaciones" class="form-label">Observaciones</label>
            <textarea class="form-control" id="malezas_observaciones" name="observaciones" rows="3"><?php echo htmlspecialchars($boletinMalezasForm['observaciones'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
        <div class="col-12"><button type="submit" class="btn btn-brand" <?php echo $boletinEdicionInvalida ? 'disabled' : ''; ?>><?php echo $boletinEditId !== null ? 'Actualizar análisis' : 'Guardar análisis'; ?></button></div>
    </form>
</section>

<datalist id="malezas-especies-latin-options">
    <?php foreach ($especiesLatinasOptions as $nombreCientifico): ?>
        <option value="<?php echo htmlspecialchars($nombreCientifico, ENT_QUOTES, 'UTF-8'); ?>"></option>
    <?php endforeach; ?>
</datalist>

<section class="section-panel" id="malezas-listado">
    <h2>Análisis cargados</h2>
    <form method="get" class="row g-3 mb-3" action="/index.php#malezas-listado">
        <input type="hidden" name="page" value="boletin_malezas">
        <div class="col-md-4">
            <label for="malezas-filtro-cliente" class="form-label">Filtrar por cliente</label>
            <select class="form-select" id="malezas-filtro-cliente" name="filtro_cliente">
                <option value="">Todos</option>
                <?php foreach ($clientesOptions as $cliente): ?>
                    <option value="<?php echo (int) $cliente['id_cliente']; ?>" <?php echo $filtroCliente === (string) $cliente['id_cliente'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $cliente['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-brand w-100"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="/index.php?page=boletin_malezas#malezas-listado" class="btn btn-outline-brand" title="Limpiar filtro"><i class="bi bi-x-circle"></i></a>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Análisis</th>
                    <th>Cliente</th>
                    <th>Muestra</th>
                    <th>Especie remitente</th>
                    <th>Especie (Latín)</th>
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th>PDF</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($boletinesMalezas === []): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No hay análisis cargados aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($boletinesMalezas as $boletin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $boletin['analisis_nro'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $boletin['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($boletin['muestra_nro'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $boletin['especie_remitente'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $boletin['especie_latin'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($tiposAnalisis[$boletin['tipo_analisis']] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(format_date_short($boletin['fecha_analisis'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><a class="btn btn-sm btn-outline-light" href="/index.php?page=boletin_malezas&amp;editar=<?php echo (int) $boletin['id_boletin_malezas']; ?>#boletin-form">Editar</a> <a class="btn btn-sm btn-outline-light" href="/index.php?boletin_malezas_pdf=<?php echo urlencode((string) $boletin['id_boletin_malezas']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Abrir PDF del análisis <?php echo htmlspecialchars((string) $boletin['analisis_nro'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-file-earmark-pdf"></i> PDF</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<template id="malezas-species-template">
    <tr>
        <td><input class="form-control" data-field="especie_latin" list="malezas-especies-latin-options" maxlength="255"></td>
        <td><input type="number" class="form-control" data-field="numero_semillas" min="0" step="1"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger malezas-remove-row" aria-label="Quitar especie"><i class="bi bi-x-lg"></i></button></td>
    </tr>
</template>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.getElementById('malezas-especies-rows');
        const addButton = document.getElementById('malezas-add-especie');
        const template = document.getElementById('malezas-species-template');
        if (!rows || !addButton || !template) return;

        addButton.addEventListener('click', function() {
            const index = Date.now().toString() + Math.floor(Math.random() * 1000);
            const row = template.content.firstElementChild.cloneNode(true);
            row.querySelector('[data-field="especie_latin"]').name = 'especies_halladas[' + index + '][especie_latin]';
            row.querySelector('[data-field="numero_semillas"]').name = 'especies_halladas[' + index + '][numero_semillas]';
            rows.appendChild(row);
        });

        rows.addEventListener('click', function(event) {
            const removeButton = event.target.closest('.malezas-remove-row');
            if (removeButton) removeButton.closest('tr').remove();
        });
    });
</script>