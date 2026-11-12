<?php

declare(strict_types=1);

function agenda_conteos_tipos(): array
{
    return [
        'primer_conteo' => 'Primer conteo',
        'conteo_final' => 'Conteo final',
    ];
}

function agenda_conteos_cargar(int $idEmpresa, DateTimeImmutable $hoy): array
{
    $hoy = $hoy->setTime(0, 0);
    $dias = [];
    for ($i = 0; $i < 7; $i++) {
        $dias[$hoy->modify('+' . $i . ' days')->format('Y-m-d')] = [];
    }
    $vencidos = [];
    $sinProgramar = 0;
    $statement = db()->prepare(
        'SELECT li.id_libro_ingreso, li.nro_muestra, li.fecha_inicio_analisis,
                li.primer_conteo_realizado_at, li.conteo_final_realizado_at,
                e.nombre_especie, e.primer_conteo, e.conteo_final, c.nombre_cliente
         FROM libro_ingreso li
         INNER JOIN clientes c ON c.id_cliente = li.id_cliente AND c.id_empresa = li.id_empresa
         LEFT JOIN especies e ON e.id_especie = li.id_especie AND e.id_empresa = li.id_empresa
         WHERE li.id_empresa = :id_empresa
           AND (li.primer_conteo_realizado_at IS NULL OR li.conteo_final_realizado_at IS NULL)'
    );
    $statement->execute([':id_empresa' => $idEmpresa]);
    foreach ($statement->fetchAll() as $ingreso) {
        $faltanDatos = false;
        foreach (agenda_conteos_tipos() as $tipo => $etiqueta) {
            if ($ingreso[$tipo . '_realizado_at'] !== null) {
                continue;
            }
            if ($ingreso['fecha_inicio_analisis'] === null || $ingreso[$tipo] === null || (int) $ingreso[$tipo] < 1) {
                $faltanDatos = true;
                continue;
            }
            $inicio = new DateTimeImmutable($ingreso['fecha_inicio_analisis'], $hoy->getTimezone());
            $fecha = $inicio->modify('+' . (int) $ingreso[$tipo] . ' days')->format('Y-m-d');
            $tarea = [
                'id_libro_ingreso' => (int) $ingreso['id_libro_ingreso'],
                'nro_muestra' => $ingreso['nro_muestra'],
                'nombre_cliente' => $ingreso['nombre_cliente'],
                'nombre_especie' => $ingreso['nombre_especie'],
                'tipo' => $tipo,
                'etiqueta' => $etiqueta,
                'fecha' => $fecha,
            ];
            if ($fecha < $hoy->format('Y-m-d')) {
                $vencidos[] = $tarea;
            } elseif (isset($dias[$fecha])) {
                $dias[$fecha][] = $tarea;
            }
        }
        if ($faltanDatos) {
            $sinProgramar++;
        }
    }
    $ordenar = static fn(array $a, array $b): int =>
        [$a['fecha'], $a['nro_muestra'], $a['tipo']] <=> [$b['fecha'], $b['nro_muestra'], $b['tipo']];
    usort($vencidos, $ordenar);
    foreach ($dias as &$tareas) {
        usort($tareas, $ordenar);
    }
    unset($tareas);

    return ['dias' => $dias, 'vencidos' => $vencidos, 'sin_programar' => $sinProgramar];
}

function agenda_conteos_marcar_realizado(int $idEmpresa, int $idIngreso, string $tipo): void
{
    if (!array_key_exists($tipo, agenda_conteos_tipos())) {
        throw new InvalidArgumentException('El tipo de conteo no es válido.');
    }
    $campoRealizado = $tipo . '_realizado_at';
    $statement = db()->prepare(
        'UPDATE libro_ingreso li
         INNER JOIN especies e ON e.id_especie = li.id_especie AND e.id_empresa = li.id_empresa
         SET li.' . $campoRealizado . ' = CURRENT_TIMESTAMP
         WHERE li.id_libro_ingreso = :id AND li.id_empresa = :id_empresa
           AND li.fecha_inicio_analisis IS NOT NULL AND e.' . $tipo . ' > 0
           AND li.' . $campoRealizado . ' IS NULL'
    );
    $statement->execute([':id' => $idIngreso, ':id_empresa' => $idEmpresa]);
    if ($statement->rowCount() !== 1) {
        throw new InvalidArgumentException('El conteo no está pendiente, no tiene datos para programarlo o no pertenece a tu empresa. Recarga la agenda.');
    }
}
