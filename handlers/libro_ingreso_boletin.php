<?php

declare(strict_types=1);

if (
    is_logged_in()
    && $_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_GET['ingreso_id'])
    && is_string($_GET['page'] ?? null)
    && isset($navigation['boletines']['items'][$_GET['page'] ?? ''])
) {
    if (isset($_GET['editar'])) {
        $ingresoId = filter_var($_GET['ingreso_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $boletinId = filter_var($_GET['editar'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($ingresoId === false || $boletinId === false) {
            $errors[] = 'El ingreso o boletín seleccionado no es válido.';
        } else {
            try {
                $statement = db()->prepare(
                    'SELECT lb.id_boletin
                     FROM libro_ingreso_boletines lb
                     INNER JOIN libro_ingreso li ON li.id_libro_ingreso = lb.id_libro_ingreso
                     WHERE lb.id_libro_ingreso = :id_ingreso AND li.id_empresa = :id_empresa
                       AND lb.tipo_boletin = :tipo
                     LIMIT 1'
                );
                $statement->execute([
                    ':id_ingreso' => $ingresoId,
                    ':id_empresa' => current_empresa_id(),
                    ':tipo' => $_GET['page'],
                ]);
                if ((int) $statement->fetchColumn() !== $boletinId) {
                    $errors[] = 'El boletín no está vinculado con el ingreso seleccionado.';
                }
            } catch (PDOException $exception) {
                $errors[] = 'No se pudo verificar el vínculo del boletín. Aplica la migración database/migrations/2026-11-02_libro_ingreso_boletines.sql.';
            }
        }
    } else {
        $ingresoId = filter_var($_GET['ingreso_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($ingresoId === false) {
            $errors[] = 'El ingreso seleccionado no es válido.';
        } else {
            try {
                $statement = db()->prepare(
                    'SELECT li.id_cliente, li.id_especie, li.id_cultivar, li.nro_inase, li.nro_muestra,
                            li.fecha_entrada, li.origen, li.lote_observaciones, cl.nombre_cliente,
                            e.nombre_especie, e.cientifico_especie, cu.nombre_cultivar
                     FROM libro_ingreso li
                     INNER JOIN clientes cl ON cl.id_cliente = li.id_cliente AND cl.id_empresa = li.id_empresa
                     LEFT JOIN especies e ON e.id_especie = li.id_especie AND e.id_empresa = li.id_empresa
                     LEFT JOIN cultivares cu ON cu.id_cultivar = li.id_cultivar AND cu.id_empresa = li.id_empresa
                     WHERE li.id_libro_ingreso = :id AND li.id_empresa = :id_empresa
                     LIMIT 1'
                );
                $statement->execute([':id' => $ingresoId, ':id_empresa' => current_empresa_id()]);
                $ingresoBoletin = $statement->fetch();

                if ($ingresoBoletin === false) {
                    $errors[] = 'El ingreso seleccionado no existe o no pertenece a tu empresa.';
                } else {
                    $vinculoStatement = db()->prepare(
                        'SELECT lb.id_boletin
                         FROM libro_ingreso_boletines lb
                         INNER JOIN libro_ingreso li ON li.id_libro_ingreso = lb.id_libro_ingreso
                         WHERE lb.id_libro_ingreso = :id_ingreso AND li.id_empresa = :id_empresa
                           AND lb.tipo_boletin = :tipo
                         LIMIT 1'
                    );
                    $vinculoStatement->execute([
                        ':id_ingreso' => $ingresoId,
                        ':id_empresa' => current_empresa_id(),
                        ':tipo' => $_GET['page'],
                    ]);
                    $boletinVinculado = $vinculoStatement->fetchColumn();
                    if ($boletinVinculado === false) {
                        $detStatement = db()->prepare(
                            'SELECT d.nombre_determinacion_libro
                             FROM libro_ingreso_determinaciones lid
                             INNER JOIN determinaciones_libro_ingreso d ON d.id_determinacion_libro = lid.id_determinacion_libro
                             WHERE lid.id_libro_ingreso = :id AND d.id_empresa = :id_empresa'
                        );
                        $detStatement->execute([':id' => $ingresoId, ':id_empresa' => current_empresa_id()]);
                        $disponibles = libro_ingreso_boletines_disponibles(
                            $detStatement->fetchAll(PDO::FETCH_COLUMN),
                            (string) ($ingresoBoletin['nombre_especie'] ?? ''),
                            $ingresoBoletin['cientifico_especie'] ?? null
                        );
                        if (!in_array($_GET['page'], $disponibles, true)) {
                            $errors[] = 'El boletín seleccionado no corresponde a las determinaciones de este ingreso.';
                        } else {
                            $boletinVinculado = libro_ingreso_buscar_boletin_legacy($_GET['page'], $ingresoBoletin);
                            if ($boletinVinculado === false) {
                                $errors[] = 'Hay varios boletines anteriores que coinciden con este ingreso; no se puede asociar uno automáticamente.';
                            } elseif ($boletinVinculado !== null) {
                                libro_ingreso_vincular_boletin($ingresoId, $_GET['page'], $boletinVinculado);
                            }
                        }
                    }

                    if ($boletinVinculado !== false && $boletinVinculado !== null) {
                        header('Location: /index.php?page=' . rawurlencode($_GET['page'])
                            . '&editar=' . (int) $boletinVinculado
                            . '&ingreso_id=' . $ingresoId . '#boletin-form');
                        exit;
                    }

                    if (!$errors) {
                        switch ($_GET['page']) {
                            case 'boletin_peso_1000_semillas':
                                $boletinPesoForm = libro_ingreso_precargar_boletin($boletinPesoForm, $ingresoBoletin);
                                break;
                            case 'boletin_tetrazolio':
                                $boletinTetrazolioForm = libro_ingreso_precargar_boletin($boletinTetrazolioForm, $ingresoBoletin);
                                break;
                            case 'boletin_tetrazolio_soja':
                                $boletinTetrazolioSojaForm['id_cliente'] = (string) $ingresoBoletin['id_cliente'];
                                $boletinTetrazolioSojaForm['id_especie'] = (string) $ingresoBoletin['id_especie'];
                                $boletinTetrazolioSojaForm['muestra_nro'] = (string) $ingresoBoletin['nro_muestra'];
                                $boletinTetrazolioSojaForm['nro_interno'] = (string) $ingresoBoletin['nro_muestra'];
                                $boletinTetrazolioSojaForm['nombre_solicitante'] = (string) ($ingresoBoletin['nombre_cliente'] ?? '');
                                $boletinTetrazolioSojaForm['procedencia'] = (string) ($ingresoBoletin['origen'] ?? '');
                                $boletinTetrazolioSojaForm['fecha_recepcion'] = (string) ($ingresoBoletin['fecha_entrada'] ?? '');
                                $boletinTetrazolioSojaForm['cultivar'] = (string) ($ingresoBoletin['nombre_cultivar'] ?? '');
                                break;
                            case 'boletin_pureza':
                                $boletinPurezaForm = libro_ingreso_precargar_boletin($boletinPurezaForm, $ingresoBoletin);
                                break;
                            case 'boletin_malezas':
                                $boletinMalezasForm = libro_ingreso_precargar_boletin($boletinMalezasForm, $ingresoBoletin);
                                break;
                            case 'boletin_germinacion':
                                $boletinGerminacionForm = libro_ingreso_precargar_boletin($boletinGerminacionForm, $ingresoBoletin);
                                break;
                            case 'boletin_cold_test':
                            case 'boletin_envejecimiento_acelerado':
                                $boletinGerminacionForm = libro_ingreso_precargar_boletin($boletinGerminacionForm, $ingresoBoletin);
                                break;
                        }
                    }
                }
            } catch (PDOException $exception) {
                $errors[] = 'No se pudieron cargar los datos del ingreso o del boletín asociado. Verifica las migraciones del Libro de Ingreso.';
            }
        }
    }
}
