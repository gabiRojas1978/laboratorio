<?php

declare(strict_types=1);

$escenario = $argv[1] ?? '';
if ($escenario === '') {
    foreach (['alta', 'edicion', 'ajena'] as $caso) {
        $process = proc_open([PHP_BINARY, __FILE__, $caso], [
            0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('No se pudo iniciar la prueba de guardado.');
        }
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);
        if ($status !== 0 || !str_contains($output, 'OK:')) {
            throw new RuntimeException('Falló el escenario ' . $caso . ': ' . $output . $error);
        }
    }
    echo "OK: alta, edición y rollback del boletín con anormalidades.\n";
    return;
}

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../includes/boletin_germinacion.php';
$connection = db();
$tablas = ['clientes', 'especies', 'boletines_germinacion', 'boletin_germinacion_repeticiones', 'anormalidades', 'boletin_germinacion_anormalidades'];
foreach ($tablas as $tabla) {
    $definition = $connection->query("SHOW CREATE TABLE $tabla")->fetch(PDO::FETCH_NUM)[1];
    $definition = preg_replace('/,\s*CONSTRAINT\s+`[^`]+`[^\n,]*/', '', $definition);
    $definition = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $definition);
    $connection->exec($definition);
}
$connection->exec("INSERT INTO clientes (id_cliente, id_empresa, nombre_cliente) VALUES (1, 1, 'Cliente')");
$connection->exec("INSERT INTO especies (id_especie, id_empresa, nombre_especie) VALUES (1, 1, 'Especie')");
$connection->exec("INSERT INTO anormalidades (id_anormalidad, id_empresa, codigo, etiqueta, grupo_tipo, categoria_principal, subcategoria) VALUES
    (1, 1, '00/01', 'Uno', 'Grupo', 'Categoría', 'Subcategoría'),
    (2, 1, '00/02', 'Dos', 'Grupo', 'Categoría', 'Subcategoría'),
    (3, 2, '00/03', 'Ajena', 'Grupo', 'Categoría', 'Subcategoría')");
if ($escenario !== 'alta') {
    $connection->exec("INSERT INTO boletines_germinacion (id_boletin_germinacion, id_empresa, id_cliente, id_especie, tipo_boletin, muestra_nro, observaciones) VALUES (7, 1, 1, 1, 'germinacion', 'Muestra', 'Original')");
    $connection->exec('INSERT INTO boletin_germinacion_repeticiones (id_boletin_germinacion, nro_repeticion, semillas_sembradas) VALUES (7, 1, 50)');
    $connection->exec('INSERT INTO boletin_germinacion_anormalidades (id_boletin_germinacion, id_anormalidad) VALUES (7, 1)');
}

$_SESSION['user'] = ['id_empresa' => 1];
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET = ['page' => 'boletin_germinacion'];
$_POST = [
    'id_cliente' => '1', 'id_especie' => '1', 'muestra_nro' => 'Muestra', 'pretratamiento_aplica' => 'no',
    'observaciones' => 'Actualizado', 'id_anormalidades' => $escenario === 'alta' ? ['1', '2'] : ($escenario === 'ajena' ? ['1', '3'] : ['2']),
    'repeticiones' => array_fill(1, 4, ['semillas_sembradas' => '100', 'primer_conteo_normales' => '90']),
];
if ($escenario === 'alta') {
    $_POST['save_boletin_germinacion'] = '1';
} else {
    $_POST['update_boletin_germinacion'] = '1';
    $_POST['boletin_germinacion_edit_id'] = '7';
}
$boletinGerminacionForm = ['id_anormalidades' => []];
$boletinGerminacionEditId = null;
$errors = [];

register_shutdown_function(static function () use ($connection, $escenario, &$errors): void {
    $fatal = error_get_last();
    if ($fatal !== null && in_array($fatal['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        fwrite(STDERR, $fatal['message']);
        exit(1);
    }
    $rows = $connection->query('SELECT id_boletin_germinacion, observaciones FROM boletines_germinacion')->fetchAll();
    if (count($rows) !== 1) {
        fwrite(STDERR, 'Cantidad inesperada de boletines.');
        exit(1);
    }
    $ids = array_map('intval', $connection->query('SELECT id_anormalidad FROM boletin_germinacion_anormalidades ORDER BY id_anormalidad')->fetchAll(PDO::FETCH_COLUMN));
    $repeticiones = $connection->query('SELECT semillas_sembradas FROM boletin_germinacion_repeticiones ORDER BY nro_repeticion')->fetchAll(PDO::FETCH_COLUMN);
    $expectedIds = $escenario === 'alta' ? [1, 2] : ($escenario === 'ajena' ? [1] : [2]);
    $expectedObservaciones = $escenario === 'ajena' ? 'Original' : 'Actualizado';
    $expectedRepeticiones = $escenario === 'ajena' ? [50] : [100, 100, 100, 100];
    if ($ids !== $expectedIds || $rows[0]['observaciones'] !== $expectedObservaciones
        || array_map('intval', $repeticiones) !== $expectedRepeticiones
        || ($escenario === 'ajena' ? $errors === [] : $errors !== [])) {
        fwrite(STDERR, 'El guardado o rollback no produjo los valores esperados: ' . implode(' | ', $errors));
        exit(1);
    }
    echo "OK: $escenario\n";
});

require __DIR__ . '/../handlers/boletin_germinacion.php';
