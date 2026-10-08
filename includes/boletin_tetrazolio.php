<?php

declare(strict_types=1);

function calcular_boletin_tetrazolio(array $repeticiones): array
{
    $totalViables = 0;
    $totalNoViables = 0;

    foreach ($repeticiones as $repeticion) {
        $viables = (int) $repeticion['viables'];
        $noViables = (int) $repeticion['no_viables'];
        $totalViables += $viables;
        $totalNoViables += $noViables;
    }

    $cantidadRepeticiones = count($repeticiones);
    $totalSemillas = $totalViables + $totalNoViables;
    $porcentajeViables = $totalSemillas > 0 ? ($totalViables / $totalSemillas) * 100 : 0.0;
    $porcentajeNoViables = $totalSemillas > 0 ? ($totalNoViables / $totalSemillas) * 100 : 0.0;

    return [
        'promedio_viables' => $cantidadRepeticiones > 0 ? $totalViables / $cantidadRepeticiones : 0.0,
        'promedio_no_viables' => $cantidadRepeticiones > 0 ? $totalNoViables / $cantidadRepeticiones : 0.0,
        'resultado_viables_porcentaje' => $porcentajeViables,
        'resultado_no_viables_porcentaje' => $porcentajeNoViables,
        'resultado_porcentaje' => $porcentajeViables,
    ];
}
