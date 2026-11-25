<?php

declare(strict_types=1);

require_once __DIR__ . '/boletin_tetrazolio_soja.php';

function libro_ingreso_anio_actual(): int
{
    return (int) date('Y');
}

/**
 * El N° INASE es correlativo por empresa dentro del año corriente.
 */
function libro_ingreso_proximo_nro_inase(int $idEmpresa, int $anio): int
{
    $statement = db()->prepare(
        'SELECT COALESCE(MAX(nro_inase), 0) + 1
         FROM libro_ingreso
         WHERE id_empresa = :id_empresa AND anio_libro_ingreso = :anio'
    );
    $statement->execute([':id_empresa' => $idEmpresa, ':anio' => $anio]);

    return (int) $statement->fetchColumn();
}

/**
 * El N° de muestra combina el año corriente con el N° INASE en cuatro dígitos.
 */
function libro_ingreso_nro_muestra(int $anio, int $nroInase): string
{
    return $anio . str_pad((string) $nroInase, 4, '0', STR_PAD_LEFT);
}

function libro_ingreso_nro_inase_formateado(int $nroInase): string
{
    return str_pad((string) $nroInase, 2, '0', STR_PAD_LEFT);
}

function libro_ingreso_boletines_disponibles(
    array $determinaciones,
    ?string $nombreEspecie = null,
    ?string $cientificoEspecie = null
): array
{
    $correspondencias = [
        'energia germinativa - 1° conteo' => 'boletin_germinacion',
        'energia germinativa - 1° conteo con tratamiento' => 'boletin_germinacion',
        'poder germinativo' => 'boletin_germinacion',
        'poder germinativo con tratamiento' => 'boletin_germinacion',
        'cold test-vigor' => 'boletin_cold_test',
        'vigor por envejecimiento acelerado' => 'boletin_envejecimiento_acelerado',
        'peso de 1000 semillas' => 'boletin_peso_1000_semillas',
        'viabilidad por tetrazolio-soja' => 'boletin_tetrazolio_soja',
        'vigor por tetrazolio soja' => 'boletin_tetrazolio_soja',
        'vigor por tetrazolio (otras semillas y forrajeras)' => 'boletin_tetrazolio',
        'pureza (soja-trigo-maiz-sorgo-girasol)' => 'boletin_pureza',
        'pureza (otras semillas y forrajeras)' => 'boletin_pureza',
        'otras semillas en numero' => 'boletin_malezas',
    ];
    $boletines = [];
    foreach ($determinaciones as $determinacion) {
        $nombre = strtr(mb_strtolower(trim($determinacion), 'UTF-8'), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        ]);
        if (isset($correspondencias[$nombre])) {
            $boletines[] = $correspondencias[$nombre];
        }
    }

    $boletines = array_values(array_unique($boletines));
    $nombreEspecieConocido = trim((string) $nombreEspecie) !== '' || trim((string) $cientificoEspecie) !== '';
    $esSoja = boletin_tetrazolio_soja_especie_valida($nombreEspecie ?? '', $cientificoEspecie);
    if (
        in_array('boletin_tetrazolio_soja', $boletines, true)
        && !$esSoja
    ) {
        $boletines = array_values(array_diff($boletines, ['boletin_tetrazolio_soja']));
    }
    if (
        in_array('boletin_tetrazolio', $boletines, true)
        && (!$nombreEspecieConocido || $esSoja)
    ) {
        $boletines = array_values(array_diff($boletines, ['boletin_tetrazolio']));
    }

    return $boletines;
}

function libro_ingreso_precargar_boletin(array $form, array $ingreso): array
{
    $form['id_cliente'] = (string) $ingreso['id_cliente'];
    $form['id_especie'] = (string) ($ingreso['id_especie'] ?? '');
    $form['observaciones'] = (string) ($ingreso['lote_observaciones'] ?? '');

    if (array_key_exists('muestra_nro', $form)) {
        $form['muestra_nro'] = (string) $ingreso['nro_muestra'];
    }
    if (array_key_exists('analisis_nro', $form)) {
        $form['analisis_nro'] = libro_ingreso_nro_inase_formateado((int) $ingreso['nro_inase']);
    }

    return $form;
}

function libro_ingreso_vincular_boletin(int $ingresoId, string $pagina, int $boletinId): void
{
    $tablasBoletines = [
        'boletin_peso_1000_semillas' => ['boletines_peso_1000_semillas', 'id_boletin_peso', null, 'id_especie', 'muestra_nro'],
        'boletin_tetrazolio' => ['boletines_tetrazolio', 'id_boletin_tetrazolio', null, 'id_especie', 'muestra_nro'],
        'boletin_tetrazolio_soja' => ['boletines_tetrazolio_soja', 'id_boletin_tetrazolio_soja', null, 'id_especie', 'muestra_nro'],
        'boletin_pureza' => ['boletines_pureza', 'id_boletin_pureza', null, 'id_especie', 'analisis_nro'],
        'boletin_malezas' => ['boletines_malezas', 'id_boletin_malezas', null, null, 'muestra_nro'],
        'boletin_germinacion' => ['boletines_germinacion', 'id_boletin_germinacion', 'germinacion', 'id_especie', 'muestra_nro'],
        'boletin_cold_test' => ['boletines_germinacion', 'id_boletin_germinacion', 'cold_test', 'id_especie', 'muestra_nro'],
        'boletin_envejecimiento_acelerado' => ['boletines_germinacion', 'id_boletin_germinacion', 'envejecimiento_acelerado', 'id_especie', 'muestra_nro'],
    ];
    if ($ingresoId < 1 || $boletinId < 1 || !isset($tablasBoletines[$pagina])) {
        throw new InvalidArgumentException('La asociación entre ingreso y boletín no es válida.');
    }

    $statement = db()->prepare(
        'SELECT id_libro_ingreso, id_cliente, id_especie, nro_muestra, nro_inase
         FROM libro_ingreso
         WHERE id_libro_ingreso = :id AND id_empresa = :id_empresa
         LIMIT 1'
    );
    $statement->execute([':id' => $ingresoId, ':id_empresa' => current_empresa_id()]);
    $ingreso = $statement->fetch(PDO::FETCH_ASSOC);
    if ($ingreso === false) {
        throw new RuntimeException('El ingreso asociado no existe o no pertenece a la empresa actual.');
    }

    [$tabla, $columnaId, $tipoGerminacion, $columnaEspecie, $campoMuestra] = $tablasBoletines[$pagina];
    $condicionTipo = $tipoGerminacion === null ? '' : ' AND tipo_boletin = :tipo_boletin';
    $columnasBoletin = ['id_cliente', $campoMuestra];
    if ($columnaEspecie !== null) {
        $columnasBoletin[] = $columnaEspecie;
    }
    $parametrosBoletin = [':id' => $boletinId, ':id_empresa' => current_empresa_id()];
    if ($tipoGerminacion !== null) {
        $parametrosBoletin[':tipo_boletin'] = $tipoGerminacion;
    }
    $statement = db()->prepare(
        'SELECT ' . implode(', ', $columnasBoletin) . ' FROM ' . $tabla
        . ' WHERE ' . $columnaId . ' = :id AND id_empresa = :id_empresa' . $condicionTipo . ' LIMIT 1'
    );
    $statement->execute($parametrosBoletin);
    $boletin = $statement->fetch(PDO::FETCH_ASSOC);
    if ($boletin === false) {
        throw new RuntimeException('El boletín asociado no existe o no pertenece a la empresa actual.');
    }
    if ((int) $boletin['id_cliente'] !== (int) $ingreso['id_cliente']) {
        throw new RuntimeException('El cliente del boletín no coincide con el ingreso.');
    }
    if ($columnaEspecie !== null && (int) ($boletin[$columnaEspecie] ?? 0) !== (int) $ingreso['id_especie']) {
        throw new RuntimeException('Los datos de cliente o especie del boletín no coinciden con el ingreso.');
    }

    $muestraEsperada = $pagina === 'boletin_pureza'
        ? libro_ingreso_nro_inase_formateado((int) $ingreso['nro_inase'])
        : (string) $ingreso['nro_muestra'];
    if ((string) ($boletin[$campoMuestra] ?? '') !== $muestraEsperada) {
        throw new RuntimeException('El número de muestra o análisis del boletín no coincide con el ingreso.');
    }

    $statement = db()->prepare(
        'SELECT id_boletin
         FROM libro_ingreso_boletines
         WHERE id_libro_ingreso = :id_ingreso AND tipo_boletin = :tipo
         LIMIT 1'
    );
    $statement->execute([':id_ingreso' => $ingresoId, ':tipo' => $pagina]);
    $boletinExistente = $statement->fetchColumn();
    if ($boletinExistente !== false) {
        if ((int) $boletinExistente !== $boletinId) {
            throw new RuntimeException('Este ingreso ya tiene otro boletín asociado para esa determinación.');
        }

        return;
    }

    $statement = db()->prepare(
        'INSERT INTO libro_ingreso_boletines (id_libro_ingreso, tipo_boletin, id_boletin)
         VALUES (:id_ingreso, :tipo, :id_boletin)'
    );
    $statement->execute([
        ':id_ingreso' => $ingresoId,
        ':tipo' => $pagina,
        ':id_boletin' => $boletinId,
    ]);
}

function libro_ingreso_buscar_boletin_legacy(string $pagina, array $ingreso): int|false|null
{
    $parametros = [
        ':id_empresa' => current_empresa_id(),
        ':id_cliente' => (int) $ingreso['id_cliente'],
        ':muestra' => (string) $ingreso['nro_muestra'],
        ':id_especie' => (int) ($ingreso['id_especie'] ?? 0),
    ];
    $consultas = [
        'boletin_peso_1000_semillas' => [
            'SELECT id_boletin_peso FROM boletines_peso_1000_semillas
             WHERE id_empresa = :id_empresa AND id_cliente = :id_cliente AND id_especie = :id_especie AND muestra_nro = :muestra',
            $parametros,
        ],
        'boletin_tetrazolio' => [
            'SELECT id_boletin_tetrazolio FROM boletines_tetrazolio
             WHERE id_empresa = :id_empresa AND id_cliente = :id_cliente AND id_especie = :id_especie AND muestra_nro = :muestra',
            $parametros,
        ],
        'boletin_tetrazolio_soja' => [
            'SELECT id_boletin_tetrazolio_soja FROM boletines_tetrazolio_soja
             WHERE id_empresa = :id_empresa AND id_cliente = :id_cliente AND id_especie = :id_especie AND muestra_nro = :muestra',
            $parametros,
        ],
        'boletin_pureza' => [
            'SELECT id_boletin_pureza FROM boletines_pureza
             WHERE id_empresa = :id_empresa AND id_cliente = :id_cliente AND id_especie = :id_especie AND analisis_nro = :analisis_nro',
            array_diff_key($parametros, [':muestra' => true]) + [':analisis_nro' => libro_ingreso_nro_inase_formateado((int) $ingreso['nro_inase'])],
        ],
        'boletin_malezas' => [
            'SELECT id_boletin_malezas FROM boletines_malezas
             WHERE id_empresa = :id_empresa AND id_cliente = :id_cliente AND muestra_nro = :muestra',
            array_diff_key($parametros, [':id_especie' => true]),
        ],
        'boletin_germinacion' => [
            'SELECT id_boletin_germinacion FROM boletines_germinacion
             WHERE id_empresa = :id_empresa AND id_cliente = :id_cliente AND id_especie = :id_especie
               AND muestra_nro = :muestra AND tipo_boletin = :tipo',
            $parametros + [':tipo' => 'germinacion'],
        ],
        'boletin_cold_test' => [
            'SELECT id_boletin_germinacion FROM boletines_germinacion
             WHERE id_empresa = :id_empresa AND id_cliente = :id_cliente AND id_especie = :id_especie
               AND muestra_nro = :muestra AND tipo_boletin = :tipo',
            $parametros + [':tipo' => 'cold_test'],
        ],
        'boletin_envejecimiento_acelerado' => [
            'SELECT id_boletin_germinacion FROM boletines_germinacion
             WHERE id_empresa = :id_empresa AND id_cliente = :id_cliente AND id_especie = :id_especie
               AND muestra_nro = :muestra AND tipo_boletin = :tipo',
            $parametros + [':tipo' => 'envejecimiento_acelerado'],
        ],
    ];
    if (!isset($consultas[$pagina])) {
        return null;
    }

    [$sql, $valores] = $consultas[$pagina];
    $sql .= ' LIMIT 2';
    $statement = db()->prepare($sql);
    $statement->execute($valores);
    $ids = $statement->fetchAll(PDO::FETCH_COLUMN);

    if (count($ids) > 1) {
        return false;
    }

    return $ids === [] ? null : (int) $ids[0];
}
