<?php

declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../includes/libro_ingreso.php';
require __DIR__ . '/../includes/format_helpers.php';
require __DIR__ . '/../includes/navigation.php';

function verificar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

// El handler redirige y termina la petición tras guardar.
if (($argv[1] ?? '') === 'guardar') {
    $_SESSION['user'] = ['id' => null, 'id_empresa' => (int) $argv[2]];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = json_decode(base64_decode($argv[3], true), true, 512, JSON_THROW_ON_ERROR);
    $errors = [];
    $libroIngresoForm = [];
    require __DIR__ . '/../handlers/libro_ingreso.php';
    echo json_encode($errors, JSON_THROW_ON_ERROR);
    exit($errors === [] ? 1 : 0);
}

function guardar_ingreso(int $empresa, array $datos): array
{
    $proceso = proc_open(
        [PHP_BINARY, __FILE__, 'guardar', (string) $empresa, base64_encode(json_encode($datos, JSON_THROW_ON_ERROR))],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes
    );
    verificar(is_resource($proceso), 'No se pudo ejecutar el handler.');
    $salida = stream_get_contents($pipes[1]);
    $erroresPhp = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $codigo = proc_close($proceso);
    verificar($codigo === 0 && $erroresPhp === '', 'Error al ejecutar el handler: ' . $erroresPhp . $salida);

    return $salida === '' ? [] : json_decode($salida, true, 512, JSON_THROW_ON_ERROR);
}

function consultar_ingreso(int $id): array
{
    $statement = db()->prepare('SELECT * FROM libro_ingreso WHERE id_libro_ingreso = ?');
    $statement->execute([$id]);
    return $statement->fetch();
}

function renderizar_ingreso(int $empresa, array $get, array $post = []): array
{
    global $navigation;
    $_SESSION['user'] = ['id' => null, 'id_empresa' => $empresa];
    $_GET = $get;
    $_POST = $post;
    $_SERVER['REQUEST_METHOD'] = $post === [] ? 'GET' : 'POST';
    $errors = [];
    $successMessage = null;
    $libroIngresoForm = array_fill_keys([
        'presupuesto', 'facturado', 'fecha_entrada', 'fecha_inicio_analisis',
        'remitente', 'id_cliente', 'origen', 'id_especie', 'id_cultivar',
        'lote_observaciones', 'ubicacion_camara_archivo',
    ], '');
    $libroIngresoForm['id_determinaciones'] = [];
    if ($post !== []) {
        require __DIR__ . '/../handlers/libro_ingreso.php';
    }
    ob_start();
    try {
        require __DIR__ . '/../pages/libro_ingreso.php';
        $html = ob_get_contents();
    } finally {
        ob_end_clean();
    }
    return [$html, $errors, $libroIngresoForm];
}

$empresas = array_map('intval', db()->query('SELECT id_empresa FROM empresas ORDER BY id_empresa LIMIT 2')->fetchAll(PDO::FETCH_COLUMN));
verificar(count($empresas) === 2, 'Se requieren dos empresas para verificar aislamiento.');
[$empresa, $otraEmpresa] = $empresas;
$statement = db()->prepare('SELECT id_cliente FROM clientes WHERE id_empresa = ? ORDER BY id_cliente LIMIT 1');
$statement->execute([$empresa]);
$cliente = (int) $statement->fetchColumn();
$statement = db()->prepare('SELECT id_especie FROM especies WHERE id_empresa = ? ORDER BY id_especie LIMIT 1');
$statement->execute([$empresa]);
$especie = (int) $statement->fetchColumn();
$statement = db()->prepare('SELECT id_determinacion_libro FROM determinaciones_libro_ingreso WHERE id_empresa = ? ORDER BY id_determinacion_libro LIMIT 2');
$statement->execute([$empresa]);
$determinaciones = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
verificar($cliente > 0 && $especie > 0 && count($determinaciones) === 2, 'Se requieren cliente, especie y dos determinaciones.');

$ingresoId = null;
$cultivarId = null;
try {
    $statement = db()->prepare('INSERT INTO cultivares (id_empresa, id_especie, nombre_cultivar) VALUES (?, ?, ?)');
    $statement->execute([$empresa, $especie, 'Prueba edicion ' . bin2hex(random_bytes(6))]);
    $cultivarId = (int) db()->lastInsertId();
    $anio = libro_ingreso_anio_actual() - 1;
    $nro = libro_ingreso_proximo_nro_inase($empresa, $anio);
    $statement = db()->prepare(
        'INSERT INTO libro_ingreso (id_empresa, anio_libro_ingreso, nro_inase, nro_muestra, fecha_entrada, id_cliente, id_especie)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->execute([$empresa, $anio, $nro, libro_ingreso_nro_muestra($anio, $nro), '2026-10-01', $cliente, $especie]);
    $ingresoId = (int) db()->lastInsertId();
    $original = consultar_ingreso($ingresoId);
    $datos = [
        'update_libro_ingreso' => '1',
        'ingreso_id' => (string) $ingresoId,
        'presupuesto' => 'SI',
        'facturado' => 'SI',
        'fecha_entrada' => '2026-10-02',
        'fecha_inicio_analisis' => '2026-10-03',
        'remitente' => 'Remitente <prueba>',
        'id_cliente' => (string) $cliente,
        'origen' => 'Origen de prueba',
        'id_especie' => (string) $especie,
        'id_cultivar' => (string) $cultivarId,
        'lote_observaciones' => 'Lote "A"',
        'ubicacion_camara_archivo' => 'Estante de prueba',
        'id_determinaciones' => array_map('strval', $determinaciones),
        'nro_inase' => '999999',
        'nro_muestra' => 'NO CAMBIAR',
        'anio_libro_ingreso' => '1900',
    ];
    verificar(guardar_ingreso($empresa, $datos) === [], 'Debe permitir editar el ingreso.');
    $actual = consultar_ingreso($ingresoId);
    foreach (['presupuesto', 'facturado', 'fecha_entrada', 'fecha_inicio_analisis', 'remitente', 'id_cliente', 'origen', 'id_especie', 'id_cultivar', 'lote_observaciones', 'ubicacion_camara_archivo'] as $campo) {
        verificar((string) $actual[$campo] === $datos[$campo], 'No se guardo el campo ' . $campo);
    }
    foreach (['id_libro_ingreso', 'id_empresa', 'id_usuario', 'anio_libro_ingreso', 'nro_inase', 'nro_muestra', 'created_at_libro_ingreso', 'primer_conteo_realizado_at', 'conteo_final_realizado_at'] as $campo) {
        verificar($actual[$campo] === $original[$campo], 'No debe modificar el campo automatico ' . $campo);
    }
    $consultarDet = db()->prepare('SELECT id_determinacion_libro FROM libro_ingreso_determinaciones WHERE id_libro_ingreso = ? ORDER BY id_determinacion_libro');
    $consultarDet->execute([$ingresoId]);
    verificar(array_map('intval', $consultarDet->fetchAll(PDO::FETCH_COLUMN)) === $determinaciones, 'Debe guardar las determinaciones.');

    [$html, $errors, $form] = renderizar_ingreso($empresa, ['editar' => (string) $ingresoId]);
    verificar($errors === [], 'La vista completa debe cargar sin errores.');
    foreach (array_keys($form) as $campo) {
        $esperado = $campo === 'id_determinaciones' ? $determinaciones : $datos[$campo];
        verificar($form[$campo] === $esperado, 'Debe precargar ' . $campo);
    }
    preg_match_all('/<th>(.*?)<\/th>/', $html, $encabezados);
    verificar($encabezados[1] === ['Muestra', 'Pres.', 'Fact', 'Entrada', 'Inicio', 'Cliente', 'Especie', 'Acciones'], 'Debe mostrar solo las ocho columnas solicitadas.');
    preg_match('/<tbody>\s*<tr>(.*?)<\/tr>/s', $html, $fila);
    verificar(substr_count($fila[1], '<td>') === 8, 'Las filas deben tener ocho celdas.');
    verificar(strpos($html, 'bi bi-eye') !== false && strpos($html, 'bi bi-pencil') === false, 'Debe reemplazar el lapiz por un ojo.');
    verificar(strpos($html, '>BOL</button>') !== false && strpos($html, '>CERT</a>') !== false, 'Debe conservar BOL y CERT.');
    verificar(strpos($html, 'Remitente &lt;prueba&gt;') !== false, 'Debe escapar los valores.');
    foreach (['libro_nro_inase', 'libro_nro_muestra'] as $id) {
        verificar(preg_match('/id="' . $id . '"[^>]*readonly disabled/', $html) === 1, 'Los numeros deben estar bloqueados.');
    }

    $invalidos = [
        ['fecha_inicio_analisis' => '2026-02-30'],
        ['fecha_entrada' => ''],
        ['presupuesto' => 'OTRO'],
        ['id_cliente' => '0'],
        ['id_especie' => '0'],
        ['id_cultivar' => 'abc'],
        ['id_determinaciones' => ['2147483647']],
        ['remitente' => str_repeat('a', 256)],
        ['ingreso_id' => '0'],
        ['ingreso_id' => '2147483647'],
    ];
    foreach ($invalidos as $cambio) {
        verificar(guardar_ingreso($empresa, array_replace($datos, $cambio)) !== [], 'Debe rechazar datos invalidos: ' . implode(', ', array_keys($cambio)));
        verificar(consultar_ingreso($ingresoId) === $actual, 'Una validacion fallida no debe cambiar el ingreso.');
    }
    [$html, $errors, $form] = renderizar_ingreso($empresa, ['editar' => (string) $ingresoId], array_replace($datos, ['fecha_inicio_analisis' => '2026-02-30']));
    verificar($errors !== [] && $form['fecha_inicio_analisis'] === '2026-02-30', 'Debe conservar los datos enviados tras un error.');
    verificar(strpos($html, 'name="update_libro_ingreso"') !== false && strpos($html, 'name="save_libro_ingreso"') === false, 'Un error no debe convertir la edicion en alta.');
    verificar(guardar_ingreso($otraEmpresa, $datos) !== [], 'No debe editar ingresos de otra empresa.');
    verificar(consultar_ingreso($ingresoId) === $actual, 'Debe conservar el ingreso de otra empresa.');
    [$html, $errors] = renderizar_ingreso($otraEmpresa, ['editar' => (string) $ingresoId]);
    verificar($errors !== [] && strpos($html, 'name="update_libro_ingreso"') === false, 'No debe mostrar el formulario de otro ingreso.');

    $vacios = array_replace($datos, array_fill_keys([
        'fecha_inicio_analisis', 'remitente', 'origen', 'id_cultivar', 'lote_observaciones', 'ubicacion_camara_archivo',
    ], ''));
    $vacios['id_determinaciones'] = [];
    verificar(guardar_ingreso($empresa, $vacios) === [], 'Debe permitir borrar campos opcionales.');
    $actual = consultar_ingreso($ingresoId);
    foreach (['fecha_inicio_analisis', 'remitente', 'origen', 'id_cultivar', 'lote_observaciones', 'ubicacion_camara_archivo'] as $campo) {
        verificar($actual[$campo] === null, 'Debe guardar NULL en ' . $campo);
    }
    $consultarDet->execute([$ingresoId]);
    verificar($consultarDet->fetchAll(PDO::FETCH_COLUMN) === [], 'Debe borrar las determinaciones deseleccionadas.');
    verificar(guardar_ingreso($empresa, $vacios) === [], 'Guardar sin cambios tambien debe funcionar.');
} finally {
    if ($ingresoId !== null) {
        $statement = db()->prepare('DELETE FROM libro_ingreso WHERE id_libro_ingreso = ?');
        $statement->execute([$ingresoId]);
    }
    if ($cultivarId !== null) {
        $statement = db()->prepare('DELETE FROM cultivares WHERE id_cultivar = ?');
        $statement->execute([$cultivarId]);
    }
}

echo 'OK: edicion completa, campos automaticos protegidos, validacion, aislamiento, vista y ocho columnas; registros de prueba eliminados.' . PHP_EOL;
