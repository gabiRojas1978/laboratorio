<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/boletin_edicion.php';
[$boletinEditId, $boletinEdicionInvalida] = boletin_edicion_cargar('tetrazolio_soja', $boletinTetrazolioSojaForm, $errors);

$clientesOptions = [];
$especiesOptions = [];
$boletinesTetrazolioSoja = [];

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
        static fn(array $especie): bool => boletin_tetrazolio_soja_especie_valida(
            (string) $especie['nombre_especie'],
            $especie['cientifico_especie'] ?? null
        )
    ));
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el selector de especies de soja.';
}

try {
    $statement = db()->prepare(
        'SELECT b.id_boletin_tetrazolio_soja, b.muestra_nro, b.nro_interno, b.fecha_analisis,
                b.analista, b.rep_mayor_menor, b.tolerancia_tabla, cl.nombre_cliente
         FROM boletines_tetrazolio_soja b
         INNER JOIN clientes cl ON cl.id_cliente = b.id_cliente AND cl.id_empresa = b.id_empresa
         WHERE b.id_empresa = :id_empresa
         ORDER BY b.id_boletin_tetrazolio_soja DESC'
    );
    $statement->execute([':id_empresa' => current_empresa_id()]);
    $boletinesTetrazolioSoja = $statement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el listado de boletines de Tetrazolio (Soja).';
}

$escapeSoja = static fn($valor): string => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
$nivelesViabilidadSoja = boletin_tetrazolio_soja_niveles_viabilidad();
$categoriasVigorSoja = boletin_tetrazolio_soja_categorias_vigor();
$tiposDanosSoja = boletin_tetrazolio_soja_tipos_danos();
?>
<section class="section-panel">
    <h2>Prueba topográfica por Tetrazolio en Soja</h2>
    <?php if ($boletinEditId !== null): ?>
        <p>Editando boletín #<?php echo $boletinEditId; ?>. <a href="/index.php?page=boletin_tetrazolio_soja">Cancelar edición</a></p>
    <?php endif; ?>
    <p>Registro interno y resumen de cuatro repeticiones de 200 semillas. Los porcentajes se calculan sobre 200 semillas por repetición; la tolerancia de tabla se completa manualmente.</p>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo $escapeSoja($error); ?></div>
    <?php endforeach; ?>
    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?php echo $escapeSoja($successMessage); ?></div>
    <?php endif; ?>

    <?php if ($especiesOptions === []): ?>
        <div class="alert alert-warning">Para usar este boletín, registra una especie Soja con nombre científico Glycine max.</div>
    <?php else: ?>
        <form method="post" class="row g-3 mt-1" id="boletin-form">
            <input type="hidden" name="save_boletin_tetrazolio_soja" value="1">
            <?php if (isset($_GET['ingreso_id']) && filter_var($_GET['ingreso_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false): ?>
                <input type="hidden" name="ingreso_id" value="<?php echo (int) $_GET['ingreso_id']; ?>">
            <?php endif; ?>
            <?php if ($boletinEditId !== null): ?>
                <input type="hidden" name="id_boletin" value="<?php echo $boletinEditId; ?>">
            <?php endif; ?>

            <div class="col-12"><h3 class="h5 mb-0">Planilla de registro</h3></div>
            <div class="col-md-3">
                <label for="tzs_cliente" class="form-label">Cliente / solicitante *</label>
                <select class="form-select" id="tzs_cliente" name="id_cliente" required>
                    <option value="">Selecciona un cliente</option>
                    <?php foreach ($clientesOptions as $cliente): ?>
                        <option value="<?php echo (int) $cliente['id_cliente']; ?>" <?php echo $boletinTetrazolioSojaForm['id_cliente'] === (string) $cliente['id_cliente'] ? 'selected' : ''; ?>><?php echo $escapeSoja($cliente['nombre_cliente']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php
            $camposCabecera = [
                'muestra_nro' => ['Muestra N° *', 100, 'required'],
                'nro_interno' => ['N° interno', 100, ''],
                'nro_certificado' => ['N° certificado', 100, ''],
                'nombre_solicitante' => ['Nombre del solicitante', 255, ''],
                'precinto_muestra' => ['Precinto de la muestra', 100, ''],
                'procedencia' => ['Procedencia', 255, ''],
            ];
            foreach ($camposCabecera as $campo => [$etiqueta, $maximo, $requerido]):
            ?>
                <div class="col-md-3">
                    <label for="tzs_<?php echo $campo; ?>" class="form-label"><?php echo $etiqueta; ?></label>
                    <input class="form-control" id="tzs_<?php echo $campo; ?>" name="<?php echo $campo; ?>" maxlength="<?php echo $maximo; ?>" value="<?php echo $escapeSoja($boletinTetrazolioSojaForm[$campo]); ?>" <?php echo $requerido; ?>>
                </div>
            <?php endforeach; ?>

            <div class="col-md-3">
                <label for="tzs_especie" class="form-label">Especie *</label>
                <select class="form-select" id="tzs_especie" name="id_especie" required>
                    <option value="">Selecciona soja</option>
                    <?php foreach ($especiesOptions as $especie): ?>
                        <option value="<?php echo (int) $especie['id_especie']; ?>" <?php echo $boletinTetrazolioSojaForm['id_especie'] === (string) $especie['id_especie'] ? 'selected' : ''; ?>><?php echo $escapeSoja($especie['nombre_especie']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="tzs_cultivar" class="form-label">Cultivar</label>
                <input class="form-control" id="tzs_cultivar" name="cultivar" maxlength="150" value="<?php echo $escapeSoja($boletinTetrazolioSojaForm['cultivar']); ?>">
            </div>
            <div class="col-md-3">
                <label for="tzs_fecha_recepcion" class="form-label">Fecha de recepción de la muestra</label>
                <input type="date" class="form-control" id="tzs_fecha_recepcion" name="fecha_recepcion" value="<?php echo $escapeSoja($boletinTetrazolioSojaForm['fecha_recepcion']); ?>">
            </div>
            <div class="col-md-3">
                <label for="tzs_fecha_analisis" class="form-label">Fecha de análisis</label>
                <input type="date" class="form-control" id="tzs_fecha_analisis" name="fecha_analisis" value="<?php echo $escapeSoja($boletinTetrazolioSojaForm['fecha_analisis']); ?>">
            </div>
            <div class="col-md-3">
                <label for="tzs_analista" class="form-label">Analista</label>
                <input class="form-control" id="tzs_analista" name="analista" maxlength="150" value="<?php echo $escapeSoja($boletinTetrazolioSojaForm['analista']); ?>">
            </div>
            <div class="col-md-3">
                <label for="tzs_semillas" class="form-label">Semillas evaluadas por repetición</label>
                <input type="number" class="form-control" id="tzs_semillas" value="200" readonly>
            </div>
            <div class="col-md-3">
                <label for="tzs_concentracion" class="form-label">Concentración de Tetrazolio</label>
                <input class="form-control" id="tzs_concentracion" name="concentracion_tz" maxlength="100" value="<?php echo $escapeSoja($boletinTetrazolioSojaForm['concentracion_tz']); ?>">
            </div>
            <div class="col-md-3">
                <label for="tzs_tiempo" class="form-label">Tiempo de tinción</label>
                <input class="form-control" id="tzs_tiempo" name="tiempo_tincion" maxlength="100" value="<?php echo $escapeSoja($boletinTetrazolioSojaForm['tiempo_tincion']); ?>">
            </div>
            <div class="col-md-3">
                <label for="tzs_temperatura" class="form-label">Temperatura de tinción</label>
                <input class="form-control" id="tzs_temperatura" name="temperatura_tincion" maxlength="100" value="<?php echo $escapeSoja($boletinTetrazolioSojaForm['temperatura_tincion']); ?>">
            </div>
            <div class="col-12">
                <label for="tzs_observaciones" class="form-label">Observaciones</label>
                <textarea class="form-control" id="tzs_observaciones" name="observaciones" rows="3"><?php echo $escapeSoja($boletinTetrazolioSojaForm['observaciones']); ?></textarea>
            </div>

            <div class="col-12"><h3 class="h5 mb-0">Planilla interna de trabajo</h3></div>
            <?php foreach (range(1, 4) as $numero): ?>
                <div class="col-12">
                    <h4 class="h6">Repetición <?php echo $numero; ?></h4>
                    <div class="table-responsive mb-3">
                        <table class="table table-dark table-bordered align-middle mb-0">
                            <thead><tr><th colspan="2">Viabilidad — cantidad de semillas</th></tr></thead>
                            <tbody>
                                <?php foreach ($nivelesViabilidadSoja as $campo => $etiqueta): ?>
                                    <tr>
                                        <th><?php echo $escapeSoja($etiqueta); ?></th>
                                        <td><input type="number" class="form-control" min="0" max="200" step="1" name="repeticiones[<?php echo $numero; ?>][<?php echo $campo; ?>]" value="<?php echo $escapeSoja($boletinTetrazolioSojaForm['repeticiones'][$numero][$campo]); ?>" required></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="table-responsive mb-3">
                        <table class="table table-dark table-bordered align-middle mb-0">
                            <thead><tr><th colspan="2">Vigor — cantidad de semillas</th></tr></thead>
                            <tbody>
                                <?php foreach ($categoriasVigorSoja as $campo => $etiqueta): ?>
                                    <tr>
                                        <th><?php echo $escapeSoja($etiqueta); ?></th>
                                        <td><input type="number" class="form-control" min="0" max="200" step="1" name="repeticiones[<?php echo $numero; ?>][<?php echo $campo; ?>]" value="<?php echo $escapeSoja($boletinTetrazolioSojaForm['repeticiones'][$numero][$campo]); ?>" required></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="table-responsive mb-3">
                        <table class="table table-dark table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nivel de viabilidad</th>
                                    <?php foreach ($tiposDanosSoja as $etiqueta): ?>
                                        <th><?php echo $escapeSoja($etiqueta); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($nivelesViabilidadSoja as $nivel => $etiquetaNivel): ?>
                                    <?php $numeroNivel = array_search($nivel, array_keys($nivelesViabilidadSoja), true) + 1; ?>
                                    <tr>
                                        <th><?php echo $escapeSoja($etiquetaNivel); ?></th>
                                        <?php foreach ($tiposDanosSoja as $tipo => $etiqueta): ?>
                                            <?php $campoDano = $tipo . '_nivel_' . $numeroNivel; ?>
                                            <td><label class="visually-hidden" for="tzs_rep_<?php echo $numero; ?>_<?php echo $campoDano; ?>"><?php echo $escapeSoja($etiqueta . ', ' . $etiquetaNivel); ?></label><input id="tzs_rep_<?php echo $numero; ?>_<?php echo $campoDano; ?>" type="number" class="form-control" min="0" max="200" step="1" name="repeticiones[<?php echo $numero; ?>][<?php echo $campoDano; ?>]" value="<?php echo $escapeSoja($boletinTetrazolioSojaForm['repeticiones'][$numero][$campoDano]); ?>" required></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="small text-muted mb-3">Los cinco niveles de viabilidad deben sumar exactamente 200 semillas en esta repetición.</div>
                </div>
            <?php endforeach; ?>

            <div class="col-md-6">
                <label for="tzs_tolerancia" class="form-label">Tolerancia de tabla</label>
                <input class="form-control" id="tzs_tolerancia" name="tolerancia_tabla" maxlength="100" value="<?php echo $escapeSoja($boletinTetrazolioSojaForm['tolerancia_tabla']); ?>">
            </div>
            <div class="col-12"><button type="submit" class="btn btn-brand" <?php echo $boletinEdicionInvalida ? 'disabled' : ''; ?>><?php echo $boletinEditId !== null ? 'Actualizar boletín' : 'Guardar boletín'; ?></button></div>
        </form>
    <?php endif; ?>
</section>

<section class="section-panel">
    <h2>Boletines cargados</h2>
    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle mb-0">
            <thead>
                <tr><th>Muestra</th><th>N° interno</th><th>Cliente</th><th>Fecha de análisis</th><th>Analista</th><th>Rep. mayor - menor</th><th>Tolerancia</th><th>PDF</th></tr>
            </thead>
            <tbody>
                <?php if ($boletinesTetrazolioSoja === []): ?>
                    <tr><td colspan="8" class="text-center text-muted">No hay boletines cargados aún.</td></tr>
                <?php else: ?>
                    <?php foreach ($boletinesTetrazolioSoja as $boletin): ?>
                        <tr>
                            <td><?php echo $escapeSoja($boletin['muestra_nro']); ?></td>
                            <td><?php echo $escapeSoja($boletin['nro_interno']); ?></td>
                            <td><?php echo $escapeSoja($boletin['nombre_cliente']); ?></td>
                            <td><?php echo htmlspecialchars(format_date_short($boletin['fecha_analisis'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo $escapeSoja($boletin['analista']); ?></td>
                            <td><?php echo $escapeSoja($boletin['rep_mayor_menor']); ?> %</td>
                            <td><?php echo $escapeSoja($boletin['tolerancia_tabla']); ?></td>
                            <td><a class="btn btn-sm btn-outline-light" href="/index.php?page=boletin_tetrazolio_soja&amp;editar=<?php echo (int) $boletin['id_boletin_tetrazolio_soja']; ?>#boletin-form">Editar</a> <a class="btn btn-sm btn-outline-light" href="/index.php?boletin_tetrazolio_soja_pdf=<?php echo (int) $boletin['id_boletin_tetrazolio_soja']; ?>" target="_blank" rel="noopener noreferrer">PDF</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
