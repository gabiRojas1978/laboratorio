<?php

declare(strict_types=1);

$libroIngresoForm = array_replace([
    'presupuesto' => 'SI',
    'facturado' => 'NO',
    'fecha_entrada' => date('Y-m-d'),
    'fecha_inicio_analisis' => '',
    'remitente' => '',
    'id_cliente' => '',
    'origen' => '',
    'id_especie' => '',
    'id_cultivar' => '',
    'lote_observaciones' => '',
    'id_determinaciones' => [],
    'ubicacion_camara_archivo' => '',
], isset($libroIngresoForm) && is_array($libroIngresoForm) ? $libroIngresoForm : []);
if (!is_array($libroIngresoForm['id_determinaciones'])) {
    $libroIngresoForm['id_determinaciones'] = [];
}

$libroClientesOptions = [];
$libroEspeciesOptions = [];
$libroCultivaresOptions = [];
$libroDeterminacionesOptions = [];
$libroIngresos = [];
$libroIngresoEdit = null;
$libroIngresoEditId = $libroIngresoEditId ?? null;
$libroBoletinesVinculados = [];
$presupuestoVista = null;
$presupuestoEmails = [];
$presupuestoIds = [];
$presupuestoError = null;
$libroFiltro = [];
$libroFiltroErrores = [];
foreach (['filtro_cliente', 'filtro_fecha_desde', 'filtro_fecha_hasta'] as $campo) {
    $valor = $_GET[$campo] ?? '';
    if (!is_string($valor)) {
        $libroFiltroErrores[] = 'Los filtros del libro de ingreso no son válidos.';
        $valor = '';
    }
    $libroFiltro[$campo] = trim($valor);
}
if ($libroFiltro['filtro_cliente'] !== '' && filter_var($libroFiltro['filtro_cliente'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
    $libroFiltroErrores[] = 'Selecciona un cliente válido para filtrar.';
}
foreach (['filtro_fecha_desde', 'filtro_fecha_hasta'] as $campo) {
    $fecha = $libroFiltro[$campo];
    if ($fecha !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !checkdate((int) substr($fecha, 5, 2), (int) substr($fecha, 8, 2), (int) substr($fecha, 0, 4)))) {
        $libroFiltroErrores[] = 'Ingresa fechas válidas para filtrar los ingresos.';
        break;
    }
}
if ($libroFiltroErrores === [] && $libroFiltro['filtro_fecha_desde'] !== '' && $libroFiltro['filtro_fecha_hasta'] !== '' && $libroFiltro['filtro_fecha_desde'] > $libroFiltro['filtro_fecha_hasta']) {
    $libroFiltroErrores[] = 'La fecha desde no puede ser posterior a la fecha hasta.';
}
$libroFiltroQuery = array_filter($libroFiltro, static fn (string $valor): bool => $valor !== '');
if (isset($_GET['preparar_presupuesto'])) {
    try {
        $presupuestoIds = presupuesto_validar_ids($_GET['presupuesto_ids'] ?? []);
        $presupuestoVista = presupuesto_obtener($presupuestoIds);
        $presupuestoEmails = $presupuestoVista['emails_cliente'];
        if (!isset($_SESSION['presupuesto_token'])) {
            $_SESSION['presupuesto_token'] = bin2hex(random_bytes(32));
        }
    } catch (InvalidArgumentException $exception) {
        $presupuestoError = $exception->getMessage();
        $errors[] = $exception->getMessage();
    } catch (PDOException | RuntimeException $exception) {
        error_log('Error al cargar presupuesto: ' . $exception->getMessage());
        $errors[] = 'No se pudo cargar el presupuesto. Verifica los datos del ingreso y sus determinaciones.';
    }
}

try {
    $statement = db()->prepare('SELECT id_cliente, nombre_cliente FROM clientes WHERE id_empresa = :id_empresa ORDER BY nombre_cliente ASC');
    $statement->execute([':id_empresa' => current_empresa_id()]);
    $libroClientesOptions = $statement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el listado de clientes.';
}

try {
    $statement = db()->prepare('SELECT id_especie, nombre_especie FROM especies WHERE id_empresa = :id_empresa ORDER BY nombre_especie ASC');
    $statement->execute([':id_empresa' => current_empresa_id()]);
    $libroEspeciesOptions = $statement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el listado de especies.';
}

try {
    $statement = db()->prepare('SELECT id_cultivar, id_especie, nombre_cultivar FROM cultivares WHERE id_empresa = :id_empresa ORDER BY nombre_cultivar ASC');
    $statement->execute([':id_empresa' => current_empresa_id()]);
    $libroCultivaresOptions = $statement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el listado de cultivares.';
}

try {
    $statement = db()->prepare('SELECT id_determinacion_libro, nombre_determinacion_libro FROM determinaciones_libro_ingreso WHERE id_empresa = :id_empresa AND nombre_determinacion_libro <> :determinacion_duplicada ORDER BY nombre_determinacion_libro ASC');
    $statement->execute([
        ':id_empresa' => current_empresa_id(),
        ':determinacion_duplicada' => 'Vigor por tetrazolio',
    ]);
    $libroDeterminacionesOptions = $statement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el listado de determinaciones. Verifica que la tabla determinaciones_libro_ingreso exista.';
}

try {
    $libroCondiciones = ['li.id_empresa = :id_empresa'];
    $libroParams = [':id_empresa' => current_empresa_id()];
    if ($libroFiltroErrores === [] && $libroFiltro['filtro_cliente'] !== '') {
        $libroCondiciones[] = 'li.id_cliente = :filtro_cliente';
        $libroParams[':filtro_cliente'] = $libroFiltro['filtro_cliente'];
    }
    if ($libroFiltroErrores === [] && $libroFiltro['filtro_fecha_desde'] !== '') {
        $libroCondiciones[] = 'li.fecha_entrada >= :filtro_fecha_desde';
        $libroParams[':filtro_fecha_desde'] = $libroFiltro['filtro_fecha_desde'];
    }
    if ($libroFiltroErrores === [] && $libroFiltro['filtro_fecha_hasta'] !== '') {
        $libroCondiciones[] = 'li.fecha_entrada <= :filtro_fecha_hasta';
        $libroParams[':filtro_fecha_hasta'] = $libroFiltro['filtro_fecha_hasta'];
    }
    if ($libroFiltroErrores !== []) {
        $libroCondiciones[] = '1 = 0';
    }
    $statement = db()->prepare(
        'SELECT li.id_libro_ingreso, li.nro_inase, li.nro_muestra, li.presupuesto, li.facturado, li.fecha_entrada, li.fecha_inicio_analisis,
                li.remitente, li.origen, li.lote_observaciones, li.ubicacion_camara_archivo,
                cl.nombre_cliente, e.nombre_especie, e.cientifico_especie, cu.nombre_cultivar,
                GROUP_CONCAT(d.nombre_determinacion_libro ORDER BY d.nombre_determinacion_libro SEPARATOR \', \') AS determinaciones,
                GROUP_CONCAT(d.id_determinacion_libro) AS determinaciones_ids
         FROM libro_ingreso li
         INNER JOIN clientes cl ON cl.id_cliente = li.id_cliente
         LEFT JOIN especies e ON e.id_especie = li.id_especie
         LEFT JOIN cultivares cu ON cu.id_cultivar = li.id_cultivar
         LEFT JOIN libro_ingreso_determinaciones lid ON lid.id_libro_ingreso = li.id_libro_ingreso
         LEFT JOIN determinaciones_libro_ingreso d ON d.id_determinacion_libro = lid.id_determinacion_libro AND d.id_empresa = li.id_empresa
         WHERE ' . implode(' AND ', $libroCondiciones) . '
         GROUP BY li.id_libro_ingreso
         ORDER BY li.anio_libro_ingreso DESC, li.nro_inase DESC'
    );
    $statement->execute($libroParams);
    $libroIngresos = $statement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el libro de ingreso. Verifica que la tabla libro_ingreso exista.';
}

try {
    $vinculosStatement = db()->prepare(
        'SELECT lb.id_libro_ingreso, lb.tipo_boletin, lb.id_boletin
         FROM libro_ingreso_boletines lb
         INNER JOIN libro_ingreso li ON li.id_libro_ingreso = lb.id_libro_ingreso
         WHERE li.id_empresa = :id_empresa'
    );
    $vinculosStatement->execute([':id_empresa' => current_empresa_id()]);
    foreach ($vinculosStatement->fetchAll() as $vinculo) {
        $libroBoletinesVinculados[(int) $vinculo['id_libro_ingreso']][(string) $vinculo['tipo_boletin']] = (int) $vinculo['id_boletin'];
    }
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar la relación de boletines del Libro de Ingreso. Aplica la migración database/migrations/2026-11-02_libro_ingreso_boletines.sql.';
}

if ($libroIngresoEditId !== null || isset($_GET['editar'])) {
    $editId = $libroIngresoEditId ?? filter_var($_GET['editar'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($editId === false) {
        $errors[] = 'El ingreso a editar no es válido.';
    } else {
        try {
            $editStatement = db()->prepare(
                'SELECT *
                 FROM libro_ingreso
                 WHERE id_libro_ingreso = :id AND id_empresa = :id_empresa
                 LIMIT 1'
            );
            $editStatement->execute([':id' => $editId, ':id_empresa' => current_empresa_id()]);
            $libroIngresoEdit = $editStatement->fetch();
            if ($libroIngresoEdit === false) {
                $libroIngresoEdit = null;
                $errors[] = 'El ingreso a editar no existe.';
            } else {
                $libroIngresoEditId = (int) $libroIngresoEdit['id_libro_ingreso'];
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    foreach ($libroIngresoForm as $campo => $valor) {
                        if ($campo !== 'id_determinaciones') {
                            $libroIngresoForm[$campo] = (string) ($libroIngresoEdit[$campo] ?? '');
                        }
                    }
                    $detStatement = db()->prepare(
                        'SELECT id_determinacion_libro FROM libro_ingreso_determinaciones WHERE id_libro_ingreso = :id'
                    );
                    $detStatement->execute([':id' => $libroIngresoEditId]);
                    $libroIngresoForm['id_determinaciones'] = array_map('intval', $detStatement->fetchAll(PDO::FETCH_COLUMN));
                }
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No se pudo cargar el ingreso para editar.';
        }
    }
}

$libroAnioActual = libro_ingreso_anio_actual();
$libroDeterminacionesPorId = array_column($libroDeterminacionesOptions, 'nombre_determinacion_libro', 'id_determinacion_libro');
$libroProximoInase = null;
try {
    $libroProximoInase = libro_ingreso_proximo_nro_inase(current_empresa_id(), $libroAnioActual);
} catch (Throwable $throwable) {
    $libroProximoInase = null;
}
?>
<?php if ($presupuestoVista !== null): ?>
<section class="section-panel" id="presupuesto">
    <h2>Presupuesto de muestras</h2>
    <p>Cliente: <?php echo htmlspecialchars((string) $presupuestoVista['cliente']['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?>.
        Se usarán los precios vigentes al enviar, en pesos argentinos, sin agregar impuestos.</p>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>
    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php $muestrasSinDeterminaciones = array_filter($presupuestoVista['muestras'], static fn (array $muestra): bool => $muestra['determinaciones'] === []); ?>
    <?php if ($muestrasSinDeterminaciones !== []): ?>
        <div class="alert alert-warning">Las siguientes muestras no tienen determinaciones: <?php echo htmlspecialchars(implode(', ', array_map(static fn (array $muestra): string => (string) $muestra['ingreso']['nro_muestra'], $muestrasSinDeterminaciones)), ENT_QUOTES, 'UTF-8'); ?>. Edita los ingresos para agregarlas.</div>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-dark table-striped">
            <thead><tr><th>Muestra</th><th>Determinación</th><th>Precio</th></tr></thead>
            <tbody>
                <?php foreach ($presupuestoVista['muestras'] as $muestra): ?>
                    <?php foreach ($muestra['determinaciones'] as $determinacion): ?>
                        <tr><td><?php echo htmlspecialchars((string) $muestra['ingreso']['nro_muestra'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($determinacion['nombre_determinacion_libro'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo presupuesto_importe($determinacion['centavos']); ?></td></tr>
                    <?php endforeach; ?>
                    <tr><th colspan="2">Subtotal muestra <?php echo htmlspecialchars((string) $muestra['ingreso']['nro_muestra'], ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo presupuesto_importe($muestra['total_centavos']); ?></th></tr>
                <?php endforeach; ?>
                <tr><th colspan="2">Total</th><th><?php echo presupuesto_importe($presupuestoVista['total_centavos']); ?></th></tr>
            </tbody>
        </table></div>
        <?php $datosLaboratorioPresupuesto = get_certificate_settings(current_empresa_id()); ?>
        <?php if (certificate_logo_path(current_empresa_id()) === null || trim($datosLaboratorioPresupuesto['nombre_laboratorio']) === ''): ?>
            <div class="alert alert-warning">Configura el nombre y el logo del laboratorio en <a href="/index.php?page=configuracion">Configuración</a> antes de generar o enviar el presupuesto.</div>
        <?php else: ?>
            <p><a class="btn btn-outline-light" href="<?php echo htmlspecialchars('/index.php?' . http_build_query(['presupuesto_pdf' => '1', 'presupuesto_ids' => $presupuestoIds]), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Ver presupuesto PDF</a></p>
        <?php endif; ?>
        <?php if (in_array('', array_intersect_key($datosLaboratorioPresupuesto, array_flip(['direccion_laboratorio', 'telefono_laboratorio', 'email_laboratorio', 'cuit_laboratorio'])), true)): ?>
            <div class="alert alert-warning">Completa la dirección, teléfono, correo y CUIT del laboratorio en <a href="/index.php?page=configuracion">Configuración</a> para incluirlos en la cabecera.</div>
        <?php endif; ?>
        <?php if ($presupuestoEmails === []): ?>
            <div class="alert alert-warning">El cliente no tiene correos registrados. Agrega uno en <a href="/index.php?page=clientes">Clientes</a> para enviar el presupuesto.</div>
        <?php else: ?>
            <form method="post" action="<?php echo htmlspecialchars('/index.php?' . http_build_query(['page' => 'libro_ingreso', 'preparar_presupuesto' => '1', 'presupuesto_ids' => $presupuestoIds] + $libroFiltroQuery) . '#presupuesto', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="enviar_presupuesto" value="1">
                <?php foreach ($presupuestoIds as $id): ?>
                    <input type="hidden" name="presupuesto_ids[]" value="<?php echo $id; ?>">
                <?php endforeach; ?>
                <input type="hidden" name="presupuesto_token" value="<?php echo htmlspecialchars($_SESSION['presupuesto_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <p>Selecciona los destinatarios:</p>
                <?php foreach ($presupuestoEmails as $indice => $email): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="destinatarios[]" id="presupuesto_email_<?php echo $indice; ?>" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" checked>
                        <label class="form-check-label" for="presupuesto_email_<?php echo $indice; ?>"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></label>
                    </div>
                <?php endforeach; ?>
                <button class="btn btn-brand mt-3" type="submit">Enviar presupuesto por email</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
    <a class="btn btn-outline-brand mt-3" href="<?php echo htmlspecialchars('/index.php?' . http_build_query(['page' => 'libro_ingreso'] + $libroFiltroQuery) . '#ingresos-registrados', ENT_QUOTES, 'UTF-8'); ?>">Volver al libro de ingreso</a>
</section>
<?php endif; ?>
<section class="section-panel" id="ingreso-form">
    <h2><?php echo $libroIngresoEditId !== null ? 'Ver / editar ingreso' : 'Libro de Ingreso'; ?></h2>
    <p><?php echo $libroIngresoEditId !== null ? 'Revisa el ingreso completo y guarda los cambios. El N° INASE y el N° de muestra no se pueden modificar.' : 'Registro de las muestras recibidas. El N° INASE y el N° de muestra se asignan automáticamente al guardar.'; ?></p>

    <?php if ($presupuestoVista === null): ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endforeach; ?>
        <?php if ($successMessage !== null): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (($libroIngresoEditId !== null || isset($_GET['editar'])) && $libroIngresoEdit === null): ?>
        <a href="/index.php?page=libro_ingreso" class="btn btn-outline-brand">Volver al libro de ingreso</a>
    <?php elseif ($libroClientesOptions === [] || $libroEspeciesOptions === []): ?>
        <div class="alert alert-warning mb-0">Primero debes cargar al menos un cliente y una especie para registrar ingresos.</div>
    <?php else: ?>
        <form method="post" action="/index.php?page=libro_ingreso<?php echo $libroIngresoEdit !== null ? '&amp;editar=' . (int) $libroIngresoEdit['id_libro_ingreso'] : ''; ?>#ingreso-form" class="row g-3 mt-1">
            <?php if ($libroIngresoEditId !== null): ?>
                <input type="hidden" name="update_libro_ingreso" value="1">
                <input type="hidden" name="ingreso_id" value="<?php echo (int) $libroIngresoEditId; ?>">
            <?php else: ?>
                <input type="hidden" name="save_libro_ingreso" value="1">
            <?php endif; ?>

            <div class="col-md-2">
                <label for="libro_nro_inase" class="form-label">N° INASE</label>
                <input type="text" class="form-control" id="libro_nro_inase" value="<?php echo $libroIngresoEdit !== null ? htmlspecialchars(libro_ingreso_nro_inase_formateado((int) $libroIngresoEdit['nro_inase']), ENT_QUOTES, 'UTF-8') : ($libroProximoInase !== null ? htmlspecialchars(libro_ingreso_nro_inase_formateado($libroProximoInase), ENT_QUOTES, 'UTF-8') : 'Automático'); ?>" readonly disabled>
            </div>

            <div class="col-md-2">
                <label for="libro_nro_muestra" class="form-label">N° Muestra</label>
                <input type="text" class="form-control" id="libro_nro_muestra" value="<?php echo $libroIngresoEdit !== null ? htmlspecialchars((string) $libroIngresoEdit['nro_muestra'], ENT_QUOTES, 'UTF-8') : ($libroProximoInase !== null ? htmlspecialchars(libro_ingreso_nro_muestra($libroAnioActual, $libroProximoInase), ENT_QUOTES, 'UTF-8') : 'Automático'); ?>" readonly disabled>
            </div>

            <div class="col-md-2">
                <label for="presupuesto" class="form-label">Presupuesto *</label>
                <select class="form-select" id="presupuesto" name="presupuesto" required>
                    <?php foreach (['SI', 'NO'] as $opcion): ?>
                        <option value="<?php echo $opcion; ?>" <?php echo $libroIngresoForm['presupuesto'] === $opcion ? 'selected' : ''; ?>><?php echo $opcion; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="facturado" class="form-label">Facturado *</label>
                <select class="form-select" id="facturado" name="facturado" required>
                    <?php foreach (['SI', 'NO'] as $opcion): ?>
                        <option value="<?php echo $opcion; ?>" <?php echo $libroIngresoForm['facturado'] === $opcion ? 'selected' : ''; ?>><?php echo $opcion; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="fecha_entrada" class="form-label">Fecha de entrada *</label>
                <input type="date" class="form-control" id="fecha_entrada" name="fecha_entrada" value="<?php echo htmlspecialchars($libroIngresoForm['fecha_entrada'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="col-md-4">
                <label for="fecha_inicio_analisis" class="form-label">Fecha de inicio de análisis</label>
                <input type="date" class="form-control" id="fecha_inicio_analisis" name="fecha_inicio_analisis" value="<?php echo htmlspecialchars($libroIngresoForm['fecha_inicio_analisis'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-6">
                <label for="remitente" class="form-label">Remitente</label>
                <input type="text" class="form-control" id="remitente" name="remitente" maxlength="255" value="<?php echo htmlspecialchars($libroIngresoForm['remitente'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-6">
                <label for="libro_id_cliente" class="form-label">Cliente *</label>
                <select class="form-select" id="libro_id_cliente" name="id_cliente" required>
                    <option value="">Selecciona un cliente</option>
                    <?php foreach ($libroClientesOptions as $clienteOption): ?>
                        <option value="<?php echo htmlspecialchars((string) $clienteOption['id_cliente'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $libroIngresoForm['id_cliente'] === (string) $clienteOption['id_cliente'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) $clienteOption['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="origen" class="form-label">Origen</label>
                <input type="text" class="form-control" id="origen" name="origen" maxlength="255" value="<?php echo htmlspecialchars($libroIngresoForm['origen'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-4">
                <label for="libro_id_especie" class="form-label">Especie *</label>
                <select class="form-select" id="libro_id_especie" name="id_especie" data-cultivar-target="libro_id_cultivar" onchange="filterCultivaresTarget(this)" required>
                    <option value="">Selecciona una especie</option>
                    <?php foreach ($libroEspeciesOptions as $especieOption): ?>
                        <option value="<?php echo htmlspecialchars((string) $especieOption['id_especie'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $libroIngresoForm['id_especie'] === (string) $especieOption['id_especie'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) $especieOption['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="libro_id_cultivar" class="form-label">Cultivar / Variedad</label>
                <select class="form-select" id="libro_id_cultivar" name="id_cultivar">
                    <option value="">Selecciona un cultivar</option>
                    <?php foreach ($libroCultivaresOptions as $cultivarOption): ?>
                        <option value="<?php echo htmlspecialchars((string) $cultivarOption['id_cultivar'], ENT_QUOTES, 'UTF-8'); ?>" data-especie="<?php echo htmlspecialchars((string) $cultivarOption['id_especie'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $libroIngresoForm['id_cultivar'] === (string) $cultivarOption['id_cultivar'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) $cultivarOption['nombre_cultivar'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="lote_observaciones" class="form-label">N° Lote - Observaciones</label>
                <input type="text" class="form-control" id="lote_observaciones" name="lote_observaciones" maxlength="255" value="<?php echo htmlspecialchars($libroIngresoForm['lote_observaciones'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-6">
                <label for="ubicacion_camara_archivo" class="form-label">Ubicación cámara archivo</label>
                <input type="text" class="form-control" id="ubicacion_camara_archivo" name="ubicacion_camara_archivo" maxlength="255" value="<?php echo htmlspecialchars($libroIngresoForm['ubicacion_camara_archivo'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Determinaciones</label>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn btn-outline-brand" data-bs-toggle="modal" data-bs-target="#determinacionesModal">
                        <i class="bi bi-list-check" aria-hidden="true"></i> Elegir determinaciones
                    </button>
                    <span id="determinacionesResumen" class="text-muted">Ninguna seleccionada</span>
                </div>

                <div class="modal fade" id="determinacionesModal" tabindex="-1" aria-labelledby="determinacionesModalTitle" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable">
                        <div class="modal-content bg-dark text-light">
                            <div class="modal-header">
                                <h5 class="modal-title" id="determinacionesModalTitle">Determinaciones</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <?php if ($libroDeterminacionesOptions === []): ?>
                                    <p class="text-muted mb-0">No hay determinaciones cargadas.</p>
                                <?php endif; ?>
                                <?php foreach ($libroDeterminacionesOptions as $determinacionOption): ?>
                                    <?php $determinacionId = (int) $determinacionOption['id_determinacion_libro']; ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input determinacion-check" type="checkbox" name="id_determinaciones[]"
                                            id="determinacion_<?php echo $determinacionId; ?>"
                                            value="<?php echo $determinacionId; ?>"
                                            data-nombre="<?php echo htmlspecialchars((string) $determinacionOption['nombre_determinacion_libro'], ENT_QUOTES, 'UTF-8'); ?>"
                                            <?php echo in_array($determinacionId, $libroIngresoForm['id_determinaciones'], true) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="determinacion_<?php echo $determinacionId; ?>">
                                            <?php echo htmlspecialchars((string) $determinacionOption['nombre_determinacion_libro'], ENT_QUOTES, 'UTF-8'); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-brand" data-bs-dismiss="modal">Aceptar</button>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                    (function() {
                        const checks = document.querySelectorAll('.determinacion-check');
                        const resumen = document.getElementById('determinacionesResumen');
                        function actualizar() {
                            const nombres = Array.from(checks).filter(c => c.checked).map(c => c.dataset.nombre);
                            resumen.textContent = nombres.length ? nombres.join(', ') : 'Ninguna seleccionada';
                        }
                        checks.forEach(c => c.addEventListener('change', actualizar));
                        actualizar();
                    })();
                </script>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-brand"><?php echo $libroIngresoEditId !== null ? 'Guardar cambios' : 'Guardar ingreso'; ?></button>
                <a href="/index.php?page=libro_ingreso" class="btn btn-outline-brand"><?php echo $libroIngresoEditId !== null ? 'Cancelar' : 'Limpiar'; ?></a>
            </div>
        </form>
    <?php endif; ?>
</section>

<?php if ($presupuestoVista === null): ?><span id="presupuesto"></span><?php endif; ?>
<section class="section-panel" id="ingresos-registrados">
    <h2>Ingresos registrados</h2>
    <form method="get" action="/index.php#ingresos-registrados" class="row g-3 mb-3">
        <input type="hidden" name="page" value="libro_ingreso">
        <div class="col-md-4">
            <label for="libro_filtro_cliente" class="form-label">Cliente</label>
            <select id="libro_filtro_cliente" name="filtro_cliente" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($libroClientesOptions as $cliente): ?>
                    <option value="<?php echo (int) $cliente['id_cliente']; ?>" <?php echo $libroFiltro['filtro_cliente'] === (string) $cliente['id_cliente'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $cliente['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="libro_filtro_desde" class="form-label">Fecha de entrada desde</label>
            <input type="date" id="libro_filtro_desde" name="filtro_fecha_desde" class="form-control" value="<?php echo htmlspecialchars($libroFiltro['filtro_fecha_desde'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-3">
            <label for="libro_filtro_hasta" class="form-label">Fecha de entrada hasta</label>
            <input type="date" id="libro_filtro_hasta" name="filtro_fecha_hasta" class="form-control" value="<?php echo htmlspecialchars($libroFiltro['filtro_fecha_hasta'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-2 align-self-end d-flex gap-2">
            <button type="submit" class="btn btn-brand">Filtrar</button>
            <a href="/index.php?page=libro_ingreso#ingresos-registrados" class="btn btn-outline-brand">Limpiar</a>
        </div>
    </form>
    <?php foreach ($libroFiltroErrores as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>
    <?php if ($presupuestoError !== null): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($presupuestoError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <p>Filtra antes de seleccionar: al cambiar los filtros se reinicia la selección. Marca las muestras de un mismo cliente y pulsa <strong>Revisar y enviar presupuesto</strong>; luego podrás elegir los destinatarios y confirmar el envío.</p>
    <form id="seleccion-presupuesto" method="get" action="/index.php#presupuesto" class="mb-3">
        <input type="hidden" name="page" value="libro_ingreso">
        <input type="hidden" name="preparar_presupuesto" value="1">
        <?php foreach ($libroFiltroQuery as $campo => $valor): ?>
            <input type="hidden" name="<?php echo htmlspecialchars($campo, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>">
        <?php endforeach; ?>
        <button type="submit" class="btn btn-brand">Revisar y enviar presupuesto</button>
    </form>

    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col">Presupuestar</th>
                    <th>Muestra</th>
                    <th>Pres.</th>
                    <th>Fact</th>
                    <th>Entrada</th>
                    <th>Inicio</th>
                    <th>Cliente</th>
                    <th>Especie</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($libroIngresos === []): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted"><?php echo $libroFiltroErrores !== [] ? 'Revisa los filtros para consultar los ingresos.' : ($libroFiltroQuery === [] ? 'No hay ingresos registrados aún.' : 'No hay ingresos que coincidan con los filtros.'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($libroIngresos as $ingreso): ?>
                        <?php
                        $ingresoDeterminaciones = array_intersect_key(
                            $libroDeterminacionesPorId,
                            array_flip(explode(',', (string) ($ingreso['determinaciones_ids'] ?? '')))
                        );
                        $ingresoBoletines = array_intersect_key(
                            $navigation['boletines']['items'],
                            array_flip(libro_ingreso_boletines_disponibles(
                                $ingresoDeterminaciones,
                                (string) ($ingreso['nombre_especie'] ?? ''),
                                $ingreso['cientifico_especie'] ?? null
                            ))
                        );
                        $vinculosIngreso = $libroBoletinesVinculados[(int) $ingreso['id_libro_ingreso']] ?? [];
                        foreach ($vinculosIngreso as $tipoBoletin => $idBoletin) {
                            if (isset($navigation['boletines']['items'][$tipoBoletin])) {
                                $ingresoBoletines[$tipoBoletin] = $navigation['boletines']['items'][$tipoBoletin] . ' (Editar cargado)';
                            }
                        }
                        $certificadoDirectoDestino = libro_ingreso_certificado_directo_destino(array_values($ingresoDeterminaciones));
                        $certificadoHref = $certificadoDirectoDestino !== null
                            ? '/index.php?page=' . $certificadoDirectoDestino . '&ingreso_id=' . (int) $ingreso['id_libro_ingreso'] . '&auto=1'
                            : '/index.php?page=libro_ingreso_certificado&ingreso_id=' . (int) $ingreso['id_libro_ingreso'];
                        ?>
                        <tr>
                            <td><input type="checkbox" class="form-check-input" form="seleccion-presupuesto" name="presupuesto_ids[]" value="<?php echo (int) $ingreso['id_libro_ingreso']; ?>" <?php echo in_array((int) $ingreso['id_libro_ingreso'], $presupuestoIds, true) ? 'checked' : ''; ?> aria-label="Presupuestar muestra <?php echo htmlspecialchars((string) $ingreso['nro_muestra'], ENT_QUOTES, 'UTF-8'); ?>"></td>
                            <td><?php echo htmlspecialchars((string) $ingreso['nro_muestra'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $ingreso['presupuesto'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $ingreso['facturado'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(format_date_short((string) $ingreso['fecha_entrada']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(format_date_short((string) ($ingreso['fecha_inicio_analisis'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $ingreso['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($ingreso['nombre_especie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <a class="btn btn-sm btn-outline-light libro-ingreso-accion"
                                    href="/index.php?page=libro_ingreso&amp;editar=<?php echo (int) $ingreso['id_libro_ingreso']; ?>#ingreso-form"
                                    title="Ver / editar ingreso"
                                    aria-label="Ver y editar ingreso de la muestra <?php echo htmlspecialchars((string) $ingreso['nro_muestra'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                <button type="button" class="btn btn-sm btn-brand libro-ingreso-accion"
                                    data-bs-toggle="modal" data-bs-target="#libroBoletinModal"
                                    data-ingreso-id="<?php echo (int) $ingreso['id_libro_ingreso']; ?>"
                                    data-boletines="<?php echo htmlspecialchars(json_encode($ingresoBoletines, JSON_THROW_ON_ERROR), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-muestra="<?php echo htmlspecialchars((string) $ingreso['nro_muestra'], ENT_QUOTES, 'UTF-8'); ?>"
                                    aria-label="Abrir o cargar boletín de la muestra <?php echo htmlspecialchars((string) $ingreso['nro_muestra'], ENT_QUOTES, 'UTF-8'); ?>">BOL</button>
                                <a class="btn btn-sm btn-brand libro-ingreso-accion mt-1"
                                        href="<?php echo htmlspecialchars($certificadoHref, ENT_QUOTES, 'UTF-8'); ?>"
                                    aria-label="Precargar certificado de la muestra <?php echo htmlspecialchars((string) $ingreso['nro_muestra'], ENT_QUOTES, 'UTF-8'); ?>">CERT</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($libroIngresos !== []): ?>
        <button type="submit" form="seleccion-presupuesto" class="btn btn-brand mt-3">Revisar y enviar presupuesto</button>
    <?php endif; ?>
</section>

<div class="modal fade" id="libroBoletinModal" tabindex="-1" aria-labelledby="libroBoletinModalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <form method="get" action="/index.php" class="modal-content bg-dark text-light">
            <div class="modal-header">
                <h5 class="modal-title" id="libroBoletinModalTitle">Abrir o cargar boletín</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>Muestra N° <strong id="libroBoletinMuestra"></strong>. Selecciona un boletín ya cargado para editarlo o uno nuevo para cargarlo.</p>
                <input type="hidden" name="ingreso_id" id="libroBoletinIngresoId">
                <label for="libroBoletinTipo" class="form-label">Tipo de boletín</label>
                <select class="form-select" name="page" id="libroBoletinTipo" required>
                    <option value="">Selecciona un boletín</option>
                </select>
                <div id="libroBoletinSinOpciones" class="alert alert-warning mt-3 mb-0" role="status" hidden>Este ingreso no tiene determinaciones con un boletín disponible.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-brand" id="libroBoletinPrecargar">Abrir / cargar</button>
            </div>
        </form>
    </div>
</div>
<script>
    document.getElementById('libroBoletinModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('libroBoletinIngresoId').value = button.dataset.ingresoId;
        document.getElementById('libroBoletinMuestra').textContent = button.dataset.muestra;
        const tipos = Object.entries(JSON.parse(button.dataset.boletines));
        const select = document.getElementById('libroBoletinTipo');
        select.replaceChildren(new Option('Selecciona un boletín', ''));
        tipos.forEach(([value, label]) => select.add(new Option(label, value)));
        select.disabled = tipos.length === 0;
        document.getElementById('libroBoletinPrecargar').disabled = tipos.length === 0;
        document.getElementById('libroBoletinSinOpciones').hidden = tipos.length !== 0;
    });
</script>