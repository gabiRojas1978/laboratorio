<?php

declare(strict_types=1);

if (
    is_logged_in()
    && $_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_GET['ingreso_id'])
    && is_string($_GET['page'] ?? null)
    && isset($navigation['certificados']['items'][$_GET['page']])
) {
    try {
        $ingresoCertificado = libro_ingreso_para_certificado($_GET['ingreso_id'], current_empresa_id());
        $seleccion = [];
        foreach (['germinacion', 'germinacion_curado', 'pureza', 'peso', 'malezas', 'tetrazolio', 'cold_test', 'envejecimiento'] as $fuente) {
            if (isset($_GET['boletin_' . $fuente]) && $_GET['boletin_' . $fuente] !== '') {
                $seleccion[$fuente] = $_GET['boletin_' . $fuente];
            }
        }
        $seleccionAvisos = [];
        if ($seleccion === [] && isset($_GET['auto'])) {
            [$seleccion, $seleccionAvisos] = libro_ingreso_seleccion_automatica($ingresoCertificado, current_empresa_id(), $_GET['page']);
        }
        [$certForm, $certPrecargaAvisos] = libro_ingreso_importar_certificado(
            $certForm, $ingresoCertificado, current_empresa_id(), $_GET['page'], $seleccion
        );
        $certPrecargaAvisos = array_merge($seleccionAvisos, $certPrecargaAvisos);
    } catch (InvalidArgumentException $exception) {
        $errors[] = $exception->getMessage();
    } catch (PDOException $exception) {
        $errors[] = 'No se pudieron cargar los datos del ingreso o los boletines para el certificado.';
    }
}
