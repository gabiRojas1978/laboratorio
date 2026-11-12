<?php

declare(strict_types=1);

function boletin_tetrazolio_soja_niveles_viabilidad(): array
{
    return [
        'viables_sin_defectos' => 'Viables sin defectos',
        'viables_defectos_leves' => 'Viables con defectos leves',
        'viables_defectos_moderados' => 'Viables con defectos moderados',
        'viables_defectos_severos' => 'Viables con defectos severos',
        'no_viables' => 'No viables',
    ];
}

function boletin_tetrazolio_soja_categorias_vigor(): array
{
    return [
        'vigor_alto' => 'Vigor alto',
        'vigor_medio' => 'Vigor medio',
        'vigor_bajo' => 'Vigor bajo',
    ];
}

function boletin_tetrazolio_soja_tipos_danos(): array
{
    return [
        'danos_mecanicos' => 'Daños mecánicos',
        'danos_ambiente' => 'Daños del ambiente',
        'danos_chinches' => 'Daños de chinches',
        'fracturas' => 'Fracturas',
        'otros_danos' => 'Otros daños',
        'semillas_duras' => 'Semillas duras',
    ];
}

function boletin_tetrazolio_soja_campos_repeticion(): array
{
    $campos = array_merge(boletin_tetrazolio_soja_niveles_viabilidad(), boletin_tetrazolio_soja_categorias_vigor());
    foreach (range(1, 5) as $nivel) {
        foreach (boletin_tetrazolio_soja_tipos_danos() as $tipo => $etiqueta) {
            $campos[$tipo . '_nivel_' . $nivel] = $etiqueta . ' (nivel ' . $nivel . ')';
        }
    }

    return $campos;
}

function boletin_tetrazolio_soja_form_vacio(): array
{
    $form = [
        'id_cliente' => '',
        'id_especie' => '',
        'muestra_nro' => '',
        'nro_interno' => '',
        'nro_certificado' => '',
        'nombre_solicitante' => '',
        'precinto_muestra' => '',
        'procedencia' => '',
        'fecha_recepcion' => '',
        'fecha_analisis' => '',
        'analista' => '',
        'cultivar' => '',
        'semillas_evaluadas' => '200',
        'concentracion_tz' => '',
        'tiempo_tincion' => '',
        'temperatura_tincion' => '',
        'rep_mayor_menor' => '',
        'tolerancia_tabla' => '',
        'observaciones' => '',
        'repeticiones' => [],
    ];
    foreach (range(1, 4) as $numero) {
        $form['repeticiones'][$numero] = array_fill_keys(array_keys(boletin_tetrazolio_soja_campos_repeticion()), '');
    }

    return $form;
}

function boletin_tetrazolio_soja_especie_valida(string $nombre, ?string $cientifico): bool
{
    $normalizar = static function (string $valor): string {
        $valor = mb_strtolower(trim($valor), 'UTF-8');
        return strtr($valor, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
    };

    return preg_match('/^soja(?:\b|$)/u', $normalizar($nombre)) === 1
        || str_starts_with($normalizar((string) $cientifico), 'glycine max');
}

function calcular_boletin_tetrazolio_soja(array $repeticiones): array
{
    $categorias = array_merge(boletin_tetrazolio_soja_niveles_viabilidad(), boletin_tetrazolio_soja_categorias_vigor());
    $resumen = [];
    foreach (array_keys($categorias) as $categoria) {
        $valores = array_map(static fn(array $repeticion): int => (int) $repeticion[$categoria], $repeticiones);
        $promedio = count($valores) > 0 ? array_sum($valores) / count($valores) : 0.0;
        $resumen[$categoria] = [
            'repeticiones' => $valores,
            'promedio' => $promedio,
            'porcentaje' => $promedio / 2,
        ];
    }

    $danosAcumulados = [];
    foreach (boletin_tetrazolio_soja_tipos_danos() as $tipo => $etiqueta) {
        $valores = array_map(
            static function (array $repeticion) use ($tipo): int {
                $total = 0;
                foreach (range(1, 5) as $nivel) {
                    $total += (int) $repeticion[$tipo . '_nivel_' . $nivel];
                }
                return $total;
            },
            $repeticiones
        );
        $promedio = count($valores) > 0 ? array_sum($valores) / count($valores) : 0.0;
        $danosAcumulados[$tipo] = [
            'repeticiones' => $valores,
            'promedio' => $promedio,
            'porcentaje' => $promedio / 2,
        ];
    }

    $viables = [];
    $vigorAcumulado = [];
    foreach ($repeticiones as $repeticion) {
        $viables[] = array_sum(array_map(
            static fn(string $categoria): int => (int) $repeticion[$categoria],
            ['viables_sin_defectos', 'viables_defectos_leves', 'viables_defectos_moderados', 'viables_defectos_severos']
        ));
        $vigorAcumulado[] = array_sum(array_map(
            static fn(string $categoria): int => (int) $repeticion[$categoria],
            ['vigor_alto', 'vigor_medio', 'vigor_bajo']
        ));
    }

    $promedioViables = count($viables) > 0 ? array_sum($viables) / count($viables) : 0.0;
    $promedioVigor = count($vigorAcumulado) > 0 ? array_sum($vigorAcumulado) / count($vigorAcumulado) : 0.0;

    return [
        'categorias' => $resumen,
        'danos_acumulados' => $danosAcumulados,
        'rep_mayor_menor_porcentaje' => count($viables) > 0 ? (max($viables) - min($viables)) / 2 : 0.0,
        'viables' => [
            'repeticiones' => $viables,
            'promedio' => $promedioViables,
            'porcentaje' => $promedioViables / 2,
        ],
        'vigor_acumulado' => [
            'repeticiones' => $vigorAcumulado,
            'promedio' => $promedioVigor,
            'porcentaje' => $promedioVigor / 2,
        ],
    ];
}
