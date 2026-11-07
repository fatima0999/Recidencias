<?php
/* ==========================================================
   GUARDAR EXTINTOR (PLANTA BAJA)
   Ruta: recidencias/activos/areas.php/seguimientoplantabaja/extintores_pb/guardar_extintor.php
   Recibe el POST de alta_extintor.php (misma carpeta)
   ========================================================== */
require_once __DIR__ . '/../../../../clases/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../extintores.php');
    exit;
}

$conexion = new Conexion();
$bd = $conexion->conectar();

/* ---------- Helpers ---------- */
function limpiar($v) {
    $v = trim((string) ($v ?? ''));
    return $v === '' ? null : $v;
}

function volverConError($mensaje) {
    echo '<script>alert(' . json_encode($mensaje, JSON_UNESCAPED_UNICODE) . '); history.back();</script>';
    exit;
}

/* ---------- Datos del formulario ---------- */
$id_area                     = (int) ($_POST['id_area'] ?? 0);
$tipo_agente                 = limpiar($_POST['tipo_agente'] ?? null);
$capacidad                   = limpiar($_POST['capacidad'] ?? null);
$marca                       = limpiar($_POST['marca'] ?? null);
$modelo                      = limpiar($_POST['modelo'] ?? null);
$estado_fisico               = limpiar($_POST['estado_fisico'] ?? null);
$peso_actual                 = limpiar($_POST['peso_actual'] ?? null);
$fecha_ultimo_mantenimiento  = limpiar($_POST['fecha_ultimo_mantenimiento'] ?? null);
$fecha_proximo_mantenimiento = limpiar($_POST['fecha_proximo_mantenimiento'] ?? null);
$fecha_recarga               = limpiar($_POST['fecha_recarga'] ?? null);
$fecha_proxima_recarga       = limpiar($_POST['fecha_proxima_recarga'] ?? null);
$observaciones               = limpiar($_POST['observaciones'] ?? null);

/* ---------- Validaciones del lado del servidor ---------- */
if ($id_area <= 0 || !$tipo_agente || $capacidad === null || !$marca || !$modelo || !$estado_fisico
    || !$fecha_ultimo_mantenimiento || !$fecha_proximo_mantenimiento
    || !$fecha_recarga || !$fecha_proxima_recarga) {
    volverConError('Completa todos los campos obligatorios.');
}

if (!in_array($tipo_agente, ['PQS ABC', 'PQS BC', 'CO2', 'Agua', 'Espuma', 'Acetato de potasio'], true)) {
    volverConError('El tipo de agente no es válido.');
}

if (!in_array($estado_fisico, ['Bueno', 'Regular', 'Malo'], true)) {
    volverConError('El estado físico no es válido.');
}

if (!is_numeric($capacidad) || (float) $capacidad <= 0 || (float) $capacidad > 100) {
    volverConError('La capacidad debe ser un número mayor a 0 (en kg).');
}

if ($peso_actual !== null && (!is_numeric($peso_actual) || (float) $peso_actual < 0 || (float) $peso_actual > 200)) {
    volverConError('El peso actual debe ser un número válido (en kg).');
}

$hoy = date('Y-m-d');
if ($fecha_ultimo_mantenimiento > $hoy || $fecha_recarga > $hoy) {
    volverConError('El último mantenimiento y la última recarga no pueden ser fechas futuras.');
}

if ($fecha_proximo_mantenimiento <= $fecha_ultimo_mantenimiento
    || $fecha_proxima_recarga <= $fecha_recarga) {
    volverConError('Cada próxima fecha debe ser posterior a su fecha anterior (mantenimiento y recarga).');
}

if ($fecha_ultimo_mantenimiento < '2000-01-01' || $fecha_recarga < '2000-01-01'
    || $fecha_proximo_mantenimiento > '2030-12-31' || $fecha_proxima_recarga > '2030-12-31') {
    volverConError('Las fechas deben estar entre el 1 de enero de 2000 y el 31 de diciembre de 2030.');
}

/* El área debe existir y ser de Planta Baja */
$chk = pg_query_params(
    $bd,
    "SELECT 1 FROM tb_areas WHERE id_area = $1 AND nivel = 'Planta Baja' AND activo = TRUE",
    [$id_area]
);
if ($chk === false || pg_num_rows($chk) === 0) {
    volverConError('El área seleccionada no es válida para Planta Baja.');
}

/* ---------- INSERT con folio (en una transacción) ----------
   numero_folio es obligatorio: se toma el siguiente id de la secuencia ANTES de insertar,
   así el folio EXT-001 entra en el mismo INSERT. */
pg_query($bd, 'BEGIN');

$seq = pg_query($bd, "SELECT nextval(pg_get_serial_sequence('tb_extintores', 'id_extintor')) AS id");
if ($seq === false) {
    $err = pg_last_error($bd);
    pg_query($bd, 'ROLLBACK');
    error_log('guardar_extintor.php nextval: ' . $err);
    volverConError('No se pudo generar el folio del extintor: ' . $err);
}

$id_extintor  = (int) pg_fetch_result($seq, 0, 'id');
$numero_folio = 'EXT-' . str_pad((string) $id_extintor, 3, '0', STR_PAD_LEFT);

$ins = pg_query_params($bd, "
    INSERT INTO tb_extintores
        (id_extintor, numero_folio, id_area, tipo_agente, capacidad, marca, modelo, estado_fisico,
         peso_actual, fecha_ultimo_mantenimiento, fecha_proximo_mantenimiento,
         fecha_recarga, fecha_proxima_recarga, observaciones)
    VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14)
", [
    $id_extintor, $numero_folio, $id_area, $tipo_agente, $capacidad, $marca, $modelo, $estado_fisico,
    $peso_actual, $fecha_ultimo_mantenimiento, $fecha_proximo_mantenimiento,
    $fecha_recarga, $fecha_proxima_recarga, $observaciones
]);

if ($ins === false) {
    /* Leer el error ANTES del ROLLBACK (si no, pg_last_error queda vacío) */
    $err = pg_last_error($bd);
    pg_query($bd, 'ROLLBACK');
    error_log('guardar_extintor.php INSERT: ' . $err);
    volverConError('No se pudo guardar el extintor: ' . $err);
}

pg_query($bd, 'COMMIT');

header('Location: ../extintores.php?ok=1');
exit;