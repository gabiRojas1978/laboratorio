<?php

declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../includes/format_helpers.php';
require __DIR__ . '/../includes/libro_ingreso.php';
require __DIR__ . '/../includes/agenda_conteos.php';

function verificar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

function tareas_de(array $agenda, int $id): array
{
    $tareas = $agenda['vencidos'];
    foreach ($agenda['dias'] as $dia) {
        $tareas = array_merge($tareas, $dia);
    }

    return array_values(array_filter($tareas, static fn(array $tarea): bool => $tarea['id_libro_ingreso'] === $id));
}

$empresas = array_map('intval', db()->query('SELECT id_empresa FROM empresas ORDER BY id_empresa LIMIT 2')->fetchAll(PDO::FETCH_COLUMN));
verificar(count($empresas) === 2, 'Se necesitan dos empresas para verificar el aislamiento.');
$hoy = new DateTimeImmutable('today');
$ids = [];
$_SESSION['user'] = ['id_empresa' => $empresas[0]];

db()->beginTransaction();
try {
    $especies = [];
    $clientes = [];
    foreach ($empresas as $empresa) {
        $statement = db()->prepare('SELECT id_cliente FROM clientes WHERE id_empresa = ? LIMIT 1');
        $statement->execute([$empresa]);
        $clientes[$empresa] = (int) $statement->fetchColumn();
        verificar($clientes[$empresa] > 0, 'Cada empresa necesita un cliente.');
        $statement = db()->prepare('INSERT INTO especies (id_empresa, nombre_especie, primer_conteo, conteo_final) VALUES (?, ?, 5, 8)');
        $statement->execute([$empresa, 'Especie <prueba> & conteos']);
        $especies[$empresa] = (int) db()->lastInsertId();
    }

    $crearIngreso = static function (int $empresa, ?string $inicio, ?int $especie = null) use ($especies, $clientes): int {
        $anio = libro_ingreso_anio_actual();
        $nro = libro_ingreso_proximo_nro_inase($empresa, $anio);
        $statement = db()->prepare(
            'INSERT INTO libro_ingreso (id_empresa, anio_libro_ingreso, nro_inase, nro_muestra, fecha_entrada, fecha_inicio_analisis, id_cliente, id_especie)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([$empresa, $anio, $nro, libro_ingreso_nro_muestra($anio, $nro), date('Y-m-d'), $inicio, $clientes[$empresa], $especie ?? $especies[$empresa]]);

        return (int) db()->lastInsertId();
    };

    $empresa = $empresas[0];
    $ids['hoy'] = $crearIngreso($empresa, $hoy->modify('-5 days')->format('Y-m-d'));
    $ids['final_hoy'] = $crearIngreso($empresa, $hoy->modify('-8 days')->format('Y-m-d'));
    $ids['vencido'] = $crearIngreso($empresa, $hoy->modify('-15 days')->format('Y-m-d'));
    $ids['ultimo'] = $crearIngreso($empresa, $hoy->modify('+1 day')->format('Y-m-d'));
    $ids['fuera'] = $crearIngreso($empresa, $hoy->modify('+2 days')->format('Y-m-d'));
    $ids['sin_fecha'] = $crearIngreso($empresa, null);
    $ids['otra_empresa'] = $crearIngreso($empresas[1], $hoy->modify('-15 days')->format('Y-m-d'));
    $statement = db()->prepare('INSERT INTO especies (id_empresa, nombre_especie, primer_conteo) VALUES (?, ?, 5)');
    $statement->execute([$empresa, 'Solo primer conteo']);
    $especieParcial = (int) db()->lastInsertId();
    $ids['parcial'] = $crearIngreso($empresa, $hoy->modify('-5 days')->format('Y-m-d'), $especieParcial);

    $agenda = agenda_conteos_cargar($empresa, $hoy);
    verificar(count($agenda['dias']) === 7, 'La agenda debe mostrar exactamente siete días.');
    verificar(array_key_first($agenda['dias']) === $hoy->format('Y-m-d'), 'La agenda debe comenzar hoy.');
    verificar(array_key_last($agenda['dias']) === $hoy->modify('+6 days')->format('Y-m-d'), 'Debe terminar en hoy + 6 días.');
    $tareas = tareas_de($agenda, $ids['hoy']);
    verificar(count($tareas) === 2 && $tareas[0]['fecha'] === $hoy->format('Y-m-d') && $tareas[1]['fecha'] === $hoy->modify('+3 days')->format('Y-m-d'), 'Los conteos deben sumar 5 y 8 días desde el inicio.');
    verificar(count(tareas_de($agenda, $ids['final_hoy'])) === 2, 'Debe incluir el primer conteo vencido y el final de hoy.');
    verificar(count(array_filter($agenda['vencidos'], static fn(array $t): bool => $t['id_libro_ingreso'] === $ids['vencido'])) === 2, 'Ambos conteos vencidos deben aparecer.');
    verificar(count(array_filter($agenda['vencidos'], static fn(array $t): bool => $t['fecha'] >= $hoy->format('Y-m-d'))) === 0, 'Los conteos de hoy no están vencidos.');
    verificar(count(tareas_de($agenda, $ids['ultimo'])) === 1, 'Debe incluir el séptimo día de la vista.');
    verificar(tareas_de($agenda, $ids['fuera']) === [], 'No debe incluir hoy + 7 días.');
    verificar(tareas_de($agenda, $ids['otra_empresa']) === [], 'La agenda debe aislar empresas.');
    verificar(tareas_de($agenda, $ids['sin_fecha']) === [], 'No debe inventar fechas sin inicio de análisis.');
    verificar(count(tareas_de($agenda, $ids['parcial'])) === 1 && $agenda['sin_programar'] >= 2, 'Debe programar el conteo conocido y avisar sobre datos faltantes.');

    $bisiesto = $crearIngreso($empresa, '2024-02-24');
    $tareas = tareas_de(agenda_conteos_cargar($empresa, new DateTimeImmutable('2024-02-29')), $bisiesto);
    verificar(array_column($tareas, 'fecha') === ['2024-02-29', '2024-03-03'], 'Debe calcular correctamente años bisiestos y cambio de mes.');
    $finAnio = $crearIngreso($empresa, '2026-12-27');
    $tareas = tareas_de(agenda_conteos_cargar($empresa, new DateTimeImmutable('2027-01-01')), $finAnio);
    verificar(array_column($tareas, 'fecha') === ['2027-01-01', '2027-01-04'], 'Debe calcular correctamente el cambio de año.');

    foreach ([['tipo_invalido', $empresa, $ids['vencido']], ['primer_conteo', $empresa, $ids['otra_empresa']], ['primer_conteo', $empresa, $ids['sin_fecha']], ['conteo_final', $empresa, $ids['parcial']]] as [$tipo, $empresaAccion, $id]) {
        $rechazado = false;
        try {
            agenda_conteos_marcar_realizado($empresaAccion, $id, $tipo);
        } catch (InvalidArgumentException $exception) {
            $rechazado = true;
        }
        verificar($rechazado, 'Debe rechazar acciones inválidas o sobre otra empresa.');
    }
    agenda_conteos_marcar_realizado($empresa, $ids['vencido'], 'primer_conteo');
    $tareas = tareas_de(agenda_conteos_cargar($empresa, $hoy), $ids['vencido']);
    verificar(count($tareas) === 1 && $tareas[0]['tipo'] === 'conteo_final', 'Marcar el primero no debe completar el final.');
    $rechazado = false;
    try {
        agenda_conteos_marcar_realizado($empresa, $ids['vencido'], 'primer_conteo');
    } catch (InvalidArgumentException $exception) {
        $rechazado = true;
    }
    verificar($rechazado, 'No debe sobrescribir una realización anterior.');
    agenda_conteos_marcar_realizado($empresa, $ids['vencido'], 'conteo_final');
    verificar(tareas_de(agenda_conteos_cargar($empresa, $hoy), $ids['vencido']) === [], 'Un ingreso completado no debe permanecer vencido.');
    $statement = db()->prepare('SELECT primer_conteo_realizado_at, conteo_final_realizado_at FROM libro_ingreso WHERE id_libro_ingreso = ?');
    $statement->execute([$ids['vencido']]);
    $realizado = $statement->fetch();
    verificar($realizado['primer_conteo_realizado_at'] !== null && $realizado['conteo_final_realizado_at'] !== null, 'Las realizaciones deben persistir en la base de datos.');

    $_SESSION['agenda_token'] = bin2hex(random_bytes(32));
    $_SERVER['REQUEST_METHOD'] = 'POST';
    foreach ([
        ['agenda_token' => 'incorrecto', 'tipo_conteo' => 'primer_conteo'],
        ['agenda_token' => $_SESSION['agenda_token'], 'tipo_conteo' => 'invalido'],
    ] as $datosInvalidos) {
        $_POST = array_merge(['marcar_conteo_realizado' => '1', 'ingreso_id' => (string) $ids['hoy']], $datosInvalidos);
        $errors = [];
        require __DIR__ . '/../handlers/agenda_conteos.php';
        verificar(count($errors) === 1, 'El handler debe informar solicitudes inválidas.');
    }
    verificar(count(tareas_de(agenda_conteos_cargar($empresa, $hoy), $ids['hoy'])) === 2, 'Las solicitudes inválidas no deben modificar tareas.');

    $errors = [];
    $successMessage = null;
    ob_start();
    try {
        require __DIR__ . '/../pages/dashboard.php';
        $html = ob_get_contents();
    } finally {
        ob_end_clean();
    }
    verificar($errors === [], 'El dashboard debe cargar sin errores.');
    verificar(substr_count($html, '<section class="agenda-dia ') === 7, 'Debe renderizar siete columnas.');
    verificar(strpos($html, 'Especie &lt;prueba&gt; &amp; conteos') !== false, 'Los datos deben escaparse al mostrar tarjetas.');
    verificar(strpos($html, 'name="agenda_token"') !== false && strpos($html, 'Marcar realizado') !== false, 'Debe mostrar formularios de cumplimiento protegidos.');
    verificar(strpos($html, 'Conteos vencidos') !== false, 'Debe mostrar la sección separada de vencidos.');
} finally {
    db()->rollBack();
}

echo 'OK: siete días, vencidos, fechas, aislamiento, cumplimiento independiente, validación y dashboard; datos de prueba revertidos.' . PHP_EOL;
