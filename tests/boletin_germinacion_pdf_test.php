<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/format_helpers.php';
require_once __DIR__ . '/../includes/boletin_germinacion_pdf.php';

function current_empresa_id(): int
{
    return 1;
}

function db(): object
{
    return new class {
        public function prepare(string $sql): object
        {
            return new class {
                public function execute(array $params): void
                {
                }

                public function fetch(): array
                {
                    return $GLOBALS['boletinPdfPrueba'];
                }

                public function fetchAll(): array
                {
                    return $GLOBALS['repeticionesPdfPrueba'];
                }
            };
        }
    };
}

$boletinPdfPrueba = [
    'analisis_nro' => '123',
    'muestra_nro' => '456',
    'analista' => 'Analista de prueba',
    'nombre_cliente' => 'Cliente de prueba',
    'nombre_especie' => 'Soja',
    'cientifico_especie' => 'Glycine max',
    'fecha_siembra' => '2026-10-01',
    'fecha_primer_conteo' => '2026-10-05',
    'fecha_segundo_conteo' => '2026-10-09',
    'pretratamiento_desde' => '2026-09-28',
    'pretratamiento_hasta' => '2026-09-30',
    'pretratamiento_total_dias' => 3,
    'pretratamiento_aplica' => 'si',
    'pretratamiento_frio' => 1,
    'pretratamiento_nitrato_k' => 0,
    'pretratamiento_presecado' => 0,
    'pretratamiento_prelavado' => 0,
    'sustrato_tipo' => 'entre_papel',
    'temperatura_camara_tipo' => '20_30',
    'primer_promedio' => 94.25,
    'resultado_plantulas_normales' => 96,
    'resultado_plantulas_anormales' => 2,
    'resultado_semillas_duras' => 0.5,
    'resultado_semillas_frescas' => 0.25,
    'resultado_semillas_muertas' => 1.25,
];
$repeticionesPdfPrueba = [];
for ($numero = 1; $numero <= 4; $numero++) {
    $repeticion = ['nro_repeticion' => $numero, 'semillas_sembradas' => 100];
    foreach (['primer', 'segundo'] as $conteo) {
        foreach (['normales', 'anormales', 'duras', 'frescas', 'muertas'] as $categoria) {
            $repeticion[$conteo . '_conteo_' . $categoria] = $categoria === 'normales' ? ($conteo === 'primer' ? 94 : 2) : 1;
        }
    }
    $repeticionesPdfPrueba[] = $repeticion;
}

foreach (['primer_conteo', 'finalizado'] as $estado) {
    foreach (['', implode("\n", array_fill(0, 8, 'Observacion de prueba para verificar el espacio disponible.'))] as $observaciones) {
        $boletinPdfPrueba['estado'] = $estado;
        $boletinPdfPrueba['observaciones'] = $observaciones;
        $resultado = build_boletin_germinacion_pdf(1);
        if ($resultado === null || !str_starts_with($resultado['content'], '%PDF-')) {
            throw new RuntimeException('No se genero el PDF de prueba.');
        }
        $paginas = preg_match_all('/\/Type\s*\/Page\b/', $resultado['content']);
        if ($paginas !== 1) {
            throw new RuntimeException('El boletin debe ocupar una hoja: ' . $estado . ', '
                . ($observaciones === '' ? 'sin observaciones' : 'ocho lineas de observaciones') . ', paginas: ' . $paginas);
        }
    }
}

echo "OK: PDF A4 de una hoja en ambos estados, sin observaciones y con ocho lineas.\n";
