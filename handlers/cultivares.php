<?php

declare(strict_types=1);

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cultivar'])) {
    $cultivarForm['id_especie'] = trim((string) ($_POST['id_especie'] ?? ''));
    $cultivarForm['nombre_cultivar'] = trim((string) ($_POST['nombre_cultivar'] ?? ''));

    $speciesId = filter_var($cultivarForm['id_especie'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($speciesId === false) {
        $errors[] = 'Debes seleccionar una especie válida.';
    }

    if ($cultivarForm['nombre_cultivar'] === '') {
        $errors[] = 'El nombre del cultivar es obligatorio.';
    }

    if (!$errors) {
        try {
            $speciesStatement = db()->prepare('SELECT id_especie FROM especies WHERE id_especie = :id_especie AND id_empresa = :id_empresa LIMIT 1');
            $speciesStatement->execute([':id_especie' => $speciesId, ':id_empresa' => current_empresa_id()]);

            if ($speciesStatement->fetch() === false) {
                $errors[] = 'La especie seleccionada no existe.';
            } else {
                $statement = db()->prepare(
                    'INSERT INTO cultivares (id_empresa, id_especie, nombre_cultivar) VALUES (:id_empresa, :id_especie, :nombre_cultivar)'
                );
                $statement->execute([
                    ':id_empresa' => current_empresa_id(),
                    ':id_especie' => $speciesId,
                    ':nombre_cultivar' => $cultivarForm['nombre_cultivar'],
                ]);

                $successMessage = 'Cultivar dado de alta correctamente.';
                $_SESSION['success_message'] = $successMessage;
                $cultivarForm = [
                    'id_especie' => '',
                    'nombre_cultivar' => '',
                ];

                if (($page ?? 'dashboard') !== 'cultivares') {
                    header('Location: /index.php?page=cultivares');
                    exit;
                }
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible guardar el cultivar.';
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cultivar'])) {
    $cultivarEditId = filter_var($_POST['cultivar_edit_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $cultivarForm['id_especie'] = trim((string) ($_POST['id_especie'] ?? ''));
    $cultivarForm['nombre_cultivar'] = trim((string) ($_POST['nombre_cultivar'] ?? ''));

    $speciesId = filter_var($cultivarForm['id_especie'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($cultivarEditId === false) {
        $errors[] = 'El cultivar a editar no es válido.';
    }

    if ($speciesId === false) {
        $errors[] = 'Debes seleccionar una especie válida.';
    }

    if ($cultivarForm['nombre_cultivar'] === '') {
        $errors[] = 'El nombre del cultivar es obligatorio.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'UPDATE cultivares SET id_especie = :id_especie, nombre_cultivar = :nombre_cultivar WHERE id_cultivar = :id_cultivar AND id_empresa = :id_empresa'
            );
            $statement->execute([
                ':id_especie' => $speciesId,
                ':nombre_cultivar' => $cultivarForm['nombre_cultivar'],
                ':id_cultivar' => $cultivarEditId,
                ':id_empresa' => current_empresa_id(),
            ]);

            $successMessage = 'Cultivar actualizado correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $cultivarEditId = null;
            $cultivarForm = ['id_especie' => '', 'nombre_cultivar' => ''];

            if (($page ?? 'dashboard') !== 'cultivares') {
                header('Location: /index.php?page=cultivares');
                exit;
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible actualizar el cultivar.';
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_cultivar'])) {
    $deleteId = filter_var($_POST['cultivar_delete_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($deleteId === false) {
        $errors[] = 'El cultivar a eliminar no es válido.';
    } else {
        try {
            $statement = db()->prepare('DELETE FROM cultivares WHERE id_cultivar = :id_cultivar AND id_empresa = :id_empresa');
            $statement->execute([':id_cultivar' => $deleteId, ':id_empresa' => current_empresa_id()]);
            $successMessage = 'Cultivar eliminado correctamente.';
            $_SESSION['success_message'] = $successMessage;

            if (($page ?? 'dashboard') !== 'cultivares') {
                header('Location: /index.php?page=cultivares');
                exit;
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible eliminar el cultivar.';
        }
    }
}
