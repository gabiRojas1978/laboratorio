<?php

declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../includes/boletin_edicion.php';
require __DIR__ . '/../includes/boletin_peso_1000_semillas.php';
require __DIR__ . '/../includes/boletin_tetrazolio.php';
require __DIR__ . '/../includes/boletin_tetrazolio_soja.php';
require __DIR__ . '/../includes/boletin_pureza.php';
require __DIR__ . '/../includes/format_helpers.php';

function verificar_edicion(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

function formulario_edicion(string $tipo): array
{
    if ($tipo === 'tetrazolio_soja') {
        return boletin_tetrazolio_soja_form_vacio();
    }
    $form = array_fill_keys([
        'id_cliente', 'id_especie', 'muestra_nro', 'analisis_nro', 'fecha_boletin',
        'fecha_inicio', 'fecha_finalizacion', 'fecha_analisis', 'analista', 'observaciones',
        'tiempo_humedecimiento', 'tiempo_tincion', 'temperatura_tincion', 'concentracion_tz',
        'peso_inicial', 'peso_semilla_pura', 'otras_determinaciones_texto',
        'especie_remitente', 'especie_latin', 'lote', 'peso_analisis',
    ], '');
    $form['tipo_analisis'] = 'completo';
    $form['id_otras_determinaciones'] = [];
    $form['pesos'] = array_fill(0, 8, '');
    $form['repeticiones'] = array_fill(1, 4, ['viables' => '', 'no_viables' => '']);
    $form['componentes_materia_inerte'] = [['nombre' => '', 'peso' => '']];
    $form['componentes_otras_semillas'] = [['nombre' => '', 'peso' => '']];
    $form['especies_halladas'] = [['especie_latin' => '', 'numero_semillas' => '']];
    return $form;
}

function variable_formulario_edicion(string $tipo): string
{
    return [
        'peso_1000_semillas' => 'boletinPesoForm',
        'tetrazolio' => 'boletinTetrazolioForm',
        'tetrazolio_soja' => 'boletinTetrazolioSojaForm',
        'pureza' => 'boletinPurezaForm',
        'malezas' => 'boletinMalezasForm',
    ][$tipo];
}

// Los handlers terminan la petición al guardar correctamente.
if (($argv[1] ?? '') === 'guardar') {
    $tipo = $argv[2];
    $_SESSION['user'] = ['id' => null, 'id_empresa' => (int) $argv[3]];
    $_POST = json_decode(base64_decode($argv[4], true), true, 512, JSON_THROW_ON_ERROR);
    $_GET = json_decode(base64_decode($argv[5], true), true, 512, JSON_THROW_ON_ERROR);
    $_SERVER['REQUEST_METHOD'] = 'POST';
    db()->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
    $errors = [];
    ${variable_formulario_edicion($tipo)} = formulario_edicion($tipo);
    require __DIR__ . '/../handlers/boletin_' . $tipo . '.php';
    echo json_encode($errors, JSON_THROW_ON_ERROR);
    exit;
}

function guardar_edicion(string $tipo, int $empresa, array $post, array $get = []): array
{
    $proceso = proc_open([
        PHP_BINARY, __FILE__, 'guardar', $tipo, (string) $empresa,
        base64_encode(json_encode($post, JSON_THROW_ON_ERROR)),
        base64_encode(json_encode($get, JSON_THROW_ON_ERROR)),
    ], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    verificar_edicion(is_resource($proceso), 'No se pudo ejecutar el handler.');
    $salida = stream_get_contents($pipes[1]);
    $errorPhp = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $codigo = proc_close($proceso);
    verificar_edicion($codigo === 0 && $errorPhp === '', 'Error PHP: ' . $errorPhp . $salida);
    return $salida === '' ? [] : json_decode($salida, true, 512, JSON_THROW_ON_ERROR);
}

function renderizar_edicion(string $tipo, int $empresa, array $get, ?array $post = null): array
{
    $_SESSION['user'] = ['id' => null, 'id_empresa' => $empresa];
    $_GET = $get;
    $_POST = $post ?? [];
    $_SERVER['REQUEST_METHOD'] = $post === null ? 'GET' : 'POST';
    $errors = [];
    $successMessage = null;
    $variable = variable_formulario_edicion($tipo);
    $$variable = $post ?? formulario_edicion($tipo);
    ob_start();
    try {
        require __DIR__ . '/../pages/boletin_' . $tipo . '.php';
        $html = ob_get_contents();
    } finally {
        ob_end_clean();
    }
    return [$html, $errors, $$variable];
}

function filas_edicion(string $tabla, string $clave, int $id): array
{
    $statement = db()->prepare("SELECT * FROM $tabla WHERE $clave = ? ORDER BY 1");
    $statement->execute([$id]);
    return $statement->fetchAll();
}

$empresas = array_map('intval', db()->query('SELECT id_empresa FROM empresas ORDER BY id_empresa LIMIT 2')->fetchAll(PDO::FETCH_COLUMN));
verificar_edicion(count($empresas) === 2, 'Se requieren dos empresas.');
[$empresa, $otraEmpresa] = $empresas;
$statement = db()->prepare('SELECT id_cliente FROM clientes WHERE id_empresa = ? ORDER BY id_cliente LIMIT 1');
$statement->execute([$empresa]);
$cliente = (int) $statement->fetchColumn();
verificar_edicion($cliente > 0, 'Se requiere un cliente de la empresa.');
$marca = 'edicion-' . bin2hex(random_bytes(6));
$especies = [];
$determinaciones = [];
$boletines = [];
try {
    foreach (['Zea mays', 'Glycine max'] as $cientifico) {
        $statement = db()->prepare('INSERT INTO especies (id_empresa, nombre_especie, cientifico_especie) VALUES (?, ?, ?)');
        $statement->execute([$empresa, $marca . '-' . count($especies), $cientifico]);
        $especies[] = (int) db()->lastInsertId();
    }
    foreach (range(1, 2) as $numero) {
        $statement = db()->prepare('INSERT INTO otras_determinaciones (id_empresa, nombre_determinacion) VALUES (?, ?)');
        $statement->execute([$empresa, $marca . '-' . $numero]);
        $determinaciones[] = (int) db()->lastInsertId();
    }
    foreach (['peso_1000_semillas', 'tetrazolio', 'tetrazolio_soja', 'pureza', 'malezas'] as $tipo) {
        [$tabla, $clave, $hijas] = boletin_edicion_tablas($tipo);
        $flag = $tipo === 'peso_1000_semillas' ? 'peso' : $tipo;
        $post = array_replace(formulario_edicion($tipo), [
            'save_boletin_' . $flag => '1', 'id_cliente' => (string) $cliente,
            'id_especie' => (string) $especies[$tipo === 'tetrazolio_soja' ? 1 : 0],
            'muestra_nro' => $marca, 'analisis_nro' => $marca, 'analista' => 'Original',
            'observaciones' => 'Notas <originales>', 'fecha_boletin' => '2026-11-01',
            'fecha_inicio' => '2026-11-01', 'fecha_finalizacion' => '2026-11-02',
            'fecha_analisis' => '2026-11-02', 'fecha_recepcion' => '2026-11-01',
            'pesos' => ['1.1', '1.2', '1.3'], 'peso_inicial' => '10', 'peso_semilla_pura' => '8',
            'componentes' => [
                'materia_inerte' => [['nombre' => 'Tierra', 'peso' => '1']],
                'otras_semillas' => [['nombre' => 'Avena', 'peso' => '1']],
            ],
            'id_otras_determinaciones' => $determinaciones,
            'otras_determinaciones_texto' => 'Libre', 'tipo_analisis' => 'limitado',
            'peso_analisis' => '25', 'lote' => 'Lote original',
            'especies_halladas' => [['especie_latin' => 'Avena fatua', 'numero_semillas' => '3']],
        ]);
        if ($tipo === 'tetrazolio_soja') {
            $repeticion = array_fill_keys(array_keys(boletin_tetrazolio_soja_campos_repeticion()), '0');
            $repeticion['viables_sin_defectos'] = '180';
            $repeticion['no_viables'] = '20';
            $repeticion['danos_chinches_nivel_4'] = '7';
            $post['repeticiones'] = array_fill(1, 4, $repeticion);
        } else {
            $post['repeticiones'] = array_fill(1, 4, ['viables' => '90', 'no_viables' => '10']);
        }
        verificar_edicion(guardar_edicion($tipo, $empresa, $post) === [], "$tipo: debe conservar el alta.");
        $campoNumero = in_array($tipo, ['pureza', 'malezas'], true) ? 'analisis_nro' : 'muestra_nro';
        $statement = db()->prepare("SELECT $clave FROM $tabla WHERE id_empresa = ? AND $campoNumero = ?");
        $statement->execute([$empresa, $marca]);
        $ids = $statement->fetchAll(PDO::FETCH_COLUMN);
        verificar_edicion(count($ids) === 1, "$tipo: debe crear un solo boletín.");
        $id = (int) $ids[0];
        $boletines[$tipo] = $id;
        $get = ['editar' => (string) $id];
        [$html, $errors, $form] = renderizar_edicion($tipo, $empresa, $get);
        verificar_edicion($errors === [], "$tipo: errores de carga: " . implode('; ', $errors));
        verificar_edicion(str_contains($html, '<form method="post" class="row g-3 mt-1" id="boletin-form">'), "$tipo: falta el ancla del formulario.");
        verificar_edicion(str_contains($html, 'name="id_boletin" value="' . $id . '"'), "$tipo: falta el ID oculto.");
        verificar_edicion($form['id_cliente'] === (string) $cliente && $form['analista'] === 'Original' && $form['observaciones'] === $post['observaciones'], "$tipo: debe precargar la cabecera.");
        $original = filas_edicion($tabla, $clave, $id)[0];
        foreach ($form as $campo => $valor) {
            if (!is_array($valor) && array_key_exists($campo, $original)) {
                verificar_edicion($valor === (string) ($original[$campo] ?? ''), "$tipo: debe cargar $campo.");
            }
        }
        if ($tipo === 'peso_1000_semillas') {
            verificar_edicion(count($form['pesos']) === 3 && (float) $form['pesos'][2] === 1.3, 'Debe cargar todos los pesos.');
        } elseif ($tipo === 'tetrazolio') {
            verificar_edicion($form['repeticiones'][4] === $post['repeticiones'][4], 'Debe cargar viables y no viables.');
        } elseif ($tipo === 'tetrazolio_soja') {
            verificar_edicion($form['repeticiones'] === $post['repeticiones'], 'Debe cargar todas las categorías de soja.');
        } else {
            verificar_edicion($form['id_otras_determinaciones'] === $determinaciones, "$tipo: debe cargar las determinaciones.");
            if ($tipo === 'pureza') {
                verificar_edicion($form['componentes_materia_inerte'][0]['nombre'] === 'Tierra' && (float) $form['componentes_otras_semillas'][0]['peso'] === 1.0, 'Debe cargar ambos tipos de componentes.');
            } else {
                verificar_edicion($form['especies_halladas'] === $post['especies_halladas'], 'Debe cargar las especies halladas.');
            }
        }
        $post['id_boletin'] = (string) $id;
        $post['analista'] = 'Actualizado';
        $post['pesos'] = ['2.1', '2.2'];
        $post['componentes']['materia_inerte'] = [];
        $post['componentes']['otras_semillas'] = [['nombre' => 'Sorgo', 'peso' => '0.5']];
        $post['id_otras_determinaciones'] = [$determinaciones[1]];
        $post['especies_halladas'] = [['especie_latin' => 'Sorghum halepense', 'numero_semillas' => '0']];
        $post['tipo_analisis'] = 'reducido';
        if ($tipo === 'tetrazolio') {
            $post['repeticiones'][1] = ['viables' => '80', 'no_viables' => '20'];
        } elseif ($tipo === 'tetrazolio_soja') {
            $post['repeticiones'][1]['viables_sin_defectos'] = '160';
            $post['repeticiones'][1]['no_viables'] = '40';
        }
        foreach (range(1, 2) as $intento) {
            verificar_edicion(guardar_edicion($tipo, $empresa, $post, $get) === [], "$tipo: debe actualizar sin duplicados.");
        }
        verificar_edicion(guardar_edicion($tipo, $empresa, $post) === [], "$tipo: debe aceptar el ID oculto sin query string.");
        $soloGet = $post;
        unset($soloGet['id_boletin']);
        verificar_edicion(guardar_edicion($tipo, $empresa, $soloGet, $get) === [], "$tipo: debe aceptar editar por query string.");
        $cabecera = filas_edicion($tabla, $clave, $id);
        verificar_edicion($cabecera[0]['analista'] === 'Actualizado', "$tipo: debe actualizar la misma cabecera.");
        if ($tipo === 'peso_1000_semillas') {
            verificar_edicion((float) $cabecera[0]['resultado_peso_mil'] === 21.5, 'Debe recalcular el peso de mil semillas.');
        } elseif ($tipo === 'tetrazolio') {
            verificar_edicion((float) $cabecera[0]['resultado_porcentaje'] === 87.5, 'Debe recalcular la viabilidad.');
        } elseif ($tipo === 'tetrazolio_soja') {
            verificar_edicion((float) $cabecera[0]['semillas_evaluadas'] === 200.0, 'Soja debe conservar 200 semillas por repetición.');
        } elseif ($tipo === 'pureza') {
            verificar_edicion((float) $cabecera[0]['peso_materia_inerte'] === 0.0 && (float) $cabecera[0]['porcentaje_otras_semillas'] === 5.0, 'Debe recalcular los componentes de pureza.');
        } else {
            verificar_edicion($cabecera[0]['tipo_analisis'] === 'reducido', 'Debe actualizar el tipo de análisis de malezas.');
        }
        $statement->execute([$empresa, $marca]);
        verificar_edicion(count($statement->fetchAll()) === 1, "$tipo: editar no debe insertar otra cabecera.");
        $hijos = [];
        foreach ($hijas as $hija) {
            $hijos[$hija] = filas_edicion($hija, $clave, $id);
            $cantidad = strpos($hija, 'determinaciones') !== false ? 1 : (in_array($tipo, ['tetrazolio', 'tetrazolio_soja'], true) ? 4 : ($tipo === 'peso_1000_semillas' ? 2 : 1));
            verificar_edicion(count($hijos[$hija]) === $cantidad, "$tipo: debe reemplazar $hija.");
        }
        [, $errors, $actualizado] = renderizar_edicion($tipo, $empresa, $get);
        verificar_edicion($errors === [] && $actualizado['analista'] === 'Actualizado', "$tipo: debe recargar la actualización.");
        if ($tipo === 'tetrazolio_soja') {
            verificar_edicion($actualizado['repeticiones'] === $post['repeticiones'], 'Debe persistir los cambios de las repeticiones de soja.');
        } elseif ($tipo === 'malezas') {
            verificar_edicion($actualizado['especies_halladas'] === $post['especies_halladas'], 'Debe persistir especies halladas con cero semillas.');
        }
        $enviado = array_replace($actualizado, ['id_boletin' => (string) $id, 'analista' => 'Pendiente']);
        [, $errors, $conservado] = renderizar_edicion($tipo, $empresa, $get, $enviado);
        verificar_edicion($errors === [] && $conservado['analista'] === 'Pendiente', "$tipo: un POST fallido no debe sobrescribirse con datos guardados.");
        foreach (['0', 'abc', '2147483647'] as $invalido) {
            [$html, $errors] = renderizar_edicion($tipo, $empresa, ['editar' => $invalido]);
            verificar_edicion($errors !== [] && str_contains($html, 'disabled'), "$tipo: un GET inválido debe bloquear el alta accidental.");
        }
        [$html, $errors] = renderizar_edicion($tipo, $otraEmpresa, $get);
        verificar_edicion($errors !== [] && !str_contains($html, 'Notas &lt;originales&gt;'), "$tipo: debe impedir lectura ajena.");
        verificar_edicion(guardar_edicion($tipo, $otraEmpresa, $post, $get) !== [], "$tipo: debe impedir actualización ajena.");
        foreach (['0', '-1', 'abc', '2147483647'] as $invalido) {
            $rechazado = array_replace($post, ['id_boletin' => $invalido]);
            verificar_edicion(guardar_edicion($tipo, $empresa, $rechazado) !== [], "$tipo: debe rechazar ID $invalido sin crear.");
        }
        verificar_edicion(guardar_edicion($tipo, $empresa, $post, ['editar' => '2147483647']) !== [], "$tipo: debe rechazar IDs contradictorios.");
        if (in_array($tipo, ['tetrazolio', 'tetrazolio_soja'], true)) {
            $rechazado = array_replace($post, ['id_especie' => (string) $especies[$tipo === 'tetrazolio' ? 1 : 0]]);
            verificar_edicion(guardar_edicion($tipo, $empresa, $rechazado, $get) !== [], "$tipo: debe mantener la restricción de especie.");
        }
        if ($tipo === 'malezas') {
            verificar_edicion(guardar_edicion($tipo, $empresa, array_replace($post, ['tipo_analisis' => 'invalido']), $get) !== [], 'Debe mantener los tipos de análisis.');
        }
        if ($tipo === 'peso_1000_semillas') {
            $rechazado = array_replace($post, ['analista' => 'No persistir', 'pesos' => ['999999.9', '1000000.01']]);
            verificar_edicion(guardar_edicion($tipo, $empresa, $rechazado, $get) !== [], 'Debe fallar una repetición que desborda DECIMAL(10,4).');
        }
        verificar_edicion(filas_edicion($tabla, $clave, $id) === $cabecera, "$tipo: errores no deben modificar la cabecera.");
        foreach ($hijas as $hija) {
            verificar_edicion(filas_edicion($hija, $clave, $id) === $hijos[$hija], "$tipo: errores deben preservar $hija.");
        }
        $post['id_otras_determinaciones'] = [];
        $post['componentes'] = ['materia_inerte' => [], 'otras_semillas' => []];
        $post['especies_halladas'] = [];
        verificar_edicion(guardar_edicion($tipo, $empresa, $post, $get) === [], "$tipo: debe admitir quitar filas opcionales.");
        if (in_array($tipo, ['pureza', 'malezas'], true)) {
            foreach ($hijas as $hija) {
                verificar_edicion(filas_edicion($hija, $clave, $id) === [], "$tipo: debe eliminar todas las filas opcionales.");
            }
        }
    }
    echo "OK: alta, edición, carga de hijos, reemplazo, aislamiento, IDs, especies y rollback de cinco boletines.\n";
} finally {
    foreach ($boletines as $tipo => $id) {
        [$tabla, $clave] = boletin_edicion_tablas($tipo);
        $statement = db()->prepare("DELETE FROM $tabla WHERE $clave = ? AND id_empresa = ?");
        $statement->execute([$id, $empresa]);
    }
    foreach ($determinaciones as $id) {
        db()->prepare('DELETE FROM otras_determinaciones WHERE id_determinacion = ? AND id_empresa = ?')->execute([$id, $empresa]);
    }
    foreach ($especies as $id) {
        db()->prepare('DELETE FROM especies WHERE id_especie = ? AND id_empresa = ?')->execute([$id, $empresa]);
    }
}
