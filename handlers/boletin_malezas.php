<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/boletin_edicion.php';

if (is_logged_in() && isset($_GET['boletin_malezas_pdf'])) {
    $boletinId = filter_var($_GET['boletin_malezas_pdf'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($boletinId === false) {
        http_response_code(400);
        exit('ID de boletín inválido.');
    }

    $pdfResult = build_boletin_malezas_pdf($boletinId);
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

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_boletin_malezas'])) {
    $ingresoAsociadoId = null;
    if (isset($_POST['ingreso_id']) && $_POST['ingreso_id'] !== '') {
        $ingresoAsociadoId = filter_var($_POST['ingreso_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($ingresoAsociadoId === false) {
            $errors[] = 'El ingreso asociado no es válido.';
        }
    }
    $boletinMalezasEditId = null;
    try {
        $boletinMalezasEditId = boletin_edicion_id();
        if ($boletinMalezasEditId !== null) {
            boletin_edicion_cabecera('malezas', $boletinMalezasEditId);
        }
    } catch (Throwable $throwable) {
        $errors[] = 'El boletín a editar no es válido o no pertenece a la empresa activa.';
    }
    foreach (
        [
            'id_cliente',
            'id_especie',
            'analisis_nro',
            'muestra_nro',
            'lote',
            'tipo_analisis',
            'peso_analisis',
            'fecha_analisis',
            'analista',
            'observaciones',
        ] as $field
    ) {
        $boletinMalezasForm[$field] = trim((string) ($_POST[$field] ?? ''));
    }
    $boletinMalezasForm['otras_determinaciones_texto'] = trim((string) ($_POST['otras_determinaciones_texto'] ?? ''));
    $boletinMalezasForm['id_otras_determinaciones'] = [];
    $postedDeterminations = $_POST['id_otras_determinaciones'] ?? [];
    foreach (is_array($postedDeterminations) ? $postedDeterminations : [] as $postedId) {
        $determinationId = filter_var($postedId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($determinationId !== false) {
            $boletinMalezasForm['id_otras_determinaciones'][] = (int) $determinationId;
        }
    }
    $boletinMalezasForm['id_otras_determinaciones'] = array_values(array_unique($boletinMalezasForm['id_otras_determinaciones']));

    $postedSpecies = (array) ($_POST['especies_halladas'] ?? []);
    $boletinMalezasForm['especies_halladas'] = [];
    $especiesHalladas = [];
    foreach ($postedSpecies as $postedRow) {
        $postedRow = is_array($postedRow) ? $postedRow : [];
        $latin = trim((string) ($postedRow['especie_latin'] ?? ''));
        $numberRaw = trim((string) ($postedRow['numero_semillas'] ?? ''));
        if ($latin === '' && $numberRaw === '') {
            continue;
        }

        $boletinMalezasForm['especies_halladas'][] = [
            'especie_latin' => $latin,
            'numero_semillas' => $numberRaw,
        ];
        $number = filter_var($numberRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 4294967295]]);
        if ($latin === '' || $number === false) {
            $errors[] = 'Completa la especie y un número entero no negativo en cada fila de especies halladas.';
            continue;
        }
        if (strlen($latin) > 255) {
            $errors[] = 'El nombre científico admite hasta 255 caracteres.';
            continue;
        }
        $especiesHalladas[] = ['especie_latin' => $latin, 'numero_semillas' => $number];
    }
    if ($boletinMalezasForm['especies_halladas'] === []) {
        $boletinMalezasForm['especies_halladas'] = [['especie_latin' => '', 'numero_semillas' => '']];
    }

    $clienteId = filter_var($boletinMalezasForm['id_cliente'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $especieId = filter_var($boletinMalezasForm['id_especie'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $pesoAnalisis = $boletinMalezasForm['peso_analisis'] === '' ? null : filter_var($boletinMalezasForm['peso_analisis'], FILTER_VALIDATE_FLOAT);
    $tiposAnalisis = ['completo', 'limitado', 'reducido', 'reducido_limitado'];

    if ($clienteId === false) {
        $errors[] = 'Debes seleccionar un cliente válido.';
    } else {
        $clienteStatement = db()->prepare('SELECT id_cliente FROM clientes WHERE id_cliente = :id AND id_empresa = :id_empresa LIMIT 1');
        $clienteStatement->execute([':id' => $clienteId, ':id_empresa' => current_empresa_id()]);
        if ($clienteStatement->fetch() === false) {
            $errors[] = 'El cliente seleccionado no existe.';
        }
    }
    if ($boletinMalezasForm['analisis_nro'] === '') {
        $errors[] = 'El número de análisis es obligatorio.';
    }
    if ($boletinMalezasForm['id_otras_determinaciones'] !== []) {
        $determinationPlaceholders = [];
        $determinationParams = [':id_empresa' => current_empresa_id()];
        foreach ($boletinMalezasForm['id_otras_determinaciones'] as $index => $determinationId) {
            $placeholder = ':determination_' . $index;
            $determinationPlaceholders[] = $placeholder;
            $determinationParams[$placeholder] = $determinationId;
        }
        $determinationStatement = db()->prepare(
            'SELECT COUNT(*) FROM otras_determinaciones WHERE id_empresa = :id_empresa AND id_determinacion IN (' . implode(', ', $determinationPlaceholders) . ')'
        );
        $determinationStatement->execute($determinationParams);
        if ((int) $determinationStatement->fetchColumn() !== count($boletinMalezasForm['id_otras_determinaciones'])) {
            $errors[] = 'Una o más determinaciones seleccionadas no existen.';
        }
    }
    if (strlen($boletinMalezasForm['otras_determinaciones_texto']) > 255) {
        $errors[] = 'La otra determinación admite hasta 255 caracteres.';
    }
    if ($especieId === false) {
        $errors[] = 'Debes seleccionar una especie válida.';
    } else {
        $especieStatement = db()->prepare('SELECT nombre_especie, cientifico_especie FROM especies WHERE id_especie = :id AND id_empresa = :id_empresa LIMIT 1');
        $especieStatement->execute([':id' => $especieId, ':id_empresa' => current_empresa_id()]);
        $especieRow = $especieStatement->fetch();
        if ($especieRow === false) {
            $errors[] = 'La especie seleccionada no existe.';
        } else {
            $boletinMalezasForm['especie_remitente'] = (string) $especieRow['nombre_especie'];
            $boletinMalezasForm['especie_latin'] = trim((string) ($especieRow['cientifico_especie'] ?? ''));
            if ($boletinMalezasForm['especie_latin'] === '') {
                $errors[] = 'La especie seleccionada no tiene nombre científico en el catálogo.';
            }
        }
    }
    if (strlen($boletinMalezasForm['analisis_nro']) > 100 || strlen($boletinMalezasForm['muestra_nro']) > 100 || strlen($boletinMalezasForm['lote']) > 100 || strlen($boletinMalezasForm['analista']) > 150 || strlen($boletinMalezasForm['especie_remitente']) > 255 || strlen($boletinMalezasForm['especie_latin']) > 255) {
        $errors[] = 'Uno o más campos exceden la longitud permitida.';
    }
    if (!in_array($boletinMalezasForm['tipo_analisis'], $tiposAnalisis, true)) {
        $errors[] = 'Selecciona un tipo de análisis válido.';
    }
    if ($boletinMalezasForm['peso_analisis'] !== '' && ($pesoAnalisis === false || !is_finite((float) $pesoAnalisis) || (float) $pesoAnalisis < 0)) {
        $errors[] = 'El peso analizado debe ser un número no negativo.';
    }
    if ($boletinMalezasForm['fecha_analisis'] !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $boletinMalezasForm['fecha_analisis']) || !checkdate((int) substr($boletinMalezasForm['fecha_analisis'], 5, 2), (int) substr($boletinMalezasForm['fecha_analisis'], 8, 2), (int) substr($boletinMalezasForm['fecha_analisis'], 0, 4)))) {
        $errors[] = 'La fecha de análisis no es válida.';
    }

    if (!$errors) {
        try {
            db()->beginTransaction();
            $insert = db()->prepare(
                'INSERT INTO boletines_malezas (id_empresa, id_cliente, id_usuario, id_especie, analisis_nro, muestra_nro, especie_remitente, especie_latin, lote, tipo_analisis, peso_analisis, fecha_analisis, analista, observaciones, otras_determinaciones_texto)
                 VALUES (:id_empresa, :id_cliente, :id_usuario, :id_especie, :analisis_nro, :muestra_nro, :especie_remitente, :especie_latin, :lote, :tipo_analisis, :peso_analisis, :fecha_analisis, :analista, :observaciones, :otras_determinaciones_texto)'
            );
            $parametrosCabecera = [
                ':id_empresa' => current_empresa_id(),
                ':id_cliente' => $clienteId,
                ':id_usuario' => $_SESSION['user']['id'] ?? null,
                ':id_especie' => $especieId,
                ':analisis_nro' => $boletinMalezasForm['analisis_nro'],
                ':muestra_nro' => $boletinMalezasForm['muestra_nro'] !== '' ? $boletinMalezasForm['muestra_nro'] : null,
                ':especie_remitente' => $boletinMalezasForm['especie_remitente'],
                ':especie_latin' => $boletinMalezasForm['especie_latin'],
                ':lote' => $boletinMalezasForm['lote'] !== '' ? $boletinMalezasForm['lote'] : null,
                ':tipo_analisis' => $boletinMalezasForm['tipo_analisis'],
                ':peso_analisis' => $pesoAnalisis !== null && $pesoAnalisis !== false ? (float) $pesoAnalisis : null,
                ':fecha_analisis' => $boletinMalezasForm['fecha_analisis'] !== '' ? $boletinMalezasForm['fecha_analisis'] : null,
                ':analista' => $boletinMalezasForm['analista'] !== '' ? $boletinMalezasForm['analista'] : null,
                ':observaciones' => $boletinMalezasForm['observaciones'] !== '' ? $boletinMalezasForm['observaciones'] : null,
                ':otras_determinaciones_texto' => $boletinMalezasForm['otras_determinaciones_texto'] !== '' ? $boletinMalezasForm['otras_determinaciones_texto'] : null,
            ];
            if ($boletinMalezasEditId !== null) {
                boletin_edicion_actualizar('malezas', $boletinMalezasEditId, $parametrosCabecera);
                $boletinId = $boletinMalezasEditId;
            } else {
                $insert->execute($parametrosCabecera);
                $boletinId = (int) db()->lastInsertId();
            }

            if ($boletinMalezasForm['id_otras_determinaciones'] !== []) {
                $insertDetermination = db()->prepare(
                    'INSERT INTO boletin_malezas_determinaciones (id_boletin_malezas, id_determinacion)
                     VALUES (:id_boletin_malezas, :id_determinacion)'
                );
                foreach ($boletinMalezasForm['id_otras_determinaciones'] as $determinationId) {
                    $insertDetermination->execute([
                        ':id_boletin_malezas' => $boletinId,
                        ':id_determinacion' => $determinationId,
                    ]);
                }
            }

            if ($especiesHalladas !== []) {
                $insertSpecies = db()->prepare(
                    'INSERT INTO boletin_malezas_especies (id_boletin_malezas, especie_latin, numero_semillas)
                     VALUES (:id_boletin_malezas, :especie_latin, :numero_semillas)'
                );
                foreach ($especiesHalladas as $row) {
                    $insertSpecies->execute([
                        ':id_boletin_malezas' => $boletinId,
                        ':especie_latin' => $row['especie_latin'],
                        ':numero_semillas' => $row['numero_semillas'],
                    ]);
                }
            }
            if ($ingresoAsociadoId !== null && $ingresoAsociadoId !== false) {
                libro_ingreso_vincular_boletin((int) $ingresoAsociadoId, 'boletin_malezas', $boletinId);
            }
            db()->commit();

            $_SESSION['success_message'] = 'Análisis de malezas guardado correctamente.';
            header('Location: /index.php?page=boletin_malezas');
            exit;
        } catch (Throwable $throwable) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            $errors[] = 'No fue posible guardar el análisis de malezas.';
        }
    }
}
