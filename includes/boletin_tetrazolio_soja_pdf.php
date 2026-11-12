<?php

declare(strict_types=1);

function build_boletin_tetrazolio_soja_pdf(int $boletinId): ?array
{
    $statement = db()->prepare(
        'SELECT b.*, cl.nombre_cliente, e.nombre_especie
         FROM boletines_tetrazolio_soja b
         INNER JOIN clientes cl ON cl.id_cliente = b.id_cliente AND cl.id_empresa = b.id_empresa
         INNER JOIN especies e ON e.id_especie = b.id_especie AND e.id_empresa = b.id_empresa
         WHERE b.id_boletin_tetrazolio_soja = :id AND b.id_empresa = :id_empresa
         LIMIT 1'
    );
    $statement->execute([':id' => $boletinId, ':id_empresa' => current_empresa_id()]);
    $boletin = $statement->fetch();
    if ($boletin === false) {
        return null;
    }

    $categorias = array_keys(boletin_tetrazolio_soja_campos_repeticion());
    $statement = db()->prepare(
        'SELECT ' . implode(', ', $categorias) . '
         FROM boletin_tetrazolio_soja_repeticiones
         WHERE id_boletin_tetrazolio_soja = :id
         ORDER BY nro_repeticion'
    );
    $statement->execute([':id' => $boletinId]);
    $repeticiones = $statement->fetchAll();
    if (count($repeticiones) !== 4) {
        return null;
    }

    $calculos = calcular_boletin_tetrazolio_soja($repeticiones);
    $nombresViabilidad = boletin_tetrazolio_soja_niveles_viabilidad();
    $nombresVigor = boletin_tetrazolio_soja_categorias_vigor();
    $nombresDanos = boletin_tetrazolio_soja_tipos_danos();
    $escape = static fn($valor): string => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
    $numero = static fn(float|int $valor, int $decimales = 0): string => number_format((float) $valor, $decimales, ',', '.');

    $metadata = [
        'N° interno' => $boletin['nro_interno'],
        'N° certificado' => $boletin['nro_certificado'],
        'Nombre del solicitante' => $boletin['nombre_solicitante'] ?: $boletin['nombre_cliente'],
        'Precinto de la muestra' => $boletin['precinto_muestra'],
        'Procedencia' => $boletin['procedencia'],
        'Fecha de recepción' => $boletin['fecha_recepcion'] ? format_date_short((string) $boletin['fecha_recepcion']) : '',
        'Fecha de análisis' => $boletin['fecha_analisis'] ? format_date_short((string) $boletin['fecha_analisis']) : '',
        'Analista' => $boletin['analista'],
        'Especie' => $boletin['nombre_especie'],
        'Cultivar' => $boletin['cultivar'],
        'Semillas evaluadas por repetición' => 200,
        'Concentración de Tetrazolio' => $boletin['concentracion_tz'],
        'Tiempo de tinción' => $boletin['tiempo_tincion'],
        'Temperatura de tinción' => $boletin['temperatura_tincion'],
        'Observaciones' => $boletin['observaciones'],
    ];
    $metadataRows = '';
    foreach (array_chunk($metadata, 3, true) as $par) {
        $celdas = '';
        foreach ($par as $etiqueta => $valor) {
            $celdas .= '<th>' . $escape($etiqueta) . '</th><td>' . nl2br($escape($valor)) . '</td>';
        }
        if (count($par) === 1) {
            $celdas .= '<th></th><td></td>';
        }
        $metadataRows .= '<tr>' . $celdas . '</tr>';
    }

    $filasInternas = '';
    foreach ($repeticiones as $indice => $repeticion) {
        foreach ($nombresViabilidad as $nivel => $etiquetaNivel) {
            $numeroNivel = array_search($nivel, array_keys($nombresViabilidad), true) + 1;
            $filasInternas .= '<tr><th>R' . ($indice + 1) . ' / ' . $numeroNivel . '</th>'
                . '<td>' . $numero((int) $repeticion[$nivel]) . '</td>';
            foreach ($nombresDanos as $tipo => $etiqueta) {
                $filasInternas .= '<td>' . $numero((int) $repeticion[$tipo . '_nivel_' . $numeroNivel]) . '</td>';
            }
            $filasInternas .= '</tr>';
        }
    }

    $filasResumen = '';
    foreach ($nombresViabilidad as $categoria => $etiqueta) {
        $dato = $calculos['categorias'][$categoria];
        $filasResumen .= '<tr><th>' . $escape($etiqueta) . '</th>';
        foreach ($dato['repeticiones'] as $valor) {
            $filasResumen .= '<td>' . $numero($valor) . '</td>';
        }
        $filasResumen .= '<td>' . $numero($dato['promedio'], 2) . '</td><td>' . $numero($dato['porcentaje'], 2) . ' %</td></tr>';
    }
    $filasResumen .= '<tr class="total"><th>Número semillas viables (1+2+3+4)</th>';
    foreach ($calculos['viables']['repeticiones'] as $valor) {
        $filasResumen .= '<td>' . $numero($valor) . '</td>';
    }
    $filasResumen .= '<td>' . $numero($calculos['viables']['promedio'], 2) . '</td><td></td></tr>';
    $filasResumen .= '<tr class="total"><th>Porciento de viabilidad</th><td colspan="4"></td><td></td><td>' . $numero($calculos['viables']['porcentaje'], 2) . ' %</td></tr>';

    $filasVigor = '';
    foreach ($nombresVigor as $categoria => $etiqueta) {
        $dato = $calculos['categorias'][$categoria];
        $filasVigor .= '<tr><th>' . $escape($etiqueta) . '</th>';
        foreach ($dato['repeticiones'] as $valor) {
            $filasVigor .= '<td>' . $numero($valor) . '</td>';
        }
        $filasVigor .= '<td>' . $numero($dato['promedio'], 2) . '</td><td>' . $numero($dato['porcentaje'], 2) . ' %</td></tr>';
    }
    $filasVigor .= '<tr class="total"><th>Vigor acumulado (A+B+C)</th>';
    foreach ($calculos['vigor_acumulado']['repeticiones'] as $valor) {
        $filasVigor .= '<td>' . $numero($valor) . '</td>';
    }
    $filasVigor .= '<td>' . $numero($calculos['vigor_acumulado']['promedio'], 2) . '</td><td>' . $numero($calculos['vigor_acumulado']['porcentaje'], 2) . ' %</td></tr>';

    $filasDanos = '';
    foreach ($nombresDanos as $categoria => $etiqueta) {
        $dato = $calculos['danos_acumulados'][$categoria];
        $filasDanos .= '<tr><th>' . $escape($etiqueta) . '</th>';
        foreach ($dato['repeticiones'] as $valor) {
            $filasDanos .= '<td>' . $numero($valor) . '</td>';
        }
        $filasDanos .= '<td>' . $numero($dato['promedio'], 2) . '</td><td>' . $numero($dato['porcentaje'], 2) . ' %</td></tr>';
    }

    $html = '<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
        body { font-family: sans-serif; color: #17233b; font-size: 7pt; }
        h1 { font-size: 12pt; text-align: center; margin: 0 0 1mm; }
        h2 { font-size: 8pt; margin: 2mm 0 1mm; }
        .subtitle { text-align: center; margin: 0 0 2mm; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 0.3mm solid #53677d; padding: 0.6mm; }
        th { background: #d7e1e9; text-align: left; }
        .metadata { margin-bottom: 2mm; }
        .metadata th { width: 12%; font-size: 6pt; }
        .metadata td { width: 21%; }
        .internal { table-layout: fixed; font-size: 5.5pt; text-align: center; }
        .internal th, .internal td { padding: 0.45mm 0.25mm; text-align: center; }
        .internal th:first-child { width: 17%; }
        .results { table-layout: fixed; font-size: 7pt; }
        .results th, .results td { text-align: center; }
        .results th:first-child { text-align: left; width: 34%; }
        .results .total { background: #e4ebf0; font-weight: bold; }
        .summary-box { border: 0.4mm solid #53677d; background: #d7e1e9; padding: 2mm; margin-top: 3mm; }
        .page-break { page-break-before: always; }
    </style></head><body>
        <h1>PRUEBA TOPOGRÁFICA POR TETRAZOLIO EN SOJA</h1>
        <p class="subtitle">PLANILLA INTERNA DE TRABAJO — Muestra N° ' . $escape($boletin['muestra_nro']) . '</p>
        <table class="metadata">' . $metadataRows . '</table>
        <h2>Registro por repetición — cantidades de semillas</h2>
        <p>1 Viables sin defectos · 2 Defectos leves · 3 Defectos moderados · 4 Defectos severos · 5 No viables</p>
        <table class="internal"><thead><tr><th>Rep. / Nivel</th><th>N° semillas</th>';
    foreach ($nombresDanos as $etiqueta) {
        $html .= '<th>' . $escape($etiqueta) . '</th>';
    }
    $html .= '</tr></thead><tbody>' . $filasInternas . '</tbody></table>
        <h2>Vigor por repetición</h2>
        <table class="internal"><thead><tr><th>Repetición</th>';
    foreach ($nombresVigor as $etiqueta) {
        $html .= '<th>' . $escape($etiqueta) . '</th>';
    }
    $html .= '<th>Vigor acumulado (A+B+C)</th></tr></thead><tbody>';
    foreach ($repeticiones as $indice => $repeticion) {
        $acumulado = (int) $repeticion['vigor_alto'] + (int) $repeticion['vigor_medio'] + (int) $repeticion['vigor_bajo'];
        $html .= '<tr><th>Repetición ' . ($indice + 1) . '</th>';
        foreach (array_keys($nombresVigor) as $categoria) {
            $html .= '<td>' . $numero((int) $repeticion[$categoria]) . '</td>';
        }
        $html .= '<td>' . $numero($acumulado) . '</td></tr>';
    }
    $html .= '</tbody></table>
        <p>La suma de los niveles 1 a 5 es de 200 semillas por repetición. El detalle de daños se registra por nivel de viabilidad.</p>
        <div class="page-break"></div>
        <h1>PRUEBA TOPOGRÁFICA POR TETRAZOLIO EN SOJA</h1>
        <p class="subtitle">PLANILLA DE REGISTRO — N° interno ' . $escape($boletin['nro_interno']) . ' — N° certificado ' . $escape($boletin['nro_certificado']) . '</p>
        <h2>Resumen de resultados — viabilidad</h2>
        <table class="results"><thead><tr><th>Niveles</th><th>R I</th><th>R II</th><th>R III</th><th>R IV</th><th>Media</th><th>%</th></tr></thead><tbody>' . $filasResumen . '</tbody></table>
        <h2>Vigor</h2>
        <table class="results"><thead><tr><th>Niveles</th><th>R I</th><th>R II</th><th>R III</th><th>R IV</th><th>Media</th><th>%</th></tr></thead><tbody>' . $filasVigor . '</tbody></table>
        <h2>Daños acumulados</h2>
        <table class="results"><thead><tr><th>Daños</th><th>R I</th><th>R II</th><th>R III</th><th>R IV</th><th>Media</th><th>%</th></tr></thead><tbody>' . $filasDanos . '</tbody></table>
        <div class="summary-box"><strong>Número de semillas viables — Rep. mayor - Rep. menor:</strong> ' . $numero($calculos['rep_mayor_menor_porcentaje'], 2) . ' % &nbsp;&nbsp; <strong>Tolerancia de tabla:</strong> ' . $escape($boletin['tolerancia_tabla']) . '</div>
    </body></html>';

    require_once __DIR__ . '/../vendor/autoload.php';
    $pdf = new \Mpdf\Mpdf([
        'format' => 'A4-L',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 6,
        'margin_bottom' => 6,
        'tempDir' => __DIR__ . '/../vendor/mpdf/mpdf/tmp',
    ]);
    $pdf->WriteHTML($html);

    return [
        'content' => $pdf->Output('', \Mpdf\Output\Destination::STRING_RETURN),
        'filename' => 'boletin_tetrazolio_soja_' . $boletinId . '.pdf',
    ];
}
