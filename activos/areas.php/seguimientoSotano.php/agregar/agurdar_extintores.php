<?php
/* ==========================================================
   GUARDAR EXTINTOR (SÓTANO)
   Ruta: recidencias/activos/areas.php/seguimientoSotano.php/agregar/agurdar_extintores.php
   ========================================================== */

require_once __DIR__ . '/../../../../clases/conexion.php';

/* Solo aceptar envíos del formulario */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../extintores.php');
    exit;
}

$conexion = (new Conexion())->conectar();


/* ==========================================================
   LEER DATOS DEL FORMULARIO
   Los campos opcionales vacíos se convierten a NULL, porque
   PostgreSQL no acepta '' en columnas integer, numeric o date.
   ========================================================== */

function valorONulo(string $campo): ?string
{
    $valor = trim($_POST[$campo] ?? '');
    return $valor === '' ? null : $valor;
}

$numero_folio = trim($_POST['numero_folio'] ?? '');
$tipo_agente  = trim($_POST['tipo_agente'] ?? '');
$id_area      = valorONulo('id_area');
$id_proveedor = valorONulo('id_proveedor');
$capacidad    = valorONulo('capacidad');
$marca        = valorONulo('marca');
$modelo       = valorONulo('modelo');
$f_ultimo     = valorONulo('fecha_ultimo_mantenimiento');
$f_proximo    = valorONulo('fecha_proximo_mantenimiento');


/* ==========================================================
   VALIDACIONES DEL LADO DEL SERVIDOR
   ========================================================== */

$agentesValidos = [
    'POLVO QUIMICO SECO (ABC)',
    'POLVO QUIMICO SECO (PQS)',
    'CO2',
    'AGUA',
    'ESPUMA',
    'OTRO',
];

if ($numero_folio === '' || $tipo_agente === '' || $id_area === null) {
    die('Faltan campos obligatorios (folio, tipo de agente o área).');
}

if (!in_array($tipo_agente, $agentesValidos, true)) {
    die('Tipo de agente no válido.');
}

if (!ctype_digit($id_area) || ($id_proveedor !== null && !ctype_digit($id_proveedor))) {
    die('Área o proveedor no válidos.');
}

if ($capacidad !== null && !is_numeric($capacidad)) {
    die('La capacidad debe ser un número.');
}

if ($f_ultimo !== null && $f_proximo !== null && $f_proximo <= $f_ultimo) {
    die('El próximo mantenimiento debe ser posterior al último.');
}


/* ==========================================================
   INSERTAR EN tb_extintores
   ========================================================== */

$sql = "
    INSERT INTO public.tb_extintores (
        id_area,
        id_proveedor,
        numero_folio,
        tipo_agente,
        capacidad,
        marca,
        modelo,
        fecha_ultimo_mantenimiento,
        fecha_proximo_mantenimiento,
        created_at,
        updated_at
    )
    VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, NOW(), NOW())
    RETURNING id_extintor
";

$resultado = pg_query_params($conexion, $sql, [
    (int) $id_area,
    $id_proveedor !== null ? (int) $id_proveedor : null,
    $numero_folio,
    $tipo_agente,
    $capacidad,
    $marca,
    $modelo,
    $f_ultimo,
    $f_proximo,
]);

if ($resultado === false) {
    /* Mientras desarrollas, muestra el error real de PostgreSQL */
    die('Error al guardar el extintor: ' . pg_last_error($conexion));
}

header('Location: ../extintores.php?guardado=1');
exit;