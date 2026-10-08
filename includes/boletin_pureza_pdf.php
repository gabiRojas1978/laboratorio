<?php

declare(strict_types=1);

function build_boletin_pureza_pdf(int $boletinId): ?array
{
    try {
        $statement = db()->prepare(
            'SELECT b.*, cl.nombre_cliente, e.nombre_especie, e.cientifico_especie
             FROM boletines_pureza b
             INNER JOIN clientes cl ON cl.id_cliente = b.id_cliente AND cl.id_empresa = b.id_empresa
             LEFT JOIN especies e ON e.id_especie = b.id_especie AND e.id_empresa = b.id_empresa
             WHERE b.id_boletin_pureza = :id AND b.id_empresa = :id_empresa
             LIMIT 1'
        );
        $statement->execute([':id' => $boletinId, ':id_empresa' => current_empresa_id()]);
        $boletin = $statement->fetch();
        if ($boletin === false) {
            return null;
        }

        $componentsStatement = db()->prepare(
            'SELECT tipo_componente, nombre_componente, peso_gramos
             FROM boletin_pureza_componentes
             WHERE id_boletin_pureza = :id_boletin_pureza
             ORDER BY tipo_componente, id_componente_pureza'
        );
        $componentsStatement->execute([':id_boletin_pureza' => $boletinId]);
        $components = $componentsStatement->fetchAll();

        $determinationsStatement = db()->prepare(
            'SELECT od.nombre_determinacion
             FROM boletin_pureza_determinaciones bpd
             INNER JOIN otras_determinaciones od ON od.id_determinacion = bpd.id_determinacion
             WHERE bpd.id_boletin_pureza = :id_boletin_pureza
             ORDER BY od.nombre_determinacion ASC'
        );
        $determinationsStatement->execute([':id_boletin_pureza' => $boletinId]);
        $determinaciones = $determinationsStatement->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $throwable) {
        return null;
    }

    $inertes = array_values(array_filter($components, static fn($row) => $row['tipo_componente'] === 'materia_inerte'));
    $otras = array_values(array_filter($components, static fn($row) => $row['tipo_componente'] === 'otras_semillas'));
    $escape = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    $pesoDecimales = decimales_boletin_pureza((float) $boletin['peso_inicial']);
    $formatGramos = static fn($value): string => number_format((float) $value, $pesoDecimales, ',', '.');
    $fecha = !empty($boletin['fecha_analisis']) ? format_date_short((string) $boletin['fecha_analisis']) : '';

    $inertesHtml = '';
    foreach ($inertes as $componente) {
        $inertesHtml .= '<tr><td>' . $escape($componente['nombre_componente']) . '</td><td>' . $formatGramos($componente['peso_gramos']) . '</td></tr>';
    }
    if ($inertesHtml === '') {
        $inertesHtml = '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';
    }

    $otrasHtml = '';
    foreach ($otras as $componente) {
        $otrasHtml .= '<tr><td>' . $escape($componente['nombre_componente']) . '</td><td>' . $formatGramos($componente['peso_gramos']) . '</td></tr>';
    }
    if ($otrasHtml === '') {
        $otrasHtml = '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';
    }

    $determinacionesHtml = implode('<br>', array_map($escape, $determinaciones));
    $determinacionLibre = trim((string) ($boletin['otras_determinaciones_texto'] ?? ''));
    if ($determinacionLibre !== '') {
        $determinacionesHtml .= ($determinacionesHtml !== '' ? '<br>' : '') . $escape($determinacionLibre);
    }
    if ($determinacionesHtml === '') {
        $determinacionesHtml = '&nbsp;';
    }
    $observaciones = nl2br($escape($boletin['observaciones'] ?? ''));

    $html = '<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
        @page { margin: 10mm 11mm; }
        body { font-family: sans-serif; font-size: 8pt; color: #111; }
        h1 { font-family: serif; font-size: 14pt; text-align: center; margin: 0 0 7mm; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #111; padding: 1.2mm; }
        .meta { width: 54%; margin: 0 0 4mm auto; }
        .meta td:first-child { width: 32%; border: 0; text-align: right; font-weight: bold; }
        .meta td:last-child { height: 5mm; }
        .species { margin-bottom: 3mm; }
        .species td:first-child { width: 20%; text-align: right; border: 0; font-weight: bold; }
        .decimals { width: 36%; margin: 0 0 3mm auto; font-size: 7pt; }
        .decimals th, .decimals td { text-align: center; padding: 1mm; }
        .shade { background: #ddd; font-weight: bold; }
        .percentages { margin: 2mm 0; }
        .percentages th, .percentages td { text-align: center; height: 7mm; }
        .components { margin-top: 2mm; }
        .components th { background: #ddd; text-align: center; }
        .components td:last-child { width: 24mm; text-align: right; }
        .components td { height: 5mm; }
        .summary { margin-top: 3mm; }
        .summary th { background: #ddd; }
        .summary th:first-child { width: 46%; }
        .summary td { text-align: right; }
        .observations { margin-top: 3mm; }
        .observations th { background: #ddd; text-align: center; }
        .observations td { height: 22mm; vertical-align: top; }
    </style></head><body>
        <h1>BOLETÍN DE PUREZA FÍSICA - BOTÁNICA</h1>
        <table class="meta">
            <tr><td>Análisis Nº</td><td>' . $escape($boletin['analisis_nro']) . '</td></tr>
            <tr><td>Fecha</td><td>' . $escape($fecha) . '</td></tr>
            <tr><td>Analista</td><td>' . $escape($boletin['analista']) . '</td></tr>
        </table>
        <table class="species">
            <tr><td>Cliente</td><td>' . $escape($boletin['nombre_cliente']) . '</td></tr>
            <tr><td>Especie Remitente</td><td>' . $escape($boletin['nombre_especie']) . '</td></tr>
            <tr><td>Especie (Latín)</td><td><em>' . $escape($boletin['cientifico_especie']) . '</em></td></tr>
        </table>
        <table class="decimals">
            <tr><th colspan="2" class="shade">Tabla Nº decimales</th></tr>
            <tr><td>Menor a 1 g</td><td>4</td></tr>
            <tr><td>1 - 9,999 g</td><td>3</td></tr>
            <tr><td>10 - 99,99 g</td><td>2</td></tr>
            <tr><td>100 - 999 g</td><td>1</td></tr>
            <tr><td>1000 g o más</td><td>0</td></tr>
        </table>
        <table class="percentages">
            <tr><th class="shade">% S.P.</th><th class="shade">% M.I.</th><th class="shade">% O.S.</th></tr>
            <tr><td>' . number_format((float) $boletin['porcentaje_semilla_pura'], 2, ',', '.') . ' %</td><td>'
        . number_format((float) $boletin['porcentaje_materia_inerte'], 2, ',', '.') . ' %</td><td>'
        . number_format((float) $boletin['porcentaje_otras_semillas'], 2, ',', '.') . ' %</td></tr>
        </table>
        <table class="components">
            <tr><th colspan="2">Materia Inerte</th></tr>
            <tr><th>Componente</th><th>Grs.</th></tr>
            ' . $inertesHtml . '
        </table>
        <table class="components">
            <tr><th colspan="2">Otras Semillas</th></tr>
            <tr><th>Especie / componente</th><th>Grs.</th></tr>
            ' . $otrasHtml . '
        </table>
        <table class="summary">
            <tr><th>Concepto</th><th>Grs.</th><th>%</th></tr>
            <tr><th>Semilla Pura (S.P.)</th><td>' . $formatGramos($boletin['peso_semilla_pura']) . '</td><td>' . number_format((float) $boletin['porcentaje_semilla_pura'], 2, ',', '.') . '</td></tr>
            <tr><th>Materia Inerte (M.I.)</th><td>' . $formatGramos($boletin['peso_materia_inerte']) . '</td><td>' . number_format((float) $boletin['porcentaje_materia_inerte'], 2, ',', '.') . '</td></tr>
            <tr><th>Otras Semillas (O.S.)</th><td>' . $formatGramos($boletin['peso_otras_semillas']) . '</td><td>' . number_format((float) $boletin['porcentaje_otras_semillas'], 2, ',', '.') . '</td></tr>
            <tr><th>Total</th><td>' . $formatGramos($boletin['peso_total']) . '</td><td>' . number_format((float) $boletin['peso_inicial'] > 0 ? ((float) $boletin['peso_total'] / (float) $boletin['peso_inicial']) * 100 : 0, 2, ',', '.') . '</td></tr>
            <tr><th>Peso inicial</th><td>' . $formatGramos($boletin['peso_inicial']) . '</td><td></td></tr>
            <tr><th>Diferencia</th><td>' . $formatGramos($boletin['diferencia_peso']) . '</td><td></td></tr>
        </table>
        <table class="observations"><tr><th>Otras Determinaciones / Observaciones</th></tr><tr><td><strong>Otras determinaciones:</strong><br>' . $determinacionesHtml . '<br><br><strong>Observaciones:</strong><br>' . $observaciones . '</td></tr></table>
    </body></html>';

    require_once __DIR__ . '/../vendor/autoload.php';
    $pdf = new \Mpdf\Mpdf([
        'format' => 'A4',
        'margin_left' => 11,
        'margin_right' => 11,
        'margin_top' => 10,
        'margin_bottom' => 10,
        'tempDir' => __DIR__ . '/../vendor/mpdf/mpdf/tmp',
    ]);
    $pdf->WriteHTML($html);

    return [
        'content' => $pdf->Output('', \Mpdf\Output\Destination::STRING_RETURN),
        'filename' => 'boletin_pureza_' . $boletinId . '.pdf',
    ];
}
