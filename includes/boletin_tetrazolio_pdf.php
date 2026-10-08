<?php

declare(strict_types=1);

function build_boletin_tetrazolio_pdf(int $boletinId): ?array
{
    try {
        $statement = db()->prepare(
            'SELECT b.*, cl.nombre_cliente, e.nombre_especie, e.cientifico_especie
             FROM boletines_tetrazolio b
             INNER JOIN clientes cl ON cl.id_cliente = b.id_cliente AND cl.id_empresa = b.id_empresa
             LEFT JOIN especies e ON e.id_especie = b.id_especie AND e.id_empresa = b.id_empresa
             WHERE b.id_boletin_tetrazolio = :id AND b.id_empresa = :id_empresa
             LIMIT 1'
        );
        $statement->execute([':id' => $boletinId, ':id_empresa' => current_empresa_id()]);
        $boletin = $statement->fetch();
        if ($boletin === false) {
            return null;
        }

        $repeticionesStatement = db()->prepare(
            'SELECT nro_repeticion, semillas_viables, semillas_no_viables
             FROM boletin_tetrazolio_repeticiones
             WHERE id_boletin_tetrazolio = :id_boletin_tetrazolio
             ORDER BY nro_repeticion ASC'
        );
        $repeticionesStatement->execute([':id_boletin_tetrazolio' => $boletinId]);
        $repeticiones = $repeticionesStatement->fetchAll();
    } catch (Throwable $throwable) {
        return null;
    }

    $calculos = calcular_boletin_tetrazolio(array_map(
        static fn($repeticion) => [
            'viables' => $repeticion['semillas_viables'],
            'no_viables' => $repeticion['semillas_no_viables'],
        ],
        $repeticiones
    ));
    $escape = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    $fechaInicio = $boletin['fecha_inicio'] ? format_date_short((string) $boletin['fecha_inicio']) : '';
    $fechaFinalizacion = $boletin['fecha_finalizacion'] ? format_date_short((string) $boletin['fecha_finalizacion']) : '';

    $rows = '';
    foreach ($repeticiones as $repeticion) {
        $rows .= '<tr><td>' . (int) $repeticion['nro_repeticion'] . '</td><td>'
            . number_format((int) $repeticion['semillas_viables'], 0, ',', '.') . '</td><td>'
            . number_format((int) $repeticion['semillas_no_viables'], 0, ',', '.') . '</td></tr>';
    }
    $rows .= '<tr class="summary"><th>PROMEDIO</th><td>'
        . number_format($calculos['promedio_viables'], 2, ',', '.') . '</td><td>'
        . number_format($calculos['promedio_no_viables'], 2, ',', '.') . '</td></tr>';
    $rows .= '<tr class="result"><th>RESULTADO = %</th><td>'
        . number_format($calculos['resultado_viables_porcentaje'], 2, ',', '.') . ' %</td><td>'
        . number_format($calculos['resultado_no_viables_porcentaje'], 2, ',', '.') . ' %</td></tr>';

    $observaciones = nl2br($escape($boletin['observaciones'] ?? ''));
    $html = '<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
        @page { margin: 10mm 11mm; }
        body { font-family: sans-serif; color: #111; font-size: 8.5pt; }
        h1 { font-family: serif; font-size: 15pt; text-align: center; margin: 0 0 9mm; }
        .rule { border-top: 1px solid #111; margin-bottom: 8mm; }
        table { width: 100%; border-collapse: collapse; }
        .metadata { width: 60%; margin-left: 40%; margin-bottom: 4mm; }
        .metadata td { border: 1px solid #111; height: 6mm; padding: 1mm; }
        .metadata .label { border: 0; text-align: right; font-weight: bold; width: 42%; white-space: nowrap; }
        .species { margin: 0 0 4mm; }
        .species td { border: 1px solid #111; padding: 1.5mm; }
        .species .label { background: #ddd; width: 18%; font-weight: bold; }
        .method { margin-bottom: 4mm; }
        .method th, .method td { border: 1px solid #111; padding: 1.5mm; }
        .method .heading { background: #ddd; text-align: center; font-weight: bold; }
        .method .label { background: #e7e7e7; width: 25%; }
        .results th, .results td { border: 1px solid #111; padding: 1.2mm; text-align: center; height: 8mm; }
        .results th { background: #ddd; }
        .results .heading { font-weight: bold; font-size: 9.5pt; }
        .results .summary { background: #efefef; }
        .results .result { height: 9mm; font-size: 10pt; font-weight: bold; }
        .observations { margin-top: 4mm; border: 1px solid #111; }
        .observations .heading { background: #ddd; border-bottom: 1px solid #111; padding: 1.5mm; font-weight: bold; }
        .observations .content { min-height: 35mm; padding: 2mm; line-height: 1.35; }
    </style></head><body>
        <h1>BOLETÍN DE TETRAZOLIO</h1>
        <div class="rule"></div>
        <table class="metadata">
            <tr><td class="label">Muestra Nº</td><td>' . $escape($boletin['muestra_nro']) . '</td></tr>
            <tr><td class="label">Fecha de inicio</td><td>' . $escape($fechaInicio) . '</td></tr>
            <tr><td class="label">Fecha de finalización</td><td>' . $escape($fechaFinalizacion) . '</td></tr>
            <tr><td class="label">Analista</td><td>' . $escape($boletin['analista'] ?? '') . '</td></tr>
        </table>
        <table class="species"><tr><td class="label">Especie remitente</td><td>' . $escape($boletin['nombre_especie'] ?? '') . '</td></tr>
        <tr><td class="label">Especie (Latín)</td><td><em>' . $escape($boletin['cientifico_especie'] ?? '') . '</em></td></tr>
        <tr><td class="label">Cliente</td><td>' . $escape($boletin['nombre_cliente']) . '</td></tr></table>
        <table class="method">
            <tr><th colspan="4" class="heading">Metodología</th></tr>
            <tr><td class="label">Tiempo de humedecimiento</td><td>' . $escape($boletin['tiempo_humedecimiento']) . '</td><td class="label">Tiempo de tinción</td><td>' . $escape($boletin['tiempo_tincion']) . '</td></tr>
            <tr><td class="label">Temperatura de tinción</td><td>' . $escape($boletin['temperatura_tincion']) . '</td><td class="label">Concentración de TZ</td><td>' . $escape($boletin['concentracion_tz']) . '</td></tr>
        </table>
        <table class="results">
            <tr><th colspan="3" class="heading">Resultado en porcentaje (%)</th></tr>
            <tr><th>Nº de repetición</th><th>Nº de semillas viables</th><th>Nº de semillas no viables</th></tr>
            ' . $rows . '
        </table>
        <div class="observations"><div class="heading">Observaciones</div><div class="content">' . $observaciones . '</div></div>
    </body></html>';

    require_once __DIR__ . '/../vendor/autoload.php';
    $pdf = new \Mpdf\Mpdf([
        'format' => 'A4',
        'margin_left' => 12,
        'margin_right' => 12,
        'margin_top' => 10,
        'margin_bottom' => 10,
        'tempDir' => __DIR__ . '/../vendor/mpdf/mpdf/tmp',
    ]);
    $pdf->WriteHTML($html);

    return [
        'content' => $pdf->Output('', \Mpdf\Output\Destination::STRING_RETURN),
        'filename' => 'boletin_tetrazolio_' . $boletinId . '.pdf',
    ];
}
