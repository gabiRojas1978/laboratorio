<?php

declare(strict_types=1);

function certificate_optional_results_html(array $certificate): string
{
    $renderLine = static function (string $label, mixed $value, bool $percentage = false): string {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return '';
        }

        $formattedValue = $percentage ? format_percent_value($text) . '%' : $text;
        return '<div class="line-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            . ': <span class="line-value">' . htmlspecialchars($formattedValue, ENT_QUOTES, 'UTF-8') . '</span></div>';
    };

    return $renderLine('Hipoclorito max 10%', $certificate['hipoclorito_max_10_certificado'] ?? '')
        . $renderLine('Bioensayo Enlist', $certificate['bioensayo_enlist_certificado'] ?? '')
        . $renderLine('Peroxidasa - Positivas (%)', $certificate['peroxidasa_positivas_certificado'] ?? '', true)
        . $renderLine('Peroxidasa - Negativas (%)', $certificate['peroxidasa_negativas_certificado'] ?? '', true)
        . $renderLine('Contam. x hilo (250gr) - Cantidad de semillas (%)', $certificate['contam_hilo_cantidad_semillas_certificado'] ?? '', true)
        . $renderLine('Contam. x hilo (250gr) - Color contaminante', $certificate['contam_hilo_color_contaminante_certificado'] ?? '')
        . $renderLine('Hipocotilo - Variedad', $certificate['hipocotilo_variedad_certificado'] ?? '')
        . $renderLine('Hipocotilo - Color contaminante', $certificate['hipocotilo_color_contaminante_certificado'] ?? '');
}

function certificate_logo_dimensions_mm(int $imageWidth, int $imageHeight): ?array
{
    if ($imageWidth <= 0 || $imageHeight <= 0) {
        return null;
    }

    $scale = min(112 / $imageWidth, 30 / $imageHeight);

    return [
        'width' => round($imageWidth * $scale, 2),
        'height' => round($imageHeight * $scale, 2),
    ];
}

function build_certificado_pdf(int $certificadoId): ?array
{
    require_once __DIR__ . '/../vendor/autoload.php';

    try {
        $pdfStatement = db()->prepare(
            'SELECT c.*, cl.nombre_cliente, e.nombre_especie, e.cientifico_especie, cu.nombre_cultivar
            FROM certificados c
            LEFT JOIN clientes cl ON cl.id_cliente = c.id_cliente_certificado
            LEFT JOIN especies e ON e.id_especie = c.id_especie_certificado
            LEFT JOIN cultivares cu ON cu.id_cultivar = c.id_cultivar_certificado
            WHERE c.id_certificado = :id AND c.id_empresa = :id_empresa
            LIMIT 1'
        );
        $pdfStatement->execute([':id' => $certificadoId, ':id_empresa' => current_empresa_id()]);
        $certificadoPdf = $pdfStatement->fetch();

        $detNombresStatement = db()->prepare(
            'SELECT od.nombre_determinacion
            FROM certificado_determinaciones cd
            JOIN otras_determinaciones od ON od.id_determinacion = cd.id_determinacion
            WHERE cd.id_certificado = :id
            ORDER BY od.nombre_determinacion ASC'
        );
        $detNombresStatement->execute([':id' => $certificadoId]);
        $detNombres = $detNombresStatement->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $throwable) {
        return null;
    }

    if ($certificadoPdf === false) {
        return null;
    }

    $cliente = format_text_value($certificadoPdf['nombre_cliente'] ?? '');
    $especie = format_text_value($certificadoPdf['nombre_especie'] ?? '');
    $cultivar = trim((string) ($certificadoPdf['nombre_cultivar'] ?? ''));
    $especieCultivar = trim($especie . ($cultivar !== '' ? ' / ' . $cultivar : ''));
    $especieCientifico = format_text_value($certificadoPdf['cientifico_especie'] ?? '');
    $localidad = format_text_value($certificadoPdf['localidad_pais_certificado'] ?? '----------------', '----------------');
    // Escapar cada elemento individualmente
    $detNombresEscapados = array_map(function ($item) {
        return htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
    }, $detNombres);

    // Unir con <br> (ya escapados)
    $otrasDet = implode('<br>', $detNombresEscapados);
    $otrasDetTexto = trim((string) ($certificadoPdf['otras_determinaciones_texto_certificado'] ?? ''));

    if ($otrasDetTexto !== '') {
        $textoEscapado = htmlspecialchars($otrasDetTexto, ENT_QUOTES, 'UTF-8');
        $otrasDet = $otrasDet !== '' ? $otrasDet . '<br>' . $textoEscapado : $textoEscapado;
    }
    $certificateSettings = get_certificate_settings(current_empresa_id());
    $claseMateria = trim((string) ($certificadoPdf['clase_materia_inerte_certificado'] ?? ''));
    $otrasSemillasTxt = trim((string) ($certificadoPdf['otras_semillas_certificado'] ?? ''));
    $amparaTotalidadLote = trim((string) ($certificadoPdf['ampara_totalidad_lote_certificado'] ?? 'N'));
    $amparaSi = $amparaTotalidadLote === 'S' ? 'X' : ' ';
    $amparaNo = $amparaTotalidadLote === 'N' ? 'X' : ' ';
    $detalleDeterminaciones = $otrasDet !== '' ? $otrasDet : 'N';

    $tipoCertificado = trim((string) ($certificadoPdf['tipo_certificado'] ?? ''));
    $esCertificadoGeneral = $tipoCertificado === 'certificadogeneral';
    $esCertificadoInase = $tipoCertificado === 'certificadoinase';
    $esCertificadoTetrazolio = $tipoCertificado === 'certificadotetrazolio';
    $determinacionesAdicionalesHtml = $esCertificadoGeneral ? certificate_optional_results_html($certificadoPdf) : '';
    $membreteImagePath = certificate_logo_path(current_empresa_id());
    $membreteImageSize = $membreteImagePath !== null && is_file($membreteImagePath)
        ? @getimagesize($membreteImagePath)
        : false;
    $logoDimensions = is_array($membreteImageSize)
        ? certificate_logo_dimensions_mm((int) $membreteImageSize[0], (int) $membreteImageSize[1])
        : null;
    $brandHeaderHtml = '';

    if ($membreteImagePath !== null && $logoDimensions !== null) {
        $membreteImagePathNormalized = str_replace('\\', '/', $membreteImagePath);
        $brandHeaderHtml = '<div class="center brand"><img src="'
            . htmlspecialchars($membreteImagePathNormalized, ENT_QUOTES, 'UTF-8')
            . '" alt="Logo del laboratorio" class="brand-image" style="width:'
            . $logoDimensions['width'] . 'mm;height:' . $logoDimensions['height'] . 'mm"></div>';
    }

    $germinacionHeading = $esCertificadoInase ? 'GERMINACIÓN' : 'PODER GERMINATIVO';
    $pgcVigorHtml = $esCertificadoInase ? '' : '
        <table class="grid" style="margin-top:4px;">
            <tr class="bold">
                <td colspan="6">PODER GERMINATIVO CURADO<br><span class="small">(% en número)</span></td>
                <td colspan="4">VIGOR: COLD TEST (PRUEBA DE FRÍO)<br><span class="small">(% en número)</span></td>
            </tr>
            <tr class="bold mid">
                <td>Número<br>de Días</td>
                <td>Plántulas<br>Normales</td>
                <td>Semillas<br>Duras</td>
                <td>Semillas<br>Frescas</td>
                <td>Plántulas<br>Anormales</td>
                <td>Semillas<br>Muertas</td>
                <td>Número<br>de Días</td>
                <td colspan="3">Plántulas Normales</td>
            </tr>
            <tr>
                <td>' . htmlspecialchars(format_text_value($certificadoPdf['pgc_numero_dias_certificado'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td class="pg-highlight">' . htmlspecialchars(format_percent_value($certificadoPdf['pgc_plantulas_normales_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pgc_semillas_duras_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pgc_semillas_frescas_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pgc_plantulas_anormales_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pgc_semillas_muertas_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_text_value($certificadoPdf['vigor_nro_dias_certificado'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td class="pg-highlight" colspan="3">' . htmlspecialchars(format_percent_value($certificadoPdf['vigor_plantulas_normales_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
        </table>';

    $html = '
    <html>
    <head>
        <style>
            body { font-family: serif; font-size: 14px; color: #222; }
            .cert { width: 100%; border-collapse: collapse; }
            .center { text-align: center; }
            .right { text-align: right; }
            .bold { font-weight: 700; }
            .title { font-size: 42px; font-weight: 700; letter-spacing: 1px; margin-top: 2px; }
            .subtitle { font-size: 13px; line-height: 1.15; }
            .brand { width: 100%; height: 30mm; margin: 0 auto 4px; text-align: center; }
            .brand-image { max-width: 100%; }
            .brand-logo { width: 62px; height: 62px; background: #4a5c2f; border: 1px solid #404040; display: inline-block; vertical-align: top; margin-right: 8px; }
            .brand-text { display: inline-block; vertical-align: top; text-align: left; }
            .brand-main { font-size: 12px; font-weight: 700; line-height: 1.2; margin-top: 1px; }
            .brand-sub { font-size: 10px; letter-spacing: 4px; color: #6f7f40; margin-top: 3px; font-weight: 700; }
            .brand-rule { height: 1px; background: #7f8f48; margin-top: 2px; width: 168px; }
            .section { margin-top: 8px; }
            .heading { background: #d8d8d8; border: 1px solid #666; font-size: 11px; font-weight: 700; text-align: center; padding: 5px 0; }
            table.grid { width: 100%; border-collapse: collapse; }
            table.grid td, table.grid th { border: 1px solid #666; padding: 4px 5px; text-align: center; vertical-align: middle; }
            table.grid .left { text-align: left; }
            .pg-highlight { background: #fdecec; }
            .small { font-size: 9px; }
            .mid { font-size: 10px; }
            .line-label { margin-top: 6px; border-top: 1px solid #333; padding-top: 4px; font-size: 11px; font-weight: 700; }
            .line-value { font-size: 11px; font-weight: 400; }
            .footer { margin-top: 8px; font-size: 10px; }
            .phone { text-align: center; font-size: 10px; letter-spacing: 1px; margin-top: 6px; }
            .address { text-align: center; font-size: 10px; letter-spacing: 1px; }
            .si-no { font-size: 13px; letter-spacing: 1px; font-weight: 700; margin: 9px 0 5px; }
        </style>
    </head>
    <body>' . $brandHeaderHtml . '

        <div class="center title">CERTIFICADO DE ANÁLISIS</div>
        <div class="center subtitle">Laboratorio inscripto en el Registro Nacional de Comercio y Fiscalización de Semillas I/8008</div>' . ($esCertificadoInase ? '' : '
        <div class="center subtitle">Certificado de Análisis fuera del alcance de la acreditación otorgada por INASE</div>') . '

        <div class="section heading">INFORMACIÓN DEL SOLICITANTE</div>
        <table class="grid">
            <tr>
                <td class="left" colspan="2"><span class="bold">Nombre del Solicitante:</span> ' . htmlspecialchars($cliente, ENT_QUOTES, 'UTF-8') . '</td>
                <td class="left" colspan="2"><span class="bold">Marca y N° de Lote:</span> ' . htmlspecialchars((string) ($certificadoPdf['marca_lote_certificado'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr>
                <td class="left" colspan="4"><span class="bold">Especie/ Cultivar/ Categoría:</span> ' . htmlspecialchars($especieCultivar, ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr class="bold">
                <td>PESO DEL LOTE</td>
                <td>N° DE ENVASES</td>
                <td>FECHA DE MUESTREO</td>
                <td>OTRAS REFERENCIAS</td>
            </tr>
            <tr>
                <td>' . htmlspecialchars(format_text_value($certificadoPdf['peso_certificado'] ?? '----------------', '----------------'), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_text_value($certificadoPdf['nro_envases_certificado'] ?? '----------------', '----------------'), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_date_short((string) ($certificadoPdf['fecha_muestreo_certificado'] ?? '')), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_text_value($certificadoPdf['otras_referencias_certificado'] ?? '----------------', '----------------'), ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr class="bold">
                <td>N° DE PRECINTO</td>
                <td>FECHA DE RECEPCIÓN<br>DE MUESTRAS</td>
                <td>FECHA DE FINALIZACIÓN<br>DE LOS ENSAYOS</td>
                <td>N° DE ANÁLISIS</td>
            </tr>
            <tr>
                <td>' . htmlspecialchars(format_text_value($certificadoPdf['precinto_certificado'] ?? '----------------', '----------------'), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_date_short((string) ($certificadoPdf['fecha_muestras_certificado'] ?? '')), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_date_short((string) ($certificadoPdf['fecha_finalizacion_ensayos_certificado'] ?? '')), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_text_value($certificadoPdf['nro_analisis_certificado'] ?? '----------------', '----------------'), ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
        </table>

        <div class="section heading">RESULTADOS LOS ANÁLISIS</div>
        <table class="grid">
            <tr>
                <td class="left" colspan="11"><span class="bold">Especie (Nombre Científico):</span> <em>' . htmlspecialchars($especieCientifico, ENT_QUOTES, 'UTF-8') . '</em></td>
            </tr>
            <tr class="bold">
                <td colspan="3">PUREZA<br><span class="small">(% en peso)</span></td>
                <td colspan="6">' . $germinacionHeading . '<br><span class="small">(% en número)</span></td>
                <td colspan="2">CONTENIDO DE HUMEDAD<br><span class="small">' . ($esCertificadoInase ? '(%)' : '(Higrómetro Delver) (%)') . '</span></td>
            </tr>
            <tr class="bold mid">
                <td>Semilla<br>Pura</td>
                <td>Materia<br>Inerte</td>
                <td>Otras<br>Semillas</td>
                <td>Número<br>de Días</td>
                <td>Plántulas<br>Normales</td>
                <td>Semillas<br>Duras</td>
                <td>Semillas<br>Frescas</td>
                <td>Plántulas<br>Anormales</td>
                <td>Semillas<br>Muertas</td>
                <td colspan="2">Contenido<br>de Humedad</td>
            </tr>
            <tr>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pureza_semilla_pura_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['materia_inerte_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pureza_otras_semillas_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_text_value($certificadoPdf['pg_numero_dias_certificado'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td class="pg-highlight">' . htmlspecialchars(format_percent_value($certificadoPdf['pg_plantulas_normales_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pg_semillas_duras_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pg_semillas_frescas_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pg_plantulas_anormales_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars(format_percent_value($certificadoPdf['pg_semillas_muertas_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
                <td colspan="2">' . htmlspecialchars(format_percent_value($certificadoPdf['pg_contenido_humedad_certificado'] ?? null), ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
        </table>
        ' . $pgcVigorHtml . '

        <div class="line-label">Clase de Materia Inerte: <span class="line-value">' . htmlspecialchars(format_text_value($certificadoPdf['clase_materia_inerte_certificado'] ?? '', ' '), ENT_QUOTES, 'UTF-8') . '</span></div>
        <div class="line-label">Otras Semillas: <span class="line-value">' . htmlspecialchars(format_text_value($certificadoPdf['otras_semillas_certificado'] ?? '', ' '), ENT_QUOTES, 'UTF-8') . '</span></div>
        ' . $determinacionesAdicionalesHtml . '
        <div class="line-label">Otras Determinaciones: <span class="line-value">' . $detalleDeterminaciones . '</span></div>

        <div class="si-no left">ESTE CERTIFICADO &nbsp;&nbsp; SI [' . $amparaSi . '] &nbsp;&nbsp; NO [' . $amparaNo . '] &nbsp;&nbsp; AMPARA LA TOTALIDAD DEL LOTE</div>
        
    </body>
    </html>';

    if ($esCertificadoTetrazolio) {
        $escape = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
        $percent = static fn($value): string => htmlspecialchars(format_percent_value($value), ENT_QUOTES, 'UTF-8');
        $viabilidadTotal = (float) ($certificadoPdf['tetrazolio_viable_sin_defectos_certificado'] ?? 0)
            + (float) ($certificadoPdf['tetrazolio_viable_defectos_leves_certificado'] ?? 0)
            + (float) ($certificadoPdf['tetrazolio_viable_defectos_moderados_certificado'] ?? 0)
            + (float) ($certificadoPdf['tetrazolio_viable_defectos_severos_certificado'] ?? 0);
        $vigorTotal = (float) ($certificadoPdf['tetrazolio_vigor_alto_certificado'] ?? 0)
            + (float) ($certificadoPdf['tetrazolio_vigor_medio_certificado'] ?? 0)
            + (float) ($certificadoPdf['tetrazolio_vigor_bajo_certificado'] ?? 0);

        $html = '<html><head><style>
            body { font-family: serif; font-size: 11px; color: #222; }
            .center { text-align: center; }
            .bold { font-weight: 700; }
            .brand { width: 100%; height: 30mm; margin: 0 auto 3px; text-align: center; }
            .brand-image { max-width: 100%; }
            .title { font-size: 29px; font-weight: 700; line-height: 1.05; letter-spacing: .5px; margin: 2px 0 5px; }
            .subtitle { border-top: 1px solid #333; padding-top: 4px; font-family: sans-serif; font-size: 10px; }
            .section-title { text-align: center; font-size: 17px; font-weight: 700; margin: 12px 0 5px; }
            .applicant { width: 100%; border-collapse: collapse; margin-bottom: 7px; }
            .applicant td { padding: 3px 1px; border-bottom: 1px solid #555; }
            .applicant .label { width: 25%; font-family: sans-serif; font-weight: 700; border-bottom: 0; }
            table.grid { width: 100%; border-collapse: collapse; margin-top: 5px; }
            table.grid th, table.grid td { border: 1px solid #555; padding: 4px 5px; text-align: center; vertical-align: middle; }
            table.grid th { background: #d8d8d8; font-weight: 700; }
            table.grid .left { text-align: left; }
            .lot-values td { height: 27px; }
            .result-values td { height: 20px; }
            .total { background: #e4e4e4; font-weight: 700; }
            .critical { color: #b00020; font-size: 9px; }
            .si-no { font-family: sans-serif; font-size: 12px; font-weight: 700; letter-spacing: .5px; margin-top: 10px; }
        </style></head><body>' . $brandHeaderHtml . '
            <div class="center title">CERTIFICADO DE ANÁLISIS<br>PRUEBA TOPOGRÁFICA POR TETRAZOLIO</div>
            
            <div class="section-title">INFORMACIÓN DEL SOLICITANTE</div>
            <table class="applicant">
                <tr><td class="label">Nombre del Solicitante:</td><td>' . $escape($cliente) . '</td></tr>
                <tr><td class="label">Especie:</td><td>' . $escape($especie) . '</td></tr>
                <tr><td class="label">Detalle:</td><td>' . $escape($certificadoPdf['tetrazolio_detalle_certificado'] ?? '') . '</td></tr>
            </table>

            <table class="grid">
                <tr><th>PESO DEL LOTE</th><th>N° DE BOLSAS</th><th>FECHA DE MUESTREO</th><th>OTRAS REFERENCIAS</th></tr>
                <tr class="lot-values">
                    <td>' . $escape(format_text_value($certificadoPdf['peso_certificado'] ?? '', '----------------')) . '</td>
                    <td>' . $escape(format_text_value($certificadoPdf['nro_envases_certificado'] ?? '', '----------------')) . '</td>
                    <td>' . $escape(format_date_short((string) ($certificadoPdf['fecha_muestreo_certificado'] ?? ''))) . '</td>
                    <td>' . $escape(format_text_value($certificadoPdf['otras_referencias_certificado'] ?? '', '----------------')) . '</td>
                </tr>
                <tr><th>N° DE PRECINTO</th><th>FECHA DE RECEPCIÓN<br>DE MUESTRAS</th><th>FECHA DE FINALIZACIÓN<br>DE LOS ENSAYOS</th><th>N° DE ANÁLISIS</th></tr>
                <tr class="lot-values">
                    <td>' . $escape(format_text_value($certificadoPdf['precinto_certificado'] ?? '', '----------------')) . '</td>
                    <td>' . $escape(format_date_short((string) ($certificadoPdf['fecha_muestras_certificado'] ?? ''))) . '</td>
                    <td>' . $escape(format_date_short((string) ($certificadoPdf['fecha_finalizacion_ensayos_certificado'] ?? ''))) . '</td>
                    <td>' . $escape(format_text_value($certificadoPdf['nro_analisis_certificado'] ?? '', '----------------')) . '</td>
                </tr>
            </table>

            <table class="grid result-values">
                <tr><th style="width:35%">VIABILIDAD POR TETRAZOLIO</th><th style="width:15%">%</th><th style="width:35%">VIGOR POR TETRAZOLIO</th><th style="width:15%">%</th></tr>
                <tr><td class="left">1. Viable sin Defectos</td><td>' . $percent($certificadoPdf['tetrazolio_viable_sin_defectos_certificado'] ?? null) . '</td><td class="left">A. Vigor Alto</td><td>' . $percent($certificadoPdf['tetrazolio_vigor_alto_certificado'] ?? null) . '</td></tr>
                <tr><td class="left">2. Viable Defectos Leves</td><td>' . $percent($certificadoPdf['tetrazolio_viable_defectos_leves_certificado'] ?? null) . '</td><td class="left">B. Vigor Medio</td><td>' . $percent($certificadoPdf['tetrazolio_vigor_medio_certificado'] ?? null) . '</td></tr>
                <tr><td class="left">3. Viable Defectos Moderados</td><td>' . $percent($certificadoPdf['tetrazolio_viable_defectos_moderados_certificado'] ?? null) . '</td><td class="left">C. Vigor Bajo</td><td>' . $percent($certificadoPdf['tetrazolio_vigor_bajo_certificado'] ?? null) . '</td></tr>
                <tr><td class="left">4. Viable Defectos Severos<br><span class="critical">Límite Crítico</span></td><td>' . $percent($certificadoPdf['tetrazolio_viable_defectos_severos_certificado'] ?? null) . '</td><td class="bold">VIGOR</td><td class="total">' . $percent($vigorTotal) . '</td></tr>
                <tr><td class="left">5. No Viables</td><td>' . $percent($certificadoPdf['tetrazolio_no_viables_certificado'] ?? null) . '</td><td colspan="2" style="border:0"></td></tr>
                <tr><td class="left bold">% VIABILIDAD</td><td class="total">' . $percent($viabilidadTotal) . '</td><td colspan="2" style="border:0"></td></tr>
            </table>

            <table class="grid result-values">
                <tr><th colspan="5">% DAÑOS ACUMULADOS</th><th rowspan="2">DURAS</th></tr>
                <tr><th>MECÁNICOS</th><th>AMBIENTES</th><th>CHINCHES</th><th>FRACTURAS</th><th>OTROS</th></tr>
                <tr>
                    <td>' . $percent($certificadoPdf['tetrazolio_danos_mecanicos_certificado'] ?? null) . '</td>
                    <td>' . $percent($certificadoPdf['tetrazolio_danos_ambientes_certificado'] ?? null) . '</td>
                    <td>' . $percent($certificadoPdf['tetrazolio_danos_chinches_certificado'] ?? null) . '</td>
                    <td>' . $percent($certificadoPdf['tetrazolio_danos_fracturas_certificado'] ?? null) . '</td>
                    <td>' . $percent($certificadoPdf['tetrazolio_danos_otros_certificado'] ?? null) . '</td>
                    <td>' . $percent($certificadoPdf['tetrazolio_duras_certificado'] ?? null) . '</td>
                </tr>
            </table>

            <div class="si-no">ESTE CERTIFICADO &nbsp;&nbsp; SI [' . $amparaSi . '] &nbsp;&nbsp; NO [' . $amparaNo . '] &nbsp;&nbsp; AMPARA LA TOTALIDAD DEL LOTE</div>
        </body></html>';
    }

    //------------------------------------------------
    $footerHtml = '
    <table class="grid footer" style="width:100%; border-collapse: collapse;">
        <tr class="bold">
            <td style="border:1px solid #666; padding:4px 5px; text-align:center;">Localidad y País</td>
            <td style="border:1px solid #666; padding:4px 5px; text-align:center;">Fecha de Emisión</td>
            <td style="border:1px solid #666; padding:4px 5px; text-align:center;">Firma del Director Técnico</td>
        </tr>
        <tr>
            <td style="border:1px solid #666; padding:4px 5px; height:54px; text-align:center;">' . htmlspecialchars($certificateSettings['localidad_pais'], ENT_QUOTES, 'UTF-8') . '</td>
            <td style="border:1px solid #666; padding:4px 5px; text-align:center;">' . htmlspecialchars(format_date_long_spanish((string) ($certificadoPdf['fecha_emision_certificado'] ?? '')), ENT_QUOTES, 'UTF-8') . '</td>
            <td style="border:1px solid #666; padding:4px 5px 5px; text-align:center; vertical-align:bottom;">' . htmlspecialchars($certificateSettings['firma_director_tecnico'], ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
    </table>
    <div class="phone" style="text-align:center; font-size:10px; letter-spacing:1px; margin-top:6px;">TELÉFONO 2346-316003</div>
    <div class="address" style="text-align:center; font-size:10px; letter-spacing:1px;">SAN LORENZO N° 341 | CHIVILCOY (6620) BUENOS AIRES</div>
    ';
    //------------------------------------------------

    $mpdf = new \Mpdf\Mpdf([
        'format' => 'A4',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 8,
        'margin_bottom' => 30, //5
    ]);
    $mpdf->SetHTMLFooter($footerHtml);
    $mpdf->WriteHTML($html);

    $pdfFilename = 'certificado_' . $certificadoId . '.pdf';
    $pdfContent = $mpdf->Output($pdfFilename, \Mpdf\Output\Destination::STRING_RETURN);

    $certificadosDir = empresa_storage_dir(current_empresa_id());
    if (is_dir($certificadosDir) && is_writable($certificadosDir)) {
        @file_put_contents(rtrim($certificadosDir, '\\/') . DIRECTORY_SEPARATOR . $pdfFilename, $pdfContent);
    }

    return ['content' => $pdfContent, 'filename' => $pdfFilename];
}
