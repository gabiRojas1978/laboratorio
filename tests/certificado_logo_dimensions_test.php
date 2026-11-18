<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/certificado_pdf.php';

$cases = [
    ['dimensions' => [800, 800], 'expected' => ['width' => 30.0, 'height' => 30.0]],
    ['dimensions' => [1600, 400], 'expected' => ['width' => 112.0, 'height' => 28.0]],
    ['dimensions' => [400, 1600], 'expected' => ['width' => 7.5, 'height' => 30.0]],
];

foreach ($cases as $case) {
    $dimensions = certificate_logo_dimensions_mm(...$case['dimensions']);
    if ($dimensions !== $case['expected']) {
        throw new RuntimeException('El logo debe conservar su proporción dentro del espacio disponible.');
    }
}

if (certificate_logo_dimensions_mm(0, 100) !== null) {
    throw new RuntimeException('Las dimensiones de imagen no válidas deben rechazarse.');
}

echo "OK: las dimensiones de los logos conservan su proporción.\n";
