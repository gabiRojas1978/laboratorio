<?php

declare(strict_types=1);

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['portal_pdf'])) {
    $id = filter_var($_GET['portal_pdf'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $tipo = $_GET['tipo_documento'] ?? '';
    if ($id === false || !is_string($tipo) || !isset(portal_tipos_documentos()[$tipo])) {
        http_response_code(400);
        exit('Documento inválido.');
    }
    try {
        $documento = portal_documento($tipo, $id, is_client_user() ? (int) $_SESSION['user']['id_cliente'] : null, is_client_user());
        if ($documento === null) {
            http_response_code(404);
            exit('Documento no disponible.');
        }
        $builder = portal_tipos_documentos()[$tipo][7];
        $pdf = $builder === 'build_boletin_germinacion_pdf' ? $builder($id, $tipo) : $builder($id);
        if ($pdf === null) {
            http_response_code(500);
            exit('No se pudo generar el documento.');
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . (isset($_GET['download']) ? 'attachment' : 'inline') . '; filename="' . $pdf['filename'] . '"');
        header('Content-Length: ' . strlen($pdf['content']));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo $pdf['content'];
        exit;
    } catch (PDOException | \Mpdf\MpdfException $exception) {
        error_log('Error al descargar documento del portal: ' . $exception->getMessage());
        http_response_code(500);
        exit('No se pudo cargar el documento. Inténtalo nuevamente.');
    }
}

if (is_logged_in() && !is_client_user() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['portal_publicar'])) {
    $id = filter_var($_POST['id_documento'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $tipo = $_POST['tipo_documento'] ?? '';
    $accion = $_POST['portal_publicar'];
    $token = $_POST['portal_token'] ?? '';
    if (!is_string($token) || !isset($_SESSION['portal_token']) || !hash_equals($_SESSION['portal_token'], $token)) {
        $errors[] = 'Solicitud inválida. Recarga la página e inténtalo nuevamente.';
    } elseif ($id === false || !is_string($tipo) || !isset(portal_tipos_documentos()[$tipo]) || !in_array($accion, ['publicar', 'retirar'], true)) {
        $errors[] = 'Documento o acción inválidos.';
    } else {
        try {
            if (portal_documento($tipo, $id, null, false) === null) {
                $errors[] = 'El documento no existe o no pertenece al laboratorio.';
            } else {
                $sql = $accion === 'publicar'
                    ? 'INSERT INTO portal_documentos (id_empresa, tipo_documento, id_documento) VALUES (:empresa, :tipo, :id) ON DUPLICATE KEY UPDATE fecha_publicacion = fecha_publicacion'
                    : 'DELETE FROM portal_documentos WHERE id_empresa = :empresa AND tipo_documento = :tipo AND id_documento = :id';
                $statement = db()->prepare($sql);
                $statement->execute([':empresa' => current_empresa_id(), ':tipo' => $tipo, ':id' => $id]);
                $_SESSION['success_message'] = $accion === 'publicar' ? 'Documento habilitado para el cliente.' : 'Documento retirado del portal.';
                $query = array_intersect_key($_GET, array_flip(['portal_page', 'tipo_documento', 'cliente', 'categoria_documento', 'fecha_desde', 'fecha_hasta']));
                $query['page'] = 'portal_clientes';
                header('Location: /index.php?' . http_build_query($query));
                exit;
            }
        } catch (PDOException $exception) {
            error_log('Error al publicar documento: ' . $exception->getMessage());
            $errors[] = 'No se pudo cambiar la publicación del documento.';
        }
    }
}
