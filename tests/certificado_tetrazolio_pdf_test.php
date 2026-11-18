<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/format_helpers.php';
require_once __DIR__ . '/../includes/certificado_pdf.php';

function current_empresa_id(): int
{
    return 1;
}

function certificate_logo_path(int $idEmpresa): ?string
{
    return null;
}

function certificate_signature_path(int $idEmpresa): ?string
{
    return $GLOBALS['firmaPrueba'] ?? null;
}

function get_certificate_settings(int $idEmpresa): array
{
    return [
        'localidad_pais' => 'Chivilcoy (6620), Argentina',
        'firma_director_tecnico' => 'Directora técnica',
    ];
}

function empresa_storage_dir(int $idEmpresa): string
{
    return sys_get_temp_dir();
}

function db(): object
{
    return new class {
        public function prepare(string $sql): object
        {
            $isCertificateQuery = str_contains($sql, 'FROM certificados c');

            return new class($isCertificateQuery) {
                public function __construct(private bool $isCertificateQuery)
                {
                }

                public function execute(array $params): void
                {
                }

                public function fetch(): array|false
                {
                    return $this->isCertificateQuery ? $GLOBALS['certificadoTetrazolioPrueba'] : false;
                }

                public function fetchAll(int $mode = 0): array
                {
                    return [];
                }
            };
        }
    };
}

$certificadoTetrazolioPrueba = [
    'tipo_certificado' => 'certificadotetrazolio',
    'nombre_cliente' => 'Ricardo Gallioti',
    'nombre_especie' => 'Soja',
    'cientifico_especie' => 'Glycine max',
    'nombre_cultivar' => null,
    'tetrazolio_detalle_certificado' => 'Muestra de prueba',
    'peso_certificado' => 1000,
    'nro_envases_certificado' => 20,
    'fecha_muestreo_certificado' => '2026-10-01',
    'otras_referencias_certificado' => 'Referencia',
    'precinto_certificado' => 'P-123',
    'fecha_muestras_certificado' => '2026-10-02',
    'fecha_finalizacion_ensayos_certificado' => '2026-10-05',
    'nro_analisis_certificado' => '25-1998',
    'tetrazolio_viable_sin_defectos_certificado' => 50,
    'tetrazolio_viable_defectos_leves_certificado' => 20,
    'tetrazolio_viable_defectos_moderados_certificado' => 10,
    'tetrazolio_viable_defectos_severos_certificado' => 5,
    'tetrazolio_no_viables_certificado' => 15,
    'tetrazolio_vigor_alto_certificado' => 50,
    'tetrazolio_vigor_medio_certificado' => 20,
    'tetrazolio_vigor_bajo_certificado' => 15,
    'tetrazolio_danos_mecanicos_certificado' => 2,
    'tetrazolio_danos_ambientes_certificado' => 3,
    'tetrazolio_danos_chinches_certificado' => 1,
    'tetrazolio_danos_fracturas_certificado' => 2,
    'tetrazolio_danos_otros_certificado' => 1,
    'tetrazolio_duras_certificado' => 0,
    'ampara_totalidad_lote_certificado' => 'N',
    'fecha_emision_certificado' => '2026-10-08',
];

$result = build_certificado_pdf(99);
if ($result === null || !str_starts_with($result['content'], '%PDF-')) {
    throw new RuntimeException('No se generó el certificado de tetrazolio.');
}

$pages = preg_match_all('/\/Type\s*\/Page\b/', $result['content']);
if ($pages !== 1) {
    throw new RuntimeException('El certificado de tetrazolio debe ocupar una hoja; páginas: ' . $pages);
}

if ($result['filename'] !== 'certificado_99.pdf') {
    throw new RuntimeException('El nombre del PDF generado no es el esperado.');
}

@unlink(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $result['filename']);

$firmaPrueba = tempnam(sys_get_temp_dir(), 'firma_certificado_');
if ($firmaPrueba === false) {
    throw new RuntimeException('No se pudo crear la imagen de prueba.');
}
try {
    $image = imagecreatetruecolor(300, 100);
    imagepng($image, $firmaPrueba);
    imagedestroy($image);
    $html = certificate_signature_html($firmaPrueba, 'Directora <técnica>');
    if (!str_contains($html, 'width:30mm;height:10mm') || !str_contains($html, 'Directora &lt;técnica&gt;')) {
        throw new RuntimeException('La firma debe mantener sus proporciones y conservar el texto escapado.');
    }
    if (certificate_signature_html(null, 'Directora <técnica>') !== 'Directora &lt;técnica&gt;') {
        throw new RuntimeException('Sin imagen debe conservarse el texto de firma.');
    }

    foreach (['certificadogeneral', 'certificadoinase', 'certificadotetrazolio'] as $tipo) {
        $certificadoTetrazolioPrueba['tipo_certificado'] = $tipo;
        $result = build_certificado_pdf(99);
        if ($result === null || !preg_match('/\/Subtype\s*\/Image\b/', $result['content'])) {
            throw new RuntimeException('El certificado debe incluir la imagen de firma: ' . $tipo);
        }
        if (preg_match_all('/\/Type\s*\/Page\b/', $result['content']) !== 1) {
            throw new RuntimeException('El certificado con firma debe ocupar una hoja: ' . $tipo);
        }
    }
} finally {
    unlink($firmaPrueba);
    @unlink(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'certificado_99.pdf');
}

echo "OK: certificado de tetrazolio generado en una hoja.\n";
