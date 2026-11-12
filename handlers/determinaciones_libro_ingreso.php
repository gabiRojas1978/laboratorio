<?php

declare(strict_types=1);

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_determinacion_libro']) || isset($_POST['update_determinacion_libro']))) {
    $nombre = $_POST['nombre_determinacion_libro'] ?? '';
    $costo = $_POST['costo'] ?? '';
    $determinacionLibroForm['nombre_determinacion_libro'] = is_string($nombre) ? trim($nombre) : '';
    $determinacionLibroForm['costo'] = is_string($costo) ? trim($costo) : '';
    $editing = isset($_POST['update_determinacion_libro']);

    if ($editing) {
        $editId = filter_var($_POST['determinacion_libro_edit_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($editId === false) {
            $errors[] = 'La determinación a editar no es válida.';
        } else {
            $determinacionLibroEditId = $editId;
        }
    }

    if ($determinacionLibroForm['nombre_determinacion_libro'] === '') {
        $errors[] = 'El nombre de la determinación es obligatorio.';
    } elseif (mb_strlen($determinacionLibroForm['nombre_determinacion_libro'], 'UTF-8') > 255) {
        $errors[] = 'El nombre de la determinación no puede superar los 255 caracteres.';
    }

    $costoNormalizado = str_replace(',', '.', $determinacionLibroForm['costo']);
    if (!is_string($costo) || ($costoNormalizado !== '' && !preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', $costoNormalizado))) {
        $errors[] = 'El costo debe ser un importe entre 0 y 9999999999,99, con hasta dos decimales.';
    }

    if (!$errors) {
        try {
            if ($editing) {
                $statement = db()->prepare('SELECT id_determinacion_libro FROM determinaciones_libro_ingreso WHERE id_determinacion_libro = :id AND id_empresa = :id_empresa');
                $statement->execute([':id' => $determinacionLibroEditId, ':id_empresa' => current_empresa_id()]);
                if ($statement->fetchColumn() === false) {
                    $errors[] = 'La determinación no existe o no pertenece a la empresa activa.';
                }
            }

            if (!$errors) {
                $parametros = [
                    ':nombre' => $determinacionLibroForm['nombre_determinacion_libro'],
                    ':costo' => $costoNormalizado !== '' ? $costoNormalizado : '0.00',
                    ':id_empresa' => current_empresa_id(),
                ];
                if ($editing) {
                    $parametros[':id'] = $determinacionLibroEditId;
                    $statement = db()->prepare('UPDATE determinaciones_libro_ingreso SET nombre_determinacion_libro = :nombre, costo = :costo WHERE id_determinacion_libro = :id AND id_empresa = :id_empresa');
                } else {
                    $statement = db()->prepare('INSERT INTO determinaciones_libro_ingreso (id_empresa, nombre_determinacion_libro, costo) VALUES (:id_empresa, :nombre, :costo)');
                }
                $statement->execute($parametros);

                $successMessage = $editing ? 'Determinación actualizada correctamente.' : 'Determinación dada de alta correctamente.';
                $_SESSION['success_message'] = $successMessage;
                $determinacionLibroEditId = null;
                $determinacionLibroForm = ['nombre_determinacion_libro' => '', 'costo' => '0.00'];

                if (($page ?? 'dashboard') !== 'determinaciones') {
                    header('Location: /index.php?page=determinaciones');
                    exit;
                }
            }
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) {
                $errors[] = 'Ya existe una determinación con ese nombre en la empresa activa.';
            } else {
                $errors[] = 'No fue posible guardar la determinación. Verifica la base de datos y la migración de costos.';
            }
        }
    }
}
