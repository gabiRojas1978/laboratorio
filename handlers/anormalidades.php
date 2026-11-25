<?php

declare(strict_types=1);

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_anormalidad']) || isset($_POST['update_anormalidad']))) {
    $editing = isset($_POST['update_anormalidad']);
    $campos = [
        'codigo' => ['Código', 30],
        'etiqueta' => ['Anormalidad', 255],
        'grupo_tipo' => ['Grupo / Tipo', 255],
        'categoria_principal' => ['Categoría Principal', 255],
        'subcategoria' => ['Subcategoría', 255],
    ];
    foreach ($campos as $campo => [$nombre, $maximo]) {
        $valor = $_POST[$campo] ?? '';
        $anormalidadForm[$campo] = is_string($valor) ? trim($valor) : '';
        if ($anormalidadForm[$campo] === '') {
            $errors[] = $nombre . ' es obligatorio.';
        } elseif (mb_strlen($anormalidadForm[$campo], 'UTF-8') > $maximo) {
            $errors[] = $nombre . ' no puede superar los ' . $maximo . ' caracteres.';
        }
    }

    if ($editing) {
        $editId = filter_var($_POST['anormalidad_edit_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($editId === false) {
            $errors[] = 'La anormalidad a editar no es válida.';
        } else {
            $anormalidadEditId = $editId;
        }
    }

    if (!$errors) {
        try {
            if ($editing) {
                $statement = db()->prepare('SELECT id_anormalidad FROM anormalidades WHERE id_anormalidad = :id AND id_empresa = :id_empresa');
                $statement->execute([':id' => $anormalidadEditId, ':id_empresa' => current_empresa_id()]);
                if ($statement->fetchColumn() === false) {
                    $errors[] = 'La anormalidad no existe o no pertenece a la empresa activa.';
                }
            }

            if (!$errors) {
                $params = [
                    ':id_empresa' => current_empresa_id(),
                    ':codigo' => $anormalidadForm['codigo'],
                    ':etiqueta' => $anormalidadForm['etiqueta'],
                    ':grupo_tipo' => $anormalidadForm['grupo_tipo'],
                    ':categoria_principal' => $anormalidadForm['categoria_principal'],
                    ':subcategoria' => $anormalidadForm['subcategoria'],
                ];
                if ($editing) {
                    $params[':id'] = $anormalidadEditId;
                    $statement = db()->prepare(
                        'UPDATE anormalidades SET codigo = :codigo, etiqueta = :etiqueta, grupo_tipo = :grupo_tipo,
                         categoria_principal = :categoria_principal, subcategoria = :subcategoria
                         WHERE id_anormalidad = :id AND id_empresa = :id_empresa'
                    );
                } else {
                    $statement = db()->prepare(
                        'INSERT INTO anormalidades (id_empresa, codigo, etiqueta, grupo_tipo, categoria_principal, subcategoria)
                         VALUES (:id_empresa, :codigo, :etiqueta, :grupo_tipo, :categoria_principal, :subcategoria)'
                    );
                }
                $statement->execute($params);
                $successMessage = $editing ? 'Anormalidad actualizada correctamente.' : 'Anormalidad dada de alta correctamente.';
                $_SESSION['success_message'] = $successMessage;
                $anormalidadForm = array_fill_keys(array_keys($campos), '');
                $anormalidadEditId = null;
                if (($page ?? '') !== 'anormalidades') {
                    header('Location: /index.php?page=anormalidades');
                    exit;
                }
            }
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) {
                $errors[] = 'Ya existe una anormalidad con ese código en la empresa activa.';
            } else {
                $errors[] = 'No fue posible guardar la anormalidad. Verifica la base de datos y la migración de anormalidades.';
            }
        }
    }
}
