<?php

declare(strict_types=1);

require __DIR__ . '/../includes/libro_ingreso.php';
require_once __DIR__ . '/../includes/boletin_tetrazolio_soja.php';

function verificar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

function is_logged_in(): bool
{
    return $GLOBALS['logueado'];
}

function is_superadmin(): bool
{
    return false;
}

function current_empresa_id(): int
{
    return 4;
}

final class LibroBoletinTestDb
{
    public array $consultas = [];
    public array $parametros = [];
    public string $ultimaConsulta = '';
    public array $boletinesLegacy = [];
    public array $ingreso = [
        'id_cliente' => 12,
        'id_especie' => 8,
        'nombre_especie' => 'Maíz',
        'cientifico_especie' => 'Zea mays',
        'nro_inase' => 3,
        'nro_muestra' => '20260003',
        'lote_observaciones' => 'Lote <A> & "B"',
    ];
    public bool $fallar = false;
    public array $determinaciones = [
        'Poder Germinativo', 'Peso de 1000 semillas',
        'Vigor por tetrazolio (otras semillas y forrajeras)',
        'Pureza (otras semillas y forrajeras)', 'Otras semillas en numero', 'Cold Test-Vigor',
        'Vigor por Envejecimiento Acelerado',
    ];

    public function prepare(string $sql): self
    {
        $this->consultas[] = $sql;
        $this->ultimaConsulta = $sql;
        if ($this->fallar) {
            throw new PDOException('Error de prueba');
        }
        return $this;
    }

    public function execute(array $parametros): void
    {
        $this->parametros = $parametros;
    }

    public function fetch(): array|false
    {
        return $this->parametros === [':id' => 7, ':id_empresa' => 4] ? $this->ingreso : false;
    }

    public function fetchColumn(): int|false
    {
        return false;
    }

    public function fetchAll(int $modo): array
    {
        verificar($modo === PDO::FETCH_COLUMN, 'Debe consultar los nombres de las determinaciones.');
        if (strpos($this->ultimaConsulta, 'SELECT d.nombre_determinacion_libro') !== false) {
            return $this->determinaciones;
        }

        return $this->boletinesLegacy;
    }
}

function db(): LibroBoletinTestDb
{
    return $GLOBALS['testDb'];
}

require __DIR__ . '/../includes/navigation.php';
foreach ([
    'boletin_cold_test' => 'Cold Test',
    'boletin_envejecimiento_acelerado' => 'Envejecimiento acelerado',
] as $pagina => $titulo) {
    verificar(($navigation['boletines']['items'][$pagina] ?? null) === $titulo, 'El nuevo boletín debe aparecer en navegación: ' . $pagina);
    verificar(($pageTemplates[$pagina] ?? null) === 'boletin_germinacion.php', 'El nuevo boletín debe reutilizar el formulario de Germinación: ' . $pagina);
}
$logueado = true;
$testDb = new LibroBoletinTestDb();

$formularios = [
    'boletin_peso_1000_semillas' => ['boletinPesoForm', ['muestra_nro' => '', 'pesos' => array_fill(0, 8, '')]],
    'boletin_tetrazolio' => ['boletinTetrazolioForm', ['muestra_nro' => '', 'repeticiones' => [1 => ['viables' => '']]]],
    'boletin_pureza' => ['boletinPurezaForm', ['analisis_nro' => '', 'componentes_materia_inerte' => [['nombre' => '', 'peso' => '']]]],
    'boletin_malezas' => ['boletinMalezasForm', ['analisis_nro' => '', 'muestra_nro' => '', 'lote' => '', 'tipo_analisis' => 'completo']],
    'boletin_germinacion' => ['boletinGerminacionForm', ['analisis_nro' => '', 'muestra_nro' => '', 'repeticiones' => [1 => ['semillas_sembradas' => '']]]],
    'boletin_cold_test' => ['boletinGerminacionForm', ['analisis_nro' => '', 'muestra_nro' => '', 'repeticiones' => [1 => ['semillas_sembradas' => '']]]],
    'boletin_envejecimiento_acelerado' => ['boletinGerminacionForm', ['analisis_nro' => '', 'muestra_nro' => '', 'repeticiones' => [1 => ['semillas_sembradas' => '']]]],
];

foreach ($formularios as $pagina => [$variable, $especificos]) {
    $inicial = array_merge([
        'id_cliente' => '',
        'id_especie' => '',
        'observaciones' => '',
        'analista' => '',
        'fecha_analisis' => '',
    ], $especificos);
    $$variable = $inicial;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = ['page' => $pagina, 'ingreso_id' => '7'];
    $errors = [];
    require __DIR__ . '/../handlers/libro_ingreso_boletin.php';

    $esperado = array_replace($inicial, [
        'id_cliente' => '12',
        'id_especie' => '8',
        'observaciones' => $testDb->ingreso['lote_observaciones'],
    ]);
    if (array_key_exists('muestra_nro', $inicial)) {
        $esperado['muestra_nro'] = '20260003';
    }
    if (array_key_exists('analisis_nro', $inicial)) {
        $esperado['analisis_nro'] = '03';
    }
    verificar($errors === [] && $$variable === $esperado, 'Precarga incorrecta: ' . $pagina);
    verificar(
        count(array_filter($testDb->consultas, static fn(string $sql): bool => strpos($sql, 'id_empresa = :id_empresa') !== false)) > 0,
        'Las consultas deben aislar las empresas.'
    );
}

verificar(libro_ingreso_boletines_disponibles([
    'Energia Germinativa - 1° Conteo', 'Poder Germinativo',
    'Energia Germinativa - 1° Conteo Con tratamiento', 'Poder germinativo Con tratamiento',
]) === ['boletin_germinacion'], 'Todas las determinaciones germinativas deben habilitar un unico boletin.');
verificar(libro_ingreso_boletines_disponibles([' Peso de 1000 SEMILLAS ']) === ['boletin_peso_1000_semillas'], 'Debe tolerar mayusculas y espacios.');
verificar(libro_ingreso_boletines_disponibles(['Energía Germinativa - 1° Conteo']) === ['boletin_germinacion'], 'Debe tolerar acentos.');
verificar(libro_ingreso_boletines_disponibles(['Cold Test-Vigor', 'Vigor por Envejecimiento Acelerado']) === [
    'boletin_cold_test', 'boletin_envejecimiento_acelerado',
], 'Cold Test y Envejecimiento acelerado deben habilitar sus boletines.');
foreach (['Cold Test', 'Envejecimiento acelerado', 'Tetrazolio', 'Otras especies en numero', 'Pureza física-botánica'] as $sinCorrespondencia) {
    verificar(libro_ingreso_boletines_disponibles([$sinCorrespondencia]) === [], 'No debe habilitar boletines para una determinación sin correspondencia: ' . $sinCorrespondencia);
}
verificar(libro_ingreso_boletines_disponibles([]) === [], 'Sin determinaciones no hay boletines disponibles.');
foreach ([
    'Energia Germinativa - 1° Conteo Con tratamiento' => 'boletin_germinacion',
    'Poder germinativo Con tratamiento' => 'boletin_germinacion',
    'Pureza (soja-trigo-maiz-sorgo-girasol)' => 'boletin_pureza',
    'Pureza (otras semillas y forrajeras)' => 'boletin_pureza',
    'Otras semillas en numero' => 'boletin_malezas',
    'Viabilidad por tetrazolio-Soja' => 'boletin_tetrazolio_soja',
    'Vigor por tetrazolio Soja' => 'boletin_tetrazolio_soja',
    'Vigor por tetrazolio (otras semillas y forrajeras)' => 'boletin_tetrazolio',
    'Cold Test-Vigor' => 'boletin_cold_test',
    'Vigor por Envejecimiento Acelerado' => 'boletin_envejecimiento_acelerado',
] as $determinacion => $boletin) {
    $especie = match ($boletin) {
        'boletin_tetrazolio_soja' => ['Soja', 'Glycine max'],
        'boletin_tetrazolio' => ['Maíz', 'Zea mays'],
        default => [null, null],
    };
    verificar(
        libro_ingreso_boletines_disponibles([$determinacion], $especie[0], $especie[1]) === [$boletin],
        'El catálogo nuevo debe habilitar su boletín: ' . $determinacion
    );
}
verificar(
    libro_ingreso_boletines_disponibles(['Viabilidad por tetrazolio-Soja'], 'Maíz', 'Zea mays') === [],
    'Tetrazolio (Soja) no debe habilitarse para otras especies.'
);
verificar(
    libro_ingreso_boletines_disponibles(['Vigor por tetrazolio (otras semillas y forrajeras)'], 'Soja', 'Glycine max') === [],
    'El Tetrazolio general no debe habilitarse para soja.'
);
verificar(libro_ingreso_boletines_disponibles(['No compatible']) === [], 'No debe habilitar boletines para determinaciones desconocidas.');
verificar(libro_ingreso_boletines_disponibles(['Poder Germinativo', 'Peso de 1000 semillas']) === [
    'boletin_germinacion', 'boletin_peso_1000_semillas',
], 'Solo debe habilitar los dos boletines correspondientes.');
$testDb->boletinesLegacy = [42];
verificar(
    libro_ingreso_buscar_boletin_legacy('boletin_germinacion', $testDb->ingreso) === 42,
    'Debe detectar un boletín histórico único por muestra, cliente y especie.'
);
$testDb->boletinesLegacy = [42, 43];
verificar(
    libro_ingreso_buscar_boletin_legacy('boletin_germinacion', $testDb->ingreso) === false,
    'No debe asociar automáticamente si hay varios boletines históricos que coinciden.'
);
$testDb->boletinesLegacy = [];

foreach ([[], ['Peso de 1000 semillas'], ['No compatible']] as $determinaciones) {
    $testDb->determinaciones = $determinaciones;
    $_GET = ['page' => 'boletin_germinacion', 'ingreso_id' => '7'];
    $errors = [];
    $anterior = $boletinGerminacionForm;
    require __DIR__ . '/../handlers/libro_ingreso_boletin.php';
    verificar(count($errors) === 1 && $boletinGerminacionForm === $anterior, 'Debe rechazar una URL con un boletin no habilitado por las determinaciones.');
}
$testDb->determinaciones = ['Poder Germinativo'];

$sinOpcionales = libro_ingreso_precargar_boletin(['muestra_nro' => ''], array_replace($testDb->ingreso, [
    'id_especie' => null,
    'lote_observaciones' => null,
]));
verificar($sinOpcionales['id_especie'] === '' && $sinOpcionales['observaciones'] === '', 'Los valores nulos deben quedar vacios.');
$textoLargo = str_repeat('x', 255);
$conTextoLargo = libro_ingreso_precargar_boletin([], array_replace($testDb->ingreso, ['lote_observaciones' => $textoLargo]));
verificar($conTextoLargo['observaciones'] === $textoLargo, 'No debe truncar lote/observaciones.');

foreach (['abc', '0', '-1', ['7'], '8'] as $idInvalido) {
    $_GET = ['page' => 'boletin_germinacion', 'ingreso_id' => $idInvalido];
    $errors = [];
    $anterior = $boletinGerminacionForm;
    require __DIR__ . '/../handlers/libro_ingreso_boletin.php';
    verificar(count($errors) === 1 && $boletinGerminacionForm === $anterior, 'Un ingreso invalido o ajeno debe informar error sin precargar.');
}

$_GET = ['page' => 'boletin_germinacion', 'ingreso_id' => '7', 'editar' => '2'];
$errors = [];
$consultasAntes = count($testDb->consultas);
require __DIR__ . '/../handlers/libro_ingreso_boletin.php';
verificar(
    count($errors) === 1
        && count($testDb->consultas) === $consultasAntes + 1
        && str_contains($errors[0], 'no está vinculado'),
    'La edición desde un ingreso debe requerir una relación existente.'
);

foreach ([
    ['POST', true, ['page' => 'boletin_germinacion', 'ingreso_id' => '7']],
    ['GET', false, ['page' => 'boletin_germinacion', 'ingreso_id' => '7']],
    ['GET', true, ['page' => 'clientes', 'ingreso_id' => '7']],
    ['GET', true, ['page' => ['boletin_germinacion'], 'ingreso_id' => '7']],
    ['GET', true, ['page' => 'boletin_germinacion']],
] as [$metodo, $logueado, $parametros]) {
    $_SERVER['REQUEST_METHOD'] = $metodo;
    $_GET = $parametros;
    $errors = [];
    $anterior = $boletinGerminacionForm;
    $consultasAntes = count($testDb->consultas);
    require __DIR__ . '/../handlers/libro_ingreso_boletin.php';
    verificar($errors === [] && $boletinGerminacionForm === $anterior && count($testDb->consultas) === $consultasAntes, 'No debe precargar en POST, sin sesion o fuera de un boletin.');
}

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['page' => 'boletin_germinacion', 'ingreso_id' => '7'];
$testDb->fallar = true;
$errors = [];
require __DIR__ . '/../handlers/libro_ingreso_boletin.php';
verificar(count($errors) === 1, 'Un error de base de datos debe notificarse.');
foreach ($testDb->consultas as $consulta) {
    verificar(strpos(ltrim($consulta), 'SELECT ') === 0, 'La precarga no debe escribir en la base de datos.');
}

echo 'OK: precarga de los siete boletines, aislamiento por empresa, errores, edicion y conservacion de POST.' . PHP_EOL;
