<?php

declare(strict_types=1);

if (is_superadmin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_empresa'])) {
    $empresaForm['nombre_empresa'] = trim((string) ($_POST['nombre_empresa'] ?? ''));
    $empresaAdminForm['nombre_usuario'] = trim((string) ($_POST['admin_nombre_usuario'] ?? ''));
    $empresaAdminForm['username_usuario'] = trim((string) ($_POST['admin_username_usuario'] ?? ''));
    $empresaAdminForm['email_usuario'] = trim((string) ($_POST['admin_email_usuario'] ?? ''));
    $empresaAdminPassword = (string) ($_POST['admin_password_usuario'] ?? '');

    if ($empresaForm['nombre_empresa'] === '') {
        $errors[] = 'El nombre de la empresa es obligatorio.';
    }

    if ($empresaAdminForm['nombre_usuario'] === '') {
        $errors[] = 'El nombre del usuario administrador es obligatorio.';
    }

    if (!preg_match('/^[a-zA-Z0-9._-]{3,60}$/', $empresaAdminForm['username_usuario'])) {
        $errors[] = 'El usuario administrador debe tener entre 3 y 60 caracteres (letras, números, puntos, guiones).';
    }

    if ($empresaAdminForm['email_usuario'] !== '' && !filter_var($empresaAdminForm['email_usuario'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email ingresado no es válido.';
    }

    if ($empresaAdminPassword === '' || strlen($empresaAdminPassword) < 6) {
        $errors[] = 'La contraseña del administrador es obligatoria y debe tener al menos 6 caracteres.';
    }

    if (!$errors) {
        try {
            db()->beginTransaction();

            $empresaStatement = db()->prepare('INSERT INTO empresas (nombre_empresa) VALUES (:nombre_empresa)');
            $empresaStatement->execute([':nombre_empresa' => $empresaForm['nombre_empresa']]);
            $newEmpresaId = (int) db()->lastInsertId();

            anormalidades_inicializar_empresa(db(), $newEmpresaId);

            $adminStatement = db()->prepare(
                'INSERT INTO usuarios (id_empresa, nombre_usuario, username_usuario, email_usuario, password_hash_usuario, activo_usuario, es_superadmin) VALUES (:id_empresa, :nombre_usuario, :username_usuario, :email_usuario, :password_hash_usuario, 1, 0)'
            );
            $adminStatement->execute([
                ':id_empresa' => $newEmpresaId,
                ':nombre_usuario' => $empresaAdminForm['nombre_usuario'],
                ':username_usuario' => $empresaAdminForm['username_usuario'],
                ':email_usuario' => $empresaAdminForm['email_usuario'] !== '' ? $empresaAdminForm['email_usuario'] : null,
                ':password_hash_usuario' => password_hash($empresaAdminPassword, PASSWORD_BCRYPT),
            ]);

            db()->commit();

            $successMessage = 'Empresa y usuario administrador dados de alta correctamente.';
            $_SESSION['success_message'] = $successMessage;

            header('Location: /index.php?page=empresas');
            exit;
        } catch (Throwable $throwable) {
            db()->rollBack();
            if ((int) $throwable->getCode() === 23000) {
                $errors[] = 'Ya existe un usuario con ese nombre de usuario.';
            } else {
                $errors[] = 'No fue posible dar de alta la empresa. Verifica la base de datos y la migración de anormalidades.';
            }
        }
    }
}

if (is_superadmin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_empresa'])) {
    $empresaEditId = filter_var($_POST['empresa_edit_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $empresaForm['nombre_empresa'] = trim((string) ($_POST['nombre_empresa'] ?? ''));
    $empresaForm['activo_empresa'] = isset($_POST['activo_empresa']) ? '1' : '0';

    if ($empresaEditId === false) {
        $errors[] = 'La empresa a editar no es válida.';
    }

    if ($empresaForm['nombre_empresa'] === '') {
        $errors[] = 'El nombre de la empresa es obligatorio.';
    }

    if (!$errors) {
        try {
            $statement = db()->prepare('UPDATE empresas SET nombre_empresa = :nombre_empresa, activo_empresa = :activo_empresa WHERE id_empresa = :id_empresa');
            $statement->execute([
                ':nombre_empresa' => $empresaForm['nombre_empresa'],
                ':activo_empresa' => $empresaForm['activo_empresa'],
                ':id_empresa' => $empresaEditId,
            ]);

            $successMessage = 'Empresa actualizada correctamente.';
            $_SESSION['success_message'] = $successMessage;
            header('Location: /index.php?page=empresas');
            exit;
        } catch (Throwable $throwable) {
            $errors[] = 'No fue posible actualizar la empresa.';
        }
    }
}
