<?php

declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../includes/navigation.php';

function verificar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

function enviar_determinacion(array $datos): array
{
    $errors = [];
    $successMessage = null;
    $determinacionLibroForm = ['nombre_determinacion_libro' => '', 'costo' => '0.00'];
    $determinacionLibroEditId = null;
    $page = 'determinaciones';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = $datos;
    require __DIR__ . '/../handlers/determinaciones_libro_ingreso.php';

    return [$errors, $determinacionLibroForm, $determinacionLibroEditId, $successMessage];
}

function mostrar_determinaciones(array $query = [], ?array $postResult = null): array
{
    [$errors, $determinacionLibroForm, $determinacionLibroEditId, $successMessage] = $postResult ?? [[], ['nombre_determinacion_libro' => '', 'costo' => '0.00'], null, null];
    $_GET = $query;
    $_SERVER['REQUEST_METHOD'] = $postResult === null ? 'GET' : 'POST';
    ob_start();
    try {
        require __DIR__ . '/../pages/determinaciones_libro_ingreso.php';
        $html = ob_get_contents();
    } finally {
        ob_end_clean();
    }

    return [$errors, $determinacionLibroForm, $determinacionLibroEditId, $html];
}

verificar(($navigation['administracion']['items']['determinaciones'] ?? '') === 'Determinaciones', 'Debe mostrar Determinaciones en Administración.');
verificar(!isset($navigation['administracion']['items']['ensayos'], $pageMap['ensayos']), 'Debe descartar la pantalla de ensayos.');
verificar($pageTemplates['determinaciones'] === 'determinaciones_libro_ingreso.php', 'Debe usar la pantalla del catálogo compartido.');
$empresas = array_map('intval', db()->query('SELECT id_empresa FROM empresas ORDER BY id_empresa LIMIT 2')->fetchAll(PDO::FETCH_COLUMN));
verificar(count($empresas) === 2, 'Se necesitan dos empresas para verificar aislamiento.');
$_SESSION['user'] = ['id_empresa' => $empresas[0]];
$nombre = 'Determinación de prueba ' . bin2hex(random_bytes(8));
$alta = ['save_determinacion_libro' => '1', 'nombre_determinacion_libro' => $nombre, 'costo' => '1234,56'];
$consultar = db()->prepare('SELECT * FROM determinaciones_libro_ingreso WHERE id_determinacion_libro = ?');

db()->beginTransaction();
try {
    foreach (['', '   ', str_repeat('á', 256), ['inválido']] as $invalido) {
        [$errors, , , $success] = enviar_determinacion(array_replace($alta, ['nombre_determinacion_libro' => $invalido]));
        verificar(count($errors) === 1 && $success === null, 'Debe rechazar nombres inválidos.');
    }
    foreach (['-1', 'abc', '1.234', '1,234', '1.234,56', '1e3', '10000000000', 'NaN', ['12']] as $invalido) {
        [$errors, $form, , $success] = enviar_determinacion(array_replace($alta, ['costo' => $invalido]));
        verificar(count($errors) === 1 && $success === null, 'Debe rechazar importes inválidos sin redondearlos.');
        verificar($form['costo'] === (is_string($invalido) ? $invalido : ''), 'Debe conservar el costo inválido para corregirlo.');
    }

    [$errors, $form, , $success] = enviar_determinacion(array_replace($alta, ['nombre_determinacion_libro' => '  ' . $nombre . '  ']));
    verificar($errors === [] && $success !== null, 'Debe crear la determinación: ' . implode(' | ', $errors));
    $id = (int) db()->lastInsertId();
    $consultar->execute([$id]);
    $row = $consultar->fetch();
    verificar($row['nombre_determinacion_libro'] === $nombre && (int) $row['id_empresa'] === $empresas[0] && $row['costo'] === '1234.56', 'Debe persistir el nombre, costo decimal exacto y empresa.');
    verificar($form === ['nombre_determinacion_libro' => '', 'costo' => '0.00'], 'Debe limpiar el formulario.');
    [$errors, , , $success] = enviar_determinacion($alta);
    verificar(count($errors) === 1 && strpos($errors[0], 'Ya existe') !== false && $success === null, 'Debe informar duplicados.');

    $edicion = ['update_determinacion_libro' => '1', 'determinacion_libro_edit_id' => (string) $id, 'nombre_determinacion_libro' => $nombre . ' <modificado>', 'costo' => '9999999999.99'];
    foreach (['0' => '0.00', '' => '0.00', '0,01' => '0.01', '12.3' => '12.30', '9999999999.99' => '9999999999.99'] as $costo => $esperado) {
        [$errors] = enviar_determinacion(array_replace($edicion, ['costo' => (string) $costo]));
        verificar($errors === [], 'Debe permitir costos válidos.');
        $consultar->execute([$id]);
        verificar($consultar->fetch()['costo'] === $esperado, 'Debe guardar el costo exacto: ' . $esperado);
    }
    [$errors] = enviar_determinacion($edicion);
    verificar($errors === [], 'Debe permitir guardar sin cambios.');

    [$errors, $form, $editId, $html] = mostrar_determinaciones(['edit' => (string) $id]);
    verificar($errors === [] && $editId === $id && $form['nombre_determinacion_libro'] === $edicion['nombre_determinacion_libro'] && $form['costo'] === '9999999999.99', 'Debe precargar nombre y costo.');
    verificar(strpos($html, '&lt;modificado&gt;') !== false && strpos($html, '<modificado>') === false, 'Debe escapar los nombres.');
    verificar(strpos($html, '9.999.999.999,99') !== false && strpos($html, 'name="costo"') !== false, 'Debe mostrar costo en listado y formulario.');

    $fallo = enviar_determinacion(array_replace($edicion, ['costo' => '-3']));
    [$errors, $form, $editId] = mostrar_determinaciones(['edit' => (string) $id], $fallo);
    verificar(count($errors) === 1 && $form['costo'] === '-3' && $editId === $id, 'Debe conservar la edición y costo tras un error.');
    foreach (['0', '-1', 'abc', '1.5', '2147483647'] as $invalido) {
        [$errors, , , $success] = enviar_determinacion(array_replace($edicion, ['determinacion_libro_edit_id' => $invalido]));
        verificar(count($errors) === 1 && $success === null, 'Debe rechazar IDs inválidos o inexistentes.');
    }

    $_SESSION['user']['id_empresa'] = $empresas[1];
    [$errors, , , $success] = enviar_determinacion($edicion);
    verificar(count($errors) === 1 && $success === null, 'No debe editar determinaciones ajenas.');
    [$errors, , $editId, $html] = mostrar_determinaciones(['edit' => (string) $id]);
    verificar(count($errors) === 1 && $editId === null && strpos($html, $nombre) === false, 'No debe precargar ni listar determinaciones ajenas.');
    [$errors] = enviar_determinacion($alta);
    verificar($errors === [], 'Dos empresas pueden usar el mismo nombre.');

    $_SESSION['user']['id_empresa'] = $empresas[0];
    [$errors] = enviar_determinacion($alta);
    verificar($errors === [], 'Debe permitir crear otra determinación.');
    $duplicadoId = (int) db()->lastInsertId();
    [$errors] = enviar_determinacion(array_replace($edicion, ['nombre_determinacion_libro' => $nombre]));
    verificar(count($errors) === 1 && strpos($errors[0], 'Ya existe') !== false, 'Debe rechazar duplicados al editar.');
    [$errors] = enviar_determinacion(['update_determinacion_libro' => '1', 'determinacion_libro_edit_id' => (string) $duplicadoId, 'nombre_determinacion_libro' => str_repeat('á', 255)]);
    $consultar->execute([$duplicadoId]);
    verificar($errors === [] && $consultar->fetch()['costo'] === '0.00', 'Debe aceptar 255 caracteres y guardar costo omitido como cero.');

    unset($_SESSION['user']);
    [, , , $success] = enviar_determinacion(array_replace($edicion, ['nombre_determinacion_libro' => 'Cambio sin sesión', 'costo' => '1']));
    $consultar->execute([$id]);
    $row = $consultar->fetch();
    verificar($success === null && $row['nombre_determinacion_libro'] === $edicion['nombre_determinacion_libro'] && $row['costo'] === $edicion['costo'], 'No debe guardar sin sesión.');
} finally {
    db()->rollBack();
}

echo 'OK: navegación, catálogo compartido, alta, edición, costos exactos, validación, duplicados, pantalla y aislamiento; pruebas revertidas.' . PHP_EOL;
