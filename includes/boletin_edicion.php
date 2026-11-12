<?php

declare(strict_types=1);

function boletin_edicion_tablas(string $tipo): array
{
    $tablas = [
        'peso_1000_semillas' => ['boletines_peso_1000_semillas', 'id_boletin_peso', ['boletin_peso_repeticiones']],
        'tetrazolio' => ['boletines_tetrazolio', 'id_boletin_tetrazolio', ['boletin_tetrazolio_repeticiones']],
        'tetrazolio_soja' => ['boletines_tetrazolio_soja', 'id_boletin_tetrazolio_soja', ['boletin_tetrazolio_soja_repeticiones']],
        'pureza' => ['boletines_pureza', 'id_boletin_pureza', ['boletin_pureza_componentes', 'boletin_pureza_determinaciones']],
        'malezas' => ['boletines_malezas', 'id_boletin_malezas', ['boletin_malezas_especies', 'boletin_malezas_determinaciones']],
    ];
    if (!isset($tablas[$tipo])) {
        throw new InvalidArgumentException('Tipo de boletín inválido.');
    }
    return $tablas[$tipo];
}

function boletin_edicion_id(): ?int
{
    $ids = [];
    if (isset($_GET['editar'])) {
        $ids[] = $_GET['editar'];
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_boletin'])) {
        $ids[] = $_POST['id_boletin'];
    }
    $id = null;
    foreach ($ids as $raw) {
        $valor = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($valor === false || ($id !== null && $id !== $valor)) {
            throw new InvalidArgumentException('ID de boletín inválido.');
        }
        $id = $valor;
    }
    return $id;
}

function boletin_edicion_cabecera(string $tipo, int $id, bool $bloquear = false): array
{
    [$tabla, $clave] = boletin_edicion_tablas($tipo);
    $statement = db()->prepare(
        "SELECT * FROM $tabla WHERE $clave = :id AND id_empresa = :id_empresa" . ($bloquear ? ' FOR UPDATE' : '')
    );
    $statement->execute([':id' => $id, ':id_empresa' => current_empresa_id()]);
    $row = $statement->fetch();
    if ($row === false) {
        throw new RuntimeException('El boletín no existe o no pertenece a la empresa activa.');
    }
    return $row;
}

function boletin_edicion_cargar(string $tipo, array &$form, array &$errors): array
{
    $id = null;
    try {
        $id = boletin_edicion_id();
        if ($id === null) {
            return [null, false];
        }
        $row = boletin_edicion_cabecera($tipo, $id);
        // En una respuesta con errores se conservan los valores enviados, no los guardados.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return [$id, false];
        }
        $cargado = $form;
        foreach ($form as $campo => $valor) {
            if (!is_array($valor) && array_key_exists($campo, $row)) {
                $cargado[$campo] = (string) ($row[$campo] ?? '');
            }
        }
        [, $clave, $hijas] = boletin_edicion_tablas($tipo);
        foreach ($hijas as $tabla) {
            $orden = strpos($tabla, 'repeticiones') !== false ? 'nro_repeticion' : '1';
            $statement = db()->prepare("SELECT * FROM $tabla WHERE $clave = :id ORDER BY $orden");
            $statement->execute([':id' => $id]);
            $rows = $statement->fetchAll();
            if (strpos($tabla, 'determinaciones') !== false) {
                $cargado['id_otras_determinaciones'] = array_map('intval', array_column($rows, 'id_determinacion'));
            } elseif ($tipo === 'peso_1000_semillas') {
                $cargado['pesos'] = array_map('strval', array_column($rows, 'peso_repeticion'));
            } elseif ($tipo === 'pureza') {
                $cargado['componentes_materia_inerte'] = [];
                $cargado['componentes_otras_semillas'] = [];
                foreach ($rows as $componente) {
                    $cargado['componentes_' . $componente['tipo_componente']][] = [
                        'nombre' => (string) $componente['nombre_componente'],
                        'peso' => (string) $componente['peso_gramos'],
                    ];
                }
                foreach (['componentes_materia_inerte', 'componentes_otras_semillas'] as $campo) {
                    if ($cargado[$campo] === []) {
                        $cargado[$campo] = [['nombre' => '', 'peso' => '']];
                    }
                }
            } elseif ($tipo === 'malezas') {
                $cargado['especies_halladas'] = array_map(static fn(array $especie): array => [
                    'especie_latin' => (string) $especie['especie_latin'],
                    'numero_semillas' => (string) $especie['numero_semillas'],
                ], $rows);
                if ($rows === []) {
                    $cargado['especies_halladas'] = [['especie_latin' => '', 'numero_semillas' => '']];
                }
            } else {
                foreach ($rows as $repeticion) {
                    $numero = (int) $repeticion['nro_repeticion'];
                    if ($tipo === 'tetrazolio') {
                        $cargado['repeticiones'][$numero] = [
                            'viables' => (string) $repeticion['semillas_viables'],
                            'no_viables' => (string) $repeticion['semillas_no_viables'],
                        ];
                    } else {
                        foreach (array_keys(boletin_tetrazolio_soja_campos_repeticion()) as $campo) {
                            $cargado['repeticiones'][$numero][$campo] = (string) $repeticion[$campo];
                        }
                    }
                }
            }
        }
        $form = $cargado;
        return [$id, false];
    } catch (Throwable $throwable) {
        $errors[] = 'No se pudo cargar el boletín para edición. Verifica el ID y la empresa activa.';
        return [$id, true];
    }
}

function boletin_edicion_actualizar(string $tipo, int $id, array $parametros): void
{
    if (!db()->inTransaction()) {
        throw new LogicException('La actualización requiere una transacción.');
    }
    [$tabla, $clave, $hijas] = boletin_edicion_tablas($tipo);
    boletin_edicion_cabecera($tipo, $id, true);
    // El autor y la empresa originales no cambian al editar.
    unset($parametros[':id_usuario']);
    $asignaciones = [];
    foreach (array_keys($parametros) as $marcador) {
        if ($marcador !== ':id_empresa') {
            $asignaciones[] = substr($marcador, 1) . ' = ' . $marcador;
        }
    }
    $parametros[':id_edicion'] = $id;
    $statement = db()->prepare(
        "UPDATE $tabla SET " . implode(', ', $asignaciones) . " WHERE $clave = :id_edicion AND id_empresa = :id_empresa"
    );
    $statement->execute($parametros);
    foreach ($hijas as $hija) {
        $statement = db()->prepare("DELETE FROM $hija WHERE $clave = :id");
        $statement->execute([':id' => $id]);
    }
}
