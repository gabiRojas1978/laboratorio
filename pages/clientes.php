<?php

declare(strict_types=1);

$clientes = [];
try {
    if (client_emails_table_exists()) {
        $clientesStatement = db()->prepare(
            'SELECT c.id_cliente, c.nombre_cliente, c.telefono_cliente, c.direccion_cliente, c.localidad_cliente, c.provincia_cliente, c.cuit_cliente, c.created_at_cliente,
                    GROUP_CONCAT(ce.email_cliente ORDER BY ce.principal_email DESC, ce.id_email_cliente ASC SEPARATOR ", ") AS emails_cliente
             FROM clientes c
             LEFT JOIN cliente_emails ce ON ce.id_cliente = c.id_cliente AND ce.id_empresa = c.id_empresa AND ce.activo_email = 1
             WHERE c.id_empresa = :id_empresa
             GROUP BY c.id_cliente, c.nombre_cliente, c.telefono_cliente, c.direccion_cliente, c.localidad_cliente, c.provincia_cliente, c.cuit_cliente, c.created_at_cliente
             ORDER BY c.id_cliente DESC'
        );
    } else {
        $clientesStatement = db()->prepare(
            'SELECT id_cliente, nombre_cliente, telefono_cliente, direccion_cliente, localidad_cliente, provincia_cliente, cuit_cliente, created_at_cliente,
                    mail_cliente AS emails_cliente
             FROM clientes
             WHERE id_empresa = :id_empresa
             ORDER BY id_cliente DESC'
        );
    }
    $clientesStatement->execute([':id_empresa' => current_empresa_id()]);
    $clientes = $clientesStatement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el listado de clientes. Verifica que la base de datos y la tabla existan.';
}

if (isset($_GET['edit']) && $clientEditId === null) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($editId !== false) {
        try {
            $editStatement = db()->prepare('SELECT id_cliente, nombre_cliente, telefono_cliente, direccion_cliente, localidad_cliente, provincia_cliente, mail_cliente, cuit_cliente FROM clientes WHERE id_cliente = :id AND id_empresa = :id_empresa LIMIT 1');
            $editStatement->execute([':id' => $editId, ':id_empresa' => current_empresa_id()]);
            $editRow = $editStatement->fetch();
            if ($editRow !== false) {
                $clientEditId = (int) $editRow['id_cliente'];
                $clientForm['nombre_cliente'] = (string) ($editRow['nombre_cliente'] ?? '');
                $clientForm['telefono_cliente'] = (string) ($editRow['telefono_cliente'] ?? '');
                $clientForm['direccion_cliente'] = (string) ($editRow['direccion_cliente'] ?? '');
                $clientForm['provincia_cliente'] = (string) ($editRow['provincia_cliente'] ?? '');
                $clientForm['localidad_cliente'] = (string) ($editRow['localidad_cliente'] ?? '');
                $clientForm['cuit_cliente'] = (string) ($editRow['cuit_cliente'] ?? '');

                if (client_emails_table_exists()) {
                    $emailsStatement = db()->prepare(
                        'SELECT email_cliente FROM cliente_emails
                         WHERE id_cliente = :id_cliente AND id_empresa = :id_empresa AND activo_email = 1
                         ORDER BY principal_email DESC, id_email_cliente ASC'
                    );
                    $emailsStatement->execute([
                        ':id_cliente' => $clientEditId,
                        ':id_empresa' => current_empresa_id(),
                    ]);
                    $clientForm['emails_cliente'] = array_values(array_filter(array_map(
                        static fn($email) => trim((string) $email),
                        $emailsStatement->fetchAll(PDO::FETCH_COLUMN)
                    ), static fn($email) => $email !== ''));
                } else {
                    $singleEmail = trim((string) ($editRow['mail_cliente'] ?? ''));
                    $clientForm['emails_cliente'] = $singleEmail !== '' ? [$singleEmail] : [];
                }

                if ($clientForm['emails_cliente'] === []) {
                    $clientForm['emails_cliente'] = [''];
                }
            }
        } catch (Throwable $throwable) {
            $errors[] = 'No se pudo cargar el cliente para edición.';
        }
    }
}
?>
<section class="section-panel">
    <?php if ($clientEditId !== null): ?>
        <h2>Editar cliente <span class="badge bg-secondary fs-6">#<?php echo $clientEditId; ?></span></h2>
        <p>Modificá los datos del cliente y guardá los cambios.</p>
    <?php else: ?>
        <h2>Alta de cliente</h2>
        <p>Completa los datos básicos del cliente y guarda el registro en la base de datos <strong>laboratorio</strong>.</p>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" class="row g-3 mt-1">
        <?php if ($clientEditId !== null): ?>
            <input type="hidden" name="update_cliente" value="1">
            <input type="hidden" name="cliente_edit_id" value="<?php echo $clientEditId; ?>">
        <?php else: ?>
            <input type="hidden" name="save_cliente" value="1">
        <?php endif; ?>

        <div class="col-12">
            <label for="nombre_cliente" class="form-label">Nombre del cliente *</label>
            <input type="text" class="form-control" id="nombre_cliente" name="nombre_cliente" maxlength="150" value="<?php echo htmlspecialchars($clientForm['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>

        <div class="col-md-6">
            <label for="telefono_cliente" class="form-label">Teléfono</label>
            <input type="text" class="form-control" id="telefono_cliente" name="telefono_cliente" maxlength="30" value="<?php echo htmlspecialchars($clientForm['telefono_cliente'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-md-6">
            <label for="cuit_cliente" class="form-label">CUIT</label>
            <input type="text" class="form-control" id="cuit_cliente" name="cuit_cliente" maxlength="13" value="<?php echo htmlspecialchars($clientForm['cuit_cliente'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-12">
            <label for="direccion_cliente" class="form-label">Dirección</label>
            <input type="text" class="form-control" id="direccion_cliente" name="direccion_cliente" maxlength="255" value="<?php echo htmlspecialchars($clientForm['direccion_cliente'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-6">
            <label for="localidad_cliente" class="form-label">Localidad</label>
            <input type="text" class="form-control" id="localidad_cliente" name="localidad_cliente" maxlength="255" value="<?php echo htmlspecialchars($clientForm['localidad_cliente'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-6">
            <label for="provincia_cliente" class="form-label">Provincia</label>
            <input type="text" class="form-control" id="provincia_cliente" name="provincia_cliente" maxlength="255" value="<?php echo htmlspecialchars($clientForm['provincia_cliente'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-12">
            <label class="form-label">Emails</label>
            <div id="cliente-emails-list">
                <?php foreach (($clientForm['emails_cliente'] ?? ['']) as $emailCliente): ?>
                    <div class="input-group mb-2 cliente-email-row">
                        <input type="email" class="form-control" name="emails_cliente[]" maxlength="150" value="<?php echo htmlspecialchars((string) $emailCliente, ENT_QUOTES, 'UTF-8'); ?>" placeholder="email@dominio.com">
                        <button type="button" class="btn btn-outline-danger cliente-email-remove">Quitar</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm btn-outline-brand" id="cliente-email-add">Agregar email</button>
        </div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-brand"><?php echo $clientEditId !== null ? 'Actualizar cliente' : 'Guardar cliente'; ?></button>
            <a href="/index.php?page=clientes" class="btn btn-outline-brand">Cancelar</a>
        </div>
    </form>
</section>

<section class="section-panel">
    <h2>Clientes cargados</h2>
    <p>Listado de registros existentes en la tabla clientes.</p>

    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Localidad</th>
                    <th>Provincia</th>
                    <th>Emails</th>
                    <th>CUIT</th>
                    <th>Alta</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($clientes === []): ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted">No hay clientes cargados aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $cliente['id_cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $cliente['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($cliente['telefono_cliente'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($cliente['direccion_cliente'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($cliente['localidad_cliente'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($cliente['provincia_cliente'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($cliente['emails_cliente'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($cliente['cuit_cliente'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($cliente['created_at_cliente'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-nowrap">
                                <a href="/index.php?page=clientes&edit=<?php echo urlencode((string) $cliente['id_cliente']); ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-pencil"></i> Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>