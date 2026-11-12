<?php

declare(strict_types=1);

$agendaHoy = new DateTimeImmutable('today');
$agenda = null;
try {
    $agenda = agenda_conteos_cargar(current_empresa_id(), $agendaHoy);
} catch (PDOException $exception) {
    error_log('No se pudo cargar la agenda de conteos: ' . $exception->getMessage());
    $errors[] = 'No se pudo cargar la agenda de conteos. Verifica que las migraciones de especies y libro de ingreso estén aplicadas.';
}
if (!isset($_SESSION['agenda_token'])) {
    $_SESSION['agenda_token'] = bin2hex(random_bytes(32));
}
$agendaDiasSemana = [1 => 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
?>
<section class="section-panel">
    <h2>Agenda de conteos</h2>
    <p>Próximos 7 días: del <?php echo $agendaHoy->format('d/m/Y'); ?> al <?php echo $agendaHoy->modify('+6 days')->format('d/m/Y'); ?>.
        Fechas calculadas desde el inicio de análisis y los días corridos de cada especie.</p>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>
    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success" role="status"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($agenda !== null): ?>
        <?php if ($agenda['sin_programar'] > 0): ?>
            <div class="alert alert-warning">
                <?php echo $agenda['sin_programar']; ?> ingreso(s) con conteos pendientes sin programar:
                falta la fecha de inicio de análisis, la especie o sus días de conteo.
                Revisa el <a href="/index.php?page=libro_ingreso">libro de ingreso</a> y las <a href="/index.php?page=especies">especies</a>.
            </div>
        <?php endif; ?>
        <div class="agenda-semana">
            <?php foreach ($agenda['dias'] as $fecha => $tareas): ?>
                <?php $dia = new DateTimeImmutable($fecha); ?>
                <section class="agenda-dia <?php echo $fecha === $agendaHoy->format('Y-m-d') ? 'agenda-dia--hoy' : ''; ?>">
                    <h3 class="h6">
                        <?php echo $agendaDiasSemana[(int) $dia->format('N')]; ?>
                        <span class="d-block mt-1"><?php echo $dia->format('d/m'); ?><?php echo $fecha === $agendaHoy->format('Y-m-d') ? ' · Hoy' : ''; ?></span>
                    </h3>
                    <?php if ($tareas === []): ?>
                        <p class="small mb-0">Sin conteos pendientes.</p>
                    <?php else: ?>
                        <?php foreach ($tareas as $tarea): ?>
                            <?php require __DIR__ . '/../includes/agenda_conteo_tarjeta.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($agenda !== null): ?>
    <section class="section-panel">
        <h2>Conteos vencidos <span class="badge bg-danger"><?php echo count($agenda['vencidos']); ?></span></h2>
        <p>Conteos de fechas anteriores a hoy que todavía no se marcaron como realizados.</p>
        <?php if ($agenda['vencidos'] === []): ?>
            <div class="alert alert-success mb-0">No hay conteos vencidos.</div>
        <?php else: ?>
            <div class="agenda-vencidos">
                <?php foreach ($agenda['vencidos'] as $tarea): ?>
                    <?php require __DIR__ . '/../includes/agenda_conteo_tarjeta.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>