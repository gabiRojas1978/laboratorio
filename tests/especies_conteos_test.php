<?php

declare(strict_types=1);

require __DIR__ . '/../config/config.php';

function verificar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

function enviar_especie(array $datos): array
{
    $errors = [];
    $speciesForm = ['nombre_especie' => '', 'cientifico_especie' => '', 'primer_conteo' => '', 'conteo_final' => ''];
    $speciesEditId = null;
    $page = 'especies';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = $datos;
    require __DIR__ . '/../handlers/especies.php';

    return [$errors, $speciesForm];
}

$empresas = array_map('intval', db()->query('SELECT id_empresa FROM empresas ORDER BY id_empresa LIMIT 2')->fetchAll(PDO::FETCH_COLUMN));
verificar(count($empresas) === 2, 'Se necesitan dos empresas para verificar el aislamiento.');
$_SESSION['user'] = ['id_empresa' => $empresas[0]];
$datos = [
    'save_especie' => '1',
    'nombre_especie' => 'Prueba conteos ' . bin2hex(random_bytes(8)),
    'cientifico_especie' => 'Nombre de prueba',
    'primer_conteo' => '5',
    'conteo_final' => '8',
];

db()->beginTransaction();
try {
    foreach (['primer_conteo', 'conteo_final'] as $campo) {
        foreach (['0', '-1', '1.5', 'abc', '65536'] as $invalido) {
            [$errors, $form] = enviar_especie(array_replace($datos, [$campo => $invalido]));
            verificar(count($errors) === 1, 'Debe rechazar ' . $campo . ': ' . $invalido);
            verificar($form[$campo] === $invalido, 'Debe conservar el valor inválido para corregirlo.');
        }
    }

    [$errors, $form] = enviar_especie($datos);
    verificar($errors === [], 'No se pudo dar de alta la especie: ' . implode(' | ', $errors));
    $id = (int) db()->lastInsertId();
    $consultar = db()->prepare('SELECT * FROM especies WHERE id_especie = ?');
    $consultar->execute([$id]);
    $row = $consultar->fetch();
    verificar((int) $row['primer_conteo'] === 5 && (int) $row['conteo_final'] === 8, 'Los conteos deben persistir.');
    verificar($form['primer_conteo'] === '' && $form['conteo_final'] === '', 'Debe limpiar los campos tras guardar.');

    $edicion = array_replace($datos, [
        'update_especie' => '1',
        'especie_edit_id' => (string) $id,
        'primer_conteo' => '4',
        'conteo_final' => '10',
    ]);
    unset($edicion['save_especie']);
    [$errors] = enviar_especie($edicion);
    verificar($errors === [], 'No se pudo editar la especie.');
    $consultar->execute([$id]);
    $row = $consultar->fetch();
    verificar((int) $row['primer_conteo'] === 4 && (int) $row['conteo_final'] === 10, 'Debe actualizar ambos conteos.');

    $errors = [];
    $successMessage = null;
    $speciesEditId = null;
    $speciesForm = [];
    $_GET = ['edit' => (string) $id];
    ob_start();
    try {
        require __DIR__ . '/../pages/especies.php';
        $html = ob_get_contents();
    } finally {
        ob_end_clean();
    }
    verificar($errors === [], 'La página de especies debe cargar sin errores.');
    verificar($speciesForm['primer_conteo'] === '4' && $speciesForm['conteo_final'] === '10', 'La edición debe precargar los conteos.');
    verificar(strpos($html, 'name="primer_conteo"') !== false && strpos($html, 'name="conteo_final"') !== false, 'Debe mostrar ambos campos.');
    verificar(strpos($html, '<td>4</td>') !== false && strpos($html, '<td>10</td>') !== false, 'El listado debe mostrar los conteos.');

    $_SESSION['user']['id_empresa'] = $empresas[1];
    enviar_especie(array_replace($edicion, ['primer_conteo' => '7']));
    $consultar->execute([$id]);
    verificar((int) $consultar->fetch()['primer_conteo'] === 4, 'No debe modificar especies de otra empresa.');

    $_SESSION['user']['id_empresa'] = $empresas[0];
    [$errors] = enviar_especie(array_replace($edicion, ['primer_conteo' => '', 'conteo_final' => '']));
    verificar($errors === [], 'Debe permitir vaciar los conteos.');
    $consultar->execute([$id]);
    $row = $consultar->fetch();
    verificar($row['primer_conteo'] === null && $row['conteo_final'] === null, 'Los campos vacíos deben guardarse como NULL.');

    $sinConteos = $datos;
    unset($sinConteos['primer_conteo'], $sinConteos['conteo_final']);
    [$errors] = enviar_especie($sinConteos);
    verificar($errors === [], 'Debe conservar compatibilidad con altas sin conteos.');
    $consultar->execute([(int) db()->lastInsertId()]);
    $row = $consultar->fetch();
    verificar($row['primer_conteo'] === null && $row['conteo_final'] === null, 'Los campos omitidos deben guardarse como NULL.');
} finally {
    db()->rollBack();
}

echo 'OK: alta, edición, validación, campos opcionales, página y aislamiento por empresa; datos de prueba revertidos.' . PHP_EOL;
