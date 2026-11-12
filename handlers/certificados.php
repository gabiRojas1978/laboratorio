<?php

declare(strict_types=1);

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_certificado'])) {
    $tipoCertificadoPost = trim((string) ($_POST['tipo_certificado'] ?? ''));
    $certForm['tipo_certificado'] = in_array($tipoCertificadoPost, ['certificadogeneral', 'certificadoinase', 'certificadotetrazolio'], true)
        ? $tipoCertificadoPost
        : 'certificadogeneral';
    $certForm['id_cliente_certificado'] = trim((string) ($_POST['id_cliente_certificado'] ?? ''));
    $certForm['id_especie_certificado'] = trim((string) ($_POST['id_especie_certificado'] ?? ''));
    $certForm['id_cultivar_certificado'] = trim((string) ($_POST['id_cultivar_certificado'] ?? ''));
    $certForm['marca_lote_certificado'] = trim((string) ($_POST['marca_lote_certificado'] ?? ''));
    $certForm['peso_certificado'] = trim((string) ($_POST['peso_certificado'] ?? ''));
    $certForm['nro_envases_certificado'] = trim((string) ($_POST['nro_envases_certificado'] ?? ''));
    $certForm['fecha_muestreo_certificado'] = trim((string) ($_POST['fecha_muestreo_certificado'] ?? ''));
    $certForm['precinto_certificado'] = trim((string) ($_POST['precinto_certificado'] ?? ''));
    $certForm['fecha_muestras_certificado'] = trim((string) ($_POST['fecha_muestras_certificado'] ?? ''));
    $certForm['fecha_finalizacion_ensayos_certificado'] = trim((string) ($_POST['fecha_finalizacion_ensayos_certificado'] ?? ''));
    $certForm['nro_analisis_certificado'] = trim((string) ($_POST['nro_analisis_certificado'] ?? ''));
    $certForm['pureza_semilla_pura_certificado'] = trim((string) ($_POST['pureza_semilla_pura_certificado'] ?? ''));
    $certForm['materia_inerte_certificado'] = trim((string) ($_POST['materia_inerte_certificado'] ?? ''));
    $certForm['pureza_otras_semillas_certificado'] = trim((string) ($_POST['pureza_otras_semillas_certificado'] ?? ''));
    $certForm['pg_numero_dias_certificado'] = trim((string) ($_POST['pg_numero_dias_certificado'] ?? ''));
    $certForm['pg_plantulas_normales_certificado'] = trim((string) ($_POST['pg_plantulas_normales_certificado'] ?? ''));
    $certForm['pg_semillas_duras_certificado'] = trim((string) ($_POST['pg_semillas_duras_certificado'] ?? ''));
    $certForm['pg_semillas_frescas_certificado'] = trim((string) ($_POST['pg_semillas_frescas_certificado'] ?? ''));
    $certForm['pg_plantulas_anormales_certificado'] = trim((string) ($_POST['pg_plantulas_anormales_certificado'] ?? ''));
    $certForm['pg_semillas_muertas_certificado'] = trim((string) ($_POST['pg_semillas_muertas_certificado'] ?? ''));
    $certForm['pg_contenido_humedad_certificado'] = trim((string) ($_POST['pg_contenido_humedad_certificado'] ?? ''));
    $certForm['pgc_numero_dias_certificado'] = trim((string) ($_POST['pgc_numero_dias_certificado'] ?? ''));
    $certForm['pgc_plantulas_normales_certificado'] = trim((string) ($_POST['pgc_plantulas_normales_certificado'] ?? ''));
    $certForm['pgc_semillas_duras_certificado'] = trim((string) ($_POST['pgc_semillas_duras_certificado'] ?? ''));
    $certForm['pgc_semillas_frescas_certificado'] = trim((string) ($_POST['pgc_semillas_frescas_certificado'] ?? ''));
    $certForm['pgc_plantulas_anormales_certificado'] = trim((string) ($_POST['pgc_plantulas_anormales_certificado'] ?? ''));
    $certForm['pgc_semillas_muertas_certificado'] = trim((string) ($_POST['pgc_semillas_muertas_certificado'] ?? ''));
    $certForm['vigor_nro_dias_certificado'] = trim((string) ($_POST['vigor_nro_dias_certificado'] ?? ''));
    $certForm['vigor_plantulas_normales_certificado'] = trim((string) ($_POST['vigor_plantulas_normales_certificado'] ?? ''));
    $certForm['id_otras_determinaciones_certificado'] = array_values(array_unique(array_filter(
        array_map('intval', (array) ($_POST['id_otras_determinaciones_certificado'] ?? [])),
        static fn($id) => $id > 0
    )));
    $certForm['otras_determinaciones_texto_certificado'] = trim((string) ($_POST['otras_determinaciones_texto_certificado'] ?? ''));
    $certForm['ampara_totalidad_lote_certificado'] = trim((string) ($_POST['ampara_totalidad_lote_certificado'] ?? 'N'));
    $certForm['localidad_pais_certificado'] = trim((string) ($_POST['localidad_pais_certificado'] ?? ''));
    $certForm['fecha_emision_certificado'] = trim((string) ($_POST['fecha_emision_certificado'] ?? ''));
    $certForm['otras_referencias_certificado'] = trim((string) ($_POST['otras_referencias_certificado'] ?? ''));
    $certForm['hipoclorito_max_10_certificado'] = trim((string) ($_POST['hipoclorito_max_10_certificado'] ?? ''));
    $certForm['bioensayo_enlist_certificado'] = trim((string) ($_POST['bioensayo_enlist_certificado'] ?? ''));
    $certForm['peroxidasa_positivas_certificado'] = trim((string) ($_POST['peroxidasa_positivas_certificado'] ?? ''));
    $certForm['peroxidasa_negativas_certificado'] = trim((string) ($_POST['peroxidasa_negativas_certificado'] ?? ''));
    $certForm['contam_hilo_cantidad_semillas_certificado'] = trim((string) ($_POST['contam_hilo_cantidad_semillas_certificado'] ?? ''));
    $certForm['contam_hilo_color_contaminante_certificado'] = trim((string) ($_POST['contam_hilo_color_contaminante_certificado'] ?? ''));
    $certForm['hipocotilo_variedad_certificado'] = trim((string) ($_POST['hipocotilo_variedad_certificado'] ?? ''));
    $certForm['hipocotilo_color_contaminante_certificado'] = trim((string) ($_POST['hipocotilo_color_contaminante_certificado'] ?? ''));
    $certForm['clase_materia_inerte_certificado'] = trim((string) ($_POST['clase_materia_inerte_certificado'] ?? ''));
    $certForm['otras_semillas_certificado'] = trim((string) ($_POST['otras_semillas_certificado'] ?? ''));
    $certForm['tetrazolio_detalle_certificado'] = trim((string) ($_POST['tetrazolio_detalle_certificado'] ?? ''));
    $tetrazolioNumericFields = [
        'tetrazolio_viable_sin_defectos_certificado',
        'tetrazolio_viable_defectos_leves_certificado',
        'tetrazolio_viable_defectos_moderados_certificado',
        'tetrazolio_viable_defectos_severos_certificado',
        'tetrazolio_no_viables_certificado',
        'tetrazolio_vigor_alto_certificado',
        'tetrazolio_vigor_medio_certificado',
        'tetrazolio_vigor_bajo_certificado',
        'tetrazolio_danos_mecanicos_certificado',
        'tetrazolio_danos_ambientes_certificado',
        'tetrazolio_danos_chinches_certificado',
        'tetrazolio_danos_fracturas_certificado',
        'tetrazolio_danos_otros_certificado',
        'tetrazolio_duras_certificado',
    ];
    foreach ($tetrazolioNumericFields as $tetrazolioNumericField) {
        $certForm[$tetrazolioNumericField] = trim((string) ($_POST[$tetrazolioNumericField] ?? ''));
    }

    if (mb_strlen($certForm['hipoclorito_max_10_certificado'], 'UTF-8') > 255) {
        $errors[] = 'El campo Hipoclorito max 10% no puede superar los 255 caracteres.';
    }

    if (mb_strlen($certForm['bioensayo_enlist_certificado'], 'UTF-8') > 255) {
        $errors[] = 'El campo Bioensayo Enlist no puede superar los 255 caracteres.';
    }

    foreach ([
        'peroxidasa_positivas_certificado',
        'peroxidasa_negativas_certificado',
        'contam_hilo_cantidad_semillas_certificado',
    ] as $percentageField) {
        $percentage = $certForm[$percentageField];
        if ($percentage !== '' && (!is_numeric($percentage) || (float) $percentage < 0 || (float) $percentage > 100)) {
            $errors[] = 'Los porcentajes de Peroxidasa y Contam. x hilo deben ser valores entre 0 y 100.';
            break;
        }
    }

    foreach ([
        'contam_hilo_color_contaminante_certificado',
        'hipocotilo_variedad_certificado',
        'hipocotilo_color_contaminante_certificado',
    ] as $textField) {
        if (mb_strlen($certForm[$textField], 'UTF-8') > 255) {
            $errors[] = 'Los campos de texto de las determinaciones adicionales no pueden superar los 255 caracteres.';
            break;
        }
    }

    if ($certForm['ampara_totalidad_lote_certificado'] !== '' && !in_array($certForm['ampara_totalidad_lote_certificado'], ['S', 'N'], true)) {
        $errors[] = 'La opción de ampara totalidad del lote no es válida.';
    }

    // Las medidas de los certificados no admiten valores negativos
    $nonNegativeFields = [
        'peso_certificado',
        'nro_envases_certificado',
        'pureza_semilla_pura_certificado',
        'materia_inerte_certificado',
        'pureza_otras_semillas_certificado',
        'pg_numero_dias_certificado',
        'pg_plantulas_normales_certificado',
        'pg_semillas_duras_certificado',
        'pg_semillas_frescas_certificado',
        'pg_plantulas_anormales_certificado',
        'pg_semillas_muertas_certificado',
        'pg_contenido_humedad_certificado',
        'pgc_numero_dias_certificado',
        'pgc_plantulas_normales_certificado',
        'pgc_semillas_duras_certificado',
        'pgc_semillas_frescas_certificado',
        'pgc_plantulas_anormales_certificado',
        'pgc_semillas_muertas_certificado',
        'vigor_nro_dias_certificado',
        'vigor_plantulas_normales_certificado',
        ...$tetrazolioNumericFields,
    ];
    foreach ($nonNegativeFields as $nonNegativeField) {
        if ($certForm[$nonNegativeField] !== '' && (float) $certForm[$nonNegativeField] < 0) {
            $errors[] = 'Las medidas del certificado no pueden ser negativas.';
            break;
        }
    }

    $clientId = filter_var($certForm['id_cliente_certificado'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $speciesId = filter_var($certForm['id_especie_certificado'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $cultivarRawId = trim((string) ($certForm['id_cultivar_certificado'] ?? ''));
    $cultivarId = $cultivarRawId === '' ? null : filter_var($cultivarRawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($clientId === false) {
        $errors[] = 'Debes seleccionar un cliente válido.';
    } else {
        $ownStatement = db()->prepare('SELECT id_cliente FROM clientes WHERE id_cliente = :id AND id_empresa = :id_empresa LIMIT 1');
        $ownStatement->execute([':id' => $clientId, ':id_empresa' => current_empresa_id()]);
        if ($ownStatement->fetch() === false) {
            $errors[] = 'El cliente seleccionado no existe.';
        }
    }

    if ($speciesId === false) {
        $errors[] = 'Debes seleccionar una especie válida.';
    } else {
        $ownStatement = db()->prepare('SELECT id_especie FROM especies WHERE id_especie = :id AND id_empresa = :id_empresa LIMIT 1');
        $ownStatement->execute([':id' => $speciesId, ':id_empresa' => current_empresa_id()]);
        if ($ownStatement->fetch() === false) {
            $errors[] = 'La especie seleccionada no existe.';
        }
    }

    if ($cultivarRawId !== '' && $cultivarId === false) {
        $errors[] = 'Debes seleccionar un cultivar válido.';
    } elseif ($cultivarId !== null) {
        $ownStatement = db()->prepare('SELECT id_cultivar FROM cultivares WHERE id_cultivar = :id AND id_empresa = :id_empresa LIMIT 1');
        $ownStatement->execute([':id' => $cultivarId, ':id_empresa' => current_empresa_id()]);
        if ($ownStatement->fetch() === false) {
            $errors[] = 'El cultivar seleccionado no existe.';
        }
    }

    if ($certForm['nro_analisis_certificado'] === '') {
        $errors[] = 'El número de análisis es obligatorio.';
    }

    if ($certForm['fecha_muestras_certificado'] === '') {
        $errors[] = 'La fecha de recepción de muestras es obligatoria.';
    }

    if ($certForm['fecha_finalizacion_ensayos_certificado'] === '') {
        $errors[] = 'La fecha de finalización de ensayos es obligatoria.';
    }

    if ($certForm['tipo_certificado'] === 'certificadotetrazolio') {
        $viabilidadFields = [
            'tetrazolio_viable_sin_defectos_certificado',
            'tetrazolio_viable_defectos_leves_certificado',
            'tetrazolio_viable_defectos_moderados_certificado',
            'tetrazolio_viable_defectos_severos_certificado',
            'tetrazolio_no_viables_certificado',
        ];
        $viabilidadSum = 0.0;

        foreach ($tetrazolioNumericFields as $tetrazolioNumericField) {
            $value = $certForm[$tetrazolioNumericField];
            if ($value === '') {
                if (in_array($tetrazolioNumericField, $viabilidadFields, true)) {
                    $errors[] = 'Debes completar todos los porcentajes de viabilidad por tetrazolio.';
                    break;
                }
                continue;
            }
            if (!is_numeric($value) || (float) $value < 0 || (float) $value > 100) {
                $errors[] = 'Los porcentajes de tetrazolio deben ser valores entre 0 y 100.';
                break;
            }
        }

        foreach ($viabilidadFields as $viabilidadField) {
            if (is_numeric($certForm[$viabilidadField])) {
                $viabilidadSum += (float) $certForm[$viabilidadField];
            }
        }
        if (abs($viabilidadSum - 100.0) > 0.01) {
            $errors[] = 'La columna de viabilidad por tetrazolio debe sumar 100 (actual: ' . format_percent_value($viabilidadSum) . ').';
        }
    }

    // Validar porcentajes de Pureza - DEBEN sumar 100 o 0
    $purezaFields = [
        'pureza_semilla_pura_certificado' => (float) $certForm['pureza_semilla_pura_certificado'],
        'materia_inerte_certificado' => (float) $certForm['materia_inerte_certificado'],
        'pureza_otras_semillas_certificado' => (float) $certForm['pureza_otras_semillas_certificado'],
    ];
    $purezaSum = 0;
    foreach ($purezaFields as $value) {
        if ($value > 100) {
            $errors[] = 'Los campos de Pureza no pueden exceder 100.';
            break;
        }
        $purezaSum += $value;
    }
    if (abs($purezaSum - 100.0) > 0.01 && abs($purezaSum) > 0.01) {
        $errors[] = 'La suma total de Pureza debe ser 100 o 0 (actual: ' . $purezaSum . ').';
    }

    // Validar porcentajes de Poder Germinativo - DEBEN sumar 100 o 0
    $pgFields = [
        'pg_plantulas_normales_certificado' => (float) $certForm['pg_plantulas_normales_certificado'],
        'pg_semillas_duras_certificado' => (float) $certForm['pg_semillas_duras_certificado'],
        'pg_semillas_frescas_certificado' => (float) $certForm['pg_semillas_frescas_certificado'],
        'pg_plantulas_anormales_certificado' => (float) $certForm['pg_plantulas_anormales_certificado'],
        'pg_semillas_muertas_certificado' => (float) $certForm['pg_semillas_muertas_certificado'],
    ];
    $pgSum = 0;
    foreach ($pgFields as $value) {
        if ($value > 100) {
            $errors[] = 'Los campos de Poder Germinativo no pueden exceder 100.';
            break;
        }
        $pgSum += $value;
    }
    if (abs($pgSum - 100.0) > 0.01 && abs($pgSum) > 0.01) {
        $errors[] = 'La suma total de Poder Germinativo debe ser 100 o 0 (actual: ' . $pgSum . ').';
    }

    // Validar porcentajes de Poder Germinativo Curado - DEBEN sumar 100 o 0
    $pgcFields = [
        'pgc_plantulas_normales_certificado' => (float) $certForm['pgc_plantulas_normales_certificado'],
        'pgc_semillas_duras_certificado' => (float) $certForm['pgc_semillas_duras_certificado'],
        'pgc_semillas_frescas_certificado' => (float) $certForm['pgc_semillas_frescas_certificado'],
        'pgc_plantulas_anormales_certificado' => (float) $certForm['pgc_plantulas_anormales_certificado'],
        'pgc_semillas_muertas_certificado' => (float) $certForm['pgc_semillas_muertas_certificado'],
    ];
    $pgcSum = 0;
    foreach ($pgcFields as $value) {
        if ($value > 100) {
            $errors[] = 'Los campos de Poder Germinativo Curado no pueden exceder 100.';
            break;
        }
        $pgcSum += $value;
    }
    if (abs($pgcSum - 100.0) > 0.01 && abs($pgcSum) > 0.01) {
        $errors[] = 'La suma total de Poder Germinativo Curado debe ser 100 o 0 (actual: ' . $pgcSum . ').';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'INSERT INTO certificados (tipo_certificado, id_empresa, id_usuario_certificado, id_cliente_certificado, id_especie_certificado, id_cultivar_certificado, marca_lote_certificado, peso_certificado, nro_envases_certificado, fecha_muestreo_certificado, otras_referencias_certificado, precinto_certificado, fecha_muestras_certificado, fecha_finalizacion_ensayos_certificado, nro_analisis_certificado, pureza_semilla_pura_certificado, materia_inerte_certificado, pureza_otras_semillas_certificado, pg_numero_dias_certificado, pg_plantulas_normales_certificado, pg_semillas_duras_certificado, pg_semillas_frescas_certificado, pg_plantulas_anormales_certificado, pg_semillas_muertas_certificado, pg_contenido_humedad_certificado, pgc_numero_dias_certificado, pgc_plantulas_normales_certificado, pgc_semillas_duras_certificado, pgc_semillas_frescas_certificado, pgc_plantulas_anormales_certificado, pgc_semillas_muertas_certificado, vigor_nro_dias_certificado, vigor_plantulas_normales_certificado, ampara_totalidad_lote_certificado, localidad_pais_certificado, fecha_emision_certificado, clase_materia_inerte_certificado, otras_semillas_certificado, hipoclorito_max_10_certificado, bioensayo_enlist_certificado, peroxidasa_positivas_certificado, peroxidasa_negativas_certificado, contam_hilo_cantidad_semillas_certificado, contam_hilo_color_contaminante_certificado, hipocotilo_variedad_certificado, hipocotilo_color_contaminante_certificado, otras_determinaciones_texto_certificado, tetrazolio_detalle_certificado, tetrazolio_viable_sin_defectos_certificado, tetrazolio_viable_defectos_leves_certificado, tetrazolio_viable_defectos_moderados_certificado, tetrazolio_viable_defectos_severos_certificado, tetrazolio_no_viables_certificado, tetrazolio_vigor_alto_certificado, tetrazolio_vigor_medio_certificado, tetrazolio_vigor_bajo_certificado, tetrazolio_danos_mecanicos_certificado, tetrazolio_danos_ambientes_certificado, tetrazolio_danos_chinches_certificado, tetrazolio_danos_fracturas_certificado, tetrazolio_danos_otros_certificado, tetrazolio_duras_certificado) VALUES (:tipo_certificado, :id_empresa, :id_usuario_certificado, :id_cliente_certificado, :id_especie_certificado, :id_cultivar_certificado, :marca_lote_certificado, :peso_certificado, :nro_envases_certificado, :fecha_muestreo_certificado, :otras_referencias_certificado, :precinto_certificado, :fecha_muestras_certificado, :fecha_finalizacion_ensayos_certificado, :nro_analisis_certificado, :pureza_semilla_pura_certificado, :materia_inerte_certificado, :pureza_otras_semillas_certificado, :pg_numero_dias_certificado, :pg_plantulas_normales_certificado, :pg_semillas_duras_certificado, :pg_semillas_frescas_certificado, :pg_plantulas_anormales_certificado, :pg_semillas_muertas_certificado, :pg_contenido_humedad_certificado, :pgc_numero_dias_certificado, :pgc_plantulas_normales_certificado, :pgc_semillas_duras_certificado, :pgc_semillas_frescas_certificado, :pgc_plantulas_anormales_certificado, :pgc_semillas_muertas_certificado, :vigor_nro_dias_certificado, :vigor_plantulas_normales_certificado, :ampara_totalidad_lote_certificado, :localidad_pais_certificado, :fecha_emision_certificado, :clase_materia_inerte_certificado, :otras_semillas_certificado, :hipoclorito_max_10_certificado, :bioensayo_enlist_certificado, :peroxidasa_positivas_certificado, :peroxidasa_negativas_certificado, :contam_hilo_cantidad_semillas_certificado, :contam_hilo_color_contaminante_certificado, :hipocotilo_variedad_certificado, :hipocotilo_color_contaminante_certificado, :otras_determinaciones_texto_certificado, :tetrazolio_detalle_certificado, :tetrazolio_viable_sin_defectos_certificado, :tetrazolio_viable_defectos_leves_certificado, :tetrazolio_viable_defectos_moderados_certificado, :tetrazolio_viable_defectos_severos_certificado, :tetrazolio_no_viables_certificado, :tetrazolio_vigor_alto_certificado, :tetrazolio_vigor_medio_certificado, :tetrazolio_vigor_bajo_certificado, :tetrazolio_danos_mecanicos_certificado, :tetrazolio_danos_ambientes_certificado, :tetrazolio_danos_chinches_certificado, :tetrazolio_danos_fracturas_certificado, :tetrazolio_danos_otros_certificado, :tetrazolio_duras_certificado)'
            );
            $statement->execute([
                ':tipo_certificado' => $certForm['tipo_certificado'],
                ':id_empresa' => current_empresa_id(),
                ':id_usuario_certificado' => $_SESSION['user']['id'] ?? null,
                ':id_cliente_certificado' => $clientId,
                ':id_especie_certificado' => $speciesId,
                ':id_cultivar_certificado' => $cultivarId !== null ? $cultivarId : null,
                ':marca_lote_certificado' => $certForm['marca_lote_certificado'] !== '' ? $certForm['marca_lote_certificado'] : null,
                ':peso_certificado' => $certForm['peso_certificado'] !== '' ? (float)$certForm['peso_certificado'] : null,
                ':nro_envases_certificado' => $certForm['nro_envases_certificado'] !== '' ? (int)$certForm['nro_envases_certificado'] : null,
                ':fecha_muestreo_certificado' => $certForm['fecha_muestreo_certificado'] !== '' ? $certForm['fecha_muestreo_certificado'] : null,
                ':otras_referencias_certificado' => $certForm['otras_referencias_certificado'] !== '' ? $certForm['otras_referencias_certificado'] : null,
                ':precinto_certificado' => $certForm['precinto_certificado'] !== '' ? $certForm['precinto_certificado'] : null,
                ':fecha_muestras_certificado' => $certForm['fecha_muestras_certificado'] !== '' ? $certForm['fecha_muestras_certificado'] : null,
                ':fecha_finalizacion_ensayos_certificado' => $certForm['fecha_finalizacion_ensayos_certificado'] !== '' ? $certForm['fecha_finalizacion_ensayos_certificado'] : null,
                ':nro_analisis_certificado' => $certForm['nro_analisis_certificado'] !== '' ? $certForm['nro_analisis_certificado'] : null,
                ':pureza_semilla_pura_certificado' => $certForm['pureza_semilla_pura_certificado'] !== '' ? (float)$certForm['pureza_semilla_pura_certificado'] : null,
                ':materia_inerte_certificado' => $certForm['materia_inerte_certificado'] !== '' ? (float)$certForm['materia_inerte_certificado'] : null,
                ':pureza_otras_semillas_certificado' => $certForm['pureza_otras_semillas_certificado'] !== '' ? (float)$certForm['pureza_otras_semillas_certificado'] : null,
                ':pg_numero_dias_certificado' => $certForm['pg_numero_dias_certificado'] !== '' ? (int)$certForm['pg_numero_dias_certificado'] : null,
                ':pg_plantulas_normales_certificado' => $certForm['pg_plantulas_normales_certificado'] !== '' ? (float)$certForm['pg_plantulas_normales_certificado'] : null,
                ':pg_semillas_duras_certificado' => $certForm['pg_semillas_duras_certificado'] !== '' ? (float)$certForm['pg_semillas_duras_certificado'] : null,
                ':pg_semillas_frescas_certificado' => $certForm['pg_semillas_frescas_certificado'] !== '' ? (float)$certForm['pg_semillas_frescas_certificado'] : null,
                ':pg_plantulas_anormales_certificado' => $certForm['pg_plantulas_anormales_certificado'] !== '' ? (float)$certForm['pg_plantulas_anormales_certificado'] : null,
                ':pg_semillas_muertas_certificado' => $certForm['pg_semillas_muertas_certificado'] !== '' ? (float)$certForm['pg_semillas_muertas_certificado'] : null,
                ':pg_contenido_humedad_certificado' => $certForm['pg_contenido_humedad_certificado'] !== '' ? (float)$certForm['pg_contenido_humedad_certificado'] : null,
                ':pgc_numero_dias_certificado' => $certForm['pgc_numero_dias_certificado'] !== '' ? (int)$certForm['pgc_numero_dias_certificado'] : null,
                ':pgc_plantulas_normales_certificado' => $certForm['pgc_plantulas_normales_certificado'] !== '' ? (float)$certForm['pgc_plantulas_normales_certificado'] : null,
                ':pgc_semillas_duras_certificado' => $certForm['pgc_semillas_duras_certificado'] !== '' ? (float)$certForm['pgc_semillas_duras_certificado'] : null,
                ':pgc_semillas_frescas_certificado' => $certForm['pgc_semillas_frescas_certificado'] !== '' ? (float)$certForm['pgc_semillas_frescas_certificado'] : null,
                ':pgc_plantulas_anormales_certificado' => $certForm['pgc_plantulas_anormales_certificado'] !== '' ? (float)$certForm['pgc_plantulas_anormales_certificado'] : null,
                ':pgc_semillas_muertas_certificado' => $certForm['pgc_semillas_muertas_certificado'] !== '' ? (float)$certForm['pgc_semillas_muertas_certificado'] : null,
                ':vigor_nro_dias_certificado' => $certForm['vigor_nro_dias_certificado'] !== '' ? (int)$certForm['vigor_nro_dias_certificado'] : null,
                ':vigor_plantulas_normales_certificado' => $certForm['vigor_plantulas_normales_certificado'] !== '' ? (float)$certForm['vigor_plantulas_normales_certificado'] : null,
                ':ampara_totalidad_lote_certificado' => $certForm['ampara_totalidad_lote_certificado'],
                ':localidad_pais_certificado' => $certForm['localidad_pais_certificado'] !== '' ? $certForm['localidad_pais_certificado'] : null,
                ':fecha_emision_certificado' => $certForm['fecha_emision_certificado'] !== '' ? $certForm['fecha_emision_certificado'] : null,
                ':clase_materia_inerte_certificado' => $certForm['clase_materia_inerte_certificado'],
                ':otras_semillas_certificado' => $certForm['otras_semillas_certificado'],
                ':hipoclorito_max_10_certificado' => $certForm['hipoclorito_max_10_certificado'] !== '' ? $certForm['hipoclorito_max_10_certificado'] : null,
                ':bioensayo_enlist_certificado' => $certForm['bioensayo_enlist_certificado'] !== '' ? $certForm['bioensayo_enlist_certificado'] : null,
                ':peroxidasa_positivas_certificado' => $certForm['peroxidasa_positivas_certificado'] !== '' ? (float) $certForm['peroxidasa_positivas_certificado'] : null,
                ':peroxidasa_negativas_certificado' => $certForm['peroxidasa_negativas_certificado'] !== '' ? (float) $certForm['peroxidasa_negativas_certificado'] : null,
                ':contam_hilo_cantidad_semillas_certificado' => $certForm['contam_hilo_cantidad_semillas_certificado'] !== '' ? (float) $certForm['contam_hilo_cantidad_semillas_certificado'] : null,
                ':contam_hilo_color_contaminante_certificado' => $certForm['contam_hilo_color_contaminante_certificado'] !== '' ? $certForm['contam_hilo_color_contaminante_certificado'] : null,
                ':hipocotilo_variedad_certificado' => $certForm['hipocotilo_variedad_certificado'] !== '' ? $certForm['hipocotilo_variedad_certificado'] : null,
                ':hipocotilo_color_contaminante_certificado' => $certForm['hipocotilo_color_contaminante_certificado'] !== '' ? $certForm['hipocotilo_color_contaminante_certificado'] : null,
                ':otras_determinaciones_texto_certificado' => $certForm['otras_determinaciones_texto_certificado'] !== '' ? $certForm['otras_determinaciones_texto_certificado'] : null,
                ':tetrazolio_detalle_certificado' => $certForm['tetrazolio_detalle_certificado'] !== '' ? $certForm['tetrazolio_detalle_certificado'] : null,
                ':tetrazolio_viable_sin_defectos_certificado' => $certForm['tetrazolio_viable_sin_defectos_certificado'] !== '' ? (float) $certForm['tetrazolio_viable_sin_defectos_certificado'] : null,
                ':tetrazolio_viable_defectos_leves_certificado' => $certForm['tetrazolio_viable_defectos_leves_certificado'] !== '' ? (float) $certForm['tetrazolio_viable_defectos_leves_certificado'] : null,
                ':tetrazolio_viable_defectos_moderados_certificado' => $certForm['tetrazolio_viable_defectos_moderados_certificado'] !== '' ? (float) $certForm['tetrazolio_viable_defectos_moderados_certificado'] : null,
                ':tetrazolio_viable_defectos_severos_certificado' => $certForm['tetrazolio_viable_defectos_severos_certificado'] !== '' ? (float) $certForm['tetrazolio_viable_defectos_severos_certificado'] : null,
                ':tetrazolio_no_viables_certificado' => $certForm['tetrazolio_no_viables_certificado'] !== '' ? (float) $certForm['tetrazolio_no_viables_certificado'] : null,
                ':tetrazolio_vigor_alto_certificado' => $certForm['tetrazolio_vigor_alto_certificado'] !== '' ? (float) $certForm['tetrazolio_vigor_alto_certificado'] : null,
                ':tetrazolio_vigor_medio_certificado' => $certForm['tetrazolio_vigor_medio_certificado'] !== '' ? (float) $certForm['tetrazolio_vigor_medio_certificado'] : null,
                ':tetrazolio_vigor_bajo_certificado' => $certForm['tetrazolio_vigor_bajo_certificado'] !== '' ? (float) $certForm['tetrazolio_vigor_bajo_certificado'] : null,
                ':tetrazolio_danos_mecanicos_certificado' => $certForm['tetrazolio_danos_mecanicos_certificado'] !== '' ? (float) $certForm['tetrazolio_danos_mecanicos_certificado'] : null,
                ':tetrazolio_danos_ambientes_certificado' => $certForm['tetrazolio_danos_ambientes_certificado'] !== '' ? (float) $certForm['tetrazolio_danos_ambientes_certificado'] : null,
                ':tetrazolio_danos_chinches_certificado' => $certForm['tetrazolio_danos_chinches_certificado'] !== '' ? (float) $certForm['tetrazolio_danos_chinches_certificado'] : null,
                ':tetrazolio_danos_fracturas_certificado' => $certForm['tetrazolio_danos_fracturas_certificado'] !== '' ? (float) $certForm['tetrazolio_danos_fracturas_certificado'] : null,
                ':tetrazolio_danos_otros_certificado' => $certForm['tetrazolio_danos_otros_certificado'] !== '' ? (float) $certForm['tetrazolio_danos_otros_certificado'] : null,
                ':tetrazolio_duras_certificado' => $certForm['tetrazolio_duras_certificado'] !== '' ? (float) $certForm['tetrazolio_duras_certificado'] : null,
            ]);

            $newCertificadoId = (int) db()->lastInsertId();

            if ($certForm['id_otras_determinaciones_certificado'] !== []) {
                $detStatement = db()->prepare(
                    'INSERT IGNORE INTO certificado_determinaciones (id_certificado, id_determinacion) SELECT :id_certificado, id_determinacion FROM otras_determinaciones WHERE id_determinacion = :id_determinacion AND id_empresa = :id_empresa'
                );
                foreach ($certForm['id_otras_determinaciones_certificado'] as $detId) {
                    $detStatement->execute([':id_certificado' => $newCertificadoId, ':id_determinacion' => $detId, ':id_empresa' => current_empresa_id()]);
                }
            }

            $successMessage = 'Certificado dado de alta correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $_SESSION['new_certificado_id'] = $newCertificadoId;
            $certPageDestino = [
                'certificadoinase' => 'CertificadoINASE',
                'certificadotetrazolio' => 'CertificadoTetrazolio',
            ][$certForm['tipo_certificado']] ?? 'Certificadogeneral';
            $certForm = [
                'tipo_certificado' => '',
                'id_cliente_certificado' => '',
                'id_especie_certificado' => '',
                'id_cultivar_certificado' => '',
                'marca_lote_certificado' => '',
                'peso_certificado' => '',
                'nro_envases_certificado' => '',
                'fecha_muestreo_certificado' => '',
                'otras_referencias_certificado' => '',
                'precinto_certificado' => '',
                'fecha_muestras_certificado' => '',
                'fecha_finalizacion_ensayos_certificado' => '',
                'nro_analisis_certificado' => '',
                'pureza_semilla_pura_certificado' => '',
                'materia_inerte_certificado' => '',
                'pureza_otras_semillas_certificado' => '',
                'pg_numero_dias_certificado' => '',
                'pg_plantulas_normales_certificado' => '',
                'pg_semillas_duras_certificado' => '',
                'pg_semillas_frescas_certificado' => '',
                'pg_plantulas_anormales_certificado' => '',
                'pg_semillas_muertas_certificado' => '',
                'pg_contenido_humedad_certificado' => '',
                'pgc_numero_dias_certificado' => '',
                'pgc_plantulas_normales_certificado' => '',
                'pgc_semillas_duras_certificado' => '',
                'pgc_semillas_frescas_certificado' => '',
                'pgc_plantulas_anormales_certificado' => '',
                'pgc_semillas_muertas_certificado' => '',
                'vigor_nro_dias_certificado' => '',
                'vigor_plantulas_normales_certificado' => '',
                'id_otras_determinaciones_certificado' => [],
                'otras_determinaciones_texto_certificado' => '',
                'ampara_totalidad_lote_certificado' => 'N',
                'localidad_pais_certificado' => '',
                'fecha_emision_certificado' => '',
                'hipoclorito_max_10_certificado' => '',
                'bioensayo_enlist_certificado' => '',
                'peroxidasa_positivas_certificado' => '',
                'peroxidasa_negativas_certificado' => '',
                'contam_hilo_cantidad_semillas_certificado' => '',
                'contam_hilo_color_contaminante_certificado' => '',
                'hipocotilo_variedad_certificado' => '',
                'hipocotilo_color_contaminante_certificado' => '',
                'clase_materia_inerte_certificado' => '',
                'otras_semillas_certificado' => '',
                'tetrazolio_detalle_certificado' => '',
                'tetrazolio_viable_sin_defectos_certificado' => '',
                'tetrazolio_viable_defectos_leves_certificado' => '',
                'tetrazolio_viable_defectos_moderados_certificado' => '',
                'tetrazolio_viable_defectos_severos_certificado' => '',
                'tetrazolio_no_viables_certificado' => '',
                'tetrazolio_vigor_alto_certificado' => '',
                'tetrazolio_vigor_medio_certificado' => '',
                'tetrazolio_vigor_bajo_certificado' => '',
                'tetrazolio_danos_mecanicos_certificado' => '',
                'tetrazolio_danos_ambientes_certificado' => '',
                'tetrazolio_danos_chinches_certificado' => '',
                'tetrazolio_danos_fracturas_certificado' => '',
                'tetrazolio_danos_otros_certificado' => '',
                'tetrazolio_duras_certificado' => '',
            ];

            if (($page ?? 'dashboard') !== $certPageDestino) {
                header('Location: /index.php?page=' . $certPageDestino);
                exit;
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible guardar el certificado: ' . $throwable->getMessage();
        }
    }
}

if (is_logged_in() && isset($_GET['pdf'])) {
    $certificadoId = filter_var($_GET['pdf'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($certificadoId === false) {
        http_response_code(400);
        echo 'ID de certificado inválido.';
        exit;
    }

    $pdfResult = build_certificado_pdf($certificadoId);

    if ($pdfResult === null) {
        http_response_code(404);
        echo 'Certificado no encontrado.';
        exit;
    }

    $pdfDisposition = isset($_GET['download']) ? 'attachment' : 'inline';
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . $pdfDisposition . '; filename="' . $pdfResult['filename'] . '"');
    header('Content-Length: ' . strlen($pdfResult['content']));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    echo $pdfResult['content'];
    exit;
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_certificado_email'])) {
    header('Content-Type: application/json; charset=utf-8');

    $certificadoId = filter_var($_POST['certificado_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $destinatarios = normalize_client_emails($_POST['destinatarios'] ?? []);

    if ($certificadoId === false) {
        echo json_encode(['ok' => false, 'error' => 'Certificado inválido.']);
        exit;
    }

    if ($destinatarios === []) {
        echo json_encode(['ok' => false, 'error' => 'Selecciona al menos un email destinatario.']);
        exit;
    }

    foreach ($destinatarios as $destinatario) {
        if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => false, 'error' => 'Uno de los emails destinatario no es válido.']);
            exit;
        }
    }

    $checkStatement = db()->prepare('SELECT id_certificado FROM certificados WHERE id_certificado = :id AND id_empresa = :id_empresa LIMIT 1');
    $checkStatement->execute([':id' => $certificadoId, ':id_empresa' => current_empresa_id()]);
    if ($checkStatement->fetch() === false) {
        echo json_encode(['ok' => false, 'error' => 'Certificado no encontrado.']);
        exit;
    }

    $smtpSettings = get_smtp_settings(current_empresa_id());
    if (!smtp_is_configured($smtpSettings)) {
        echo json_encode(['ok' => false, 'error' => 'La empresa no tiene configurado el envío de correo (ver Configuración).']);
        exit;
    }

    $pdfResult = build_certificado_pdf($certificadoId);
    if ($pdfResult === null) {
        echo json_encode(['ok' => false, 'error' => 'No fue posible generar el PDF del certificado.']);
        exit;
    }

    try {
        $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = $smtpSettings['host'];
        $mailer->Port = (int) ($smtpSettings['port'] !== '' ? $smtpSettings['port'] : 587);
        $mailer->SMTPAuth = true;
        $mailer->Username = $smtpSettings['usuario'];
        $mailer->Password = $smtpSettings['password'];
        if ($smtpSettings['seguridad'] === 'ssl') {
            $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($smtpSettings['seguridad'] === 'tls') {
            $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mailer->SMTPAutoTLS = false;
        }
        $mailer->CharSet = 'UTF-8';
        $mailer->setFrom($smtpSettings['remitente_email'], $smtpSettings['remitente_nombre'] !== '' ? $smtpSettings['remitente_nombre'] : APP_NAME);
        foreach ($destinatarios as $destinatario) {
            $mailer->addAddress($destinatario);
        }
        $mailer->Subject = 'Certificado de análisis N° ' . $certificadoId;
        $mailer->isHTML(true);
        $mailer->Body = '<p>Adjuntamos el certificado de análisis solicitado.</p>';
        $mailer->addStringAttachment($pdfResult['content'], $pdfResult['filename'], 'base64', 'application/pdf');
        $mailer->send();

        echo json_encode(['ok' => true]);
    } catch (Throwable $throwable) {
        echo json_encode(['ok' => false, 'error' => 'No fue posible enviar el email: ' . $throwable->getMessage()]);
    }
    exit;
}
