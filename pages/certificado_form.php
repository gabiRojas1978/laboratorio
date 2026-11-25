<?php

declare(strict_types=1);

$clientesOptions = [];
$especiesOptions = [];
$cultivaresOptions = [];
$determinacionesOptions = [];
$certificados = [];

$certTiposPorPagina = [
    'Certificadogeneral' => 'certificadogeneral',
    'CertificadoINASE' => 'certificadoinase',
    'CertificadoTetrazolio' => 'certificadotetrazolio',
];
$certTipoActual = $certTiposPorPagina[$page] ?? 'certificadogeneral';
if (!isset($_SESSION['certificado_delete_token'])) {
    $_SESSION['certificado_delete_token'] = bin2hex(random_bytes(32));
}
$certDeleteQuery = array_intersect_key($_GET, array_flip(['page', 'filtro_cliente', 'filtro_especie', 'filtro_fecha_desde', 'filtro_fecha_hasta', 'cert_page']));
$certDeleteQuery['page'] = $page;
$certDeleteAction = '/index.php?' . http_build_query($certDeleteQuery) . '#certificados-listado';

try {
    $clientesOptionsStatement = db()->prepare('SELECT id_cliente, nombre_cliente FROM clientes WHERE id_empresa = :id_empresa ORDER BY nombre_cliente ASC');
    $clientesOptionsStatement->execute([':id_empresa' => current_empresa_id()]);
    $clientesOptions = $clientesOptionsStatement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el selector de clientes.';
}

try {
    $especiesOptionsStatement = db()->prepare('SELECT id_especie, nombre_especie, cientifico_especie FROM especies WHERE id_empresa = :id_empresa ORDER BY nombre_especie ASC');
    $especiesOptionsStatement->execute([':id_empresa' => current_empresa_id()]);
    $especiesOptions = $especiesOptionsStatement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el selector de especies.';
}

try {
    $cultivaresOptionsStatement = db()->prepare('SELECT c.id_cultivar, c.nombre_cultivar, c.id_especie FROM cultivares c WHERE c.id_empresa = :id_empresa ORDER BY c.nombre_cultivar ASC');
    $cultivaresOptionsStatement->execute([':id_empresa' => current_empresa_id()]);
    $cultivaresOptions = $cultivaresOptionsStatement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el selector de cultivares.';
}

try {
    $determinacionesOptionsStatement = db()->prepare('SELECT id_determinacion, nombre_determinacion FROM otras_determinaciones WHERE id_empresa = :id_empresa ORDER BY nombre_determinacion ASC');
    $determinacionesOptionsStatement->execute([':id_empresa' => current_empresa_id()]);
    $determinacionesOptions = $determinacionesOptionsStatement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el selector de determinaciones.';
}

$certFilter['id_cliente'] = trim((string) ($_GET['filtro_cliente'] ?? ''));
$certFilter['id_especie'] = trim((string) ($_GET['filtro_especie'] ?? ''));
$certFilter['fecha_desde'] = trim((string) ($_GET['filtro_fecha_desde'] ?? ''));
$certFilter['fecha_hasta'] = trim((string) ($_GET['filtro_fecha_hasta'] ?? ''));

$certConditions = ['c.id_empresa = :id_empresa', 'c.tipo_certificado = :cert_tipo_actual'];
$certParams = [':id_empresa' => current_empresa_id(), ':cert_tipo_actual' => $certTipoActual];

$filterClienteId = filter_var($certFilter['id_cliente'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($filterClienteId !== false) {
    $certConditions[] = 'c.id_cliente_certificado = :filtro_cliente';
    $certParams[':filtro_cliente'] = $filterClienteId;
}

$filterEspecieId = filter_var($certFilter['id_especie'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($filterEspecieId !== false) {
    $certConditions[] = 'c.id_especie_certificado = :filtro_especie';
    $certParams[':filtro_especie'] = $filterEspecieId;
}

if ($certFilter['fecha_desde'] !== '' && strtotime($certFilter['fecha_desde']) !== false) {
    $certConditions[] = 'c.fecha_emision_certificado >= :filtro_fecha_desde';
    $certParams[':filtro_fecha_desde'] = $certFilter['fecha_desde'];
}

if ($certFilter['fecha_hasta'] !== '' && strtotime($certFilter['fecha_hasta']) !== false) {
    $certConditions[] = 'c.fecha_emision_certificado <= :filtro_fecha_hasta';
    $certParams[':filtro_fecha_hasta'] = $certFilter['fecha_hasta'];
}

$certWhere = $certConditions !== [] ? ' WHERE ' . implode(' AND ', $certConditions) : '';

$certPerPage = 10;
$certPage = filter_var($_GET['cert_page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'default' => 1]]);
$certTotalPages = 1;

try {
    $countStatement = db()->prepare(
        'SELECT COUNT(*) AS total FROM certificados c' . $certWhere
    );
    $countStatement->execute($certParams);
    $certTotal = (int) $countStatement->fetch()['total'];
    $certTotalPages = max(1, (int) ceil($certTotal / $certPerPage));
    $certPage = min($certPage, $certTotalPages);
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo contar el listado de certificados.';
}

$certOffset = ($certPage - 1) * $certPerPage;

try {
    $certStatement = db()->prepare(
        'SELECT c.id_certificado, c.tipo_certificado, c.fecha_emision_certificado, cl.nombre_cliente, e.nombre_especie, cu.nombre_cultivar, u.nombre_usuario FROM certificados c LEFT JOIN clientes cl ON cl.id_cliente = c.id_cliente_certificado LEFT JOIN especies e ON e.id_especie = c.id_especie_certificado LEFT JOIN cultivares cu ON cu.id_cultivar = c.id_cultivar_certificado LEFT JOIN usuarios u ON u.id_usuario = c.id_usuario_certificado' . $certWhere . ' ORDER BY c.id_certificado DESC LIMIT :cert_limit OFFSET :cert_offset'
    );
    foreach ($certParams as $paramKey => $paramValue) {
        $certStatement->bindValue($paramKey, $paramValue);
    }
    $certStatement->bindValue(':cert_limit', $certPerPage, PDO::PARAM_INT);
    $certStatement->bindValue(':cert_offset', $certOffset, PDO::PARAM_INT);
    $certStatement->execute();
    $certificados = $certStatement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el listado de certificados.';
}
?>
<section class="section-panel">
    <h2>Alta de certificado</h2>
    <p>Completa los datos del análisis y guarda el certificado.</p>
    <?php foreach ($certPrecargaAvisos ?? [] as $aviso): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($aviso, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($newCertificadoId !== null): ?>
        <script>
            (function() {
                const certificadoId = <?php echo json_encode((string) $newCertificadoId); ?>;
                const clienteEmails = <?php echo json_encode($newCertificadoClienteEmails, JSON_UNESCAPED_UNICODE); ?>;

                const downloadFrame = document.createElement('iframe');
                downloadFrame.style.display = 'none';
                downloadFrame.src = '/index.php?pdf=' + encodeURIComponent(certificadoId) + '&download=1';
                document.body.appendChild(downloadFrame);

                document.addEventListener('DOMContentLoaded', function() {
                    if (clienteEmails.length === 0) {
                        return;
                    }

                    const listContainer = document.getElementById('certificadoEmailList');
                    const feedback = document.getElementById('certificadoEmailFeedback');
                    const sendBtn = document.getElementById('certificadoEmailSendBtn');
                    listContainer.innerHTML = clienteEmails.map(function(email, index) {
                        const optId = 'certEmailOpt' + index;
                        return '<div class="form-check"><input class="form-check-input" type="checkbox" value="' + email + '" id="' + optId + '" checked>' +
                            '<label class="form-check-label" for="' + optId + '">' + email + '</label></div>';
                    }).join('');

                    const modal = new bootstrap.Modal(document.getElementById('certificadoEmailModal'));
                    modal.show();

                    sendBtn.addEventListener('click', function() {
                        const selected = Array.from(listContainer.querySelectorAll('input[type=checkbox]:checked')).map(function(el) {
                            return el.value;
                        });
                        if (selected.length === 0) {
                            feedback.innerHTML = '<div class="alert alert-warning py-2 mb-0">Selecciona al menos un email.</div>';
                            return;
                        }

                        feedback.innerHTML = '<div class="text-muted">Enviando...</div>';
                        sendBtn.disabled = true;
                        const formData = new FormData();
                        formData.append('enviar_certificado_email', '1');
                        formData.append('certificado_id', certificadoId);
                        selected.forEach(function(email) {
                            formData.append('destinatarios[]', email);
                        });

                        fetch('/index.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(function(response) {
                                return response.json();
                            })
                            .then(function(data) {
                                sendBtn.disabled = false;
                                feedback.innerHTML = data.ok ?
                                    '<div class="alert alert-success py-2 mb-0">Certificado enviado correctamente.</div>' :
                                    '<div class="alert alert-danger py-2 mb-0">' + (data.error || 'No fue posible enviar el email.') + '</div>';
                            })
                            .catch(function() {
                                sendBtn.disabled = false;
                                feedback.innerHTML = '<div class="alert alert-danger py-2 mb-0">No fue posible enviar el email.</div>';
                            });
                    });
                });
            })();
        </script>
    <?php endif; ?>

    <?php if ($clientesOptions === [] || $especiesOptions === []): ?>
        <div class="alert alert-warning mb-0">Primero debes cargar al menos un cliente y una especie para poder dar de alta certificados.</div>
    <?php else: ?>
        <form method="post" class="row g-3 mt-1">
            <input type="hidden" name="save_certificado" value="1">
            <input type="hidden" name="tipo_certificado" value="<?php echo htmlspecialchars($certTipoActual, ENT_QUOTES, 'UTF-8'); ?>">

            <!-- Datos principales del certificado -->
            <div class="col-md-4">
                <label for="id_cliente_certificado" class="form-label">Cliente *</label>
                <select class="form-select" id="id_cliente_certificado" name="id_cliente_certificado" required>
                    <option value="">Selecciona un cliente</option>
                    <?php foreach ($clientesOptions as $cliente): ?>
                        <option value="<?php echo htmlspecialchars((string) $cliente['id_cliente'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $certForm['id_cliente_certificado'] === (string) $cliente['id_cliente'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) $cliente['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="id_especie_certificado" class="form-label">Especie/Nombre científico *</label>
                <select class="form-select" id="id_especie_certificado" name="id_especie_certificado" required onchange="filterCultivares()">
                    <option value="">Selecciona una especie</option>
                    <?php foreach ($especiesOptions as $especie): ?>
                        <option value="<?php echo htmlspecialchars((string) $especie['id_especie'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $certForm['id_especie_certificado'] === (string) $especie['id_especie'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) $especie['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?><?php echo ($especie['cientifico_especie'] ? ' (' . htmlspecialchars((string) $especie['cientifico_especie'], ENT_QUOTES, 'UTF-8') . ')' : ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($page === 'CertificadoTetrazolio'): ?>
                <div class="col-md-4">
                    <label for="tetrazolio_detalle_certificado" class="form-label">Detalle</label>
                    <input type="text" class="form-control" id="tetrazolio_detalle_certificado" name="tetrazolio_detalle_certificado" maxlength="255" value="<?php echo htmlspecialchars($certForm['tetrazolio_detalle_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            <?php else: ?>
                <div class="col-md-4">
                    <label for="id_cultivar_certificado" class="form-label">Cultivar</label>
                    <select class="form-select" id="id_cultivar_certificado" name="id_cultivar_certificado">
                        <option value="">Selecciona un cultivar</option>
                        <?php foreach ($cultivaresOptions as $cultivar): ?>
                            <option value="<?php echo htmlspecialchars((string) $cultivar['id_cultivar'], ENT_QUOTES, 'UTF-8'); ?>" data-especie="<?php echo htmlspecialchars((string) $cultivar['id_especie'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $certForm['id_cultivar_certificado'] === (string) $cultivar['id_cultivar'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) $cultivar['nombre_cultivar'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <hr class="my-4">
            <h5>Información del Lote</h5>

            <?php if ($page !== 'CertificadoTetrazolio'): ?>
                <div class="col-md-4">
                    <label for="marca_lote_certificado" class="form-label">Marca/Lote</label>
                    <input type="text" class="form-control" id="marca_lote_certificado" name="marca_lote_certificado" maxlength="255" value="<?php echo htmlspecialchars($certForm['marca_lote_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            <?php endif; ?>

            <div class="col-md-4">
                <label for="peso_certificado" class="form-label">Peso (kg)</label>
                <input type="number" class="form-control" id="peso_certificado" name="peso_certificado" step="0.01" min="0" value="<?php echo htmlspecialchars($certForm['peso_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-4">
                <label for="nro_envases_certificado" class="form-label">Nro de envases</label>
                <input type="number" class="form-control" id="nro_envases_certificado" name="nro_envases_certificado" min="0" value="<?php echo htmlspecialchars($certForm['nro_envases_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-4">
                <label for="otras_referencias_certificado" class="form-label">Otras referencias</label>
                <input type="text" class="form-control" id="otras_referencias_certificado" name="otras_referencias_certificado" maxlength="255" value="<?php echo htmlspecialchars($certForm['otras_referencias_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-4">
                <label for="precinto_certificado" class="form-label">N° de Precinto</label>
                <input type="text" class="form-control" id="precinto_certificado" name="precinto_certificado" maxlength="100" value="<?php echo htmlspecialchars($certForm['precinto_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-4">
                <label for="nro_analisis_certificado" class="form-label">N° de Análisis *</label>
                <input type="text" class="form-control" id="nro_analisis_certificado" name="nro_analisis_certificado" maxlength="100" value="<?php echo htmlspecialchars($certForm['nro_analisis_certificado'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <hr class="my-4">
            <h5>Fechas</h5>

            <div class="col-md-3">
                <label for="fecha_muestreo_certificado" class="form-label">Fecha de muestreo</label>
                <input type="date" class="form-control" id="fecha_muestreo_certificado" name="fecha_muestreo_certificado" value="<?php echo htmlspecialchars($certForm['fecha_muestreo_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-3" style="width: 29.1667%;">
                <label for="fecha_muestras_certificado" class="form-label">Fecha de recepción de muestras *</label>
                <input type="date" class="form-control" id="fecha_muestras_certificado" name="fecha_muestras_certificado" value="<?php echo htmlspecialchars($certForm['fecha_muestras_certificado'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="col-md-3">
                <label for="fecha_finalizacion_ensayos_certificado" class="form-label">Fecha finalización de ensayos *</label>
                <input type="date" class="form-control" id="fecha_finalizacion_ensayos_certificado" name="fecha_finalizacion_ensayos_certificado" value="<?php echo htmlspecialchars($certForm['fecha_finalizacion_ensayos_certificado'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <?php if ($page === 'CertificadoTetrazolio'): ?>
                <hr class="my-4">
                <h5>Viabilidad por tetrazolio (%)</h5>
                <p class="text-muted mb-0">Las cinco categorías deben sumar 100. El total de viabilidad se calcula únicamente con las cuatro categorías viables.</p>

                <?php
                $tetrazolioViabilidadFields = [
                    'tetrazolio_viable_sin_defectos_certificado' => '1. Viable sin defectos',
                    'tetrazolio_viable_defectos_leves_certificado' => '2. Viable defectos leves',
                    'tetrazolio_viable_defectos_moderados_certificado' => '3. Viable defectos moderados',
                    'tetrazolio_viable_defectos_severos_certificado' => '4. Viable defectos severos (límite crítico)',
                    'tetrazolio_no_viables_certificado' => '5. No viables',
                ];
                ?>
                <?php foreach ($tetrazolioViabilidadFields as $field => $label): ?>
                    <div class="col-md">
                        <label for="<?php echo $field; ?>" class="form-label"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?> *</label>
                        <input type="number" class="form-control" id="<?php echo $field; ?>" name="<?php echo $field; ?>" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($certForm[$field], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                <?php endforeach; ?>

                <hr class="my-4">
                <h5>Vigor por tetrazolio (%)</h5>
                <?php
                $tetrazolioVigorFields = [
                    'tetrazolio_vigor_alto_certificado' => 'A. Vigor alto',
                    'tetrazolio_vigor_medio_certificado' => 'B. Vigor medio',
                    'tetrazolio_vigor_bajo_certificado' => 'C. Vigor bajo',
                ];
                ?>
                <?php foreach ($tetrazolioVigorFields as $field => $label): ?>
                    <div class="col-md-4">
                        <label for="<?php echo $field; ?>" class="form-label"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="number" class="form-control" id="<?php echo $field; ?>" name="<?php echo $field; ?>" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($certForm[$field], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                <?php endforeach; ?>

                <hr class="my-4">
                <h5>Daños acumulados (%)</h5>
                <?php
                $tetrazolioDanosFields = [
                    'tetrazolio_danos_mecanicos_certificado' => 'Mecánicos',
                    'tetrazolio_danos_ambientes_certificado' => 'Ambientes',
                    'tetrazolio_danos_chinches_certificado' => 'Chinches',
                    'tetrazolio_danos_fracturas_certificado' => 'Fracturas',
                    'tetrazolio_danos_otros_certificado' => 'Otros',
                    'tetrazolio_duras_certificado' => 'Duras',
                ];
                ?>
                <?php foreach ($tetrazolioDanosFields as $field => $label): ?>
                    <div class="col-md-2">
                        <label for="<?php echo $field; ?>" class="form-label"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="number" class="form-control" id="<?php echo $field; ?>" name="<?php echo $field; ?>" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($certForm[$field], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
            <hr class="my-4">
            <h5>Pureza (%)</h5>

            <div class="col-md-3">
                <label for="pureza_semilla_pura_certificado" class="form-label">Semilla pura</label>
                <input type="number" class="form-control" id="pureza_semilla_pura_certificado" name="pureza_semilla_pura_certificado" step="0.01" min="0" value="<?php echo htmlspecialchars($certForm['pureza_semilla_pura_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-3">
                <label for="materia_inerte_certificado" class="form-label">Materia inerte</label>
                <input type="number" class="form-control" id="materia_inerte_certificado" name="materia_inerte_certificado" step="0.01" min="0" value="<?php echo htmlspecialchars($certForm['materia_inerte_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-3">
                <label for="pureza_otras_semillas_certificado" class="form-label">Otras semillas</label>
                <input type="number" class="form-control" id="pureza_otras_semillas_certificado" name="pureza_otras_semillas_certificado" step="0.01" min="0" value="<?php echo htmlspecialchars($certForm['pureza_otras_semillas_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <hr class="my-4">
            <h5><?php echo $page === 'CertificadoINASE' ? 'Germinación' : 'Poder Germinativo'; ?></h5>

            <div class="col-md-2">
                <label for="pg_numero_dias_certificado" class="form-label">Días</label>
                <input type="number" step="1" min="0" class="form-control" id="pg_numero_dias_certificado" name="pg_numero_dias_certificado" value="<?php echo htmlspecialchars($certForm['pg_numero_dias_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-2">
                <label for="pg_plantulas_normales_certificado" class="form-label">Plántulas normales</label>
                <input type="number" step="0.01" min="0" class="form-control" id="pg_plantulas_normales_certificado" name="pg_plantulas_normales_certificado" value="<?php echo htmlspecialchars($certForm['pg_plantulas_normales_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-2">
                <label for="pg_semillas_duras_certificado" class="form-label">Semillas duras</label>
                <input type="number" step="0.01" min="0" class="form-control" id="pg_semillas_duras_certificado" name="pg_semillas_duras_certificado" value="<?php echo htmlspecialchars($certForm['pg_semillas_duras_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-2">
                <label for="pg_semillas_frescas_certificado" class="form-label">Semillas frescas</label>
                <input type="number" step="0.01" min="0" class="form-control" id="pg_semillas_frescas_certificado" name="pg_semillas_frescas_certificado" value="<?php echo htmlspecialchars($certForm['pg_semillas_frescas_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-2">
                <label for="pg_plantulas_anormales_certificado" class="form-label">Plántulas anormales</label>
                <input type="number" step="0.01" min="0" class="form-control" id="pg_plantulas_anormales_certificado" name="pg_plantulas_anormales_certificado" value="<?php echo htmlspecialchars($certForm['pg_plantulas_anormales_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-2">
                <label for="pg_semillas_muertas_certificado" class="form-label">Semillas muertas</label>
                <input type="number" class="form-control" id="pg_semillas_muertas_certificado" name="pg_semillas_muertas_certificado" step="0.01" min="0" value="<?php echo htmlspecialchars($certForm['pg_semillas_muertas_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-12">
                <label for="pg_contenido_humedad_certificado" class="form-label"><?php echo $page === 'Certificadogeneral' ? 'Contenido humedad (%) — Higrómetro Delver' : 'Contenido humedad (%)'; ?></label>
                <input type="number" step="0.01" min="0" class="form-control" id="pg_contenido_humedad_certificado" name="pg_contenido_humedad_certificado" value="<?php echo htmlspecialchars($certForm['pg_contenido_humedad_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <?php if ($page !== 'CertificadoINASE'): ?>
                <hr class="my-4">
                <h5>Poder Germinativo Curado</h5>

                <div class="col-md-2">
                    <label for="pgc_numero_dias_certificado" class="form-label">Días</label>
                    <input type="number" step="1" min="0" class="form-control" id="pgc_numero_dias_certificado" name="pgc_numero_dias_certificado" value="<?php echo htmlspecialchars($certForm['pgc_numero_dias_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-md-2">
                    <label for="pgc_plantulas_normales_certificado" class="form-label">Plántulas normales</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="pgc_plantulas_normales_certificado" name="pgc_plantulas_normales_certificado" value="<?php echo htmlspecialchars($certForm['pgc_plantulas_normales_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-md-2">
                    <label for="pgc_semillas_duras_certificado" class="form-label">Semillas duras</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="pgc_semillas_duras_certificado" name="pgc_semillas_duras_certificado" value="<?php echo htmlspecialchars($certForm['pgc_semillas_duras_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-md-2">
                    <label for="pgc_semillas_frescas_certificado" class="form-label">Semillas frescas</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="pgc_semillas_frescas_certificado" name="pgc_semillas_frescas_certificado" value="<?php echo htmlspecialchars($certForm['pgc_semillas_frescas_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-md-2">
                    <label for="pgc_plantulas_anormales_certificado" class="form-label">Plántulas anormales</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="pgc_plantulas_anormales_certificado" name="pgc_plantulas_anormales_certificado" value="<?php echo htmlspecialchars($certForm['pgc_plantulas_anormales_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-md-2">
                    <label for="pgc_semillas_muertas_certificado" class="form-label">Semillas muertas</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="pgc_semillas_muertas_certificado" name="pgc_semillas_muertas_certificado" value="<?php echo htmlspecialchars($certForm['pgc_semillas_muertas_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <hr class="my-4">
                <h5>Vigor: Cold Test (prueba de frío)</h5>

                <div class="col-md-6">
                    <label for="vigor_nro_dias_certificado" class="form-label">Nro de días</label>
                    <input type="number" class="form-control" id="vigor_nro_dias_certificado" name="vigor_nro_dias_certificado" min="0" value="<?php echo htmlspecialchars($certForm['vigor_nro_dias_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-md-6">
                    <label for="vigor_plantulas_normales_certificado" class="form-label">Plántulas normales</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="vigor_plantulas_normales_certificado" name="vigor_plantulas_normales_certificado" value="<?php echo htmlspecialchars($certForm['vigor_plantulas_normales_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            <?php endif; ?>

            <hr class="my-4">

            <div class="col-md-6">
                <label for="clase_materia_inerte_certificado" class="form-label">Clase de Materia Inerte</label>
                <input type="text" step="1" class="form-control" id="clase_materia_inerte_certificado" name="clase_materia_inerte_certificado" maxlength="100" value="<?php echo htmlspecialchars($certForm['clase_materia_inerte_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-6">
                <label for="otras_semillas_certificado" class="form-label">Otras semillas</label>
                <input type="text" step="1" class="form-control" id="otras_semillas_certificado" name="otras_semillas_certificado" value="<?php echo htmlspecialchars($certForm['otras_semillas_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <?php if ($page === 'Certificadogeneral'): ?>
                <div class="col-md-6">
                    <label for="hipoclorito_max_10_certificado" class="form-label">Hipoclorito max 10%:</label>
                    <input type="text" class="form-control" id="hipoclorito_max_10_certificado" name="hipoclorito_max_10_certificado" maxlength="255" value="<?php echo htmlspecialchars($certForm['hipoclorito_max_10_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-6">
                    <label for="bioensayo_enlist_certificado" class="form-label">Bioensayo Enlist:</label>
                    <input type="text" class="form-control" id="bioensayo_enlist_certificado" name="bioensayo_enlist_certificado" maxlength="255" value="<?php echo htmlspecialchars($certForm['bioensayo_enlist_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-3">
                    <label for="peroxidasa_positivas_certificado" class="form-label">Peroxidasa positivas (%)</label>
                    <input type="number" class="form-control" id="peroxidasa_positivas_certificado" name="peroxidasa_positivas_certificado" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($certForm['peroxidasa_positivas_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-3">
                    <label for="peroxidasa_negativas_certificado" class="form-label">Peroxidasa negativas (%)</label>
                    <input type="number" class="form-control" id="peroxidasa_negativas_certificado" name="peroxidasa_negativas_certificado" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($certForm['peroxidasa_negativas_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-3">
                    <label for="contam_hilo_cantidad_semillas_certificado" class="form-label">Contam. x hilo (250gr) - Cantidad de semillas (%)</label>
                    <input type="number" class="form-control" id="contam_hilo_cantidad_semillas_certificado" name="contam_hilo_cantidad_semillas_certificado" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($certForm['contam_hilo_cantidad_semillas_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-3">
                    <label for="contam_hilo_color_contaminante_certificado" class="form-label">Contam. x hilo (250gr) - Color contaminante</label>
                    <input type="text" class="form-control" id="contam_hilo_color_contaminante_certificado" name="contam_hilo_color_contaminante_certificado" maxlength="255" value="<?php echo htmlspecialchars($certForm['contam_hilo_color_contaminante_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-3">
                    <label for="hipocotilo_variedad_certificado" class="form-label">Hipocotilo - Variedad</label>
                    <input type="text" class="form-control" id="hipocotilo_variedad_certificado" name="hipocotilo_variedad_certificado" maxlength="255" value="<?php echo htmlspecialchars($certForm['hipocotilo_variedad_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-3">
                    <label for="hipocotilo_color_contaminante_certificado" class="form-label">Hipocotilo - Color contaminante</label>
                    <input type="text" class="form-control" id="hipocotilo_color_contaminante_certificado" name="hipocotilo_color_contaminante_certificado" maxlength="255" value="<?php echo htmlspecialchars($certForm['hipocotilo_color_contaminante_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            <?php endif; ?>

            <hr class="my-4">
            <h5>Otras Determinaciones</h5>

            <div class="col-12">
                <select class="form-select" id="id_otras_determinaciones_certificado" name="id_otras_determinaciones_certificado[]" multiple size="5">
                    <?php foreach ($determinacionesOptions as $det): ?>
                        <option value="<?php echo htmlspecialchars((string) $det['id_determinacion'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo in_array((int) $det['id_determinacion'], $certForm['id_otras_determinaciones_certificado'], true) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) $det['nombre_determinacion'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Mantené Ctrl (o Cmd) presionado para seleccionar varias opciones.</div>
            </div>

            <div class="col-12">
                <label for="otras_determinaciones_texto_certificado" class="form-label">Otra determinación (si no está en la lista)</label>
                <input type="text" class="form-control" id="otras_determinaciones_texto_certificado" name="otras_determinaciones_texto_certificado" maxlength="255" value="<?php echo htmlspecialchars($certForm['otras_determinaciones_texto_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <?php endif; ?>

            <div class="col-md-2">
                <label for="ampara_totalidad_lote_certificado" class="form-label">Ampara la totalidad del Lote?</label>
                <select class="form-select" id="ampara_totalidad_lote_certificado" name="ampara_totalidad_lote_certificado">
                    <option value="S" <?php echo $certForm['ampara_totalidad_lote_certificado'] === 'S' ? 'selected' : ''; ?>>Si</option>
                    <option value="N" <?php echo $certForm['ampara_totalidad_lote_certificado'] === 'N' ? 'selected' : ''; ?>>No</option>
                </select>
            </div>

            <div class="col-md-3">
                <label for="fecha_emision_certificado" class="form-label">Fecha de emisión</label>
                <input type="date" class="form-control" id="fecha_emision_certificado" name="fecha_emision_certificado"
                    value="<?php echo htmlspecialchars(!empty($certForm['fecha_emision_certificado']) ? $certForm['fecha_emision_certificado'] : date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-brand">Guardar certificado</button>
                <a href="/index.php?page=<?php echo htmlspecialchars($page, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-brand">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</section>

<section class="section-panel" id="certificados-listado">
    <h2>Certificados cargados</h2>
    <p>Listado de certificados guardados en la base de datos.</p>

    <?php if (isset($_POST['delete_certificado'])): ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if ($successMessage === 'Certificado eliminado correctamente.'): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="get" class="row g-3 mb-3" action="/index.php#certificados-listado">
        <input type="hidden" name="page" value="<?php echo htmlspecialchars($page, ENT_QUOTES, 'UTF-8'); ?>">

        <div class="col-md-3">
            <label for="filtro_cliente" class="form-label">Cliente</label>
            <select class="form-select" id="filtro_cliente" name="filtro_cliente">
                <option value="">Todos</option>
                <?php foreach ($clientesOptions as $clienteOption): ?>
                    <option value="<?php echo htmlspecialchars((string) $clienteOption['id_cliente'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $certFilter['id_cliente'] === (string) $clienteOption['id_cliente'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string) $clienteOption['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label for="filtro_especie" class="form-label">Especie</label>
            <select class="form-select" id="filtro_especie" name="filtro_especie">
                <option value="">Todas</option>
                <?php foreach ($especiesOptions as $especieOption): ?>
                    <option value="<?php echo htmlspecialchars((string) $especieOption['id_especie'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $certFilter['id_especie'] === (string) $especieOption['id_especie'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string) $especieOption['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label for="filtro_fecha_desde" class="form-label">Emisión desde</label>
            <input type="date" class="form-control" id="filtro_fecha_desde" name="filtro_fecha_desde" value="<?php echo htmlspecialchars($certFilter['fecha_desde'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-md-2">
            <label for="filtro_fecha_hasta" class="form-label">Emisión hasta</label>
            <input type="date" class="form-control" id="filtro_fecha_hasta" name="filtro_fecha_hasta" value="<?php echo htmlspecialchars($certFilter['fecha_hasta'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-md-2 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-brand w-100"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="/index.php?page=<?php echo htmlspecialchars($page, ENT_QUOTES, 'UTF-8'); ?>#certificados-listado" class="btn btn-outline-brand" title="Limpiar filtros"><i class="bi bi-x-circle"></i></a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Cliente</th>
                    <th>Especie</th>
                    <th>Cultivar</th>
                    <th>Fecha Emisión</th>
                    <th>Cargado por</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($certificados === []): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No hay certificados cargados aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($certificados as $certificado): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $certificado['id_certificado'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($certificado['tipo_certificado'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($certificado['nombre_cliente'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($certificado['nombre_especie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($certificado['nombre_cultivar'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($certificado['fecha_emision_certificado'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($certificado['nombre_usuario'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <a
                                    href="/index.php?pdf=<?php echo urlencode((string) $certificado['id_certificado']); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-sm btn-outline-light">
                                    <i class="bi bi-file-earmark-pdf"></i> PDF
                                </a>
                                <form method="post" action="<?php echo htmlspecialchars($certDeleteAction, ENT_QUOTES, 'UTF-8'); ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este certificado y su PDF? Esta acción no se puede deshacer.');">
                                    <input type="hidden" name="delete_certificado" value="1">
                                    <input type="hidden" name="certificado_delete_id" value="<?php echo (int) $certificado['id_certificado']; ?>">
                                    <input type="hidden" name="certificado_delete_token" value="<?php echo htmlspecialchars($_SESSION['certificado_delete_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Eliminar certificado <?php echo (int) $certificado['id_certificado']; ?>"><i class="bi bi-trash"></i> Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($certTotalPages > 1): ?>
        <nav class="mt-3" aria-label="Paginación de certificados">
            <ul class="pagination pagination-sm mb-0 flex-wrap">
                <?php for ($certPageNumber = 1; $certPageNumber <= $certTotalPages; $certPageNumber++): ?>
                    <?php
                    $certPageQuery = array_merge($_GET, ['page' => $page, 'cert_page' => $certPageNumber]);
                    $certPageUrl = '/index.php?' . http_build_query($certPageQuery) . '#certificados-listado';
                    ?>
                    <li class="page-item <?php echo $certPageNumber === $certPage ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo htmlspecialchars($certPageUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $certPageNumber; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</section>