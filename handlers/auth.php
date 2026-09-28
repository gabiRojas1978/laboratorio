<?php

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    try {
        $userStatement = db()->prepare(
            'SELECT u.id_usuario, u.nombre_usuario, u.username_usuario, u.password_hash_usuario, u.activo_usuario, u.es_superadmin, u.id_empresa, e.activo_empresa, e.nombre_empresa
            FROM usuarios u
            INNER JOIN empresas e ON e.id_empresa = u.id_empresa
            WHERE u.username_usuario = :username LIMIT 1'
        );
        $userStatement->execute([':username' => $username]);
        $userRow = $userStatement->fetch();

        if (
            $userRow !== false
            && (int) $userRow['activo_usuario'] === 1
            && (int) $userRow['activo_empresa'] === 1
            && password_verify($password, $userRow['password_hash_usuario'])
        ) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int) $userRow['id_usuario'],
                'username' => $userRow['username_usuario'],
                'name' => $userRow['nombre_usuario'],
                'id_empresa' => (int) $userRow['id_empresa'],
                'empresa_nombre' => $userRow['nombre_empresa'],
                'es_superadmin' => (bool) $userRow['es_superadmin'],
            ];
            header('Location: /index.php');
            exit;
        }

        $errors[] = 'Usuario o contraseña incorrectos.';
    } catch (Throwable $throwable) {
        $errors[] = 'No fue posible iniciar sesión. Verifica la configuración de base de datos del servidor.';
    }
}
