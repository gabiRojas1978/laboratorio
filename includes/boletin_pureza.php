<?php

declare(strict_types=1);

function decimales_boletin_pureza(float $pesoInicial): int
{
    if ($pesoInicial < 1) {
        return 4;
    }
    if ($pesoInicial < 10) {
        return 3;
    }
    if ($pesoInicial < 100) {
        return 2;
    }
    if ($pesoInicial < 1000) {
        return 1;
    }

    return 0;
}

function calcular_boletin_pureza(float $pesoInicial, float $pesoSemillaPura, array $materiaInerte, array $otrasSemillas): array
{
    $pesoMateriaInerte = array_sum(array_column($materiaInerte, 'peso'));
    $pesoOtrasSemillas = array_sum(array_column($otrasSemillas, 'peso'));
    $pesoTotal = $pesoSemillaPura + $pesoMateriaInerte + $pesoOtrasSemillas;

    return [
        'peso_inicial' => $pesoInicial,
        'peso_semilla_pura' => $pesoSemillaPura,
        'peso_materia_inerte' => $pesoMateriaInerte,
        'peso_otras_semillas' => $pesoOtrasSemillas,
        'peso_total' => $pesoTotal,
        'diferencia_peso' => $pesoInicial - $pesoTotal,
        'porcentaje_semilla_pura' => $pesoInicial > 0 ? ($pesoSemillaPura / $pesoInicial) * 100 : 0.0,
        'porcentaje_materia_inerte' => $pesoInicial > 0 ? ($pesoMateriaInerte / $pesoInicial) * 100 : 0.0,
        'porcentaje_otras_semillas' => $pesoInicial > 0 ? ($pesoOtrasSemillas / $pesoInicial) * 100 : 0.0,
        'decimales' => decimales_boletin_pureza($pesoInicial),
    ];
}
