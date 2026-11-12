<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/boletin_edicion.php';

if (is_logged_in() && isset($_GET['boletin_peso_pdf'])) {
    $boletinId = filter_var($_GET['boletin_peso_pdf'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($boletinId === false) {
        http_response_code(400);
        exit('ID de boletín inválido.');
    }

    $pdfResult = build_boletin_peso_1000_pdf($boletinId);
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

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_boletin_peso'])) {
    $ingresoAsociadoId = null;
    if (isset($_POST['ingreso_id']) && $_POST['ingreso_id'] !== '') {
        $ingresoAsociadoId = filter_var($_POST['ingreso_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($ingresoAsociadoId === false) {
            $errors[] = 'El ingreso asociado no es válido.';
        }
    }
    $boletinPesoEditId = null;
    try {
        $boletinPesoEditId = boletin_edicion_id();
        if ($boletinPesoEditId !== null) {
            boletin_edicion_cabecera('peso_1000_semillas', $boletinPesoEditId);
        }
    } catch (Throwable $throwable) {
        $errors[] = 'El boletín a editar no es válido o no pertenece a la empresa activa.';
    }
    $boletinPesoForm['id_cliente'] = trim((string) ($_POST['id_cliente'] ?? ''));
    $boletinPesoForm['id_especie'] = trim((string) ($_POST['id_especie'] ?? ''));
    $boletinPesoForm['muestra_nro'] = trim((string) ($_POST['muestra_nro'] ?? ''));
    $boletinPesoForm['fecha_boletin'] = trim((string) ($_POST['fecha_boletin'] ?? ''));
    $boletinPesoForm['analista'] = trim((string) ($_POST['analista'] ?? ''));
    $boletinPesoForm['observaciones'] = trim((string) ($_POST['observaciones'] ?? ''));
    $pesosInput = (array) ($_POST['pesos'] ?? []);
    $boletinPesoForm['pesos'] = array_map(static fn($peso) => trim((string) $peso), $pesosInput);

    $pesos = [];
    foreach ($boletinPesoForm['pesos'] as $pesoRaw) {
        if ($pesoRaw === '') {
            continue;
        }
        if (!is_numeric($pesoRaw) || (float) $pesoRaw < 0) {
            $errors[] = 'Los pesos ingresados deben ser números positivos.';
            break;
        }
        $pesos[] = (float) $pesoRaw;
    }

    $especieId = filter_var($boletinPesoForm['id_especie'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $clienteId = filter_var($boletinPesoForm['id_cliente'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($boletinPesoForm['muestra_nro'] === '') {
        $errors[] = 'El número de muestra es obligatorio.';
    }

    if ($clienteId === false) {
        $errors[] = 'Debes seleccionar un cliente válido.';
    } else {
        $clienteOwnStatement = db()->prepare('SELECT id_cliente FROM clientes WHERE id_cliente = :id AND id_empresa = :id_empresa LIMIT 1');
        $clienteOwnStatement->execute([':id' => $clienteId, ':id_empresa' => current_empresa_id()]);
        if ($clienteOwnStatement->fetch() === false) {
            $errors[] = 'El cliente seleccionado no existe.';
        }
    }

    if ($especieId === false) {
        $errors[] = 'Debes seleccionar una especie válida.';
    } else {
        $especieOwnStatement = db()->prepare('SELECT cientifico_especie FROM especies WHERE id_especie = :id AND id_empresa = :id_empresa LIMIT 1');
        $especieOwnStatement->execute([':id' => $especieId, ':id_empresa' => current_empresa_id()]);
        $especieRow = $especieOwnStatement->fetch();
        if ($especieRow === false) {
            $errors[] = 'La especie seleccionada no existe.';
        } elseif (trim((string) ($especieRow['cientifico_especie'] ?? '')) === '') {
            $errors[] = 'La especie seleccionada no tiene nombre científico en el catálogo.';
        }
    }

    if (count($pesos) < 2) {
        $errors[] = 'Debes cargar al menos 2 repeticiones de peso para calcular la varianza.';
    }

    if (count($pesos) > 16) {
        $errors[] = 'No se admiten más de 16 repeticiones.';
    }

    if (!$errors) {
        $resultado = calcular_boletin_peso_1000_semillas($pesos);

        try {
            db()->beginTransaction();

            $statement = db()->prepare(
                'INSERT INTO boletines_peso_1000_semillas (id_empresa, id_usuario, id_cliente, id_especie, muestra_nro, fecha_boletin, analista, suma_pesos, promedio_peso, varianza, desvio_estandar, coeficiente_variacion, resultado_peso_mil, observaciones) VALUES (:id_empresa, :id_usuario, :id_cliente, :id_especie, :muestra_nro, :fecha_boletin, :analista, :suma_pesos, :promedio_peso, :varianza, :desvio_estandar, :coeficiente_variacion, :resultado_peso_mil, :observaciones)'
            );
            $parametrosCabecera = [
                ':id_empresa' => current_empresa_id(),
                ':id_usuario' => $_SESSION['user']['id'] ?? null,
                ':id_cliente' => $clienteId,
                ':id_especie' => $especieId,
                ':muestra_nro' => $boletinPesoForm['muestra_nro'],
                ':fecha_boletin' => $boletinPesoForm['fecha_boletin'] !== '' ? $boletinPesoForm['fecha_boletin'] : null,
                ':analista' => $boletinPesoForm['analista'] !== '' ? $boletinPesoForm['analista'] : null,
                ':suma_pesos' => $resultado['suma_pesos'],
                ':promedio_peso' => $resultado['promedio_peso'],
                ':varianza' => $resultado['varianza'],
                ':desvio_estandar' => $resultado['desvio_estandar'],
                ':coeficiente_variacion' => $resultado['coeficiente_variacion'],
                ':resultado_peso_mil' => $resultado['resultado_peso_mil'],
                ':observaciones' => $boletinPesoForm['observaciones'] !== '' ? $boletinPesoForm['observaciones'] : null,
            ];

            if ($boletinPesoEditId !== null) {
                boletin_edicion_actualizar('peso_1000_semillas', $boletinPesoEditId, $parametrosCabecera);
                $newBoletinPesoId = $boletinPesoEditId;
            } else {
                $statement->execute($parametrosCabecera);
                $newBoletinPesoId = (int) db()->lastInsertId();
            }

            $repeticionStatement = db()->prepare(
                'INSERT INTO boletin_peso_repeticiones (id_boletin_peso, nro_repeticion, peso_repeticion) VALUES (:id_boletin_peso, :nro_repeticion, :peso_repeticion)'
            );
            foreach ($pesos as $index => $peso) {
                $repeticionStatement->execute([
                    ':id_boletin_peso' => $newBoletinPesoId,
                    ':nro_repeticion' => $index + 1,
                    ':peso_repeticion' => $peso,
                ]);
            }

            if ($ingresoAsociadoId !== null && $ingresoAsociadoId !== false) {
                libro_ingreso_vincular_boletin((int) $ingresoAsociadoId, 'boletin_peso_1000_semillas', $newBoletinPesoId);
            }

            db()->commit();

            $successMessage = 'Boletín de peso de 1000 semillas guardado correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $boletinPesoForm = [
                'id_cliente' => '',
                'id_especie' => '',
                'muestra_nro' => '',
                'fecha_boletin' => '',
                'analista' => '',
                'observaciones' => '',
                'pesos' => array_fill(0, 8, ''),
            ];

            header('Location: /index.php?page=boletin_peso_1000_semillas');
            exit;
        } catch (Throwable $throwable) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            $errors[] = 'No fue posible guardar el boletín.';
        }
    }
}
