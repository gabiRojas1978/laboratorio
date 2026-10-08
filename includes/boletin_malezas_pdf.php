<?php

declare(strict_types=1);

function build_boletin_malezas_pdf(int $boletinId): ?array
{
    try {
        $statement = db()->prepare(
            'SELECT b.*, cl.nombre_cliente, e.nombre_especie AS nombre_especie_catalogo, e.cientifico_especie
             FROM boletines_malezas b
             INNER JOIN clientes cl ON cl.id_cliente = b.id_cliente AND cl.id_empresa = b.id_empresa
             LEFT JOIN especies e ON e.id_especie = b.id_especie AND e.id_empresa = b.id_empresa
             WHERE b.id_boletin_malezas = :id AND b.id_empresa = :id_empresa
             LIMIT 1'
        );
        $statement->execute([':id' => $boletinId, ':id_empresa' => current_empresa_id()]);
        $boletin = $statement->fetch();
        if ($boletin === false) {
            return null;
        }

        $speciesStatement = db()->prepare(
            'SELECT especie_latin, numero_semillas
             FROM boletin_malezas_especies
             WHERE id_boletin_malezas = :id_boletin_malezas
             ORDER BY especie_latin ASC'
        );
        $speciesStatement->execute([':id_boletin_malezas' => $boletinId]);
        $species = $speciesStatement->fetchAll();

        $determinationsStatement = db()->prepare(
            'SELECT od.nombre_determinacion
             FROM boletin_malezas_determinaciones bmd
             INNER JOIN otras_determinaciones od ON od.id_determinacion = bmd.id_determinacion
               WHERE bmd.id_boletin_malezas = :id_boletin_malezas AND od.id_empresa = :id_empresa
             ORDER BY od.nombre_determinacion ASC'
        );
        $determinationsStatement->execute([':id_boletin_malezas' => $boletinId, ':id_empresa' => current_empresa_id()]);
        $determinations = $determinationsStatement->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $throwable) {
        return null;
    }

    $escape = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    $fecha = !empty($boletin['fecha_analisis']) ? format_date_short((string) $boletin['fecha_analisis']) : '';
    $types = [
        'completo' => '*Completo',
        'limitado' => '**Limitado',
        'reducido' => '***Reducido',
        'reducido_limitado' => '****Limitado y reducido',
    ];
    $checks = [];
    foreach ($types as $key => $label) {
        $checks[] = '<span class="type-choice">' . $escape($label) . ' [' . ($boletin['tipo_analisis'] === $key ? 'X' : '&nbsp;') . ']</span>';
    }

    $speciesRows = '';
    foreach ($species as $row) {
        $speciesRows .= '<tr><td>' . $escape($row['especie_latin']) . '</td><td>'
            . number_format((int) $row['numero_semillas'], 0, ',', '.') . '</td></tr>';
    }
    if ($speciesRows === '') {
        $speciesRows = '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';
    }
    $determinationLines = array_map($escape, $determinations);
    $customDetermination = trim((string) ($boletin['otras_determinaciones_texto'] ?? ''));
    if ($customDetermination !== '') {
        $determinationLines[] = $escape($customDetermination);
    }
    $determinationsHtml = implode('<br>', $determinationLines);
    if ($determinationsHtml === '') {
        $determinationsHtml = '&nbsp;';
    }

    $html = '<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
        @page { margin: 11mm 12mm; }
        body { font-family: sans-serif; color: #111; font-size: 8.5pt; }
        h1 { font-family: serif; font-size: 15pt; text-align: center; margin: 0 0 12mm; }
        .rule { border-top: 1px solid #111; margin-bottom: 8mm; }
        table { border-collapse: collapse; width: 100%; }
        .meta { width: 60%; margin: 0 0 5mm auto; }
        .meta td { border: 1px solid #111; height: 6mm; padding: 1mm 2mm; }
        .meta td:first-child { border: 0; width: 33%; text-align: right; font-weight: bold; }
        .identity { margin-bottom: 4mm; }
        .identity td { border: 1px solid #111; padding: 2mm; }
        .identity td:first-child { background: #ddd; width: 22%; font-weight: bold; }
        .type-table { margin: 4mm 0 0; }
        .type-table th { background: #ddd; text-align: center; padding: 2mm; }
        .type-table td { padding: 1.5mm 2mm; }
        .type-choice { display: inline-block; width: 48%; }
        .weight { border: 0; }
        .weight td { padding: 2mm; }
        .species-table th, .species-table td { border: 1px solid #111; padding: 2mm; }
        .species-table th { background: #ddd; text-align: center; }
        .species-table td:last-child, .species-table th:last-child { width: 24mm; text-align: center; }
        .species-table td { height: 6mm; }
        .species-table .blank { height: 6mm; }
        .notes { margin-top: 4mm; }
        .notes th { background: #ddd; text-align: center; padding: 2mm; }
        .notes td { height: 22mm; vertical-align: top; padding: 2mm; }
        .footnotes { margin-top: 3mm; font-size: 7pt; line-height: 1.3; }
    </style></head><body>
        <h1>OTRAS ESPECIES EN NÚMERO</h1>
        <div class="rule"></div>
        <table class="meta">
            <tr><td>Análisis Nº</td><td>' . $escape($boletin['analisis_nro']) . '</td></tr>
            <tr><td>Fecha</td><td>' . $escape($fecha) . '</td></tr>
            <tr><td>Analista</td><td>' . $escape($boletin['analista']) . '</td></tr>
        </table>
        <table class="identity">
            <tr><td>Cliente</td><td>' . $escape($boletin['nombre_cliente']) . '</td><td>Muestra Nº</td><td>' . $escape($boletin['muestra_nro']) . '</td></tr>
            <tr><td>Especie remitente</td><td>' . $escape($boletin['nombre_especie_catalogo'] ?? $boletin['especie_remitente']) . '</td><td>Lote</td><td>' . $escape($boletin['lote']) . '</td></tr>
            <tr><td>Especie (Latín)</td><td colspan="3"><em>' . $escape($boletin['cientifico_especie'] ?? $boletin['especie_latin']) . '</em></td></tr>
        </table>
        <table class="type-table">
            <tr><th colspan="2">Tipo de análisis</th></tr>
            <tr><td colspan="2">' . implode(' &nbsp; ', $checks) . '</td></tr>
        </table>
        <table class="weight"><tr><td>Análisis realizado en: <strong>' . $escape($boletin['peso_analisis']) . ' g</strong></td></tr></table>
        <table class="species-table">
            <tr><th>Otras especies en número</th><th>Nº semillas</th></tr>
            ' . $speciesRows . '
        </table>
        <table class="notes"><tr><th>Otras determinaciones</th></tr><tr><td>' . $determinationsHtml . '</td></tr></table>
        <table class="notes"><tr><th>Observaciones</th></tr><tr><td>' . nl2br($escape($boletin['observaciones'] ?? '')) . '</td></tr></table>
        <div class="footnotes">
            * Análisis completo: nominación y cuantificación del total de especies halladas en la muestra analizada.<br>
            ** Análisis limitado: cuantificación de todas las especies citadas por el usuario.<br>
            *** Análisis reducido: nominación y cuantificación del total de especies halladas en una muestra inferior al peso establecido por las reglas ISTA vigentes.<br>
            **** Análisis reducido y limitado: cuantificación de las especies citadas por el usuario en una muestra inferior al peso establecido por las reglas ISTA vigentes.
        </div>
    </body></html>';

    require_once __DIR__ . '/../vendor/autoload.php';
    $pdf = new \Mpdf\Mpdf([
        'format' => 'A4',
        'margin_left' => 12,
        'margin_right' => 12,
        'margin_top' => 11,
        'margin_bottom' => 11,
        'tempDir' => __DIR__ . '/../vendor/mpdf/mpdf/tmp',
    ]);
    $pdf->WriteHTML($html);

    return [
        'content' => $pdf->Output('', \Mpdf\Output\Destination::STRING_RETURN),
        'filename' => 'boletin_malezas_' . $boletinId . '.pdf',
    ];
}
