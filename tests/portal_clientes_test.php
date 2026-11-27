<?php

declare(strict_types=1);

$caso = $argv[1] ?? '';
if ($caso === '') {
    $casos = ['consultas', 'alta_usuario', 'editar_usuario', 'cliente_ajeno', 'login', 'login_sin_cliente',
        'index_usuarios', 'index_usuario_editar', 'index_pdf_real',
        'index_tipo_todos',
        'publicar', 'retirar', 'publicar_ajeno', 'publicar_token', 'index_cliente', 'index_interno',
        'index_post', 'index_admin', 'index_pdf_legacy', 'sesion_desactivada', 'sesion_cliente_eliminado',
        'pdf_ajeno', 'pdf_no_publicado', 'pdf_empresa_ajena'];
    require_once __DIR__ . '/../includes/portal_clientes.php';
    require_once __DIR__ . '/../config/config.php';
    foreach (array_keys(portal_tipos_documentos()) as $tipo) {
        $casos[] = 'pdf_' . $tipo;
    }
    foreach ($casos as $escenario) {
        $schema = 'portal_test_' . bin2hex(random_bytes(12));
        db()->exec("CREATE DATABASE `$schema`");
        try {
            $process = proc_open([PHP_BINARY, __FILE__, $escenario, $schema], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            if (!is_resource($process)) {
                throw new RuntimeException('No se pudo iniciar la prueba.');
            }
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            if (proc_close($process) !== 0 || !str_contains($output, 'OK:') || $error !== '') {
                throw new RuntimeException("Falló $escenario: $output$error");
            }
        } finally {
            db()->exec("DROP DATABASE `$schema`");
        }
    }
    echo "OK: portal, cuentas, publicación, once descargas y aislamiento de accesos.\n";
    return;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/portal_clientes.php';
$connection = db();
$tipos = portal_tipos_documentos();
$tablas = ['usuarios', 'clientes', 'empresas', 'portal_documentos', 'especies', 'cultivares', 'certificado_determinaciones', 'otras_determinaciones'];
foreach ($tipos as $def) {
    $tablas[] = $def[1];
}
$definitions = [];
foreach (array_unique($tablas) as $tabla) {
    $definition = $connection->query("SHOW CREATE TABLE $tabla")->fetch(PDO::FETCH_NUM)[1];
    $definition = preg_replace('/,\s*CONSTRAINT\s+`[^`]+`[^\n,]*/', '', $definition);
    $definitions[] = $definition;
}
$schema = $argv[2] ?? '';
comprobar_portal(preg_match('/^portal_test_[a-f0-9]{24}$/', $schema) === 1, 'Se requiere una base aislada creada por la prueba.');
$connection->exec("USE `$schema`");
foreach ($definitions as $definition) {
    $connection->exec($definition);
}

function portal_test_insert(string $table, array $values): void
{
    foreach (db()->query("SHOW COLUMNS FROM $table") as $column) {
        $key = $column['Field'];
        if (array_key_exists($key, $values) || $column['Null'] === 'YES' || $column['Default'] !== null || str_contains($column['Extra'], 'auto_increment')) {
            continue;
        }
        $type = $column['Type'];
        $values[$key] = match (true) {
            str_starts_with($type, 'enum(') => explode("'", $type)[1],
            preg_match('/int|decimal|float|double/', $type) === 1 => 0,
            $type === 'date' => '2026-11-25',
            default => 'Prueba',
        };
    }
    $columns = implode(', ', array_keys($values));
    $markers = implode(', ', array_fill(0, count($values), '?'));
    db()->prepare("INSERT INTO $table ($columns) VALUES ($markers)")->execute(array_values($values));
}

function comprobar_portal(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

portal_test_insert('empresas', ['id_empresa' => 1, 'nombre_empresa' => 'Laboratorio', 'activo_empresa' => 1]);
portal_test_insert('empresas', ['id_empresa' => 2, 'nombre_empresa' => 'Otro', 'activo_empresa' => 1]);
foreach ([1 => 1, 2 => 1, 3 => 2] as $id => $empresa) {
    portal_test_insert('clientes', ['id_cliente' => $id, 'id_empresa' => $empresa, 'nombre_cliente' => 'Cliente ' . $id]);
}
portal_test_insert('usuarios', ['id_usuario' => 3, 'id_empresa' => 1, 'username_usuario' => 'clienteprueba', 'tipo_usuario' => 'cliente', 'id_cliente_usuario' => 1, 'activo_usuario' => 1, 'password_hash_usuario' => password_hash('prueba123', PASSWORD_BCRYPT)]);
portal_test_insert('usuarios', ['id_usuario' => 4, 'id_empresa' => 1, 'username_usuario' => 'internoprueba', 'tipo_usuario' => 'interno', 'activo_usuario' => 1, 'es_superadmin' => 1]);

$ids = [];
$id = 100;
foreach ($tipos as $tipo => [$label, $table, $key, $client, $ref, $date]) {
    $ids[$tipo] = $id++;
    $values = [$key => $ids[$tipo], 'id_empresa' => 1, $client => 1, $ref => 'DOC-' . $tipo, $date => '2026-11-25'];
    if ($table === 'certificados') {
        $values['tipo_certificado'] = $tipo;
    } elseif ($table === 'boletines_germinacion') {
        $values['tipo_boletin'] = $tipo;
    }
    portal_test_insert($table, $values);
    $connection->prepare('INSERT INTO portal_documentos (id_empresa, tipo_documento, id_documento) VALUES (1, ?, ?)')->execute([$tipo, $ids[$tipo]]);
}
foreach ([201 => [1, 2], 202 => [1, 1], 203 => [2, 3]] as $id => [$empresa, $cliente]) {
    portal_test_insert('certificados', ['id_certificado' => $id, 'id_empresa' => $empresa, 'id_cliente_certificado' => $cliente, 'tipo_certificado' => 'certificadogeneral', 'nro_analisis_certificado' => 'OCULTO-' . $id]);
    if ($id !== 202) {
        $connection->prepare("INSERT INTO portal_documentos (id_empresa, tipo_documento, id_documento) VALUES (?, 'certificadogeneral', ?)")->execute([$empresa, $id]);
    }
}
$_SESSION['user'] = ['id' => 3, 'id_empresa' => 1, 'tipo_usuario' => 'cliente', 'id_cliente' => 1, 'name' => 'Cliente', 'es_superadmin' => false];
$_SESSION['portal_token'] = 'token-prueba';
$_GET = [];
$_POST = [];
$_SERVER['REQUEST_METHOD'] = 'GET';
$errors = [];
$successMessage = null;
$realPdfPath = null;

if ($caso === 'consultas') {
    [$sql, $params] = portal_documentos_consulta(null, 1, true);
    $statement = $connection->prepare($sql);
    $statement->execute($params);
    comprobar_portal(count($statement->fetchAll()) === 11, 'El portal debe ofrecer exactamente los once documentos propios publicados.');
    foreach ($ids as $tipo => $id) {
        comprobar_portal(portal_documento($tipo, $id, 1, true) !== null, 'Documento propio ausente: ' . $tipo);
        comprobar_portal(portal_documento($tipo, $id, 2, true) === null, 'No debe compartir con otro cliente: ' . $tipo);
    }
    [$sql, $params] = portal_documentos_consulta(null, 1, true, 'certificado', '2026-11-25', '2026-11-25');
    $statement = $connection->prepare($sql);
    $statement->execute($params);
    $certificadosFiltrados = $statement->fetchAll();
    comprobar_portal(count($certificadosFiltrados) === 3, 'El filtro debe devolver certificados de la fecha elegida.');
    comprobar_portal(portal_tipo_categoria($certificadosFiltrados[0]['tipo_documento']) === 'certificado', 'La categoría de certificado debe ser explícita.');
    [$sql, $params] = portal_documentos_consulta(null, 1, false);
    $statement = $connection->prepare($sql);
    $statement->execute($params);
    $documentosCliente = $statement->fetchAll();
    comprobar_portal(count($documentosCliente) === 12, 'El tipo Todos debe listar todos los documentos del cliente.');
    comprobar_portal(
        count(array_filter($documentosCliente, static fn (array $doc): bool => portal_tipo_categoria($doc['tipo_documento']) === 'certificado')) > 0
        && count(array_filter($documentosCliente, static fn (array $doc): bool => portal_tipo_categoria($doc['tipo_documento']) === 'boletin')) > 0,
        'El tipo Todos debe incluir ambas categorías de documento.'
    );
    [$sql, $params] = portal_documentos_consulta(null, 1, true, 'boletin', '2026-11-26', null);
    $statement = $connection->prepare($sql);
    $statement->execute($params);
    comprobar_portal($statement->fetchAll() === [], 'El filtro por fecha debe excluir documentos anteriores.');
    comprobar_portal(portal_solicitud_cliente_permitida('GET', ['page' => 'portal_clientes', 'fecha_desde' => '2026-11-25', 'categoria_documento' => 'certificado']), 'El portal debe permitir los filtros de listado.');
    foreach ([201, 202, 203] as $id) {
        comprobar_portal(portal_documento('certificadogeneral', $id, 1, true) === null, 'Documento no autorizado: ' . $id);
    }
    $_SESSION['user']['id'] = 4;
    $_POST = ['tipo_usuario' => 'cliente', 'id_cliente_usuario' => '1'];
    $form = [];
    $errors = [];
    portal_usuario_vinculo($form, $errors, 4);
    comprobar_portal($errors !== [], 'No debe convertir la propia cuenta ni un superadmin en cliente.');
    $_POST = ['tipo_usuario' => 'interno', 'id_cliente_usuario' => '3'];
    $errors = [];
    portal_usuario_vinculo($form, $errors);
    comprobar_portal($errors === [] && $form['id_cliente_usuario'] === '', 'Un interno no debe mantener asociación de cliente.');
    comprobar_portal(portal_documento('certificadoinase', 100, 1, true) === null, 'No debe confundir el tipo de certificado.');
    foreach ([['POST', []], ['GET', ['page' => 'usuarios']], ['GET', ['pdf' => '100']], ['GET', ['boletin_pureza_pdf' => '106']], ['GET', ['portal_pdf' => '100', 'pdf' => '201']]] as [$method, $query]) {
        comprobar_portal(!portal_solicitud_cliente_permitida($method, $query), 'La ruta interna debe estar bloqueada.');
    }
    echo "OK: consultas\n";
    return;
}

ob_start();
register_shutdown_function(static function () use ($connection, $caso, $ids, &$errors, &$realPdfPath): void {
    $output = ob_get_clean();
    try {
        if (str_contains($output, 'Fatal error') || str_contains($output, 'Warning')) {
            throw new RuntimeException($output);
        }
        if (str_starts_with($caso, 'index_')) {
            if ($caso === 'index_pdf_real') {
                comprobar_portal(str_starts_with($output, '%PDF-'), 'El portal no generó el PDF real.');
            } elseif ($caso === 'index_cliente') {
                comprobar_portal(str_contains($output, 'Mis boletines y certificados') && str_contains($output, 'DOC-pureza') && str_contains($output, 'Boletín') && str_contains($output, 'fecha_desde') && !str_contains($output, 'OCULTO-') && !str_contains($output, 'page=usuarios'), 'El portal mostró información o menús incorrectos.');
            } elseif ($caso === 'index_interno') {
                comprobar_portal(str_contains($output, 'Habilitar para cliente') && str_contains($output, 'OCULTO-201'), 'El interno no puede administrar publicaciones.');
                foreach (portal_tipos_documentos() as $def) {
                    comprobar_portal(function_exists($def[7]), 'No existe el generador PDF: ' . $def[7]);
                }
            } elseif ($caso === 'index_tipo_todos') {
                comprobar_portal(str_contains($output, 'value="" selected>Todos</option>'), 'No se seleccionó Todos en el filtro.');
                comprobar_portal(str_contains($output, 'DOC-pureza'), 'Todos no listó el boletín del cliente seleccionado.');
                comprobar_portal(str_contains($output, 'OCULTO-202'), 'Todos no listó el certificado del cliente seleccionado.');
            } elseif (in_array($caso, ['index_usuarios', 'index_usuario_editar'], true)) {
                comprobar_portal(str_contains($output, 'Tipo de acceso') && str_contains($output, 'Cliente asociado'), 'No se mostraron los campos para cuentas de cliente.');
                if ($caso === 'index_usuario_editar') {
                    comprobar_portal(str_contains($output, 'value="cliente" selected') && str_contains($output, 'value="1" selected'), 'La edición no precargó el tipo y cliente.');
                }
            } else {
                comprobar_portal(http_response_code() === 403 && !str_contains($output, '<html'), 'La ruta interna no fue bloqueada antes de ejecutarse.');
            }
        } elseif (str_starts_with($caso, 'sesion_')) {
            comprobar_portal(!isset($_SESSION['user']) && $output === '', 'La sesión revocada sigue activa.');
        } elseif (str_starts_with($caso, 'pdf_')) {
            $tipo = substr($caso, 4);
            if (in_array($tipo, ['ajeno', 'no_publicado', 'empresa_ajena'], true)) {
                comprobar_portal(http_response_code() === 404 && $output === 'Documento no disponible.', 'La descarga no autorizada no fue bloqueada.');
            } else {
                comprobar_portal($output === 'PDF:' . $ids[$tipo] . ':' . (in_array($tipo, ['germinacion', 'cold_test', 'envejecimiento_acelerado'], true) ? $tipo : ''), 'No se entregó el PDF correcto para ' . $tipo);
            }
        } elseif (in_array($caso, ['login', 'login_sin_cliente'], true)) {
            comprobar_portal($caso === 'login' ? (is_client_user() && $_SESSION['user']['id_cliente'] === 1) : !isset($_SESSION['user']), 'Login incorrecto.');
        } elseif (in_array($caso, ['alta_usuario', 'editar_usuario', 'cliente_ajeno'], true)) {
            $row = $connection->query("SELECT tipo_usuario, id_cliente_usuario FROM usuarios WHERE username_usuario = 'nuevocliente'")->fetch();
            comprobar_portal($caso === 'cliente_ajeno' ? ($row === false && $errors !== []) : ($row !== false && $row['tipo_usuario'] === 'cliente' && (int) $row['id_cliente_usuario'] === 2 && $errors === []), 'La cuenta no se guardó o validó correctamente.');
        } else {
            $published = (bool) $connection->query("SELECT COUNT(*) FROM portal_documentos WHERE id_empresa = 1 AND tipo_documento = 'certificadogeneral' AND id_documento = 202")->fetchColumn();
            comprobar_portal($caso === 'publicar' ? $published : !$published, 'Publicación o retiro incorrectos.');
            comprobar_portal(in_array($caso, ['publicar_ajeno', 'publicar_token'], true) ? $errors !== [] : $errors === [], 'La publicación no notificó el resultado correcto.');
        }
        echo "OK: $caso\n";
    } catch (Throwable $exception) {
        fwrite(STDERR, $exception->getMessage() . PHP_EOL);
        $failed = true;
    } finally {
        if ($realPdfPath !== null && is_file($realPdfPath)) {
            unlink($realPdfPath);
        }
    }
    if (isset($failed)) {
        exit(1);
    }
});

if (str_starts_with($caso, 'index_') || str_starts_with($caso, 'sesion_')) {
    $_GET = ['page' => 'portal_clientes'];
    if ($caso === 'index_interno') {
        $_SESSION['user']['id'] = 4;
    } elseif ($caso === 'index_tipo_todos') {
        $_SESSION['user']['id'] = 4;
        $_GET = ['page' => 'portal_clientes', 'tipo_documento' => '', 'cliente' => '1'];
    } elseif ($caso === 'index_pdf_real') {
        do {
            $pdfId = random_int(1000000000, 2000000000);
            $realPdfPath = empresa_storage_dir(1) . DIRECTORY_SEPARATOR . 'certificado_' . $pdfId . '.pdf';
        } while (file_exists($realPdfPath));
        $connection->exec("UPDATE certificados SET id_certificado = $pdfId WHERE id_certificado = 100");
        $connection->exec("UPDATE portal_documentos SET id_documento = $pdfId WHERE tipo_documento = 'certificadogeneral' AND id_documento = 100");
        $_GET = ['portal_pdf' => (string) $pdfId, 'tipo_documento' => 'certificadogeneral'];
    } elseif (in_array($caso, ['index_usuarios', 'index_usuario_editar'], true)) {
        $_SESSION['user']['id'] = 4;
        $_GET = ['page' => 'usuarios'];
        if ($caso === 'index_usuario_editar') {
            $_GET['edit'] = '3';
        }
    } elseif ($caso === 'index_post') {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['delete_certificado' => '1', 'certificado_delete_id' => '100'];
    } elseif ($caso === 'index_admin') {
        $_GET = ['page' => 'usuarios'];
    } elseif ($caso === 'index_pdf_legacy') {
        $_GET = ['pdf' => '100'];
    } elseif ($caso === 'sesion_desactivada') {
        $connection->exec('UPDATE usuarios SET activo_usuario = 0 WHERE id_usuario = 3');
    } elseif ($caso === 'sesion_cliente_eliminado') {
        $connection->exec('DELETE FROM clientes WHERE id_cliente = 1');
    }
    require __DIR__ . '/../index.php';
} elseif (str_starts_with($caso, 'pdf_')) {
    function portal_test_pdf(int $id, string $tipo = ''): array { return ['content' => "PDF:$id:$tipo", 'filename' => 'prueba.pdf']; }
    function build_certificado_pdf(int $id): array { return portal_test_pdf($id); }
    function build_boletin_peso_1000_pdf(int $id): array { return portal_test_pdf($id); }
    function build_boletin_tetrazolio_pdf(int $id): array { return portal_test_pdf($id); }
    function build_boletin_tetrazolio_soja_pdf(int $id): array { return portal_test_pdf($id); }
    function build_boletin_pureza_pdf(int $id): array { return portal_test_pdf($id); }
    function build_boletin_malezas_pdf(int $id): array { return portal_test_pdf($id); }
    function build_boletin_germinacion_pdf(int $id, string $tipo): array { return portal_test_pdf($id, $tipo); }
    $tipo = substr($caso, 4);
    $_GET = ['portal_pdf' => match ($tipo) {
        'ajeno' => 201, 'no_publicado' => 202, 'empresa_ajena' => 203, default => $ids[$tipo],
    }, 'tipo_documento' => isset($ids[$tipo]) ? $tipo : 'certificadogeneral'];
    require __DIR__ . '/../handlers/portal_clientes.php';
} elseif (in_array($caso, ['login', 'login_sin_cliente'], true)) {
    unset($_SESSION['user']);
    if ($caso === 'login_sin_cliente') {
        $connection->exec('UPDATE usuarios SET id_cliente_usuario = NULL WHERE id_usuario = 3');
    }
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = ['login' => '1', 'username' => 'clienteprueba', 'password' => 'prueba123'];
    require __DIR__ . '/../handlers/auth.php';
} elseif (in_array($caso, ['alta_usuario', 'editar_usuario', 'cliente_ajeno'], true)) {
    $_SESSION['user']['id'] = 4;
    $_SESSION['user']['tipo_usuario'] = 'interno';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = ['save_usuario' => '1', 'tipo_usuario' => 'cliente', 'id_cliente_usuario' => $caso === 'cliente_ajeno' ? '3' : '2', 'nombre_usuario' => 'Nuevo cliente', 'username_usuario' => 'nuevocliente', 'password_usuario' => 'prueba123', 'activo_usuario' => '1'];
    if ($caso === 'editar_usuario') {
        unset($_POST['save_usuario']);
        $_POST['update_usuario'] = '1';
        $_POST['usuario_edit_id'] = '3';
    }
    $userForm = [];
    require __DIR__ . '/../handlers/usuarios.php';
} else {
    $_SESSION['user']['tipo_usuario'] = 'interno';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = ['portal_publicar' => $caso === 'retirar' ? 'retirar' : 'publicar', 'portal_token' => $caso === 'publicar_token' ? 'incorrecto' : 'token-prueba', 'tipo_documento' => 'certificadogeneral', 'id_documento' => $caso === 'publicar_ajeno' ? '203' : '202'];
    if ($caso === 'retirar') {
        $connection->exec("INSERT INTO portal_documentos (id_empresa, tipo_documento, id_documento) VALUES (1, 'certificadogeneral', 202)");
    }
    require __DIR__ . '/../handlers/portal_clientes.php';
}
