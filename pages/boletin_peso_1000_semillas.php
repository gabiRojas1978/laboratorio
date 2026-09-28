<?php

declare(strict_types=1);

$clientesOptions = [];
try {
    $clientesOptionsStatement = db()->prepare('SELECT id_cliente, nombre_cliente FROM clientes WHERE id_empresa = :id_empresa ORDER BY nombre_cliente ASC');
    $clientesOptionsStatement->execute([':id_empresa' => current_empresa_id()]);
    $clientesOptions = $clientesOptionsStatement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el selector de clientes.';
}

$especiesOptions = [];
try {
    $especiesOptionsStatement = db()->prepare('SELECT id_especie, nombre_especie FROM especies WHERE id_empresa = :id_empresa ORDER BY nombre_especie ASC');
    $especiesOptionsStatement->execute([':id_empresa' => current_empresa_id()]);
    $especiesOptions = $especiesOptionsStatement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el selector de especies.';
}

$boletinFiltroCliente = trim((string) ($_GET['filtro_cliente'] ?? ''));
$boletinFiltroClienteId = filter_var($boletinFiltroCliente, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$boletinesPeso = [];
try {
    $boletinesConditions = ['b.id_empresa = :id_empresa'];
    $boletinesParams = [':id_empresa' => current_empresa_id()];
    if ($boletinFiltroClienteId !== false) {
        $boletinesConditions[] = 'b.id_cliente = :filtro_cliente';
        $boletinesParams[':filtro_cliente'] = $boletinFiltroClienteId;
    }

    $boletinesStatement = db()->prepare(
        'SELECT b.id_boletin_peso, b.muestra_nro, b.fecha_boletin, b.analista, b.promedio_peso, b.desvio_estandar, b.coeficiente_variacion, b.resultado_peso_mil, e.nombre_especie, cl.nombre_cliente
         FROM boletines_peso_1000_semillas b
         LEFT JOIN especies e ON e.id_especie = b.id_especie
         LEFT JOIN clientes cl ON cl.id_cliente = b.id_cliente
         WHERE ' . implode(' AND ', $boletinesConditions) . '
         ORDER BY b.id_boletin_peso DESC'
    );
    $boletinesStatement->execute($boletinesParams);
    $boletinesPeso = $boletinesStatement->fetchAll();
} catch (Throwable $throwable) {
    $errors[] = 'No se pudo cargar el listado de boletines. Verifica que las tablas existan (ver migración database/migrations/2026-09-24_boletin_peso_1000_semillas.sql).';
}
?>
<section class="section-panel">
    <h2>Boletín: Peso de 1000 semillas</h2>
    <p>Cargá las repeticiones de peso (mínimo 2, hasta 16) para calcular varianza, desvío estándar, coeficiente de variación y el resultado final.</p>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" class="row g-3 mt-1">
        <input type="hidden" name="save_boletin_peso" value="1">

        <div class="col-md-4">
            <label for="id_cliente" class="form-label">Cliente *</label>
            <select class="form-select" id="id_cliente" name="id_cliente" required>
                <option value="">Selecciona un cliente</option>
                <?php foreach ($clientesOptions as $cliente): ?>
                    <option value="<?php echo htmlspecialchars((string) $cliente['id_cliente'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $boletinPesoForm['id_cliente'] === (string) $cliente['id_cliente'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string) $cliente['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label for="muestra_nro" class="form-label">Muestra N° *</label>
            <input type="text" class="form-control" id="muestra_nro" name="muestra_nro" maxlength="100" value="<?php echo htmlspecialchars($boletinPesoForm['muestra_nro'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>

        <div class="col-md-4">
            <label for="id_especie" class="form-label">Especie</label>
            <select class="form-select" id="id_especie" name="id_especie">
                <option value="">Selecciona una especie</option>
                <?php foreach ($especiesOptions as $especie): ?>
                    <option value="<?php echo htmlspecialchars((string) $especie['id_especie'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $boletinPesoForm['id_especie'] === (string) $especie['id_especie'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string) $especie['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label for="fecha_boletin" class="form-label">Fecha</label>
            <input type="date" class="form-control" id="fecha_boletin" name="fecha_boletin" value="<?php echo htmlspecialchars($boletinPesoForm['fecha_boletin'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-md-6">
            <label for="analista" class="form-label">Analista</label>
            <input type="text" class="form-control" id="analista" name="analista" maxlength="150" value="<?php echo htmlspecialchars($boletinPesoForm['analista'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="col-12">
            <label class="form-label">Repeticiones — Peso (X) en gramos</label>
            <div id="boletin-peso-repeticiones" class="row g-2">
                <?php foreach ($boletinPesoForm['pesos'] as $index => $peso): ?>
                    <div class="col-6 col-md-3 col-lg-2 boletin-peso-row">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><?php echo $index + 1; ?></span>
                            <input type="number" step="0.0001" min="0" class="form-control" name="pesos[]" value="<?php echo htmlspecialchars((string) $peso, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm btn-outline-brand mt-2" id="boletin-peso-add" data-max="16">Agregar repetición</button>
            <div class="form-text">Según ISTA: si el coeficiente de variación excede 6,0 (gramíneas no desnudas) o 4,0 (otras semillas), se deben contar 8 repeticiones adicionales (hasta 16 en total).</div>
        </div>

        <div class="col-12">
            <label for="observaciones" class="form-label">Observaciones</label>
            <textarea class="form-control" id="observaciones" name="observaciones" maxlength="500" rows="2"><?php echo htmlspecialchars($boletinPesoForm['observaciones'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-brand">Guardar boletín</button>
            <a href="/index.php?page=boletin_peso_1000_semillas" class="btn btn-outline-brand">Cancelar</a>
        </div>
    </form>
</section>

<section class="section-panel" id="boletines-listado">
    <h2>Boletines cargados</h2>
    <p>Listado de boletines de peso de 1000 semillas guardados.</p>

    <form method="get" class="row g-3 mb-3" action="/index.php#boletines-listado">
        <input type="hidden" name="page" value="boletin_peso_1000_semillas">
        <div class="col-md-4">
            <label for="filtro_cliente" class="form-label">Filtrar por cliente</label>
            <select class="form-select" id="filtro_cliente" name="filtro_cliente">
                <option value="">Todos</option>
                <?php foreach ($clientesOptions as $clienteOption): ?>
                    <option value="<?php echo htmlspecialchars((string) $clienteOption['id_cliente'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $boletinFiltroCliente === (string) $clienteOption['id_cliente'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string) $clienteOption['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-brand w-100"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="/index.php?page=boletin_peso_1000_semillas#boletines-listado" class="btn btn-outline-brand" title="Limpiar filtro"><i class="bi bi-x-circle"></i></a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Muestra</th>
                    <th>Cliente</th>
                    <th>Especie</th>
                    <th>Fecha</th>
                    <th>Analista</th>
                    <th>Promedio</th>
                    <th>Desvío estándar</th>
                    <th>Coef. Variación (%)</th>
                    <th>Resultado (peso/1000)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($boletinesPeso === []): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted">No hay boletines cargados aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($boletinesPeso as $boletin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $boletin['id_boletin_peso'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $boletin['muestra_nro'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($boletin['nombre_cliente'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($boletin['nombre_especie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(format_date_short($boletin['fecha_boletin'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($boletin['analista'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($boletin['promedio_peso'] !== null ? number_format((float) $boletin['promedio_peso'], 4) : '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($boletin['desvio_estandar'] !== null ? number_format((float) $boletin['desvio_estandar'], 4) : '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($boletin['coeficiente_variacion'] !== null ? number_format((float) $boletin['coeficiente_variacion'], 2) : '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($boletin['resultado_peso_mil'] !== null ? number_format((float) $boletin['resultado_peso_mil'], 4) : '', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('boletin-peso-repeticiones');
        const addButton = document.getElementById('boletin-peso-add');
        if (!container || !addButton) return;

        addButton.addEventListener('click', function() {
            const rows = container.querySelectorAll('.boletin-peso-row');
            const max = parseInt(addButton.dataset.max, 10) || 16;
            if (rows.length >= max) return;

            const row = document.createElement('div');
            row.className = 'col-6 col-md-3 col-lg-2 boletin-peso-row';
            row.innerHTML = '<div class="input-group input-group-sm">' +
                '<span class="input-group-text">' + (rows.length + 1) + '</span>' +
                '<input type="number" step="0.0001" min="0" class="form-control" name="pesos[]" value="">' +
                '</div>';
            container.appendChild(row);
        });
    });
</script>