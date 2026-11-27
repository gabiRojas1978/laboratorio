<?php

declare(strict_types=1);

$usuarios = [];
$usuariosClientes = [];
try {
    $usuariosStatement = db()->prepare('SELECT u.id_usuario, u.nombre_usuario, u.username_usuario, u.email_usuario, u.activo_usuario, u.created_at_usuario, u.tipo_usuario, cl.nombre_cliente FROM usuarios u LEFT JOIN clientes cl ON cl.id_cliente = u.id_cliente_usuario AND cl.id_empresa = u.id_empresa WHERE u.id_empresa = :id_empresa ORDER BY u.id_usuario DESC');
    $usuariosStatement->execute([':id_empresa' => current_empresa_id()]);
    $usuarios = $usuariosStatement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el listado de usuarios. Verifica que la tabla exista.';
}
try {
    $statement = db()->prepare('SELECT id_cliente, nombre_cliente FROM clientes WHERE id_empresa = :empresa ORDER BY nombre_cliente');
    $statement->execute([':empresa' => current_empresa_id()]);
    $usuariosClientes = $statement->fetchAll();
} catch (PDOException $exception) {
    $errors[] = 'No se pudo cargar el listado de clientes.';
}

if (isset($_GET['edit']) && $userEditId === null) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($editId !== false) {
        try {
            $editStatement = db()->prepare('SELECT id_usuario, nombre_usuario, username_usuario, email_usuario, activo_usuario, tipo_usuario, id_cliente_usuario FROM usuarios WHERE id_usuario = :id AND id_empresa = :id_empresa LIMIT 1');
            $editStatement->execute([':id' => $editId, ':id_empresa' => current_empresa_id()]);
            $editRow = $editStatement->fetch();
            if ($editRow !== false) {
                $userEditId = (int) $editRow['id_usuario'];
                $userForm['nombre_usuario'] = (string) ($editRow['nombre_usuario'] ?? '');
                $userForm['username_usuario'] = (string) ($editRow['username_usuario'] ?? '');
                $userForm['email_usuario'] = (string) ($editRow['email_usuario'] ?? '');
                $userForm['activo_usuario'] = (string) ($editRow['activo_usuario'] ?? '1');
                $userForm['tipo_usuario'] = $editRow['tipo_usuario'];
                $userForm['id_cliente_usuario'] = (string) ($editRow['id_cliente_usuario'] ?? '');
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No se pudo cargar el usuario para edición.';
        }
    }
}
?>
<section class="section-panel">
    <?php if ($userEditId !== null): ?>
        <h2>Editar usuario <span class="badge bg-secondary fs-6">#<?php echo $userEditId; ?></span></h2>
        <p>Modificá los datos del usuario. Dejá la contraseña en blanco para mantener la actual.</p>
    <?php else: ?>
        <h2>Alta de usuario</h2>
        <p>Completa los datos y guarda el registro en la tabla <strong>usuarios</strong>.</p>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" class="row g-3 mt-1">
        <?php if ($userEditId !== null): ?>
            <input type="hidden" name="update_usuario" value="1">
            <input type="hidden" name="usuario_edit_id" value="<?php echo $userEditId; ?>">
        <?php else: ?>
            <input type="hidden" name="save_usuario" value="1">
        <?php endif; ?>

        <div class="col-md-6">
            <label for="tipo_usuario" class="form-label">Tipo de acceso *</label>
            <select class="form-select" id="tipo_usuario" name="tipo_usuario" required>
                <option value="interno" <?php echo $userForm['tipo_usuario'] === 'interno' ? 'selected' : ''; ?>>Interno — administración del laboratorio</option>
                <option value="cliente" <?php echo $userForm['tipo_usuario'] === 'cliente' ? 'selected' : ''; ?>>Cliente — consulta y descarga de documentos</option>
            </select>
        </div>
        <div class="col-md-6" id="usuario_cliente_campo">
            <label for="id_cliente_usuario" class="form-label">Cliente asociado *</label>
            <select class="form-select" id="id_cliente_usuario" name="id_cliente_usuario">
                <option value="">Selecciona un cliente</option>
                <?php foreach ($usuariosClientes as $cliente): ?>
                    <option value="<?php echo (int) $cliente['id_cliente']; ?>" <?php echo $userForm['id_cliente_usuario'] === (string) $cliente['id_cliente'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cliente['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <script>
            (function () {
                const tipo = document.getElementById('tipo_usuario');
                const cliente = document.getElementById('id_cliente_usuario');
                function actualizarCliente() {
                    const esCliente = tipo.value === 'cliente';
                    document.getElementById('usuario_cliente_campo').hidden = !esCliente;
                    cliente.required = esCliente;
                    cliente.disabled = !esCliente;
                }
                tipo.addEventListener('change', actualizarCliente);
                actualizarCliente();
            })();
        </script>

        <div class="col-md-6">
            <label for="nombre_usuario" class="form-label">Nombre completo *</label>
            <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" maxlength="150" value="<?php echo htmlspecialchars($userForm['nombre_usuario'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>

        <div class="col-md-6">
            <label for="username_usuario" class="form-label">Usuario *</label>
            <input type="text" class="form-control" id="username_usuario" name="username_usuario" maxlength="60" value="<?php echo htmlspecialchars($userForm['username_usuario'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>

        <div class="col-md-6">
            <label for="email_usuario" class="form-label">Email</label>
            <input type="email" class="form-control" id="email_usuario" name="email_usuario" maxlength="150" value="<?php echo htmlspecialchars($userForm['email_usuario'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-md-6">
            <label for="password_usuario" class="form-label">Contraseña <?php echo $userEditId !== null ? '(dejar en blanco para no cambiarla)' : '*'; ?></label>
            <input type="password" class="form-control" id="password_usuario" name="password_usuario" autocomplete="new-password" <?php echo $userEditId === null ? 'required' : ''; ?>>
        </div>

        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="activo_usuario" name="activo_usuario" value="1" <?php echo $userForm['activo_usuario'] === '1' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="activo_usuario">Usuario activo</label>
            </div>
        </div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-brand"><?php echo $userEditId !== null ? 'Actualizar usuario' : 'Guardar usuario'; ?></button>
            <a href="/index.php?page=usuarios" class="btn btn-outline-brand">Cancelar</a>
        </div>
    </form>
</section>

<section class="section-panel">
    <h2>Usuarios cargados</h2>
    <p>Listado de registros existentes en la tabla usuarios.</p>

    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Acceso / Cliente</th>
                    <th>Estado</th>
                    <th>Alta</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($usuarios === []): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No hay usuarios cargados aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $usuario['id_usuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $usuario['nombre_usuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $usuario['username_usuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($usuario['email_usuario'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo $usuario['tipo_usuario'] === 'cliente' ? 'Cliente: ' . htmlspecialchars((string) ($usuario['nombre_cliente'] ?? 'Sin cliente asociado'), ENT_QUOTES, 'UTF-8') : 'Interno'; ?></td>
                            <td><?php echo ((int) $usuario['activo_usuario'] === 1) ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>'; ?></td>
                            <td><?php echo htmlspecialchars((string) ($usuario['created_at_usuario'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-nowrap">
                                <a href="/index.php?page=usuarios&edit=<?php echo urlencode((string) $usuario['id_usuario']); ?>" class="btn btn-sm btn-outline-light me-1"><i class="bi bi-pencil"></i> Editar</a>
                                <?php if ((int) $usuario['id_usuario'] !== ($_SESSION['user']['id'] ?? null)): ?>
                                    <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar el usuario &quot;<?php echo htmlspecialchars(addslashes((string) $usuario['username_usuario']), ENT_QUOTES, 'UTF-8'); ?>&quot;? Esta acción no se puede deshacer.')">
                                        <input type="hidden" name="delete_usuario" value="1">
                                        <input type="hidden" name="usuario_delete_id" value="<?php echo htmlspecialchars((string) $usuario['id_usuario'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Eliminar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>