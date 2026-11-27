<?php

declare(strict_types=1);

$esClientePortal = is_client_user();
$tiposPortal = portal_tipos_documentos();
$documentosPortal = [];
$clientesPortal = [];
$tipoPortal = null;
$categoriaPortal = $_GET['categoria_documento'] ?? '';
$fechaDesdePortal = $_GET['fecha_desde'] ?? '';
$fechaHastaPortal = $_GET['fecha_hasta'] ?? '';
$clientePortal = $esClientePortal ? (int) $_SESSION['user']['id_cliente'] : null;
$portalTotalPages = 1;
$portalPagina = filter_var($_GET['portal_page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'default' => 1]]);
if (!isset($_SESSION['portal_token'])) {
    $_SESSION['portal_token'] = bin2hex(random_bytes(32));
}
try {
    if (!$esClientePortal) {
        $tipoSeleccionado = $_GET['tipo_documento'] ?? 'certificadogeneral';
        if (!is_string($tipoSeleccionado) || ($tipoSeleccionado !== '' && !isset($tiposPortal[$tipoSeleccionado]))) {
            throw new InvalidArgumentException('Selecciona un tipo de documento válido.');
        }
        $tipoPortal = $tipoSeleccionado === '' ? null : $tipoSeleccionado;
        $clienteRaw = $_GET['cliente'] ?? '';
        if ($clienteRaw !== '') {
            $clientePortal = filter_var($clienteRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($clientePortal === false) {
                throw new InvalidArgumentException('Selecciona un cliente válido.');
            }
        }
        $statement = db()->prepare('SELECT id_cliente, nombre_cliente FROM clientes WHERE id_empresa = :empresa ORDER BY nombre_cliente');
        $statement->execute([':empresa' => current_empresa_id()]);
        $clientesPortal = $statement->fetchAll();
    }
    if (!is_string($categoriaPortal) || !in_array($categoriaPortal, ['', 'certificado', 'boletin'], true)) {
        throw new InvalidArgumentException('Selecciona una categoría de documento válida.');
    }
    foreach (['fechaDesdePortal', 'fechaHastaPortal'] as $fechaVariable) {
        $fecha = $$fechaVariable;
        if (!is_string($fecha) || ($fecha !== '' && (
            DateTimeImmutable::createFromFormat('!Y-m-d', $fecha) === false
            || DateTimeImmutable::createFromFormat('!Y-m-d', $fecha)->format('Y-m-d') !== $fecha
        ))) {
            throw new InvalidArgumentException('Ingresa fechas válidas para filtrar los documentos.');
        }
    }
    if ($fechaDesdePortal !== '' && $fechaHastaPortal !== '' && $fechaDesdePortal > $fechaHastaPortal) {
        throw new InvalidArgumentException('La fecha desde no puede ser posterior a la fecha hasta.');
    }
    $tipoConsulta = $tipoPortal;
    if ($tipoConsulta !== null && $categoriaPortal !== '' && portal_tipo_categoria($tipoConsulta) !== $categoriaPortal) {
        $tipoConsulta = null;
    }
    [$sql, $params] = portal_documentos_consulta(
        $tipoConsulta,
        $clientePortal,
        $esClientePortal,
        $categoriaPortal === '' ? null : $categoriaPortal,
        $fechaDesdePortal === '' ? null : $fechaDesdePortal,
        $fechaHastaPortal === '' ? null : $fechaHastaPortal
    );
    $statement = db()->prepare("SELECT COUNT(*) FROM ($sql) documentos");
    $statement->execute($params);
    $portalTotalPages = max(1, (int) ceil((int) $statement->fetchColumn() / 20));
    $portalPagina = min($portalPagina, $portalTotalPages);
    $statement = db()->prepare("SELECT * FROM ($sql) documentos ORDER BY fecha DESC, tipo_documento, id_documento DESC LIMIT 20 OFFSET " . (($portalPagina - 1) * 20));
    $statement->execute($params);
    $documentosPortal = $statement->fetchAll();
} catch (InvalidArgumentException $exception) {
    $errors[] = $exception->getMessage();
} catch (PDOException $exception) {
    error_log('Error al listar documentos del portal: ' . $exception->getMessage());
    $errors[] = 'No se pudieron cargar los documentos. Verifica las migraciones del portal y de los boletines.';
}
?>
<section class="section-panel">
    <h2><?php echo $esClientePortal ? 'Mis boletines y certificados' : 'Documentos del portal de clientes'; ?></h2>
    <p><?php echo $esClientePortal ? 'Aquí puedes consultar y descargar los documentos que el laboratorio habilitó para tu cuenta.' : 'Selecciona los documentos revisados que deseas habilitar para su cliente. Retirar del portal no elimina el documento original.'; ?></p>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>
    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <form method="get" action="/index.php" class="row g-3 mb-3">
            <input type="hidden" name="page" value="portal_clientes">
            <div class="col-md-3">
                <label for="portal_categoria" class="form-label">Categoría</label>
                <select id="portal_categoria" name="categoria_documento" class="form-select">
                    <option value="">Todas</option>
                    <option value="certificado" <?php echo $categoriaPortal === 'certificado' ? 'selected' : ''; ?>>Certificados</option>
                    <option value="boletin" <?php echo $categoriaPortal === 'boletin' ? 'selected' : ''; ?>>Boletines</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="portal_fecha_desde" class="form-label">Fecha desde</label>
                <input type="date" id="portal_fecha_desde" name="fecha_desde" class="form-control" value="<?php echo htmlspecialchars(is_string($fechaDesdePortal) ? $fechaDesdePortal : '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-3">
                <label for="portal_fecha_hasta" class="form-label">Fecha hasta</label>
                <input type="date" id="portal_fecha_hasta" name="fecha_hasta" class="form-control" value="<?php echo htmlspecialchars(is_string($fechaHastaPortal) ? $fechaHastaPortal : '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <?php if (!$esClientePortal): ?>
            <div class="col-md-5">
                <label for="portal_tipo" class="form-label">Tipo de documento</label>
                <select id="portal_tipo" name="tipo_documento" class="form-select">
                    <option value="" <?php echo $tipoPortal === null ? 'selected' : ''; ?>>Todos</option>
                    <?php foreach ($tiposPortal as $key => $def): ?>
                        <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $tipoPortal === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($def[0], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label for="portal_cliente" class="form-label">Cliente</label>
                <select id="portal_cliente" name="cliente" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($clientesPortal as $cliente): ?>
                        <option value="<?php echo (int) $cliente['id_cliente']; ?>" <?php echo $clientePortal === (int) $cliente['id_cliente'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cliente['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2 align-self-end"><button class="btn btn-brand" type="submit">Filtrar</button></div>
            <div class="col-md-2 align-self-end"><a href="/index.php?page=portal_clientes" class="btn btn-outline-brand">Limpiar</a></div>
    </form>
    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle">
            <thead><tr><th>Tipo</th><th>Documento</th><th>Muestra / Análisis</th><th>Fecha</th><?php if (!$esClientePortal): ?><th>Cliente</th><th>Estado</th><?php endif; ?><th>Acciones</th></tr></thead>
            <tbody>
                <?php if ($documentosPortal === []): ?>
                    <tr><td colspan="<?php echo $esClientePortal ? 5 : 7; ?>" class="text-center">No hay documentos disponibles.</td></tr>
                <?php endif; ?>
                <?php foreach ($documentosPortal as $doc): ?>
                    <?php $pdfUrl = '/index.php?' . http_build_query(['portal_pdf' => $doc['id_documento'], 'tipo_documento' => $doc['tipo_documento']]); ?>
                    <tr>
                        <td><?php echo portal_tipo_categoria($doc['tipo_documento']) === 'certificado' ? 'Certificado' : 'Boletín'; ?></td>
                        <td><?php echo htmlspecialchars($tiposPortal[$doc['tipo_documento']][0], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) $doc['referencia'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(format_date_short($doc['fecha']), ENT_QUOTES, 'UTF-8'); ?></td>
                        <?php if (!$esClientePortal): ?>
                            <td><?php echo htmlspecialchars($doc['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo $doc['fecha_publicacion'] === null ? 'No publicado' : 'Habilitado'; ?></td>
                        <?php endif; ?>
                        <td>
                            <a class="btn btn-sm btn-outline-light" href="<?php echo htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Ver PDF</a>
                            <a class="btn btn-sm btn-outline-light" href="<?php echo htmlspecialchars($pdfUrl . '&download=1', ENT_QUOTES, 'UTF-8'); ?>">Descargar</a>
                            <?php if (!$esClientePortal): ?>
                                <form method="post" class="d-inline" onsubmit="return confirm('¿Cambiar la disponibilidad de este documento en el portal del cliente?');">
                                    <input type="hidden" name="portal_token" value="<?php echo htmlspecialchars($_SESSION['portal_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="tipo_documento" value="<?php echo htmlspecialchars($doc['tipo_documento'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="id_documento" value="<?php echo (int) $doc['id_documento']; ?>">
                                    <button class="btn btn-sm <?php echo $doc['fecha_publicacion'] === null ? 'btn-outline-success' : 'btn-outline-warning'; ?>" name="portal_publicar" value="<?php echo $doc['fecha_publicacion'] === null ? 'publicar' : 'retirar'; ?>"><?php echo $doc['fecha_publicacion'] === null ? 'Habilitar para cliente' : 'Retirar del portal'; ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($portalTotalPages > 1): ?>
        <nav aria-label="Paginación de documentos"><ul class="pagination flex-wrap">
            <?php for ($number = 1; $number <= $portalTotalPages; $number++): ?>
                <?php $query = array_intersect_key($_GET, array_flip(['tipo_documento', 'cliente', 'categoria_documento', 'fecha_desde', 'fecha_hasta'])); ?>
                <li class="page-item <?php echo $number === $portalPagina ? 'active' : ''; ?>"><a class="page-link" href="<?php echo htmlspecialchars('/index.php?' . http_build_query($query + ['page' => 'portal_clientes', 'portal_page' => $number]), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $number; ?></a></li>
            <?php endfor; ?>
        </ul></nav>
    <?php endif; ?>
</section>
