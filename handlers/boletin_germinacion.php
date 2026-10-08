<?php

declare(strict_types=1);

if (is_logged_in() && isset($_GET['boletin_germinacion_pdf'])) {
    $boletinId = filter_var($_GET['boletin_germinacion_pdf'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($boletinId === false) {
        http_response_code(400);
        exit('ID de boletín inválido.');
    }

    $pdfResult = build_boletin_germinacion_pdf($boletinId);
    if ($pdfResult === null) {
        http_response_code(404);
        exit('Boletín no encontrado.');
    }

    $disposition = isset($_GET['download']) ? 'attachment' : 'inline';
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . $disposition . '; filename="' . $pdfResult['filename'] . '"');
    header('Content-Length: ' . strlen($pdfResult['content']));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    echo $pdfResult['content'];
    exit;
}

function leer_repeticiones_boletin_germinacion(array $postedRepeticiones): array
{
    $enteroOpcional = static function ($raw, int $max) {
        $raw = trim((string) ($raw ?? ''));
        if ($raw === '') {
            return [null, null];
        }
        $valor = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => $max]]);

        return [$raw, $valor];
    };
    $completarConteo = static function (array $valores): array {
        if (array_filter($valores, static fn($valor) => $valor !== null) === []) {
            return $valores;
        }

        return array_map(static fn($valor) => $valor ?? 0, $valores);
    };

    $repeticionesForm = [];
    $repeticionesData = [];
    $huboError = false;
    $erroresRepeticiones = [];

    for ($numero = 1; $numero <= 4; $numero++) {
        $posted = (array) ($postedRepeticiones[$numero] ?? []);
        $semillasRaw = trim((string) ($posted['semillas_sembradas'] ?? ''));
        $semillas = filter_var($semillasRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);

        [$primerNormalesRaw, $primerNormalesValor] = $enteroOpcional($posted['primer_conteo_normales'] ?? '', $semillas !== false ? $semillas : 2147483647);
        [$primerAnormalesRaw, $primerAnormalesValor] = $enteroOpcional($posted['primer_conteo_anormales'] ?? '', $semillas !== false ? $semillas : 2147483647);
        [$primerDurasRaw, $primerDurasValor] = $enteroOpcional($posted['primer_conteo_duras'] ?? '', $semillas !== false ? $semillas : 2147483647);
        [$primerFrescasRaw, $primerFrescasValor] = $enteroOpcional($posted['primer_conteo_frescas'] ?? '', $semillas !== false ? $semillas : 2147483647);
        [$primerMuertasRaw, $primerMuertasValor] = $enteroOpcional($posted['primer_conteo_muertas'] ?? '', $semillas !== false ? $semillas : 2147483647);
        [$normalesRaw, $normalesValor] = $enteroOpcional($posted['segundo_conteo_normales'] ?? '', $semillas !== false ? $semillas : 2147483647);
        [$anormalesRaw, $anormalesValor] = $enteroOpcional($posted['segundo_conteo_anormales'] ?? '', $semillas !== false ? $semillas : 2147483647);
        [$durasRaw, $durasValor] = $enteroOpcional($posted['segundo_conteo_duras'] ?? '', $semillas !== false ? $semillas : 2147483647);
        [$frescasRaw, $frescasValor] = $enteroOpcional($posted['segundo_conteo_frescas'] ?? '', $semillas !== false ? $semillas : 2147483647);
        [$muertasRaw, $muertasValor] = $enteroOpcional($posted['segundo_conteo_muertas'] ?? '', $semillas !== false ? $semillas : 2147483647);

        $repeticionesForm[$numero] = [
            'semillas_sembradas' => $semillasRaw,
            'primer_conteo_normales' => $primerNormalesRaw ?? '',
            'primer_conteo_anormales' => $primerAnormalesRaw ?? '',
            'primer_conteo_duras' => $primerDurasRaw ?? '',
            'primer_conteo_frescas' => $primerFrescasRaw ?? '',
            'primer_conteo_muertas' => $primerMuertasRaw ?? '',
            'segundo_conteo_normales' => $normalesRaw ?? '',
            'segundo_conteo_anormales' => $anormalesRaw ?? '',
            'segundo_conteo_duras' => $durasRaw ?? '',
            'segundo_conteo_frescas' => $frescasRaw ?? '',
            'segundo_conteo_muertas' => $muertasRaw ?? '',
        ];

        if ($semillas === false) {
            $huboError = true;
            continue;
        }

        $primerValores = $completarConteo([$primerNormalesValor, $primerAnormalesValor, $primerDurasValor, $primerFrescasValor, $primerMuertasValor]);
        [$primerNormalesValor, $primerAnormalesValor, $primerDurasValor, $primerFrescasValor, $primerMuertasValor] = $primerValores;
        if (in_array(false, $primerValores, true) || array_sum($primerValores) > $semillas) {
            $huboError = true;
            continue;
        }

        $segundoCampos = [$normalesRaw, $anormalesRaw, $durasRaw, $frescasRaw, $muertasRaw];
        $segundoValores = $completarConteo([$normalesValor, $anormalesValor, $durasValor, $frescasValor, $muertasValor]);
        [$normalesValor, $anormalesValor, $durasValor, $frescasValor, $muertasValor] = $segundoValores;
        $algunoCompletado = array_filter($segundoCampos, static fn($valor) => $valor !== null) !== [];
        if ($algunoCompletado) {
            if (in_array(false, $segundoValores, true)) {
                $huboError = true;
                continue;
            }
            $totalConteos = array_sum($primerValores) + array_sum($segundoValores);
            if ($totalConteos !== $semillas) {
                $huboError = true;
                $erroresRepeticiones[] = 'Repetición ' . $numero . ': el 1er conteo suma ' . array_sum($primerValores)
                    . ' y el 2do suma ' . array_sum($segundoValores) . ' (total ' . $totalConteos
                    . '). Ambos conteos deben sumar ' . $semillas . ' semillas sembradas.';
                continue;
            }
        }

        $repeticionesData[] = [
            'semillas_sembradas' => $semillas,
            'primer_conteo_normales' => $primerNormalesValor,
            'primer_conteo_anormales' => $primerAnormalesValor,
            'primer_conteo_duras' => $primerDurasValor,
            'primer_conteo_frescas' => $primerFrescasValor,
            'primer_conteo_muertas' => $primerMuertasValor,
            'segundo_conteo_normales' => $algunoCompletado ? $normalesValor : null,
            'segundo_conteo_anormales' => $algunoCompletado ? $anormalesValor : null,
            'segundo_conteo_duras' => $algunoCompletado ? $durasValor : null,
            'segundo_conteo_frescas' => $algunoCompletado ? $frescasValor : null,
            'segundo_conteo_muertas' => $algunoCompletado ? $muertasValor : null,
        ];
    }

    return [$repeticionesForm, $huboError ? [] : $repeticionesData, $huboError, $erroresRepeticiones];
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_boletin_germinacion']) || isset($_POST['update_boletin_germinacion']))) {
    $esActualizacion = isset($_POST['update_boletin_germinacion']);

    $boletinGerminacionEditId = null;
    if ($esActualizacion) {
        $boletinGerminacionEditId = filter_var($_POST['boletin_germinacion_edit_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($boletinGerminacionEditId === false) {
            $errors[] = 'El boletín a editar no es válido.';
        }
    }

    foreach (
        [
            'id_cliente',
            'id_especie',
            'analisis_nro',
            'muestra_nro',
            'fecha_siembra',
            'fecha_primer_conteo',
            'fecha_segundo_conteo',
            'analista',
            'pretratamiento_desde',
            'pretratamiento_hasta',
            'pretratamiento_total_dias',
            'pretratamiento_aplica',
            'sustrato_tipo',
            'sustrato_otro',
            'temperatura_camara_tipo',
            'temperatura_camara_otra',
            'observaciones',
        ] as $field
    ) {
        $boletinGerminacionForm[$field] = trim((string) ($_POST[$field] ?? ''));
    }
    foreach (
        [
            'pretratamiento_frio',
            'pretratamiento_nitrato_k',
            'pretratamiento_presecado',
            'pretratamiento_prelavado',
        ] as $field
    ) {
        $boletinGerminacionForm[$field] = isset($_POST[$field]);
    }
    if ($boletinGerminacionForm['pretratamiento_aplica'] === 'no') {
        $boletinGerminacionForm['pretratamiento_desde'] = '';
        $boletinGerminacionForm['pretratamiento_hasta'] = '';
        $boletinGerminacionForm['pretratamiento_total_dias'] = '';
        $boletinGerminacionForm['pretratamiento_frio'] = false;
        $boletinGerminacionForm['pretratamiento_nitrato_k'] = false;
        $boletinGerminacionForm['pretratamiento_presecado'] = false;
        $boletinGerminacionForm['pretratamiento_prelavado'] = false;
    }

    [$repeticionesForm, $repeticionesData, $huboErrorRepeticiones, $erroresRepeticiones] = leer_repeticiones_boletin_germinacion((array) ($_POST['repeticiones'] ?? []));
    $boletinGerminacionForm['repeticiones'] = $repeticionesForm;
    if ($huboErrorRepeticiones) {
        $errors[] = 'Revisa las repeticiones: las semillas sembradas deben ser enteros positivos y los conteos, enteros no negativos. En un conteo iniciado, las categorías vacías se toman como 0. El 1er conteo puede quedar parcial, pero su suma no puede superar las semillas sembradas. En el 2do conteo carga solo las semillas nuevas: la suma de ambos conteos debe ser exactamente las semillas sembradas.';
        foreach ($erroresRepeticiones as $errorRepeticion) {
            $errors[] = $errorRepeticion;
        }
    }

    $clienteId = filter_var($boletinGerminacionForm['id_cliente'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $especieId = filter_var($boletinGerminacionForm['id_especie'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($clienteId === false) {
        $errors[] = 'Debes seleccionar un cliente válido.';
    } else {
        $clienteStatement = db()->prepare('SELECT id_cliente FROM clientes WHERE id_cliente = :id AND id_empresa = :id_empresa LIMIT 1');
        $clienteStatement->execute([':id' => $clienteId, ':id_empresa' => current_empresa_id()]);
        if ($clienteStatement->fetch() === false) {
            $errors[] = 'El cliente seleccionado no existe.';
        }
    }
    if ($boletinGerminacionForm['muestra_nro'] === '') {
        $errors[] = 'El número de muestra es obligatorio.';
    }
    if ($especieId === false) {
        $errors[] = 'Debes seleccionar una especie válida.';
    } else {
        $especieStatement = db()->prepare('SELECT id_especie FROM especies WHERE id_especie = :id AND id_empresa = :id_empresa LIMIT 1');
        $especieStatement->execute([':id' => $especieId, ':id_empresa' => current_empresa_id()]);
        if ($especieStatement->fetch() === false) {
            $errors[] = 'La especie seleccionada no existe.';
        }
    }
    if (strlen($boletinGerminacionForm['analisis_nro']) > 100 || strlen($boletinGerminacionForm['muestra_nro']) > 100 || strlen($boletinGerminacionForm['analista']) > 150) {
        $errors[] = 'Uno o más campos exceden la longitud permitida.';
    }
    foreach (['fecha_siembra', 'fecha_primer_conteo', 'fecha_segundo_conteo', 'pretratamiento_desde', 'pretratamiento_hasta'] as $campoFecha) {
        $fecha = $boletinGerminacionForm[$campoFecha];
        if ($fecha !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !checkdate((int) substr($fecha, 5, 2), (int) substr($fecha, 8, 2), (int) substr($fecha, 0, 4)))) {
            $errors[] = 'Las fechas ingresadas no son válidas.';
            break;
        }
    }
    if (!in_array($boletinGerminacionForm['pretratamiento_aplica'], ['si', 'no'], true)) {
        $errors[] = 'Debes indicar si aplica pre-tratamiento (SI/NO).';
    }
    $pretratamientoTotalDias = null;
    if ($boletinGerminacionForm['pretratamiento_total_dias'] !== '') {
        $pretratamientoTotalDias = filter_var($boletinGerminacionForm['pretratamiento_total_dias'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 32767]]);
        if ($pretratamientoTotalDias === false) {
            $errors[] = 'El total de días de pre-tratamiento debe ser un entero no negativo.';
        }
    }
    if (strlen($boletinGerminacionForm['sustrato_otro']) > 255 || strlen($boletinGerminacionForm['temperatura_camara_otra']) > 100) {
        $errors[] = 'Los campos "Otro" del sustrato y la temperatura exceden la longitud permitida.';
    }
    if ($boletinGerminacionForm['sustrato_tipo'] !== '' && !in_array($boletinGerminacionForm['sustrato_tipo'], ['arena', 'sobre_papel', 'entre_papel', 'otro'], true)) {
        $errors[] = 'El sustrato utilizado no es válido.';
    }
    if ($boletinGerminacionForm['temperatura_camara_tipo'] !== '' && !in_array($boletinGerminacionForm['temperatura_camara_tipo'], ['20', '25', '20_30', 'otra'], true)) {
        $errors[] = 'La temperatura de la cámara no es válida.';
    }

    if ($esActualizacion && $boletinGerminacionEditId !== false && $boletinGerminacionEditId !== null) {
        $existeStatement = db()->prepare('SELECT id_boletin_germinacion FROM boletines_germinacion WHERE id_boletin_germinacion = :id AND id_empresa = :id_empresa LIMIT 1');
        $existeStatement->execute([':id' => $boletinGerminacionEditId, ':id_empresa' => current_empresa_id()]);
        if ($existeStatement->fetch() === false) {
            $errors[] = 'El boletín a editar no existe.';
        }
    }

    if (count($repeticionesData) === 4 && !$errors) {
        $calculos = calcular_boletin_germinacion($repeticionesData);
        try {
            db()->beginTransaction();

            if ($esActualizacion) {
                $update = db()->prepare(
                    'UPDATE boletines_germinacion SET
                        id_cliente = :id_cliente,
                        id_especie = :id_especie,
                        analisis_nro = :analisis_nro,
                        muestra_nro = :muestra_nro,
                        fecha_siembra = :fecha_siembra,
                        fecha_primer_conteo = :fecha_primer_conteo,
                        fecha_segundo_conteo = :fecha_segundo_conteo,
                        analista = :analista,
                        pretratamiento_desde = :pretratamiento_desde,
                        pretratamiento_hasta = :pretratamiento_hasta,
                        pretratamiento_total_dias = :pretratamiento_total_dias,
                        pretratamiento_aplica = :pretratamiento_aplica,
                        pretratamiento_frio = :pretratamiento_frio,
                        pretratamiento_nitrato_k = :pretratamiento_nitrato_k,
                        pretratamiento_presecado = :pretratamiento_presecado,
                        pretratamiento_prelavado = :pretratamiento_prelavado,
                        sustrato_tipo = :sustrato_tipo,
                        sustrato_otro = :sustrato_otro,
                        temperatura_camara_tipo = :temperatura_camara_tipo,
                        temperatura_camara_otra = :temperatura_camara_otra,
                        primer_promedio = :primer_promedio,
                        resultado_plantulas_normales = :resultado_plantulas_normales,
                        resultado_plantulas_anormales = :resultado_plantulas_anormales,
                        resultado_semillas_duras = :resultado_semillas_duras,
                        resultado_semillas_frescas = :resultado_semillas_frescas,
                        resultado_semillas_muertas = :resultado_semillas_muertas,
                        estado = :estado,
                        observaciones = :observaciones
                     WHERE id_boletin_germinacion = :id_boletin_germinacion AND id_empresa = :id_empresa'
                );
                $update->execute([
                    ':id_cliente' => $clienteId,
                    ':id_especie' => $especieId,
                    ':analisis_nro' => $boletinGerminacionForm['analisis_nro'] !== '' ? $boletinGerminacionForm['analisis_nro'] : null,
                    ':muestra_nro' => $boletinGerminacionForm['muestra_nro'],
                    ':fecha_siembra' => $boletinGerminacionForm['fecha_siembra'] !== '' ? $boletinGerminacionForm['fecha_siembra'] : null,
                    ':fecha_primer_conteo' => $boletinGerminacionForm['fecha_primer_conteo'] !== '' ? $boletinGerminacionForm['fecha_primer_conteo'] : null,
                    ':fecha_segundo_conteo' => $boletinGerminacionForm['fecha_segundo_conteo'] !== '' ? $boletinGerminacionForm['fecha_segundo_conteo'] : null,
                    ':analista' => $boletinGerminacionForm['analista'] !== '' ? $boletinGerminacionForm['analista'] : null,
                    ':pretratamiento_desde' => $boletinGerminacionForm['pretratamiento_desde'] !== '' ? $boletinGerminacionForm['pretratamiento_desde'] : null,
                    ':pretratamiento_hasta' => $boletinGerminacionForm['pretratamiento_hasta'] !== '' ? $boletinGerminacionForm['pretratamiento_hasta'] : null,
                    ':pretratamiento_total_dias' => $pretratamientoTotalDias,
                    ':pretratamiento_aplica' => $boletinGerminacionForm['pretratamiento_aplica'] !== '' ? $boletinGerminacionForm['pretratamiento_aplica'] : null,
                    ':pretratamiento_frio' => $boletinGerminacionForm['pretratamiento_frio'] ? 1 : 0,
                    ':pretratamiento_nitrato_k' => $boletinGerminacionForm['pretratamiento_nitrato_k'] ? 1 : 0,
                    ':pretratamiento_presecado' => $boletinGerminacionForm['pretratamiento_presecado'] ? 1 : 0,
                    ':pretratamiento_prelavado' => $boletinGerminacionForm['pretratamiento_prelavado'] ? 1 : 0,
                    ':sustrato_tipo' => $boletinGerminacionForm['sustrato_tipo'] !== '' ? $boletinGerminacionForm['sustrato_tipo'] : null,
                    ':sustrato_otro' => $boletinGerminacionForm['sustrato_otro'] !== '' ? $boletinGerminacionForm['sustrato_otro'] : null,
                    ':temperatura_camara_tipo' => $boletinGerminacionForm['temperatura_camara_tipo'] !== '' ? $boletinGerminacionForm['temperatura_camara_tipo'] : null,
                    ':temperatura_camara_otra' => $boletinGerminacionForm['temperatura_camara_otra'] !== '' ? $boletinGerminacionForm['temperatura_camara_otra'] : null,
                    ':primer_promedio' => $calculos['primer_promedio'],
                    ':resultado_plantulas_normales' => $calculos['resultado_plantulas_normales'],
                    ':resultado_plantulas_anormales' => $calculos['resultado_plantulas_anormales'],
                    ':resultado_semillas_duras' => $calculos['resultado_semillas_duras'],
                    ':resultado_semillas_frescas' => $calculos['resultado_semillas_frescas'],
                    ':resultado_semillas_muertas' => $calculos['resultado_semillas_muertas'],
                    ':estado' => $calculos['estado'],
                    ':observaciones' => $boletinGerminacionForm['observaciones'] !== '' ? $boletinGerminacionForm['observaciones'] : null,
                    ':id_boletin_germinacion' => $boletinGerminacionEditId,
                    ':id_empresa' => current_empresa_id(),
                ]);
                $boletinId = $boletinGerminacionEditId;

                $deleteRepeticiones = db()->prepare('DELETE FROM boletin_germinacion_repeticiones WHERE id_boletin_germinacion = :id_boletin_germinacion');
                $deleteRepeticiones->execute([':id_boletin_germinacion' => $boletinId]);
            } else {
                $insert = db()->prepare(
                    'INSERT INTO boletines_germinacion (id_empresa, id_cliente, id_usuario, id_especie, analisis_nro, muestra_nro, fecha_siembra, fecha_primer_conteo, fecha_segundo_conteo, analista, pretratamiento_desde, pretratamiento_hasta, pretratamiento_total_dias, pretratamiento_aplica, pretratamiento_frio, pretratamiento_nitrato_k, pretratamiento_presecado, pretratamiento_prelavado, sustrato_tipo, sustrato_otro, temperatura_camara_tipo, temperatura_camara_otra, primer_promedio, resultado_plantulas_normales, resultado_plantulas_anormales, resultado_semillas_duras, resultado_semillas_frescas, resultado_semillas_muertas, estado, observaciones)
                     VALUES (:id_empresa, :id_cliente, :id_usuario, :id_especie, :analisis_nro, :muestra_nro, :fecha_siembra, :fecha_primer_conteo, :fecha_segundo_conteo, :analista, :pretratamiento_desde, :pretratamiento_hasta, :pretratamiento_total_dias, :pretratamiento_aplica, :pretratamiento_frio, :pretratamiento_nitrato_k, :pretratamiento_presecado, :pretratamiento_prelavado, :sustrato_tipo, :sustrato_otro, :temperatura_camara_tipo, :temperatura_camara_otra, :primer_promedio, :resultado_plantulas_normales, :resultado_plantulas_anormales, :resultado_semillas_duras, :resultado_semillas_frescas, :resultado_semillas_muertas, :estado, :observaciones)'
                );
                $insert->execute([
                    ':id_empresa' => current_empresa_id(),
                    ':id_cliente' => $clienteId,
                    ':id_usuario' => $_SESSION['user']['id'] ?? null,
                    ':id_especie' => $especieId,
                    ':analisis_nro' => $boletinGerminacionForm['analisis_nro'] !== '' ? $boletinGerminacionForm['analisis_nro'] : null,
                    ':muestra_nro' => $boletinGerminacionForm['muestra_nro'],
                    ':fecha_siembra' => $boletinGerminacionForm['fecha_siembra'] !== '' ? $boletinGerminacionForm['fecha_siembra'] : null,
                    ':fecha_primer_conteo' => $boletinGerminacionForm['fecha_primer_conteo'] !== '' ? $boletinGerminacionForm['fecha_primer_conteo'] : null,
                    ':fecha_segundo_conteo' => $boletinGerminacionForm['fecha_segundo_conteo'] !== '' ? $boletinGerminacionForm['fecha_segundo_conteo'] : null,
                    ':analista' => $boletinGerminacionForm['analista'] !== '' ? $boletinGerminacionForm['analista'] : null,
                    ':pretratamiento_desde' => $boletinGerminacionForm['pretratamiento_desde'] !== '' ? $boletinGerminacionForm['pretratamiento_desde'] : null,
                    ':pretratamiento_hasta' => $boletinGerminacionForm['pretratamiento_hasta'] !== '' ? $boletinGerminacionForm['pretratamiento_hasta'] : null,
                    ':pretratamiento_total_dias' => $pretratamientoTotalDias,
                    ':pretratamiento_aplica' => $boletinGerminacionForm['pretratamiento_aplica'] !== '' ? $boletinGerminacionForm['pretratamiento_aplica'] : null,
                    ':pretratamiento_frio' => $boletinGerminacionForm['pretratamiento_frio'] ? 1 : 0,
                    ':pretratamiento_nitrato_k' => $boletinGerminacionForm['pretratamiento_nitrato_k'] ? 1 : 0,
                    ':pretratamiento_presecado' => $boletinGerminacionForm['pretratamiento_presecado'] ? 1 : 0,
                    ':pretratamiento_prelavado' => $boletinGerminacionForm['pretratamiento_prelavado'] ? 1 : 0,
                    ':sustrato_tipo' => $boletinGerminacionForm['sustrato_tipo'] !== '' ? $boletinGerminacionForm['sustrato_tipo'] : null,
                    ':sustrato_otro' => $boletinGerminacionForm['sustrato_otro'] !== '' ? $boletinGerminacionForm['sustrato_otro'] : null,
                    ':temperatura_camara_tipo' => $boletinGerminacionForm['temperatura_camara_tipo'] !== '' ? $boletinGerminacionForm['temperatura_camara_tipo'] : null,
                    ':temperatura_camara_otra' => $boletinGerminacionForm['temperatura_camara_otra'] !== '' ? $boletinGerminacionForm['temperatura_camara_otra'] : null,
                    ':primer_promedio' => $calculos['primer_promedio'],
                    ':resultado_plantulas_normales' => $calculos['resultado_plantulas_normales'],
                    ':resultado_plantulas_anormales' => $calculos['resultado_plantulas_anormales'],
                    ':resultado_semillas_duras' => $calculos['resultado_semillas_duras'],
                    ':resultado_semillas_frescas' => $calculos['resultado_semillas_frescas'],
                    ':resultado_semillas_muertas' => $calculos['resultado_semillas_muertas'],
                    ':estado' => $calculos['estado'],
                    ':observaciones' => $boletinGerminacionForm['observaciones'] !== '' ? $boletinGerminacionForm['observaciones'] : null,
                ]);
                $boletinId = (int) db()->lastInsertId();
            }

            $insertRepeticion = db()->prepare(
                'INSERT INTO boletin_germinacion_repeticiones (id_boletin_germinacion, nro_repeticion, semillas_sembradas, primer_conteo_normales, primer_conteo_anormales, primer_conteo_duras, primer_conteo_frescas, primer_conteo_muertas, segundo_conteo_normales, segundo_conteo_anormales, segundo_conteo_duras, segundo_conteo_frescas, segundo_conteo_muertas)
                 VALUES (:id_boletin_germinacion, :nro_repeticion, :semillas_sembradas, :primer_conteo_normales, :primer_conteo_anormales, :primer_conteo_duras, :primer_conteo_frescas, :primer_conteo_muertas, :segundo_conteo_normales, :segundo_conteo_anormales, :segundo_conteo_duras, :segundo_conteo_frescas, :segundo_conteo_muertas)'
            );
            foreach ($repeticionesData as $index => $repeticion) {
                $insertRepeticion->execute([
                    ':id_boletin_germinacion' => $boletinId,
                    ':nro_repeticion' => $index + 1,
                    ':semillas_sembradas' => $repeticion['semillas_sembradas'],
                    ':primer_conteo_normales' => $repeticion['primer_conteo_normales'],
                    ':primer_conteo_anormales' => $repeticion['primer_conteo_anormales'],
                    ':primer_conteo_duras' => $repeticion['primer_conteo_duras'],
                    ':primer_conteo_frescas' => $repeticion['primer_conteo_frescas'],
                    ':primer_conteo_muertas' => $repeticion['primer_conteo_muertas'],
                    ':segundo_conteo_normales' => $repeticion['segundo_conteo_normales'],
                    ':segundo_conteo_anormales' => $repeticion['segundo_conteo_anormales'],
                    ':segundo_conteo_duras' => $repeticion['segundo_conteo_duras'],
                    ':segundo_conteo_frescas' => $repeticion['segundo_conteo_frescas'],
                    ':segundo_conteo_muertas' => $repeticion['segundo_conteo_muertas'],
                ]);
            }

            db()->commit();

            $_SESSION['success_message'] = $esActualizacion ? 'Boletín de Germinación actualizado correctamente.' : 'Boletín de Germinación guardado correctamente.';
            header('Location: /index.php?page=boletin_germinacion');
            exit;
        } catch (Throwable $throwable) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            $errors[] = $esActualizacion ? 'No fue posible actualizar el boletín de Germinación.' : 'No fue posible guardar el boletín de Germinación.';
        }
    } elseif (count($repeticionesData) !== 4 && !$huboErrorRepeticiones) {
        $errors[] = 'Completa las semillas sembradas en las cuatro repeticiones.';
    }
}
