<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/boletin_edicion.php';

if (is_logged_in() && isset($_GET['boletin_tetrazolio_soja_pdf'])) {
    $boletinId = filter_var($_GET['boletin_tetrazolio_soja_pdf'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($boletinId === false) {
        http_response_code(400);
        exit('ID de boletín inválido.');
    }

    $pdfResult = build_boletin_tetrazolio_soja_pdf($boletinId);
    if ($pdfResult === null) {
        http_response_code(404);
        exit('Boletín no encontrado.');
    }

    $disposition = isset($_GET['download']) ? 'attachment' : 'inline';
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . $disposition . '; filename="' . $pdfResult['filename'] . '"');
    header('Content-Length: ' . strlen($pdfResult['content']));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    echo $pdfResult['content'];
    exit;
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_boletin_tetrazolio_soja'])) {
    $ingresoAsociadoId = null;
    if (isset($_POST['ingreso_id']) && $_POST['ingreso_id'] !== '') {
        $ingresoAsociadoId = filter_var($_POST['ingreso_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($ingresoAsociadoId === false) {
            $errors[] = 'El ingreso asociado no es válido.';
        }
    }
    $boletinTetrazolioSojaEditId = null;
    try {
        $boletinTetrazolioSojaEditId = boletin_edicion_id();
        if ($boletinTetrazolioSojaEditId !== null) {
            boletin_edicion_cabecera('tetrazolio_soja', $boletinTetrazolioSojaEditId);
        }
    } catch (Throwable $throwable) {
        $errors[] = 'El boletín a editar no es válido o no pertenece a la empresa activa.';
    }
    foreach ([
        'id_cliente', 'id_especie', 'muestra_nro', 'nro_interno', 'nro_certificado',
        'nombre_solicitante', 'precinto_muestra', 'procedencia', 'fecha_recepcion',
        'fecha_analisis', 'analista', 'cultivar', 'concentracion_tz', 'tiempo_tincion',
        'temperatura_tincion', 'tolerancia_tabla', 'observaciones',
    ] as $campo) {
        $boletinTetrazolioSojaForm[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }
    $boletinTetrazolioSojaForm['semillas_evaluadas'] = '200';

    $postedRepeticiones = (array) ($_POST['repeticiones'] ?? []);
    $repeticiones = [];
    foreach (range(1, 4) as $numero) {
        $posted = (array) ($postedRepeticiones[$numero] ?? []);
        $repeticion = [];
        foreach (array_keys(boletin_tetrazolio_soja_campos_repeticion()) as $categoria) {
            $raw = trim((string) ($posted[$categoria] ?? ''));
            $boletinTetrazolioSojaForm['repeticiones'][$numero][$categoria] = $raw;
            $valor = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 200]]);
            if ($valor === false) {
                $errors[] = 'Completa todas las cantidades de las cuatro repeticiones con enteros entre 0 y 200.';
                continue 2;
            }
            $repeticion[$categoria] = $valor;
        }

        $totalViablesNoViables = array_sum(array_map(
            static fn(string $categoria): int => $repeticion[$categoria],
            ['viables_sin_defectos', 'viables_defectos_leves', 'viables_defectos_moderados', 'viables_defectos_severos', 'no_viables']
        ));
        if ($totalViablesNoViables !== 200) {
            $errors[] = 'La suma de los cinco niveles de viabilidad debe ser exactamente 200 semillas por repetición.';
        }
        $repeticiones[] = $repeticion;
    }

    if ($boletinTetrazolioSojaForm['muestra_nro'] === '') {
        $errors[] = 'El número de muestra es obligatorio.';
    }
    $longitudesMaximas = [
        'muestra_nro' => 100,
        'nro_interno' => 100,
        'nro_certificado' => 100,
        'nombre_solicitante' => 255,
        'precinto_muestra' => 100,
        'procedencia' => 255,
        'analista' => 150,
        'cultivar' => 150,
        'concentracion_tz' => 100,
        'tiempo_tincion' => 100,
        'temperatura_tincion' => 100,
        'tolerancia_tabla' => 100,
    ];
    foreach ($longitudesMaximas as $campo => $maximo) {
        if (mb_strlen($boletinTetrazolioSojaForm[$campo], 'UTF-8') > $maximo) {
            $errors[] = 'El campo ' . str_replace('_', ' ', $campo) . ' admite hasta ' . $maximo . ' caracteres.';
        }
    }

    foreach (['fecha_recepcion', 'fecha_analisis'] as $campoFecha) {
        $fecha = $boletinTetrazolioSojaForm[$campoFecha];
        if ($fecha !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !checkdate((int) substr($fecha, 5, 2), (int) substr($fecha, 8, 2), (int) substr($fecha, 0, 4)))) {
            $errors[] = 'Las fechas de recepción y análisis deben ser válidas.';
            break;
        }
    }

    $clienteId = filter_var($boletinTetrazolioSojaForm['id_cliente'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($clienteId === false) {
        $errors[] = 'Debes seleccionar un cliente válido.';
    } else {
        $clienteStatement = db()->prepare('SELECT id_cliente FROM clientes WHERE id_cliente = :id AND id_empresa = :id_empresa LIMIT 1');
        $clienteStatement->execute([':id' => $clienteId, ':id_empresa' => current_empresa_id()]);
        if ($clienteStatement->fetch() === false) {
            $errors[] = 'El cliente seleccionado no existe.';
        }
    }

    $especieId = filter_var($boletinTetrazolioSojaForm['id_especie'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($especieId === false) {
        $errors[] = 'Debes seleccionar una especie válida.';
    } else {
        $especieStatement = db()->prepare(
            'SELECT nombre_especie, cientifico_especie
             FROM especies
             WHERE id_especie = :id AND id_empresa = :id_empresa
             LIMIT 1'
        );
        $especieStatement->execute([':id' => $especieId, ':id_empresa' => current_empresa_id()]);
        $especie = $especieStatement->fetch();
        if ($especie === false || !boletin_tetrazolio_soja_especie_valida((string) $especie['nombre_especie'], $especie['cientifico_especie'] ?? null)) {
            $errors[] = 'Este boletín solo admite especies de soja.';
        }
    }

    if (count($repeticiones) === 4 && !$errors) {
        $calculos = calcular_boletin_tetrazolio_soja($repeticiones);
        try {
            db()->beginTransaction();
            $insert = db()->prepare(
                'INSERT INTO boletines_tetrazolio_soja
                    (id_empresa, id_cliente, id_usuario, id_especie, muestra_nro, nro_interno, nro_certificado,
                     nombre_solicitante, precinto_muestra, procedencia, fecha_recepcion, fecha_analisis, analista,
                     cultivar, semillas_evaluadas, concentracion_tz, tiempo_tincion, temperatura_tincion,
                     rep_mayor_menor, tolerancia_tabla, observaciones)
                 VALUES
                    (:id_empresa, :id_cliente, :id_usuario, :id_especie, :muestra_nro, :nro_interno, :nro_certificado,
                     :nombre_solicitante, :precinto_muestra, :procedencia, :fecha_recepcion, :fecha_analisis, :analista,
                     :cultivar, 200, :concentracion_tz, :tiempo_tincion, :temperatura_tincion,
                     :rep_mayor_menor, :tolerancia_tabla, :observaciones)'
            );
            $parametrosCabecera = [
                ':id_empresa' => current_empresa_id(),
                ':id_cliente' => $clienteId,
                ':id_usuario' => $_SESSION['user']['id'] ?? null,
                ':id_especie' => $especieId,
                ':muestra_nro' => $boletinTetrazolioSojaForm['muestra_nro'],
                ':nro_interno' => $boletinTetrazolioSojaForm['nro_interno'] !== '' ? $boletinTetrazolioSojaForm['nro_interno'] : null,
                ':nro_certificado' => $boletinTetrazolioSojaForm['nro_certificado'] !== '' ? $boletinTetrazolioSojaForm['nro_certificado'] : null,
                ':nombre_solicitante' => $boletinTetrazolioSojaForm['nombre_solicitante'] !== '' ? $boletinTetrazolioSojaForm['nombre_solicitante'] : null,
                ':precinto_muestra' => $boletinTetrazolioSojaForm['precinto_muestra'] !== '' ? $boletinTetrazolioSojaForm['precinto_muestra'] : null,
                ':procedencia' => $boletinTetrazolioSojaForm['procedencia'] !== '' ? $boletinTetrazolioSojaForm['procedencia'] : null,
                ':fecha_recepcion' => $boletinTetrazolioSojaForm['fecha_recepcion'] !== '' ? $boletinTetrazolioSojaForm['fecha_recepcion'] : null,
                ':fecha_analisis' => $boletinTetrazolioSojaForm['fecha_analisis'] !== '' ? $boletinTetrazolioSojaForm['fecha_analisis'] : null,
                ':analista' => $boletinTetrazolioSojaForm['analista'] !== '' ? $boletinTetrazolioSojaForm['analista'] : null,
                ':cultivar' => $boletinTetrazolioSojaForm['cultivar'] !== '' ? $boletinTetrazolioSojaForm['cultivar'] : null,
                ':concentracion_tz' => $boletinTetrazolioSojaForm['concentracion_tz'] !== '' ? $boletinTetrazolioSojaForm['concentracion_tz'] : null,
                ':tiempo_tincion' => $boletinTetrazolioSojaForm['tiempo_tincion'] !== '' ? $boletinTetrazolioSojaForm['tiempo_tincion'] : null,
                ':temperatura_tincion' => $boletinTetrazolioSojaForm['temperatura_tincion'] !== '' ? $boletinTetrazolioSojaForm['temperatura_tincion'] : null,
                ':rep_mayor_menor' => number_format($calculos['rep_mayor_menor_porcentaje'], 2, '.', ''),
                ':tolerancia_tabla' => $boletinTetrazolioSojaForm['tolerancia_tabla'] !== '' ? $boletinTetrazolioSojaForm['tolerancia_tabla'] : null,
                ':observaciones' => $boletinTetrazolioSojaForm['observaciones'] !== '' ? $boletinTetrazolioSojaForm['observaciones'] : null,
            ];
            if ($boletinTetrazolioSojaEditId !== null) {
                $parametrosCabecera[':semillas_evaluadas'] = 200;
                boletin_edicion_actualizar('tetrazolio_soja', $boletinTetrazolioSojaEditId, $parametrosCabecera);
                $boletinId = $boletinTetrazolioSojaEditId;
            } else {
                $insert->execute($parametrosCabecera);
                $boletinId = (int) db()->lastInsertId();
            }

            $columnasRepeticion = implode(', ', array_keys(boletin_tetrazolio_soja_campos_repeticion()));
            $marcadores = implode(', ', array_map(static fn(string $columna): string => ':' . $columna, array_keys(boletin_tetrazolio_soja_campos_repeticion())));
            $insertRepeticion = db()->prepare(
                'INSERT INTO boletin_tetrazolio_soja_repeticiones
                    (id_boletin_tetrazolio_soja, nro_repeticion, ' . $columnasRepeticion . ')
                 VALUES (:id_boletin, :nro_repeticion, ' . $marcadores . ')'
            );
            foreach ($repeticiones as $indice => $repeticion) {
                $parametros = [
                    ':id_boletin' => $boletinId,
                    ':nro_repeticion' => $indice + 1,
                ];
                foreach ($repeticion as $categoria => $cantidad) {
                    $parametros[':' . $categoria] = $cantidad;
                }
                $insertRepeticion->execute($parametros);
            }
            if ($ingresoAsociadoId !== null && $ingresoAsociadoId !== false) {
                libro_ingreso_vincular_boletin((int) $ingresoAsociadoId, 'boletin_tetrazolio_soja', $boletinId);
            }
            db()->commit();
            $_SESSION['success_message'] = 'Boletín de Tetrazolio (Soja) guardado correctamente.';
            header('Location: /index.php?page=boletin_tetrazolio_soja');
            exit;
        } catch (Throwable $throwable) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            $errors[] = 'No fue posible guardar el boletín de Tetrazolio (Soja).';
        }
    }
}
