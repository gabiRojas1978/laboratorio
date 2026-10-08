<?php

declare(strict_types=1);

function build_boletin_peso_1000_pdf(int $boletinId): ?array
{
    try {
        $statement = db()->prepare(
            'SELECT b.*, cl.nombre_cliente, e.nombre_especie, e.cientifico_especie
             FROM boletines_peso_1000_semillas b
             INNER JOIN clientes cl ON cl.id_cliente = b.id_cliente AND cl.id_empresa = b.id_empresa
             LEFT JOIN especies e ON e.id_especie = b.id_especie AND e.id_empresa = b.id_empresa
             WHERE b.id_boletin_peso = :id AND b.id_empresa = :id_empresa
             LIMIT 1'
        );
        $statement->execute([':id' => $boletinId, ':id_empresa' => current_empresa_id()]);
        $boletin = $statement->fetch();

        if ($boletin === false) {
            return null;
        }

        $repeticionesStatement = db()->prepare(
            'SELECT nro_repeticion, peso_repeticion
             FROM boletin_peso_repeticiones
             WHERE id_boletin_peso = :id_boletin_peso
             ORDER BY nro_repeticion ASC'
        );
        $repeticionesStatement->execute([':id_boletin_peso' => $boletinId]);
        $repeticiones = $repeticionesStatement->fetchAll();
    } catch (Throwable $throwable) {
        return null;
    }

    $pesos = array_map(static fn($repeticion) => (float) $repeticion['peso_repeticion'], $repeticiones);
    $calculos = calcular_boletin_peso_1000_semillas($pesos);
    $escape = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    $fecha = !empty($boletin['fecha_boletin']) ? date('d/m/Y', strtotime((string) $boletin['fecha_boletin'])) : '';

    $repeticionesHtml = '';
    foreach ($repeticiones as $repeticion) {
        $peso = (float) $repeticion['peso_repeticion'];
        $repeticionesHtml .= '<tr><td>' . (int) $repeticion['nro_repeticion'] . '</td><td>'
            . number_format($peso, 4, ',', '.') . '</td><td>'
            . number_format($peso * $peso, 6, ',', '.') . '</td></tr>';
    }
    $repeticionesHtml .= '<tr class="summary"><th>SUMA</th><td>'
        . number_format($calculos['suma_pesos'], 4, ',', '.') . '</td><td>'
        . number_format(array_sum(array_map(static fn($peso) => $peso * $peso, $pesos)), 6, ',', '.') . '</td></tr>';
    $repeticionesHtml .= '<tr class="summary"><th>PROMEDIO</th><td>'
        . number_format($calculos['promedio_peso'], 4, ',', '.') . '</td><td class="crossed"></td></tr>';

    $observaciones = trim((string) ($boletin['observaciones'] ?? ''));
    $html = '<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
        @page { margin: 10mm 11mm; }
        body { font-family: sans-serif; font-size: 9pt; color: #111; }
        h1 { font-family: serif; font-size: 16pt; text-align: center; margin: 0 0 14mm; }
        .rule { border-top: 1px solid #111; margin-bottom: 8mm; }
        table { border-collapse: collapse; width: 100%; }
        .meta { width: 58%; margin-left: 42%; margin-bottom: 8mm; }
        .meta td { border: 1px solid #111; height: 9mm; padding: 2mm; }
        .meta .label { border: 0; text-align: right; font-weight: bold; width: 28%; }
        .main { table-layout: fixed; }
        .main > tbody > tr > td { vertical-align: top; width: 50%; padding: 0; }
        .title-cell { text-align: center; font-weight: bold; background: #e7e7e7; }
        .weights th, .weights td { border: 1px solid #111; padding: 1.5mm 1mm; text-align: center; }
        .weights th { background: #e7e7e7; }
        .weights .summary { background: #ededed; font-weight: bold; }
        .calc { border: 1px solid #111; padding: 4mm; min-height: 82mm; }
        .calc h2 { font-size: 10pt; margin: 0 0 4mm; }
        .formula { font-size: 10pt; text-align: center; margin: 4mm 0; }
        .answer { border: 2px solid #111; min-width: 35mm; display: inline-block; padding: 2mm; font-weight: bold; text-align: center; }
        .refs { margin-top: 10mm; line-height: 1.6; }
        .bottom { margin-top: 0; table-layout: fixed; }
        .bottom td { width: 50%; vertical-align: top; border: 1px solid #111; padding: 4mm; height: 63mm; }
        .note { font-size: 8pt; line-height: 1.35; margin: 4mm 0; }
        .result { font-size: 11pt; margin-top: 8mm; }
        .observations { margin-top: 8mm; height: 32mm; background: #dedede; border: 1px solid #111; padding: 3mm; }
        .small { font-size: 8pt; }
        .crossed { background: #d8d8d8; }
    </style></head><body>
        <h1>BOLETÍN DEL PESO DE 1000 SEMILLAS</h1>
        <div class="rule"></div>
        <table class="meta">
            <tr><td class="label">Cliente</td><td>' . $escape($boletin['nombre_cliente']) . '</td></tr>
            <tr><td class="label">Muestra Nº</td><td>' . $escape($boletin['muestra_nro']) . '</td></tr>
            <tr><td class="label">Especie</td><td>' . $escape($boletin['nombre_especie'] ?? '') . '</td></tr>
            <tr><td class="label">Especie (Latín)</td><td><em>' . $escape($boletin['cientifico_especie'] ?? '') . '</em></td></tr>
            <tr><td class="label">Fecha</td><td>' . $escape($fecha) . '</td></tr>
            <tr><td class="label">Analista</td><td>' . $escape($boletin['analista'] ?? '') . '</td></tr>
        </table>
        <table class="main"><tr>
            <td>
                <table class="weights">
                    <tr><th colspan="3" class="title-cell">RECUENTO DE REPETICIONES</th></tr>
                    <tr><th>Nº de Repeticiones</th><th>X (Peso)</th><th>X<sup>2</sup></th></tr>
                    ' . $repeticionesHtml . '
                </table>
            </td>
            <td>
                <div class="calc">
                    <h2>1) Cálculo de la Varianza:</h2>
                    <div class="formula">Varianza = [ n × (Σ X<sup>2</sup>) − (Σ X)<sup>2</sup> ] ÷ [ n × (n−1) ]</div>
                    <div class="formula small">n = ' . $calculos['n'] . ' &nbsp; | &nbsp; ΣX = ' . number_format($calculos['suma_pesos'], 4, ',', '.')
        . ' &nbsp; | &nbsp; ΣX² = ' . number_format(array_sum(array_map(static fn($peso) => $peso * $peso, $pesos)), 6, ',', '.') . '</div>
                    <div class="formula">Varianza = <span class="answer">' . number_format($calculos['varianza'], 6, ',', '.') . '</span></div>
                    <div class="refs"><strong>Referencias:</strong><br>X = Peso de cada repetición en gramos.<br>n = Número de repeticiones.<br>Σ = Sumatoria.</div>
                </div>
            </td>
        </tr></table>
        <table class="bottom"><tr>
            <td>
                <h2>2) Cálculo del Desvío Estándar (S)</h2>
                <div class="formula">Desvío estándar = √ Varianza</div>
                <div class="formula">Desvío estándar (S) = <span class="answer">' . number_format($calculos['desvio_estandar'], 6, ',', '.') . '</span></div>
                <hr><h2>3) Cálculo del Coeficiente de Variación</h2>
                <div class="formula">Coef. de Variación = S × 100 ÷ x̄</div>
                <div class="formula">Coef. de Variación = <span class="answer">' . number_format($calculos['coeficiente_variacion'], 2, ',', '.') . ' %</span></div>
            </td>
            <td>
                <h2>4) Cálculo del Resultado</h2>
                <p class="note"><strong>NOTA 1:</strong> Si el coeficiente de variación no excede 6,0 para semillas de gramíneas no desnudas o 4,0 para otras semillas, se puede proseguir con el cálculo.</p>
                <p class="note"><strong>NOTA 2:</strong> Si excede cualquiera de los límites, contar otras 8 réplicas y calcular el coeficiente de variación con las 16 repeticiones.</p>
                <div class="result">Resultado = x̄ × 10<br><br>Resultado = <span class="answer">' . number_format($calculos['resultado_peso_mil'], 4, ',', '.') . ' g</span></div>
            </td>
        </tr></table>
        <div class="observations"><strong>Observaciones:</strong><br>' . nl2br($escape($observaciones)) . '</div>
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
        'filename' => 'boletin_peso_1000_semillas_' . $boletinId . '.pdf',
    ];
}
