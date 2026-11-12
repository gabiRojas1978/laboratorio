<?php

declare(strict_types=1);

require __DIR__ . '/../includes/libro_ingreso.php';
require __DIR__ . '/../includes/libro_ingreso_certificado.php';

function verificar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

function rechazar(callable $accion, string $mensaje): void
{
    try {
        $accion();
    } catch (InvalidArgumentException $exception) {
        return;
    }
    throw new RuntimeException($mensaje);
}

function is_logged_in(): bool
{
    return $GLOBALS['logueado'];
}

function current_empresa_id(): int
{
    return 4;
}

final class CertificadoIngresoTestDb
{
    public array $consultas = [];
    public array $params = [];
    public string $sql = '';
    public array $filas = [];
    public bool $fallar = false;

    public function prepare(string $sql): self
    {
        if ($this->fallar) {
            throw new PDOException('Error simulado');
        }
        $this->sql = $sql;
        $this->consultas[] = $sql;
        return $this;
    }

    public function execute(array $params): void
    {
        $this->params = $params;
    }

    public function fetch(): array|false
    {
        return $this->params === [':id' => 7, ':id_empresa' => 4] ? $GLOBALS['ingreso'] : false;
    }

    public function fetchAll(): array
    {
        if (str_contains($this->sql, 'FROM boletin_pureza_componentes')) {
            return [
                ['tipo_componente' => 'materia_inerte', 'nombre_componente' => 'Tierra'],
                ['tipo_componente' => 'otras_semillas', 'nombre_componente' => 'Avena'],
            ];
        }
        if (str_contains($this->sql, 'FROM boletin_malezas_especies')) {
            return [['especie_latin' => 'Lolium', 'numero_semillas' => 0]];
        }
        verificar(str_contains($this->sql, 'id_empresa = :id_empresa AND id_cliente = :id_cliente AND id_especie <=> :id_especie'), 'Debe aislar empresa, cliente y especie.');
        verificar($this->params[':id_empresa'] === 4 && $this->params[':id_cliente'] === 12 && $this->params[':id_especie'] === 8, 'Parametros de aislamiento incorrectos.');
        if (!str_contains($this->sql, 'FROM boletines_pureza')) {
            verificar(str_contains($this->sql, 'muestra_nro = :muestra') && $this->params[':muestra'] === '20260003', 'Debe filtrar por muestra.');
        }
        if (str_contains($this->sql, 'FROM boletines_germinacion')) {
            verificar(
                str_contains($this->sql, 'tipo_boletin = :tipo_boletin')
                    && in_array($this->params[':tipo_boletin'] ?? null, ['germinacion', 'cold_test', 'envejecimiento_acelerado'], true),
                'No debe importar Cold Test o Envejecimiento acelerado como Germinación.'
            );
            verificar(str_contains($this->sql, "estado = 'finalizado'"), 'Solo debe ofrecer germinacion finalizada.');
        }
        foreach ($this->filas as $tabla => $filas) {
            if (str_contains($this->sql, 'FROM ' . $tabla)) {
                return $filas;
            }
        }
        return [];
    }
}

function db(): CertificadoIngresoTestDb
{
    return $GLOBALS['testDb'];
}

$ingreso = [
    'id_libro_ingreso' => 7, 'id_cliente' => 12, 'id_especie' => 8, 'id_cultivar' => 9,
    'nro_inase' => 3, 'nro_muestra' => '20260003',
    'fecha_entrada' => '2026-10-01', 'lote_observaciones' => 'Lote <A> & "B"',
];
$inicial = [
    'ampara_totalidad_lote_certificado' => 'N', 'peso_certificado' => '',
    'pg_contenido_humedad_certificado' => '', 'tetrazolio_viable_sin_defectos_certificado' => '',
    'tetrazolio_viable_defectos_leves_certificado' => '',
    'tetrazolio_viable_defectos_moderados_certificado' => '',
    'tetrazolio_viable_defectos_severos_certificado' => '',
    'tetrazolio_vigor_alto_certificado' => '', 'tetrazolio_danos_mecanicos_certificado' => '',
];
$testDb = new CertificadoIngresoTestDb();
$testDb->filas = [
    'boletines_germinacion' => [
        [
            'id_origen' => 11, 'fecha_origen' => '2026-10-10',
            'fecha_siembra' => '2026-10-02', 'fecha_primer_conteo' => '2026-10-05', 'fecha_segundo_conteo' => '2026-10-10',
            'primer_promedio' => '88.50', 'resultado_plantulas_normales' => '95.25',
            'resultado_plantulas_anormales' => '2.00', 'resultado_semillas_duras' => '0.00',
            'resultado_semillas_frescas' => '1.00', 'resultado_semillas_muertas' => '1.75',
        ],
        [
            'id_origen' => 12, 'fecha_origen' => '2026-10-12',
            'fecha_siembra' => null, 'fecha_primer_conteo' => null, 'fecha_segundo_conteo' => '2026-10-12',
            'primer_promedio' => '92.00', 'resultado_plantulas_normales' => '97.00',
        ],
    ],
    'boletines_pureza' => [[
        'id_origen' => 21, 'fecha_origen' => '2026-10-06',
        'porcentaje_semilla_pura' => '99.1234', 'porcentaje_materia_inerte' => '0.8766', 'porcentaje_otras_semillas' => '0.0000',
    ]],
    'boletines_peso_1000_semillas' => [['id_origen' => 31, 'fecha_origen' => '2026-10-08', 'resultado_peso_mil' => '156.1234']],
    'boletines_malezas' => [['id_origen' => 41, 'fecha_origen' => '2026-10-07']],
    'boletines_tetrazolio' => [[
        'id_origen' => 51, 'fecha_origen' => '2026-10-09',
        'resultado_porcentaje' => '96.00', 'resultado_no_viables_porcentaje' => '4.00',
    ]],
];

verificar(libro_ingreso_para_certificado('7', 4) === $ingreso, 'Debe recuperar el ingreso propio.');
foreach (['abc', '0', '-1', ['7'], '8'] as $id) {
    rechazar(fn() => libro_ingreso_para_certificado($id, 4), 'Debe rechazar ingresos invalidos o inexistentes.');
}
rechazar(fn() => libro_ingreso_para_certificado('7', 99), 'Debe rechazar ingresos de otra empresa.');
rechazar(fn() => libro_ingreso_fuentes_certificado('clientes'), 'Debe rechazar tipos desconocidos.');
rechazar(fn() => libro_ingreso_candidatos_certificado($ingreso, 4, 'invalido'), 'Debe rechazar fuentes desconocidas.');
verificar(
    libro_ingreso_certificado_directo_destino(['Test de Lavandina- Evaluacion de daño Mecanico']) === 'Certificadogeneral',
    'Lavandina debe abrir directamente el certificado general no INASE.'
);
verificar(
    libro_ingreso_certificado_directo_destino(['Peroxidasa en semilla de soja', 'Pureza Genetica (hilo-peroxidasa-hipocotilo)', 'Cold Test-Vigor']) === 'Certificadogeneral',
    'Las determinaciones de pureza varietal y vigor deben abrir el certificado general.'
);
verificar(
    libro_ingreso_certificado_directo_destino(['Pureza (otras semillas y forrajeras)', 'Peso de 1000 semillas', 'Poder Germinativo', 'Vigor por tetrazolio', 'Otras semillas en numero']) === 'CertificadoINASE',
    'Pureza, peso, PG sin curar, tetrazolio no soja y otras especies deben abrir el certificado INASE.'
);
verificar(
    libro_ingreso_certificado_directo_destino(['Viabilidad por tetrazolio-Soja']) === 'CertificadoTetrazolio',
    'Tetrazolio de soja debe abrir el certificado de tetrazolio.'
);
verificar(
    libro_ingreso_certificado_directo_destino(['Poder Germinativo', 'Cold Test-Vigor']) === null,
    'Las determinaciones de certificados distintos deben conservar la seleccion habitual.'
);
verificar(
    libro_ingreso_certificado_directo_destino(['Determinacion desconocida']) === null
        && libro_ingreso_certificado_directo_destino([]) === null,
    'Determinaciones desconocidas o ausentes deben conservar la seleccion habitual.'
);

[$form] = libro_ingreso_importar_certificado($inicial, $ingreso, 4, 'Certificadogeneral', []);
verificar($form['id_cliente_certificado'] === '12' && $form['id_especie_certificado'] === '8' && $form['id_cultivar_certificado'] === '9', 'Debe precargar cliente, especie y cultivar.');
verificar($form['nro_analisis_certificado'] === '03' && $form['fecha_muestras_certificado'] === '2026-10-01', 'Debe precargar analisis y recepcion.');
verificar($form['marca_lote_certificado'] === $ingreso['lote_observaciones'] && $form['otras_referencias_certificado'] === 'Muestra N° 20260003', 'Debe conservar lote y muestra.');
verificar(!isset($form['fecha_finalizacion_ensayos_certificado'], $form['pg_plantulas_normales_certificado']), 'Sin seleccion no debe inventar resultados ni fechas.');
[$nulos] = libro_ingreso_importar_certificado($inicial, array_replace($ingreso, ['id_especie' => null, 'id_cultivar' => null, 'lote_observaciones' => null]), 4, 'Certificadogeneral', []);
verificar($nulos['id_especie_certificado'] === '' && $nulos['id_cultivar_certificado'] === '' && $nulos['marca_lote_certificado'] === '', 'Debe conservar vacios los campos nulos.');

[$form, $avisos] = libro_ingreso_importar_certificado($inicial, $ingreso, 4, 'Certificadogeneral', [
    'malezas' => '41', 'peso' => '31', 'pureza' => '21', 'germinacion_curado' => '12', 'germinacion' => '11',
]);
verificar($form['pg_plantulas_normales_certificado'] === '95.25' && $form['pg_semillas_duras_certificado'] === '0.00', 'Debe importar porcentajes conservando ceros.');
verificar($form['pg_numero_dias_certificado'] === '8' && !isset($form['vigor_nro_dias_certificado'], $form['vigor_plantulas_normales_certificado']), 'Debe importar dias sin confundir energia germinativa con un ensayo de vigor.');
verificar($form['pgc_plantulas_normales_certificado'] === '97.00' && !isset($form['pgc_numero_dias_certificado']), 'Curado debe venir del ensayo elegido sin inventar dias.');
verificar($form['pureza_semilla_pura_certificado'] === '99.12' && $form['materia_inerte_certificado'] === '0.88' && $form['pureza_otras_semillas_certificado'] === '0.00', 'Pureza debe respetar la precision del formulario.');
verificar($form['clase_materia_inerte_certificado'] === 'Tierra' && $form['otras_semillas_certificado'] === 'Avena; Lolium: 0 semillas', 'Debe combinar los detalles sin perder ceros ni componentes.');
verificar($form['otras_determinaciones_texto_certificado'] === 'Peso de 1000 semillas: 156.1234 g' && $form['peso_certificado'] === '', 'No debe confundir peso de mil semillas con peso del lote.');
verificar($form['fecha_finalizacion_ensayos_certificado'] === '2026-10-12' && count($avisos) === 2, 'Debe usar ultima fecha e informar asociacion manual de Pureza.');

[$inase] = libro_ingreso_importar_certificado($inicial, $ingreso, 4, 'CertificadoINASE', ['germinacion' => '11']);
verificar($inase['tipo_certificado'] === 'certificadoinase' && !isset($inase['pgc_plantulas_normales_certificado']), 'INASE no debe importar curado.');
[$tetra, $avisos] = libro_ingreso_importar_certificado($inicial, $ingreso, 4, 'CertificadoTetrazolio', ['tetrazolio' => '51']);
verificar($tetra['tetrazolio_no_viables_certificado'] === '4.00' && $tetra['tetrazolio_viable_sin_defectos_certificado'] === '' && $tetra['tetrazolio_vigor_alto_certificado'] === '', 'Tetrazolio no debe inventar categorias ni vigor.');
verificar($tetra['id_cultivar_certificado'] === '' && $tetra['tetrazolio_detalle_certificado'] === $ingreso['lote_observaciones'] && str_contains($avisos[0], '96.00'), 'Tetrazolio debe mostrar detalle y total viable como referencia.');

[$largo, $avisos] = libro_ingreso_precargar_certificado($inicial, $ingreso, 'Certificadogeneral', [
    'malezas' => ['especies_halladas' => [['especie_latin' => str_repeat('x', 101), 'numero_semillas' => 1]]],
]);
verificar($largo['otras_semillas_certificado'] === '' && str_contains($avisos[0], str_repeat('x', 101)), 'Debe avisar y conservar en el aviso el detalle que no cabe en el certificado.');

foreach ([
    ['Certificadogeneral', ['germinacion' => '999']],
    ['Certificadogeneral', ['germinacion' => ['11']]],
    ['Certificadogeneral', ['germinacion' => '-1']],
    ['Certificadogeneral', ['germinacion' => '11', 'germinacion_curado' => '11']],
    ['CertificadoINASE', ['germinacion_curado' => '12']],
    ['CertificadoTetrazolio', ['pureza' => '21']],
] as [$pagina, $seleccion]) {
    rechazar(fn() => libro_ingreso_importar_certificado($inicial, $ingreso, 4, $pagina, $seleccion), 'Debe rechazar selecciones incompatibles o boletines ajenos.');
}

$navigation = ['certificados' => ['items' => ['Certificadogeneral' => 'General', 'CertificadoINASE' => 'INASE', 'CertificadoTetrazolio' => 'Tetrazolio']]];
foreach ([
    ['POST', true, ['page' => 'Certificadogeneral', 'ingreso_id' => '7']],
    ['GET', false, ['page' => 'Certificadogeneral', 'ingreso_id' => '7']],
    ['GET', true, ['page' => 'libro_ingreso', 'ingreso_id' => '7']],
    ['GET', true, ['page' => ['Certificadogeneral'], 'ingreso_id' => '7']],
    ['GET', true, ['page' => 'Certificadogeneral']],
] as [$metodo, $logueado, $query]) {
    $_SERVER['REQUEST_METHOD'] = $metodo;
    $_GET = $query;
    $certForm = $inicial;
    $errors = [];
    require __DIR__ . '/../handlers/libro_ingreso_certificado.php';
    verificar($certForm === $inicial && $errors === [], 'No debe sobrescribir POST ni precargar fuera de contexto.');
}
$logueado = true;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['page' => 'Certificadogeneral', 'ingreso_id' => '7', 'boletin_germinacion' => '11'];
$errors = [];
require __DIR__ . '/../handlers/libro_ingreso_certificado.php';
verificar($errors === [] && $certForm['pg_plantulas_normales_certificado'] === '95.25', 'El handler debe conectar la seleccion con la precarga.');
$_GET = ['page' => 'CertificadoINASE', 'ingreso_id' => '7', 'auto' => '1'];
$certForm = $inicial;
$errors = [];
require __DIR__ . '/../handlers/libro_ingreso_certificado.php';
verificar($errors === [] && !isset($certForm['pg_plantulas_normales_certificado']), 'Con varios ensayos de germinacion la precarga automatica no debe elegir uno.');
verificar(
    $certForm['pureza_semilla_pura_certificado'] === '99.12'
        && $certForm['otras_determinaciones_texto_certificado'] === 'Peso de 1000 semillas: 156.1234 g; Viabilidad por tetrazolio: 96.00 %; No viables por tetrazolio: 4.00 %',
    'La precarga automatica debe importar los boletines con coincidencia unica.'
);
verificar(
    array_filter($certPrecargaAvisos, static fn(string $aviso): bool => str_contains($aviso, 'varios boletines')) !== [],
    'Debe avisar cuando una fuente tiene varios boletines y queda manual.'
);
$_GET = ['page' => 'Certificadogeneral', 'ingreso_id' => '7', 'boletin_germinacion' => '999'];
$_GET['boletin_germinacion'] = '999';
$certForm = $inicial;
$errors = [];
require __DIR__ . '/../handlers/libro_ingreso_certificado.php';
verificar(count($errors) === 1 && $certForm === $inicial, 'Debe mostrar error sin precarga parcial.');
$testDb->fallar = true;
$errors = [];
require __DIR__ . '/../handlers/libro_ingreso_certificado.php';
verificar(count($errors) === 1 && $certForm === $inicial, 'Debe notificar errores de base de datos.');
foreach ($testDb->consultas as $sql) {
    verificar(str_starts_with($sql, 'SELECT '), 'La precarga no debe modificar datos.');
}

echo 'OK: certificados general, INASE y tetrazolio, seleccion explicita, aislamiento, resultados, fechas, errores y POST.' . PHP_EOL;
