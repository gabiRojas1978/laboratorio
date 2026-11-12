<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/boletin_edicion.php';

if (is_logged_in() && isset($_GET['boletin_pureza_pdf'])) {
    $boletinId = filter_var($_GET['boletin_pureza_pdf'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($boletinId === false) {
        http_response_code(400);
        exit('ID de boletín inválido.');
    }

    $pdfResult = build_boletin_pureza_pdf($boletinId);
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

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_boletin_pureza'])) {
    $ingresoAsociadoId = null;
    if (isset($_POST['ingreso_id']) && $_POST['ingreso_id'] !== '') {
        $ingresoAsociadoId = filter_var($_POST['ingreso_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($ingresoAsociadoId === false) {
            $errors[] = 'El ingreso asociado no es válido.';
        }
    }
    $boletinPurezaEditId = null;
    try {
        $boletinPurezaEditId = boletin_edicion_id();
        if ($boletinPurezaEditId !== null) {
            boletin_edicion_cabecera('pureza', $boletinPurezaEditId);
        }
    } catch (Throwable $throwable) {
        $errors[] = 'El boletín a editar no es válido o no pertenece a la empresa activa.';
    }
    $boletinPurezaForm['id_cliente'] = trim((string) ($_POST['id_cliente'] ?? ''));
    $boletinPurezaForm['id_especie'] = trim((string) ($_POST['id_especie'] ?? ''));
    $boletinPurezaForm['analisis_nro'] = trim((string) ($_POST['analisis_nro'] ?? ''));
    $boletinPurezaForm['fecha_analisis'] = trim((string) ($_POST['fecha_analisis'] ?? ''));
    $boletinPurezaForm['analista'] = trim((string) ($_POST['analista'] ?? ''));
    $boletinPurezaForm['peso_inicial'] = trim((string) ($_POST['peso_inicial'] ?? ''));
    $boletinPurezaForm['peso_semilla_pura'] = trim((string) ($_POST['peso_semilla_pura'] ?? ''));
    $boletinPurezaForm['observaciones'] = trim((string) ($_POST['observaciones'] ?? ''));
    $boletinPurezaForm['id_otras_determinaciones'] = array_values(array_unique(array_filter(
        array_map('intval', (array) ($_POST['id_otras_determinaciones'] ?? [])),
        static fn($id) => $id > 0
    )));
    $boletinPurezaForm['otras_determinaciones_texto'] = trim((string) ($_POST['otras_determinaciones_texto'] ?? ''));

    $normalizarComponentes = static function ($postedRows, string $label, array &$errors): array {
        if (!is_array($postedRows)) {
            $postedRows = [];
        }
        $rows = [];
        foreach ($postedRows as $postedRow) {
            $postedRow = is_array($postedRow) ? $postedRow : [];
            $name = trim((string) ($postedRow['nombre'] ?? ''));
            $weightRaw = trim((string) ($postedRow['peso'] ?? ''));
            if ($name === '' && $weightRaw === '') {
                continue;
            }
            if ($name === '' || $weightRaw === '' || !is_numeric($weightRaw) || !is_finite((float) $weightRaw) || (float) $weightRaw < 0) {
                $errors[] = 'Completa el nombre y un peso válido no negativo para cada renglón de ' . $label . '.';
                continue;
            }
            if (strlen($name) > 255) {
                $errors[] = 'Los nombres de componentes admiten hasta 255 caracteres.';
                continue;
            }
            $rows[] = ['nombre' => $name, 'peso' => (float) $weightRaw];
        }
        return $rows;
    };

    $boletinPurezaForm['componentes_materia_inerte'] = $normalizarComponentes(
        $_POST['componentes']['materia_inerte'] ?? [],
        'materia inerte',
        $errors
    );
    $boletinPurezaForm['componentes_otras_semillas'] = $normalizarComponentes(
        $_POST['componentes']['otras_semillas'] ?? [],
        'otras semillas',
        $errors
    );

    $clienteId = filter_var($boletinPurezaForm['id_cliente'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $especieId = filter_var($boletinPurezaForm['id_especie'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $pesoInicial = $boletinPurezaForm['peso_inicial'] !== '' && is_numeric($boletinPurezaForm['peso_inicial'])
        ? (float) $boletinPurezaForm['peso_inicial']
        : 0.0;
    $pesoSemillaPura = $boletinPurezaForm['peso_semilla_pura'] !== '' && is_numeric($boletinPurezaForm['peso_semilla_pura'])
        ? (float) $boletinPurezaForm['peso_semilla_pura']
        : 0.0;

    if ($boletinPurezaForm['analisis_nro'] === '') {
        $errors[] = 'El número de análisis es obligatorio.';
    }
    if (strlen($boletinPurezaForm['analisis_nro']) > 100 || strlen($boletinPurezaForm['analista']) > 150) {
        $errors[] = 'El número de análisis admite 100 caracteres y el analista 150.';
    }
    if (strlen($boletinPurezaForm['otras_determinaciones_texto']) > 255) {
        $errors[] = 'La determinación libre admite hasta 255 caracteres.';
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
    if ($boletinPurezaForm['id_otras_determinaciones'] !== []) {
        $determinacionStatement = db()->prepare(
            'SELECT id_determinacion FROM otras_determinaciones WHERE id_determinacion = :id AND id_empresa = :id_empresa LIMIT 1'
        );
        foreach ($boletinPurezaForm['id_otras_determinaciones'] as $determinacionId) {
            $determinacionStatement->execute([
                ':id' => $determinacionId,
                ':id_empresa' => current_empresa_id(),
            ]);
            if ($determinacionStatement->fetch() === false) {
                $errors[] = 'Una de las otras determinaciones seleccionadas no existe.';
                break;
            }
        }
    }
    if ($pesoInicial <= 0 || !is_finite($pesoInicial)) {
        $errors[] = 'El peso inicial debe ser un número mayor que cero.';
    }
    if ($boletinPurezaForm['peso_semilla_pura'] === '' || !is_numeric($boletinPurezaForm['peso_semilla_pura']) || !is_finite($pesoSemillaPura) || $pesoSemillaPura < 0) {
        $errors[] = 'El peso de semilla pura debe ser un número no negativo.';
    }
    if ($boletinPurezaForm['fecha_analisis'] !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $boletinPurezaForm['fecha_analisis']) || !checkdate((int) substr($boletinPurezaForm['fecha_analisis'], 5, 2), (int) substr($boletinPurezaForm['fecha_analisis'], 8, 2), (int) substr($boletinPurezaForm['fecha_analisis'], 0, 4)))) {
        $errors[] = 'La fecha de análisis no es válida.';
    }

    if (!$errors) {
        $calculos = calcular_boletin_pureza(
            $pesoInicial,
            $pesoSemillaPura,
            $boletinPurezaForm['componentes_materia_inerte'],
            $boletinPurezaForm['componentes_otras_semillas']
        );
        try {
            db()->beginTransaction();
            $insert = db()->prepare(
                'INSERT INTO boletines_pureza (id_empresa, id_cliente, id_usuario, id_especie, analisis_nro, fecha_analisis, analista, peso_inicial, peso_semilla_pura, peso_materia_inerte, peso_otras_semillas, peso_total, diferencia_peso, porcentaje_semilla_pura, porcentaje_materia_inerte, porcentaje_otras_semillas, observaciones, otras_determinaciones_texto)
                 VALUES (:id_empresa, :id_cliente, :id_usuario, :id_especie, :analisis_nro, :fecha_analisis, :analista, :peso_inicial, :peso_semilla_pura, :peso_materia_inerte, :peso_otras_semillas, :peso_total, :diferencia_peso, :porcentaje_semilla_pura, :porcentaje_materia_inerte, :porcentaje_otras_semillas, :observaciones, :otras_determinaciones_texto)'
            );
            $parametrosCabecera = [
                ':id_empresa' => current_empresa_id(),
                ':id_cliente' => $clienteId,
                ':id_usuario' => $_SESSION['user']['id'] ?? null,
                ':id_especie' => $especieId,
                ':analisis_nro' => $boletinPurezaForm['analisis_nro'],
                ':fecha_analisis' => $boletinPurezaForm['fecha_analisis'] !== '' ? $boletinPurezaForm['fecha_analisis'] : null,
                ':analista' => $boletinPurezaForm['analista'] !== '' ? $boletinPurezaForm['analista'] : null,
                ':peso_inicial' => round($calculos['peso_inicial'], 4),
                ':peso_semilla_pura' => round($calculos['peso_semilla_pura'], 4),
                ':peso_materia_inerte' => round($calculos['peso_materia_inerte'], 4),
                ':peso_otras_semillas' => round($calculos['peso_otras_semillas'], 4),
                ':peso_total' => round($calculos['peso_total'], 4),
                ':diferencia_peso' => round($calculos['diferencia_peso'], 4),
                ':porcentaje_semilla_pura' => round($calculos['porcentaje_semilla_pura'], 4),
                ':porcentaje_materia_inerte' => round($calculos['porcentaje_materia_inerte'], 4),
                ':porcentaje_otras_semillas' => round($calculos['porcentaje_otras_semillas'], 4),
                ':observaciones' => $boletinPurezaForm['observaciones'] !== '' ? $boletinPurezaForm['observaciones'] : null,
                ':otras_determinaciones_texto' => $boletinPurezaForm['otras_determinaciones_texto'] !== '' ? $boletinPurezaForm['otras_determinaciones_texto'] : null,
            ];
            if ($boletinPurezaEditId !== null) {
                boletin_edicion_actualizar('pureza', $boletinPurezaEditId, $parametrosCabecera);
                $boletinId = $boletinPurezaEditId;
            } else {
                $insert->execute($parametrosCabecera);
                $boletinId = (int) db()->lastInsertId();
            }

            $insertComponent = db()->prepare(
                'INSERT INTO boletin_pureza_componentes (id_boletin_pureza, tipo_componente, nombre_componente, peso_gramos)
                 VALUES (:id_boletin_pureza, :tipo_componente, :nombre_componente, :peso_gramos)'
            );
            foreach (
                [
                    'materia_inerte' => $boletinPurezaForm['componentes_materia_inerte'],
                    'otras_semillas' => $boletinPurezaForm['componentes_otras_semillas'],
                ] as $tipo => $componentes
            ) {
                foreach ($componentes as $componente) {
                    $insertComponent->execute([
                        ':id_boletin_pureza' => $boletinId,
                        ':tipo_componente' => $tipo,
                        ':nombre_componente' => $componente['nombre'],
                        ':peso_gramos' => round($componente['peso'], 4),
                    ]);
                }
            }
            if ($boletinPurezaForm['id_otras_determinaciones'] !== []) {
                $insertDeterminacion = db()->prepare(
                    'INSERT INTO boletin_pureza_determinaciones (id_boletin_pureza, id_determinacion)
                     VALUES (:id_boletin_pureza, :id_determinacion)'
                );
                foreach ($boletinPurezaForm['id_otras_determinaciones'] as $determinacionId) {
                    $insertDeterminacion->execute([
                        ':id_boletin_pureza' => $boletinId,
                        ':id_determinacion' => $determinacionId,
                    ]);
                }
            }
            if ($ingresoAsociadoId !== null && $ingresoAsociadoId !== false) {
                libro_ingreso_vincular_boletin((int) $ingresoAsociadoId, 'boletin_pureza', $boletinId);
            }
            db()->commit();

            $_SESSION['success_message'] = 'Boletín de Pureza física-botánica guardado correctamente.';
            header('Location: /index.php?page=boletin_pureza');
            exit;
        } catch (Throwable $throwable) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            $errors[] = 'No fue posible guardar el boletín de Pureza física-botánica.';
        }
    }
}
