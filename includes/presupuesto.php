<?php

declare(strict_types=1);

function presupuesto_validar_ids(mixed $ids): array
{
    if (!is_array($ids) || $ids === []) {
        throw new InvalidArgumentException('Selecciona al menos un ingreso para presupuestar.');
    }
    $validos = [];
    foreach ($ids as $id) {
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new InvalidArgumentException('La selección de ingresos no es válida.');
        }
        $validos[$id] = $id;
    }
    return array_values($validos);
}

function presupuesto_obtener(array $ingresoIds): array
{
    $ingresoIds = presupuesto_validar_ids($ingresoIds);
    $placeholders = implode(',', array_fill(0, count($ingresoIds), '?'));
    $params = array_merge([current_empresa_id()], $ingresoIds);
    $statement = db()->prepare(
        'SELECT li.id_libro_ingreso, li.id_cliente, li.nro_muestra, li.fecha_entrada,
                cl.nombre_cliente, cl.direccion_cliente, cl.localidad_cliente, cl.provincia_cliente,
                cl.telefono_cliente, cl.cuit_cliente
         FROM libro_ingreso li
         JOIN clientes cl ON cl.id_cliente = li.id_cliente AND cl.id_empresa = li.id_empresa
         WHERE li.id_empresa = ? AND li.id_libro_ingreso IN (' . $placeholders . ')
         ORDER BY li.id_libro_ingreso'
    );
    $statement->execute($params);
    $ingresos = $statement->fetchAll();
    if (count($ingresos) !== count($ingresoIds)) {
        throw new InvalidArgumentException('Algún ingreso no existe o no pertenece al laboratorio.');
    }
    $cliente = $ingresos[0];
    $muestras = [];
    foreach ($ingresos as $ingreso) {
        if ((int) $ingreso['id_cliente'] !== (int) $cliente['id_cliente']) {
            throw new InvalidArgumentException('Selecciona únicamente ingresos del mismo cliente.');
        }
        $muestras[(int) $ingreso['id_libro_ingreso']] = [
            'ingreso' => $ingreso,
            'determinaciones' => [],
            'total_centavos' => 0,
        ];
    }
    $statement = db()->prepare(
        'SELECT lid.id_libro_ingreso, d.nombre_determinacion_libro, d.costo
         FROM libro_ingreso_determinaciones lid
         JOIN libro_ingreso li ON li.id_libro_ingreso = lid.id_libro_ingreso
         JOIN determinaciones_libro_ingreso d ON d.id_determinacion_libro = lid.id_determinacion_libro AND d.id_empresa = li.id_empresa
         WHERE li.id_empresa = ? AND li.id_libro_ingreso IN (' . $placeholders . ')
         ORDER BY lid.id_libro_ingreso, d.nombre_determinacion_libro'
    );
    $statement->execute($params);
    $totalCentavos = 0;
    foreach ($statement->fetchAll() as $determinacion) {
        $costo = (string) $determinacion['costo'];
        if (!preg_match('/^(\d+)\.(\d{2})$/', $costo, $partes)) {
            throw new RuntimeException('El precio de una determinación no es válido.');
        }
        $determinacion['centavos'] = (int) $partes[1] * 100 + (int) $partes[2];
        $totalCentavos += $determinacion['centavos'];
        $id = (int) $determinacion['id_libro_ingreso'];
        $muestras[$id]['determinaciones'][] = $determinacion;
        $muestras[$id]['total_centavos'] += $determinacion['centavos'];
    }
    return [
        'cliente' => $cliente,
        'emails_cliente' => get_cliente_emails((int) $cliente['id_cliente']),
        'muestras' => array_values($muestras),
        'total_centavos' => $totalCentavos,
    ];
}

function presupuesto_importe(int $centavos): string
{
    return '$ ' . number_format(intdiv($centavos, 100), 0, ',', '.') . ',' . str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
}

function presupuesto_html(array $presupuesto, array $laboratorio, ?string $logo): string
{
    foreach ($presupuesto['muestras'] as $muestra) {
        if ($muestra['determinaciones'] === []) {
            throw new RuntimeException('La muestra ' . $muestra['ingreso']['nro_muestra'] . ' no tiene determinaciones para presupuestar.');
        }
    }
    if (trim($laboratorio['nombre_laboratorio']) === '' || $logo === null) {
        throw new RuntimeException('Configura el nombre y el logo del laboratorio antes de generar el presupuesto.');
    }
    $escapar = static fn ($valor): string => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
    $cliente = $presupuesto['cliente'];
    $html = '<style>body{font-family:sans-serif;font-size:10pt} table{width:100%;border-collapse:collapse}'
        . 'th,td{padding:7px;border-bottom:1px solid #ccc} th{text-align:left;background:#eee} .importe{text-align:right}'
        . 'h1{font-size:18pt} .datos{line-height:1.6}</style>';
    $html .= '<table><tr><td style="width:28%;border:0"><img src="' . $escapar(str_replace('\\', '/', $logo)) . '" style="max-width:45mm;max-height:25mm"></td>'
        . '<td style="border:0" class="datos"><strong>' . $escapar($laboratorio['nombre_laboratorio']) . '</strong><br>';
    foreach (['direccion_laboratorio', 'localidad_pais', 'telefono_laboratorio', 'email_laboratorio', 'cuit_laboratorio'] as $campo) {
        if ($laboratorio[$campo] !== '') {
            $html .= ($campo === 'cuit_laboratorio' ? 'CUIT: ' : '') . $escapar($laboratorio[$campo]) . '<br>';
        }
    }
    $html .= '</td></tr></table><h1>Presupuesto</h1><p>Fecha: ' . date('d/m/Y')
        . '</p><div class="datos"><strong>Cliente: '
        . $escapar($cliente['nombre_cliente']) . '</strong><br>';
    foreach (['direccion_cliente', 'localidad_cliente', 'provincia_cliente', 'telefono_cliente', 'cuit_cliente'] as $campo) {
        if (trim((string) ($cliente[$campo] ?? '')) !== '') {
            $html .= ($campo === 'cuit_cliente' ? 'CUIT: ' : '') . $escapar($cliente[$campo]) . '<br>';
        }
    }
    if ($presupuesto['emails_cliente'] !== []) {
        $html .= 'Correo: ' . $escapar(implode(', ', $presupuesto['emails_cliente'])) . '<br>';
    }
    $html .= '</div><br><table><thead><tr><th>Determinación</th><th class="importe">Precio (ARS)</th></tr></thead><tbody>';
    foreach ($presupuesto['muestras'] as $muestra) {
        $html .= '<tr><td colspan="2"><strong>Muestra N° ' . $escapar($muestra['ingreso']['nro_muestra']) . '</strong></td></tr>';
        foreach ($muestra['determinaciones'] as $determinacion) {
            $html .= '<tr><td>' . $escapar($determinacion['nombre_determinacion_libro']) . '</td><td class="importe">'
                . $escapar(presupuesto_importe($determinacion['centavos'])) . '</td></tr>';
        }
        $html .= '<tr><td>Subtotal muestra</td><td class="importe">' . $escapar(presupuesto_importe($muestra['total_centavos'])) . '</td></tr>';
    }
    $html .= '<tr><td><strong>Total</strong></td><td class="importe"><strong>'
        . $escapar(presupuesto_importe($presupuesto['total_centavos'])) . '</strong></td></tr></tbody></table>'
        . '<p>Importes en pesos argentinos. No se adicionan impuestos.</p>';
    return $html;
}

function presupuesto_pdf(array $presupuesto, array $laboratorio, ?string $logo): array
{
    $html = presupuesto_html($presupuesto, $laboratorio, $logo);
    require_once __DIR__ . '/../vendor/autoload.php';
    $pdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir()]);
    $pdf->WriteHTML($html);
    return ['content' => $pdf->Output('', 'S'), 'filename' => 'presupuesto.pdf'];
}
