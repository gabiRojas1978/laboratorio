<?php

declare(strict_types=1);

require __DIR__ . '/config/config.php';

function format_date_short(?string $date): string
{
    if ($date === null || $date === '') {
        return '----------------';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '----------------';
    }

    return date('d/m/Y', $timestamp);
}

function format_date_long_spanish(?string $date): string
{
    if ($date === null || $date === '') {
        return '----------------';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '----------------';
    }

    $months = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre',
    ];

    $day = date('j', $timestamp);
    $month = $months[(int) date('n', $timestamp)] ?? '';
    $year = date('Y', $timestamp);

    return $day . ' de ' . $month . ' de ' . $year;
}

function format_percent_value($value): string
{
    if ($value === null) {
        return 'N';
    }

    $raw = trim((string) $value);
    if ($raw === '' || !is_numeric($raw)) {
        return 'N';
    }

    $number = (float) $raw;
    if ($number === 0.0) {
        return '0';
    }

    return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
}

function format_text_value($value, string $fallback = 'N'): string
{
    $text = trim((string) ($value ?? ''));
    return $text !== '' ? $text : $fallback;
}

$navigation = [
    'administracion' => [
        'label' => 'Administración',
        'items' => [
            'clientes' => 'Clientes',
            'especies' => 'Especies',
            'cultivares' => 'Cultivares',
            'otras_determinaciones' => 'Otras Determinaciones',
            'usuarios' => 'Usuarios',
            'configuracion' => 'Configuración',
        ],
    ],
    'certificados' => [
        'label' => 'Certificados',
        'items' => [
            'Certificadogeneral' => 'Certificado general',
        ],
    ],
];

if (is_superadmin()) {
    $navigation['superadmin'] = [
        'label' => 'Superadmin',
        'items' => [
            'empresas' => 'Empresas',
        ],
    ];
}

$pageMap = [
    'dashboard' => ['title' => 'Inicio', 'subtitle' => 'Resumen general del sistema'],
    'clientes' => ['title' => 'Clientes', 'subtitle' => 'Gestión de clientes'],
    'especies' => ['title' => 'Especies', 'subtitle' => 'Catálogo de especies'],
    'cultivares' => ['title' => 'Cultivares', 'subtitle' => 'Registro de cultivares'],
    'otras_determinaciones' => ['title' => 'Otras Determinaciones', 'subtitle' => 'Parámetros auxiliares'],
    'usuarios' => ['title' => 'Usuarios', 'subtitle' => 'Gestión de usuarios del sistema'],
    'configuracion' => ['title' => 'Configuración', 'subtitle' => 'Preferencias del sistema'],
    'Certificadogeneral' => ['title' => 'Certificado general', 'subtitle' => 'Emisión de certificados'],
    'empresas' => ['title' => 'Empresas', 'subtitle' => 'Alta y administración de laboratorios (tenants)'],
];

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
$clientForm = [
    'nombre_cliente' => '',
    'telefono_cliente' => '',
    'direccion_cliente' => '',
    'mail_cliente' => '',
    'cuit_cliente' => '',
];
$clientEditId = null;
$speciesForm = [
    'nombre_especie' => '',
    'cientifico_especie' => '',
];
$speciesEditId = null;
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
    'clase_materia_inerte_certificado' => '',
    'otras_semillas_certificado' => '',
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $userStatement = db()->prepare(
        'SELECT u.id_usuario, u.nombre_usuario, u.username_usuario, u.password_hash_usuario, u.activo_usuario, u.es_superadmin, u.id_empresa, e.activo_empresa, e.nombre_empresa
        FROM usuarios u
        INNER JOIN empresas e ON e.id_empresa = u.id_empresa
        WHERE u.username_usuario = :username LIMIT 1'
    );
    $userStatement->execute([':username' => $username]);
    $userRow = $userStatement->fetch();

    if (
        $userRow !== false
        && (int) $userRow['activo_usuario'] === 1
        && (int) $userRow['activo_empresa'] === 1
        && password_verify($password, $userRow['password_hash_usuario'])
    ) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $userRow['id_usuario'],
            'username' => $userRow['username_usuario'],
            'name' => $userRow['nombre_usuario'],
            'id_empresa' => (int) $userRow['id_empresa'],
            'empresa_nombre' => $userRow['nombre_empresa'],
            'es_superadmin' => (bool) $userRow['es_superadmin'],
        ];
        header('Location: /index.php');
        exit;
    }

    $errors[] = 'Usuario o contraseña incorrectos.';
}

if (is_superadmin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_empresa'])) {
    $empresaForm['nombre_empresa'] = trim((string) ($_POST['nombre_empresa'] ?? ''));
    $empresaAdminForm['nombre_usuario'] = trim((string) ($_POST['admin_nombre_usuario'] ?? ''));
    $empresaAdminForm['username_usuario'] = trim((string) ($_POST['admin_username_usuario'] ?? ''));
    $empresaAdminForm['email_usuario'] = trim((string) ($_POST['admin_email_usuario'] ?? ''));
    $empresaAdminPassword = (string) ($_POST['admin_password_usuario'] ?? '');

    if ($empresaForm['nombre_empresa'] === '') {
        $errors[] = 'El nombre de la empresa es obligatorio.';
    }

    if ($empresaAdminForm['nombre_usuario'] === '') {
        $errors[] = 'El nombre del usuario administrador es obligatorio.';
    }

    if (!preg_match('/^[a-zA-Z0-9._-]{3,60}$/', $empresaAdminForm['username_usuario'])) {
        $errors[] = 'El usuario administrador debe tener entre 3 y 60 caracteres (letras, números, puntos, guiones).';
    }

    if ($empresaAdminForm['email_usuario'] !== '' && !filter_var($empresaAdminForm['email_usuario'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email ingresado no es válido.';
    }

    if ($empresaAdminPassword === '' || strlen($empresaAdminPassword) < 6) {
        $errors[] = 'La contraseña del administrador es obligatoria y debe tener al menos 6 caracteres.';
    }

    if (!$errors) {
        try {
            db()->beginTransaction();

            $empresaStatement = db()->prepare('INSERT INTO empresas (nombre_empresa) VALUES (:nombre_empresa)');
            $empresaStatement->execute([':nombre_empresa' => $empresaForm['nombre_empresa']]);
            $newEmpresaId = (int) db()->lastInsertId();

            $adminStatement = db()->prepare(
                'INSERT INTO usuarios (id_empresa, nombre_usuario, username_usuario, email_usuario, password_hash_usuario, activo_usuario, es_superadmin) VALUES (:id_empresa, :nombre_usuario, :username_usuario, :email_usuario, :password_hash_usuario, 1, 0)'
            );
            $adminStatement->execute([
                ':id_empresa' => $newEmpresaId,
                ':nombre_usuario' => $empresaAdminForm['nombre_usuario'],
                ':username_usuario' => $empresaAdminForm['username_usuario'],
                ':email_usuario' => $empresaAdminForm['email_usuario'] !== '' ? $empresaAdminForm['email_usuario'] : null,
                ':password_hash_usuario' => password_hash($empresaAdminPassword, PASSWORD_BCRYPT),
            ]);

            db()->commit();

            $successMessage = 'Empresa y usuario administrador dados de alta correctamente.';
            $_SESSION['success_message'] = $successMessage;

            header('Location: /index.php?page=empresas');
            exit;
        } catch (Throwable $throwable) {
            db()->rollBack();
            if ((int) $throwable->getCode() === 23000) {
                $errors[] = 'Ya existe un usuario con ese nombre de usuario.';
            } else {
                $errors[] = 'No fue posible dar de alta la empresa.';
            }
        }
    }
}

if (is_superadmin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_empresa'])) {
    $empresaEditId = filter_var($_POST['empresa_edit_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $empresaForm['nombre_empresa'] = trim((string) ($_POST['nombre_empresa'] ?? ''));
    $empresaForm['activo_empresa'] = isset($_POST['activo_empresa']) ? '1' : '0';

    if ($empresaEditId === false) {
        $errors[] = 'La empresa a editar no es válida.';
    }

    if ($empresaForm['nombre_empresa'] === '') {
        $errors[] = 'El nombre de la empresa es obligatorio.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare('UPDATE empresas SET nombre_empresa = :nombre_empresa, activo_empresa = :activo_empresa WHERE id_empresa = :id_empresa');
            $statement->execute([
                ':nombre_empresa' => $empresaForm['nombre_empresa'],
                ':activo_empresa' => $empresaForm['activo_empresa'],
                ':id_empresa' => $empresaEditId,
            ]);

            $successMessage = 'Empresa actualizada correctamente.';
            $_SESSION['success_message'] = $successMessage;
            header('Location: /index.php?page=empresas');
            exit;
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible actualizar la empresa.';
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cliente'])) {
    $clientForm['nombre_cliente'] = trim((string) ($_POST['nombre_cliente'] ?? ''));
    $clientForm['telefono_cliente'] = trim((string) ($_POST['telefono_cliente'] ?? ''));
    $clientForm['direccion_cliente'] = trim((string) ($_POST['direccion_cliente'] ?? ''));
    $clientForm['mail_cliente'] = trim((string) ($_POST['mail_cliente'] ?? ''));
    $clientForm['cuit_cliente'] = trim((string) ($_POST['cuit_cliente'] ?? ''));

    if ($clientForm['nombre_cliente'] === '') {
        $errors[] = 'El nombre del cliente es obligatorio.';
    }

    if ($clientForm['cuit_cliente'] !== '' && !preg_match('/^[0-9\-]{7,13}$/', $clientForm['cuit_cliente'])) {
        $errors[] = 'El CUIT ingresado no tiene un formato válido.';
    }

    if ($clientForm['mail_cliente'] !== '' && !filter_var($clientForm['mail_cliente'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email ingresado no es válido.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'INSERT INTO clientes (id_empresa, nombre_cliente, telefono_cliente, direccion_cliente, mail_cliente, cuit_cliente) VALUES (:id_empresa, :nombre_cliente, :telefono_cliente, :direccion_cliente, :mail_cliente, :cuit_cliente)'
            );
            $statement->execute([
                ':id_empresa' => current_empresa_id(),
                ':nombre_cliente' => $clientForm['nombre_cliente'],
                ':telefono_cliente' => $clientForm['telefono_cliente'] !== '' ? $clientForm['telefono_cliente'] : null,
                ':direccion_cliente' => $clientForm['direccion_cliente'] !== '' ? $clientForm['direccion_cliente'] : null,
                ':mail_cliente' => $clientForm['mail_cliente'] !== '' ? $clientForm['mail_cliente'] : null,
                ':cuit_cliente' => $clientForm['cuit_cliente'] !== '' ? $clientForm['cuit_cliente'] : null,
            ]);

            $successMessage = 'Cliente dado de alta correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $clientForm = [
                'nombre_cliente' => '',
                'telefono_cliente' => '',
                'direccion_cliente' => '',
                'mail_cliente' => '',
                'cuit_cliente' => '',
            ];

            if (($page ?? 'dashboard') !== 'clientes') {
                header('Location: /index.php?page=clientes');
                exit;
            }
        } catch (Throwable $throwable) {
            if ((int) $throwable->getCode() === 23000) {
                $errors[] = 'Ya existe un cliente con ese CUIT.';
            } else {
                $errors[] = 'No fue posible guardar el cliente.';
            }
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cliente'])) {
    $clientEditId = filter_var($_POST['cliente_edit_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $clientForm['nombre_cliente'] = trim((string) ($_POST['nombre_cliente'] ?? ''));
    $clientForm['telefono_cliente'] = trim((string) ($_POST['telefono_cliente'] ?? ''));
    $clientForm['direccion_cliente'] = trim((string) ($_POST['direccion_cliente'] ?? ''));
    $clientForm['mail_cliente'] = trim((string) ($_POST['mail_cliente'] ?? ''));
    $clientForm['cuit_cliente'] = trim((string) ($_POST['cuit_cliente'] ?? ''));

    if ($clientEditId === false) {
        $errors[] = 'El cliente a editar no es válido.';
    }

    if ($clientForm['nombre_cliente'] === '') {
        $errors[] = 'El nombre del cliente es obligatorio.';
    }

    if ($clientForm['cuit_cliente'] !== '' && !preg_match('/^[0-9\-]{7,13}$/', $clientForm['cuit_cliente'])) {
        $errors[] = 'El CUIT ingresado no tiene un formato válido.';
    }

    if ($clientForm['mail_cliente'] !== '' && !filter_var($clientForm['mail_cliente'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email ingresado no es válido.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'UPDATE clientes SET nombre_cliente = :nombre_cliente, telefono_cliente = :telefono_cliente, direccion_cliente = :direccion_cliente, mail_cliente = :mail_cliente, cuit_cliente = :cuit_cliente WHERE id_cliente = :id_cliente AND id_empresa = :id_empresa'
            );
            $statement->execute([
                ':nombre_cliente' => $clientForm['nombre_cliente'],
                ':telefono_cliente' => $clientForm['telefono_cliente'] !== '' ? $clientForm['telefono_cliente'] : null,
                ':direccion_cliente' => $clientForm['direccion_cliente'] !== '' ? $clientForm['direccion_cliente'] : null,
                ':mail_cliente' => $clientForm['mail_cliente'] !== '' ? $clientForm['mail_cliente'] : null,
                ':cuit_cliente' => $clientForm['cuit_cliente'] !== '' ? $clientForm['cuit_cliente'] : null,
                ':id_cliente' => $clientEditId,
                ':id_empresa' => current_empresa_id(),
            ]);

            $successMessage = 'Cliente actualizado correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $clientEditId = null;
            $clientForm = ['nombre_cliente' => '', 'telefono_cliente' => '', 'direccion_cliente' => '', 'mail_cliente' => '', 'cuit_cliente' => ''];

            if (($page ?? 'dashboard') !== 'clientes') {
                header('Location: /index.php?page=clientes');
                exit;
            }
        } catch (Throwable $throwable) {
            if ((int) $throwable->getCode() === 23000) {
                $errors[] = 'Ya existe un cliente con ese CUIT.';
            } else {
                $errors[] = 'No fue posible actualizar el cliente.';
            }
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_especie'])) {
    $speciesForm['nombre_especie'] = trim((string) ($_POST['nombre_especie'] ?? ''));
    $speciesForm['cientifico_especie'] = trim((string) ($_POST['cientifico_especie'] ?? ''));

    if ($speciesForm['nombre_especie'] === '') {
        $errors[] = 'El nombre de la especie es obligatorio.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'INSERT INTO especies (id_empresa, nombre_especie, cientifico_especie) VALUES (:id_empresa, :nombre_especie, :cientifico_especie)'
            );
            $statement->execute([
                ':id_empresa' => current_empresa_id(),
                ':nombre_especie' => $speciesForm['nombre_especie'],
                ':cientifico_especie' => $speciesForm['cientifico_especie'] !== '' ? $speciesForm['cientifico_especie'] : null,
            ]);

            $successMessage = 'Especie dada de alta correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $speciesForm = [
                'nombre_especie' => '',
                'cientifico_especie' => '',
            ];

            if (($page ?? 'dashboard') !== 'especies') {
                header('Location: /index.php?page=especies');
                exit;
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible guardar la especie.';
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_especie'])) {
    $speciesEditId = filter_var($_POST['especie_edit_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $speciesForm['nombre_especie'] = trim((string) ($_POST['nombre_especie'] ?? ''));
    $speciesForm['cientifico_especie'] = trim((string) ($_POST['cientifico_especie'] ?? ''));

    if ($speciesEditId === false) {
        $errors[] = 'La especie a editar no es válida.';
    }

    if ($speciesForm['nombre_especie'] === '') {
        $errors[] = 'El nombre de la especie es obligatorio.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'UPDATE especies SET nombre_especie = :nombre_especie, cientifico_especie = :cientifico_especie WHERE id_especie = :id_especie AND id_empresa = :id_empresa'
            );
            $statement->execute([
                ':nombre_especie' => $speciesForm['nombre_especie'],
                ':cientifico_especie' => $speciesForm['cientifico_especie'] !== '' ? $speciesForm['cientifico_especie'] : null,
                ':id_especie' => $speciesEditId,
                ':id_empresa' => current_empresa_id(),
            ]);

            $successMessage = 'Especie actualizada correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $speciesEditId = null;
            $speciesForm = ['nombre_especie' => '', 'cientifico_especie' => ''];

            if (($page ?? 'dashboard') !== 'especies') {
                header('Location: /index.php?page=especies');
                exit;
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible actualizar la especie.';
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cultivar'])) {
    $cultivarForm['id_especie'] = trim((string) ($_POST['id_especie'] ?? ''));
    $cultivarForm['nombre_cultivar'] = trim((string) ($_POST['nombre_cultivar'] ?? ''));

    $speciesId = filter_var($cultivarForm['id_especie'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($speciesId === false) {
        $errors[] = 'Debes seleccionar una especie válida.';
    }

    if ($cultivarForm['nombre_cultivar'] === '') {
        $errors[] = 'El nombre del cultivar es obligatorio.';
    }

    if (!$errors) {
        try {
            $speciesStatement = db()->prepare('SELECT id_especie FROM especies WHERE id_especie = :id_especie AND id_empresa = :id_empresa LIMIT 1');
            $speciesStatement->execute([':id_especie' => $speciesId, ':id_empresa' => current_empresa_id()]);

            if ($speciesStatement->fetch() === false) {
                $errors[] = 'La especie seleccionada no existe.';
            } else {
                $statement = db()->prepare(
                    'INSERT INTO cultivares (id_empresa, id_especie, nombre_cultivar) VALUES (:id_empresa, :id_especie, :nombre_cultivar)'
                );
                $statement->execute([
                    ':id_empresa' => current_empresa_id(),
                    ':id_especie' => $speciesId,
                    ':nombre_cultivar' => $cultivarForm['nombre_cultivar'],
                ]);

                $successMessage = 'Cultivar dado de alta correctamente.';
                $_SESSION['success_message'] = $successMessage;
                $cultivarForm = [
                    'id_especie' => '',
                    'nombre_cultivar' => '',
                ];

                if (($page ?? 'dashboard') !== 'cultivares') {
                    header('Location: /index.php?page=cultivares');
                    exit;
                }
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible guardar el cultivar.';
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cultivar'])) {
    $cultivarEditId = filter_var($_POST['cultivar_edit_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $cultivarForm['id_especie'] = trim((string) ($_POST['id_especie'] ?? ''));
    $cultivarForm['nombre_cultivar'] = trim((string) ($_POST['nombre_cultivar'] ?? ''));

    $speciesId = filter_var($cultivarForm['id_especie'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($cultivarEditId === false) {
        $errors[] = 'El cultivar a editar no es válido.';
    }

    if ($speciesId === false) {
        $errors[] = 'Debes seleccionar una especie válida.';
    }

    if ($cultivarForm['nombre_cultivar'] === '') {
        $errors[] = 'El nombre del cultivar es obligatorio.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'UPDATE cultivares SET id_especie = :id_especie, nombre_cultivar = :nombre_cultivar WHERE id_cultivar = :id_cultivar AND id_empresa = :id_empresa'
            );
            $statement->execute([
                ':id_especie' => $speciesId,
                ':nombre_cultivar' => $cultivarForm['nombre_cultivar'],
                ':id_cultivar' => $cultivarEditId,
                ':id_empresa' => current_empresa_id(),
            ]);

            $successMessage = 'Cultivar actualizado correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $cultivarEditId = null;
            $cultivarForm = ['id_especie' => '', 'nombre_cultivar' => ''];

            if (($page ?? 'dashboard') !== 'cultivares') {
                header('Location: /index.php?page=cultivares');
                exit;
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible actualizar el cultivar.';
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_cultivar'])) {
    $deleteId = filter_var($_POST['cultivar_delete_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($deleteId === false) {
        $errors[] = 'El cultivar a eliminar no es válido.';
    } else {
        try {
            $statement = db()->prepare('DELETE FROM cultivares WHERE id_cultivar = :id_cultivar AND id_empresa = :id_empresa');
            $statement->execute([':id_cultivar' => $deleteId, ':id_empresa' => current_empresa_id()]);
            $successMessage = 'Cultivar eliminado correctamente.';
            $_SESSION['success_message'] = $successMessage;

            if (($page ?? 'dashboard') !== 'cultivares') {
                header('Location: /index.php?page=cultivares');
                exit;
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible eliminar el cultivar.';
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_determinacion'])) {
    $detForm['nombre_determinacion'] = trim((string) ($_POST['nombre_determinacion'] ?? ''));

    if ($detForm['nombre_determinacion'] === '') {
        $errors[] = 'El nombre de la determinación es obligatorio.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'INSERT INTO otras_determinaciones (id_empresa, nombre_determinacion) VALUES (:id_empresa, :nombre_determinacion)'
            );
            $statement->execute([':id_empresa' => current_empresa_id(), ':nombre_determinacion' => $detForm['nombre_determinacion']]);

            $successMessage = 'Determinación dada de alta correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $detForm = ['nombre_determinacion' => ''];

            if (($page ?? 'dashboard') !== 'otras_determinaciones') {
                header('Location: /index.php?page=otras_determinaciones');
                exit;
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible guardar la determinación.';
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_determinacion'])) {
    $detEditId = filter_var($_POST['det_edit_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $detForm['nombre_determinacion'] = trim((string) ($_POST['nombre_determinacion'] ?? ''));

    if ($detEditId === false) {
        $errors[] = 'La determinación a editar no es válida.';
    }

    if ($detForm['nombre_determinacion'] === '') {
        $errors[] = 'El nombre de la determinación es obligatorio.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'UPDATE otras_determinaciones SET nombre_determinacion = :nombre_determinacion WHERE id_determinacion = :id_determinacion AND id_empresa = :id_empresa'
            );
            $statement->execute([
                ':nombre_determinacion' => $detForm['nombre_determinacion'],
                ':id_determinacion' => $detEditId,
                ':id_empresa' => current_empresa_id(),
            ]);

            $successMessage = 'Determinación actualizada correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $detEditId = null;
            $detForm = ['nombre_determinacion' => ''];

            if (($page ?? 'dashboard') !== 'otras_determinaciones') {
                header('Location: /index.php?page=otras_determinaciones');
                exit;
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible actualizar la determinación.';
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_usuario'])) {
    $userForm['nombre_usuario'] = trim((string) ($_POST['nombre_usuario'] ?? ''));
    $userForm['username_usuario'] = trim((string) ($_POST['username_usuario'] ?? ''));
    $userForm['email_usuario'] = trim((string) ($_POST['email_usuario'] ?? ''));
    $userForm['activo_usuario'] = isset($_POST['activo_usuario']) ? '1' : '0';
    $userPassword = (string) ($_POST['password_usuario'] ?? '');

    if ($userForm['nombre_usuario'] === '') {
        $errors[] = 'El nombre del usuario es obligatorio.';
    }

    if (!preg_match('/^[a-zA-Z0-9._-]{3,60}$/', $userForm['username_usuario'])) {
        $errors[] = 'El usuario debe tener entre 3 y 60 caracteres (letras, números, puntos, guiones).';
    }

    if ($userForm['email_usuario'] !== '' && !filter_var($userForm['email_usuario'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email ingresado no es válido.';
    }

    if ($userPassword === '' || strlen($userPassword) < 6) {
        $errors[] = 'La contraseña es obligatoria y debe tener al menos 6 caracteres.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'INSERT INTO usuarios (id_empresa, nombre_usuario, username_usuario, email_usuario, password_hash_usuario, activo_usuario) VALUES (:id_empresa, :nombre_usuario, :username_usuario, :email_usuario, :password_hash_usuario, :activo_usuario)'
            );
            $statement->execute([
                ':id_empresa' => current_empresa_id(),
                ':nombre_usuario' => $userForm['nombre_usuario'],
                ':username_usuario' => $userForm['username_usuario'],
                ':email_usuario' => $userForm['email_usuario'] !== '' ? $userForm['email_usuario'] : null,
                ':password_hash_usuario' => password_hash($userPassword, PASSWORD_BCRYPT),
                ':activo_usuario' => $userForm['activo_usuario'],
            ]);

            $successMessage = 'Usuario dado de alta correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $userForm = ['nombre_usuario' => '', 'username_usuario' => '', 'email_usuario' => '', 'activo_usuario' => '1'];

            if (($page ?? 'dashboard') !== 'usuarios') {
                header('Location: /index.php?page=usuarios');
                exit;
            }
        } catch (Throwable $throwable) {
            if ((int) $throwable->getCode() === 23000) {
                $errors[] = 'Ya existe un usuario con ese nombre de usuario.';
            } else {
                $errors[] = 'No fue posible guardar el usuario.';
            }
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_usuario'])) {
    $userEditId = filter_var($_POST['usuario_edit_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $userForm['nombre_usuario'] = trim((string) ($_POST['nombre_usuario'] ?? ''));
    $userForm['username_usuario'] = trim((string) ($_POST['username_usuario'] ?? ''));
    $userForm['email_usuario'] = trim((string) ($_POST['email_usuario'] ?? ''));
    $userForm['activo_usuario'] = isset($_POST['activo_usuario']) ? '1' : '0';
    $userPassword = (string) ($_POST['password_usuario'] ?? '');

    if ($userEditId === false) {
        $errors[] = 'El usuario a editar no es válido.';
    }

    if ($userForm['nombre_usuario'] === '') {
        $errors[] = 'El nombre del usuario es obligatorio.';
    }

    if (!preg_match('/^[a-zA-Z0-9._-]{3,60}$/', $userForm['username_usuario'])) {
        $errors[] = 'El usuario debe tener entre 3 y 60 caracteres (letras, números, puntos, guiones).';
    }

    if ($userForm['email_usuario'] !== '' && !filter_var($userForm['email_usuario'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email ingresado no es válido.';
    }

    if ($userPassword !== '' && strlen($userPassword) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
    }

    if ($userEditId !== false && $userEditId === ($_SESSION['user']['id'] ?? null) && $userForm['activo_usuario'] === '0') {
        $errors[] = 'No podés desactivar tu propio usuario.';
    }

    if (!$errors) {
        try {
            if ($userPassword !== '') {
                $statement = db()->prepare(
                    'UPDATE usuarios SET nombre_usuario = :nombre_usuario, username_usuario = :username_usuario, email_usuario = :email_usuario, password_hash_usuario = :password_hash_usuario, activo_usuario = :activo_usuario WHERE id_usuario = :id_usuario AND id_empresa = :id_empresa'
                );
                $statement->execute([
                    ':nombre_usuario' => $userForm['nombre_usuario'],
                    ':username_usuario' => $userForm['username_usuario'],
                    ':email_usuario' => $userForm['email_usuario'] !== '' ? $userForm['email_usuario'] : null,
                    ':password_hash_usuario' => password_hash($userPassword, PASSWORD_BCRYPT),
                    ':activo_usuario' => $userForm['activo_usuario'],
                    ':id_usuario' => $userEditId,
                    ':id_empresa' => current_empresa_id(),
                ]);
            } else {
                $statement = db()->prepare(
                    'UPDATE usuarios SET nombre_usuario = :nombre_usuario, username_usuario = :username_usuario, email_usuario = :email_usuario, activo_usuario = :activo_usuario WHERE id_usuario = :id_usuario AND id_empresa = :id_empresa'
                );
                $statement->execute([
                    ':nombre_usuario' => $userForm['nombre_usuario'],
                    ':username_usuario' => $userForm['username_usuario'],
                    ':email_usuario' => $userForm['email_usuario'] !== '' ? $userForm['email_usuario'] : null,
                    ':activo_usuario' => $userForm['activo_usuario'],
                    ':id_usuario' => $userEditId,
                    ':id_empresa' => current_empresa_id(),
                ]);
            }

            $successMessage = 'Usuario actualizado correctamente.';
            $_SESSION['success_message'] = $successMessage;
            if ($userEditId === ($_SESSION['user']['id'] ?? null)) {
                $_SESSION['user']['name'] = $userForm['nombre_usuario'];
            }
            $userEditId = null;
            $userForm = ['nombre_usuario' => '', 'username_usuario' => '', 'email_usuario' => '', 'activo_usuario' => '1'];

            if (($page ?? 'dashboard') !== 'usuarios') {
                header('Location: /index.php?page=usuarios');
                exit;
            }
        } catch (Throwable $throwable) {
            if ((int) $throwable->getCode() === 23000) {
                $errors[] = 'Ya existe un usuario con ese nombre de usuario.';
            } else {
                $errors[] = 'No fue posible actualizar el usuario.';
            }
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_usuario'])) {
    $deleteId = filter_var($_POST['usuario_delete_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($deleteId === false) {
        $errors[] = 'El usuario a eliminar no es válido.';
    } elseif ($deleteId === ($_SESSION['user']['id'] ?? null)) {
        $errors[] = 'No podés eliminar tu propio usuario.';
    } else {
        try {
            $statement = db()->prepare('DELETE FROM usuarios WHERE id_usuario = :id_usuario AND id_empresa = :id_empresa');
            $statement->execute([':id_usuario' => $deleteId, ':id_empresa' => current_empresa_id()]);
            $successMessage = 'Usuario eliminado correctamente.';
            $_SESSION['success_message'] = $successMessage;

            if (($page ?? 'dashboard') !== 'usuarios') {
                header('Location: /index.php?page=usuarios');
                exit;
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible eliminar el usuario.';
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_certificado'])) {
    // Tipo de certificado fijo
    $certForm['tipo_certificado'] = 'certificadogeneral';
    $certForm['id_cliente_certificado'] = trim((string) ($_POST['id_cliente_certificado'] ?? ''));
    $certForm['id_especie_certificado'] = trim((string) ($_POST['id_especie_certificado'] ?? ''));
    $certForm['id_cultivar_certificado'] = trim((string) ($_POST['id_cultivar_certificado'] ?? ''));
    $certForm['marca_lote_certificado'] = trim((string) ($_POST['marca_lote_certificado'] ?? ''));
    $certForm['peso_certificado'] = trim((string) ($_POST['peso_certificado'] ?? ''));
    $certForm['nro_envases_certificado'] = trim((string) ($_POST['nro_envases_certificado'] ?? ''));
    $certForm['fecha_muestreo_certificado'] = trim((string) ($_POST['fecha_muestreo_certificado'] ?? ''));
    $certForm['precinto_certificado'] = trim((string) ($_POST['precinto_certificado'] ?? ''));
    $certForm['fecha_muestras_certificado'] = trim((string) ($_POST['fecha_muestras_certificado'] ?? ''));
    $certForm['fecha_finalizacion_ensayos_certificado'] = trim((string) ($_POST['fecha_finalizacion_ensayos_certificado'] ?? ''));
    $certForm['nro_analisis_certificado'] = trim((string) ($_POST['nro_analisis_certificado'] ?? ''));
    $certForm['pureza_semilla_pura_certificado'] = trim((string) ($_POST['pureza_semilla_pura_certificado'] ?? ''));
    $certForm['materia_inerte_certificado'] = trim((string) ($_POST['materia_inerte_certificado'] ?? ''));
    $certForm['pureza_otras_semillas_certificado'] = trim((string) ($_POST['pureza_otras_semillas_certificado'] ?? ''));
    $certForm['pg_numero_dias_certificado'] = trim((string) ($_POST['pg_numero_dias_certificado'] ?? ''));
    $certForm['pg_plantulas_normales_certificado'] = trim((string) ($_POST['pg_plantulas_normales_certificado'] ?? ''));
    $certForm['pg_semillas_duras_certificado'] = trim((string) ($_POST['pg_semillas_duras_certificado'] ?? ''));
    $certForm['pg_semillas_frescas_certificado'] = trim((string) ($_POST['pg_semillas_frescas_certificado'] ?? ''));
    $certForm['pg_plantulas_anormales_certificado'] = trim((string) ($_POST['pg_plantulas_anormales_certificado'] ?? ''));
    $certForm['pg_semillas_muertas_certificado'] = trim((string) ($_POST['pg_semillas_muertas_certificado'] ?? ''));
    $certForm['pg_contenido_humedad_certificado'] = trim((string) ($_POST['pg_contenido_humedad_certificado'] ?? ''));
    $certForm['pgc_numero_dias_certificado'] = trim((string) ($_POST['pgc_numero_dias_certificado'] ?? ''));
    $certForm['pgc_plantulas_normales_certificado'] = trim((string) ($_POST['pgc_plantulas_normales_certificado'] ?? ''));
    $certForm['pgc_semillas_duras_certificado'] = trim((string) ($_POST['pgc_semillas_duras_certificado'] ?? ''));
    $certForm['pgc_semillas_frescas_certificado'] = trim((string) ($_POST['pgc_semillas_frescas_certificado'] ?? ''));
    $certForm['pgc_plantulas_anormales_certificado'] = trim((string) ($_POST['pgc_plantulas_anormales_certificado'] ?? ''));
    $certForm['pgc_semillas_muertas_certificado'] = trim((string) ($_POST['pgc_semillas_muertas_certificado'] ?? ''));
    $certForm['vigor_nro_dias_certificado'] = trim((string) ($_POST['vigor_nro_dias_certificado'] ?? ''));
    $certForm['vigor_plantulas_normales_certificado'] = trim((string) ($_POST['vigor_plantulas_normales_certificado'] ?? ''));
    $certForm['id_otras_determinaciones_certificado'] = array_values(array_unique(array_filter(
        array_map('intval', (array) ($_POST['id_otras_determinaciones_certificado'] ?? [])),
        static fn($id) => $id > 0
    )));
    $certForm['otras_determinaciones_texto_certificado'] = trim((string) ($_POST['otras_determinaciones_texto_certificado'] ?? ''));
    $certForm['ampara_totalidad_lote_certificado'] = trim((string) ($_POST['ampara_totalidad_lote_certificado'] ?? 'N'));
    $certForm['localidad_pais_certificado'] = trim((string) ($_POST['localidad_pais_certificado'] ?? ''));
    $certForm['fecha_emision_certificado'] = trim((string) ($_POST['fecha_emision_certificado'] ?? ''));
    $certForm['otras_referencias_certificado'] = trim((string) ($_POST['otras_referencias_certificado'] ?? ''));
    $certForm['clase_materia_inerte_certificado'] = trim((string) ($_POST['clase_materia_inerte_certificado'] ?? ''));
    $certForm['otras_semillas_certificado'] = trim((string) ($_POST['otras_semillas_certificado'] ?? ''));

    if ($certForm['ampara_totalidad_lote_certificado'] !== '' && !in_array($certForm['ampara_totalidad_lote_certificado'], ['S', 'N'], true)) {
        $errors[] = 'La opción de ampara totalidad del lote no es válida.';
    }

    $clientId = filter_var($certForm['id_cliente_certificado'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $speciesId = filter_var($certForm['id_especie_certificado'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $cultivarId = filter_var($certForm['id_cultivar_certificado'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($clientId === false) {
        $errors[] = 'Debes seleccionar un cliente válido.';
    } else {
        $ownStatement = db()->prepare('SELECT id_cliente FROM clientes WHERE id_cliente = :id AND id_empresa = :id_empresa LIMIT 1');
        $ownStatement->execute([':id' => $clientId, ':id_empresa' => current_empresa_id()]);
        if ($ownStatement->fetch() === false) {
            $errors[] = 'El cliente seleccionado no existe.';
        }
    }

    if ($speciesId === false) {
        $errors[] = 'Debes seleccionar una especie válida.';
    } else {
        $ownStatement = db()->prepare('SELECT id_especie FROM especies WHERE id_especie = :id AND id_empresa = :id_empresa LIMIT 1');
        $ownStatement->execute([':id' => $speciesId, ':id_empresa' => current_empresa_id()]);
        if ($ownStatement->fetch() === false) {
            $errors[] = 'La especie seleccionada no existe.';
        }
    }

    if ($cultivarId === false) {
        $errors[] = 'Debes seleccionar un cultivar válido.';
    } else {
        $ownStatement = db()->prepare('SELECT id_cultivar FROM cultivares WHERE id_cultivar = :id AND id_empresa = :id_empresa LIMIT 1');
        $ownStatement->execute([':id' => $cultivarId, ':id_empresa' => current_empresa_id()]);
        if ($ownStatement->fetch() === false) {
            $errors[] = 'El cultivar seleccionado no existe.';
        }
    }

    // Validar porcentajes de Pureza - DEBEN sumar 100 o 0
    $purezaFields = [
        'pureza_semilla_pura_certificado' => (float) $certForm['pureza_semilla_pura_certificado'],
        'materia_inerte_certificado' => (float) $certForm['materia_inerte_certificado'],
        'pureza_otras_semillas_certificado' => (float) $certForm['pureza_otras_semillas_certificado'],
    ];
    $purezaSum = 0;
    foreach ($purezaFields as $value) {
        if ($value > 100) {
            $errors[] = 'Los campos de Pureza no pueden exceder 100.';
            break;
        }
        $purezaSum += $value;
    }
    if (abs($purezaSum - 100.0) > 0.01 && abs($purezaSum) > 0.01) {
        $errors[] = 'La suma total de Pureza debe ser 100 o 0 (actual: ' . $purezaSum . ').';
    }

    // Validar porcentajes de Poder Germinativo - DEBEN sumar 100 o 0
    $pgFields = [
        'pg_plantulas_normales_certificado' => (float) $certForm['pg_plantulas_normales_certificado'],
        'pg_semillas_duras_certificado' => (float) $certForm['pg_semillas_duras_certificado'],
        'pg_semillas_frescas_certificado' => (float) $certForm['pg_semillas_frescas_certificado'],
        'pg_plantulas_anormales_certificado' => (float) $certForm['pg_plantulas_anormales_certificado'],
        'pg_semillas_muertas_certificado' => (float) $certForm['pg_semillas_muertas_certificado'],
    ];
    $pgSum = 0;
    foreach ($pgFields as $value) {
        if ($value > 100) {
            $errors[] = 'Los campos de Poder Germinativo no pueden exceder 100.';
            break;
        }
        $pgSum += $value;
    }
    if (abs($pgSum - 100.0) > 0.01 && abs($pgSum) > 0.01) {
        $errors[] = 'La suma total de Poder Germinativo debe ser 100 o 0 (actual: ' . $pgSum . ').';
    }

    // Validar porcentajes de Poder Germinativo Curado - DEBEN sumar 100 o 0
    $pgcFields = [
        'pgc_plantulas_normales_certificado' => (float) $certForm['pgc_plantulas_normales_certificado'],
        'pgc_semillas_duras_certificado' => (float) $certForm['pgc_semillas_duras_certificado'],
        'pgc_semillas_frescas_certificado' => (float) $certForm['pgc_semillas_frescas_certificado'],
        'pgc_plantulas_anormales_certificado' => (float) $certForm['pgc_plantulas_anormales_certificado'],
        'pgc_semillas_muertas_certificado' => (float) $certForm['pgc_semillas_muertas_certificado'],
    ];
    $pgcSum = 0;
    foreach ($pgcFields as $value) {
        if ($value > 100) {
            $errors[] = 'Los campos de Poder Germinativo Curado no pueden exceder 100.';
            break;
        }
        $pgcSum += $value;
    }
    if (abs($pgcSum - 100.0) > 0.01 && abs($pgcSum) > 0.01) {
        $errors[] = 'La suma total de Poder Germinativo Curado debe ser 100 o 0 (actual: ' . $pgcSum . ').';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'INSERT INTO certificados (tipo_certificado, id_empresa, id_usuario_certificado, id_cliente_certificado, id_especie_certificado, id_cultivar_certificado, marca_lote_certificado, peso_certificado, nro_envases_certificado, fecha_muestreo_certificado, otras_referencias_certificado, precinto_certificado, fecha_muestras_certificado, fecha_finalizacion_ensayos_certificado, nro_analisis_certificado, pureza_semilla_pura_certificado, materia_inerte_certificado, pureza_otras_semillas_certificado, pg_numero_dias_certificado, pg_plantulas_normales_certificado, pg_semillas_duras_certificado, pg_semillas_frescas_certificado, pg_plantulas_anormales_certificado, pg_semillas_muertas_certificado, pg_contenido_humedad_certificado, pgc_numero_dias_certificado, pgc_plantulas_normales_certificado, pgc_semillas_duras_certificado, pgc_semillas_frescas_certificado, pgc_plantulas_anormales_certificado, pgc_semillas_muertas_certificado, vigor_nro_dias_certificado, vigor_plantulas_normales_certificado, ampara_totalidad_lote_certificado, localidad_pais_certificado, fecha_emision_certificado, clase_materia_inerte_certificado, otras_semillas_certificado, otras_determinaciones_texto_certificado) VALUES (:tipo_certificado, :id_empresa, :id_usuario_certificado, :id_cliente_certificado, :id_especie_certificado, :id_cultivar_certificado, :marca_lote_certificado, :peso_certificado, :nro_envases_certificado, :fecha_muestreo_certificado, :otras_referencias_certificado, :precinto_certificado, :fecha_muestras_certificado, :fecha_finalizacion_ensayos_certificado, :nro_analisis_certificado, :pureza_semilla_pura_certificado, :materia_inerte_certificado, :pureza_otras_semillas_certificado, :pg_numero_dias_certificado, :pg_plantulas_normales_certificado, :pg_semillas_duras_certificado, :pg_semillas_frescas_certificado, :pg_plantulas_anormales_certificado, :pg_semillas_muertas_certificado, :pg_contenido_humedad_certificado, :pgc_numero_dias_certificado, :pgc_plantulas_normales_certificado, :pgc_semillas_duras_certificado, :pgc_semillas_frescas_certificado, :pgc_plantulas_anormales_certificado, :pgc_semillas_muertas_certificado, :vigor_nro_dias_certificado, :vigor_plantulas_normales_certificado, :ampara_totalidad_lote_certificado, :localidad_pais_certificado, :fecha_emision_certificado, :clase_materia_inerte_certificado, :otras_semillas_certificado, :otras_determinaciones_texto_certificado)'
            );
            $statement->execute([
                ':tipo_certificado' => $certForm['tipo_certificado'],
                ':id_empresa' => current_empresa_id(),
                ':id_usuario_certificado' => $_SESSION['user']['id'] ?? null,
                ':id_cliente_certificado' => $clientId,
                ':id_especie_certificado' => $speciesId,
                ':id_cultivar_certificado' => $cultivarId,
                ':marca_lote_certificado' => $certForm['marca_lote_certificado'] !== '' ? $certForm['marca_lote_certificado'] : null,
                ':peso_certificado' => $certForm['peso_certificado'] !== '' ? (float)$certForm['peso_certificado'] : null,
                ':nro_envases_certificado' => $certForm['nro_envases_certificado'] !== '' ? (int)$certForm['nro_envases_certificado'] : null,
                ':fecha_muestreo_certificado' => $certForm['fecha_muestreo_certificado'] !== '' ? $certForm['fecha_muestreo_certificado'] : null,
                ':otras_referencias_certificado' => $certForm['otras_referencias_certificado'] !== '' ? $certForm['otras_referencias_certificado'] : null,
                ':precinto_certificado' => $certForm['precinto_certificado'] !== '' ? $certForm['precinto_certificado'] : null,
                ':fecha_muestras_certificado' => $certForm['fecha_muestras_certificado'] !== '' ? $certForm['fecha_muestras_certificado'] : null,
                ':fecha_finalizacion_ensayos_certificado' => $certForm['fecha_finalizacion_ensayos_certificado'] !== '' ? $certForm['fecha_finalizacion_ensayos_certificado'] : null,
                ':nro_analisis_certificado' => $certForm['nro_analisis_certificado'] !== '' ? $certForm['nro_analisis_certificado'] : null,
                ':pureza_semilla_pura_certificado' => $certForm['pureza_semilla_pura_certificado'] !== '' ? (float)$certForm['pureza_semilla_pura_certificado'] : null,
                ':materia_inerte_certificado' => $certForm['materia_inerte_certificado'] !== '' ? (float)$certForm['materia_inerte_certificado'] : null,
                ':pureza_otras_semillas_certificado' => $certForm['pureza_otras_semillas_certificado'] !== '' ? (float)$certForm['pureza_otras_semillas_certificado'] : null,
                ':pg_numero_dias_certificado' => $certForm['pg_numero_dias_certificado'] !== '' ? (int)$certForm['pg_numero_dias_certificado'] : null,
                ':pg_plantulas_normales_certificado' => $certForm['pg_plantulas_normales_certificado'] !== '' ? (float)$certForm['pg_plantulas_normales_certificado'] : null,
                ':pg_semillas_duras_certificado' => $certForm['pg_semillas_duras_certificado'] !== '' ? (float)$certForm['pg_semillas_duras_certificado'] : null,
                ':pg_semillas_frescas_certificado' => $certForm['pg_semillas_frescas_certificado'] !== '' ? (float)$certForm['pg_semillas_frescas_certificado'] : null,
                ':pg_plantulas_anormales_certificado' => $certForm['pg_plantulas_anormales_certificado'] !== '' ? (float)$certForm['pg_plantulas_anormales_certificado'] : null,
                ':pg_semillas_muertas_certificado' => $certForm['pg_semillas_muertas_certificado'] !== '' ? (float)$certForm['pg_semillas_muertas_certificado'] : null,
                ':pg_contenido_humedad_certificado' => $certForm['pg_contenido_humedad_certificado'] !== '' ? (float)$certForm['pg_contenido_humedad_certificado'] : null,
                ':pgc_numero_dias_certificado' => $certForm['pgc_numero_dias_certificado'] !== '' ? (int)$certForm['pgc_numero_dias_certificado'] : null,
                ':pgc_plantulas_normales_certificado' => $certForm['pgc_plantulas_normales_certificado'] !== '' ? (float)$certForm['pgc_plantulas_normales_certificado'] : null,
                ':pgc_semillas_duras_certificado' => $certForm['pgc_semillas_duras_certificado'] !== '' ? (float)$certForm['pgc_semillas_duras_certificado'] : null,
                ':pgc_semillas_frescas_certificado' => $certForm['pgc_semillas_frescas_certificado'] !== '' ? (float)$certForm['pgc_semillas_frescas_certificado'] : null,
                ':pgc_plantulas_anormales_certificado' => $certForm['pgc_plantulas_anormales_certificado'] !== '' ? (float)$certForm['pgc_plantulas_anormales_certificado'] : null,
                ':pgc_semillas_muertas_certificado' => $certForm['pgc_semillas_muertas_certificado'] !== '' ? (float)$certForm['pgc_semillas_muertas_certificado'] : null,
                ':vigor_nro_dias_certificado' => $certForm['vigor_nro_dias_certificado'] !== '' ? (int)$certForm['vigor_nro_dias_certificado'] : null,
                ':vigor_plantulas_normales_certificado' => $certForm['vigor_plantulas_normales_certificado'] !== '' ? (float)$certForm['vigor_plantulas_normales_certificado'] : null,
                ':ampara_totalidad_lote_certificado' => $certForm['ampara_totalidad_lote_certificado'],
                ':localidad_pais_certificado' => $certForm['localidad_pais_certificado'] !== '' ? $certForm['localidad_pais_certificado'] : null,
                ':fecha_emision_certificado' => $certForm['fecha_emision_certificado'] !== '' ? $certForm['fecha_emision_certificado'] : null,
                ':clase_materia_inerte_certificado' => $certForm['clase_materia_inerte_certificado'] !== '' ? $certForm['clase_materia_inerte_certificado'] : null,
                ':otras_semillas_certificado' => $certForm['otras_semillas_certificado'] !== '' ? $certForm['otras_semillas_certificado'] : null,
                ':otras_determinaciones_texto_certificado' => $certForm['otras_determinaciones_texto_certificado'] !== '' ? $certForm['otras_determinaciones_texto_certificado'] : null,
            ]);

            $newCertificadoId = (int) db()->lastInsertId();

            if ($certForm['id_otras_determinaciones_certificado'] !== []) {
                $detStatement = db()->prepare(
                    'INSERT IGNORE INTO certificado_determinaciones (id_certificado, id_determinacion) SELECT :id_certificado, id_determinacion FROM otras_determinaciones WHERE id_determinacion = :id_determinacion AND id_empresa = :id_empresa'
                );
                foreach ($certForm['id_otras_determinaciones_certificado'] as $detId) {
                    $detStatement->execute([':id_certificado' => $newCertificadoId, ':id_determinacion' => $detId, ':id_empresa' => current_empresa_id()]);
                }
            }

            $successMessage = 'Certificado dado de alta correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $_SESSION['new_certificado_id'] = $newCertificadoId;
            $certForm = [
                'tipo_certificado' => '',
                'id_cliente_certificado' => '',
                'id_especie_certificado' => '',
                'id_cultivar_certificado' => '',
                'marca_lote_certificado' => '',
                'peso_certificado' => '',
                'nro_envases_certificado' => '',
                'fecha_muestreo_certificado' => '',
                'otras_referencias_certificado' => '',
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
                'clase_materia_inerte_certificado' => '',
                'otras_semillas_certificado' => '',
            ];

            if (($page ?? 'dashboard') !== 'Certificadogeneral') {
                header('Location: /index.php?page=Certificadogeneral');
                exit;
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible guardar el certificado: ' . $throwable->getMessage();
        }
    }
}

if (is_logged_in() && isset($_GET['pdf'])) {
    $certificadoId = filter_var($_GET['pdf'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($certificadoId === false) {
        http_response_code(400);
        echo 'ID de certificado inválido.';
        exit;
    }

    try {
        $pdfStatement = db()->prepare(
            'SELECT c.*, cl.nombre_cliente, e.nombre_especie, e.cientifico_especie, cu.nombre_cultivar
            FROM certificados c
            LEFT JOIN clientes cl ON cl.id_cliente = c.id_cliente_certificado
            LEFT JOIN especies e ON e.id_especie = c.id_especie_certificado
            LEFT JOIN cultivares cu ON cu.id_cultivar = c.id_cultivar_certificado
            WHERE c.id_certificado = :id AND c.id_empresa = :id_empresa
            LIMIT 1'
        );
        $pdfStatement->execute([':id' => $certificadoId, ':id_empresa' => current_empresa_id()]);
        $certificadoPdf = $pdfStatement->fetch();

        $detNombresStatement = db()->prepare(
            'SELECT od.nombre_determinacion
            FROM certificado_determinaciones cd
            JOIN otras_determinaciones od ON od.id_determinacion = cd.id_determinacion
            WHERE cd.id_certificado = :id
            ORDER BY od.nombre_determinacion ASC'
        );
        $detNombresStatement->execute([':id' => $certificadoId]);
        $detNombres = $detNombresStatement->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $throwable) {
        http_response_code(500);
        echo 'No fue posible obtener el certificado.';
        exit;
    }

    if ($certificadoPdf === false) {
        http_response_code(404);
        echo 'Certificado no encontrado.';
        exit;
    }

    require_once __DIR__ . '/vendor/autoload.php';

    $cliente = format_text_value($certificadoPdf['nombre_cliente'] ?? '');
    $especie = format_text_value($certificadoPdf['nombre_especie'] ?? '');
    $cultivar = trim((string) ($certificadoPdf['nombre_cultivar'] ?? ''));
    $especieCultivar = trim($especie . ($cultivar !== '' ? ' / ' . $cultivar : ''));
    $especieCientifico = format_text_value($certificadoPdf['cientifico_especie'] ?? '');
    $localidad = format_text_value($certificadoPdf['localidad_pais_certificado'] ?? '----------------', '----------------');
    $otrasDet = implode(', ', $detNombres);
    $otrasDetTexto = trim((string) ($certificadoPdf['otras_determinaciones_texto_certificado'] ?? ''));
    if ($otrasDetTexto !== '') {
        $otrasDet = $otrasDet !== '' ? $otrasDet . ', ' . $otrasDetTexto : $otrasDetTexto;
    }

    $claseMateria = trim((string) ($certificadoPdf['clase_materia_inerte_certificado'] ?? ''));
    $otrasSemillasTxt = trim((string) ($certificadoPdf['otras_semillas_certificado'] ?? ''));
    $amparaTotalidadLote = trim((string) ($certificadoPdf['ampara_totalidad_lote_certificado'] ?? 'N'));
    $amparaSi = $amparaTotalidadLote === 'S' ? 'X' : ' ';
    $amparaNo = $amparaTotalidadLote === 'N' ? 'X' : ' ';
    $detalleDeterminaciones = $otrasDet !== '' ? htmlspecialchars($otrasDet, ENT_QUOTES, 'UTF-8') : 'N';

    $membreteImagePath = __DIR__ . '/assets/img/membrete-leucaena.png';
    $membreteImagePathNormalized = str_replace('\\', '/', $membreteImagePath);
    $brandHeaderHtml = '<div class="center brand">'
        . '<span class="brand-logo"></span>'
        . '<span class="brand-text">'
        . '<div class="brand-main">Laboratorio de<br>Análisis de Semillas</div>'
        . '<div class="brand-sub">LEUCAENA</div>'
        . '<div class="brand-rule"></div>'
        . '</span>'
        . '</div>';

    if (is_file($membreteImagePath)) {
        $brandHeaderHtml = '<div class="center brand"><img src="'
            . htmlspecialchars($membreteImagePathNormalized, ENT_QUOTES, 'UTF-8')
            . '" alt="Laboratorio Leucaena" class="brand-image"></div>';
    }

    $html = '
    <html>
    <head>
        <style>
            body { font-family: serif; font-size: 14px; color: #222; }
            .cert { width: 100%; border-collapse: collapse; }
            .center { text-align: center; }
            .right { text-align: right; }
            .bold { font-weight: 700; }
            .title { font-size: 42px; font-weight: 700; letter-spacing: 1px; margin-top: 2px; }
            .subtitle { font-size: 13px; line-height: 1.15; }
            .brand { margin: 0 auto 4px; text-align: center; }
            .brand-image { width: 102mm; max-width: 100%; height: auto; }
            .brand-logo { width: 62px; height: 62px; background: #4a5c2f; border: 1px solid #404040; display: inline-block; vertical-align: top; margin-right: 8px; }
            .brand-text { display: inline-block; vertical-align: top; text-align: left; }
            .brand-main { font-size: 12px; font-weight: 700; line-height: 1.2; margin-top: 1px; }
            .brand-sub { font-size: 10px; letter-spacing: 4px; color: #6f7f40; margin-top: 3px; font-weight: 700; }
            .brand-rule { height: 1px; background: #7f8f48; margin-top: 2px; width: 168px; }
            .section { margin-top: 8px; }
            .heading { background: #d8d8d8; border: 1px solid #666; font-size: 11px; font-weight: 700; text-align: center; padding: 5px 0; }
            table.grid { width: 100%; border-collapse: collapse; }
            table.grid td, table.grid th { border: 1px solid #666; padding: 4px 5px; text-align: center; vertical-align: middle; }
            table.grid .left { text-align: left; }
            .pg-highlight { background: #fdecec; }
            .small { font-size: 9px; }
            .mid { font-size: 10px; }
            .line-label { margin-top: 6px; border-top: 1px solid #333; padding-top: 4px; font-size: 11px; font-weight: 700; }
            .line-value { font-size: 11px; font-weight: 400; }
            .footer { margin-top: 8px; font-size: 10px; }
            .phone { text-align: center; font-size: 10px; letter-spacing: 1px; margin-top: 6px; }
            .address { text-align: center; font-size: 10px; letter-spacing: 1px; }
            .si-no { font-size: 13px; letter-spacing: 1px; font-weight: 700; margin: 9px 0 5px; }
        </style>
    </head>
    <body>' . $brandHeaderHtml . '

        <div class="center title">CERTIFICADO DE ANÁLISIS</div>
        <div class="center subtitle">Laboratorio inscripto en el Registro Nacional de Comercio y Fiscalización de Semillas I/8008</div>
        <div class="center subtitle">Certificado de Análisis fuera del alcance de la acreditación otorgada por INASE</div>

        <div class="section heading">INFORMACIÓN DEL SOLICITANTE</div>
        <table class="grid">
            <tr>
                <td class="left" colspan="2"><span class="bold">Nombre del Solicitante:</span> ' . htmlspecialchars($cliente, ENT_QUOTES, 'UTF-8') . '</td>
                <td class="left" colspan="2"><span class="bold">Marca y N° de Lote:</span> ' . htmlspecialchars((string) ($certificadoPdf['marca_lote_certificado'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr>
                <td class="left" colspan="4"><span class="bold">Especie/ Cultivar/ Categoría:</span> ' . htmlspecialchars($especieCultivar, ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr class="bold">
                <td>PESO DEL LOTE</td>
                <td>N° DE ENVASES</td>
                <td>FECHA DE MUESTREO</td>
                <td>OTRAS REFERENCIAS</td>
            </tr>
            <tr>
                <td>' . htmlspecialchars(format_text_value($certificadoPdf['peso_certificado'] ?? '----------------', '----------------'), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_text_value($certificadoPdf['nro_envases_certificado'] ?? '----------------', '----------------'), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_date_short((string) ($certificadoPdf['fecha_muestreo_certificado'] ?? '')), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_text_value($certificadoPdf['otras_referencias_certificado'] ?? '----------------', '----------------'), ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr class="bold">
                <td>N° DE PRECINTO</td>
                <td>FECHA DE RECEPCIÓN<br>DE MUESTRAS</td>
                <td>FECHA DE FINALIZACIÓN<br>DE LOS ENSAYOS</td>
                <td>N° DE ANÁLISIS</td>
            </tr>
            <tr>
                <td>' . htmlspecialchars(format_text_value($certificadoPdf['precinto_certificado'] ?? '----------------', '----------------'), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_date_short((string) ($certificadoPdf['fecha_muestras_certificado'] ?? '')), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_date_short((string) ($certificadoPdf['fecha_finalizacion_ensayos_certificado'] ?? '')), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_text_value($certificadoPdf['nro_analisis_certificado'] ?? '----------------', '----------------'), ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
        </table>

        <div class="section heading">RESULTADOS LOS ANÁLISIS</div>
        <table class="grid">
            <tr>
                <td class="left" colspan="11"><span class="bold">Especie (Nombre Científico):</span> <em>' . htmlspecialchars($especieCientifico, ENT_QUOTES, 'UTF-8') . '</em></td>
            </tr>
            <tr class="bold">
                <td colspan="3">PUREZA<br><span class="small">(% en peso)</span></td>
                <td colspan="6">PODER GERMINATIVO<br><span class="small">(% en número)</span></td>
                <td colspan="2">CONTENIDO DE HUMEDAD<br><span class="small">(%)</span></td>
            </tr>
            <tr class="bold mid">
                <td>Semilla<br>Pura</td>
                <td>Materia<br>Inerte</td>
                <td>Otras<br>Semillas</td>
                <td>Número<br>de Días</td>
                <td>Plántulas<br>Normales</td>
                <td>Semillas<br>Duras</td>
                <td>Semillas<br>Frescas</td>
                <td>Plántulas<br>Anormales</td>
                <td>Semillas<br>Muertas</td>
                <td colspan="2">Contenido<br>de Humedad</td>
            </tr>
            <tr>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pureza_semilla_pura_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['materia_inerte_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pureza_otras_semillas_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_text_value($certificadoPdf['pg_numero_dias_certificado'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td class="pg-highlight">' . htmlspecialchars(format_percent_value($certificadoPdf['pg_plantulas_normales_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pg_semillas_duras_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pg_semillas_frescas_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pg_plantulas_anormales_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pg_semillas_muertas_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td colspan="2">' . htmlspecialchars(format_percent_value($certificadoPdf['pg_contenido_humedad_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
        </table>

        <table class="grid" style="margin-top:4px;">
            <tr class="bold">
                <td colspan="6">PODER GERMINATIVO CURADO<br><span class="small">(% en número)</span></td>
                <td colspan="4">VIGOR: COLD TEST (PRUEBA DE FRÍO)<br><span class="small">(% en número)</span></td>
            </tr>
            <tr class="bold mid">
                <td>Número<br>de Días</td>
                <td>Plántulas<br>Normales</td>
                <td>Semillas<br>Duras</td>
                <td>Semillas<br>Frescas</td>
                <td>Plántulas<br>Anormales</td>
                <td>Semillas<br>Muertas</td>
                <td>Número<br>de Días</td>
                <td colspan="3">Plántulas Normales</td>
            </tr>
            <tr>
                <td>' . htmlspecialchars(format_text_value($certificadoPdf['pgc_numero_dias_certificado'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td class="pg-highlight">' . htmlspecialchars(format_percent_value($certificadoPdf['pgc_plantulas_normales_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pgc_semillas_duras_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pgc_semillas_frescas_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pgc_plantulas_anormales_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pgc_semillas_muertas_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_text_value($certificadoPdf['vigor_nro_dias_certificado'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td class="pg-highlight" colspan="3">' . htmlspecialchars(format_percent_value($certificadoPdf['vigor_plantulas_normales_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
        </table>

        <div class="line-label">Clase de Materia Inerte: <span class="line-value">' . htmlspecialchars(format_text_value($certificadoPdf['clase_materia_inerte_certificado'] ?? '', ' '), ENT_QUOTES, 'UTF-8') . '</span></div>
        <div class="line-label">Otras Semillas: <span class="line-value">' . htmlspecialchars(format_text_value($certificadoPdf['otras_semillas_certificado'] ?? '', ' '), ENT_QUOTES, 'UTF-8') . '</span></div>
        <div class="line-label">Otras Determinaciones: <span class="line-value">' . $detalleDeterminaciones . '</span></div>

        <div class="si-no left">ESTE CERTIFICADO &nbsp;&nbsp; SI [' . $amparaSi . '] &nbsp;&nbsp; NO [' . $amparaNo . '] &nbsp;&nbsp; AMPARA LA TOTALIDAD DEL LOTE</div>
        
    </body>
    </html>';

    //------------------------------------------------
    $footerHtml = '
    <table class="grid footer" style="width:100%; border-collapse: collapse;">
        <tr class="bold">
            <td style="border:1px solid #666; padding:4px 5px; text-align:center;">Localidad y País</td>
            <td style="border:1px solid #666; padding:4px 5px; text-align:center;">Fecha de Emisión</td>
            <td style="border:1px solid #666; padding:4px 5px; text-align:center;">Firma del Director Técnico</td>
        </tr>
        <tr>
            <td style="border:1px solid #666; padding:4px 5px; height:54px; text-align:center;">' . htmlspecialchars($localidad, ENT_QUOTES, 'UTF-8') . '</td>
            <td style="border:1px solid #666; padding:4px 5px; text-align:center;">' . htmlspecialchars(format_date_long_spanish((string) ($certificadoPdf['fecha_emision_certificado'] ?? '')), ENT_QUOTES, 'UTF-8') . '</td>
            <td style="border:1px solid #666; padding:4px 5px 5px; text-align:center; vertical-align:bottom;">Ma. Eugenia Bryant M.N 16746</td>
        </tr>
    </table>
    <div class="phone" style="text-align:center; font-size:10px; letter-spacing:1px; margin-top:6px;">TELÉFONO 2346-316003</div>
    <div class="address" style="text-align:center; font-size:10px; letter-spacing:1px;">SAN LORENZO N° 341 | CHIVILCOY (6620) BUENOS AIRES</div>
    ';
    //------------------------------------------------

    $mpdf = new \Mpdf\Mpdf([
        'format' => 'A4',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 8,
        'margin_bottom' => 30, //5
    ]);
    $mpdf->SetHTMLFooter($footerHtml);
    $mpdf->WriteHTML($html);

    $pdfFilename = 'certificado_' . $certificadoId . '.pdf';
    $pdfContent = $mpdf->Output($pdfFilename, \Mpdf\Output\Destination::STRING_RETURN);

    $certificadosDir = empresa_storage_dir(current_empresa_id());
    if (is_dir($certificadosDir) && is_writable($certificadosDir)) {
        @file_put_contents(rtrim($certificadosDir, '\\/') . DIRECTORY_SEPARATOR . $pdfFilename, $pdfContent);
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $pdfFilename . '"');
    header('Content-Length: ' . strlen($pdfContent));
    echo $pdfContent;
    exit;
}

if (!is_logged_in()) {
    $isLoginPage = true;
} else {
    $isLoginPage = false;
    $page = (string) ($_GET['page'] ?? 'dashboard');
    if (!array_key_exists($page, $pageMap)) {
        $page = 'dashboard';
    }
    if ($page === 'empresas' && !is_superadmin()) {
        $page = 'dashboard';
    }
    $currentPage = $pageMap[$page];
}

$clientes = [];
if (is_logged_in() && ($page ?? 'dashboard') === 'clientes') {
    try {
        $clientesStatement = db()->prepare('SELECT id_cliente, nombre_cliente, telefono_cliente, direccion_cliente, mail_cliente, cuit_cliente, created_at_cliente FROM clientes WHERE id_empresa = :id_empresa ORDER BY id_cliente DESC');
        $clientesStatement->execute([':id_empresa' => current_empresa_id()]);
        $clientes = $clientesStatement->fetchAll();
    } catch (Throwable $throwable) {
        $errors[] = 'No se pudo cargar el listado de clientes. Verifica que la base de datos y la tabla existan.';
    }
}

if (is_logged_in() && ($page ?? 'dashboard') === 'clientes' && isset($_GET['edit']) && $clientEditId === null) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($editId !== false) {
        try {
            $editStatement = db()->prepare('SELECT id_cliente, nombre_cliente, telefono_cliente, direccion_cliente, mail_cliente, cuit_cliente FROM clientes WHERE id_cliente = :id AND id_empresa = :id_empresa LIMIT 1');
            $editStatement->execute([':id' => $editId, ':id_empresa' => current_empresa_id()]);
            $editRow = $editStatement->fetch();
            if ($editRow !== false) {
                $clientEditId = (int) $editRow['id_cliente'];
                $clientForm['nombre_cliente'] = (string) ($editRow['nombre_cliente'] ?? '');
                $clientForm['telefono_cliente'] = (string) ($editRow['telefono_cliente'] ?? '');
                $clientForm['direccion_cliente'] = (string) ($editRow['direccion_cliente'] ?? '');
                $clientForm['mail_cliente'] = (string) ($editRow['mail_cliente'] ?? '');
                $clientForm['cuit_cliente'] = (string) ($editRow['cuit_cliente'] ?? '');
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No se pudo cargar el cliente para edición.';
        }
    }
}

$especies = [];
if (is_logged_in() && ($page ?? 'dashboard') === 'especies') {
    try {
        $especiesStatement = db()->prepare('SELECT id_especie, nombre_especie, cientifico_especie, created_at_especie FROM especies WHERE id_empresa = :id_empresa ORDER BY id_especie DESC');
        $especiesStatement->execute([':id_empresa' => current_empresa_id()]);
        $especies = $especiesStatement->fetchAll();
    } catch (Throwable $throwable) {
        $errors[] = 'No se pudo cargar el listado de especies. Verifica que la base de datos y la tabla existan.';
    }
}

if (is_logged_in() && ($page ?? 'dashboard') === 'especies' && isset($_GET['edit']) && $speciesEditId === null) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($editId !== false) {
        try {
            $editStatement = db()->prepare('SELECT id_especie, nombre_especie, cientifico_especie FROM especies WHERE id_especie = :id AND id_empresa = :id_empresa LIMIT 1');
            $editStatement->execute([':id' => $editId, ':id_empresa' => current_empresa_id()]);
            $editRow = $editStatement->fetch();
            if ($editRow !== false) {
                $speciesEditId = (int) $editRow['id_especie'];
                $speciesForm['nombre_especie'] = (string) ($editRow['nombre_especie'] ?? '');
                $speciesForm['cientifico_especie'] = (string) ($editRow['cientifico_especie'] ?? '');
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No se pudo cargar la especie para edición.';
        }
    }
}

$detList = [];
if (is_logged_in() && ($page ?? 'dashboard') === 'otras_determinaciones') {
    try {
        $detListStatement = db()->prepare('SELECT id_determinacion, nombre_determinacion, created_at_determinacion FROM otras_determinaciones WHERE id_empresa = :id_empresa ORDER BY id_determinacion DESC');
        $detListStatement->execute([':id_empresa' => current_empresa_id()]);
        $detList = $detListStatement->fetchAll();
    } catch (Throwable $throwable) {
        $errors[] = 'No se pudo cargar el listado de determinaciones. Verifica que la tabla exista.';
    }
}

if (is_logged_in() && ($page ?? 'dashboard') === 'otras_determinaciones' && isset($_GET['edit']) && $detEditId === null) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($editId !== false) {
        try {
            $editStatement = db()->prepare('SELECT id_determinacion, nombre_determinacion FROM otras_determinaciones WHERE id_determinacion = :id AND id_empresa = :id_empresa LIMIT 1');
            $editStatement->execute([':id' => $editId, ':id_empresa' => current_empresa_id()]);
            $editRow = $editStatement->fetch();
            if ($editRow !== false) {
                $detEditId = (int) $editRow['id_determinacion'];
                $detForm['nombre_determinacion'] = (string) ($editRow['nombre_determinacion'] ?? '');
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No se pudo cargar la determinación para edición.';
        }
    }
}

$usuarios = [];
if (is_logged_in() && ($page ?? 'dashboard') === 'usuarios') {
    try {
        $usuariosStatement = db()->prepare('SELECT id_usuario, nombre_usuario, username_usuario, email_usuario, activo_usuario, created_at_usuario FROM usuarios WHERE id_empresa = :id_empresa ORDER BY id_usuario DESC');
        $usuariosStatement->execute([':id_empresa' => current_empresa_id()]);
        $usuarios = $usuariosStatement->fetchAll();
    } catch (Throwable $throwable) {
        $errors[] = 'No se pudo cargar el listado de usuarios. Verifica que la tabla exista.';
    }
}

if (is_logged_in() && ($page ?? 'dashboard') === 'usuarios' && isset($_GET['edit']) && $userEditId === null) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($editId !== false) {
        try {
            $editStatement = db()->prepare('SELECT id_usuario, nombre_usuario, username_usuario, email_usuario, activo_usuario FROM usuarios WHERE id_usuario = :id AND id_empresa = :id_empresa LIMIT 1');
            $editStatement->execute([':id' => $editId, ':id_empresa' => current_empresa_id()]);
            $editRow = $editStatement->fetch();
            if ($editRow !== false) {
                $userEditId = (int) $editRow['id_usuario'];
                $userForm['nombre_usuario'] = (string) ($editRow['nombre_usuario'] ?? '');
                $userForm['username_usuario'] = (string) ($editRow['username_usuario'] ?? '');
                $userForm['email_usuario'] = (string) ($editRow['email_usuario'] ?? '');
                $userForm['activo_usuario'] = (string) ($editRow['activo_usuario'] ?? '1');
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No se pudo cargar el usuario para edición.';
        }
    }
}

$speciesOptions = [];
if (is_logged_in() && ($page ?? 'dashboard') === 'cultivares') {
    try {
        $speciesOptionsStatement = db()->prepare('SELECT id_especie, nombre_especie FROM especies WHERE id_empresa = :id_empresa ORDER BY nombre_especie ASC');
        $speciesOptionsStatement->execute([':id_empresa' => current_empresa_id()]);
        $speciesOptions = $speciesOptionsStatement->fetchAll();
    } catch (Throwable $throwable) {
        $errors[] = 'No se pudo cargar el selector de especies. Verifica que la tabla especies exista.';
    }
}

$cultivares = [];
if (is_logged_in() && ($page ?? 'dashboard') === 'cultivares') {
    try {
        $cultivaresStatement = db()->prepare('SELECT c.id_cultivar, c.nombre_cultivar, c.created_at_cultivar, e.id_especie, e.nombre_especie FROM cultivares c INNER JOIN especies e ON e.id_especie = c.id_especie WHERE c.id_empresa = :id_empresa ORDER BY c.id_cultivar DESC');
        $cultivaresStatement->execute([':id_empresa' => current_empresa_id()]);
        $cultivares = $cultivaresStatement->fetchAll();
    } catch (Throwable $throwable) {
        $errors[] = 'No se pudo cargar el listado de cultivares. Verifica que la tabla cultivares exista.';
    }
}

if (is_logged_in() && ($page ?? 'dashboard') === 'cultivares' && isset($_GET['edit']) && $cultivarEditId === null) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($editId !== false) {
        try {
            $editStatement = db()->prepare('SELECT id_cultivar, id_especie, nombre_cultivar FROM cultivares WHERE id_cultivar = :id AND id_empresa = :id_empresa LIMIT 1');
            $editStatement->execute([':id' => $editId, ':id_empresa' => current_empresa_id()]);
            $editRow = $editStatement->fetch();
            if ($editRow !== false) {
                $cultivarEditId = (int) $editRow['id_cultivar'];
                $cultivarForm['id_especie'] = (string) $editRow['id_especie'];
                $cultivarForm['nombre_cultivar'] = (string) $editRow['nombre_cultivar'];
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No se pudo cargar el cultivar para edición.';
        }
    }
}

$clientesOptions = [];
$especiesOptions = [];
$cultivaresOptions = [];
$determinacionesOptions = [];
$certificados = [];

if (is_logged_in() && ($page ?? 'dashboard') === 'Certificadogeneral') {
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

    $certConditions = ['c.id_empresa = :id_empresa'];
    $certParams = [':id_empresa' => current_empresa_id()];

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
}

$empresas = [];
if (is_superadmin() && ($page ?? 'dashboard') === 'empresas') {
    try {
        $empresas = db()->query('SELECT id_empresa, nombre_empresa, activo_empresa, created_at_empresa FROM empresas ORDER BY id_empresa DESC')->fetchAll();
    } catch (Throwable $throwable) {
        $errors[] = 'No se pudo cargar el listado de empresas.';
    }
}

if (is_superadmin() && ($page ?? 'dashboard') === 'empresas' && isset($_GET['edit']) && $empresaEditId === null) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($editId !== false) {
        try {
            $editStatement = db()->prepare('SELECT id_empresa, nombre_empresa, activo_empresa FROM empresas WHERE id_empresa = :id LIMIT 1');
            $editStatement->execute([':id' => $editId]);
            $editRow = $editStatement->fetch();
            if ($editRow !== false) {
                $empresaEditId = (int) $editRow['id_empresa'];
                $empresaForm['nombre_empresa'] = (string) ($editRow['nombre_empresa'] ?? '');
                $empresaForm['activo_empresa'] = (string) ($editRow['activo_empresa'] ?? '1');
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No se pudo cargar la empresa para edición.';
        }
    }
}

function is_active_page(string $key, string $currentPage): string
{
    return $key === $currentPage ? 'active' : '';
}

function render_card(string $title, string $text): string
{
    return sprintf(
        '<div class="info-card"><h3>%s</h3><p>%s</p></div>',
        htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
    );
}

$loggedUser = $_SESSION['user']['name'] ?? 'Usuario';
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
                <div class="brand-mark">
                    <span class="brand-mark__icon"><i class="bi bi-leaf-fill"></i></span>
                    <div>
                        <div class="brand-title"><?php echo htmlspecialchars(APP_BRAND, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="brand-subtitle"><?php echo htmlspecialchars(APP_SUBTITLE, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
                <h1 class="login-heading">Acceso al sistema</h1>
                <p class="login-copy">Inicia sesión para administrar clientes, especies, cultivares y certificados.</p>

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
                    <div class="sidebar-brand__icon"><i class="bi bi-leaf-fill"></i></div>
                    <div>
                        <div class="sidebar-brand__title"><?php echo htmlspecialchars(APP_BRAND, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="sidebar-brand__subtitle"><?php echo htmlspecialchars(APP_SUBTITLE, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>

                <nav class="sidebar-nav flex-grow-1">
                    <a class="nav-link <?php echo is_active_page('dashboard', $page); ?>" href="/index.php?page=dashboard"><i class="bi bi-speedometer2"></i> Inicio</a>

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
                    <?php if ($page === 'dashboard'): ?>
                        <section class="hero-panel">
                            <div>
                                <h2>Panel de trabajo</h2>
                                <p>Accede rápidamente a la administración del laboratorio y a la emisión de certificados.</p>
                            </div>
                            <div class="hero-badge"><i class="bi bi-shield-check"></i> PHP 8 + Bootstrap</div>
                        </section>
                        <section class="grid-cards">
                            <?php echo render_card('Clientes', 'Alta, edición y consulta de clientes del laboratorio.'); ?>
                            <?php echo render_card('Especies', 'Listado y mantenimiento del catálogo de especies.'); ?>
                            <?php echo render_card('Certificados', 'Ingreso y preparación de Certificado general.'); ?>
                        </section>
                    <?php elseif ($page === 'clientes'): ?>
                        <section class="section-panel">
                            <?php if ($clientEditId !== null): ?>
                                <h2>Editar cliente <span class="badge bg-secondary fs-6">#<?php echo $clientEditId; ?></span></h2>
                                <p>Modificá los datos del cliente y guardá los cambios.</p>
                            <?php else: ?>
                                <h2>Alta de cliente</h2>
                                <p>Completa los datos básicos del cliente y guarda el registro en la base de datos <strong>laboratorio</strong>.</p>
                            <?php endif; ?>

                            <?php foreach ($errors as $error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endforeach; ?>

                            <?php if ($successMessage !== null): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>

                            <form method="post" class="row g-3 mt-1">
                                <?php if ($clientEditId !== null): ?>
                                    <input type="hidden" name="update_cliente" value="1">
                                    <input type="hidden" name="cliente_edit_id" value="<?php echo $clientEditId; ?>">
                                <?php else: ?>
                                    <input type="hidden" name="save_cliente" value="1">
                                <?php endif; ?>

                                <div class="col-12">
                                    <label for="nombre_cliente" class="form-label">Nombre del cliente *</label>
                                    <input type="text" class="form-control" id="nombre_cliente" name="nombre_cliente" maxlength="150" value="<?php echo htmlspecialchars($clientForm['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="telefono_cliente" class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" id="telefono_cliente" name="telefono_cliente" maxlength="30" value="<?php echo htmlspecialchars($clientForm['telefono_cliente'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>

                                <div class="col-md-6">
                                    <label for="cuit_cliente" class="form-label">CUIT</label>
                                    <input type="text" class="form-control" id="cuit_cliente" name="cuit_cliente" maxlength="13" value="<?php echo htmlspecialchars($clientForm['cuit_cliente'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>

                                <div class="col-12">
                                    <label for="direccion_cliente" class="form-label">Dirección</label>
                                    <input type="text" class="form-control" id="direccion_cliente" name="direccion_cliente" maxlength="255" value="<?php echo htmlspecialchars($clientForm['direccion_cliente'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>

                                <div class="col-12">
                                    <label for="mail_cliente" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="mail_cliente" name="mail_cliente" maxlength="150" value="<?php echo htmlspecialchars($clientForm['mail_cliente'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>

                                <div class="col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-brand"><?php echo $clientEditId !== null ? 'Actualizar cliente' : 'Guardar cliente'; ?></button>
                                    <a href="/index.php?page=clientes" class="btn btn-outline-brand">Cancelar</a>
                                </div>
                            </form>
                        </section>

                        <section class="section-panel">
                            <h2>Clientes cargados</h2>
                            <p>Listado de registros existentes en la tabla clientes.</p>

                            <div class="table-responsive">
                                <table class="table table-dark table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Teléfono</th>
                                            <th>Dirección</th>
                                            <th>Email</th>
                                            <th>CUIT</th>
                                            <th>Alta</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($clientes === []): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">No hay clientes cargados aún.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($clientes as $cliente): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars((string) $cliente['id_cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) $cliente['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($cliente['telefono_cliente'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($cliente['direccion_cliente'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($cliente['mail_cliente'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($cliente['cuit_cliente'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($cliente['created_at_cliente'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="text-nowrap">
                                                        <a href="/index.php?page=clientes&edit=<?php echo urlencode((string) $cliente['id_cliente']); ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-pencil"></i> Editar</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php elseif ($page === 'especies'): ?>
                        <section class="section-panel">
                            <?php if ($speciesEditId !== null): ?>
                                <h2>Editar especie <span class="badge bg-secondary fs-6">#<?php echo $speciesEditId; ?></span></h2>
                                <p>Modificá los datos de la especie y guardá los cambios.</p>
                            <?php else: ?>
                                <h2>Alta de especie</h2>
                                <p>Completa los datos de la especie y guarda el registro en la tabla <strong>especies</strong>.</p>
                            <?php endif; ?>

                            <?php foreach ($errors as $error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endforeach; ?>

                            <?php if ($successMessage !== null): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>

                            <form method="post" class="row g-3 mt-1">
                                <?php if ($speciesEditId !== null): ?>
                                    <input type="hidden" name="update_especie" value="1">
                                    <input type="hidden" name="especie_edit_id" value="<?php echo $speciesEditId; ?>">
                                <?php else: ?>
                                    <input type="hidden" name="save_especie" value="1">
                                <?php endif; ?>

                                <div class="col-12">
                                    <label for="nombre_especie" class="form-label">Nombre de la especie *</label>
                                    <input type="text" class="form-control" id="nombre_especie" name="nombre_especie" maxlength="150" value="<?php echo htmlspecialchars($speciesForm['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>

                                <div class="col-12">
                                    <label for="cientifico_especie" class="form-label">Nombre cientifico</label>
                                    <input type="text" class="form-control" id="cientifico_especie" name="cientifico_especie" maxlength="255" value="<?php echo htmlspecialchars($speciesForm['cientifico_especie'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>

                                <div class="col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-brand"><?php echo $speciesEditId !== null ? 'Actualizar especie' : 'Guardar especie'; ?></button>
                                    <a href="/index.php?page=especies" class="btn btn-outline-brand">Cancelar</a>
                                </div>
                            </form>
                        </section>

                        <section class="section-panel">
                            <h2>Especies cargadas</h2>
                            <p>Listado de registros existentes en la tabla especies.</p>

                            <div class="table-responsive">
                                <table class="table table-dark table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Nombre cientifico</th>
                                            <th>Alta</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($especies === []): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">No hay especies cargadas aún.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($especies as $especie): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars((string) $especie['id_especie'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) $especie['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($especie['cientifico_especie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($especie['created_at_especie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="text-nowrap">
                                                        <a href="/index.php?page=especies&edit=<?php echo urlencode((string) $especie['id_especie']); ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-pencil"></i> Editar</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php elseif ($page === 'cultivares'): ?>
                        <section class="section-panel">
                            <?php if ($cultivarEditId !== null): ?>
                                <h2>Editar cultivar <span class="badge bg-secondary fs-6">#<?php echo $cultivarEditId; ?></span></h2>
                                <p>Modificá la especie o el nombre del cultivar y guardá los cambios.</p>
                            <?php else: ?>
                                <h2>Alta de cultivar</h2>
                                <p>Selecciona una especie y registra el cultivar en la tabla <strong>cultivares</strong>.</p>
                            <?php endif; ?>

                            <?php foreach ($errors as $error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endforeach; ?>

                            <?php if ($successMessage !== null): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>

                            <?php if ($speciesOptions === []): ?>
                                <div class="alert alert-warning mb-0">Primero debes cargar al menos una especie para poder dar de alta cultivares.</div>
                            <?php else: ?>
                                <form method="post" class="row g-3 mt-1">
                                    <?php if ($cultivarEditId !== null): ?>
                                        <input type="hidden" name="update_cultivar" value="1">
                                        <input type="hidden" name="cultivar_edit_id" value="<?php echo $cultivarEditId; ?>">
                                    <?php else: ?>
                                        <input type="hidden" name="save_cultivar" value="1">
                                    <?php endif; ?>

                                    <div class="col-md-5">
                                        <label for="id_especie" class="form-label">Especie *</label>
                                        <select class="form-select" id="id_especie" name="id_especie" required>
                                            <option value="">Selecciona una especie</option>
                                            <?php foreach ($speciesOptions as $speciesOption): ?>
                                                <option value="<?php echo htmlspecialchars((string) $speciesOption['id_especie'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $cultivarForm['id_especie'] === (string) $speciesOption['id_especie'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars((string) $speciesOption['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-7">
                                        <label for="nombre_cultivar" class="form-label">Nombre del cultivar *</label>
                                        <input type="text" class="form-control" id="nombre_cultivar" name="nombre_cultivar" maxlength="150" value="<?php echo htmlspecialchars($cultivarForm['nombre_cultivar'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>

                                    <div class="col-12 d-flex gap-2">
                                        <button type="submit" class="btn btn-brand"><?php echo $cultivarEditId !== null ? 'Actualizar cultivar' : 'Guardar cultivar'; ?></button>
                                        <a href="/index.php?page=cultivares" class="btn btn-outline-brand">Cancelar</a>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </section>

                        <section class="section-panel">
                            <h2>Cultivares cargados</h2>
                            <p>Listado de cultivares vinculados a su especie.</p>

                            <div class="table-responsive">
                                <table class="table table-dark table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Especie</th>
                                            <th>Cultivar</th>
                                            <th>Alta</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($cultivares === []): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">No hay cultivares cargados aún.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($cultivares as $cultivar): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars((string) $cultivar['id_cultivar'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) $cultivar['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) $cultivar['nombre_cultivar'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($cultivar['created_at_cultivar'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="text-nowrap">
                                                        <a href="/index.php?page=cultivares&edit=<?php echo urlencode((string) $cultivar['id_cultivar']); ?>" class="btn btn-sm btn-outline-light me-1"><i class="bi bi-pencil"></i> Editar</a>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar el cultivar &quot;<?php echo htmlspecialchars(addslashes((string) $cultivar['nombre_cultivar']), ENT_QUOTES, 'UTF-8'); ?>&quot;? Esta acción no se puede deshacer.')">
                                                            <input type="hidden" name="delete_cultivar" value="1">
                                                            <input type="hidden" name="cultivar_delete_id" value="<?php echo htmlspecialchars((string) $cultivar['id_cultivar'], ENT_QUOTES, 'UTF-8'); ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Eliminar</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php elseif ($page === 'otras_determinaciones'): ?>
                        <section class="section-panel">
                            <?php if ($detEditId !== null): ?>
                                <h2>Editar determinación <span class="badge bg-secondary fs-6">#<?php echo $detEditId; ?></span></h2>
                                <p>Modificá el nombre de la determinación y guardá los cambios.</p>
                            <?php else: ?>
                                <h2>Alta de determinación</h2>
                                <p>Registrá una nueva determinación en la tabla <strong>otras_determinaciones</strong>.</p>
                            <?php endif; ?>

                            <?php foreach ($errors as $error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endforeach; ?>

                            <?php if ($successMessage !== null): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>

                            <form method="post" class="row g-3 mt-1">
                                <?php if ($detEditId !== null): ?>
                                    <input type="hidden" name="update_determinacion" value="1">
                                    <input type="hidden" name="det_edit_id" value="<?php echo $detEditId; ?>">
                                <?php else: ?>
                                    <input type="hidden" name="save_determinacion" value="1">
                                <?php endif; ?>

                                <div class="col-12">
                                    <label for="nombre_determinacion" class="form-label">Nombre de la determinación *</label>
                                    <input type="text" class="form-control" id="nombre_determinacion" name="nombre_determinacion" maxlength="255" value="<?php echo htmlspecialchars($detForm['nombre_determinacion'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>

                                <div class="col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-brand"><?php echo $detEditId !== null ? 'Actualizar determinación' : 'Guardar determinación'; ?></button>
                                    <a href="/index.php?page=otras_determinaciones" class="btn btn-outline-brand">Cancelar</a>
                                </div>
                            </form>
                        </section>

                        <section class="section-panel">
                            <h2>Determinaciones cargadas</h2>
                            <p>Listado de registros existentes en la tabla otras_determinaciones.</p>

                            <div class="table-responsive">
                                <table class="table table-dark table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Alta</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($detList === []): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No hay determinaciones cargadas aún.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($detList as $det): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars((string) $det['id_determinacion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) $det['nombre_determinacion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($det['created_at_determinacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="text-nowrap">
                                                        <a href="/index.php?page=otras_determinaciones&edit=<?php echo urlencode((string) $det['id_determinacion']); ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-pencil"></i> Editar</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php elseif ($page === 'usuarios'): ?>
                        <section class="section-panel">
                            <?php if ($userEditId !== null): ?>
                                <h2>Editar usuario <span class="badge bg-secondary fs-6">#<?php echo $userEditId; ?></span></h2>
                                <p>Modificá los datos del usuario. Dejá la contraseña en blanco para mantener la actual.</p>
                            <?php else: ?>
                                <h2>Alta de usuario</h2>
                                <p>Completa los datos y guarda el registro en la tabla <strong>usuarios</strong>.</p>
                            <?php endif; ?>

                            <?php foreach ($errors as $error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endforeach; ?>

                            <?php if ($successMessage !== null): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>

                            <form method="post" class="row g-3 mt-1">
                                <?php if ($userEditId !== null): ?>
                                    <input type="hidden" name="update_usuario" value="1">
                                    <input type="hidden" name="usuario_edit_id" value="<?php echo $userEditId; ?>">
                                <?php else: ?>
                                    <input type="hidden" name="save_usuario" value="1">
                                <?php endif; ?>

                                <div class="col-md-6">
                                    <label for="nombre_usuario" class="form-label">Nombre completo *</label>
                                    <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" maxlength="150" value="<?php echo htmlspecialchars($userForm['nombre_usuario'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="username_usuario" class="form-label">Usuario *</label>
                                    <input type="text" class="form-control" id="username_usuario" name="username_usuario" maxlength="60" value="<?php echo htmlspecialchars($userForm['username_usuario'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="email_usuario" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email_usuario" name="email_usuario" maxlength="150" value="<?php echo htmlspecialchars($userForm['email_usuario'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>

                                <div class="col-md-6">
                                    <label for="password_usuario" class="form-label">Contraseña <?php echo $userEditId !== null ? '(dejar en blanco para no cambiarla)' : '*'; ?></label>
                                    <input type="password" class="form-control" id="password_usuario" name="password_usuario" autocomplete="new-password" <?php echo $userEditId === null ? 'required' : ''; ?>>
                                </div>

                                <div class="col-12">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="activo_usuario" name="activo_usuario" value="1" <?php echo $userForm['activo_usuario'] === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="activo_usuario">Usuario activo</label>
                                    </div>
                                </div>

                                <div class="col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-brand"><?php echo $userEditId !== null ? 'Actualizar usuario' : 'Guardar usuario'; ?></button>
                                    <a href="/index.php?page=usuarios" class="btn btn-outline-brand">Cancelar</a>
                                </div>
                            </form>
                        </section>

                        <section class="section-panel">
                            <h2>Usuarios cargados</h2>
                            <p>Listado de registros existentes en la tabla usuarios.</p>

                            <div class="table-responsive">
                                <table class="table table-dark table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Usuario</th>
                                            <th>Email</th>
                                            <th>Estado</th>
                                            <th>Alta</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($usuarios === []): ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">No hay usuarios cargados aún.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($usuarios as $usuario): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars((string) $usuario['id_usuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) $usuario['nombre_usuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) $usuario['username_usuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($usuario['email_usuario'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo ((int) $usuario['activo_usuario'] === 1) ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>'; ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($usuario['created_at_usuario'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="text-nowrap">
                                                        <a href="/index.php?page=usuarios&edit=<?php echo urlencode((string) $usuario['id_usuario']); ?>" class="btn btn-sm btn-outline-light me-1"><i class="bi bi-pencil"></i> Editar</a>
                                                        <?php if ((int) $usuario['id_usuario'] !== ($_SESSION['user']['id'] ?? null)): ?>
                                                            <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar el usuario &quot;<?php echo htmlspecialchars(addslashes((string) $usuario['username_usuario']), ENT_QUOTES, 'UTF-8'); ?>&quot;? Esta acción no se puede deshacer.')">
                                                                <input type="hidden" name="delete_usuario" value="1">
                                                                <input type="hidden" name="usuario_delete_id" value="<?php echo htmlspecialchars((string) $usuario['id_usuario'], ENT_QUOTES, 'UTF-8'); ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Eliminar</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php elseif ($page === 'configuracion'): ?>
                        <section class="section-panel">
                            <h2>Configuración</h2>
                            <p>Información de tu laboratorio en el sistema.</p>

                            <?php foreach ($errors as $error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endforeach; ?>

                            <dl class="row mb-0">
                                <dt class="col-sm-4">Carpeta de guardado de certificados</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars(empresa_storage_dir(current_empresa_id()), ENT_QUOTES, 'UTF-8'); ?></dd>
                            </dl>
                            <p class="text-muted small mb-0">Cada laboratorio tiene su propia carpeta de almacenamiento asignada automáticamente; no es configurable manualmente por razones de seguridad.</p>
                        </section>
                    <?php elseif ($page === 'empresas'): ?>
                        <section class="section-panel">
                            <?php if ($empresaEditId !== null): ?>
                                <h2>Editar empresa <span class="badge bg-secondary fs-6">#<?php echo $empresaEditId; ?></span></h2>
                                <p>Modificá el nombre o el estado de la empresa.</p>
                            <?php else: ?>
                                <h2>Alta de empresa</h2>
                                <p>Creá un nuevo laboratorio (tenant) junto con su primer usuario administrador.</p>
                            <?php endif; ?>

                            <?php foreach ($errors as $error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endforeach; ?>

                            <?php if ($successMessage !== null): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>

                            <?php if ($empresaEditId !== null): ?>
                                <form method="post" class="row g-3 mt-1">
                                    <input type="hidden" name="update_empresa" value="1">
                                    <input type="hidden" name="empresa_edit_id" value="<?php echo $empresaEditId; ?>">

                                    <div class="col-md-8">
                                        <label for="nombre_empresa" class="form-label">Nombre de la empresa *</label>
                                        <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" maxlength="150" value="<?php echo htmlspecialchars($empresaForm['nombre_empresa'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="activo_empresa" name="activo_empresa" value="1" <?php echo $empresaForm['activo_empresa'] === '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="activo_empresa">Empresa activa</label>
                                        </div>
                                    </div>

                                    <div class="col-12 d-flex gap-2">
                                        <button type="submit" class="btn btn-brand">Actualizar empresa</button>
                                        <a href="/index.php?page=empresas" class="btn btn-outline-brand">Cancelar</a>
                                    </div>
                                </form>
                            <?php else: ?>
                                <form method="post" class="row g-3 mt-1">
                                    <input type="hidden" name="save_empresa" value="1">

                                    <div class="col-12">
                                        <label for="nombre_empresa" class="form-label">Nombre de la empresa *</label>
                                        <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" maxlength="150" value="<?php echo htmlspecialchars($empresaForm['nombre_empresa'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>

                                    <div class="col-12">
                                        <hr>
                                        <h3 class="h6">Primer usuario administrador</h3>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="admin_nombre_usuario" class="form-label">Nombre completo *</label>
                                        <input type="text" class="form-control" id="admin_nombre_usuario" name="admin_nombre_usuario" maxlength="150" value="<?php echo htmlspecialchars($empresaAdminForm['nombre_usuario'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="admin_username_usuario" class="form-label">Usuario *</label>
                                        <input type="text" class="form-control" id="admin_username_usuario" name="admin_username_usuario" maxlength="60" value="<?php echo htmlspecialchars($empresaAdminForm['username_usuario'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="admin_email_usuario" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="admin_email_usuario" name="admin_email_usuario" maxlength="150" value="<?php echo htmlspecialchars($empresaAdminForm['email_usuario'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="admin_password_usuario" class="form-label">Contraseña *</label>
                                        <input type="password" class="form-control" id="admin_password_usuario" name="admin_password_usuario" autocomplete="new-password" required>
                                    </div>

                                    <div class="col-12 d-flex gap-2">
                                        <button type="submit" class="btn btn-brand">Crear empresa</button>
                                        <a href="/index.php?page=empresas" class="btn btn-outline-brand">Cancelar</a>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </section>

                        <section class="section-panel">
                            <h2>Empresas cargadas</h2>
                            <p>Listado de laboratorios (tenants) registrados en el sistema.</p>

                            <div class="table-responsive">
                                <table class="table table-dark table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Estado</th>
                                            <th>Alta</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($empresas === []): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">No hay empresas cargadas aún.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($empresas as $empresa): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars((string) $empresa['id_empresa'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) $empresa['nombre_empresa'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo ((int) $empresa['activo_empresa'] === 1) ? '<span class="badge bg-success">Activa</span>' : '<span class="badge bg-secondary">Inactiva</span>'; ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($empresa['created_at_empresa'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="text-nowrap">
                                                        <a href="/index.php?page=empresas&edit=<?php echo urlencode((string) $empresa['id_empresa']); ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-pencil"></i> Editar</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php elseif ($page === 'Certificadogeneral'): ?>
                        <section class="section-panel">
                            <h2>Alta de certificado</h2>
                            <p>Completa los datos del análisis y guarda el certificado.</p>

                            <?php foreach ($errors as $error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endforeach; ?>

                            <?php if ($successMessage !== null): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>

                            <?php if ($newCertificadoId !== null): ?>
                                <script>
                                    window.open('/index.php?pdf=<?php echo urlencode((string) $newCertificadoId); ?>', '_blank', 'noopener,noreferrer');
                                </script>
                            <?php endif; ?>

                            <?php if ($clientesOptions === [] || $especiesOptions === [] || $cultivaresOptions === []): ?>
                                <div class="alert alert-warning mb-0">Primero debes cargar al menos un cliente, una especie y un cultivar para poder dar de alta certificados.</div>
                            <?php else: ?>
                                <form method="post" class="row g-3 mt-1">
                                    <input type="hidden" name="save_certificado" value="1">
                                    <input type="hidden" name="tipo_certificado" value="certificadogeneral">

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

                                    <div class="col-md-4">
                                        <label for="id_cultivar_certificado" class="form-label">Cultivar *</label>
                                        <select class="form-select" id="id_cultivar_certificado" name="id_cultivar_certificado" required>
                                            <option value="">Selecciona un cultivar</option>
                                            <?php foreach ($cultivaresOptions as $cultivar): ?>
                                                <option value="<?php echo htmlspecialchars((string) $cultivar['id_cultivar'], ENT_QUOTES, 'UTF-8'); ?>" data-especie="<?php echo htmlspecialchars((string) $cultivar['id_especie'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $certForm['id_cultivar_certificado'] === (string) $cultivar['id_cultivar'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars((string) $cultivar['nombre_cultivar'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <hr class="my-4">
                                    <h5>Información del Lote</h5>

                                    <div class="col-md-4">
                                        <label for="marca_lote_certificado" class="form-label">Marca/Lote</label>
                                        <input type="text" class="form-control" id="marca_lote_certificado" name="marca_lote_certificado" maxlength="255" value="<?php echo htmlspecialchars($certForm['marca_lote_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="peso_certificado" class="form-label">Peso (kg)</label>
                                        <input type="number" class="form-control" id="peso_certificado" name="peso_certificado" step="0.01" value="<?php echo htmlspecialchars($certForm['peso_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="nro_envases_certificado" class="form-label">Nro de envases</label>
                                        <input type="number" class="form-control" id="nro_envases_certificado" name="nro_envases_certificado" value="<?php echo htmlspecialchars($certForm['nro_envases_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
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
                                        <label for="nro_analisis_certificado" class="form-label">N° de Análisis</label>
                                        <input type="text" class="form-control" id="nro_analisis_certificado" name="nro_analisis_certificado" maxlength="100" value="<?php echo htmlspecialchars($certForm['nro_analisis_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <hr class="my-4">
                                    <h5>Fechas</h5>

                                    <div class="col-md-3">
                                        <label for="fecha_muestreo_certificado" class="form-label">Fecha de muestreo</label>
                                        <input type="date" class="form-control" id="fecha_muestreo_certificado" name="fecha_muestreo_certificado" value="<?php echo htmlspecialchars($certForm['fecha_muestreo_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="fecha_muestras_certificado" class="form-label">Fecha de recepción de muestras</label>
                                        <input type="date" class="form-control" id="fecha_muestras_certificado" name="fecha_muestras_certificado" value="<?php echo htmlspecialchars($certForm['fecha_muestras_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="fecha_finalizacion_ensayos_certificado" class="form-label">Fecha finalización de ensayos</label>
                                        <input type="date" class="form-control" id="fecha_finalizacion_ensayos_certificado" name="fecha_finalizacion_ensayos_certificado" value="<?php echo htmlspecialchars($certForm['fecha_finalizacion_ensayos_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <hr class="my-4">
                                    <h5>Pureza (%)</h5>

                                    <div class="col-md-3">
                                        <label for="pureza_semilla_pura_certificado" class="form-label">Semilla pura</label>
                                        <input type="number" class="form-control" id="pureza_semilla_pura_certificado" name="pureza_semilla_pura_certificado" step="0.01" value="<?php echo htmlspecialchars($certForm['pureza_semilla_pura_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="materia_inerte_certificado" class="form-label">Materia inerte</label>
                                        <input type="number" class="form-control" id="materia_inerte_certificado" name="materia_inerte_certificado" step="0.01" value="<?php echo htmlspecialchars($certForm['materia_inerte_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="pureza_otras_semillas_certificado" class="form-label">Otras semillas</label>
                                        <input type="number" class="form-control" id="pureza_otras_semillas_certificado" name="pureza_otras_semillas_certificado" step="0.01" value="<?php echo htmlspecialchars($certForm['pureza_otras_semillas_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <hr class="my-4">
                                    <h5>Poder Germinativo</h5>

                                    <div class="col-md-2">
                                        <label for="pg_numero_dias_certificado" class="form-label">Días</label>
                                        <input type="number" class="form-control" id="pg_numero_dias_certificado" name="pg_numero_dias_certificado" value="<?php echo htmlspecialchars($certForm['pg_numero_dias_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="pg_plantulas_normales_certificado" class="form-label">Plántulas normales</label>
                                        <input type="number" class="form-control" id="pg_plantulas_normales_certificado" name="pg_plantulas_normales_certificado" step="0.01" value="<?php echo htmlspecialchars($certForm['pg_plantulas_normales_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="pg_semillas_duras_certificado" class="form-label">Semillas duras</label>
                                        <input type="number" class="form-control" id="pg_semillas_duras_certificado" name="pg_semillas_duras_certificado" step="0.01" value="<?php echo htmlspecialchars($certForm['pg_semillas_duras_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="pg_semillas_frescas_certificado" class="form-label">Semillas frescas</label>
                                        <input type="number" class="form-control" id="pg_semillas_frescas_certificado" name="pg_semillas_frescas_certificado" step="0.01" value="<?php echo htmlspecialchars($certForm['pg_semillas_frescas_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="pg_plantulas_anormales_certificado" class="form-label">Plántulas anormales</label>
                                        <input type="number" class="form-control" id="pg_plantulas_anormales_certificado" name="pg_plantulas_anormales_certificado" step="0.01" value="<?php echo htmlspecialchars($certForm['pg_plantulas_anormales_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="pg_semillas_muertas_certificado" class="form-label">Semillas muertas</label>
                                        <input type="number" class="form-control" id="pg_semillas_muertas_certificado" name="pg_semillas_muertas_certificado" step="0.01" value="<?php echo htmlspecialchars($certForm['pg_semillas_muertas_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-12">
                                        <label for="pg_contenido_humedad_certificado" class="form-label">Contenido humedad (%)</label>
                                        <input type="number" class="form-control" id="pg_contenido_humedad_certificado" name="pg_contenido_humedad_certificado" step="0.01" value="<?php echo htmlspecialchars($certForm['pg_contenido_humedad_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <hr class="my-4">
                                    <h5>Poder Germinativo Curado</h5>

                                    <div class="col-md-2">
                                        <label for="pgc_numero_dias_certificado" class="form-label">Días</label>
                                        <input type="number" class="form-control" id="pgc_numero_dias_certificado" name="pgc_numero_dias_certificado" value="<?php echo htmlspecialchars($certForm['pgc_numero_dias_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="pgc_plantulas_normales_certificado" class="form-label">Plántulas normales</label>
                                        <input type="number" class="form-control" id="pgc_plantulas_normales_certificado" name="pgc_plantulas_normales_certificado" step="0.01" value="<?php echo htmlspecialchars($certForm['pgc_plantulas_normales_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="pgc_semillas_duras_certificado" class="form-label">Semillas duras</label>
                                        <input type="number" class="form-control" id="pgc_semillas_duras_certificado" name="pgc_semillas_duras_certificado" step="0.01" value="<?php echo htmlspecialchars($certForm['pgc_semillas_duras_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="pgc_semillas_frescas_certificado" class="form-label">Semillas frescas</label>
                                        <input type="number" class="form-control" id="pgc_semillas_frescas_certificado" name="pgc_semillas_frescas_certificado" step="0.01" value="<?php echo htmlspecialchars($certForm['pgc_semillas_frescas_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="pgc_plantulas_anormales_certificado" class="form-label">Plántulas anormales</label>
                                        <input type="number" class="form-control" id="pgc_plantulas_anormales_certificado" name="pgc_plantulas_anormales_certificado" step="0.01" value="<?php echo htmlspecialchars($certForm['pgc_plantulas_anormales_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="pgc_semillas_muertas_certificado" class="form-label">Semillas muertas</label>
                                        <input type="number" class="form-control" id="pgc_semillas_muertas_certificado" name="pgc_semillas_muertas_certificado" step="0.01" value="<?php echo htmlspecialchars($certForm['pgc_semillas_muertas_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <hr class="my-4">
                                    <h5>Vigor</h5>

                                    <div class="col-md-6">
                                        <label for="vigor_nro_dias_certificado" class="form-label">Nro de días</label>
                                        <input type="number" class="form-control" id="vigor_nro_dias_certificado" name="vigor_nro_dias_certificado" value="<?php echo htmlspecialchars($certForm['vigor_nro_dias_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="vigor_plantulas_normales_certificado" class="form-label">Plántulas normales</label>
                                        <input type="number" class="form-control" id="vigor_plantulas_normales_certificado" name="vigor_plantulas_normales_certificado" step="0.01" value="<?php echo htmlspecialchars($certForm['vigor_plantulas_normales_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <hr class="my-4">

                                    <div class="col-md-6">
                                        <label for="clase_materia_inerte_certificado" class="form-label">Clase de Materia Inerte</label>
                                        <input type="text" class="form-control" id="clase_materia_inerte_certificado" name="clase_materia_inerte_certificado" maxlength="100" value="<?php echo htmlspecialchars($certForm['clase_materia_inerte_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="otras_semillas_certificado" class="form-label">Otras semillas</label>
                                        <input type="text" class="form-control" id="otras_semillas_certificado" name="otras_semillas_certificado" value="<?php echo htmlspecialchars($certForm['otras_semillas_certificado'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

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

                                    <div class="col-md-2">
                                        <label for="ampara_totalidad_lote_certificado" class="form-label">Ampara la totalidad del Lote?</label>
                                        <select class="form-select" id="ampara_totalidad_lote_certificado" name="ampara_totalidad_lote_certificado">
                                            <option value="S" <?php echo $certForm['ampara_totalidad_lote_certificado'] === 'S' ? 'selected' : ''; ?>>Si</option>
                                            <option value="N" <?php echo $certForm['ampara_totalidad_lote_certificado'] === 'N' ? 'selected' : ''; ?>>No</option>
                                        </select>
                                    </div>

                                    <div class="col-10">
                                        <label for="localidad_pais_certificado" class="form-label">Localidad/País</label>
                                        <input type="text" class="form-control" id="localidad_pais_certificado" name="localidad_pais_certificado" maxlength="255"
                                            value="<?php echo htmlspecialchars(!empty($certForm['localidad_pais_certificado']) ? $certForm['localidad_pais_certificado'] : 'Chivilcoy, Buenos Aires', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-2">
                                        <label for="fecha_emision_certificado" class="form-label">Fecha de emisión</label>
                                        <input type="date" class="form-control" id="fecha_emision_certificado" name="fecha_emision_certificado"
                                            value="<?php echo htmlspecialchars(!empty($certForm['fecha_emision_certificado']) ? $certForm['fecha_emision_certificado'] : date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-12 d-flex gap-2">
                                        <button type="submit" class="btn btn-brand">Guardar certificado</button>
                                        <a href="/index.php?page=Certificadogeneral" class="btn btn-outline-brand">Cancelar</a>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </section>

                        <section class="section-panel" id="certificados-listado">
                            <h2>Certificados cargados</h2>
                            <p>Listado de certificados guardados en la base de datos.</p>

                            <form method="get" class="row g-3 mb-3" action="/index.php#certificados-listado">
                                <input type="hidden" name="page" value="Certificadogeneral">

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
                                    <a href="/index.php?page=Certificadogeneral#certificados-listado" class="btn btn-outline-brand" title="Limpiar filtros"><i class="bi bi-x-circle"></i></a>
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
                                            $certPageQuery = array_merge($_GET, ['page' => 'Certificadogeneral', 'cert_page' => $certPageNumber]);
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
                    <?php else: ?>
                        <section class="section-panel">
                            <h2><?php echo htmlspecialchars($currentPage['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p>Sección inicial preparada para implementar formularios, listados y flujos de trabajo.</p>
                            <div class="empty-state">
                                <i class="bi bi-folder2-open"></i>
                                <span>Contenido pendiente de desarrollo</span>
                            </div>
                        </section>
                    <?php endif; ?>
                </main>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterCultivares() {
            const especieSelect = document.getElementById('id_especie_certificado');
            const cultivarSelect = document.getElementById('id_cultivar_certificado');
            const especieValue = especieSelect.value;

            if (!cultivarSelect) return;

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
            cultivarSelect.value = '';
        };

        // Reset selection if current value is hidden
        const selectedOption = cultivarSelect.options[cultivarSelect.selectedIndex];
        if (selectedOption && selectedOption.style.display === 'none') {
            cultivarSelect.value = '';
        }

        // Filter on page load
        document.addEventListener('DOMContentLoaded', filterCultivares);

        // Validación de porcentajes en Pureza, Poder Germinativo y Poder Germinativo Curado
        function validatePercentageFields(fieldIds, maxTotal = 100, groupName = 'Campos') {
            fieldIds.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (!field) return;

                field.addEventListener('input', function() {
                    const value = parseFloat(this.value) || 0;

                    // Crear elemento de error si no existe
                    let errorElement = this.parentElement.querySelector('.invalid-feedback-custom');
                    if (!errorElement) {
                        errorElement = document.createElement('div');
                        errorElement.className = 'invalid-feedback-custom d-block mt-1 small text-danger';
                        this.parentElement.appendChild(errorElement);
                    }

                    // Validar que no supere 100 individualmente
                    if (value > 100) {
                        this.classList.add('is-invalid');
                        errorElement.textContent = 'El valor no puede superar 100';
                        return;
                    }

                    // Validar la suma total sea 100 o 0
                    let total = 0;
                    fieldIds.forEach(id => {
                        const field = document.getElementById(id);
                        if (field) {
                            total += parseFloat(field.value) || 0;
                        }
                    });

                    if (Math.abs(total - maxTotal) > 0.01 && Math.abs(total) > 0.01) {
                        this.classList.add('is-invalid');
                        errorElement.textContent = `La suma total de ${groupName} debe ser ${maxTotal} o 0 (actual: ${total.toFixed(2)})`;
                    } else {
                        this.classList.remove('is-invalid');
                        errorElement.textContent = '';
                    }
                });

                // Validar al perder el foco también
                field.addEventListener('blur', function() {
                    const value = parseFloat(this.value) || 0;
                    let errorElement = this.parentElement.querySelector('.invalid-feedback-custom');
                    if (!errorElement) {
                        errorElement = document.createElement('div');
                        errorElement.className = 'invalid-feedback-custom d-block mt-1 small text-danger';
                        this.parentElement.appendChild(errorElement);
                    }

                    if (value > 100) {
                        this.classList.add('is-invalid');
                        errorElement.textContent = 'El valor no puede superar 100';
                        return;
                    }

                    let total = 0;
                    fieldIds.forEach(id => {
                        const field = document.getElementById(id);
                        if (field) {
                            total += parseFloat(field.value) || 0;
                        }
                    });

                    if (Math.abs(total - maxTotal) > 0.01 && Math.abs(total) > 0.01) {
                        this.classList.add('is-invalid');
                        errorElement.textContent = `La suma total de ${groupName} debe ser ${maxTotal} o 0 (actual: ${total.toFixed(2)})`;
                    } else {
                        this.classList.remove('is-invalid');
                        errorElement.textContent = '';
                    }
                });
            });
        }

        // Validación para Pureza (%)
        validatePercentageFields([
            'pureza_semilla_pura_certificado',
            'materia_inerte_certificado',
            'pureza_otras_semillas_certificado'
        ], 100, 'Pureza');

        // Validación para Poder Germinativo
        validatePercentageFields([
            'pg_plantulas_normales_certificado',
            'pg_semillas_duras_certificado',
            'pg_semillas_frescas_certificado',
            'pg_plantulas_anormales_certificado',
            'pg_semillas_muertas_certificado'
        ], 100, 'Poder Germinativo');

        // Validación para Poder Germinativo Curado
        validatePercentageFields([
            'pgc_plantulas_normales_certificado',
            'pgc_semillas_duras_certificado',
            'pgc_semillas_frescas_certificado',
            'pgc_plantulas_anormales_certificado',
            'pgc_semillas_muertas_certificado'
        ], 100, 'Poder Germinativo Curado');

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