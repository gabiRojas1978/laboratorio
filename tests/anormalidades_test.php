<?php

declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../includes/navigation.php';
require __DIR__ . '/../includes/anormalidades.php';

function verificar_anormalidad(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function enviar_anormalidad(array $post): array
{
    $errors = [];
    $successMessage = null;
    $anormalidadForm = array_fill_keys(['codigo', 'etiqueta', 'grupo_tipo', 'categoria_principal', 'subcategoria'], '');
    $anormalidadEditId = null;
    $page = 'anormalidades';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = $post;
    require __DIR__ . '/../handlers/anormalidades.php';
    return [$errors, $anormalidadForm, $anormalidadEditId, $successMessage];
}

function mostrar_anormalidades(array $query, ?array $postResult = null): array
{
    [$errors, $anormalidadForm, $anormalidadEditId, $successMessage] = $postResult
        ?? [[], array_fill_keys(['codigo', 'etiqueta', 'grupo_tipo', 'categoria_principal', 'subcategoria'], ''), null, null];
    $_GET = $query;
    $_SERVER['REQUEST_METHOD'] = $postResult === null ? 'GET' : 'POST';
    ob_start();
    try {
        require __DIR__ . '/../pages/anormalidades.php';
        $html = ob_get_contents();
    } finally {
        ob_end_clean();
    }
    return [$errors, $anormalidadForm, $anormalidadEditId, $html];
}

verificar_anormalidad(($pageTemplates['anormalidades'] ?? '') === 'anormalidades.php', 'Falta la ruta de Anormalidades.');
$migration = file_get_contents(__DIR__ . '/../database/migrations/2026-11-18_anormalidades.sql');
if ($migration === false) {
    throw new RuntimeException('No se pudo leer la migración.');
}
// Las tablas temporales aíslan la migración de las tablas reales de la aplicación.
$migration = str_replace(
    ["CREATE TABLE anormalidades_modelo (", "CREATE TABLE anormalidades (",
        ",\n    CONSTRAINT fk_anormalidad_empresa FOREIGN KEY (id_empresa)\n        REFERENCES empresas(id_empresa) ON DELETE CASCADE"],
    ["CREATE TEMPORARY TABLE anormalidades_modelo (", "CREATE TEMPORARY TABLE anormalidades (", ''],
    str_replace("\r\n", "\n", $migration)
);

$connection = db();
$connection->exec('CREATE TEMPORARY TABLE empresas (id_empresa INT PRIMARY KEY) ENGINE=InnoDB');
try {
    $connection->exec('INSERT INTO empresas (id_empresa) VALUES (1), (2)');
    foreach (explode(';', $migration) as $statement) {
        if (trim($statement) !== '') {
            $connection->exec($statement);
        }
    }
    $rows = $connection->query('SELECT * FROM anormalidades WHERE id_empresa = 1 ORDER BY codigo')->fetchAll();
    $expectedCodes = [];
    foreach (['00' => [1, 2, 3, 4, 6, 7, 8, 9, 10, 11], '11' => range(1, 12),
        '21' => [1, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13], '22' => range(1, 5),
        '31' => range(1, 8), '33' => [1, 2, 3, 4, 5, 7], '41' => range(1, 12), '42' => range(1, 6)] as $prefix => $numbers) {
        foreach ($numbers as $number) {
            $expectedCodes[] = sprintf('%02d/%02d', $prefix, $number);
        }
    }
    verificar_anormalidad(count($rows) === 71 && array_column($rows, 'codigo') === $expectedCodes, 'La migración debe cargar exactamente los 71 códigos proporcionados.');
    verificar_anormalidad((int) $connection->query('SELECT COUNT(*) FROM anormalidades WHERE id_empresa = 2')->fetchColumn() === 71, 'Cada empresa debe recibir su catálogo.');
    $byCode = array_column($rows, null, 'codigo');
    verificar_anormalidad($byCode['22/05']['etiqueta'] === '22/05 Yema terminal y tejidos circundantes podrida como consecuencia de infección primaria', 'La etiqueta larga debe copiarse completa.');
    foreach ([
        '00/01' => ['Dicotiledóneas / General', '0. Anormalidades generales', '00 Plántula'],
        '11/01' => ['Dicotiledóneas / General', '1. Anormalidades del sistema radicular', '11 Raíz primaria'],
        '21/01' => ['Dicotiledóneas / General', '2. Anormalidades del sistema de tallo', '21 Hipocótilo y/o epicótilo'],
        '22/01' => ['Dicotiledóneas / General', '2. Anormalidades del sistema de tallo', '22 Yema terminal y tejidos circundantes'],
        '31/01' => ['Dicotiledóneas', '3. Anormalidades de Cotiledones y/o hojas primarias', '31 Cotiledones (aplica la regla del 50 %)'],
        '33/01' => ['Dicotiledóneas', '3. Anormalidades de Cotiledones y/o hojas primarias', '33 Hojas Primarias (aplica la regla del 50 %)'],
        '41/01' => ['Monocotiledóneas', '4. Anormalidades de Coleoptilo y Hoja Primaria', '41 Coleoptilo'],
        '42/01' => ['Monocotiledóneas', '4. Anormalidades de Coleoptilo y Hoja Primaria', '42 Hoja primaria'],
    ] as $code => $expected) {
        verificar_anormalidad([$byCode[$code]['grupo_tipo'], $byCode[$code]['categoria_principal'], $byCode[$code]['subcategoria']] === $expected, 'Clasificación incorrecta: ' . $code);
    }

    $_SESSION['user'] = ['id_empresa' => 1];
    $post = ['save_anormalidad' => '1', 'codigo' => '99/01', 'etiqueta' => '99/01 <Prueba>', 'grupo_tipo' => 'Grupo', 'categoria_principal' => 'Categoría', 'subcategoria' => 'Subcategoría'];
    foreach (['codigo', 'etiqueta', 'grupo_tipo', 'categoria_principal', 'subcategoria'] as $field) {
        [$errors, , , $success] = enviar_anormalidad(array_replace($post, [$field => '  ']));
        verificar_anormalidad($errors !== [] && $success === null, 'Debe rechazar el campo vacío: ' . $field);
    }
    [$errors] = enviar_anormalidad(array_replace($post, ['codigo' => str_repeat('x', 31)]));
    verificar_anormalidad($errors !== [], 'Debe rechazar un código demasiado largo.');
    [$errors, , , $success] = enviar_anormalidad($post);
    verificar_anormalidad($errors === [] && $success !== null, 'Debe crear una anormalidad.');
    $id = (int) $connection->lastInsertId();
    [$errors] = enviar_anormalidad($post);
    verificar_anormalidad(count($errors) === 1 && str_contains($errors[0], 'Ya existe'), 'Debe rechazar códigos duplicados.');
    [$errors, $form, $editId, $html] = mostrar_anormalidades(['edit' => (string) $id]);
    verificar_anormalidad($errors === [] && $editId === $id && $form['etiqueta'] === $post['etiqueta'], 'Debe precargar la edición.');
    verificar_anormalidad(str_contains($html, '&lt;Prueba&gt;') && !str_contains($html, '<Prueba>'), 'Debe escapar los valores mostrados.');
    $update = array_replace($post, ['update_anormalidad' => '1', 'anormalidad_edit_id' => (string) $id, 'etiqueta' => 'Modificada']);
    unset($update['save_anormalidad']);
    [$errors] = enviar_anormalidad($update);
    verificar_anormalidad($errors === [], 'Debe actualizar la anormalidad.');
    $check = $connection->prepare('SELECT etiqueta FROM anormalidades WHERE id_anormalidad = ?');
    $check->execute([$id]);
    verificar_anormalidad($check->fetchColumn() === 'Modificada', 'Debe persistir la edición.');
    [$errors] = enviar_anormalidad(array_replace($update, ['codigo' => '00/01']));
    verificar_anormalidad(count($errors) === 1 && str_contains($errors[0], 'Ya existe'), 'Debe rechazar códigos duplicados al editar.');
    $failed = enviar_anormalidad(array_replace($update, ['subcategoria' => '']));
    [$errors, $form, $editId] = mostrar_anormalidades(['edit' => (string) $id], $failed);
    verificar_anormalidad($errors !== [] && $form['subcategoria'] === '' && $editId === $id, 'Debe conservar el formulario después de un error.');

    $seedUpdate = [
        'update_anormalidad' => '1', 'anormalidad_edit_id' => (string) $byCode['00/01']['id_anormalidad'],
        'codigo' => '00/01', 'etiqueta' => 'Etiqueta modificada', 'grupo_tipo' => $byCode['00/01']['grupo_tipo'],
        'categoria_principal' => $byCode['00/01']['categoria_principal'], 'subcategoria' => $byCode['00/01']['subcategoria'],
    ];
    [$errors] = enviar_anormalidad($seedUpdate);
    verificar_anormalidad($errors === [], 'Debe poder editarse un registro inicial.');
    $_SESSION['user']['id_empresa'] = 2;
    [$errors] = enviar_anormalidad($update);
    verificar_anormalidad($errors !== [], 'No debe permitir editar una anormalidad de otra empresa.');
    [$errors, , , $html] = mostrar_anormalidades(['edit' => (string) $id]);
    verificar_anormalidad($errors !== [] && !str_contains($html, 'Modificada'), 'No debe mostrar anormalidades de otra empresa.');
    [$errors] = enviar_anormalidad($post);
    verificar_anormalidad($errors === [], 'Distintas empresas pueden usar el mismo código.');
    verificar_anormalidad($connection->query("SELECT etiqueta FROM anormalidades WHERE id_empresa = 2 AND codigo = '00/01'")->fetchColumn() === '00/01 Plántula deforme', 'Editar el catálogo de una empresa no debe modificar otra.');

    $connection->exec('INSERT INTO empresas (id_empresa) VALUES (3)');
    anormalidades_inicializar_empresa($connection, 3);
    $model = $connection->query("SELECT etiqueta FROM anormalidades WHERE id_empresa = 3 AND codigo = '00/01'")->fetchColumn();
    verificar_anormalidad($model === '00/01 Plántula deforme' && (int) $connection->query('SELECT COUNT(*) FROM anormalidades WHERE id_empresa = 3')->fetchColumn() === 71, 'Una empresa nueva debe recibir la plantilla original.');

    unset($_SESSION['user']);
    [, , , $success] = enviar_anormalidad(array_replace($update, ['etiqueta' => 'Cambio sin sesión']));
    $check->execute([$id]);
    verificar_anormalidad($success === null && $check->fetchColumn() === 'Modificada', 'No debe guardar sin sesión.');
} finally {
    $connection->exec('DROP TEMPORARY TABLE IF EXISTS anormalidades_importacion');
    $connection->exec('DROP TEMPORARY TABLE IF EXISTS anormalidades');
    $connection->exec('DROP TEMPORARY TABLE IF EXISTS anormalidades_modelo');
    $connection->exec('DROP TEMPORARY TABLE IF EXISTS empresas');
}

echo "OK: migración, datos, alta, edición, validación y aislamiento de anormalidades.\n";
