<?php

declare(strict_types=1);

$caso = $argv[1] ?? '';
if ($caso === '') {
    foreach (['vista', 'general', 'inase', 'tetrazolio', 'sin_pdf', 'ajeno', 'otro_tipo', 'inexistente', 'token', 'id', 'pdf_error'] as $escenario) {
        $process = proc_open([PHP_BINARY, __FILE__, $escenario], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('No se pudo iniciar la prueba.');
        }
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0 || !str_contains($output, 'OK:')) {
            throw new RuntimeException("Falló $escenario: $output$error");
        }
    }
    echo "OK: eliminación, aislamiento, validaciones y rollback de certificados.\n";
    return;
}

$config = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'name' => getenv('DB_NAME') ?: 'laboratorio',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') ?: '',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
];
$localConfig = __DIR__ . '/../config/database.local.php';
if (is_file($localConfig)) {
    $local = require $localConfig;
    if (is_array($local)) {
        $config = array_merge($config, $local);
    }
}
$connection = new PDO(
    sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'], $config['name'], $config['charset']),
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
);
$connection->exec('CREATE TEMPORARY TABLE certificados (
    id_certificado INT PRIMARY KEY, id_empresa INT NOT NULL, tipo_certificado VARCHAR(50) NOT NULL,
    id_cliente_certificado INT NULL, id_especie_certificado INT NULL, id_cultivar_certificado INT NULL,
    id_usuario_certificado INT NULL, fecha_emision_certificado DATE NULL
) ENGINE=InnoDB');
$connection->exec('CREATE TEMPORARY TABLE certificado_determinaciones (id_certificado INT NOT NULL, id_determinacion INT NOT NULL) ENGINE=InnoDB');
$connection->exec("INSERT INTO certificados (id_certificado, id_empresa, tipo_certificado) VALUES (1, 1, 'certificadogeneral'), (2, 2, 'certificadogeneral'), (3, 1, 'certificadoinase'), (4, 1, 'certificadotetrazolio')");
$connection->exec('INSERT INTO certificado_determinaciones VALUES (1, 10), (2, 20), (3, 30), (4, 40)');

function db(): PDO { return $GLOBALS['connection']; }
function is_logged_in(): bool { return true; }
function current_empresa_id(): int { return 1; }
function empresa_storage_dir(int $empresa): string { return $GLOBALS['directorio']; }

if ($caso === 'vista') {
    $connection->exec('CREATE TEMPORARY TABLE clientes (id_cliente INT, id_empresa INT, nombre_cliente VARCHAR(100))');
    $connection->exec('CREATE TEMPORARY TABLE especies (id_especie INT, id_empresa INT, nombre_especie VARCHAR(100), cientifico_especie VARCHAR(100))');
    $connection->exec('CREATE TEMPORARY TABLE cultivares (id_cultivar INT, id_empresa INT, id_especie INT, nombre_cultivar VARCHAR(100))');
    $connection->exec('CREATE TEMPORARY TABLE usuarios (id_usuario INT, nombre_usuario VARCHAR(100))');
    $connection->exec('CREATE TEMPORARY TABLE otras_determinaciones (id_determinacion INT, id_empresa INT, nombre_determinacion VARCHAR(100))');
    $_SESSION = [];
    foreach (['Certificadogeneral' => 1, 'CertificadoINASE' => 3, 'CertificadoTetrazolio' => 4] as $page => $id) {
        $_GET = ['page' => $page, 'cert_page' => '1'];
        $errors = [];
        $successMessage = null;
        $newCertificadoId = null;
        ob_start();
        require __DIR__ . '/../pages/certificado_form.php';
        $html = ob_get_clean();
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($dom);
        $forms = $xpath->query('//form[input[@name="delete_certificado"]]');
        if ($errors !== [] || $forms->length !== 1) {
            throw new RuntimeException('No se mostró la eliminación para ' . $page . ': ' . implode(' | ', $errors));
        }
        $form = $forms->item(0);
        parse_str((string) parse_url($form->getAttribute('action'), PHP_URL_QUERY), $actionQuery);
        if ($form->getAttribute('method') !== 'post' || $actionQuery !== $_GET
            || !str_contains($form->getAttribute('onsubmit'), 'confirm(')
            || $xpath->query('.//input[@name="certificado_delete_id" and @value="' . $id . '"]', $form)->length !== 1
            || $xpath->query('.//input[@name="certificado_delete_token"]', $form)->item(0)->getAttribute('value') !== $_SESSION['certificado_delete_token']) {
            throw new RuntimeException('El formulario de eliminación no es correcto para ' . $page);
        }
    }
    echo "OK: vista\n";
    return;
}

$directorio = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'certificado_eliminar_' . bin2hex(random_bytes(12));
if (!mkdir($directorio)) {
    throw new RuntimeException('No se pudo crear el directorio temporal.');
}
$objetivo = match ($caso) {
    'inase', 'otro_tipo' => 3,
    'tetrazolio' => 4,
    'ajeno' => 2,
    'inexistente' => 99,
    default => 1,
};
$pdf = $directorio . DIRECTORY_SEPARATOR . 'certificado_' . $objetivo . '.pdf';
if ($caso === 'pdf_error') {
    mkdir($pdf);
} elseif ($caso !== 'sin_pdf') {
    file_put_contents($pdf, 'PDF de prueba');
}

$_SESSION = ['certificado_delete_token' => 'token-de-prueba'];
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET = ['page' => match ($caso) {
    'inase' => 'CertificadoINASE',
    'tetrazolio' => 'CertificadoTetrazolio',
    default => 'Certificadogeneral',
}];
$_POST = [
    'delete_certificado' => '1',
    'certificado_delete_id' => $caso === 'id' ? 'invalido' : (string) $objetivo,
    'certificado_delete_token' => $caso === 'token' ? 'incorrecto' : 'token-de-prueba',
];
$errors = [];
$esperaExito = in_array($caso, ['general', 'inase', 'tetrazolio', 'sin_pdf'], true);

register_shutdown_function(static function () use ($connection, $caso, $objetivo, $pdf, $directorio, $esperaExito, &$errors): void {
    $fatal = error_get_last();
    try {
        if ($fatal !== null && in_array($fatal['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            throw new RuntimeException($fatal['message']);
        }
        $rows = $connection->query('SELECT id_certificado FROM certificados ORDER BY id_certificado')->fetchAll(PDO::FETCH_COLUMN);
        $children = $connection->query('SELECT id_certificado FROM certificado_determinaciones ORDER BY id_certificado')->fetchAll(PDO::FETCH_COLUMN);
        $expected = $esperaExito ? array_values(array_diff([1, 2, 3, 4], [$objetivo])) : [1, 2, 3, 4];
        if (array_map('intval', $rows) !== $expected || array_map('intval', $children) !== $expected) {
            throw new RuntimeException('Los registros eliminados o conservados no son correctos.');
        }
        if ($esperaExito ? ($errors !== [] || file_exists($pdf) || !isset($_SESSION['success_message'])) : $errors === []) {
            throw new RuntimeException('El resultado, mensaje o archivo PDF no es el esperado.');
        }
        if (!$esperaExito && $caso !== 'sin_pdf' && !file_exists($pdf)) {
            throw new RuntimeException('Se eliminó el PDF pese al fallo.');
        }
        echo "OK: $caso\n";
    } catch (Throwable $exception) {
        fwrite(STDERR, $exception->getMessage() . PHP_EOL);
        $failed = true;
    } finally {
        if (is_dir($pdf)) {
            rmdir($pdf);
        } elseif (is_file($pdf)) {
            unlink($pdf);
        }
        rmdir($directorio);
    }
    if (isset($failed)) {
        exit(1);
    }
});

require __DIR__ . '/../handlers/certificados.php';
