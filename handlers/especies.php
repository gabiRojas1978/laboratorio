<?php

declare(strict_types=1);

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_especie'])) {
    $speciesForm['nombre_especie'] = trim((string) ($_POST['nombre_especie'] ?? ''));
    $speciesForm['cientifico_especie'] = trim((string) ($_POST['cientifico_especie'] ?? ''));

    if ($speciesForm['nombre_especie'] === '') {
        $errors[] = 'El nombre de la especie es obligatorio.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'INSERT INTO especies (id_empresa, nombre_especie, cientifico_especie) VALUES (:id_empresa, :nombre_especie, :cientifico_especie)'
            );
            $statement->execute([
                ':id_empresa' => current_empresa_id(),
                ':nombre_especie' => $speciesForm['nombre_especie'],
                ':cientifico_especie' => $speciesForm['cientifico_especie'] !== '' ? $speciesForm['cientifico_especie'] : null,
            ]);

            $successMessage = 'Especie dada de alta correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $speciesForm = [
                'nombre_especie' => '',
                'cientifico_especie' => '',
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
                'UPDATE especies SET nombre_especie = :nombre_especie, cientifico_especie = :cientifico_especie WHERE id_especie = :id_especie AND id_empresa = :id_empresa'
            );
            $statement->execute([
                ':nombre_especie' => $speciesForm['nombre_especie'],
                ':cientifico_especie' => $speciesForm['cientifico_especie'] !== '' ? $speciesForm['cientifico_especie'] : null,
                ':id_especie' => $speciesEditId,
                ':id_empresa' => current_empresa_id(),
            ]);

            $successMessage = 'Especie actualizada correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $speciesEditId = null;
            $speciesForm = ['nombre_especie' => '', 'cientifico_especie' => ''];

            if (($page ?? 'dashboard') !== 'especies') {
                header('Location: /index.php?page=especies');
                exit;
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible actualizar la especie.';
        }
    }
}
