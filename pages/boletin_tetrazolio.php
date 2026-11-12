<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/boletin_edicion.php';
[$boletinEditId, $boletinEdicionInvalida] = boletin_edicion_cargar('tetrazolio', $boletinTetrazolioForm, $errors);

$clientesOptions = [];
$especiesOptions = [];
$boletinesTetrazolio = [];

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
    $especiesOptions = array_values(array_filter(
        $statement->fetchAll(),
        static fn(array $especie): bool => !boletin_tetrazolio_soja_especie_valida(
            (string) $especie['nombre_especie'],
            $especie['cientifico_especie'] ?? null
        )
    ));
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el selector de especies.';
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
        'SELECT b.id_boletin_tetrazolio, b.muestra_nro, b.fecha_inicio, b.fecha_finalizacion, b.analista, b.resultado_porcentaje, b.resultado_no_viables_porcentaje, cl.nombre_cliente, e.nombre_especie
         FROM boletines_tetrazolio b
         INNER JOIN clientes cl ON cl.id_cliente = b.id_cliente AND cl.id_empresa = b.id_empresa
         LEFT JOIN especies e ON e.id_especie = b.id_especie AND e.id_empresa = b.id_empresa
         WHERE ' . implode(' AND ', $conditions) . '
         ORDER BY b.id_boletin_tetrazolio DESC'
    );
    $statement->execute($params);
    $boletinesTetrazolio = $statement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el listado de boletines de Tetrazolio.';
}
?>
<section class="section-panel">
    <h2>Boletín de Tetrazolio</h2>
    <?php if ($boletinEditId !== null): ?>
        <p>Editando boletín #<?php echo $boletinEditId; ?>. <a href="/index.php?page=boletin_tetrazolio">Cancelar edición</a></p>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>
    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($especiesOptions === []): ?>
        <div class="alert alert-warning">Este boletín corresponde a especies distintas de soja. Registra una especie compatible para continuar.</div>
    <?php else: ?>
        <form method="post" class="row g-3 mt-1" id="boletin-form">
        <input type="hidden" name="save_boletin_tetrazolio" value="1">
        <?php if (isset($_GET['ingreso_id']) && filter_var($_GET['ingreso_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false): ?>
            <input type="hidden" name="ingreso_id" value="<?php echo (int) $_GET['ingreso_id']; ?>">
        <?php endif; ?>
        <?php if ($boletinEditId !== null): ?>
            <input type="hidden" name="id_boletin" value="<?php echo $boletinEditId; ?>">
        <?php endif; ?>
        <div class="col-md-4">
            <label for="tet_cliente" class="form-label">Cliente *</label>
            <select class="form-select" id="tet_cliente" name="id_cliente" required>
                <option value="">Selecciona un cliente</option>
                <?php foreach ($clientesOptions as $cliente): ?>
                    <option value="<?php echo (int) $cliente['id_cliente']; ?>" <?php echo $boletinTetrazolioForm['id_cliente'] === (string) $cliente['id_cliente'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $cliente['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label for="tet_muestra" class="form-label">Muestra N° *</label>
            <input class="form-control" id="tet_muestra" name="muestra_nro" maxlength="100" value="<?php echo htmlspecialchars($boletinTetrazolioForm['muestra_nro'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="col-md-4">
            <label for="tet_especie" class="form-label">Especie remitente *</label>
            <select class="form-select" id="tet_especie" name="id_especie" data-scientific-target="tet_especie_latin" required>
                <option value="">Selecciona una especie</option>
                <?php foreach ($especiesOptions as $especie): ?>
                    <option value="<?php echo (int) $especie['id_especie']; ?>" data-scientific="<?php echo htmlspecialchars((string) ($especie['cientifico_especie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo $boletinTetrazolioForm['id_especie'] === (string) $especie['id_especie'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $especie['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label for="tet_especie_latin" class="form-label">Especie (Latín)</label>
            <input class="form-control" id="tet_especie_latin" readonly aria-readonly="true" placeholder="Se completa al seleccionar la especie">
        </div>

        <div class="col-md-3">
            <label for="tet_fecha_inicio" class="form-label">Fecha de inicio</label>
            <input type="date" class="form-control" id="tet_fecha_inicio" name="fecha_inicio" value="<?php echo htmlspecialchars($boletinTetrazolioForm['fecha_inicio'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-3">
            <label for="tet_fecha_finalizacion" class="form-label">Fecha de finalización</label>
            <input type="date" class="form-control" id="tet_fecha_finalizacion" name="fecha_finalizacion" value="<?php echo htmlspecialchars($boletinTetrazolioForm['fecha_finalizacion'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-6">
            <label for="tet_analista" class="form-label">Analista</label>
            <input class="form-control" id="tet_analista" name="analista" maxlength="150" value="<?php echo htmlspecialchars($boletinTetrazolioForm['analista'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-12">
            <h3 class="h6 mb-0">Metodología</h3>
        </div>
        <div class="col-md-3">
            <label for="tet_humedecimiento" class="form-label">Tiempo de humedecimiento</label>
            <input class="form-control" id="tet_humedecimiento" name="tiempo_humedecimiento" maxlength="100" value="<?php echo htmlspecialchars($boletinTetrazolioForm['tiempo_humedecimiento'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-3">
            <label for="tet_tincion" class="form-label">Tiempo de tinción</label>
            <input class="form-control" id="tet_tincion" name="tiempo_tincion" maxlength="100" value="<?php echo htmlspecialchars($boletinTetrazolioForm['tiempo_tincion'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-3">
            <label for="tet_temperatura" class="form-label">Temperatura de tinción</label>
            <input class="form-control" id="tet_temperatura" name="temperatura_tincion" maxlength="100" value="<?php echo htmlspecialchars($boletinTetrazolioForm['temperatura_tincion'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-3">
            <label for="tet_concentracion" class="form-label">Concentración de TZ</label>
            <input class="form-control" id="tet_concentracion" name="concentracion_tz" maxlength="100" value="<?php echo htmlspecialchars($boletinTetrazolioForm['concentracion_tz'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-12">
            <h3 class="h6 mb-0">Repeticiones</h3>
        </div>
        <div class="col-12 table-responsive">
            <table class="table table-dark table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>N° de repetición</th>
                        <th>Semillas viables</th>
                        <th>Semillas no viables</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($numero = 1; $numero <= 4; $numero++): ?>
                        <tr>
                            <th><?php echo $numero; ?></th>
                            <td><input type="number" class="form-control" min="0" step="1" name="repeticiones[<?php echo $numero; ?>][viables]" value="<?php echo htmlspecialchars($boletinTetrazolioForm['repeticiones'][$numero]['viables'], ENT_QUOTES, 'UTF-8'); ?>" required></td>
                            <td><input type="number" class="form-control" min="0" step="1" name="repeticiones[<?php echo $numero; ?>][no_viables]" value="<?php echo htmlspecialchars($boletinTetrazolioForm['repeticiones'][$numero]['no_viables'], ENT_QUOTES, 'UTF-8'); ?>" required></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
        <div class="col-12">
            <label for="tet_observaciones" class="form-label">Observaciones</label>
            <textarea class="form-control" id="tet_observaciones" name="observaciones" rows="4"><?php echo htmlspecialchars($boletinTetrazolioForm['observaciones'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
        <div class="col-12"><button type="submit" class="btn btn-brand" <?php echo $boletinEdicionInvalida ? 'disabled' : ''; ?>><?php echo $boletinEditId !== null ? 'Actualizar boletín' : 'Guardar boletín'; ?></button></div>
    </form>
    <?php endif; ?>
</section>

<section class="section-panel" id="tetrazolio-listado">
    <h2>Boletines cargados</h2>
    <form method="get" class="row g-3 mb-3" action="/index.php#tetrazolio-listado">
        <input type="hidden" name="page" value="boletin_tetrazolio">
        <div class="col-md-4">
            <label for="tet_filtro_cliente" class="form-label">Filtrar por cliente</label>
            <select class="form-select" id="tet_filtro_cliente" name="filtro_cliente">
                <option value="">Todos</option>
                <?php foreach ($clientesOptions as $cliente): ?>
                    <option value="<?php echo (int) $cliente['id_cliente']; ?>" <?php echo $filtroCliente === (string) $cliente['id_cliente'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $cliente['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-brand w-100"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="/index.php?page=boletin_tetrazolio#tetrazolio-listado" class="btn btn-outline-brand" title="Limpiar filtro"><i class="bi bi-x-circle"></i></a>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Muestra</th>
                    <th>Cliente</th>
                    <th>Especie</th>
                    <th>Inicio</th>
                    <th>Finalización</th>
                    <th>Analista</th>
                    <th>Viables (%)</th>
                    <th>No viables (%)</th>
                    <th>PDF</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($boletinesTetrazolio === []): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">No hay boletines cargados aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($boletinesTetrazolio as $boletin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $boletin['muestra_nro'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $boletin['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($boletin['nombre_especie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(format_date_short($boletin['fecha_inicio'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(format_date_short($boletin['fecha_finalizacion'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($boletin['analista'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(number_format((float) $boletin['resultado_porcentaje'], 2, ',', '.') . ' %', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(number_format((float) $boletin['resultado_no_viables_porcentaje'], 2, ',', '.') . ' %', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><a class="btn btn-sm btn-outline-light" href="/index.php?page=boletin_tetrazolio&amp;editar=<?php echo (int) $boletin['id_boletin_tetrazolio']; ?>#boletin-form">Editar</a> <a class="btn btn-sm btn-outline-light" href="/index.php?boletin_tetrazolio_pdf=<?php echo urlencode((string) $boletin['id_boletin_tetrazolio']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Abrir PDF de la muestra <?php echo htmlspecialchars((string) $boletin['muestra_nro'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-file-earmark-pdf"></i> PDF</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>