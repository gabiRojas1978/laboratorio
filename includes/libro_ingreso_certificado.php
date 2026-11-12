<?php

declare(strict_types=1);

function libro_ingreso_para_certificado(mixed $id, int $empresa): array
{
    $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        throw new InvalidArgumentException('El ingreso seleccionado no es válido.');
    }
    $statement = db()->prepare('SELECT * FROM libro_ingreso WHERE id_libro_ingreso = :id AND id_empresa = :id_empresa LIMIT 1');
    $statement->execute([':id' => $id, ':id_empresa' => $empresa]);
    $ingreso = $statement->fetch();
    if ($ingreso === false) {
        throw new InvalidArgumentException('El ingreso seleccionado no existe o no pertenece a tu empresa.');
    }
    return $ingreso;
}

function libro_ingreso_fuentes_certificado(string $pagina): array
{
    if (!in_array($pagina, ['Certificadogeneral', 'CertificadoINASE', 'CertificadoTetrazolio'], true)) {
        throw new InvalidArgumentException('El tipo de certificado seleccionado no es válido.');
    }
    if ($pagina === 'CertificadoTetrazolio') {
        return ['tetrazolio' => 'Tetrazolio'];
    }
    $fuentes = ['germinacion' => 'Poder germinativo'];
    if ($pagina === 'Certificadogeneral') {
        $fuentes['germinacion_curado'] = 'Poder germinativo curado (selecciona el ensayo con tratamiento)';
    }
    $fuentes += [
        'pureza' => 'Pureza física-botánica (verifica N° de análisis y fecha)',
        'peso' => 'Peso de 1000 semillas',
        'malezas' => 'Otras especies en número',
    ];
    if ($pagina === 'Certificadogeneral') {
        $fuentes['cold_test'] = 'Vigor: Cold Test (prueba de frío)';
        $fuentes['envejecimiento'] = 'Vigor por envejecimiento acelerado (se vuelca en Otras determinaciones)';
    } else {
        $fuentes['tetrazolio'] = 'Tetrazolio — no soja (se vuelca en Otras determinaciones)';
    }
    return $fuentes;
}

function libro_ingreso_certificado_directo_destino(array $determinaciones): ?string
{
    // Correspondencia determinación → certificado. Si todas las determinaciones del
    // ingreso apuntan al mismo certificado, se abre directamente; si están mezcladas
    // o hay alguna desconocida, se mantiene la selección manual del tipo.
    $correspondencias = [
        'Poder Germinativo' => 'CertificadoINASE',
        'Peso de 1000 semillas' => 'CertificadoINASE',
        'Pureza (soja-trigo-maiz-sorgo-girasol)' => 'CertificadoINASE',
        'Pureza (otras semillas y forrajeras)' => 'CertificadoINASE',
        'Vigor por tetrazolio' => 'CertificadoINASE',
        'Vigor por tetrazolio (otras semillas y forrajeras)' => 'CertificadoINASE',
        'Otras semillas en numero' => 'CertificadoINASE',
        'Energia Germinativa - 1° Conteo' => 'Certificadogeneral',
        'Energia Germinativo- 1ª Conteo Con tratamiento' => 'Certificadogeneral',
        'Poder germinativo Con tratamiento' => 'Certificadogeneral',
        'Cold Test-Vigor' => 'Certificadogeneral',
        'Vigor por Envejecimiento Acelerado' => 'Certificadogeneral',
        'Pureza Genetica (hilo-peroxidasa-hipocotilo)' => 'Certificadogeneral',
        'Peroxidasa en semilla de soja' => 'Certificadogeneral',
        'Test de Lavandina- Evaluacion de daño Mecanico' => 'Certificadogeneral',
        'Viabilidad por tetrazolio-Soja' => 'CertificadoTetrazolio',
    ];
    $destinos = [];
    foreach ($determinaciones as $determinacion) {
        if (!isset($correspondencias[$determinacion])) {
            return null;
        }
        $destinos[$correspondencias[$determinacion]] = true;
    }
    return count($destinos) === 1 ? array_key_first($destinos) : null;
}

function libro_ingreso_seleccion_automatica(array $ingreso, int $empresa, string $pagina): array
{
    $seleccion = [];
    $avisos = [];
    foreach (libro_ingreso_fuentes_certificado($pagina) as $fuente => $label) {
        // El ensayo con tratamiento no se puede distinguir automáticamente del común.
        if ($fuente === 'germinacion_curado') {
            continue;
        }
        $candidatos = libro_ingreso_candidatos_certificado($ingreso, $empresa, $fuente);
        if (count($candidatos) === 1) {
            $seleccion[$fuente] = (string) $candidatos[0]['id_origen'];
        } elseif (count($candidatos) > 1) {
            $avisos[] = 'Hay varios boletines que coinciden para "' . $label . '"; no se importó ninguno automáticamente.';
        }
    }
    return [$seleccion, $avisos];
}

function libro_ingreso_candidatos_certificado(array $ingreso, int $empresa, string $fuente): array
{
    $tablas = [
        'germinacion' => ['boletines_germinacion', 'id_boletin_germinacion', 'fecha_segundo_conteo'],
        'germinacion_curado' => ['boletines_germinacion', 'id_boletin_germinacion', 'fecha_segundo_conteo'],
        'cold_test' => ['boletines_germinacion', 'id_boletin_germinacion', 'fecha_segundo_conteo'],
        'envejecimiento' => ['boletines_germinacion', 'id_boletin_germinacion', 'fecha_segundo_conteo'],
        'pureza' => ['boletines_pureza', 'id_boletin_pureza', 'fecha_analisis'],
        'peso' => ['boletines_peso_1000_semillas', 'id_boletin_peso', 'fecha_boletin'],
        'malezas' => ['boletines_malezas', 'id_boletin_malezas', 'fecha_analisis'],
        'tetrazolio' => ['boletines_tetrazolio', 'id_boletin_tetrazolio', 'fecha_finalizacion'],
    ];
    if (!isset($tablas[$fuente])) {
        throw new InvalidArgumentException('El tipo de boletín seleccionado no es válido.');
    }
    [$tabla, $clave, $fecha] = $tablas[$fuente];
    $sql = "SELECT *, $clave AS id_origen, $fecha AS fecha_origen FROM $tabla
            WHERE id_empresa = :id_empresa AND id_cliente = :id_cliente AND id_especie <=> :id_especie";
    $params = [
        ':id_empresa' => $empresa,
        ':id_cliente' => $ingreso['id_cliente'],
        ':id_especie' => $ingreso['id_especie'],
    ];
    // Pureza no guarda N° de muestra: su asociación siempre se confirma manualmente.
    if ($fuente !== 'pureza') {
        $sql .= ' AND muestra_nro = :muestra';
        $params[':muestra'] = $ingreso['nro_muestra'];
    }
    // Los ensayos basados en germinación comparten tabla y se distinguen por tipo_boletin.
    $tiposGerminacion = [
        'germinacion' => 'germinacion',
        'germinacion_curado' => 'germinacion',
        'cold_test' => 'cold_test',
        'envejecimiento' => 'envejecimiento_acelerado',
    ];
    if (isset($tiposGerminacion[$fuente])) {
        $sql .= " AND tipo_boletin = :tipo_boletin AND estado = 'finalizado'";
        $params[':tipo_boletin'] = $tiposGerminacion[$fuente];
    }
    $statement = db()->prepare($sql . " ORDER BY $clave DESC");
    $statement->execute($params);
    return $statement->fetchAll();
}

function libro_ingreso_precargar_certificado(array $form, array $ingreso, string $pagina, array $boletines): array
{
    $form['tipo_certificado'] = strtolower($pagina);
    $form['id_cliente_certificado'] = (string) $ingreso['id_cliente'];
    $form['id_especie_certificado'] = (string) ($ingreso['id_especie'] ?? '');
    $form['id_cultivar_certificado'] = $pagina === 'CertificadoTetrazolio' ? '' : (string) ($ingreso['id_cultivar'] ?? '');
    $form['nro_analisis_certificado'] = libro_ingreso_nro_inase_formateado((int) $ingreso['nro_inase']);
    $form['fecha_muestras_certificado'] = (string) $ingreso['fecha_entrada'];
    $form[$pagina === 'CertificadoTetrazolio' ? 'tetrazolio_detalle_certificado' : 'marca_lote_certificado'] = (string) ($ingreso['lote_observaciones'] ?? '');
    $form['otras_referencias_certificado'] = 'Muestra N° ' . $ingreso['nro_muestra'];
    $avisos = [];
    $fechas = [];
    $agregarOtraDeterminacion = static function (array $form, string $detalle): array {
        $texto = trim((string) ($form['otras_determinaciones_texto_certificado'] ?? ''));
        $form['otras_determinaciones_texto_certificado'] = $texto === '' ? $detalle : $texto . '; ' . $detalle;
        return $form;
    };
    foreach ($boletines as $fuente => $boletin) {
        if (!empty($boletin['fecha_origen'])) {
            $fechas[] = $boletin['fecha_origen'];
        }
        if (in_array($fuente, ['germinacion', 'germinacion_curado'], true)) {
            $prefijo = $fuente === 'germinacion' ? 'pg_' : 'pgc_';
            foreach (['plantulas_normales', 'plantulas_anormales', 'semillas_duras', 'semillas_frescas', 'semillas_muertas'] as $categoria) {
                $form[$prefijo . $categoria . '_certificado'] = (string) ($boletin['resultado_' . $categoria] ?? '');
            }
            if (!empty($boletin['fecha_siembra']) && !empty($boletin['fecha_segundo_conteo']) && $boletin['fecha_segundo_conteo'] >= $boletin['fecha_siembra']) {
                $form[$prefijo . 'numero_dias_certificado'] = (string) (new DateTimeImmutable($boletin['fecha_siembra']))->diff(new DateTimeImmutable($boletin['fecha_segundo_conteo']))->days;
            }
            if ($fuente === 'germinacion' && $pagina === 'CertificadoINASE' && $boletin['resultado_plantulas_anormales'] !== null) {
                $form = $agregarOtraDeterminacion($form, 'Plántulas anormales: ' . $boletin['resultado_plantulas_anormales'] . ' %');
            }
        } elseif ($fuente === 'pureza') {
            foreach ([
                'pureza_semilla_pura_certificado' => 'porcentaje_semilla_pura',
                'materia_inerte_certificado' => 'porcentaje_materia_inerte',
                'pureza_otras_semillas_certificado' => 'porcentaje_otras_semillas',
            ] as $campo => $origen) {
                $form[$campo] = number_format((float) $boletin[$origen], 2, '.', '');
            }
            $form['clase_materia_inerte_certificado'] = implode(', ', $boletin['materia_inerte_nombres']);
            $form['otras_semillas_certificado'] = implode(', ', $boletin['otras_semillas_nombres']);
            $avisos[] = 'Pureza se asoció por selección manual. Verifica que el análisis y su fecha correspondan a este ingreso.';
        } elseif ($fuente === 'peso') {
            if ($boletin['resultado_peso_mil'] !== null) {
                $form = $agregarOtraDeterminacion($form, 'Peso de 1000 semillas: ' . $boletin['resultado_peso_mil'] . ' g');
            }
        } elseif ($fuente === 'cold_test') {
            $form['vigor_plantulas_normales_certificado'] = (string) ($boletin['resultado_plantulas_normales'] ?? '');
            if (!empty($boletin['fecha_siembra']) && !empty($boletin['fecha_segundo_conteo']) && $boletin['fecha_segundo_conteo'] >= $boletin['fecha_siembra']) {
                $form['vigor_nro_dias_certificado'] = (string) (new DateTimeImmutable($boletin['fecha_siembra']))->diff(new DateTimeImmutable($boletin['fecha_segundo_conteo']))->days;
            }
        } elseif ($fuente === 'envejecimiento') {
            if ($boletin['resultado_plantulas_normales'] !== null) {
                $form = $agregarOtraDeterminacion($form, 'Vigor por envejecimiento acelerado: ' . $boletin['resultado_plantulas_normales'] . ' %');
            }
        } elseif ($fuente === 'malezas') {
            $detalle = [];
            foreach ($boletin['especies_halladas'] as $especie) {
                $detalle[] = $especie['especie_latin'] . ': ' . $especie['numero_semillas'] . ' semillas';
            }
            $form['otras_semillas_certificado'] = trim(($form['otras_semillas_certificado'] ?? '') . '; ' . implode('; ', $detalle), '; ');
        } elseif ($fuente === 'tetrazolio') {
            if ($pagina === 'CertificadoINASE') {
                if ($boletin['resultado_porcentaje'] !== null) {
                    $form = $agregarOtraDeterminacion($form, 'Viabilidad por tetrazolio: ' . $boletin['resultado_porcentaje'] . ' %');
                }
                if ($boletin['resultado_no_viables_porcentaje'] !== null) {
                    $form = $agregarOtraDeterminacion($form, 'No viables por tetrazolio: ' . $boletin['resultado_no_viables_porcentaje'] . ' %');
                }
            } else {
                $form['tetrazolio_no_viables_certificado'] = (string) ($boletin['resultado_no_viables_porcentaje'] ?? '');
                $avisos[] = 'Tetrazolio: viables totales ' . ($boletin['resultado_porcentaje'] ?? 'sin resultado') . ' %. Completa manualmente las cuatro categorías viables, vigor y daños: el boletín no contiene ese desglose.';
            }
        }
    }
    if ($fechas !== []) {
        $form['fecha_finalizacion_ensayos_certificado'] = max($fechas);
    }
    foreach ([
        'clase_materia_inerte_certificado' => 'Clase de materia inerte',
        'otras_semillas_certificado' => 'Otras semillas',
    ] as $campo => $label) {
        if (mb_strlen($form[$campo] ?? '', 'UTF-8') > 100) {
            $avisos[] = $label . ' supera los 100 caracteres admitidos. Resume manualmente este detalle en el campo correspondiente: ' . $form[$campo];
            $form[$campo] = '';
        }
    }
    if (mb_strlen($form['otras_determinaciones_texto_certificado'] ?? '', 'UTF-8') > 255) {
        $avisos[] = 'Otras determinaciones supera los 255 caracteres admitidos. Resume manualmente este detalle en el campo correspondiente: ' . $form['otras_determinaciones_texto_certificado'];
        $form['otras_determinaciones_texto_certificado'] = '';
    }
    $avisos[] = 'Revisa los datos importados y las fechas antes de guardar. Los campos sin resultado disponible quedan pendientes.';
    return [$form, $avisos];
}

function libro_ingreso_importar_certificado(array $form, array $ingreso, int $empresa, string $pagina, array $seleccion): array
{
    $boletines = [];
    $fuentes = libro_ingreso_fuentes_certificado($pagina);
    foreach ($seleccion as $fuente => $id) {
        if (!isset($fuentes[$fuente]) || filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            throw new InvalidArgumentException('La selección de boletines no es válida.');
        }
        $candidatos = array_column(libro_ingreso_candidatos_certificado($ingreso, $empresa, $fuente), null, 'id_origen');
        if (!isset($candidatos[$id])) {
            throw new InvalidArgumentException('El boletín seleccionado no corresponde a la muestra, cliente, especie o empresa, o no está finalizado.');
        }
        $boletines[$fuente] = $candidatos[$id];
    }
    if (isset($boletines['germinacion'], $boletines['germinacion_curado']) && $boletines['germinacion']['id_origen'] === $boletines['germinacion_curado']['id_origen']) {
        throw new InvalidArgumentException('Selecciona ensayos distintos para poder germinativo y poder germinativo curado.');
    }
    foreach ($boletines as $fuente => &$boletin) {
        if ($fuente === 'pureza') {
            $statement = db()->prepare('SELECT tipo_componente, nombre_componente FROM boletin_pureza_componentes WHERE id_boletin_pureza = :id ORDER BY id_componente_pureza');
            $statement->execute([':id' => $boletin['id_origen']]);
            $boletin['materia_inerte_nombres'] = [];
            $boletin['otras_semillas_nombres'] = [];
            foreach ($statement->fetchAll() as $componente) {
                $boletin[$componente['tipo_componente'] . '_nombres'][] = $componente['nombre_componente'];
            }
        } elseif ($fuente === 'malezas') {
            $statement = db()->prepare('SELECT especie_latin, numero_semillas FROM boletin_malezas_especies WHERE id_boletin_malezas = :id ORDER BY id_especie_hallada');
            $statement->execute([':id' => $boletin['id_origen']]);
            $boletin['especies_halladas'] = $statement->fetchAll();
        }
    }
    unset($boletin);
    $ordenados = [];
    foreach ($fuentes as $fuente => $label) {
        if (isset($boletines[$fuente])) {
            $ordenados[$fuente] = $boletines[$fuente];
        }
    }
    return libro_ingreso_precargar_certificado($form, $ingreso, $pagina, $ordenados);
}
