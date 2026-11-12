<?php

declare(strict_types=1);

require __DIR__ . '/../includes/boletin_tetrazolio_soja.php';
require __DIR__ . '/../includes/libro_ingreso.php';

function verificar_soja(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

verificar_soja(boletin_tetrazolio_soja_especie_valida('Soja', null), 'Debe reconocer Soja por el nombre.');
verificar_soja(boletin_tetrazolio_soja_especie_valida('Leguminosa', 'Glycine max (L.) Merr.'), 'Debe reconocer soja por el nombre científico.');
verificar_soja(!boletin_tetrazolio_soja_especie_valida('Maíz', 'Zea mays'), 'No debe reconocer otras especies como soja.');

$repeticionBase = array_replace(array_fill_keys(array_keys(boletin_tetrazolio_soja_campos_repeticion()), 0), [
    'viables_sin_defectos' => 100,
    'viables_defectos_leves' => 40,
    'viables_defectos_moderados' => 20,
    'viables_defectos_severos' => 10,
    'no_viables' => 30,
    'vigor_alto' => 80,
    'vigor_medio' => 50,
    'vigor_bajo' => 20,
]);
$repeticionBase['danos_mecanicos_nivel_1'] = 5;
$repeticionBase['danos_ambiente_nivel_1'] = 4;
$repeticionBase['danos_chinches_nivel_1'] = 3;
$repeticionBase['fracturas_nivel_1'] = 2;
$repeticionBase['otros_danos_nivel_1'] = 1;
$repeticionBase['semillas_duras_nivel_1'] = 6;
$repeticiones = [
    $repeticionBase,
    array_replace($repeticionBase, ['viables_sin_defectos' => 120, 'no_viables' => 10]),
    array_replace($repeticionBase, [
        'viables_sin_defectos' => 80,
        'viables_defectos_moderados' => 30,
        'no_viables' => 40,
    ]),
    $repeticionBase,
];
$calculos = calcular_boletin_tetrazolio_soja($repeticiones);
verificar_soja($calculos['viables']['repeticiones'] === [170, 190, 160, 170], 'Debe sumar niveles 1 a 4 como semillas viables.');
verificar_soja($calculos['viables']['promedio'] === 172.5, 'Debe calcular la media de semillas viables.');
verificar_soja($calculos['viables']['porcentaje'] === 86.25, 'Debe expresar viabilidad como porcentaje de 200.');
verificar_soja($calculos['vigor_acumulado']['repeticiones'] === [150, 150, 150, 150], 'Debe sumar A, B y C como vigor acumulado.');
verificar_soja($calculos['danos_acumulados']['danos_mecanicos']['porcentaje'] === 2.5, 'Debe acumular los daños por nivel y expresarlos como porcentaje de 200.');
verificar_soja($calculos['rep_mayor_menor_porcentaje'] === 15, 'Debe calcular la diferencia porcentual entre la repetición mayor y la menor.');

$form = boletin_tetrazolio_soja_form_vacio();
verificar_soja($form['semillas_evaluadas'] === '200' && count($form['repeticiones']) === 4, 'El formulario debe iniciar con cuatro repeticiones de 200 semillas.');
verificar_soja(
    libro_ingreso_boletines_disponibles(['Viabilidad por tetrazolio-Soja'], 'Soja', 'Glycine max') === ['boletin_tetrazolio_soja'],
    'La determinación de viabilidad de soja debe abrir el nuevo boletín.'
);
verificar_soja(
    libro_ingreso_boletines_disponibles(['Vigor por tetrazolio Soja'], 'Soja', 'Glycine max') === ['boletin_tetrazolio_soja'],
    'La determinación de vigor de soja debe abrir el nuevo boletín.'
);
verificar_soja(
    libro_ingreso_boletines_disponibles(['Vigor por tetrazolio (otras semillas y forrajeras)'], 'Maíz', 'Zea mays') === ['boletin_tetrazolio'],
    'Tetrazolio general debe estar disponible para especies distintas de soja.'
);
verificar_soja(
    libro_ingreso_boletines_disponibles(['Vigor por tetrazolio (otras semillas y forrajeras)'], 'Soja', 'Glycine max') === [],
    'Tetrazolio general no debe habilitarse para soja.'
);

echo "OK: especie soja, correspondencias, cuatro repeticiones y cálculos por 200 semillas.\n";
