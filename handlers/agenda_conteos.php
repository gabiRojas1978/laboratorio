<?php

declare(strict_types=1);

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_conteo_realizado'])) {
    $token = $_POST['agenda_token'] ?? null;
    $idIngreso = filter_var($_POST['ingreso_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $tipo = $_POST['tipo_conteo'] ?? null;
    if (!is_string($token) || !isset($_SESSION['agenda_token']) || !hash_equals($_SESSION['agenda_token'], $token)) {
        $errors[] = 'La solicitud venció o no es válida. Recarga la agenda e intenta nuevamente.';
    } elseif ($idIngreso === false || !is_string($tipo) || !array_key_exists($tipo, agenda_conteos_tipos())) {
        $errors[] = 'El ingreso o el tipo de conteo no es válido.';
    } else {
        try {
            agenda_conteos_marcar_realizado(current_empresa_id(), $idIngreso, $tipo);
            $_SESSION['success_message'] = agenda_conteos_tipos()[$tipo] . ' marcado como realizado.';
            header('Location: /index.php?page=dashboard');
            exit;
        } catch (InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();
        } catch (PDOException $exception) {
            error_log('No se pudo marcar el conteo realizado: ' . $exception->getMessage());
            $errors[] = 'No se pudo guardar el conteo realizado. Intenta nuevamente.';
        }
    }
}
