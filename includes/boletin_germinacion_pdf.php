<?php

declare(strict_types=1);

function build_boletin_germinacion_pdf(int $boletinId): ?array
{
    try {
        $statement = db()->prepare(
            'SELECT b.*, cl.nombre_cliente, e.nombre_especie, e.cientifico_especie
             FROM boletines_germinacion b
             INNER JOIN clientes cl ON cl.id_cliente = b.id_cliente AND cl.id_empresa = b.id_empresa
             LEFT JOIN especies e ON e.id_especie = b.id_especie AND e.id_empresa = b.id_empresa
             WHERE b.id_boletin_germinacion = :id AND b.id_empresa = :id_empresa
             LIMIT 1'
        );
        $statement->execute([':id' => $boletinId, ':id_empresa' => current_empresa_id()]);
        $boletin = $statement->fetch();
        if ($boletin === false) {
            return null;
        }

        $repeticionesStatement = db()->prepare(
            'SELECT nro_repeticion, semillas_sembradas, primer_conteo_normales, primer_conteo_anormales, primer_conteo_duras, primer_conteo_frescas, primer_conteo_muertas, segundo_conteo_normales, segundo_conteo_anormales, segundo_conteo_duras, segundo_conteo_frescas, segundo_conteo_muertas
             FROM boletin_germinacion_repeticiones
             WHERE id_boletin_germinacion = :id_boletin_germinacion
             ORDER BY nro_repeticion ASC'
        );
        $repeticionesStatement->execute([':id_boletin_germinacion' => $boletinId]);
        $repeticiones = $repeticionesStatement->fetchAll();
    } catch (Throwable $throwable) {
        return null;
    }

    $escape = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    $fechaSiembra = !empty($boletin['fecha_siembra']) ? format_date_short((string) $boletin['fecha_siembra']) : '';
    $fechaPrimerConteo = !empty($boletin['fecha_primer_conteo']) ? format_date_short((string) $boletin['fecha_primer_conteo']) : '';
    $fechaSegundoConteo = !empty($boletin['fecha_segundo_conteo']) ? format_date_short((string) $boletin['fecha_segundo_conteo']) : '';
    $esFinalizado = (string) ($boletin['estado'] ?? '') === 'finalizado';

    $check = static fn(bool $marcado): string => $marcado ? 'X' : '&nbsp;';
    $fechaPretratamientoDesde = !empty($boletin['pretratamiento_desde']) ? format_date_short((string) $boletin['pretratamiento_desde']) : '';
    $fechaPretratamientoHasta = !empty($boletin['pretratamiento_hasta']) ? format_date_short((string) $boletin['pretratamiento_hasta']) : '';
    $pretratamientoAplicaSi = (string) ($boletin['pretratamiento_aplica'] ?? '') === 'si';
    $pretratamientoAplicaNo = (string) ($boletin['pretratamiento_aplica'] ?? '') === 'no';

    $repeticionesRows = '';
    foreach ($repeticiones as $repeticion) {
        $repeticionesRows .= '<tr>'
            . '<td rowspan="2">' . (int) $repeticion['nro_repeticion'] . '</td>'
            . '<td rowspan="2">' . (int) $repeticion['semillas_sembradas'] . '</td>'
            . '<td>1er conteo</td>'
            . '<td>' . $escape($repeticion['primer_conteo_normales'] ?? '----') . '</td>'
            . '<td>' . $escape($repeticion['primer_conteo_anormales'] ?? '----') . '</td>'
            . '<td>' . $escape($repeticion['primer_conteo_duras'] ?? '----') . '</td>'
            . '<td>' . $escape($repeticion['primer_conteo_frescas'] ?? '----') . '</td>'
            . '<td>' . $escape($repeticion['primer_conteo_muertas'] ?? '----') . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td>2do conteo</td>'
            . '<td>' . $escape($repeticion['segundo_conteo_normales'] ?? '----') . '</td>'
            . '<td>' . $escape($repeticion['segundo_conteo_anormales'] ?? '----') . '</td>'
            . '<td>' . $escape($repeticion['segundo_conteo_duras'] ?? '----') . '</td>'
            . '<td>' . $escape($repeticion['segundo_conteo_frescas'] ?? '----') . '</td>'
            . '<td>' . $escape($repeticion['segundo_conteo_muertas'] ?? '----') . '</td>'
            . '</tr>';
    }
    if ($repeticionesRows === '') {
        $repeticionesRows = '<tr><td colspan="7">&nbsp;</td></tr>';
    }

    $html = '<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
        @page { margin: 11mm 12mm; }
        body { font-family: sans-serif; color: #111; font-size: 8.5pt; }
        h1 { font-family: serif; font-size: 14pt; text-align: center; margin: 0 0 4mm; }
        .rule { border-top: 1px solid #111; margin-bottom: 3mm; }
        table { border-collapse: collapse; width: 100%; }
        .meta { width: 60%; margin: 0 0 3mm auto; }
        .meta td { border: 1px solid #111; padding: 1mm 2mm; }
        .meta td:first-child { border: 0; width: 33%; text-align: right; font-weight: bold; }
        .identity { margin-bottom: 3mm; }
        .identity td { border: 1px solid #111; padding: 1.2mm 2mm; }
        .identity td:first-child { background: #ddd; width: 22%; font-weight: bold; }
        .reps-table th, .reps-table td { border: 1px solid #111; padding: 1mm 2mm; text-align: center; }
        .reps-table th { background: #ddd; }
        .pretratamiento { margin-bottom: 3mm; }
        .pretratamiento th { background: #ddd; text-align: left; padding: 1mm 2mm; }
        .pretratamiento td { border: 1px solid #111; padding: 1mm 2mm; }
        .pretratamiento .check { display: inline-block; margin-right: 4mm; }
        .primer-promedio { margin-bottom: 3mm; }
        .primer-promedio th, .primer-promedio td { border: 1px solid #111; padding: 1.2mm 2mm; text-align: center; background: #fdecec; font-weight: bold; }
        .primer-promedio th { width: 50%; }
        .results-caption { margin: 2mm 0 1mm; }
        .results-table th, .results-table td { border: 1px solid #111; padding: 1.2mm 2mm; text-align: center; }
        .results-table th { background: #ddd; }
        .status { margin-top: 2mm; font-weight: bold; }
        .status.ok { color: #1e6b2e; }
        .status.pending { color: #8a6500; }
        .notes { margin-top: 2mm; }
        .notes th { background: #ddd; text-align: center; padding: 1.2mm 2mm; }
        .notes td { height: 12mm; vertical-align: top; padding: 1.2mm 2mm; }
    </style></head><body>
        <h1>BOLETÍN DE GERMINACIÓN</h1>
        <div class="rule"></div>
        <table class="meta">
            <tr><td>Análisis Nº</td><td>' . $escape($boletin['analisis_nro'] ?? '') . '</td></tr>
            <tr><td>Muestra Nº</td><td>' . $escape($boletin['muestra_nro']) . '</td></tr>
            <tr><td>Analista</td><td>' . $escape($boletin['analista']) . '</td></tr>
        </table>
        <table class="identity">
            <tr><td>Cliente</td><td colspan="3">' . $escape($boletin['nombre_cliente']) . '</td></tr>
            <tr><td>Especie remitente</td><td>' . $escape($boletin['nombre_especie'] ?? '') . '</td><td>Especie (Latín)</td><td><em>' . $escape($boletin['cientifico_especie'] ?? '') . '</em></td></tr>
            <tr><td>Fecha de siembra</td><td>' . $escape($fechaSiembra) . '</td><td>Fecha 1er conteo</td><td>' . $escape($fechaPrimerConteo) . '</td></tr>
            <tr><td>Fecha 2do conteo</td><td colspan="3">' . $escape($fechaSegundoConteo) . '</td></tr>
        </table>
        <table class="pretratamiento">
            <tr><th colspan="4">Pre-tratamiento</th></tr>
            <tr><td>Desde: ' . $escape($fechaPretratamientoDesde) . '</td><td>Hasta: ' . $escape($fechaPretratamientoHasta) . '</td><td colspan="2">Total Días: ' . $escape($boletin['pretratamiento_total_dias'] ?? '') . '</td></tr>
            <tr>
                <td colspan="4">
                    <span class="check">SI [' . $check($pretratamientoAplicaSi) . ']</span>
                    <span class="check">NO [' . $check($pretratamientoAplicaNo) . ']</span>
                    <span class="check">Frío [' . $check((bool) $boletin['pretratamiento_frio']) . ']</span>
                    <span class="check">Nitrato de K [' . $check((bool) $boletin['pretratamiento_nitrato_k']) . ']</span>
                    <span class="check">Pre-secado [' . $check((bool) $boletin['pretratamiento_presecado']) . ']</span>
                    <span class="check">Pre-lavado [' . $check((bool) $boletin['pretratamiento_prelavado']) . ']</span>
                </td>
            </tr>
            <tr><th colspan="4">Sustrato utilizado</th></tr>
            <tr>
                <td colspan="4">
                    <span class="check">Arena [' . $check((string) ($boletin['sustrato_tipo'] ?? '') === 'arena') . ']</span>
                    <span class="check">Sobre papel [' . $check((string) ($boletin['sustrato_tipo'] ?? '') === 'sobre_papel') . ']</span>
                    <span class="check">Entre papel [' . $check((string) ($boletin['sustrato_tipo'] ?? '') === 'entre_papel') . ']</span>
                    <span class="check">Otro: ' . $escape($boletin['sustrato_otro'] ?? '') . '</span>
                </td>
            </tr>
            <tr><th colspan="4">Temp. de la Cámara</th></tr>
            <tr>
                <td colspan="4">
                    <span class="check">20 ºC [' . $check((string) ($boletin['temperatura_camara_tipo'] ?? '') === '20') . ']</span>
                    <span class="check">25 ºC [' . $check((string) ($boletin['temperatura_camara_tipo'] ?? '') === '25') . ']</span>
                    <span class="check">20-30 ºC [' . $check((string) ($boletin['temperatura_camara_tipo'] ?? '') === '20_30') . ']</span>
                    <span class="check">Otra: ' . $escape($boletin['temperatura_camara_otra'] ?? '') . '</span>
                </td>
            </tr>
        </table>
        <table class="primer-promedio">
            <tr><th>1er Promedio</th><td>' . $escape(format_percent_value($boletin['primer_promedio'] ?? null)) . ' %</td></tr>
        </table>
        <table class="reps-table">
            <tr>
                <th style="width: 7%">N° rep.</th>
                <th>Semillas sembradas</th>
                <th>Conteo</th>
                <th>Normales</th>
                <th>Anormales</th>
                <th>Duras</th>
                <th>Frescas</th>
                <th>Muertas</th>
            </tr>
            ' . $repeticionesRows . '
        </table>
        <p class="results-caption">Resultado final: suma del 1er y 2do conteo por categoría.</p>
        <table class="results-table">
            <tr>
                <th>Plántulas normales</th>
                <th>Plántulas anormales</th>
                <th>Semillas duras</th>
                <th>Semillas frescas</th>
                <th>Semillas muertas</th>
            </tr>
            <tr>
                <td>' . $escape(format_percent_value($boletin['resultado_plantulas_normales'] ?? null)) . ' %</td>
                <td>' . $escape(format_percent_value($boletin['resultado_plantulas_anormales'] ?? null)) . ' %</td>
                <td>' . $escape(format_percent_value($boletin['resultado_semillas_duras'] ?? null)) . ' %</td>
                <td>' . $escape(format_percent_value($boletin['resultado_semillas_frescas'] ?? null)) . ' %</td>
                <td>' . $escape(format_percent_value($boletin['resultado_semillas_muertas'] ?? null)) . ' %</td>
            </tr>
        </table>
        <div class="status ' . ($esFinalizado ? 'ok' : 'pending') . '">Estado: ' . ($esFinalizado ? 'Finalizado (2do conteo completo)' : 'En proceso (pendiente 2do conteo)') . '</div>
        <table class="notes"><tr><th>Observaciones</th></tr><tr><td>' . nl2br($escape($boletin['observaciones'] ?? '')) . '</td></tr></table>
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
        'filename' => 'boletin_germinacion_' . $boletinId . '.pdf',
    ];
}
