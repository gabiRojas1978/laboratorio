<?php

declare(strict_types=1);

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cliente'])) {
    $clientForm['nombre_cliente'] = trim((string) ($_POST['nombre_cliente'] ?? ''));
    $clientForm['telefono_cliente'] = trim((string) ($_POST['telefono_cliente'] ?? ''));
    $clientForm['direccion_cliente'] = trim((string) ($_POST['direccion_cliente'] ?? ''));
    $clientForm['localidad_cliente'] = trim((string) ($_POST['localidad_cliente'] ?? ''));
    $clientForm['provincia_cliente'] = trim((string) ($_POST['provincia_cliente'] ?? ''));
    $clientForm['emails_cliente'] = normalize_client_emails($_POST['emails_cliente'] ?? []);
    $clientForm['cuit_cliente'] = trim((string) ($_POST['cuit_cliente'] ?? ''));

    if ($clientForm['emails_cliente'] === []) {
        $clientForm['emails_cliente'] = [''];
    }

    if ($clientForm['nombre_cliente'] === '') {
        $errors[] = 'El nombre del cliente es obligatorio.';
    }

    if ($clientForm['cuit_cliente'] !== '' && !preg_match('/^[0-9\-]{7,13}$/', $clientForm['cuit_cliente'])) {
        $errors[] = 'El CUIT ingresado no tiene un formato válido.';
    }

    foreach ($clientForm['emails_cliente'] as $emailCliente) {
        if ($emailCliente !== '' && !filter_var($emailCliente, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Uno de los emails ingresados no es válido.';
            break;
        }
    }

    if (!$errors) {
        try {
            db()->beginTransaction();

            $primaryClientEmail = $clientForm['emails_cliente'][0] ?? '';

            if (client_emails_table_exists()) {
                $statement = db()->prepare(
                    'INSERT INTO clientes (id_empresa, nombre_cliente, telefono_cliente, direccion_cliente, localidad_cliente, provincia_cliente, cuit_cliente) VALUES (:id_empresa, :nombre_cliente, :telefono_cliente, :direccion_cliente, :localidad_cliente, :provincia_cliente, :cuit_cliente)'
                );
                $statement->execute([
                    ':id_empresa' => current_empresa_id(),
                    ':nombre_cliente' => $clientForm['nombre_cliente'],
                    ':telefono_cliente' => $clientForm['telefono_cliente'] !== '' ? $clientForm['telefono_cliente'] : null,
                    ':direccion_cliente' => $clientForm['direccion_cliente'] !== '' ? $clientForm['direccion_cliente'] : null,
                    ':localidad_cliente' => $clientForm['localidad_cliente'] !== '' ? $clientForm['localidad_cliente'] : null,
                    ':provincia_cliente' => $clientForm['provincia_cliente'] !== '' ? $clientForm['provincia_cliente'] : null,
                    ':cuit_cliente' => $clientForm['cuit_cliente'] !== '' ? $clientForm['cuit_cliente'] : null,
                ]);
            } else {
                $statement = db()->prepare(
                    'INSERT INTO clientes (id_empresa, nombre_cliente, telefono_cliente, direccion_cliente, localidad_cliente, provincia_cliente, mail_cliente, cuit_cliente) VALUES (:id_empresa, :nombre_cliente, :telefono_cliente, :direccion_cliente, :localidad_cliente, :provincia_cliente, :mail_cliente, :cuit_cliente)'
                );
                $statement->execute([
                    ':id_empresa' => current_empresa_id(),
                    ':nombre_cliente' => $clientForm['nombre_cliente'],
                    ':telefono_cliente' => $clientForm['telefono_cliente'] !== '' ? $clientForm['telefono_cliente'] : null,
                    ':direccion_cliente' => $clientForm['direccion_cliente'] !== '' ? $clientForm['direccion_cliente'] : null,
                    ':localidad_cliente' => $clientForm['localidad_cliente'] !== '' ? $clientForm['localidad_cliente'] : null,
                    ':provincia_cliente' => $clientForm['provincia_cliente'] !== '' ? $clientForm['provincia_cliente'] : null,
                    ':mail_cliente' => $primaryClientEmail !== '' ? $primaryClientEmail : null,
                    ':cuit_cliente' => $clientForm['cuit_cliente'] !== '' ? $clientForm['cuit_cliente'] : null,
                ]);
            }

            $newClientId = (int) db()->lastInsertId();
            if (client_emails_table_exists() && $clientForm['emails_cliente'] !== ['']) {
                $emailInsert = db()->prepare(
                    'INSERT INTO cliente_emails (id_empresa, id_cliente, email_cliente, principal_email, activo_email) VALUES (:id_empresa, :id_cliente, :email_cliente, :principal_email, 1)'
                );
                foreach ($clientForm['emails_cliente'] as $index => $emailCliente) {
                    $emailInsert->execute([
                        ':id_empresa' => current_empresa_id(),
                        ':id_cliente' => $newClientId,
                        ':email_cliente' => $emailCliente,
                        ':principal_email' => $index === 0 ? 1 : 0,
                    ]);
                }
            }

            db()->commit();

            $successMessage = 'Cliente dado de alta correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $clientForm = [
                'nombre_cliente' => '',
                'telefono_cliente' => '',
                'direccion_cliente' => '',
                'localidad_cliente' => '',
                'provincia_cliente' => '',
                'emails_cliente' => [''],
                'cuit_cliente' => '',
            ];

            if (($page ?? 'dashboard') !== 'clientes') {
                header('Location: /index.php?page=clientes');
                exit;
            }
        } catch (Throwable $throwable) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            if ((int) $throwable->getCode() === 23000) {
                $errors[] = 'Ya existe un cliente con ese CUIT.';
            } else {
                $errors[] = 'No fue posible guardar el cliente.';
            }
        }
    }
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cliente'])) {
    $clientEditId = filter_var($_POST['cliente_edit_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $clientForm['nombre_cliente'] = trim((string) ($_POST['nombre_cliente'] ?? ''));
    $clientForm['telefono_cliente'] = trim((string) ($_POST['telefono_cliente'] ?? ''));
    $clientForm['direccion_cliente'] = trim((string) ($_POST['direccion_cliente'] ?? ''));
    $clientForm['localidad_cliente'] = trim((string) ($_POST['localidad_cliente'] ?? ''));
    $clientForm['provincia_cliente'] = trim((string) ($_POST['provincia_cliente'] ?? ''));
    $clientForm['emails_cliente'] = normalize_client_emails($_POST['emails_cliente'] ?? []);
    $clientForm['cuit_cliente'] = trim((string) ($_POST['cuit_cliente'] ?? ''));

    if ($clientForm['emails_cliente'] === []) {
        $clientForm['emails_cliente'] = [''];
    }

    if ($clientEditId === false) {
        $errors[] = 'El cliente a editar no es válido.';
    }

    if ($clientForm['nombre_cliente'] === '') {
        $errors[] = 'El nombre del cliente es obligatorio.';
    }

    if ($clientForm['cuit_cliente'] !== '' && !preg_match('/^[0-9\-]{7,13}$/', $clientForm['cuit_cliente'])) {
        $errors[] = 'El CUIT ingresado no tiene un formato válido.';
    }

    foreach ($clientForm['emails_cliente'] as $emailCliente) {
        if ($emailCliente !== '' && !filter_var($emailCliente, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Uno de los emails ingresados no es válido.';
            break;
        }
    }

    if (!$errors) {
        try {
            db()->beginTransaction();

            $primaryClientEmail = $clientForm['emails_cliente'][0] ?? '';

            if (client_emails_table_exists()) {
                $statement = db()->prepare(
                    'UPDATE clientes SET nombre_cliente = :nombre_cliente, telefono_cliente = :telefono_cliente, direccion_cliente = :direccion_cliente, localidad_cliente = :localidad_cliente, provincia_cliente = :provincia_cliente, cuit_cliente = :cuit_cliente WHERE id_cliente = :id_cliente AND id_empresa = :id_empresa'
                );
                $statement->execute([
                    ':nombre_cliente' => $clientForm['nombre_cliente'],
                    ':telefono_cliente' => $clientForm['telefono_cliente'] !== '' ? $clientForm['telefono_cliente'] : null,
                    ':direccion_cliente' => $clientForm['direccion_cliente'] !== '' ? $clientForm['direccion_cliente'] : null,
                    ':localidad_cliente' => $clientForm['localidad_cliente'] !== '' ? $clientForm['localidad_cliente'] : null,
                    ':provincia_cliente' => $clientForm['provincia_cliente'] !== '' ? $clientForm['provincia_cliente'] : null,
                    ':cuit_cliente' => $clientForm['cuit_cliente'] !== '' ? $clientForm['cuit_cliente'] : null,
                    ':id_cliente' => $clientEditId,
                    ':id_empresa' => current_empresa_id(),
                ]);

                $emailDelete = db()->prepare('DELETE FROM cliente_emails WHERE id_cliente = :id_cliente AND id_empresa = :id_empresa');
                $emailDelete->execute([
                    ':id_cliente' => $clientEditId,
                    ':id_empresa' => current_empresa_id(),
                ]);

                if ($clientForm['emails_cliente'] !== ['']) {
                    $emailInsert = db()->prepare(
                        'INSERT INTO cliente_emails (id_empresa, id_cliente, email_cliente, principal_email, activo_email) VALUES (:id_empresa, :id_cliente, :email_cliente, :principal_email, 1)'
                    );
                    foreach ($clientForm['emails_cliente'] as $index => $emailCliente) {
                        $emailInsert->execute([
                            ':id_empresa' => current_empresa_id(),
                            ':id_cliente' => $clientEditId,
                            ':email_cliente' => $emailCliente,
                            ':principal_email' => $index === 0 ? 1 : 0,
                        ]);
                    }
                }
            } else {
                $statement = db()->prepare(
                    'UPDATE clientes SET nombre_cliente = :nombre_cliente, telefono_cliente = :telefono_cliente, direccion_cliente = :direccion_cliente, localidad_cliente = :localidad_cliente, provincia_cliente = :provincia_cliente, mail_cliente = :mail_cliente, cuit_cliente = :cuit_cliente WHERE id_cliente = :id_cliente AND id_empresa = :id_empresa'
                );
                $statement->execute([
                    ':nombre_cliente' => $clientForm['nombre_cliente'],
                    ':telefono_cliente' => $clientForm['telefono_cliente'] !== '' ? $clientForm['telefono_cliente'] : null,
                    ':direccion_cliente' => $clientForm['direccion_cliente'] !== '' ? $clientForm['direccion_cliente'] : null,
                    ':localidad_cliente' => $clientForm['localidad_cliente'] !== '' ? $clientForm['localidad_cliente'] : null,
                    ':provincia_cliente' => $clientForm['provincia_cliente'] !== '' ? $clientForm['provincia_cliente'] : null,
                    ':mail_cliente' => $primaryClientEmail !== '' ? $primaryClientEmail : null,
                    ':cuit_cliente' => $clientForm['cuit_cliente'] !== '' ? $clientForm['cuit_cliente'] : null,
                    ':id_cliente' => $clientEditId,
                    ':id_empresa' => current_empresa_id(),
                ]);
            }

            db()->commit();

            $successMessage = 'Cliente actualizado correctamente.';
            $_SESSION['success_message'] = $successMessage;
            $clientEditId = null;
            $clientForm = ['nombre_cliente' => '', 'telefono_cliente' => '', 'direccion_cliente' => '', 'localidad_cliente' => '', 'provincia_cliente' => '', 'emails_cliente' => [''], 'cuit_cliente' => ''];

            if (($page ?? 'dashboard') !== 'clientes') {
                header('Location: /index.php?page=clientes');
                exit;
            }
        } catch (Throwable $throwable) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            if ((int) $throwable->getCode() === 23000) {
                $errors[] = 'Ya existe un cliente con ese CUIT.';
            } else {
                $errors[] = 'No fue posible actualizar el cliente.';
            }
        }
    }
}
