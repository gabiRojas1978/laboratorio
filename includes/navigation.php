<?php

declare(strict_types=1);

$navigation = [
    'administracion' => [
        'label' => 'Administración',
        'items' => [
            'clientes' => 'Clientes',
            'especies' => 'Especies',
            'cultivares' => 'Cultivares',
            'otras_determinaciones' => 'Otras Determinaciones',
            'usuarios' => 'Usuarios',
            'configuracion' => 'Configuración',
        ],
    ],
    'boletines' => [
        'label' => 'Boletines',
        'items' => [
            'boletin_peso_1000_semillas' => 'Peso de 1000 semillas',
            'boletin_tetrazolio' => 'Tetrazolio',
            'boletin_pureza' => 'Pureza física-botánica',
            'boletin_malezas' => 'Otras especies en número',
            'boletin_germinacion' => 'Germinación',
        ],
    ],
    'certificados' => [
        'label' => 'Certificados',
        'items' => [
            'Certificadogeneral' => 'Certificado general',
            'CertificadoINASE' => 'Certificado INASE',
            'CertificadoTetrazolio' => 'Certificado de tetrazolio',
        ],
    ],
];

if (is_superadmin()) {
    $navigation['superadmin'] = [
        'label' => 'Superadmin',
        'items' => [
            'empresas' => 'Empresas',
        ],
    ];
}

$pageMap = [
    'dashboard' => ['title' => 'Inicio', 'subtitle' => ''],
    'clientes' => ['title' => 'Clientes', 'subtitle' => 'Gestión de clientes'],
    'especies' => ['title' => 'Especies', 'subtitle' => 'Catálogo de especies'],
    'cultivares' => ['title' => 'Cultivares', 'subtitle' => 'Registro de cultivares'],
    'otras_determinaciones' => ['title' => 'Otras Determinaciones', 'subtitle' => 'Parámetros auxiliares'],
    'usuarios' => ['title' => 'Usuarios', 'subtitle' => 'Gestión de usuarios del sistema'],
    'configuracion' => ['title' => 'Configuración', 'subtitle' => 'Preferencias del sistema'],
    'boletin_peso_1000_semillas' => ['title' => 'Boletín: Peso de 1000 semillas', 'subtitle' => 'Cálculo de varianza, desvío y coeficiente de variación'],
    'boletin_tetrazolio' => ['title' => 'Boletín de Tetrazolio', 'subtitle' => 'Viabilidad de semillas'],
    'boletin_pureza' => ['title' => 'Boletín de Pureza física-botánica', 'subtitle' => 'Semilla pura, materia inerte y otras semillas'],
    'boletin_malezas' => ['title' => 'Otras especies en número', 'subtitle' => 'Registro y número de semillas'],
    'boletin_germinacion' => ['title' => 'Boletín de Germinación', 'subtitle' => 'Conteos, tolerancia y resultado final'],
    'Certificadogeneral' => ['title' => 'Certificado general', 'subtitle' => 'Emisión de certificados'],
    'CertificadoINASE' => ['title' => 'Certificado INASE', 'subtitle' => 'Emisión de certificados INASE'],
    'CertificadoTetrazolio' => ['title' => 'Certificado de tetrazolio', 'subtitle' => 'Prueba topográfica por tetrazolio'],
    'empresas' => ['title' => 'Empresas', 'subtitle' => 'Alta y administración de laboratorios (tenants)'],
];

// Cada clave de página apunta al archivo de vista que la renderiza dentro de pages/
$pageTemplates = [
    'dashboard' => 'dashboard.php',
    'clientes' => 'clientes.php',
    'especies' => 'especies.php',
    'cultivares' => 'cultivares.php',
    'otras_determinaciones' => 'otras_determinaciones.php',
    'usuarios' => 'usuarios.php',
    'configuracion' => 'configuracion.php',
    'empresas' => 'empresas.php',
    'boletin_peso_1000_semillas' => 'boletin_peso_1000_semillas.php',
    'boletin_tetrazolio' => 'boletin_tetrazolio.php',
    'boletin_pureza' => 'boletin_pureza.php',
    'boletin_malezas' => 'boletin_malezas.php',
    'boletin_germinacion' => 'boletin_germinacion.php',
    'Certificadogeneral' => 'certificado_form.php',
    'CertificadoINASE' => 'certificado_form.php',
    'CertificadoTetrazolio' => 'certificado_form.php',
];
