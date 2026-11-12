<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/libro_ingreso.php';
require_once __DIR__ . '/../includes/libro_ingreso_certificado.php';

$ingreso = [
    'id_cliente' => 7,
    'id_especie' => 3,
    'id_cultivar' => 2,
    'nro_inase' => 123,
    'nro_muestra' => 456,
    'fecha_entrada' => '2026-10-01',
    'lote_observaciones' => 'Lote A',
];

// Las fuentes del certificado INASE incluyen tetrazolio (no soja); las del general, cold test y envejecimiento.
$fuentesInase = libro_ingreso_fuentes_certificado('CertificadoINASE');
foreach (['germinacion', 'pureza', 'peso', 'malezas', 'tetrazolio'] as $fuente) {
    if (!isset($fuentesInase[$fuente])) {
        throw new RuntimeException('Falta la fuente ' . $fuente . ' en el certificado INASE.');
    }
}
if (isset($fuentesInase['germinacion_curado']) || isset($fuentesInase['cold_test']) || isset($fuentesInase['envejecimiento'])) {
    throw new RuntimeException('El certificado INASE no debe ofrecer curado, cold test ni envejecimiento.');
}

$fuentesGeneral = libro_ingreso_fuentes_certificado('Certificadogeneral');
foreach (['germinacion', 'germinacion_curado', 'pureza', 'peso', 'malezas', 'cold_test', 'envejecimiento'] as $fuente) {
    if (!isset($fuentesGeneral[$fuente])) {
        throw new RuntimeException('Falta la fuente ' . $fuente . ' en el certificado general.');
    }
}
if (isset($fuentesGeneral['tetrazolio'])) {
    throw new RuntimeException('El certificado general no debe ofrecer tetrazolio.');
}

// INASE: peso, plántulas anormales y tetrazolio se vuelcan juntos en Otras determinaciones.
[$formInase] = libro_ingreso_precargar_certificado([], $ingreso, 'CertificadoINASE', [
    'germinacion' => [
        'fecha_origen' => '2026-10-20',
        'fecha_siembra' => '2026-10-10',
        'fecha_segundo_conteo' => '2026-10-20',
        'resultado_plantulas_normales' => '90.00',
        'resultado_plantulas_anormales' => '4.00',
        'resultado_semillas_duras' => '1.00',
        'resultado_semillas_frescas' => '2.00',
        'resultado_semillas_muertas' => '3.00',
    ],
    'peso' => ['fecha_origen' => '2026-10-15', 'resultado_peso_mil' => '35.40'],
    'tetrazolio' => [
        'fecha_origen' => '2026-10-18',
        'resultado_porcentaje' => '95.00',
        'resultado_no_viables_porcentaje' => '5.00',
    ],
]);

$esperado = 'Plántulas anormales: 4.00 %; Peso de 1000 semillas: 35.40 g; Viabilidad por tetrazolio: 95.00 %; No viables por tetrazolio: 5.00 %';
if ($formInase['otras_determinaciones_texto_certificado'] !== $esperado) {
    throw new RuntimeException('Otras determinaciones INASE incorrecto: ' . $formInase['otras_determinaciones_texto_certificado']);
}
if ($formInase['pg_plantulas_normales_certificado'] !== '90.00' || $formInase['pg_numero_dias_certificado'] !== '10') {
    throw new RuntimeException('La germinación INASE no se precargó en los campos de PG.');
}
if (($formInase['tetrazolio_no_viables_certificado'] ?? '') !== '') {
    throw new RuntimeException('El tetrazolio INASE no debe ocupar los campos del certificado de tetrazolio.');
}

// General: cold test llena los campos de vigor y envejecimiento va a Otras determinaciones.
[$formGeneral] = libro_ingreso_precargar_certificado([], $ingreso, 'Certificadogeneral', [
    'cold_test' => [
        'fecha_origen' => '2026-10-22',
        'fecha_siembra' => '2026-10-15',
        'fecha_segundo_conteo' => '2026-10-22',
        'resultado_plantulas_normales' => '85.00',
    ],
    'envejecimiento' => [
        'fecha_origen' => '2026-10-25',
        'resultado_plantulas_normales' => '80.00',
    ],
]);

if ($formGeneral['vigor_plantulas_normales_certificado'] !== '85.00' || $formGeneral['vigor_nro_dias_certificado'] !== '7') {
    throw new RuntimeException('El cold test no se precargó en los campos de vigor.');
}
if ($formGeneral['otras_determinaciones_texto_certificado'] !== 'Vigor por envejecimiento acelerado: 80.00 %') {
    throw new RuntimeException('El envejecimiento acelerado no se volcó en Otras determinaciones.');
}
if ($formGeneral['fecha_finalizacion_ensayos_certificado'] !== '2026-10-25') {
    throw new RuntimeException('La fecha de finalización no tomó la mayor fecha de los boletines.');
}

// El certificado de tetrazolio conserva su comportamiento actual.
[$formTetrazolio] = libro_ingreso_precargar_certificado([], $ingreso, 'CertificadoTetrazolio', [
    'tetrazolio' => [
        'fecha_origen' => '2026-10-18',
        'resultado_porcentaje' => '95.00',
        'resultado_no_viables_porcentaje' => '5.00',
    ],
]);
if ($formTetrazolio['tetrazolio_no_viables_certificado'] !== '5.00') {
    throw new RuntimeException('El certificado de tetrazolio debe seguir precargando los no viables.');
}
if (($formTetrazolio['otras_determinaciones_texto_certificado'] ?? '') !== '') {
    throw new RuntimeException('El certificado de tetrazolio no debe volcar texto en Otras determinaciones.');
}

echo "OK: la precarga de certificados desde el libro de ingreso respeta los ensayos de cada tipo.\n";
