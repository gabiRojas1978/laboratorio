<?php

declare(strict_types=1);

// Cálculos estadísticos del boletín de Peso de 1000 semillas (ISTA)
function calcular_boletin_peso_1000_semillas(array $pesos): array
{
    $n = count($pesos);
    $sumaPesos = array_sum($pesos);
    $sumaCuadrados = array_sum(array_map(static fn($peso) => $peso * $peso, $pesos));
    $promedio = $n > 0 ? $sumaPesos / $n : 0.0;
    $varianza = $n > 1 ? (($n * $sumaCuadrados) - ($sumaPesos ** 2)) / ($n * ($n - 1)) : 0.0;
    $varianza = max($varianza, 0.0);
    $desvioEstandar = sqrt($varianza);
    $coeficienteVariacion = $promedio != 0.0 ? ($desvioEstandar * 100 / $promedio) : 0.0;
    $resultado = $promedio * 10;

    return [
        'n' => $n,
        'suma_pesos' => $sumaPesos,
        'promedio_peso' => $promedio,
        'varianza' => $varianza,
        'desvio_estandar' => $desvioEstandar,
        'coeficiente_variacion' => $coeficienteVariacion,
        'resultado_peso_mil' => $resultado,
    ];
}
