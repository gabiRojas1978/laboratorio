<?php

declare(strict_types=1);

$clientesOptions = [];
$especiesOptions = [];
$boletinesGerminacion = [];

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

if (isset($_GET['editar']) && $boletinGerminacionEditId === null) {
    $editId = filter_var($_GET['editar'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($editId !== false) {
        try {
            $editStatement = db()->prepare('SELECT * FROM boletines_germinacion WHERE id_boletin_germinacion = :id AND id_empresa = :id_empresa AND tipo_boletin = :tipo_boletin LIMIT 1');
            $editStatement->execute([':id' => $editId, ':id_empresa' => current_empresa_id(), ':tipo_boletin' => $boletinGerminacionTipo]);
            $editRow = $editStatement->fetch();
            if ($editRow !== false) {
                $boletinGerminacionEditId = (int) $editRow['id_boletin_germinacion'];
                $boletinGerminacionForm['id_cliente'] = (string) $editRow['id_cliente'];
                $boletinGerminacionForm['id_especie'] = (string) ($editRow['id_especie'] ?? '');
                $boletinGerminacionForm['analisis_nro'] = (string) ($editRow['analisis_nro'] ?? '');
                $boletinGerminacionForm['muestra_nro'] = (string) $editRow['muestra_nro'];
                $boletinGerminacionForm['fecha_siembra'] = (string) ($editRow['fecha_siembra'] ?? '');
                $boletinGerminacionForm['fecha_primer_conteo'] = (string) ($editRow['fecha_primer_conteo'] ?? '');
                $boletinGerminacionForm['fecha_segundo_conteo'] = (string) ($editRow['fecha_segundo_conteo'] ?? '');
                $boletinGerminacionForm['analista'] = (string) ($editRow['analista'] ?? '');
                $boletinGerminacionForm['pretratamiento_desde'] = (string) ($editRow['pretratamiento_desde'] ?? '');
                $boletinGerminacionForm['pretratamiento_hasta'] = (string) ($editRow['pretratamiento_hasta'] ?? '');
                $boletinGerminacionForm['pretratamiento_total_dias'] = (string) ($editRow['pretratamiento_total_dias'] ?? '');
                $boletinGerminacionForm['pretratamiento_aplica'] = (string) ($editRow['pretratamiento_aplica'] ?? '');
                $boletinGerminacionForm['pretratamiento_frio'] = (bool) $editRow['pretratamiento_frio'];
                $boletinGerminacionForm['pretratamiento_nitrato_k'] = (bool) $editRow['pretratamiento_nitrato_k'];
                $boletinGerminacionForm['pretratamiento_presecado'] = (bool) $editRow['pretratamiento_presecado'];
                $boletinGerminacionForm['pretratamiento_prelavado'] = (bool) $editRow['pretratamiento_prelavado'];
                $boletinGerminacionForm['sustrato_tipo'] = (string) ($editRow['sustrato_tipo'] ?? '');
                $boletinGerminacionForm['sustrato_otro'] = (string) ($editRow['sustrato_otro'] ?? '');
                $boletinGerminacionForm['temperatura_camara_tipo'] = (string) ($editRow['temperatura_camara_tipo'] ?? '');
                $boletinGerminacionForm['temperatura_camara_otra'] = (string) ($editRow['temperatura_camara_otra'] ?? '');
                $boletinGerminacionForm['observaciones'] = (string) ($editRow['observaciones'] ?? '');

                $repeticionesStatement = db()->prepare(
                    'SELECT nro_repeticion, semillas_sembradas, primer_conteo_normales, primer_conteo_anormales, primer_conteo_duras, primer_conteo_frescas, primer_conteo_muertas, segundo_conteo_normales, segundo_conteo_anormales, segundo_conteo_duras, segundo_conteo_frescas, segundo_conteo_muertas
                     FROM boletin_germinacion_repeticiones
                     WHERE id_boletin_germinacion = :id
                     ORDER BY nro_repeticion ASC'
                );
                $repeticionesStatement->execute([':id' => $boletinGerminacionEditId]);
                foreach ($repeticionesStatement->fetchAll() as $repeticionRow) {
                    $numero = (int) $repeticionRow['nro_repeticion'];
                    $boletinGerminacionForm['repeticiones'][$numero] = [
                        'semillas_sembradas' => (string) $repeticionRow['semillas_sembradas'],
                        'primer_conteo_normales' => (string) ($repeticionRow['primer_conteo_normales'] ?? ''),
                        'primer_conteo_anormales' => (string) ($repeticionRow['primer_conteo_anormales'] ?? ''),
                        'primer_conteo_duras' => (string) ($repeticionRow['primer_conteo_duras'] ?? ''),
                        'primer_conteo_frescas' => (string) ($repeticionRow['primer_conteo_frescas'] ?? ''),
                        'primer_conteo_muertas' => (string) ($repeticionRow['primer_conteo_muertas'] ?? ''),
                        'segundo_conteo_normales' => (string) ($repeticionRow['segundo_conteo_normales'] ?? ''),
                        'segundo_conteo_anormales' => (string) ($repeticionRow['segundo_conteo_anormales'] ?? ''),
                        'segundo_conteo_duras' => (string) ($repeticionRow['segundo_conteo_duras'] ?? ''),
                        'segundo_conteo_frescas' => (string) ($repeticionRow['segundo_conteo_frescas'] ?? ''),
                        'segundo_conteo_muertas' => (string) ($repeticionRow['segundo_conteo_muertas'] ?? ''),
                    ];
                }
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No se pudo cargar el boletín para edición.';
        }
    }
}

$filtroCliente = trim((string) ($_GET['filtro_cliente'] ?? ''));
$filtroClienteId = filter_var($filtroCliente, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$filtroEspecie = trim((string) ($_GET['filtro_especie'] ?? ''));
$filtroEspecieId = filter_var($filtroEspecie, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$filtroEstado = trim((string) ($_GET['filtro_estado'] ?? ''));
if (!in_array($filtroEstado, ['primer_conteo', 'finalizado'], true)) {
    $filtroEstado = '';
}
$conditions = ['b.id_empresa = :id_empresa'];
$params = [':id_empresa' => current_empresa_id()];
if ($filtroClienteId !== false) {
    $conditions[] = 'b.id_cliente = :id_cliente';
    $params[':id_cliente'] = $filtroClienteId;
}
if ($filtroEspecieId !== false) {
    $conditions[] = 'b.id_especie = :id_especie_filtro';
    $params[':id_especie_filtro'] = $filtroEspecieId;
}
if ($filtroEstado !== '') {
    $conditions[] = 'b.estado = :estado_filtro';
    $params[':estado_filtro'] = $filtroEstado;
}

try {
    $statement = db()->prepare(
        'SELECT b.id_boletin_germinacion, b.analisis_nro, b.muestra_nro, b.fecha_siembra, b.primer_promedio, b.resultado_plantulas_normales, b.estado, cl.nombre_cliente, e.nombre_especie
         FROM boletines_germinacion b
         INNER JOIN clientes cl ON cl.id_cliente = b.id_cliente AND cl.id_empresa = b.id_empresa
         LEFT JOIN especies e ON e.id_especie = b.id_especie AND e.id_empresa = b.id_empresa
         WHERE ' . implode(' AND ', $conditions) . ' AND b.tipo_boletin = :tipo_boletin
         ORDER BY b.id_boletin_germinacion DESC'
    );
    $params[':tipo_boletin'] = $boletinGerminacionTipo;
    $statement->execute($params);
    $boletinesGerminacion = $statement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el listado de boletines de ' . $boletinGerminacionTitulo . '.';
}
?>
<section class="section-panel" id="germinacion-form">
    <?php if ($boletinGerminacionEditId !== null): ?>
        <h2>Editar boletín de <?php echo htmlspecialchars($boletinGerminacionTitulo, ENT_QUOTES, 'UTF-8'); ?> <span class="badge bg-secondary fs-6">#<?php echo $boletinGerminacionEditId; ?></span></h2>
        <?php if ($boletinGerminacionTipo === 'cold_test'): ?>
            <p>Actualiza el 1er conteo. El 2do conteo es opcional y permite cerrar el resultado final.</p>
        <?php else: ?>
            <p>Actualiza el 1er conteo o completa el 2do conteo para cerrar el resultado final.</p>
        <?php endif; ?>
    <?php else: ?>
        <h2>Boletín de <?php echo htmlspecialchars($boletinGerminacionTitulo, ENT_QUOTES, 'UTF-8'); ?></h2>
        <?php if ($boletinGerminacionTipo === 'cold_test'): ?>
            <p>Completa al menos el 1er conteo en las cuatro repeticiones para guardar el boletín. El 2do conteo es opcional.</p>
        <?php else: ?>
            <p>Carga el 1er conteo (indicador temprano) y, cuando esté disponible, el 2do conteo final por repetición.</p>
        <?php endif; ?>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>
    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="/index.php?page=<?php echo htmlspecialchars($boletinGerminacionPagina, ENT_QUOTES, 'UTF-8'); ?>" id="boletin-form" class="row g-3 mt-1">
        <?php if (isset($_GET['ingreso_id']) && filter_var($_GET['ingreso_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false): ?>
            <input type="hidden" name="ingreso_id" value="<?php echo (int) $_GET['ingreso_id']; ?>">
        <?php endif; ?>
        <?php if ($boletinGerminacionEditId !== null): ?>
            <input type="hidden" name="update_boletin_germinacion" value="1">
            <input type="hidden" name="boletin_germinacion_edit_id" value="<?php echo $boletinGerminacionEditId; ?>">
        <?php else: ?>
            <input type="hidden" name="save_boletin_germinacion" value="1">
        <?php endif; ?>

        <div class="col-md-4">
            <label for="germ_cliente" class="form-label">Cliente *</label>
            <select class="form-select" id="germ_cliente" name="id_cliente" required>
                <option value="">Selecciona un cliente</option>
                <?php foreach ($clientesOptions as $cliente): ?>
                    <option value="<?php echo (int) $cliente['id_cliente']; ?>" <?php echo $boletinGerminacionForm['id_cliente'] === (string) $cliente['id_cliente'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $cliente['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label for="germ_analisis" class="form-label">Análisis Nº</label>
            <input class="form-control" id="germ_analisis" name="analisis_nro" maxlength="100" value="<?php echo htmlspecialchars($boletinGerminacionForm['analisis_nro'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-4">
            <label for="germ_muestra" class="form-label">Muestra Nº *</label>
            <input class="form-control" id="germ_muestra" name="muestra_nro" maxlength="100" value="<?php echo htmlspecialchars($boletinGerminacionForm['muestra_nro'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="col-md-6">
            <label for="germ_especie" class="form-label">Especie remitente *</label>
            <select class="form-select" id="germ_especie" name="id_especie" required>
                <option value="">Selecciona una especie</option>
                <?php foreach ($especiesOptions as $especie): ?>
                    <option value="<?php echo (int) $especie['id_especie']; ?>" <?php echo $boletinGerminacionForm['id_especie'] === (string) $especie['id_especie'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $especie['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label for="germ_analista" class="form-label">Analista</label>
            <input class="form-control" id="germ_analista" name="analista" maxlength="150" value="<?php echo htmlspecialchars($boletinGerminacionForm['analista'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-md-4">
            <label for="germ_fecha_siembra" class="form-label">Fecha de siembra</label>
            <input type="date" class="form-control" id="germ_fecha_siembra" name="fecha_siembra" value="<?php echo htmlspecialchars($boletinGerminacionForm['fecha_siembra'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-4">
            <label for="germ_fecha_primer_conteo" class="form-label">Fecha 1er conteo</label>
            <input type="date" class="form-control" id="germ_fecha_primer_conteo" name="fecha_primer_conteo" value="<?php echo htmlspecialchars($boletinGerminacionForm['fecha_primer_conteo'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-4">
            <label for="germ_fecha_segundo_conteo" class="form-label">Fecha 2do conteo</label>
            <input type="date" class="form-control" id="germ_fecha_segundo_conteo" name="fecha_segundo_conteo" value="<?php echo htmlspecialchars($boletinGerminacionForm['fecha_segundo_conteo'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-12">
            <h3 class="h6 mb-0">Pre-tratamiento</h3>
        </div>
        <?php $pretratamientoDeshabilitado = $boletinGerminacionForm['pretratamiento_aplica'] === 'no'; ?>
        <div class="col-md-3">
            <label class="form-label d-block">¿Aplica pre-tratamiento? *</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" id="germ_pretratamiento_si" name="pretratamiento_aplica" value="si" <?php echo $boletinGerminacionForm['pretratamiento_aplica'] === 'si' ? 'checked' : ''; ?> required>
                <label class="form-check-label" for="germ_pretratamiento_si">SI</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" id="germ_pretratamiento_no" name="pretratamiento_aplica" value="no" <?php echo $boletinGerminacionForm['pretratamiento_aplica'] === 'no' ? 'checked' : ''; ?> required>
                <label class="form-check-label" for="germ_pretratamiento_no">NO</label>
            </div>
        </div>
        <div class="col-md-3">
            <label for="germ_pretratamiento_desde" class="form-label">Desde</label>
            <input type="date" class="form-control pretratamiento-detalle" id="germ_pretratamiento_desde" name="pretratamiento_desde" value="<?php echo htmlspecialchars($boletinGerminacionForm['pretratamiento_desde'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $pretratamientoDeshabilitado ? 'disabled' : ''; ?>>
        </div>
        <div class="col-md-3">
            <label for="germ_pretratamiento_hasta" class="form-label">Hasta</label>
            <input type="date" class="form-control pretratamiento-detalle" id="germ_pretratamiento_hasta" name="pretratamiento_hasta" value="<?php echo htmlspecialchars($boletinGerminacionForm['pretratamiento_hasta'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $pretratamientoDeshabilitado ? 'disabled' : ''; ?>>
        </div>
        <div class="col-md-3">
            <label for="germ_pretratamiento_total_dias" class="form-label">Total días</label>
            <input type="number" class="form-control pretratamiento-detalle" id="germ_pretratamiento_total_dias" name="pretratamiento_total_dias" min="0" step="1" value="<?php echo htmlspecialchars($boletinGerminacionForm['pretratamiento_total_dias'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $pretratamientoDeshabilitado ? 'disabled' : ''; ?>>
        </div>
        <div class="col-12 d-flex flex-wrap gap-4">
            <div class="form-check">
                <input class="form-check-input pretratamiento-detalle" type="checkbox" id="germ_pretratamiento_frio" name="pretratamiento_frio" value="1" <?php echo $boletinGerminacionForm['pretratamiento_frio'] ? 'checked' : ''; ?> <?php echo $pretratamientoDeshabilitado ? 'disabled' : ''; ?>>
                <label class="form-check-label" for="germ_pretratamiento_frio">Frío</label>
            </div>
            <div class="form-check">
                <input class="form-check-input pretratamiento-detalle" type="checkbox" id="germ_pretratamiento_nitrato_k" name="pretratamiento_nitrato_k" value="1" <?php echo $boletinGerminacionForm['pretratamiento_nitrato_k'] ? 'checked' : ''; ?> <?php echo $pretratamientoDeshabilitado ? 'disabled' : ''; ?>>
                <label class="form-check-label" for="germ_pretratamiento_nitrato_k">Nitrato de K</label>
            </div>
            <div class="form-check">
                <input class="form-check-input pretratamiento-detalle" type="checkbox" id="germ_pretratamiento_presecado" name="pretratamiento_presecado" value="1" <?php echo $boletinGerminacionForm['pretratamiento_presecado'] ? 'checked' : ''; ?> <?php echo $pretratamientoDeshabilitado ? 'disabled' : ''; ?>>
                <label class="form-check-label" for="germ_pretratamiento_presecado">Pre-secado</label>
            </div>
            <div class="form-check">
                <input class="form-check-input pretratamiento-detalle" type="checkbox" id="germ_pretratamiento_prelavado" name="pretratamiento_prelavado" value="1" <?php echo $boletinGerminacionForm['pretratamiento_prelavado'] ? 'checked' : ''; ?> <?php echo $pretratamientoDeshabilitado ? 'disabled' : ''; ?>>
                <label class="form-check-label" for="germ_pretratamiento_prelavado">Pre-lavado</label>
            </div>
        </div>

        <div class="col-12">
            <h3 class="h6 mb-0">Sustrato utilizado</h3>
        </div>
        <div class="col-12 d-flex flex-wrap align-items-center gap-4">
            <div class="form-check">
                <input class="form-check-input" type="radio" id="germ_sustrato_arena" name="sustrato_tipo" value="arena" <?php echo $boletinGerminacionForm['sustrato_tipo'] === 'arena' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="germ_sustrato_arena">Arena</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" id="germ_sustrato_sobre_papel" name="sustrato_tipo" value="sobre_papel" <?php echo $boletinGerminacionForm['sustrato_tipo'] === 'sobre_papel' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="germ_sustrato_sobre_papel">Sobre papel</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" id="germ_sustrato_entre_papel" name="sustrato_tipo" value="entre_papel" <?php echo $boletinGerminacionForm['sustrato_tipo'] === 'entre_papel' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="germ_sustrato_entre_papel">Entre papel</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" id="germ_sustrato_otro_check" name="sustrato_tipo" value="otro" <?php echo $boletinGerminacionForm['sustrato_tipo'] === 'otro' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="germ_sustrato_otro_check">Otro:</label>
            </div>
            <input type="text" class="form-control" style="max-width: 220px" id="germ_sustrato_otro" name="sustrato_otro" maxlength="255" value="<?php echo htmlspecialchars($boletinGerminacionForm['sustrato_otro'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-12">
            <h3 class="h6 mb-0">Temp. de la Cámara</h3>
        </div>
        <div class="col-12 d-flex flex-wrap align-items-center gap-4">
            <div class="form-check">
                <input class="form-check-input" type="radio" id="germ_temperatura_20" name="temperatura_camara_tipo" value="20" <?php echo $boletinGerminacionForm['temperatura_camara_tipo'] === '20' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="germ_temperatura_20">20 ºC</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" id="germ_temperatura_25" name="temperatura_camara_tipo" value="25" <?php echo $boletinGerminacionForm['temperatura_camara_tipo'] === '25' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="germ_temperatura_25">25 ºC</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" id="germ_temperatura_20_30" name="temperatura_camara_tipo" value="20_30" <?php echo $boletinGerminacionForm['temperatura_camara_tipo'] === '20_30' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="germ_temperatura_20_30">20-30 ºC</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" id="germ_temperatura_otra_check" name="temperatura_camara_tipo" value="otra" <?php echo $boletinGerminacionForm['temperatura_camara_tipo'] === 'otra' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="germ_temperatura_otra_check">Otra:</label>
            </div>
            <input type="text" class="form-control" style="max-width: 220px" id="germ_temperatura_otra" name="temperatura_camara_otra" maxlength="100" value="<?php echo htmlspecialchars($boletinGerminacionForm['temperatura_camara_otra'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-12">
            <h3 class="h6 mb-0">Repeticiones</h3>
            <p class="small text-muted mb-0">En cada conteo, si completas alguna categoría, las casillas vacías se toman como 0. El 1er conteo es el indicador temprano (el promedio de "Normales" alimenta el 1er Promedio): no necesita sumar todas las semillas sembradas, pero no puede superar esa cantidad. En el 2do conteo carga solo las semillas nuevas clasificadas. La suma del 1er y 2do conteo debe coincidir con las semillas sembradas; el resultado final suma ambos conteos por categoría. Un conteo totalmente vacío queda pendiente; si no hay semillas nuevas en el 2do conteo, ingresa un 0 en alguna casilla para indicar que se realizó.</p>
        </div>
        <div class="col-12 table-responsive">
            <table class="table table-dark table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 56px" class="text-center">N° rep.</th>
                        <th style="width: 140px">Semillas sembradas</th>
                        <th style="width: 110px">Conteo</th>
                        <th style="width: 110px">Normales</th>
                        <th style="width: 110px">Anormales</th>
                        <th style="width: 110px">Duras</th>
                        <th style="width: 110px">Frescas</th>
                        <th style="width: 110px">Muertas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($numero = 1; $numero <= 4; $numero++): ?>
                        <tr>
                            <th rowspan="2" class="text-center align-middle"><?php echo $numero; ?></th>
                            <td rowspan="2"><input type="number" class="form-control" min="1" step="1" name="repeticiones[<?php echo $numero; ?>][semillas_sembradas]" value="<?php echo htmlspecialchars($boletinGerminacionForm['repeticiones'][$numero]['semillas_sembradas'], ENT_QUOTES, 'UTF-8'); ?>" required></td>
                            <th>1er conteo</th>
                            <td><input type="number" class="form-control" min="0" step="1" name="repeticiones[<?php echo $numero; ?>][primer_conteo_normales]" value="<?php echo htmlspecialchars($boletinGerminacionForm['repeticiones'][$numero]['primer_conteo_normales'], ENT_QUOTES, 'UTF-8'); ?>"></td>
                            <td><input type="number" class="form-control" min="0" step="1" name="repeticiones[<?php echo $numero; ?>][primer_conteo_anormales]" value="<?php echo htmlspecialchars($boletinGerminacionForm['repeticiones'][$numero]['primer_conteo_anormales'], ENT_QUOTES, 'UTF-8'); ?>"></td>
                            <td><input type="number" class="form-control" min="0" step="1" name="repeticiones[<?php echo $numero; ?>][primer_conteo_duras]" value="<?php echo htmlspecialchars($boletinGerminacionForm['repeticiones'][$numero]['primer_conteo_duras'], ENT_QUOTES, 'UTF-8'); ?>"></td>
                            <td><input type="number" class="form-control" min="0" step="1" name="repeticiones[<?php echo $numero; ?>][primer_conteo_frescas]" value="<?php echo htmlspecialchars($boletinGerminacionForm['repeticiones'][$numero]['primer_conteo_frescas'], ENT_QUOTES, 'UTF-8'); ?>"></td>
                            <td><input type="number" class="form-control" min="0" step="1" name="repeticiones[<?php echo $numero; ?>][primer_conteo_muertas]" value="<?php echo htmlspecialchars($boletinGerminacionForm['repeticiones'][$numero]['primer_conteo_muertas'], ENT_QUOTES, 'UTF-8'); ?>"></td>
                        </tr>
                        <tr>
                            <th>2do conteo</th>
                            <td><input type="number" class="form-control" min="0" step="1" name="repeticiones[<?php echo $numero; ?>][segundo_conteo_normales]" value="<?php echo htmlspecialchars($boletinGerminacionForm['repeticiones'][$numero]['segundo_conteo_normales'], ENT_QUOTES, 'UTF-8'); ?>"></td>
                            <td><input type="number" class="form-control" min="0" step="1" name="repeticiones[<?php echo $numero; ?>][segundo_conteo_anormales]" value="<?php echo htmlspecialchars($boletinGerminacionForm['repeticiones'][$numero]['segundo_conteo_anormales'], ENT_QUOTES, 'UTF-8'); ?>"></td>
                            <td><input type="number" class="form-control" min="0" step="1" name="repeticiones[<?php echo $numero; ?>][segundo_conteo_duras]" value="<?php echo htmlspecialchars($boletinGerminacionForm['repeticiones'][$numero]['segundo_conteo_duras'], ENT_QUOTES, 'UTF-8'); ?>"></td>
                            <td><input type="number" class="form-control" min="0" step="1" name="repeticiones[<?php echo $numero; ?>][segundo_conteo_frescas]" value="<?php echo htmlspecialchars($boletinGerminacionForm['repeticiones'][$numero]['segundo_conteo_frescas'], ENT_QUOTES, 'UTF-8'); ?>"></td>
                            <td><input type="number" class="form-control" min="0" step="1" name="repeticiones[<?php echo $numero; ?>][segundo_conteo_muertas]" value="<?php echo htmlspecialchars($boletinGerminacionForm['repeticiones'][$numero]['segundo_conteo_muertas'], ENT_QUOTES, 'UTF-8'); ?>"></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <div class="col-12">
            <label for="germ_observaciones" class="form-label">Observaciones</label>
            <textarea class="form-control" id="germ_observaciones" name="observaciones" rows="3"><?php echo htmlspecialchars($boletinGerminacionForm['observaciones'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-brand"><?php echo $boletinGerminacionEditId !== null ? 'Guardar cambios' : 'Guardar boletín'; ?></button>
            <?php if ($boletinGerminacionEditId !== null): ?>
                <a href="/index.php?page=<?php echo htmlspecialchars($boletinGerminacionPagina, ENT_QUOTES, 'UTF-8'); ?>#germinacion-listado" class="btn btn-outline-brand">Cancelar edición</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="section-panel" id="germinacion-listado">
    <h2>Boletines de <?php echo htmlspecialchars($boletinGerminacionTitulo, ENT_QUOTES, 'UTF-8'); ?> cargados</h2>
    <form method="get" class="row g-3 mb-3" action="/index.php#germinacion-listado">
        <input type="hidden" name="page" value="<?php echo htmlspecialchars($boletinGerminacionPagina, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="col-md-3">
            <label for="germ-filtro-cliente" class="form-label">Filtrar por cliente</label>
            <select class="form-select" id="germ-filtro-cliente" name="filtro_cliente">
                <option value="">Todos</option>
                <?php foreach ($clientesOptions as $cliente): ?>
                    <option value="<?php echo (int) $cliente['id_cliente']; ?>" <?php echo $filtroCliente === (string) $cliente['id_cliente'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $cliente['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="germ-filtro-especie" class="form-label">Filtrar por especie</label>
            <select class="form-select" id="germ-filtro-especie" name="filtro_especie">
                <option value="">Todas</option>
                <?php foreach ($especiesOptions as $especie): ?>
                    <option value="<?php echo (int) $especie['id_especie']; ?>" <?php echo $filtroEspecie === (string) $especie['id_especie'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $especie['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="germ-filtro-estado" class="form-label">Filtrar por estado</label>
            <select class="form-select" id="germ-filtro-estado" name="filtro_estado">
                <option value="">Todos</option>
                <option value="primer_conteo" <?php echo $filtroEstado === 'primer_conteo' ? 'selected' : ''; ?>>En proceso</option>
                <option value="finalizado" <?php echo $filtroEstado === 'finalizado' ? 'selected' : ''; ?>>Finalizado</option>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-brand w-100"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="/index.php?page=<?php echo htmlspecialchars($boletinGerminacionPagina, ENT_QUOTES, 'UTF-8'); ?>#germinacion-listado" class="btn btn-outline-brand" title="Limpiar filtro"><i class="bi bi-x-circle"></i></a>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Análisis</th>
                    <th>Cliente</th>
                    <th>Muestra</th>
                    <th>Especie</th>
                    <th>Fecha siembra</th>
                    <th>1er Promedio</th>
                    <th>Resultado final</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($boletinesGerminacion === []): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">No hay boletines cargados aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($boletinesGerminacion as $boletin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) ($boletin['analisis_nro'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $boletin['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $boletin['muestra_nro'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($boletin['nombre_especie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(format_date_short($boletin['fecha_siembra'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo $boletin['primer_promedio'] !== null ? htmlspecialchars(number_format((float) $boletin['primer_promedio'], 2, ',', '.') . ' %', ENT_QUOTES, 'UTF-8') : '----------------'; ?></td>
                            <td><?php echo $boletin['resultado_plantulas_normales'] !== null ? htmlspecialchars(number_format((float) $boletin['resultado_plantulas_normales'], 2, ',', '.') . ' %', ENT_QUOTES, 'UTF-8') : '----------------'; ?></td>
                            <td><?php echo $boletin['estado'] === 'finalizado' ? '<span class="badge bg-success">Finalizado</span>' : '<span class="badge bg-warning text-dark">En proceso</span>'; ?></td>
                            <td class="d-flex gap-2">
                                <a class="btn btn-sm btn-outline-light" href="/index.php?page=<?php echo urlencode($boletinGerminacionPagina); ?>&amp;editar=<?php echo urlencode((string) $boletin['id_boletin_germinacion']); ?>#germinacion-form" aria-label="Editar boletín <?php echo htmlspecialchars((string) $boletin['muestra_nro'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-pencil"></i> Editar</a>
                                <a class="btn btn-sm btn-outline-light" href="/index.php?boletin_germinacion_pdf=<?php echo urlencode((string) $boletin['id_boletin_germinacion']); ?>&amp;tipo_boletin=<?php echo urlencode($boletinGerminacionTipo); ?>" target="_blank" rel="noopener noreferrer" aria-label="Abrir PDF del boletín <?php echo htmlspecialchars((string) $boletin['muestra_nro'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const radios = document.querySelectorAll('input[name="pretratamiento_aplica"]');
        const detalles = document.querySelectorAll('.pretratamiento-detalle');
        if (!radios.length) return;

        function actualizarEstado() {
            const aplicaNo = document.querySelector('input[name="pretratamiento_aplica"]:checked')?.value === 'no';
            detalles.forEach(function(campo) {
                campo.disabled = aplicaNo;
                if (aplicaNo) {
                    campo.type === 'checkbox' ? (campo.checked = false) : (campo.value = '');
                }
            });
        }

        radios.forEach(function(radio) {
            radio.addEventListener('change', actualizarEstado);
        });
    });
</script>