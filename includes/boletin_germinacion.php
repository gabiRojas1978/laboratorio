<?php

declare(strict_types=1);

function boletin_germinacion_leer_anormalidades(mixed $entrada): array
{
    if (!is_array($entrada)) {
        throw new InvalidArgumentException('La selección de anormalidades no es válida.');
    }
    $ids = [];
    foreach ($entrada as $valor) {
        $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new InvalidArgumentException('La selección de anormalidades no es válida.');
        }
        $ids[] = (int) $id;
    }
    return array_values(array_unique($ids));
}

function boletin_germinacion_guardar_anormalidades(PDO $conexion, int $boletinId, int $empresaId, array $ids): void
{
    if (!$conexion->inTransaction()) {
        throw new LogicException('La selección de anormalidades requiere una transacción.');
    }
    $delete = $conexion->prepare('DELETE FROM boletin_germinacion_anormalidades WHERE id_boletin_germinacion = :id_boletin');
    $delete->execute([':id_boletin' => $boletinId]);

    $insert = $conexion->prepare(
        'INSERT INTO boletin_germinacion_anormalidades (id_boletin_germinacion, id_anormalidad)
         SELECT :id_boletin, id_anormalidad FROM anormalidades
         WHERE id_anormalidad = :id_anormalidad AND id_empresa = :id_empresa'
    );
    foreach ($ids as $id) {
        $insert->execute([':id_boletin' => $boletinId, ':id_anormalidad' => $id, ':id_empresa' => $empresaId]);
        if ($insert->rowCount() !== 1) {
            throw new InvalidArgumentException('Una o más anormalidades seleccionadas no pertenecen a la empresa activa.');
        }
    }
}

// El resultado final acumula las semillas clasificadas en ambos conteos.
function calcular_boletin_germinacion(array $repeticiones): array
{
    $primerPorcentajes = [];
    $normalesPorcentajes = [];
    $anormalesPorcentajes = [];
    $durasPorcentajes = [];
    $frescasPorcentajes = [];
    $muertasPorcentajes = [];
    $segundoConteoCompleto = $repeticiones !== [];

    foreach ($repeticiones as $repeticion) {
        $semillas = (int) $repeticion['semillas_sembradas'];
        if ($semillas <= 0) {
            $segundoConteoCompleto = false;
            continue;
        }

        if ($repeticion['primer_conteo_normales'] !== null) {
            $primerPorcentajes[] = ($repeticion['primer_conteo_normales'] / $semillas) * 100;
        }

        $segundoCampos = [
            $repeticion['segundo_conteo_normales'],
            $repeticion['segundo_conteo_anormales'],
            $repeticion['segundo_conteo_duras'],
            $repeticion['segundo_conteo_frescas'],
            $repeticion['segundo_conteo_muertas'],
        ];
        if (in_array(null, $segundoCampos, true)) {
            $segundoConteoCompleto = false;
            continue;
        }

        $totales = [];
        foreach (['normales', 'anormales', 'duras', 'frescas', 'muertas'] as $categoria) {
            $totales[$categoria] = ($repeticion['primer_conteo_' . $categoria] ?? 0) + $repeticion['segundo_conteo_' . $categoria];
        }
        if (array_sum($totales) !== $semillas) {
            $segundoConteoCompleto = false;
            continue;
        }

        $normalesPorcentajes[] = ($totales['normales'] / $semillas) * 100;
        $anormalesPorcentajes[] = ($totales['anormales'] / $semillas) * 100;
        $durasPorcentajes[] = ($totales['duras'] / $semillas) * 100;
        $frescasPorcentajes[] = ($totales['frescas'] / $semillas) * 100;
        $muertasPorcentajes[] = ($totales['muertas'] / $semillas) * 100;
    }

    $promedio = static fn(array $valores): ?float => $valores !== [] ? array_sum($valores) / count($valores) : null;

    return [
        'primer_promedio' => $promedio($primerPorcentajes),
        'resultado_plantulas_normales' => $segundoConteoCompleto ? $promedio($normalesPorcentajes) : null,
        'resultado_plantulas_anormales' => $segundoConteoCompleto ? $promedio($anormalesPorcentajes) : null,
        'resultado_semillas_duras' => $segundoConteoCompleto ? $promedio($durasPorcentajes) : null,
        'resultado_semillas_frescas' => $segundoConteoCompleto ? $promedio($frescasPorcentajes) : null,
        'resultado_semillas_muertas' => $segundoConteoCompleto ? $promedio($muertasPorcentajes) : null,
        'estado' => $segundoConteoCompleto ? 'finalizado' : 'primer_conteo',
    ];
}
