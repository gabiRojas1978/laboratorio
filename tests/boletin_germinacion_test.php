<?php

declare(strict_types=1);

function is_logged_in(): bool
{
    return false;
}

require_once __DIR__ . '/../handlers/boletin_germinacion.php';
require_once __DIR__ . '/../includes/boletin_germinacion.php';

function verificar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

$posted = [];
foreach ([1 => [90, 1], 2 => [93, 0], 3 => [98, 0], 4 => [96, 1]] as $numero => [$normales, $anormales]) {
    $posted[$numero] = ['semillas_sembradas' => '100'];
    foreach (['primer', 'segundo'] as $conteo) {
        foreach (['normales', 'anormales', 'duras', 'frescas', 'muertas'] as $categoria) {
            $posted[$numero][$conteo . '_conteo_' . $categoria] = '';
        }
    }
    $posted[$numero]['primer_conteo_normales'] = (string) $normales;
    $posted[$numero]['primer_conteo_anormales'] = (string) $anormales;
}

[$form, $data, $error] = leer_repeticiones_boletin_germinacion($posted);
verificar(!$error && count($data) === 4, 'Los datos del primer conteo de la captura deben aceptarse.');
verificar($form[2]['primer_conteo_anormales'] === '0', 'El formulario debe conservar los ceros.');
verificar($data[1]['primer_conteo_anormales'] === 0, 'Un cero no debe convertirse en null.');
verificar($data[0]['primer_conteo_duras'] === 0, 'Los campos vacios de un conteo iniciado deben ser cero.');
verificar($form[1]['primer_conteo_duras'] === '', 'El formulario debe conservar la entrada vacia.');
verificar($data[0]['segundo_conteo_normales'] === null, 'Un segundo conteo totalmente vacio debe seguir pendiente.');
$calculos = calcular_boletin_germinacion($data);
verificar($calculos['primer_promedio'] === 94.25, 'El primer promedio debe ser 94.25.');
verificar($calculos['estado'] === 'primer_conteo' && $calculos['resultado_plantulas_normales'] === null, 'No debe finalizarse un segundo conteo vacio.');
verificar(boletin_germinacion_primer_conteo_iniciado($data), 'El primer conteo en las cuatro repeticiones debe permitir guardar un Cold Test sin segundo conteo.');
$primerConteoIncompleto = $data;
$primerConteoIncompleto[3]['primer_conteo_normales'] = null;
$primerConteoIncompleto[3]['primer_conteo_anormales'] = null;
$primerConteoIncompleto[3]['primer_conteo_duras'] = null;
$primerConteoIncompleto[3]['primer_conteo_frescas'] = null;
$primerConteoIncompleto[3]['primer_conteo_muertas'] = null;
verificar(!boletin_germinacion_primer_conteo_iniciado($primerConteoIncompleto), 'Debe exigirse el primer conteo en cada repeticion del Cold Test.');
verificar(!boletin_germinacion_primer_conteo_iniciado([]), 'No debe aceptarse un Cold Test sin repeticiones con primer conteo.');
$sinPrimerConteo = $posted;
foreach ($sinPrimerConteo as &$repeticion) {
    foreach (['normales', 'anormales', 'duras', 'frescas', 'muertas'] as $categoria) {
        $repeticion['primer_conteo_' . $categoria] = '';
    }
}
unset($repeticion);
[, $sinPrimerConteoData] = leer_repeticiones_boletin_germinacion($sinPrimerConteo);
verificar(!boletin_germinacion_primer_conteo_iniciado($sinPrimerConteoData), 'No debe guardarse Cold Test con las cuatro repeticiones sin primer conteo.');

$casosValidos = [
    'solo normales' => ['primer_conteo_anormales' => ''],
    'primer conteo vacio' => ['primer_conteo_normales' => '', 'primer_conteo_anormales' => ''],
    'primer conteo completo parcial' => ['primer_conteo_duras' => '0', 'primer_conteo_frescas' => '0', 'primer_conteo_muertas' => '0'],
    'primer conteo suma exacta' => ['primer_conteo_duras' => '9'],
    'segundo conteo solo normales' => ['segundo_conteo_normales' => '9'],
    'segundo conteo dos categorias' => ['segundo_conteo_normales' => '5', 'segundo_conteo_anormales' => '4'],
];
foreach ($casosValidos as $nombre => $cambios) {
    $caso = $posted;
    $caso[1] = array_replace($caso[1], $cambios);
    [, $datos, $error] = leer_repeticiones_boletin_germinacion($caso);
    verificar(!$error && count($datos) === 4, 'Debe aceptarse: ' . $nombre);
}

$caso = $posted;
$caso[1]['primer_conteo_normales'] = '';
$caso[1]['primer_conteo_anormales'] = '0';
[, $datos, $error] = leer_repeticiones_boletin_germinacion($caso);
verificar(!$error && $datos[0]['primer_conteo_normales'] === 0, 'Un cero explicito debe iniciar el conteo y completar los vacios con cero.');
$caso[1]['primer_conteo_anormales'] = '';
[, $datos, $error] = leer_repeticiones_boletin_germinacion($caso);
verificar(!$error && $datos[0]['primer_conteo_normales'] === null, 'Un primer conteo totalmente vacio debe seguir pendiente.');

$casosInvalidos = [
    'semillas vacias' => ['semillas_sembradas' => ''],
    'semillas cero' => ['semillas_sembradas' => '0'],
    'semillas decimales' => ['semillas_sembradas' => '100.5'],
    'primer conteo negativo' => ['primer_conteo_normales' => '-1'],
    'primer conteo decimal' => ['primer_conteo_normales' => '90.5'],
    'primer conteo texto' => ['primer_conteo_duras' => 'abc'],
    'categoria supera semillas' => ['primer_conteo_normales' => '101'],
    'suma supera semillas' => ['primer_conteo_duras' => '10'],
    'segundo conteo parcial' => ['segundo_conteo_normales' => '90'],
    'segundo conteo solo cero' => ['segundo_conteo_normales' => '0'],
    'segundo conteo suma incorrecta' => ['segundo_conteo_normales' => '90', 'segundo_conteo_anormales' => '1', 'segundo_conteo_duras' => '0', 'segundo_conteo_frescas' => '0', 'segundo_conteo_muertas' => '0'],
    'segundo conteo negativo' => ['segundo_conteo_normales' => '90', 'segundo_conteo_anormales' => '10', 'segundo_conteo_duras' => '-1', 'segundo_conteo_frescas' => '0', 'segundo_conteo_muertas' => '1'],
];
foreach ($casosInvalidos as $nombre => $cambios) {
    $caso = $posted;
    $caso[1] = array_replace($caso[1], $cambios);
    [, $datos, $error] = leer_repeticiones_boletin_germinacion($caso);
    verificar($error && $datos === [], 'Debe rechazarse: ' . $nombre);
}

$final = $posted;
foreach ([1 => [2, 3, '', '', 4], 2 => [2, 2, 1, 1, 1], 3 => [1, 1, '', '', ''], 4 => [2, '', 1, '', 0]] as $numero => $valores) {
    foreach (['normales', 'anormales', 'duras', 'frescas', 'muertas'] as $index => $categoria) {
        $final[$numero]['segundo_conteo_' . $categoria] = (string) $valores[$index];
    }
}
[, $data, $error] = leer_repeticiones_boletin_germinacion($final);
verificar(!$error && count($data) === 4, 'El segundo conteo completo debe aceptarse.');
$calculos = calcular_boletin_germinacion($data);
verificar($calculos['estado'] === 'finalizado', 'El segundo conteo completo debe finalizar el boletin.');
verificar($calculos['primer_promedio'] === 94.25, 'El resultado final debe conservar el primer promedio.');
verificar($data[0]['segundo_conteo_normales'] === 2 && $data[0]['primer_conteo_normales'] === 90, 'Deben conservarse los conteos originales sin guardar totales en el segundo.');
foreach (['plantulas_normales' => 96.0, 'plantulas_anormales' => 2.0, 'semillas_duras' => 0.5, 'semillas_frescas' => 0.25, 'semillas_muertas' => 1.25] as $categoria => $esperado) {
    verificar($calculos['resultado_' . $categoria] === $esperado, 'Resultado final incorrecto: ' . $categoria);
}

$soloNormales = $posted;
foreach ($soloNormales as &$repeticion) {
    $repeticion['primer_conteo_anormales'] = '';
    $repeticion['segundo_conteo_normales'] = (string) (100 - (int) $repeticion['primer_conteo_normales']);
}
unset($repeticion);
[, $datos, $error] = leer_repeticiones_boletin_germinacion($soloNormales);
verificar(!$error, 'Deben aceptarse cuatro segundos conteos con solo normales.');
$calculos = calcular_boletin_germinacion($datos);
verificar($calculos['estado'] === 'finalizado' && $calculos['resultado_plantulas_normales'] === 100.0, 'Los vacios convertidos en cero deben permitir finalizar.');
foreach (['anormales', 'duras', 'frescas', 'muertas'] as $categoria) {
    verificar($datos[0]['segundo_conteo_' . $categoria] === 0, 'Debe guardarse cero para la categoria vacia: ' . $categoria);
}
$soloNormales[4]['segundo_conteo_normales'] = '';
[, $datos, $error] = leer_repeticiones_boletin_germinacion($soloNormales);
verificar(!$error && calcular_boletin_germinacion($datos)['estado'] === 'primer_conteo', 'Una repeticion sin segundo conteo debe impedir finalizar.');

$caso = $final;
$caso[1]['segundo_conteo_normales'] = '1';
[, , $error, $mensajes] = leer_repeticiones_boletin_germinacion($caso);
verificar($error && str_contains($mensajes[0], 'Repetición 1') && str_contains($mensajes[0], 'total 99'), 'El error debe identificar la repeticion y el total incorrecto.');
$caso[1]['segundo_conteo_normales'] = '3';
[, , $error, $mensajes] = leer_repeticiones_boletin_germinacion($caso);
verificar($error && str_contains($mensajes[0], 'total 101'), 'Debe rechazarse un total acumulado superior al sembrado.');
$caso = $posted;
foreach ($caso as &$repeticion) {
    $repeticion['primer_conteo_normales'] = '100';
    $repeticion['primer_conteo_anormales'] = '';
    $repeticion['segundo_conteo_normales'] = '0';
}
unset($repeticion);
[, $datos, $error] = leer_repeticiones_boletin_germinacion($caso);
verificar(!$error && calcular_boletin_germinacion($datos)['estado'] === 'finalizado', 'Un segundo conteo explicito de cero debe cerrar un primer conteo de 100.');
$caso[1]['primer_conteo_normales'] = '';
$caso[1]['segundo_conteo_normales'] = '100';
[, $datos, $error] = leer_repeticiones_boletin_germinacion($caso);
verificar(!$error && calcular_boletin_germinacion($datos)['resultado_plantulas_normales'] === 100.0, 'Debe aceptarse un primer conteo pendiente con todas las semillas en el segundo.');
$datos[0]['segundo_conteo_normales'] = 99;
verificar(calcular_boletin_germinacion($datos)['estado'] === 'primer_conteo', 'El calculo no debe finalizar totales invalidos.');

$caso = $final;
foreach ($caso as &$repeticion) {
    $repeticion['semillas_sembradas'] = '200';
    foreach (['primer', 'segundo'] as $conteo) {
        foreach (['normales', 'anormales', 'duras', 'frescas', 'muertas'] as $categoria) {
            $campo = $conteo . '_conteo_' . $categoria;
            if ($repeticion[$campo] !== '') {
                $repeticion[$campo] = (string) ((int) $repeticion[$campo] * 2);
            }
        }
    }
}
unset($repeticion);
[, $datos, $error] = leer_repeticiones_boletin_germinacion($caso);
[, $datosBase] = leer_repeticiones_boletin_germinacion($final);
verificar(!$error && calcular_boletin_germinacion($datos) === calcular_boletin_germinacion($datosBase), 'La validacion y los porcentajes deben usar las semillas sembradas, no un fijo de 100.');

echo "OK: primer conteo parcial, validaciones y segundo conteo final.\n";
