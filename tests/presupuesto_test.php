<?php

declare(strict_types=1);

namespace PHPMailer\PHPMailer {
    class PHPMailer
    {
        public const ENCRYPTION_SMTPS = 'ssl';
        public const ENCRYPTION_STARTTLS = 'tls';
        public string $Host = '';
        public int $Port = 0;
        public bool $SMTPAuth = false;
        public string $Username = '';
        public string $Password = '';
        public string $SMTPSecure = '';
        public bool $SMTPAutoTLS = true;
        public string $CharSet = '';
        public string $Subject = '';
        public string $Body = '';
        public array $addresses = [];
        public array $attachment = [];
        public function __construct(bool $exceptions) {}
        public function isSMTP(): void {}
        public function setFrom(string $email, string $name): void {}
        public function addAddress(string $email): void { $this->addresses[] = $email; }
        public function addStringAttachment(string $content, string $filename, string $encoding, string $type): void
        {
            $this->attachment = [$content, $filename, $type];
        }
        public function send(): bool
        {
            $GLOBALS['correo_prueba'] = $this;
            return true;
        }
    }
}

namespace {
    require __DIR__ . '/../config/config.php';
    require __DIR__ . '/../includes/format_helpers.php';
    require __DIR__ . '/../includes/smtp_mailer.php';
    require __DIR__ . '/../includes/presupuesto.php';

    function comprobar_presupuesto(bool $condicion, string $mensaje): void
    {
        if (!$condicion) {
            throw new \RuntimeException($mensaje);
        }
    }

    function get_certificate_settings(int $empresa): array
    {
        return [
            'nombre_laboratorio' => 'Lab & Prueba', 'localidad_pais' => 'Córdoba',
            'direccion_laboratorio' => 'Calle 123', 'telefono_laboratorio' => '123456',
            'email_laboratorio' => 'lab@example.test', 'cuit_laboratorio' => '20-12345678-9',
        ];
    }
    function certificate_logo_path(int $empresa): ?string { return __DIR__ . '/../assets/img/membrete-leucaena.PNG'; }
    function get_smtp_settings(int $empresa): array
    {
        return ['host' => 'localhost', 'port' => '587', 'usuario' => 'test', 'password' => 'test',
            'seguridad' => 'tls', 'remitente_email' => 'lab@example.test', 'remitente_nombre' => 'Lab'];
    }
    function smtp_is_configured(array $smtp): bool { return true; }
    $schema = $argv[1] ?? '';
    if ($schema === '') {
        $schema = 'presupuesto_test_' . bin2hex(random_bytes(12));
        db()->exec("CREATE DATABASE `$schema`");
        try {
            $process = proc_open([PHP_BINARY, __FILE__, $schema], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            if (!is_resource($process)) {
                throw new \RuntimeException('No se pudo iniciar la prueba.');
            }
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            if (proc_close($process) !== 0 || !str_contains($output, 'OK:') || $error !== '') {
                throw new \RuntimeException("Falló la prueba: $output$error");
            }
            echo $output;
        } finally {
            db()->exec("DROP DATABASE `$schema`");
        }
        return;
    }
    comprobar_presupuesto(preg_match('/^presupuesto_test_[a-f0-9]{24}$/', $schema) === 1, 'Base de prueba inválida.');
    db()->exec("USE `$schema`");
    if (($argv[2] ?? '') === 'guardar') {
        require __DIR__ . '/../includes/libro_ingreso.php';
        $_SESSION['user'] = ['id' => 1, 'id_empresa' => 1, 'tipo_usuario' => 'interno'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'save_libro_ingreso' => '1', 'presupuesto' => 'SI', 'facturado' => 'NO',
            'fecha_entrada' => '2026-11-27', 'id_cliente' => '1', 'id_especie' => '1',
            'id_determinaciones' => ['1', '2'],
        ];
        $errors = [];
        $libroIngresoForm = [];
        register_shutdown_function(static function (): void {
            global $errors;
            $id = (int) db()->query('SELECT MAX(id_libro_ingreso) FROM libro_ingreso')->fetchColumn();
            $nuevo = presupuesto_obtener([$id]);
            if ($errors !== [] || count($nuevo['muestras'][0]['determinaciones']) !== 2 || $nuevo['total_centavos'] !== 124181) {
                fwrite(STDERR, "No se guardó el ingreso con las determinaciones para su presupuesto.\n");
                exit(1);
            }
            echo "OK: ingreso guardado.\n";
        });
        require __DIR__ . '/../handlers/libro_ingreso.php';
        exit(1);
    }
    db()->exec('CREATE TABLE clientes (id_cliente INT PRIMARY KEY, id_empresa INT, nombre_cliente VARCHAR(150), direccion_cliente VARCHAR(255), localidad_cliente VARCHAR(255), provincia_cliente VARCHAR(255), telefono_cliente VARCHAR(30), cuit_cliente VARCHAR(13))');
    db()->exec('CREATE TABLE cliente_emails (id_email_cliente INT PRIMARY KEY, id_empresa INT, id_cliente INT, email_cliente VARCHAR(150), principal_email INT, activo_email INT)');
    db()->exec('CREATE TABLE especies (id_especie INT PRIMARY KEY, id_empresa INT, nombre_especie VARCHAR(150), cientifico_especie VARCHAR(150))');
    db()->exec('CREATE TABLE cultivares (id_cultivar INT PRIMARY KEY, id_empresa INT, id_especie INT, nombre_cultivar VARCHAR(150))');
    db()->exec('CREATE TABLE libro_ingreso_boletines (id_libro_ingreso INT, tipo_boletin VARCHAR(60), id_boletin INT)');
    db()->exec("CREATE TABLE libro_ingreso (id_libro_ingreso INT AUTO_INCREMENT PRIMARY KEY, id_empresa INT, id_usuario INT, anio_libro_ingreso INT, nro_inase INT, id_cliente INT, nro_muestra VARCHAR(20), presupuesto VARCHAR(2), facturado VARCHAR(2), fecha_entrada DATE, fecha_inicio_analisis DATE NULL, remitente VARCHAR(255), origen VARCHAR(255), id_especie INT, id_cultivar INT, lote_observaciones VARCHAR(255), ubicacion_camara_archivo VARCHAR(255))");
    db()->exec('CREATE TABLE determinaciones_libro_ingreso (id_determinacion_libro INT PRIMARY KEY, id_empresa INT, nombre_determinacion_libro VARCHAR(255), costo DECIMAL(12,2))');
    db()->exec('CREATE TABLE libro_ingreso_determinaciones (id_libro_ingreso INT, id_determinacion_libro INT)');
    db()->exec("INSERT INTO clientes VALUES (1,1,'Cliente & Prueba','Calle 4','Rosario','Santa Fe','123','20-12345678-9'),(2,2,'Otro','','','','',''),(3,1,'Otro cliente','','','','','')");
    db()->exec("INSERT INTO cliente_emails VALUES (1,1,1,'uno@example.test',1,1),(2,1,1,'dos@example.test',0,1),(3,2,2,'ajeno@example.test',1,1)");
    db()->exec("INSERT INTO especies VALUES (1,1,'Especie','')");
    db()->exec("INSERT INTO libro_ingreso (id_libro_ingreso,id_empresa,id_cliente,nro_muestra,fecha_entrada) VALUES (1,1,1,'20260001','2026-11-27'),(2,2,2,'20260002','2026-11-27'),(3,1,1,'20260003','2026-11-27'),(4,1,1,'20260004','2026-11-29'),(5,1,3,'20260005','2026-11-27')");
    db()->exec("INSERT INTO determinaciones_libro_ingreso VALUES (1,1,'Pureza',1234.56),(2,1,'Germinación',0.10),(3,2,'Ajena',999.00)");
    db()->exec('INSERT INTO libro_ingreso_determinaciones VALUES (1,1),(1,2),(2,3),(4,1),(5,1)');
    $_SESSION['user'] = ['id' => 1, 'id_empresa' => 1, 'tipo_usuario' => 'interno'];
    $_SESSION['presupuesto_token'] = 'token-prueba';

    $presupuesto = presupuesto_obtener([1]);
    comprobar_presupuesto($presupuesto['total_centavos'] === 123466, 'Total de precios incorrecto.');
    foreach ([[], [1, 2], [1, 5], [1, 999], [1, 'invalido']] as $invalidos) {
        try {
            presupuesto_obtener($invalidos);
            throw new \LogicException('Se aceptó una selección inválida.');
        } catch (\InvalidArgumentException $exception) {
            comprobar_presupuesto($exception->getMessage() !== '', 'No se informó el error de selección.');
        }
    }
    db()->exec('UPDATE determinaciones_libro_ingreso SET costo = 7.25 WHERE id_determinacion_libro = 2');
    $presupuesto = presupuesto_obtener([1, 4, 1]);
    comprobar_presupuesto(count($presupuesto['muestras']) === 2 && $presupuesto['total_centavos'] === 247637, 'No se agruparon las muestras o los precios vigentes correctamente.');
    $process = proc_open([PHP_BINARY, __FILE__, $schema, 'guardar'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        throw new \RuntimeException('No se pudo iniciar la prueba de guardado.');
    }
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    comprobar_presupuesto(proc_close($process) === 0 && str_contains($output, 'OK: ingreso guardado') && $error === '', 'Falló el guardado del ingreso: ' . $output . $error);
    $html = presupuesto_html($presupuesto, get_certificate_settings(1), certificate_logo_path(1));
    foreach (['Lab &amp; Prueba', 'Cliente &amp; Prueba', 'Calle 123', '20-12345678-9', 'uno@example.test', '20260001', '20260004', 'Pureza', 'Germinación', '$ 1.241,81', '$ 2.476,37', '<img'] as $texto) {
        comprobar_presupuesto(str_contains($html, $texto), 'Falta en el presupuesto: ' . $texto);
    }
    $pdf = presupuesto_pdf($presupuesto, get_certificate_settings(1), certificate_logo_path(1));
    comprobar_presupuesto(str_starts_with($pdf['content'], '%PDF-'), 'No se generó un PDF.');
    require __DIR__ . '/../includes/libro_ingreso.php';
    require __DIR__ . '/../includes/libro_ingreso_certificado.php';
    $_GET = ['preparar_presupuesto' => '1', 'presupuesto_ids' => ['1', '4'], 'filtro_cliente' => '1', 'filtro_fecha_desde' => '2026-11-27', 'filtro_fecha_hasta' => '2026-11-29'];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $navigation = ['boletines' => ['items' => []]];
    $errors = [];
    $successMessage = null;
    ob_start();
    require __DIR__ . '/../pages/libro_ingreso.php';
    $pantalla = ob_get_clean();
    comprobar_presupuesto($errors === [], 'Falló la carga de la pantalla del libro: ' . implode(' | ', $errors));
    foreach (['form="seleccion-presupuesto" name="presupuesto_ids[]" value="1" checked', 'form="seleccion-presupuesto" name="presupuesto_ids[]" value="4" checked', 'name="filtro_cliente" value="1"', 'Revisar y enviar presupuesto', '20260001', '20260004', '$ 2.476,37', 'Ver presupuesto PDF', 'Enviar presupuesto por email'] as $texto) {
        comprobar_presupuesto(str_contains($pantalla, $texto), 'Falta en la pantalla de presupuesto: ' . $texto);
    }
    $_GET = ['filtro_cliente' => '1', 'filtro_fecha_desde' => '2026-11-29', 'filtro_fecha_hasta' => '2026-11-29'];
    $errors = [];
    ob_start();
    require __DIR__ . '/../pages/libro_ingreso.php';
    $pantalla = ob_get_clean();
    comprobar_presupuesto($errors === [] && array_column($libroIngresos, 'id_libro_ingreso') === [4], 'El filtro combinado debe incluir los extremos y respetar el cliente.');
    comprobar_presupuesto(str_contains($pantalla, 'name="filtro_fecha_desde" value="2026-11-29"') && str_contains($pantalla, 'Revisar y enviar presupuesto'), 'Los filtros o el botón de presupuesto no se mostraron.');
    $_GET = ['filtro_cliente' => '3'];
    $errors = [];
    ob_start();
    require __DIR__ . '/../pages/libro_ingreso.php';
    ob_end_clean();
    comprobar_presupuesto($errors === [] && array_column($libroIngresos, 'id_libro_ingreso') === [5], 'El filtro por cliente no funciona.');
    $_GET = ['filtro_fecha_hasta' => '2026-11-27'];
    $errors = [];
    ob_start();
    require __DIR__ . '/../pages/libro_ingreso.php';
    ob_end_clean();
    $idsFiltrados = array_column($libroIngresos, 'id_libro_ingreso');
    comprobar_presupuesto($errors === [] && in_array(1, $idsFiltrados, true) && in_array(5, $idsFiltrados, true) && !in_array(4, $idsFiltrados, true) && !in_array(2, $idsFiltrados, true), 'La fecha hasta debe incluir el límite sin mostrar ingresos de otra empresa.');
    foreach ([['filtro_fecha_desde' => '2026-02-31'], ['filtro_fecha_desde' => '2026-11-30', 'filtro_fecha_hasta' => '2026-11-01'], ['filtro_cliente' => ['1']]] as $filtrosInvalidos) {
        $_GET = $filtrosInvalidos;
        $errors = [];
        ob_start();
        require __DIR__ . '/../pages/libro_ingreso.php';
        ob_end_clean();
        comprobar_presupuesto($libroFiltroErrores !== [] && $libroIngresos === [], 'El filtro inválido debe informarse sin mostrar todos los ingresos.');
    }
    $_GET = ['preparar_presupuesto' => '1'];
    $errors = [];
    ob_start();
    require __DIR__ . '/../pages/libro_ingreso.php';
    $pantalla = ob_get_clean();
    comprobar_presupuesto(str_contains($pantalla, 'id="presupuesto"></span>') && str_contains($pantalla, 'Selecciona al menos un ingreso para presupuestar.'), 'La selección vacía debe informarse junto al listado.');
    foreach (['tls' => 'tls', 'ssl' => 'ssl', 'none' => ''] as $seguridad => $esperada) {
        $smtp = get_smtp_settings(1);
        $smtp['seguridad'] = $seguridad;
        $mailer = smtp_create_mailer($smtp);
        comprobar_presupuesto($mailer->SMTPSecure === $esperada && $mailer->SMTPAutoTLS === ($seguridad !== 'none'), 'La seguridad SMTP no se configuró correctamente.');
    }
    foreach ([[presupuesto_obtener([1, 3]), certificate_logo_path(1)], [$presupuesto, null]] as [$sinDatos, $logo]) {
        try {
            presupuesto_html($sinDatos, get_certificate_settings(1), $logo);
            throw new \LogicException('Se generó un presupuesto sin los datos obligatorios.');
        } catch (\RuntimeException $exception) {
            comprobar_presupuesto(!($exception instanceof \LogicException), 'Se generó un presupuesto sin los datos obligatorios.');
        }
    }
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = ['enviar_presupuesto' => '1', 'presupuesto_ids' => ['1', '4'], 'presupuesto_token' => 'token-prueba', 'destinatarios' => ['ajeno@example.test']];
    $errors = [];
    require __DIR__ . '/../handlers/presupuesto.php';
    comprobar_presupuesto($errors !== [] && !isset($GLOBALS['correo_prueba']), 'Se aceptó un correo ajeno.');
    $_POST['destinatarios'] = ['uno@example.test'];
    $_POST['presupuesto_ids'] = ['1', '5'];
    $errors = [];
    require __DIR__ . '/../handlers/presupuesto.php';
    comprobar_presupuesto($errors !== [] && !isset($GLOBALS['correo_prueba']), 'Se aceptaron muestras de diferentes clientes.');
    $_POST['presupuesto_ids'] = ['1', '4'];
    $_POST['presupuesto_token'] = 'incorrecto';
    $_POST['destinatarios'] = ['uno@example.test'];
    $errors = [];
    require __DIR__ . '/../handlers/presupuesto.php';
    comprobar_presupuesto($errors !== [] && !isset($GLOBALS['correo_prueba']), 'Se aceptó un token inválido.');
    $_POST['presupuesto_token'] = 'token-prueba';
    $_POST['destinatarios'] = ['dos@example.test'];
    $errors = [];
    register_shutdown_function(static function (): void {
        $mail = $GLOBALS['correo_prueba'] ?? null;
        if ($mail === null || $mail->addresses !== ['dos@example.test'] || !str_starts_with($mail->attachment[0], '%PDF-') || $mail->attachment[2] !== 'application/pdf') {
            fwrite(STDERR, "No se envió el PDF al destinatario seleccionado.\n");
            exit(1);
        }
        echo "OK: presupuesto, precios vigentes, aislamiento y envío simulado.\n";
    });
    require __DIR__ . '/../handlers/presupuesto.php';
}
