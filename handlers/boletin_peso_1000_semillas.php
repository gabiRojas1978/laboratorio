<?php

declare(strict_types=1);

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_boletin_peso'])) {
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

    $especieId = $boletinPesoForm['id_especie'] !== ''
        ? filter_var($boletinPesoForm['id_especie'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : null;
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

    if ($boletinPesoForm['id_especie'] !== '' && $especieId === false) {
        $errors[] = 'Debes seleccionar una especie válida.';
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
            $statement->execute([
                ':id_empresa' => current_empresa_id(),
                ':id_usuario' => $_SESSION['user']['id'] ?? null,
                ':id_cliente' => $clienteId,
                ':id_especie' => $especieId !== false ? $especieId : null,
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
            ]);

            $newBoletinPesoId = (int) db()->lastInsertId();

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
