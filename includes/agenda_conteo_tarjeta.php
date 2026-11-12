<article class="agenda-conteo">
    <strong class="d-block"><?php echo htmlspecialchars($tarea['etiqueta'], ENT_QUOTES, 'UTF-8'); ?></strong>
    <span class="d-block small">Fecha: <?php echo htmlspecialchars(format_date_short($tarea['fecha']), ENT_QUOTES, 'UTF-8'); ?></span>
    <span class="d-block small">Muestra: <?php echo htmlspecialchars($tarea['nro_muestra'], ENT_QUOTES, 'UTF-8'); ?></span>
    <span class="d-block small"><?php echo htmlspecialchars($tarea['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?></span>
    <span class="d-block small"><?php echo htmlspecialchars($tarea['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></span>
    <form method="post" action="/index.php?page=dashboard" class="mt-2">
        <input type="hidden" name="marcar_conteo_realizado" value="1">
        <input type="hidden" name="agenda_token" value="<?php echo htmlspecialchars($_SESSION['agenda_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="ingreso_id" value="<?php echo $tarea['id_libro_ingreso']; ?>">
        <input type="hidden" name="tipo_conteo" value="<?php echo htmlspecialchars($tarea['tipo'], ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit" class="btn btn-sm btn-brand">Marcar realizado<span class="visually-hidden">: <?php echo htmlspecialchars($tarea['etiqueta'] . ', muestra ' . $tarea['nro_muestra'], ENT_QUOTES, 'UTF-8'); ?></span></button>
    </form>
</article>
