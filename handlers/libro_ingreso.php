<?php

declare(strict_types=1);

$libroIngresoEditId = null;

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_libro_ingreso_fecha'])) {
    $ingresoId = filter_var($_POST['ingreso_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $fechaInicioAnalisis = trim((string) ($_POST['fecha_inicio_analisis'] ?? ''));

    if ($ingresoId === false) {
        $errors[] = 'El ingreso a editar no es válido.';
    }

    if (
        $fechaInicioAnalisis !== ''
        && (
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicioAnalisis)
            || !checkdate((int) substr($fechaInicioAnalisis, 5, 2), (int) substr($fechaInicioAnalisis, 8, 2), (int) substr($fechaInicioAnalisis, 0, 4))
        )
    ) {
        $errors[] = 'La fecha de inicio de análisis no es válida.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'UPDATE libro_ingreso
                 SET fecha_inicio_analisis = :fecha_inicio_analisis
                 WHERE id_libro_ingreso = :id AND id_empresa = :id_empresa'
            );
            $statement->execute([
                ':fecha_inicio_analisis' => $fechaInicioAnalisis !== '' ? $fechaInicioAnalisis : null,
                ':id' => $ingresoId,
                ':id_empresa' => current_empresa_id(),
            ]);

            if ($statement->rowCount() === 0) {
                $exists = db()->prepare(
                    'SELECT 1 FROM libro_ingreso WHERE id_libro_ingreso = :id AND id_empresa = :id_empresa LIMIT 1'
                );
                $exists->execute([':id' => $ingresoId, ':id_empresa' => current_empresa_id()]);
                if ($exists->fetchColumn() === false) {
                    $errors[] = 'El ingreso a editar no existe.';
                }
            }

            if (!$errors) {
                $_SESSION['success_message'] = 'Fecha de inicio de análisis actualizada correctamente.';
                header('Location: /index.php?page=libro_ingreso');
                exit;
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible actualizar la fecha de inicio de análisis.';
        }
    }
} elseif (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_libro_ingreso']) || isset($_POST['update_libro_ingreso']))) {
    $esActualizacion = isset($_POST['update_libro_ingreso']);
    if ($esActualizacion) {
        $libroIngresoEditId = filter_var($_POST['ingreso_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($libroIngresoEditId === false) {
            $errors[] = 'El ingreso a editar no es válido.';
        }
    }

    $libroIngresoForm['presupuesto'] = strtoupper(trim((string) ($_POST['presupuesto'] ?? 'NO')));
    $libroIngresoForm['facturado'] = strtoupper(trim((string) ($_POST['facturado'] ?? 'NO')));
    $libroIngresoForm['fecha_entrada'] = trim((string) ($_POST['fecha_entrada'] ?? ''));
    $libroIngresoForm['fecha_inicio_analisis'] = trim((string) ($_POST['fecha_inicio_analisis'] ?? ''));
    $libroIngresoForm['remitente'] = trim((string) ($_POST['remitente'] ?? ''));
    $libroIngresoForm['id_cliente'] = trim((string) ($_POST['id_cliente'] ?? ''));
    $libroIngresoForm['origen'] = trim((string) ($_POST['origen'] ?? ''));
    $libroIngresoForm['id_especie'] = trim((string) ($_POST['id_especie'] ?? ''));
    $libroIngresoForm['id_cultivar'] = trim((string) ($_POST['id_cultivar'] ?? ''));
    $libroIngresoForm['lote_observaciones'] = trim((string) ($_POST['lote_observaciones'] ?? ''));
    $libroIngresoForm['ubicacion_camara_archivo'] = trim((string) ($_POST['ubicacion_camara_archivo'] ?? ''));
    $libroIngresoForm['id_determinaciones'] = array_values(array_unique(array_filter(
        array_map('intval', (array) ($_POST['id_determinaciones'] ?? [])),
        static fn($id) => $id > 0
    )));

    foreach (['presupuesto', 'facturado'] as $campoOpcion) {
        if (!in_array($libroIngresoForm[$campoOpcion], ['SI', 'NO'], true)) {
            $errors[] = 'Las opciones de presupuesto y facturado deben ser SI o NO.';
            break;
        }
    }

    $fechaEntrada = $libroIngresoForm['fecha_entrada'];
    if ($fechaEntrada === '') {
        $errors[] = 'La fecha de entrada es obligatoria.';
    } elseif (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaEntrada)
        || !checkdate((int) substr($fechaEntrada, 5, 2), (int) substr($fechaEntrada, 8, 2), (int) substr($fechaEntrada, 0, 4))
    ) {
        $errors[] = 'La fecha de entrada no es válida.';
    }

    $fechaInicioAnalisis = $libroIngresoForm['fecha_inicio_analisis'];
    if (
        $fechaInicioAnalisis !== ''
        && (
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicioAnalisis)
            || !checkdate((int) substr($fechaInicioAnalisis, 5, 2), (int) substr($fechaInicioAnalisis, 8, 2), (int) substr($fechaInicioAnalisis, 0, 4))
        )
    ) {
        $errors[] = 'La fecha de inicio de análisis no es válida.';
    }

    $libroClienteId = filter_var($libroIngresoForm['id_cliente'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($libroClienteId === false) {
        $errors[] = 'Debes seleccionar un cliente válido.';
    } else {
        $statement = db()->prepare('SELECT id_cliente FROM clientes WHERE id_cliente = :id AND id_empresa = :id_empresa LIMIT 1');
        $statement->execute([':id' => $libroClienteId, ':id_empresa' => current_empresa_id()]);
        if ($statement->fetch() === false) {
            $errors[] = 'El cliente seleccionado no existe.';
        }
    }

    $libroEspecieId = filter_var($libroIngresoForm['id_especie'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($libroEspecieId === false) {
        $errors[] = 'Debes seleccionar una especie válida.';
    } else {
        $statement = db()->prepare('SELECT id_especie FROM especies WHERE id_especie = :id AND id_empresa = :id_empresa LIMIT 1');
        $statement->execute([':id' => $libroEspecieId, ':id_empresa' => current_empresa_id()]);
        if ($statement->fetch() === false) {
            $errors[] = 'La especie seleccionada no existe.';
        }
    }

    $libroCultivarRaw = $libroIngresoForm['id_cultivar'];
    $libroCultivarId = $libroCultivarRaw === ''
        ? null
        : filter_var($libroCultivarRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($libroCultivarRaw !== '' && $libroCultivarId === false) {
        $errors[] = 'Debes seleccionar un cultivar válido.';
    } elseif (is_int($libroCultivarId)) {
        $statement = db()->prepare('SELECT id_especie FROM cultivares WHERE id_cultivar = :id AND id_empresa = :id_empresa LIMIT 1');
        $statement->execute([':id' => $libroCultivarId, ':id_empresa' => current_empresa_id()]);
        $cultivarRow = $statement->fetch();
        if ($cultivarRow === false) {
            $errors[] = 'El cultivar seleccionado no existe.';
        } elseif ($libroEspecieId !== false && (int) $cultivarRow['id_especie'] !== $libroEspecieId) {
            $errors[] = 'El cultivar seleccionado no pertenece a la especie elegida.';
        }
    }

    $libroCamposTexto = [
        'remitente' => 'El remitente admite hasta 255 caracteres.',
        'origen' => 'El origen admite hasta 255 caracteres.',
        'lote_observaciones' => 'El N° de lote / observaciones admite hasta 255 caracteres.',
        'ubicacion_camara_archivo' => 'La ubicación cámara archivo admite hasta 255 caracteres.',
    ];
    foreach ($libroCamposTexto as $campoTexto => $mensaje) {
        if (mb_strlen($libroIngresoForm[$campoTexto]) > 255) {
            $errors[] = $mensaje;
        }
    }

    if ($libroIngresoForm['id_determinaciones'] !== []) {
        $placeholders = implode(',', array_fill(0, count($libroIngresoForm['id_determinaciones']), '?'));
        $statement = db()->prepare(
            'SELECT COUNT(*) FROM determinaciones_libro_ingreso
             WHERE id_empresa = ? AND id_determinacion_libro IN (' . $placeholders . ')'
        );
        $statement->execute(array_merge([current_empresa_id()], $libroIngresoForm['id_determinaciones']));
        if ((int) $statement->fetchColumn() !== count($libroIngresoForm['id_determinaciones'])) {
            $errors[] = 'Alguna de las determinaciones seleccionadas no existe.';
        }
    }

    if (!$errors) {
        $anioLibro = libro_ingreso_anio_actual();
        $intentos = 0;
        $guardado = false;

        // El correlativo se asigna al guardar; si otro usuario tomó el número, se reintenta.
        while (!$guardado && $intentos < 5) {
            $intentos++;
            $nroInase = $esActualizacion ? null : libro_ingreso_proximo_nro_inase(current_empresa_id(), $anioLibro);

            try {
                db()->beginTransaction();
                if ($esActualizacion) {
                    $existente = db()->prepare(
                        'SELECT nro_muestra FROM libro_ingreso
                         WHERE id_libro_ingreso = :id AND id_empresa = :id_empresa FOR UPDATE'
                    );
                    $existente->execute([':id' => $libroIngresoEditId, ':id_empresa' => current_empresa_id()]);
                    $muestraExistente = $existente->fetchColumn();
                    if ($muestraExistente === false) {
                        db()->rollBack();
                        $errors[] = 'El ingreso a editar no existe.';
                        break;
                    }
                }

                $parametros = [
                    ':id_empresa' => current_empresa_id(),
                    ':presupuesto' => $libroIngresoForm['presupuesto'],
                    ':facturado' => $libroIngresoForm['facturado'],
                    ':fecha_entrada' => $libroIngresoForm['fecha_entrada'],
                    ':fecha_inicio_analisis' => $libroIngresoForm['fecha_inicio_analisis'] !== '' ? $libroIngresoForm['fecha_inicio_analisis'] : null,
                    ':remitente' => $libroIngresoForm['remitente'] !== '' ? $libroIngresoForm['remitente'] : null,
                    ':id_cliente' => $libroClienteId,
                    ':origen' => $libroIngresoForm['origen'] !== '' ? $libroIngresoForm['origen'] : null,
                    ':id_especie' => $libroEspecieId,
                    ':id_cultivar' => $libroCultivarId,
                    ':lote_observaciones' => $libroIngresoForm['lote_observaciones'] !== '' ? $libroIngresoForm['lote_observaciones'] : null,
                    ':ubicacion_camara_archivo' => $libroIngresoForm['ubicacion_camara_archivo'] !== '' ? $libroIngresoForm['ubicacion_camara_archivo'] : null,
                ];

                if ($esActualizacion) {
                    $parametros[':id'] = $libroIngresoEditId;
                    $guardar = db()->prepare(
                        'UPDATE libro_ingreso SET presupuesto = :presupuesto, facturado = :facturado,
                         fecha_entrada = :fecha_entrada, fecha_inicio_analisis = :fecha_inicio_analisis,
                         remitente = :remitente, id_cliente = :id_cliente, origen = :origen,
                         id_especie = :id_especie, id_cultivar = :id_cultivar,
                         lote_observaciones = :lote_observaciones, ubicacion_camara_archivo = :ubicacion_camara_archivo
                         WHERE id_libro_ingreso = :id AND id_empresa = :id_empresa'
                    );
                    $guardar->execute($parametros);
                    $nuevoLibroIngresoId = $libroIngresoEditId;
                    $borrarDeterminaciones = db()->prepare('DELETE FROM libro_ingreso_determinaciones WHERE id_libro_ingreso = :id');
                    $borrarDeterminaciones->execute([':id' => $nuevoLibroIngresoId]);
                } else {
                    $parametros[':id_usuario'] = $_SESSION['user']['id'] ?? null;
                    $parametros[':anio'] = $anioLibro;
                    $parametros[':nro_inase'] = $nroInase;
                    $parametros[':nro_muestra'] = libro_ingreso_nro_muestra($anioLibro, $nroInase);
                    $guardar = db()->prepare(
                        'INSERT INTO libro_ingreso (id_empresa, id_usuario, anio_libro_ingreso, nro_inase, nro_muestra, presupuesto, facturado, fecha_entrada, fecha_inicio_analisis, remitente, id_cliente, origen, id_especie, id_cultivar, lote_observaciones, ubicacion_camara_archivo)
                         VALUES (:id_empresa, :id_usuario, :anio, :nro_inase, :nro_muestra, :presupuesto, :facturado, :fecha_entrada, :fecha_inicio_analisis, :remitente, :id_cliente, :origen, :id_especie, :id_cultivar, :lote_observaciones, :ubicacion_camara_archivo)'
                    );
                    $guardar->execute($parametros);
                    $nuevoLibroIngresoId = (int) db()->lastInsertId();
                }

                if ($libroIngresoForm['id_determinaciones'] !== []) {
                    $detStatement = db()->prepare(
                        'INSERT IGNORE INTO libro_ingreso_determinaciones (id_libro_ingreso, id_determinacion_libro)
                         SELECT :id_libro_ingreso, id_determinacion_libro FROM determinaciones_libro_ingreso
                         WHERE id_determinacion_libro = :id_determinacion AND id_empresa = :id_empresa'
                    );
                    foreach ($libroIngresoForm['id_determinaciones'] as $idDeterminacion) {
                        $detStatement->execute([
                            ':id_libro_ingreso' => $nuevoLibroIngresoId,
                            ':id_determinacion' => $idDeterminacion,
                            ':id_empresa' => current_empresa_id(),
                        ]);
                    }
                }

                db()->commit();
                $guardado = true;

                $_SESSION['success_message'] = $esActualizacion
                    ? 'Ingreso actualizado correctamente con el N° de muestra ' . $muestraExistente . '.'
                    : 'Ingreso registrado correctamente con el N° de muestra ' . libro_ingreso_nro_muestra($anioLibro, $nroInase) . '.';
                $libroIngresoForm = [
                    'presupuesto' => 'SI',
                    'facturado' => 'NO',
                    'fecha_entrada' => date('Y-m-d'),
                    'fecha_inicio_analisis' => '',
                    'remitente' => '',
                    'id_cliente' => '',
                    'origen' => '',
                    'id_especie' => '',
                    'id_cultivar' => '',
                    'lote_observaciones' => '',
                    'id_determinaciones' => [],
                    'ubicacion_camara_archivo' => '',
                ];

                header('Location: /index.php?page=libro_ingreso' . ($esActualizacion ? '' : '&presupuesto_id=' . $nuevoLibroIngresoId . '#presupuesto'));
                exit;
            } catch (Throwable $throwable) {
                if (db()->inTransaction()) {
                    db()->rollBack();
                }

                $esDuplicado = $throwable instanceof PDOException && (string) $throwable->getCode() === '23000';
                if ($esActualizacion || !$esDuplicado) {
                    $errors[] = ($esActualizacion ? 'No fue posible actualizar el ingreso: ' : 'No fue posible registrar el ingreso: ') . $throwable->getMessage();
                    break;
                }

                if ($intentos >= 5) {
                    $errors[] = 'No fue posible asignar un N° INASE disponible. Intentá nuevamente.';
                }
            }
        }
    }
}
