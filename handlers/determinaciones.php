<?php

declare(strict_types=1);

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_determinacion'])) {
    $detForm['nombre_determinacion'] = trim((string) ($_POST['nombre_determinacion'] ?? ''));

    if ($detForm['nombre_determinacion'] === '') {
        $errors[] = 'El nombre de la determinación es obligatorio.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'INSERT INTO otras_determinaciones (id_empresa, nombre_determinacion) VALUES (:id_empresa, :nombre_determinacion)'
            );
            $statement->execute([':id_empresa' => current_empresa_id(), ':nombre_determinacion' => $detForm['nombre_determinacion']]);

            $successMessage = 'Determinación dada de alta correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $detForm = ['nombre_determinacion' => ''];

            if (($page ?? 'dashboard') !== 'otras_determinaciones') {
                header('Location: /index.php?page=otras_determinaciones');
                exit;
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible guardar la determinación.';
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_determinacion'])) {
    $detEditId = filter_var($_POST['det_edit_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $detForm['nombre_determinacion'] = trim((string) ($_POST['nombre_determinacion'] ?? ''));

    if ($detEditId === false) {
        $errors[] = 'La determinación a editar no es válida.';
    }

    if ($detForm['nombre_determinacion'] === '') {
        $errors[] = 'El nombre de la determinación es obligatorio.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'UPDATE otras_determinaciones SET nombre_determinacion = :nombre_determinacion WHERE id_determinacion = :id_determinacion AND id_empresa = :id_empresa'
            );
            $statement->execute([
                ':nombre_determinacion' => $detForm['nombre_determinacion'],
                ':id_determinacion' => $detEditId,
                ':id_empresa' => current_empresa_id(),
            ]);

            $successMessage = 'Determinación actualizada correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $detEditId = null;
            $detForm = ['nombre_determinacion' => ''];

            if (($page ?? 'dashboard') !== 'otras_determinaciones') {
                header('Location: /index.php?page=otras_determinaciones');
                exit;
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible actualizar la determinación.';
        }
    }
}
