<?php

declare(strict_types=1);

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_usuario'])) {
    $userForm['nombre_usuario'] = trim((string) ($_POST['nombre_usuario'] ?? ''));
    $userForm['username_usuario'] = trim((string) ($_POST['username_usuario'] ?? ''));
    $userForm['email_usuario'] = trim((string) ($_POST['email_usuario'] ?? ''));
    $userForm['activo_usuario'] = isset($_POST['activo_usuario']) ? '1' : '0';
    $userPassword = (string) ($_POST['password_usuario'] ?? '');

    if ($userForm['nombre_usuario'] === '') {
        $errors[] = 'El nombre del usuario es obligatorio.';
    }

    if (!preg_match('/^[a-zA-Z0-9._-]{3,60}$/', $userForm['username_usuario'])) {
        $errors[] = 'El usuario debe tener entre 3 y 60 caracteres (letras, números, puntos, guiones).';
    }

    if ($userForm['email_usuario'] !== '' && !filter_var($userForm['email_usuario'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email ingresado no es válido.';
    }

    if ($userPassword === '' || strlen($userPassword) < 6) {
        $errors[] = 'La contraseña es obligatoria y debe tener al menos 6 caracteres.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'INSERT INTO usuarios (id_empresa, nombre_usuario, username_usuario, email_usuario, password_hash_usuario, activo_usuario) VALUES (:id_empresa, :nombre_usuario, :username_usuario, :email_usuario, :password_hash_usuario, :activo_usuario)'
            );
            $statement->execute([
                ':id_empresa' => current_empresa_id(),
                ':nombre_usuario' => $userForm['nombre_usuario'],
                ':username_usuario' => $userForm['username_usuario'],
                ':email_usuario' => $userForm['email_usuario'] !== '' ? $userForm['email_usuario'] : null,
                ':password_hash_usuario' => password_hash($userPassword, PASSWORD_BCRYPT),
                ':activo_usuario' => $userForm['activo_usuario'],
            ]);

            $successMessage = 'Usuario dado de alta correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $userForm = ['nombre_usuario' => '', 'username_usuario' => '', 'email_usuario' => '', 'activo_usuario' => '1'];

            if (($page ?? 'dashboard') !== 'usuarios') {
                header('Location: /index.php?page=usuarios');
                exit;
            }
        } catch (Throwable $throwable) {
            if ((int) $throwable->getCode() === 23000) {
                $errors[] = 'Ya existe un usuario con ese nombre de usuario.';
            } else {
                $errors[] = 'No fue posible guardar el usuario.';
            }
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_usuario'])) {
    $userEditId = filter_var($_POST['usuario_edit_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $userForm['nombre_usuario'] = trim((string) ($_POST['nombre_usuario'] ?? ''));
    $userForm['username_usuario'] = trim((string) ($_POST['username_usuario'] ?? ''));
    $userForm['email_usuario'] = trim((string) ($_POST['email_usuario'] ?? ''));
    $userForm['activo_usuario'] = isset($_POST['activo_usuario']) ? '1' : '0';
    $userPassword = (string) ($_POST['password_usuario'] ?? '');

    if ($userEditId === false) {
        $errors[] = 'El usuario a editar no es válido.';
    }

    if ($userForm['nombre_usuario'] === '') {
        $errors[] = 'El nombre del usuario es obligatorio.';
    }

    if (!preg_match('/^[a-zA-Z0-9._-]{3,60}$/', $userForm['username_usuario'])) {
        $errors[] = 'El usuario debe tener entre 3 y 60 caracteres (letras, números, puntos, guiones).';
    }

    if ($userForm['email_usuario'] !== '' && !filter_var($userForm['email_usuario'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email ingresado no es válido.';
    }

    if ($userPassword !== '' && strlen($userPassword) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
    }

    if ($userEditId !== false && $userEditId === ($_SESSION['user']['id'] ?? null) && $userForm['activo_usuario'] === '0') {
        $errors[] = 'No podés desactivar tu propio usuario.';
    }

    if (!$errors) {
        try {
            if ($userPassword !== '') {
                $statement = db()->prepare(
                    'UPDATE usuarios SET nombre_usuario = :nombre_usuario, username_usuario = :username_usuario, email_usuario = :email_usuario, password_hash_usuario = :password_hash_usuario, activo_usuario = :activo_usuario WHERE id_usuario = :id_usuario AND id_empresa = :id_empresa'
                );
                $statement->execute([
                    ':nombre_usuario' => $userForm['nombre_usuario'],
                    ':username_usuario' => $userForm['username_usuario'],
                    ':email_usuario' => $userForm['email_usuario'] !== '' ? $userForm['email_usuario'] : null,
                    ':password_hash_usuario' => password_hash($userPassword, PASSWORD_BCRYPT),
                    ':activo_usuario' => $userForm['activo_usuario'],
                    ':id_usuario' => $userEditId,
                    ':id_empresa' => current_empresa_id(),
                ]);
            } else {
                $statement = db()->prepare(
                    'UPDATE usuarios SET nombre_usuario = :nombre_usuario, username_usuario = :username_usuario, email_usuario = :email_usuario, activo_usuario = :activo_usuario WHERE id_usuario = :id_usuario AND id_empresa = :id_empresa'
                );
                $statement->execute([
                    ':nombre_usuario' => $userForm['nombre_usuario'],
                    ':username_usuario' => $userForm['username_usuario'],
                    ':email_usuario' => $userForm['email_usuario'] !== '' ? $userForm['email_usuario'] : null,
                    ':activo_usuario' => $userForm['activo_usuario'],
                    ':id_usuario' => $userEditId,
                    ':id_empresa' => current_empresa_id(),
                ]);
            }

            $successMessage = 'Usuario actualizado correctamente.';
            $_SESSION['success_message'] = $successMessage;
            if ($userEditId === ($_SESSION['user']['id'] ?? null)) {
                $_SESSION['user']['name'] = $userForm['nombre_usuario'];
            }
            $userEditId = null;
            $userForm = ['nombre_usuario' => '', 'username_usuario' => '', 'email_usuario' => '', 'activo_usuario' => '1'];

            if (($page ?? 'dashboard') !== 'usuarios') {
                header('Location: /index.php?page=usuarios');
                exit;
            }
        } catch (Throwable $throwable) {
            if ((int) $throwable->getCode() === 23000) {
                $errors[] = 'Ya existe un usuario con ese nombre de usuario.';
            } else {
                $errors[] = 'No fue posible actualizar el usuario.';
            }
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_usuario'])) {
    $deleteId = filter_var($_POST['usuario_delete_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($deleteId === false) {
        $errors[] = 'El usuario a eliminar no es válido.';
    } elseif ($deleteId === ($_SESSION['user']['id'] ?? null)) {
        $errors[] = 'No podés eliminar tu propio usuario.';
    } else {
        try {
            $statement = db()->prepare('DELETE FROM usuarios WHERE id_usuario = :id_usuario AND id_empresa = :id_empresa');
            $statement->execute([':id_usuario' => $deleteId, ':id_empresa' => current_empresa_id()]);
            $successMessage = 'Usuario eliminado correctamente.';
            $_SESSION['success_message'] = $successMessage;

            if (($page ?? 'dashboard') !== 'usuarios') {
                header('Location: /index.php?page=usuarios');
                exit;
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible eliminar el usuario.';
        }
    }
}
