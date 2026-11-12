<?php

declare(strict_types=1);

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_especie']) || isset($_POST['update_especie']))) {
    foreach (['primer_conteo' => 'primer conteo', 'conteo_final' => 'conteo final'] as $campo => $etiqueta) {
        $speciesForm[$campo] = trim((string) ($_POST[$campo] ?? ''));
        if ($speciesForm[$campo] !== '' && filter_var($speciesForm[$campo], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]) === false) {
            $errors[] = 'El ' . $etiqueta . ' debe ser un número entero entre 1 y 65535 días.';
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_especie'])) {
    $speciesForm['nombre_especie'] = trim((string) ($_POST['nombre_especie'] ?? ''));
    $speciesForm['cientifico_especie'] = trim((string) ($_POST['cientifico_especie'] ?? ''));

    if ($speciesForm['nombre_especie'] === '') {
        $errors[] = 'El nombre de la especie es obligatorio.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'INSERT INTO especies (id_empresa, nombre_especie, cientifico_especie, primer_conteo, conteo_final) VALUES (:id_empresa, :nombre_especie, :cientifico_especie, :primer_conteo, :conteo_final)'
            );
            $statement->execute([
                ':id_empresa' => current_empresa_id(),
                ':nombre_especie' => $speciesForm['nombre_especie'],
                ':cientifico_especie' => $speciesForm['cientifico_especie'] !== '' ? $speciesForm['cientifico_especie'] : null,
                ':primer_conteo' => $speciesForm['primer_conteo'] !== '' ? (int) $speciesForm['primer_conteo'] : null,
                ':conteo_final' => $speciesForm['conteo_final'] !== '' ? (int) $speciesForm['conteo_final'] : null,
            ]);

            $successMessage = 'Especie dada de alta correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $speciesForm = [
                'nombre_especie' => '',
                'cientifico_especie' => '',
                'primer_conteo' => '',
                'conteo_final' => '',
            ];

            if (($page ?? 'dashboard') !== 'especies') {
                header('Location: /index.php?page=especies');
                exit;
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible guardar la especie.';
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_especie'])) {
    $speciesEditId = filter_var($_POST['especie_edit_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $speciesForm['nombre_especie'] = trim((string) ($_POST['nombre_especie'] ?? ''));
    $speciesForm['cientifico_especie'] = trim((string) ($_POST['cientifico_especie'] ?? ''));

    if ($speciesEditId === false) {
        $errors[] = 'La especie a editar no es válida.';
    }

    if ($speciesForm['nombre_especie'] === '') {
        $errors[] = 'El nombre de la especie es obligatorio.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'UPDATE especies SET nombre_especie = :nombre_especie, cientifico_especie = :cientifico_especie, primer_conteo = :primer_conteo, conteo_final = :conteo_final WHERE id_especie = :id_especie AND id_empresa = :id_empresa'
            );
            $statement->execute([
                ':nombre_especie' => $speciesForm['nombre_especie'],
                ':cientifico_especie' => $speciesForm['cientifico_especie'] !== '' ? $speciesForm['cientifico_especie'] : null,
                ':primer_conteo' => $speciesForm['primer_conteo'] !== '' ? (int) $speciesForm['primer_conteo'] : null,
                ':conteo_final' => $speciesForm['conteo_final'] !== '' ? (int) $speciesForm['conteo_final'] : null,
                ':id_especie' => $speciesEditId,
                ':id_empresa' => current_empresa_id(),
            ]);

            $successMessage = 'Especie actualizada correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $speciesEditId = null;
            $speciesForm = ['nombre_especie' => '', 'cientifico_especie' => '', 'primer_conteo' => '', 'conteo_final' => ''];

            if (($page ?? 'dashboard') !== 'especies') {
                header('Location: /index.php?page=especies');
                exit;
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible actualizar la especie.';
        }
    }
}
