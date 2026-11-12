<?php

declare(strict_types=1);

/**
 * Prueba de integración del handler del Libro de Ingreso: inserta un ingreso real,
 * verifica el correlativo y el N° de muestra, y limpia los datos creados.
 */

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../includes/libro_ingreso.php';

$idEmpresa = (int) db()->query('SELECT id_empresa FROM empresas ORDER BY id_empresa ASC LIMIT 1')->fetchColumn();
if ($idEmpresa === 0) {
    throw new RuntimeException('No hay empresas cargadas para ejecutar la prueba.');
}

$statement = db()->prepare('SELECT id_cliente FROM clientes WHERE id_empresa = :id_empresa ORDER BY id_cliente ASC LIMIT 1');
$statement->execute([':id_empresa' => $idEmpresa]);
$idCliente = (int) $statement->fetchColumn();

$statement = db()->prepare('SELECT id_especie FROM especies WHERE id_empresa = :id_empresa ORDER BY id_especie ASC LIMIT 1');
$statement->execute([':id_empresa' => $idEmpresa]);
$idEspecie = (int) $statement->fetchColumn();

if ($idCliente === 0 || $idEspecie === 0) {
    echo 'OMITIDO: la empresa ' . $idEmpresa . ' no tiene clientes o especies cargadas.' . PHP_EOL;
    exit(0);
}

$statement = db()->prepare('SELECT id_determinacion_libro FROM determinaciones_libro_ingreso WHERE id_empresa = :id_empresa ORDER BY id_determinacion_libro ASC LIMIT 2');
$statement->execute([':id_empresa' => $idEmpresa]);
$determinaciones = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
if (count($determinaciones) < 2) {
    throw new RuntimeException('El catálogo de determinaciones del libro de ingreso no fue sembrado.');
}

$anio = libro_ingreso_anio_actual();
$esperadoInase = libro_ingreso_proximo_nro_inase($idEmpresa, $anio);
$fechaInicioAnalisis = date('Y-m-d', strtotime('+1 day'));

$_SESSION['user'] = ['id' => null, 'id_empresa' => $idEmpresa];
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'save_libro_ingreso' => '1',
    'presupuesto' => 'SI',
    'facturado' => 'NO',
    'fecha_entrada' => date('Y-m-d'),
    'fecha_inicio_analisis' => $fechaInicioAnalisis,
    'remitente' => 'Remitente de prueba',
    'id_cliente' => (string) $idCliente,
    'origen' => 'La Carlota',
    'id_especie' => (string) $idEspecie,
    'id_cultivar' => '',
    'lote_observaciones' => 'etiqueta',
    'ubicacion_camara_archivo' => 'Estante 3',
    'id_determinaciones' => array_map('strval', $determinaciones),
];

$errors = [];
$libroIngresoForm = [
    'presupuesto' => 'SI',
    'facturado' => 'NO',
    'fecha_entrada' => date('Y-m-d'),
    'fecha_inicio_analisis' => $fechaInicioAnalisis,
    'remitente' => '',
    'id_cliente' => '',
    'origen' => '',
    'id_especie' => '',
    'id_cultivar' => '',
    'lote_observaciones' => '',
    'id_determinaciones' => [],
    'ubicacion_camara_archivo' => '',
];

register_shutdown_function(static function () use ($idEmpresa, $anio, $esperadoInase, $determinaciones, $fechaInicioAnalisis): void {
    global $errors;

    if ($errors !== []) {
        echo 'FALLÓ: ' . implode(' | ', $errors) . PHP_EOL;
        exit(1);
    }

    $statement = db()->prepare(
        'SELECT id_libro_ingreso, nro_inase, nro_muestra, presupuesto, facturado, fecha_inicio_analisis, origen
         FROM libro_ingreso WHERE id_empresa = :id_empresa AND anio_libro_ingreso = :anio AND nro_inase = :nro_inase'
    );
    $statement->execute([':id_empresa' => $idEmpresa, ':anio' => $anio, ':nro_inase' => $esperadoInase]);
    $row = $statement->fetch();

    if ($row === false) {
        echo 'FALLÓ: no se registró el ingreso esperado.' . PHP_EOL;
        exit(1);
    }

    $muestraEsperada = libro_ingreso_nro_muestra($anio, $esperadoInase);
    $muestraValida = (string) $row['nro_muestra'] === $muestraEsperada;
    $fechaInicioAnalisisValida = (string) $row['fecha_inicio_analisis'] === $fechaInicioAnalisis;

    $statement = db()->prepare('SELECT COUNT(*) FROM libro_ingreso_determinaciones WHERE id_libro_ingreso = :id');
    $statement->execute([':id' => $row['id_libro_ingreso']]);
    $totalDeterminaciones = (int) $statement->fetchColumn();

    $statement = db()->prepare('DELETE FROM libro_ingreso WHERE id_libro_ingreso = :id');
    $statement->execute([':id' => $row['id_libro_ingreso']]);

    if (!$muestraValida) {
        echo 'FALLÓ: N° de muestra ' . $row['nro_muestra'] . ' en lugar de ' . $muestraEsperada . PHP_EOL;
        exit(1);
    }
    if (!$fechaInicioAnalisisValida) {
        echo 'FALLÓ: no se guardó la fecha de inicio de análisis esperada.' . PHP_EOL;
        exit(1);
    }
    if ($totalDeterminaciones !== count($determinaciones)) {
        echo 'FALLÓ: se guardaron ' . $totalDeterminaciones . ' determinaciones en lugar de ' . count($determinaciones) . PHP_EOL;
        exit(1);
    }

    echo 'OK: ingreso registrado con N° INASE ' . $esperadoInase . ' y N° de muestra ' . $muestraEsperada . '.' . PHP_EOL;
});

require __DIR__ . '/../handlers/libro_ingreso.php';
