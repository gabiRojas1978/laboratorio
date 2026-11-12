<?php

declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../includes/libro_ingreso.php';

$idEmpresa = (int) db()->query('SELECT id_empresa FROM empresas ORDER BY id_empresa ASC LIMIT 1')->fetchColumn();
$statement = db()->prepare('SELECT id_cliente FROM clientes WHERE id_empresa = :id_empresa ORDER BY id_cliente ASC LIMIT 1');
$statement->execute([':id_empresa' => $idEmpresa]);
$idCliente = (int) $statement->fetchColumn();
$statement = db()->prepare('SELECT id_especie FROM especies WHERE id_empresa = :id_empresa ORDER BY id_especie ASC LIMIT 1');
$statement->execute([':id_empresa' => $idEmpresa]);
$idEspecie = (int) $statement->fetchColumn();

if ($idEmpresa === 0 || $idCliente === 0 || $idEspecie === 0) {
    echo 'OMITIDO: se requiere una empresa con cliente y especie.' . PHP_EOL;
    exit(0);
}

$anio = libro_ingreso_anio_actual();
$nroInase = libro_ingreso_proximo_nro_inase($idEmpresa, $anio);
$fechaInicioAnalisis = date('Y-m-d', strtotime('+1 day'));
$statement = db()->prepare(
    'INSERT INTO libro_ingreso
        (id_empresa, id_usuario, anio_libro_ingreso, nro_inase, nro_muestra, presupuesto, facturado,
         fecha_entrada, fecha_inicio_analisis, id_cliente, id_especie)
     VALUES (:id_empresa, NULL, :anio, :nro_inase, :nro_muestra, :presupuesto, :facturado,
         :fecha_entrada, :fecha_inicio_analisis, :id_cliente, :id_especie)'
);
$statement->execute([
    ':id_empresa' => $idEmpresa,
    ':anio' => $anio,
    ':nro_inase' => $nroInase,
    ':nro_muestra' => libro_ingreso_nro_muestra($anio, $nroInase),
    ':presupuesto' => 'SI',
    ':facturado' => 'NO',
    ':fecha_entrada' => date('Y-m-d'),
    ':fecha_inicio_analisis' => date('Y-m-d'),
    ':id_cliente' => $idCliente,
    ':id_especie' => $idEspecie,
]);
$ingresoId = (int) db()->lastInsertId();

$_SESSION['user'] = ['id' => null, 'id_empresa' => $idEmpresa];
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'update_libro_ingreso_fecha' => '1',
    'ingreso_id' => (string) $ingresoId,
    'fecha_inicio_analisis' => $fechaInicioAnalisis,
];
$errors = [];

register_shutdown_function(static function () use ($ingresoId, $fechaInicioAnalisis): void {
    $statement = db()->prepare('SELECT fecha_inicio_analisis FROM libro_ingreso WHERE id_libro_ingreso = :id');
    $statement->execute([':id' => $ingresoId]);
    $fechaGuardada = $statement->fetchColumn();

    $statement = db()->prepare('DELETE FROM libro_ingreso WHERE id_libro_ingreso = :id');
    $statement->execute([':id' => $ingresoId]);

    if ($fechaGuardada !== $fechaInicioAnalisis) {
        echo 'FALLÓ: no se actualizó la fecha de inicio de análisis.' . PHP_EOL;
        exit(1);
    }

    echo 'OK: fecha de inicio de análisis actualizada.' . PHP_EOL;
});

require __DIR__ . '/../handlers/libro_ingreso.php';
