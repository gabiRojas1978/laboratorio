<?php

declare(strict_types=1);

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
