<?php

declare(strict_types=1);

if (is_logged_in() && !is_client_user() && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['presupuesto_pdf'])) {
    $id = filter_var($_GET['presupuesto_pdf'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        http_response_code(400);
        exit('Ingreso inválido.');
    }
    try {
        $presupuesto = presupuesto_obtener($id);
        if ($presupuesto === null) {
            http_response_code(404);
            exit('Ingreso no encontrado.');
        }
        $pdf = presupuesto_pdf($presupuesto, get_certificate_settings(current_empresa_id()), certificate_logo_path(current_empresa_id()));
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $pdf['filename'] . '"');
        header('Cache-Control: no-store');
        echo $pdf['content'];
    } catch (RuntimeException | PDOException | \Mpdf\MpdfException $exception) {
        error_log('Error al generar presupuesto: ' . $exception->getMessage());
        http_response_code(500);
        echo 'No fue posible generar el presupuesto. Verifica los datos del laboratorio y las determinaciones.';
    }
    exit;
}

if (is_logged_in() && !is_client_user() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_presupuesto'])) {
    $id = filter_var($_POST['ingreso_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $token = $_POST['presupuesto_token'] ?? '';
    $emailsRaw = $_POST['destinatarios'] ?? [];
    $destinatariosValidos = is_array($emailsRaw) && count(array_filter($emailsRaw, 'is_string')) === count($emailsRaw);
    $destinatarios = $destinatariosValidos ? normalize_client_emails($emailsRaw) : [];
    if ($id === false || !is_string($token) || !isset($_SESSION['presupuesto_token']) || !hash_equals($_SESSION['presupuesto_token'], $token)) {
        $errors[] = 'Solicitud de presupuesto inválida. Recarga la página e inténtalo nuevamente.';
    } else {
        try {
            $presupuesto = presupuesto_obtener($id);
            if ($presupuesto === null) {
                throw new RuntimeException('El ingreso no existe o no pertenece al laboratorio.');
            }
            $emailsCliente = get_cliente_emails((int) $presupuesto['ingreso']['id_cliente']);
            if ($destinatarios === [] || array_diff($destinatarios, $emailsCliente) !== []) {
                throw new RuntimeException('Selecciona al menos un correo registrado para este cliente.');
            }
            foreach ($destinatarios as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Uno de los correos seleccionados no es válido.');
                }
            }
            $smtp = get_smtp_settings(current_empresa_id());
            if (!smtp_is_configured($smtp)) {
                throw new RuntimeException('Configura el correo SMTP antes de enviar el presupuesto.');
            }
            $pdf = presupuesto_pdf($presupuesto, get_certificate_settings(current_empresa_id()), certificate_logo_path(current_empresa_id()));
            $mailer = smtp_create_mailer($smtp);
            foreach ($destinatarios as $email) {
                $mailer->addAddress($email);
            }
            $mailer->Subject = 'Presupuesto de análisis - muestra N° ' . $presupuesto['ingreso']['nro_muestra'];
            $mailer->Body = 'Adjuntamos el presupuesto de análisis de su muestra.';
            $mailer->addStringAttachment($pdf['content'], $pdf['filename'], 'base64', 'application/pdf');
            $mailer->send();
            $_SESSION['success_message'] = 'Presupuesto enviado correctamente.';
            header('Location: /index.php?page=libro_ingreso&presupuesto_id=' . $id . '#presupuesto');
            exit;
        } catch (PDOException | \Mpdf\MpdfException | \PHPMailer\PHPMailer\Exception $exception) {
            error_log('Error al enviar presupuesto: ' . $exception->getMessage());
            $errors[] = 'No fue posible enviar el presupuesto. Verifica la configuración de correo y vuelve a intentarlo.';
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }
    }
}
