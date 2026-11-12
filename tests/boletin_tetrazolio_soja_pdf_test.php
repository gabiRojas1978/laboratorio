<?php

declare(strict_types=1);

require __DIR__ . '/../includes/format_helpers.php';
require __DIR__ . '/../includes/boletin_tetrazolio_soja.php';
require __DIR__ . '/../includes/boletin_tetrazolio_soja_pdf.php';

function verificar_pdf_soja(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

function current_empresa_id(): int
{
    return 4;
}

function db(): BoletinTetrazolioSojaPdfTestDb
{
    return $GLOBALS['boletinSojaPdfDb'];
}

final class BoletinTetrazolioSojaPdfTestDb
{
    public function prepare(string $sql): BoletinTetrazolioSojaPdfTestStatement
    {
        return new BoletinTetrazolioSojaPdfTestStatement($sql);
    }
}

final class BoletinTetrazolioSojaPdfTestStatement
{
    public function __construct(private string $sql)
    {
    }

    public function execute(array $params): void
    {
    }

    public function fetch(): array|false
    {
        return [
            'nro_interno' => 'INT-01',
            'nro_certificado' => 'CERT-01',
            'nombre_solicitante' => 'Solicitante de prueba',
            'nombre_cliente' => 'Cliente de prueba',
            'precinto_muestra' => 'PREC-01',
            'procedencia' => 'Origen de prueba',
            'fecha_recepcion' => '2026-11-01',
            'fecha_analisis' => '2026-11-02',
            'analista' => 'Analista',
            'nombre_especie' => 'Soja',
            'cultivar' => 'Cultivar',
            'muestra_nro' => '20260001',
            'concentracion_tz' => '1%',
            'tiempo_tincion' => '24 h',
            'temperatura_tincion' => '35 °C',
            'observaciones' => 'Observaciones',
            'tolerancia_tabla' => '2%',
        ];
    }

    public function fetchAll(): array
    {
        $repeticion = array_fill_keys(array_keys(boletin_tetrazolio_soja_campos_repeticion()), 0);
        $repeticion['viables_sin_defectos'] = 150;
        $repeticion['viables_defectos_leves'] = 20;
        $repeticion['no_viables'] = 30;

        return [$repeticion, $repeticion, $repeticion, $repeticion];
    }
}

$GLOBALS['boletinSojaPdfDb'] = new BoletinTetrazolioSojaPdfTestDb();
$pdf = build_boletin_tetrazolio_soja_pdf(7);
verificar_pdf_soja($pdf !== null, 'Debe construir el PDF con cuatro repeticiones.');
verificar_pdf_soja(str_starts_with($pdf['content'], '%PDF-'), 'La salida debe ser un PDF válido.');
verificar_pdf_soja($pdf['filename'] === 'boletin_tetrazolio_soja_7.pdf', 'El nombre del archivo debe identificar el boletín.');
$stream = \setasign\Fpdi\PdfParser\StreamReader::createByString($pdf['content']);
$parser = new \setasign\Fpdi\PdfParser\PdfParser($stream);
$lector = new \setasign\Fpdi\PdfReader\PdfReader($parser);
$paginas = $lector->getPageCount();
verificar_pdf_soja($paginas === 2, 'El PDF debe contener exactamente las dos hojas del boletín; páginas: ' . $paginas);

echo "OK: PDF de Tetrazolio (Soja) generado.\n";
