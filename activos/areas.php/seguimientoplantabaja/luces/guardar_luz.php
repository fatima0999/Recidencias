<?php
/* ==========================================================
   GUARDAR LUZ DE EMERGENCIA (PLANTA BAJA)
   Ruta: recidencias/activos/areas.php/seguimientoplantabaja/luces/guardar_luz.php
   Recibe el POST de lucesemergencia.php (misma carpeta)
   ========================================================== */
require_once __DIR__ . '/../../../../clases/conexion.php';

$LISTADO = '../lucesdeemergencia.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $LISTADO);
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
$id_area                = (int) ($_POST['id_area'] ?? 0);
$ubicacion_detalle      = limpiar($_POST['ubicacion_detalle'] ?? null);
$tipo_iluminacion       = limpiar($_POST['tipo_iluminacion'] ?? null);
$marca                  = limpiar($_POST['marca'] ?? null);
$modelo                 = limpiar($_POST['modelo'] ?? null);
$duracion_bateria       = limpiar($_POST['duracion_bateria'] ?? null);
$estado_fisico          = limpiar($_POST['estado_fisico'] ?? null);
$fecha_instalacion      = limpiar($_POST['fecha_instalacion'] ?? null);
$fecha_ultima_revision  = limpiar($_POST['fecha_ultima_revision'] ?? null);
$fecha_proxima_revision = limpiar($_POST['fecha_proxima_revision'] ?? null);
$observaciones          = limpiar($_POST['observaciones'] ?? null);

/* ---------- Validaciones del lado del servidor ---------- */
if ($id_area <= 0 || !$tipo_iluminacion || !$duracion_bateria || !$estado_fisico
    || !$fecha_instalacion || !$fecha_ultima_revision || !$fecha_proxima_revision) {
    volverConError('Completa todos los campos obligatorios.');
}

if (!in_array($tipo_iluminacion, ['LED', 'Fluorescente', 'Halógena', 'Otro'], true)) {
    volverConError('El tipo de iluminación no es válido.');
}

if (!in_array($estado_fisico, ['Bueno', 'Regular', 'Malo'], true)) {
    volverConError('El estado físico no es válido.');
}

$hoy = date('Y-m-d');
if ($fecha_instalacion > $hoy || $fecha_ultima_revision > $hoy
    || $fecha_ultima_revision < $fecha_instalacion
    || $fecha_proxima_revision <= $fecha_ultima_revision) {
    volverConError('Revisa las fechas: la próxima revisión debe ser posterior a la última y ninguna puede ser futura.');
}

if ($fecha_instalacion < '2000-01-01' || $fecha_proxima_revision > '2030-12-31') {
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

/* ¿La tabla tiene columna numero_folio? (si no, el folio se calcula en pantalla) */
$colFolio = pg_query(
    $bd,
    "SELECT 1 FROM information_schema.columns
     WHERE table_name = 'tb_luces_emergencia' AND column_name = 'numero_folio'"
);
$tieneFolio = ($colFolio !== false && pg_num_rows($colFolio) > 0);

/* ---------- INSERT + folio automático (en una transacción) ---------- */
pg_query($bd, 'BEGIN');

$ins = pg_query_params($bd, "
    INSERT INTO tb_luces_emergencia
        (id_area, ubicacion_detalle, tipo_iluminacion, marca, modelo, duracion_bateria,
         estado_fisico, fecha_instalacion, fecha_ultima_revision, fecha_proxima_revision,
         observaciones)
    VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)
    RETURNING id_luz
", [
    $id_area, $ubicacion_detalle, $tipo_iluminacion, $marca, $modelo, $duracion_bateria,
    $estado_fisico, $fecha_instalacion, $fecha_ultima_revision, $fecha_proxima_revision,
    $observaciones
]);

if ($ins === false) {
    /* Leer el error ANTES del ROLLBACK (si no, pg_last_error queda vacío) */
    $err = pg_last_error($bd);
    pg_query($bd, 'ROLLBACK');
    error_log('guardar_luz.php INSERT: ' . $err);
    volverConError('No se pudo guardar la luz de emergencia: ' . $err);
}

$id_luz = (int) pg_fetch_result($ins, 0, 'id_luz');

if ($tieneFolio) {
    $upd = pg_query_params(
        $bd,
        "UPDATE tb_luces_emergencia SET numero_folio = 'LUZ-' || LPAD(id_luz::text, 3, '0') WHERE id_luz = $1",
        [$id_luz]
    );

    if ($upd === false) {
        $err = pg_last_error($bd);
        pg_query($bd, 'ROLLBACK');
        error_log('guardar_luz.php UPDATE folio: ' . $err);
        volverConError('No se pudo generar el folio de la luz: ' . $err);
    }
}

pg_query($bd, 'COMMIT');

header('Location: ' . $LISTADO . '?ok=1');
exit;