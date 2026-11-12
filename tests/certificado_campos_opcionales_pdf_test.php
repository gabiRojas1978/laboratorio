<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/format_helpers.php';
require_once __DIR__ . '/../includes/certificado_pdf.php';

$emptyFields = [
    'hipoclorito_max_10_certificado' => '',
    'peroxidasa_positivas_certificado' => null,
    'peroxidasa_negativas_certificado' => '',
    'contam_hilo_cantidad_semillas_certificado' => null,
    'contam_hilo_color_contaminante_certificado' => '',
    'hipocotilo_variedad_certificado' => '',
    'hipocotilo_color_contaminante_certificado' => null,
];

if (certificate_optional_results_html($emptyFields) !== '') {
    throw new RuntimeException('Los resultados opcionales vacíos no deben figurar en el certificado.');
}

$filledFields = [
    'hipoclorito_max_10_certificado' => '',
    'peroxidasa_positivas_certificado' => '0',
    'peroxidasa_negativas_certificado' => '',
    'contam_hilo_cantidad_semillas_certificado' => '12.5',
    'contam_hilo_color_contaminante_certificado' => '<rojo>',
    'hipocotilo_variedad_certificado' => '',
    'hipocotilo_color_contaminante_certificado' => null,
];
$html = certificate_optional_results_html($filledFields);

foreach ([
    'Peroxidasa - Positivas (%): <span class="line-value">0%</span>',
    'Contam. x hilo (250gr) - Cantidad de semillas (%): <span class="line-value">12.5%</span>',
    'Contam. x hilo (250gr) - Color contaminante: <span class="line-value">&lt;rojo&gt;</span>',
] as $expected) {
    if (!str_contains($html, $expected)) {
        throw new RuntimeException('Falta el resultado opcional esperado: ' . $expected);
    }
}

if (str_contains($html, 'Negativas') || str_contains($html, 'Hipocotilo') || str_contains($html, '>N<')) {
    throw new RuntimeException('Los resultados opcionales sin completar no deben imprimirse como N.');
}

$completeFields = [
    'hipoclorito_max_10_certificado' => 'Cumple',
    'peroxidasa_positivas_certificado' => '90',
    'peroxidasa_negativas_certificado' => '10',
    'contam_hilo_cantidad_semillas_certificado' => '2',
    'contam_hilo_color_contaminante_certificado' => 'amarillo',
    'hipocotilo_variedad_certificado' => 'Soja',
    'hipocotilo_color_contaminante_certificado' => 'verde',
];
$completeHtml = certificate_optional_results_html($completeFields);

foreach ([
    'Hipoclorito max 10%: <span class="line-value">Cumple</span>',
    'Peroxidasa - Positivas (%): <span class="line-value">90%</span>',
    'Peroxidasa - Negativas (%): <span class="line-value">10%</span>',
    'Contam. x hilo (250gr) - Cantidad de semillas (%): <span class="line-value">2%</span>',
    'Contam. x hilo (250gr) - Color contaminante: <span class="line-value">amarillo</span>',
    'Hipocotilo - Variedad: <span class="line-value">Soja</span>',
    'Hipocotilo - Color contaminante: <span class="line-value">verde</span>',
] as $expected) {
    if (!str_contains($completeHtml, $expected)) {
        throw new RuntimeException('Falta el resultado opcional esperado: ' . $expected);
    }
}

echo "OK: solo se imprimen los resultados opcionales completados.\n";
