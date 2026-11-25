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
            return new class($sql) {
                public function __construct(private string $sql)
                {
                }

                public function execute(array $params): void
                {
                }

                public function fetch(): array
                {
                    return $GLOBALS['boletinPdfPrueba'];
                }

                public function fetchAll(int $mode = PDO::FETCH_DEFAULT): array
                {
                    if (str_contains($this->sql, 'boletin_germinacion_anormalidades')) {
                        if ($mode !== PDO::FETCH_COLUMN || !str_contains($this->sql, 'a.id_empresa = :id_empresa')) {
                            throw new RuntimeException('La consulta de anormalidades debe limitarse a la empresa activa.');
                        }
                        return $GLOBALS['anormalidadesPdfPrueba'] ?? [];
                    }
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

foreach (['germinacion', 'cold_test', 'envejecimiento_acelerado'] as $tipoBoletin) {
    foreach (['primer_conteo', 'finalizado'] as $estado) {
        foreach (['', implode("\n", array_fill(0, 8, 'Observacion de prueba para verificar el espacio disponible.'))] as $observaciones) {
            $boletinPdfPrueba['estado'] = $estado;
            $boletinPdfPrueba['observaciones'] = $observaciones;
            $resultado = build_boletin_germinacion_pdf(1, $tipoBoletin);
            if ($resultado === null || !str_starts_with($resultado['content'], '%PDF-')) {
                throw new RuntimeException('No se genero el PDF de prueba.');
            }
            if (!str_starts_with($resultado['filename'], 'boletin_' . $tipoBoletin . '_')) {
                throw new RuntimeException('El nombre del PDF no corresponde al tipo de boletin: ' . $tipoBoletin);
            }
            $paginas = preg_match_all('/\/Type\s*\/Page\b/', $resultado['content']);
            if ($paginas !== 1) {
                throw new RuntimeException('El boletin debe ocupar una hoja: ' . $tipoBoletin . ', ' . $estado . ', '
                    . ($observaciones === '' ? 'sin observaciones' : 'ocho lineas de observaciones') . ', paginas: ' . $paginas);
            }
        }
    }
}

$anormalidadesPdfPrueba = ['00/01 Plántula <deforme>', '11/02 Raíz dañada'];
$htmlAnormalidades = boletin_germinacion_anormalidades_pdf_html($anormalidadesPdfPrueba);
if (!str_contains($htmlAnormalidades, '00/01 Plántula &lt;deforme&gt;') || !str_contains($htmlAnormalidades, '11/02 Raíz dañada')
    || boletin_germinacion_anormalidades_pdf_html([]) !== '') {
    throw new RuntimeException('Las anormalidades deben imprimirse completas y escapadas, solo cuando existen.');
}
$resultado = build_boletin_germinacion_pdf(1, 'germinacion');
if ($resultado === null || !str_starts_with($resultado['content'], '%PDF-')) {
    throw new RuntimeException('Debe generarse el PDF con varias anormalidades.');
}
$anormalidadesPdfPrueba = array_map(static fn(int $i): string => 'Anormalidad ' . $i . ' con descripción de prueba', range(1, 71));
$resultado = build_boletin_germinacion_pdf(1, 'germinacion');
if ($resultado === null || preg_match_all('/\/Type\s*\/Page\b/', $resultado['content']) < 2) {
    throw new RuntimeException('Una lista extensa de anormalidades debe poder continuar en otra página.');
}

echo "OK: PDFs A4 de una hoja para los tres tipos, en ambos estados y con ocho lineas de observaciones.\n";
