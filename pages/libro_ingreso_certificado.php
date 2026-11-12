<?php

declare(strict_types=1);

$ingresoCertificado = null;
$certificadoDestino = $_GET['tipo'] ?? '';
$certificadoCandidatos = [];
try {
    $ingresoCertificado = libro_ingreso_para_certificado($_GET['ingreso_id'] ?? '', current_empresa_id());
    if (!is_string($certificadoDestino) || ($certificadoDestino !== '' && !isset($navigation['certificados']['items'][$certificadoDestino]))) {
        throw new InvalidArgumentException('El tipo de certificado seleccionado no es válido.');
    }
    if ($certificadoDestino !== '') {
        foreach (libro_ingreso_fuentes_certificado($certificadoDestino) as $fuente => $label) {
            $certificadoCandidatos[$fuente] = libro_ingreso_candidatos_certificado($ingresoCertificado, current_empresa_id(), $fuente);
        }
    }
} catch (InvalidArgumentException $exception) {
    $errors[] = $exception->getMessage();
} catch (PDOException $exception) {
    $errors[] = 'No se pudieron cargar el ingreso o los boletines disponibles.';
}
?>
<section class="section-panel">
    <h2>Certificado desde el ingreso</h2>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>
    <?php if ($ingresoCertificado !== null && $errors === []): ?>
        <p>Muestra N° <strong><?php echo htmlspecialchars((string) $ingresoCertificado['nro_muestra'], ENT_QUOTES, 'UTF-8'); ?></strong>. El certificado no se emitirá hasta que lo revises y guardes.</p>
        <form method="get" action="/index.php" class="row g-3 mb-4">
            <input type="hidden" name="page" value="libro_ingreso_certificado">
            <input type="hidden" name="ingreso_id" value="<?php echo (int) $ingresoCertificado['id_libro_ingreso']; ?>">
            <div class="col-md-8">
                <label for="certificadoDestino" class="form-label">Tipo de certificado</label>
                <select name="tipo" id="certificadoDestino" class="form-select" required>
                    <option value="">Selecciona un tipo</option>
                    <?php foreach ($navigation['certificados']['items'] as $destino => $label): ?>
                        <option value="<?php echo htmlspecialchars($destino, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $destino === $certificadoDestino ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 align-self-end"><button type="submit" class="btn btn-brand">Ver boletines disponibles</button></div>
        </form>
        <?php if ($certificadoDestino !== ''): ?>
            <form method="get" action="/index.php" class="row g-3">
                <input type="hidden" name="page" value="<?php echo htmlspecialchars($certificadoDestino, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="ingreso_id" value="<?php echo (int) $ingresoCertificado['id_libro_ingreso']; ?>">
                <p>Elige los boletines que deseas importar. No se selecciona ninguno automáticamente. Germinación solo incluye ensayos finalizados de esta muestra, cliente y especie.</p>
                <?php if ($certificadoDestino !== 'CertificadoTetrazolio'): ?>
                    <div class="alert alert-warning">Pureza no tiene N° de muestra: se muestran los análisis del mismo cliente y especie. Confirma manualmente el N° de análisis y la fecha antes de seleccionarlo. Para germinación curada, elige explícitamente el ensayo con tratamiento.</div>
                <?php else: ?>
                    <div class="alert alert-warning">El boletín de tetrazolio aporta no viables y el total viable como referencia. Las cuatro categorías viables, vigor y daños se completan manualmente.</div>
                <?php endif; ?>
                <?php foreach (libro_ingreso_fuentes_certificado($certificadoDestino) as $fuente => $label): ?>
                    <div class="col-md-6">
                        <label for="certFuente_<?php echo $fuente; ?>" class="form-label"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></label>
                        <select name="boletin_<?php echo $fuente; ?>" id="certFuente_<?php echo $fuente; ?>" class="form-select">
                            <option value=""><?php echo $certificadoCandidatos[$fuente] === [] ? 'Sin boletines disponibles; completar manualmente' : 'No importar; completar manualmente'; ?></option>
                            <?php foreach ($certificadoCandidatos[$fuente] as $candidato): ?>
                                <option value="<?php echo (int) $candidato['id_origen']; ?>"><?php echo htmlspecialchars(
                                    '#' . $candidato['id_origen'] . ' — Análisis: ' . ($candidato['analisis_nro'] ?? 'sin número')
                                    . ' — Muestra: ' . ($candidato['muestra_nro'] ?? 'no registrada')
                                    . ' — Fecha: ' . ($candidato['fecha_origen'] ?? 'sin fecha'), ENT_QUOTES, 'UTF-8'
                                ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
                <div class="col-12"><button type="submit" class="btn btn-brand">Precargar certificado</button></div>
            </form>
        <?php endif; ?>
    <?php endif; ?>
    <a class="btn btn-outline-brand mt-3" href="/index.php?page=libro_ingreso">Volver al libro de ingreso</a>
</section>
