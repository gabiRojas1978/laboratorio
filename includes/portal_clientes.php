<?php

declare(strict_types=1);

function portal_actualizar_sesion(): void
{
    if (!is_logged_in()) {
        return;
    }
    $statement = db()->prepare(
        'SELECT u.tipo_usuario, u.id_cliente_usuario, u.id_empresa, u.nombre_usuario, u.es_superadmin
         FROM usuarios u
         JOIN empresas e ON e.id_empresa = u.id_empresa AND e.activo_empresa = 1
         LEFT JOIN clientes cl ON cl.id_cliente = u.id_cliente_usuario AND cl.id_empresa = u.id_empresa
         WHERE u.id_usuario = :id AND u.activo_usuario = 1
           AND (u.tipo_usuario = \'interno\' OR (u.tipo_usuario = \'cliente\' AND cl.id_cliente IS NOT NULL))'
    );
    $statement->execute([':id' => $_SESSION['user']['id']]);
    $row = $statement->fetch();
    if ($row === false) {
        unset($_SESSION['user']);
        header('Location: /index.php');
        exit;
    }
    $_SESSION['user']['tipo_usuario'] = $row['tipo_usuario'];
    $_SESSION['user']['id_cliente'] = $row['id_cliente_usuario'] === null ? null : (int) $row['id_cliente_usuario'];
    $_SESSION['user']['id_empresa'] = (int) $row['id_empresa'];
    $_SESSION['user']['name'] = $row['nombre_usuario'];
    $_SESSION['user']['es_superadmin'] = $row['tipo_usuario'] === 'interno' && (bool) $row['es_superadmin'];
}

function portal_solicitud_cliente_permitida(string $method, array $query): bool
{
    if ($method !== 'GET') {
        return false;
    }
    if (isset($query['portal_pdf'])) {
        return array_diff(array_keys($query), ['portal_pdf', 'tipo_documento', 'download']) === [];
    }
    return (!isset($query['page']) || $query['page'] === 'portal_clientes')
        && array_diff(array_keys($query), ['page', 'portal_page', 'categoria_documento', 'fecha_desde', 'fecha_hasta']) === [];
}

function portal_tipos_documentos(): array
{
    return [
        'certificadogeneral' => ['Certificado general', 'certificados', 'id_certificado', 'id_cliente_certificado', 'nro_analisis_certificado', 'fecha_emision_certificado', "b.tipo_certificado = 'certificadogeneral'", 'build_certificado_pdf'],
        'certificadoinase' => ['Certificado INASE', 'certificados', 'id_certificado', 'id_cliente_certificado', 'nro_analisis_certificado', 'fecha_emision_certificado', "b.tipo_certificado = 'certificadoinase'", 'build_certificado_pdf'],
        'certificadotetrazolio' => ['Certificado de tetrazolio', 'certificados', 'id_certificado', 'id_cliente_certificado', 'nro_analisis_certificado', 'fecha_emision_certificado', "b.tipo_certificado = 'certificadotetrazolio'", 'build_certificado_pdf'],
        'peso_1000_semillas' => ['Peso de 1000 semillas', 'boletines_peso_1000_semillas', 'id_boletin_peso', 'id_cliente', 'muestra_nro', 'fecha_boletin', '1=1', 'build_boletin_peso_1000_pdf'],
        'tetrazolio' => ['Tetrazolio', 'boletines_tetrazolio', 'id_boletin_tetrazolio', 'id_cliente', 'muestra_nro', 'fecha_finalizacion', '1=1', 'build_boletin_tetrazolio_pdf'],
        'tetrazolio_soja' => ['Tetrazolio (Soja)', 'boletines_tetrazolio_soja', 'id_boletin_tetrazolio_soja', 'id_cliente', 'muestra_nro', 'fecha_analisis', '1=1', 'build_boletin_tetrazolio_soja_pdf'],
        'pureza' => ['Pureza física-botánica', 'boletines_pureza', 'id_boletin_pureza', 'id_cliente', 'analisis_nro', 'fecha_analisis', '1=1', 'build_boletin_pureza_pdf'],
        'malezas' => ['Otras especies en número', 'boletines_malezas', 'id_boletin_malezas', 'id_cliente', 'analisis_nro', 'fecha_analisis', '1=1', 'build_boletin_malezas_pdf'],
        'germinacion' => ['Germinación', 'boletines_germinacion', 'id_boletin_germinacion', 'id_cliente', 'muestra_nro', 'fecha_segundo_conteo', "b.tipo_boletin = 'germinacion'", 'build_boletin_germinacion_pdf'],
        'cold_test' => ['Cold Test', 'boletines_germinacion', 'id_boletin_germinacion', 'id_cliente', 'muestra_nro', 'fecha_segundo_conteo', "b.tipo_boletin = 'cold_test'", 'build_boletin_germinacion_pdf'],
        'envejecimiento_acelerado' => ['Envejecimiento acelerado', 'boletines_germinacion', 'id_boletin_germinacion', 'id_cliente', 'muestra_nro', 'fecha_segundo_conteo', "b.tipo_boletin = 'envejecimiento_acelerado'", 'build_boletin_germinacion_pdf'],
    ];
}

function portal_tipo_categoria(string $tipo): string
{
    $definicion = portal_tipos_documentos()[$tipo] ?? null;
    if ($definicion === null) {
        throw new InvalidArgumentException('Tipo de documento inválido.');
    }
    return $definicion[1] === 'certificados' ? 'certificado' : 'boletin';
}

function portal_documentos_consulta(
    ?string $tipo,
    ?int $cliente,
    bool $publicados,
    ?string $categoria = null,
    ?string $fechaDesde = null,
    ?string $fechaHasta = null
): array
{
    $tipos = portal_tipos_documentos();
    if ($categoria !== null && !in_array($categoria, ['certificado', 'boletin'], true)) {
        throw new InvalidArgumentException('Selecciona una categoría de documento válida.');
    }
    if ($tipo !== null) {
        if (!isset($tipos[$tipo])) {
            throw new InvalidArgumentException('Tipo de documento inválido.');
        }
        $tipos = [$tipo => $tipos[$tipo]];
    }
    if ($categoria !== null) {
        $tipos = array_filter(
            $tipos,
            static fn (array $definicion, string $key): bool => portal_tipo_categoria($key) === $categoria,
            ARRAY_FILTER_USE_BOTH
        );
    }
    $consultas = [];
    $params = [];
    $indice = 0;
    foreach ($tipos as $key => [$label, $table, $id, $client, $ref, $date, $condition]) {
        $suffix = (string) $indice++;
        $consultas[] = "SELECT '$key' AS tipo_documento, b.$id AS id_documento, b.$client AS id_cliente,
            b.$ref AS referencia, b.$date AS fecha, cl.nombre_cliente, p.fecha_publicacion
            FROM $table b
            JOIN clientes cl ON cl.id_cliente = b.$client AND cl.id_empresa = b.id_empresa
            LEFT JOIN portal_documentos p ON p.id_empresa = b.id_empresa AND p.tipo_documento = '$key' AND p.id_documento = b.$id
            WHERE b.id_empresa = :empresa$suffix AND $condition"
            . ($cliente === null ? '' : " AND b.$client = :cliente$suffix")
            . ($publicados ? ' AND p.id_documento IS NOT NULL' : '');
        $params[":empresa$suffix"] = current_empresa_id();
        if ($fechaDesde !== null) {
            $consultas[array_key_last($consultas)] .= " AND b.$date >= :fecha_desde$suffix";
            $params[":fecha_desde$suffix"] = $fechaDesde;
        }
        if ($fechaHasta !== null) {
            $consultas[array_key_last($consultas)] .= " AND b.$date <= :fecha_hasta$suffix";
            $params[":fecha_hasta$suffix"] = $fechaHasta;
        }
        if ($cliente !== null) {
            $params[":cliente$suffix"] = $cliente;
        }
    }
    return [implode(' UNION ALL ', $consultas), $params];
}

function portal_documento(string $tipo, int $id, ?int $cliente, bool $publicado): ?array
{
    [$sql, $params] = portal_documentos_consulta($tipo, $cliente, $publicado);
    $statement = db()->prepare("SELECT * FROM ($sql) documentos WHERE id_documento = :id");
    $statement->execute($params + [':id' => $id]);
    $row = $statement->fetch();
    return $row === false ? null : $row;
}

function portal_usuario_vinculo(array &$form, array &$errors, ?int $editId = null): void
{
    $form['tipo_usuario'] = is_string($_POST['tipo_usuario'] ?? null) ? $_POST['tipo_usuario'] : '';
    $form['id_cliente_usuario'] = is_string($_POST['id_cliente_usuario'] ?? null) ? $_POST['id_cliente_usuario'] : '';
    if (!in_array($form['tipo_usuario'], ['interno', 'cliente'], true)) {
        $errors[] = 'Selecciona un tipo de acceso válido.';
        return;
    }
    if ($form['tipo_usuario'] === 'interno') {
        $form['id_cliente_usuario'] = '';
        return;
    }
    if ($editId !== null) {
        $statement = db()->prepare('SELECT es_superadmin FROM usuarios WHERE id_usuario = :id AND id_empresa = :empresa');
        $statement->execute([':id' => $editId, ':empresa' => current_empresa_id()]);
        if ($editId === ($_SESSION['user']['id'] ?? null) || (bool) $statement->fetchColumn()) {
            $errors[] = 'No se puede convertir tu propia cuenta ni un superadmin en usuario cliente.';
        }
    }
    $id = filter_var($form['id_cliente_usuario'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        $errors[] = 'Selecciona el cliente al que pertenece la cuenta.';
        return;
    }
    $statement = db()->prepare('SELECT id_cliente FROM clientes WHERE id_cliente = :id AND id_empresa = :empresa');
    $statement->execute([':id' => $id, ':empresa' => current_empresa_id()]);
    if ($statement->fetchColumn() === false) {
        $errors[] = 'El cliente seleccionado no pertenece al laboratorio.';
    }
}
