<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/portal_clientes.php';
try {
    portal_actualizar_sesion();
} catch (PDOException $exception) {
    error_log('Error al validar acceso al portal: ' . $exception->getMessage());
    http_response_code(503);
    exit('No se pudo validar el acceso. Verifica la migración del portal de clientes.');
}
if (is_client_user() && !portal_solicitud_cliente_permitida($_SERVER['REQUEST_METHOD'], $_GET)) {
    http_response_code(403);
    exit('No tienes permiso para acceder a esta función.');
}
require __DIR__ . '/includes/format_helpers.php';
require __DIR__ . '/includes/certificado_settings.php';
require __DIR__ . '/includes/smtp_settings.php';
require __DIR__ . '/includes/certificado_pdf.php';
require __DIR__ . '/includes/navigation.php';
require __DIR__ . '/includes/anormalidades.php';
require __DIR__ . '/includes/boletin_peso_1000_semillas.php';
require __DIR__ . '/includes/boletin_peso_1000_semillas_pdf.php';
require __DIR__ . '/includes/boletin_tetrazolio.php';
require __DIR__ . '/includes/boletin_tetrazolio_pdf.php';
require __DIR__ . '/includes/boletin_tetrazolio_soja.php';
require __DIR__ . '/includes/boletin_tetrazolio_soja_pdf.php';
require __DIR__ . '/includes/boletin_pureza.php';
require __DIR__ . '/includes/boletin_pureza_pdf.php';
require __DIR__ . '/includes/boletin_malezas_pdf.php';
require __DIR__ . '/includes/boletin_germinacion.php';
require __DIR__ . '/includes/boletin_germinacion_pdf.php';
require __DIR__ . '/includes/libro_ingreso.php';
require __DIR__ . '/includes/presupuesto.php';
require __DIR__ . '/includes/libro_ingreso_certificado.php';
require __DIR__ . '/includes/agenda_conteos.php';

$errors = [];
$successMessage = null;
$newCertificadoId = null;

// Recuperar mensaje de éxito de la sesión (del redirect anterior)
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['new_certificado_id'])) {
    $newCertificadoId = $_SESSION['new_certificado_id'];
    unset($_SESSION['new_certificado_id']);
}
$newCertificadoClienteEmails = [];
if ($newCertificadoId !== null && is_logged_in()) {
    try {
        $certClienteStatement = db()->prepare('SELECT id_cliente_certificado FROM certificados WHERE id_certificado = :id AND id_empresa = :id_empresa LIMIT 1');
        $certClienteStatement->execute([':id' => $newCertificadoId, ':id_empresa' => current_empresa_id()]);
        $certClienteId = $certClienteStatement->fetchColumn();
        if ($certClienteId !== false && $certClienteId !== null) {
            $newCertificadoClienteEmails = get_cliente_emails((int) $certClienteId);
        }
    } catch (Throwable $throwable) {
        $newCertificadoClienteEmails = [];
    }
}
$clientForm = [
    'nombre_cliente' => '',
    'telefono_cliente' => '',
    'direccion_cliente' => '',
    'localidad_cliente' => '',
    'provincia_cliente' => '',
    'emails_cliente' => [''],
    'cuit_cliente' => '',
];
$clientEditId = null;
$speciesForm = [
    'nombre_especie' => '',
    'cientifico_especie' => '',
    'primer_conteo' => '',
    'conteo_final' => '',
];
$speciesEditId = null;
$determinacionLibroForm = ['nombre_determinacion_libro' => '', 'costo' => '0.00'];
$determinacionLibroEditId = null;
$anormalidadForm = ['codigo' => '', 'etiqueta' => '', 'grupo_tipo' => '', 'categoria_principal' => '', 'subcategoria' => ''];
$anormalidadEditId = null;
$cultivarForm = [
    'id_especie' => '',
    'nombre_cultivar' => '',
];
$cultivarEditId = null;
$detForm = [
    'nombre_determinacion' => '',
];
$detEditId = null;
$userForm = [
    'tipo_usuario' => 'interno',
    'id_cliente_usuario' => '',
    'nombre_usuario' => '',
    'username_usuario' => '',
    'email_usuario' => '',
    'activo_usuario' => '1',
];
$userEditId = null;
$certForm = [
    'tipo_certificado' => '',
    'id_cliente_certificado' => '',
    'id_especie_certificado' => '',
    'id_cultivar_certificado' => '',
    'marca_lote_certificado' => '',
    'peso_certificado' => '',
    'nro_envases_certificado' => '',
    'fecha_muestreo_certificado' => '',
    'precinto_certificado' => '',
    'fecha_muestras_certificado' => '',
    'fecha_finalizacion_ensayos_certificado' => '',
    'nro_analisis_certificado' => '',
    'pureza_semilla_pura_certificado' => '',
    'materia_inerte_certificado' => '',
    'pureza_otras_semillas_certificado' => '',
    'pg_numero_dias_certificado' => '',
    'pg_plantulas_normales_certificado' => '',
    'pg_semillas_duras_certificado' => '',
    'pg_semillas_frescas_certificado' => '',
    'pg_plantulas_anormales_certificado' => '',
    'pg_semillas_muertas_certificado' => '',
    'pg_contenido_humedad_certificado' => '',
    'pgc_numero_dias_certificado' => '',
    'pgc_plantulas_normales_certificado' => '',
    'pgc_semillas_duras_certificado' => '',
    'pgc_semillas_frescas_certificado' => '',
    'pgc_plantulas_anormales_certificado' => '',
    'pgc_semillas_muertas_certificado' => '',
    'vigor_nro_dias_certificado' => '',
    'vigor_plantulas_normales_certificado' => '',
    'id_otras_determinaciones_certificado' => [],
    'otras_determinaciones_texto_certificado' => '',
    'ampara_totalidad_lote_certificado' => 'N',
    'localidad_pais_certificado' => '',
    'fecha_emision_certificado' => '',
    'otras_referencias_certificado' => '',
    'hipoclorito_max_10_certificado' => '',
    'bioensayo_enlist_certificado' => '',
    'peroxidasa_positivas_certificado' => '',
    'peroxidasa_negativas_certificado' => '',
    'contam_hilo_cantidad_semillas_certificado' => '',
    'contam_hilo_color_contaminante_certificado' => '',
    'hipocotilo_variedad_certificado' => '',
    'hipocotilo_color_contaminante_certificado' => '',
    'clase_materia_inerte_certificado' => '',
    'otras_semillas_certificado' => '',
    'tetrazolio_detalle_certificado' => '',
    'tetrazolio_viable_sin_defectos_certificado' => '',
    'tetrazolio_viable_defectos_leves_certificado' => '',
    'tetrazolio_viable_defectos_moderados_certificado' => '',
    'tetrazolio_viable_defectos_severos_certificado' => '',
    'tetrazolio_no_viables_certificado' => '',
    'tetrazolio_vigor_alto_certificado' => '',
    'tetrazolio_vigor_medio_certificado' => '',
    'tetrazolio_vigor_bajo_certificado' => '',
    'tetrazolio_danos_mecanicos_certificado' => '',
    'tetrazolio_danos_ambientes_certificado' => '',
    'tetrazolio_danos_chinches_certificado' => '',
    'tetrazolio_danos_fracturas_certificado' => '',
    'tetrazolio_danos_otros_certificado' => '',
    'tetrazolio_duras_certificado' => '',
];
$certEditId = null;
$certFilter = [
    'id_cliente' => '',
    'id_especie' => '',
    'fecha_desde' => '',
    'fecha_hasta' => '',
];
$certPage = 1;
$certTotalPages = 1;
$certPerPage = 10;
$empresaForm = [
    'nombre_empresa' => '',
    'activo_empresa' => '1',
];
$empresaEditId = null;
$empresaAdminForm = [
    'nombre_usuario' => '',
    'username_usuario' => '',
    'email_usuario' => '',
];
$boletinPesoForm = [
    'id_cliente' => '',
    'id_especie' => '',
    'muestra_nro' => '',
    'fecha_boletin' => '',
    'analista' => '',
    'observaciones' => '',
    'pesos' => array_fill(0, 8, ''),
];
$boletinTetrazolioForm = [
    'id_cliente' => '',
    'id_especie' => '',
    'muestra_nro' => '',
    'fecha_inicio' => '',
    'fecha_finalizacion' => '',
    'analista' => '',
    'tiempo_humedecimiento' => '',
    'tiempo_tincion' => '',
    'temperatura_tincion' => '',
    'concentracion_tz' => '',
    'observaciones' => '',
    'repeticiones' => [
        1 => ['viables' => '', 'no_viables' => ''],
        2 => ['viables' => '', 'no_viables' => ''],
        3 => ['viables' => '', 'no_viables' => ''],
        4 => ['viables' => '', 'no_viables' => ''],
    ],
];
$boletinPurezaForm = [
    'id_cliente' => '',
    'id_especie' => '',
    'analisis_nro' => '',
    'fecha_analisis' => '',
    'analista' => '',
    'peso_inicial' => '',
    'peso_semilla_pura' => '',
    'observaciones' => '',
    'id_otras_determinaciones' => [],
    'otras_determinaciones_texto' => '',
    'componentes_materia_inerte' => [['nombre' => '', 'peso' => '']],
    'componentes_otras_semillas' => [['nombre' => '', 'peso' => '']],
];
$boletinMalezasForm = [
    'id_cliente' => '',
    'id_especie' => '',
    'analisis_nro' => '',
    'muestra_nro' => '',
    'especie_remitente' => '',
    'especie_latin' => '',
    'lote' => '',
    'tipo_analisis' => 'completo',
    'peso_analisis' => '',
    'fecha_analisis' => '',
    'analista' => '',
    'id_otras_determinaciones' => [],
    'otras_determinaciones_texto' => '',
    'observaciones' => '',
    'especies_halladas' => [['especie_latin' => '', 'numero_semillas' => '']],
];
$boletinGerminacionForm = [
    'id_cliente' => '',
    'id_especie' => '',
    'analisis_nro' => '',
    'muestra_nro' => '',
    'fecha_siembra' => '',
    'fecha_primer_conteo' => '',
    'fecha_segundo_conteo' => '',
    'analista' => '',
    'pretratamiento_desde' => '',
    'pretratamiento_hasta' => '',
    'pretratamiento_total_dias' => '',
    'pretratamiento_aplica' => '',
    'pretratamiento_frio' => false,
    'pretratamiento_nitrato_k' => false,
    'pretratamiento_presecado' => false,
    'pretratamiento_prelavado' => false,
    'sustrato_tipo' => '',
    'sustrato_otro' => '',
    'temperatura_camara_tipo' => '',
    'temperatura_camara_otra' => '',
    'observaciones' => '',
    'id_anormalidades' => [],
    'repeticiones' => [
        1 => ['semillas_sembradas' => '', 'primer_conteo_normales' => '', 'primer_conteo_anormales' => '', 'primer_conteo_duras' => '', 'primer_conteo_frescas' => '', 'primer_conteo_muertas' => '', 'segundo_conteo_normales' => '', 'segundo_conteo_anormales' => '', 'segundo_conteo_duras' => '', 'segundo_conteo_frescas' => '', 'segundo_conteo_muertas' => ''],
        2 => ['semillas_sembradas' => '', 'primer_conteo_normales' => '', 'primer_conteo_anormales' => '', 'primer_conteo_duras' => '', 'primer_conteo_frescas' => '', 'primer_conteo_muertas' => '', 'segundo_conteo_normales' => '', 'segundo_conteo_anormales' => '', 'segundo_conteo_duras' => '', 'segundo_conteo_frescas' => '', 'segundo_conteo_muertas' => ''],
        3 => ['semillas_sembradas' => '', 'primer_conteo_normales' => '', 'primer_conteo_anormales' => '', 'primer_conteo_duras' => '', 'primer_conteo_frescas' => '', 'primer_conteo_muertas' => '', 'segundo_conteo_normales' => '', 'segundo_conteo_anormales' => '', 'segundo_conteo_duras' => '', 'segundo_conteo_frescas' => '', 'segundo_conteo_muertas' => ''],
        4 => ['semillas_sembradas' => '', 'primer_conteo_normales' => '', 'primer_conteo_anormales' => '', 'primer_conteo_duras' => '', 'primer_conteo_frescas' => '', 'primer_conteo_muertas' => '', 'segundo_conteo_normales' => '', 'segundo_conteo_anormales' => '', 'segundo_conteo_duras' => '', 'segundo_conteo_frescas' => '', 'segundo_conteo_muertas' => ''],
    ],
];
$boletinGerminacionEditId = null;
$boletinTetrazolioSojaForm = boletin_tetrazolio_soja_form_vacio();
$libroIngresoForm = [
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
];

require __DIR__ . '/handlers/auth.php';
require __DIR__ . '/handlers/portal_clientes.php';
require __DIR__ . '/handlers/configuracion.php';
require __DIR__ . '/handlers/anormalidades.php';

require __DIR__ . '/handlers/empresas.php';

require __DIR__ . '/handlers/clientes.php';

require __DIR__ . '/handlers/especies.php';

require __DIR__ . '/handlers/determinaciones_libro_ingreso.php';

require __DIR__ . '/handlers/cultivares.php';

require __DIR__ . '/handlers/determinaciones.php';

require __DIR__ . '/handlers/usuarios.php';

require __DIR__ . '/handlers/certificados.php';
require __DIR__ . '/handlers/boletin_peso_1000_semillas.php';
require __DIR__ . '/handlers/boletin_tetrazolio.php';
require __DIR__ . '/handlers/boletin_tetrazolio_soja.php';
require __DIR__ . '/handlers/boletin_pureza.php';
require __DIR__ . '/handlers/boletin_malezas.php';
require __DIR__ . '/handlers/boletin_germinacion.php';
require __DIR__ . '/handlers/libro_ingreso.php';
require __DIR__ . '/handlers/presupuesto.php';
require __DIR__ . '/handlers/libro_ingreso_boletin.php';
require __DIR__ . '/handlers/libro_ingreso_certificado.php';
require __DIR__ . '/handlers/agenda_conteos.php';

if (!is_logged_in()) {
    $isLoginPage = true;
} else {
    $isLoginPage = false;
    $page = (string) ($_GET['page'] ?? 'dashboard');
    if (is_client_user()) {
        $page = 'portal_clientes';
    }
    if (!array_key_exists($page, $pageMap)) {
        $page = 'dashboard';
    }
    if ($page === 'empresas' && !is_superadmin()) {
        $page = 'dashboard';
    }
    $currentPage = $pageMap[$page];
}

$loggedUser = $_SESSION['user']['name'] ?? 'Usuario';
$laboratorySettings = is_logged_in() ? get_certificate_settings(current_empresa_id()) : [];
$laboratoryName = trim((string) ($laboratorySettings['nombre_laboratorio'] ?? ''));
if ($laboratoryName === '') {
    $laboratoryName = trim((string) ($_SESSION['user']['empresa_nombre'] ?? ''));
}
if ($laboratoryName === '') {
    $laboratoryName = APP_SUBTITLE;
}
if (($laboratorySettings['nombre_laboratorio'] ?? '') === '') {
    $laboratorySettings['nombre_laboratorio'] = $laboratoryName;
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/assets/css/app.css?v=<?php echo @filemtime(__DIR__ . '/assets/css/app.css'); ?>" rel="stylesheet">
</head>

<body>
    <?php if ($isLoginPage): ?>
        <main class="login-shell">
            <div class="login-panel">
                <h1 class="login-heading">Acceso al sistema</h1>
                <p class="login-copy">Inicia sesión para acceder al laboratorio o consultar tus documentos.</p>

                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger mb-3"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>

                <form method="post" class="login-form">
                    <input type="hidden" name="login" value="1">
                    <div class="mb-3">
                        <label for="username" class="form-label">Usuario</label>
                        <input type="text" class="form-control form-control-lg" id="username" name="username" autocomplete="username" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" autocomplete="current-password" required>
                    </div>
                    <button class="btn btn-brand w-100 btn-lg" type="submit">Ingresar</button>
                </form>

                <div class="login-note">
                    <strong>Demo:</strong> admin / admin123
                </div>
            </div>
        </main>
    <?php else: ?>
        <div class="app-shell">
            <aside class="sidebar collapse d-lg-flex flex-column" id="appSidebar">
                <div class="sidebar-brand">
                    <div>
                        <div class="sidebar-brand__title"><?php echo htmlspecialchars(APP_BRAND, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="sidebar-brand__subtitle"><?php echo htmlspecialchars($laboratoryName, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>

                <nav class="sidebar-nav flex-grow-1">
                    <?php if (!is_client_user()): ?>
                        <a class="nav-link <?php echo is_active_page('dashboard', $page); ?>" href="/index.php?page=dashboard"><i class="bi bi-speedometer2"></i> Inicio</a>
                    <?php endif; ?>

                    <?php foreach ($navigation as $groupKey => $group): ?>
                        <?php
                        $collapseId = 'group-' . $groupKey;
                        ?>
                        <div class="nav-group">
                            <button
                                class="nav-group__toggle"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#<?php echo htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8'); ?>"
                                aria-controls="<?php echo htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8'); ?>"
                                aria-expanded="false">
                                <span><?php echo htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="collapse" id="<?php echo htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="nav-group__items">
                                    <?php foreach ($group['items'] as $key => $label): ?>
                                        <a class="nav-link <?php echo is_active_page($key, $page); ?>" href="/index.php?page=<?php echo urlencode($key); ?>">
                                            <i class="bi bi-chevron-right"></i> <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </nav>

                <div class="sidebar-footer">
                    <div class="small text-uppercase text-muted">Sesión activa</div>
                    <div class="fw-semibold"><?php echo htmlspecialchars($loggedUser, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="small text-muted"><?php echo htmlspecialchars((string) ($_SESSION['user']['empresa_nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    <a class="btn btn-outline-brand btn-sm mt-3 w-100" href="/logout.php">Cerrar sesión</a>
                </div>
            </aside>

            <div class="main-area">
                <header class="topbar">
                    <button class="btn btn-brand-outline d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#appSidebar" aria-controls="appSidebar" aria-expanded="false">
                        <i class="bi bi-list"></i>
                    </button>
                    <div>
                        <div class="topbar-kicker"><?php echo htmlspecialchars(APP_BRAND, ENT_QUOTES, 'UTF-8'); ?></div>
                        <h1 class="topbar-title"><?php echo htmlspecialchars($currentPage['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                        <p class="topbar-subtitle"><?php echo htmlspecialchars($currentPage['subtitle'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </header>

                <main class="content-area">
                    <?php
                    $pageTemplate = $pageTemplates[$page] ?? null;
                    if ($pageTemplate !== null && is_file(__DIR__ . '/pages/' . $pageTemplate)) {
                        require __DIR__ . '/pages/' . $pageTemplate;
                    } else {
                    ?>
                        <section class="section-panel">
                            <h2><?php echo htmlspecialchars($currentPage['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p>Sección inicial preparada para implementar formularios, listados y flujos de trabajo.</p>
                            <div class="empty-state">
                                <i class="bi bi-folder2-open"></i>
                                <span>Contenido pendiente de desarrollo</span>
                            </div>
                        </section>
                    <?php
                    }
                    ?>
                </main>
            </div>
        </div>


        <div class="modal fade" id="certificadoEmailModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-dark text-light">
                    <div class="modal-header">
                        <h5 class="modal-title">Enviar certificado por email</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p>El certificado se descargó automáticamente. ¿Querés además enviarlo por email al cliente?</p>
                        <div id="certificadoEmailList" class="mb-3"></div>
                        <div id="certificadoEmailFeedback"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-brand" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-brand" id="certificadoEmailSendBtn">Enviar</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterCultivares() {
            const especieSelect = document.getElementById('id_especie_certificado');
            const cultivarSelect = document.getElementById('id_cultivar_certificado');
            if (!especieSelect || !cultivarSelect) return;
            const especieValue = especieSelect.value;

            const options = cultivarSelect.querySelectorAll('option');
            options.forEach(option => {
                // Mostrar opción vacía siempre
                if (option.value === '') {
                    option.style.display = 'block';
                } else {
                    const cultivarEspecie = option.dataset.especie;
                    // Mostrar cultivar si coincide con la especie seleccionada
                    option.style.display = (cultivarEspecie === especieValue) ? 'block' : 'none';
                }
            });

            // Resetear el select al cambiar de especie
            if (!cultivarSelect.selectedOptions[0] || cultivarSelect.selectedOptions[0].dataset.especie !== especieValue) {
                cultivarSelect.value = '';
            }
        };

        // Filter on page load
        document.addEventListener('DOMContentLoaded', filterCultivares);

        // Variante genérica: la especie declara su select de cultivares con data-cultivar-target
        function filterCultivaresTarget(especieSelect) {
            const cultivarSelect = document.getElementById(especieSelect.dataset.cultivarTarget);
            if (!cultivarSelect) return;
            const especieValue = especieSelect.value;
            let seleccionVisible = false;

            cultivarSelect.querySelectorAll('option').forEach(option => {
                const visible = option.value === '' || option.dataset.especie === especieValue;
                option.style.display = visible ? 'block' : 'none';
                if (visible && option.value !== '' && option.value === cultivarSelect.value) {
                    seleccionVisible = true;
                }
            });

            if (!seleccionVisible) {
                cultivarSelect.value = '';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('select[data-cultivar-target]').forEach(filterCultivaresTarget);
        });

        function syncScientificName(speciesSelect) {
            const scientificField = document.getElementById(speciesSelect.dataset.scientificTarget);
            if (!scientificField) return;

            scientificField.value = speciesSelect.selectedOptions[0]?.dataset.scientific || '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-scientific-target]').forEach(syncScientificName);
        });
        document.addEventListener('change', function(event) {
            if (event.target.matches('[data-scientific-target]')) {
                syncScientificName(event.target);
            }
        });

        function initClienteEmails() {
            const emailList = document.getElementById('cliente-emails-list');
            const addButton = document.getElementById('cliente-email-add');
            if (!emailList || !addButton) return;

            const addEmailRow = (value = '') => {
                const row = document.createElement('div');
                row.className = 'input-group mb-2 cliente-email-row';
                row.innerHTML = '<input type="email" class="form-control" name="emails_cliente[]" maxlength="150" placeholder="email@dominio.com">' +
                    '<button type="button" class="btn btn-outline-danger cliente-email-remove">Quitar</button>';
                row.querySelector('input').value = value;
                emailList.appendChild(row);
            };

            addButton.addEventListener('click', function() {
                addEmailRow('');
            });

            emailList.addEventListener('click', function(event) {
                const button = event.target.closest('.cliente-email-remove');
                if (!button) return;

                const row = button.closest('.cliente-email-row');
                if (!row) return;

                if (emailList.querySelectorAll('.cliente-email-row').length <= 1) {
                    const input = row.querySelector('input');
                    if (input) input.value = '';
                    return;
                }

                row.remove();
            });

            const rows = emailList.querySelectorAll('.cliente-email-row');
            if (rows.length === 0) {
                addEmailRow('');
            }
        }

        document.addEventListener('DOMContentLoaded', initClienteEmails);

        // Prevenir que Enter guarde el formulario
        document.addEventListener('DOMContentLoaded', function() {
            const certForm = document.querySelector('form[action*="Certificadogeneral"]') || document.querySelector('form');
            if (certForm) {
                certForm.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                });
            }
        });
    </script>
</body>

</html>