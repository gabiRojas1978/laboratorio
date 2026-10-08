<?php

declare(strict_types=1);

if (is_logged_in() && isset($_GET['boletin_tetrazolio_pdf'])) {
    $boletinId = filter_var($_GET['boletin_tetrazolio_pdf'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($boletinId === false) {
        http_response_code(400);
        exit('ID de boletín inválido.');
    }

    $pdfResult = build_boletin_tetrazolio_pdf($boletinId);
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

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_boletin_tetrazolio'])) {
    $boletinTetrazolioForm['id_cliente'] = trim((string) ($_POST['id_cliente'] ?? ''));
    $boletinTetrazolioForm['id_especie'] = trim((string) ($_POST['id_especie'] ?? ''));
    $boletinTetrazolioForm['muestra_nro'] = trim((string) ($_POST['muestra_nro'] ?? ''));
    $boletinTetrazolioForm['fecha_inicio'] = trim((string) ($_POST['fecha_inicio'] ?? ''));
    $boletinTetrazolioForm['fecha_finalizacion'] = trim((string) ($_POST['fecha_finalizacion'] ?? ''));
    $boletinTetrazolioForm['analista'] = trim((string) ($_POST['analista'] ?? ''));
    $boletinTetrazolioForm['tiempo_humedecimiento'] = trim((string) ($_POST['tiempo_humedecimiento'] ?? ''));
    $boletinTetrazolioForm['tiempo_tincion'] = trim((string) ($_POST['tiempo_tincion'] ?? ''));
    $boletinTetrazolioForm['temperatura_tincion'] = trim((string) ($_POST['temperatura_tincion'] ?? ''));
    $boletinTetrazolioForm['concentracion_tz'] = trim((string) ($_POST['concentracion_tz'] ?? ''));
    $boletinTetrazolioForm['observaciones'] = trim((string) ($_POST['observaciones'] ?? ''));

    $postedRepeticiones = (array) ($_POST['repeticiones'] ?? []);
    $repeticiones = [];
    for ($numero = 1; $numero <= 4; $numero++) {
        $posted = (array) ($postedRepeticiones[$numero] ?? []);
        $viablesRaw = trim((string) ($posted['viables'] ?? ''));
        $noViablesRaw = trim((string) ($posted['no_viables'] ?? ''));
        $boletinTetrazolioForm['repeticiones'][$numero] = [
            'viables' => $viablesRaw,
            'no_viables' => $noViablesRaw,
        ];

        $viables = filter_var($viablesRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2147483647]]);
        $noViables = filter_var($noViablesRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2147483647]]);
        if ($viables === false || $noViables === false) {
            $errors[] = 'Completa las semillas viables y no viables con cantidades enteras no negativas en las cuatro repeticiones.';
            continue;
        }
        if ($viables + $noViables < 1) {
            $errors[] = 'Cada repetición debe contener al menos una semilla.';
            continue;
        }
        $repeticiones[] = ['viables' => $viables, 'no_viables' => $noViables];
    }

    $clienteId = filter_var($boletinTetrazolioForm['id_cliente'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $especieId = filter_var($boletinTetrazolioForm['id_especie'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($boletinTetrazolioForm['muestra_nro'] === '') {
        $errors[] = 'El número de muestra es obligatorio.';
    }
    if (strlen($boletinTetrazolioForm['muestra_nro']) > 100 || strlen($boletinTetrazolioForm['analista']) > 150) {
        $errors[] = 'El número de muestra admite 100 caracteres y el analista 150.';
    }
    if ($clienteId === false) {
        $errors[] = 'Debes seleccionar un cliente válido.';
    } else {
        $clienteStatement = db()->prepare('SELECT id_cliente FROM clientes WHERE id_cliente = :id AND id_empresa = :id_empresa LIMIT 1');
        $clienteStatement->execute([':id' => $clienteId, ':id_empresa' => current_empresa_id()]);
        if ($clienteStatement->fetch() === false) {
            $errors[] = 'El cliente seleccionado no existe.';
        }
    }
    if ($especieId === false) {
        $errors[] = 'Debes seleccionar una especie válida.';
    } else {
        $especieStatement = db()->prepare('SELECT cientifico_especie FROM especies WHERE id_especie = :id AND id_empresa = :id_empresa LIMIT 1');
        $especieStatement->execute([':id' => $especieId, ':id_empresa' => current_empresa_id()]);
        $especieRow = $especieStatement->fetch();
        if ($especieRow === false) {
            $errors[] = 'La especie seleccionada no existe.';
        } elseif (trim((string) ($especieRow['cientifico_especie'] ?? '')) === '') {
            $errors[] = 'La especie seleccionada no tiene nombre científico en el catálogo.';
        }
    }

    foreach (['fecha_inicio', 'fecha_finalizacion'] as $campoFecha) {
        $fecha = $boletinTetrazolioForm[$campoFecha];
        if ($fecha !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !checkdate((int) substr($fecha, 5, 2), (int) substr($fecha, 8, 2), (int) substr($fecha, 0, 4)))) {
            $errors[] = 'Las fechas ingresadas no son válidas.';
            break;
        }
    }
    if ($boletinTetrazolioForm['fecha_inicio'] !== '' && $boletinTetrazolioForm['fecha_finalizacion'] !== '' && $boletinTetrazolioForm['fecha_finalizacion'] < $boletinTetrazolioForm['fecha_inicio']) {
        $errors[] = 'La fecha de finalización no puede ser anterior a la fecha de inicio.';
    }

    foreach (['tiempo_humedecimiento', 'tiempo_tincion', 'temperatura_tincion', 'concentracion_tz'] as $campoMetodo) {
        if (strlen($boletinTetrazolioForm[$campoMetodo]) > 100) {
            $errors[] = 'Los datos de metodología admiten hasta 100 caracteres cada uno.';
            break;
        }
    }

    if (count($repeticiones) === 4 && !$errors) {
        $calculos = calcular_boletin_tetrazolio($repeticiones);
        try {
            db()->beginTransaction();
            $insert = db()->prepare(
                'INSERT INTO boletines_tetrazolio (id_empresa, id_cliente, id_usuario, id_especie, muestra_nro, fecha_inicio, fecha_finalizacion, analista, tiempo_humedecimiento, tiempo_tincion, temperatura_tincion, concentracion_tz, promedio_viables, promedio_no_viables, resultado_porcentaje, resultado_no_viables_porcentaje, observaciones)
                 VALUES (:id_empresa, :id_cliente, :id_usuario, :id_especie, :muestra_nro, :fecha_inicio, :fecha_finalizacion, :analista, :tiempo_humedecimiento, :tiempo_tincion, :temperatura_tincion, :concentracion_tz, :promedio_viables, :promedio_no_viables, :resultado_porcentaje, :resultado_no_viables_porcentaje, :observaciones)'
            );
            $insert->execute([
                ':id_empresa' => current_empresa_id(),
                ':id_cliente' => $clienteId,
                ':id_usuario' => $_SESSION['user']['id'] ?? null,
                ':id_especie' => $especieId,
                ':muestra_nro' => $boletinTetrazolioForm['muestra_nro'],
                ':fecha_inicio' => $boletinTetrazolioForm['fecha_inicio'] !== '' ? $boletinTetrazolioForm['fecha_inicio'] : null,
                ':fecha_finalizacion' => $boletinTetrazolioForm['fecha_finalizacion'] !== '' ? $boletinTetrazolioForm['fecha_finalizacion'] : null,
                ':analista' => $boletinTetrazolioForm['analista'] !== '' ? $boletinTetrazolioForm['analista'] : null,
                ':tiempo_humedecimiento' => $boletinTetrazolioForm['tiempo_humedecimiento'] !== '' ? $boletinTetrazolioForm['tiempo_humedecimiento'] : null,
                ':tiempo_tincion' => $boletinTetrazolioForm['tiempo_tincion'] !== '' ? $boletinTetrazolioForm['tiempo_tincion'] : null,
                ':temperatura_tincion' => $boletinTetrazolioForm['temperatura_tincion'] !== '' ? $boletinTetrazolioForm['temperatura_tincion'] : null,
                ':concentracion_tz' => $boletinTetrazolioForm['concentracion_tz'] !== '' ? $boletinTetrazolioForm['concentracion_tz'] : null,
                ':promedio_viables' => $calculos['promedio_viables'],
                ':promedio_no_viables' => $calculos['promedio_no_viables'],
                ':resultado_porcentaje' => $calculos['resultado_viables_porcentaje'],
                ':resultado_no_viables_porcentaje' => $calculos['resultado_no_viables_porcentaje'],
                ':observaciones' => $boletinTetrazolioForm['observaciones'] !== '' ? $boletinTetrazolioForm['observaciones'] : null,
            ]);
            $boletinId = (int) db()->lastInsertId();

            $insertRepeticion = db()->prepare(
                'INSERT INTO boletin_tetrazolio_repeticiones (id_boletin_tetrazolio, nro_repeticion, semillas_viables, semillas_no_viables)
                 VALUES (:id_boletin_tetrazolio, :nro_repeticion, :semillas_viables, :semillas_no_viables)'
            );
            foreach ($repeticiones as $index => $repeticion) {
                $insertRepeticion->execute([
                    ':id_boletin_tetrazolio' => $boletinId,
                    ':nro_repeticion' => $index + 1,
                    ':semillas_viables' => $repeticion['viables'],
                    ':semillas_no_viables' => $repeticion['no_viables'],
                ]);
            }
            db()->commit();

            $_SESSION['success_message'] = 'Boletín de Tetrazolio guardado correctamente.';
            header('Location: /index.php?page=boletin_tetrazolio');
            exit;
        } catch (Throwable $throwable) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            $errors[] = 'No fue posible guardar el boletín de Tetrazolio.';
        }
    }
}
