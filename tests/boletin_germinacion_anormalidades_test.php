<?php

declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../includes/format_helpers.php';
require __DIR__ . '/../includes/boletin_germinacion.php';

function verificar_seleccion(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

function mostrar_boletin_anormalidades(string $tipo, bool $edicion, ?array $seleccionPost = null): string
{
    $boletinGerminacionTipo = $tipo;
    $boletinGerminacionPagina = 'boletin_' . $tipo;
    $boletinGerminacionTitulo = 'Prueba';
    $errors = [];
    $successMessage = null;
    $boletinGerminacionEditId = $seleccionPost !== null ? 1 : null;
    $boletinGerminacionForm = array_fill_keys([
        'id_cliente', 'id_especie', 'analisis_nro', 'muestra_nro', 'fecha_siembra', 'fecha_primer_conteo',
        'fecha_segundo_conteo', 'analista', 'pretratamiento_desde', 'pretratamiento_hasta', 'pretratamiento_total_dias',
        'pretratamiento_aplica', 'sustrato_tipo', 'sustrato_otro', 'temperatura_camara_tipo', 'temperatura_camara_otra', 'observaciones',
    ], '');
    foreach (['pretratamiento_frio', 'pretratamiento_nitrato_k', 'pretratamiento_presecado', 'pretratamiento_prelavado'] as $campo) {
        $boletinGerminacionForm[$campo] = false;
    }
    $boletinGerminacionForm['id_anormalidades'] = $seleccionPost ?? [];
    $repeticion = array_fill_keys([
        'semillas_sembradas', 'primer_conteo_normales', 'primer_conteo_anormales', 'primer_conteo_duras',
        'primer_conteo_frescas', 'primer_conteo_muertas', 'segundo_conteo_normales', 'segundo_conteo_anormales',
        'segundo_conteo_duras', 'segundo_conteo_frescas', 'segundo_conteo_muertas',
    ], '');
    $boletinGerminacionForm['repeticiones'] = array_fill(1, 4, $repeticion);
    $_GET = $edicion ? ['editar' => '1'] : [];
    $_SERVER['REQUEST_METHOD'] = $seleccionPost !== null ? 'POST' : 'GET';
    ob_start();
    try {
        require __DIR__ . '/../pages/boletin_germinacion.php';
        $html = ob_get_contents();
    } finally {
        ob_end_clean();
    }
    verificar_seleccion($errors === [], 'No se pudo renderizar el formulario: ' . implode(' | ', $errors));
    return $html;
}

verificar_seleccion(boletin_germinacion_leer_anormalidades(['1', '2', '1']) === [1, 2], 'Debe aceptar varias selecciones sin duplicados.');
foreach (['1', ['0'], ['-1'], ['abc'], [['1']]] as $invalida) {
    try {
        boletin_germinacion_leer_anormalidades($invalida);
        throw new RuntimeException('Debe rechazarse una selección inválida.');
    } catch (InvalidArgumentException $exception) {
    }
}

$connection = db();
$tablas = ['clientes', 'especies', 'boletines_germinacion', 'boletin_germinacion_repeticiones', 'anormalidades', 'boletin_germinacion_anormalidades'];
try {
    foreach ($tablas as $tabla) {
        $definition = $connection->query("SHOW CREATE TABLE $tabla")->fetch(PDO::FETCH_NUM)[1];
        $definition = preg_replace('/,\s*CONSTRAINT\s+`[^`]+`[^\n,]*/', '', $definition);
        $definition = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $definition);
        $connection->exec($definition);
    }
    $connection->exec("INSERT INTO clientes (id_cliente, id_empresa, nombre_cliente) VALUES (1, 1, 'Cliente de prueba')");
    $connection->exec("INSERT INTO especies (id_especie, id_empresa, nombre_especie) VALUES (1, 1, 'Especie de prueba')");
    $connection->exec("INSERT INTO boletines_germinacion (id_boletin_germinacion, id_empresa, id_cliente, id_especie, tipo_boletin, muestra_nro) VALUES (1, 1, 1, 1, 'germinacion', 'Prueba')");
    $connection->exec("INSERT INTO anormalidades (id_anormalidad, id_empresa, codigo, etiqueta, grupo_tipo, categoria_principal, subcategoria) VALUES
        (1, 1, '00/01', '00/01 Plántula <deforme>', 'Grupo', 'Categoría', 'Subcategoría'),
        (2, 1, '11/01', '11/01 Raíz dañada', 'Grupo', 'Categoría', 'Subcategoría'),
        (3, 2, '99/01', 'Etiqueta de otra empresa', 'Grupo', 'Categoría', 'Subcategoría')");
    $_SESSION['user'] = ['id_empresa' => 1];
    $connection->beginTransaction();
    boletin_germinacion_guardar_anormalidades($connection, 1, 1, [1, 2]);
    $guardadas = $connection->query('SELECT id_anormalidad FROM boletin_germinacion_anormalidades WHERE id_boletin_germinacion = 1 ORDER BY id_anormalidad')->fetchAll(PDO::FETCH_COLUMN);
    verificar_seleccion(array_map('intval', $guardadas) === [1, 2], 'Deben guardarse ambas selecciones.');

    $html = mostrar_boletin_anormalidades('germinacion', true);
    $repeticionesPos = strpos($html, '>Repeticiones</h3>');
    $selectorPos = strpos($html, 'Elegir anormalidades');
    $observacionesPos = strpos($html, 'for="germ_observaciones"');
    verificar_seleccion($repeticionesPos !== false && $selectorPos > $repeticionesPos && $observacionesPos > $selectorPos, 'El selector debe aparecer entre las repeticiones y las observaciones.');
    verificar_seleccion(substr_count($html, 'class="form-check-input germ-anormalidad-check"') === 2, 'Solo deben ofrecerse las anormalidades de la empresa activa.');
    verificar_seleccion((bool) preg_match('/id="germ_anormalidad_1"[^>]*checked/s', $html) && (bool) preg_match('/id="germ_anormalidad_2"[^>]*checked/s', $html), 'La edición debe recuperar ambas selecciones.');
    verificar_seleccion(str_contains($html, '&lt;deforme&gt;') && !str_contains($html, 'Etiqueta de otra empresa'), 'Debe escapar las etiquetas y aislar empresas.');
    $htmlPost = mostrar_boletin_anormalidades('germinacion', true, [2]);
    verificar_seleccion(!(bool) preg_match('/id="germ_anormalidad_1"[^>]*checked/s', $htmlPost) && (bool) preg_match('/id="germ_anormalidad_2"[^>]*checked/s', $htmlPost), 'Un POST con errores debe conservar lo seleccionado.');
    $htmlCold = mostrar_boletin_anormalidades('cold_test', false);
    verificar_seleccion(!str_contains($htmlCold, 'germAnormalidadesModal'), 'Cold Test no debe mostrar el selector.');

    $connection->exec('SAVEPOINT seleccion_valida');
    try {
        boletin_germinacion_guardar_anormalidades($connection, 1, 1, [1, 3]);
        throw new RuntimeException('Debe rechazarse una anormalidad de otra empresa.');
    } catch (InvalidArgumentException $exception) {
        $connection->exec('ROLLBACK TO SAVEPOINT seleccion_valida');
    }
    $guardadas = $connection->query('SELECT id_anormalidad FROM boletin_germinacion_anormalidades WHERE id_boletin_germinacion = 1 ORDER BY id_anormalidad')->fetchAll(PDO::FETCH_COLUMN);
    verificar_seleccion(array_map('intval', $guardadas) === [1, 2], 'Un error debe permitir revertir sin perder las selecciones anteriores.');
    boletin_germinacion_guardar_anormalidades($connection, 1, 1, [2]);
    verificar_seleccion((int) $connection->query('SELECT COUNT(*) FROM boletin_germinacion_anormalidades')->fetchColumn() === 1, 'La edición debe reemplazar la selección.');
    boletin_germinacion_guardar_anormalidades($connection, 1, 1, []);
    verificar_seleccion((int) $connection->query('SELECT COUNT(*) FROM boletin_germinacion_anormalidades')->fetchColumn() === 0, 'Debe poder quitarse toda la selección.');
} finally {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    foreach ($tablas as $tabla) {
        $connection->exec("DROP TEMPORARY TABLE IF EXISTS $tabla");
    }
}

echo "OK: selección múltiple, edición, aislamiento y formulario de Germinación.\n";
