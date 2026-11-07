<?php
/* ==========================================================
   GUARDAR ALARMA SÍSMICA (PLANTA BAJA)
   Ruta: recidencias/activos/areas.php/seguimientoplantabaja/sismicas/guardar_alarma.php
   Recibe el POST de alta_alarma.php (misma carpeta)
   ========================================================== */
require_once __DIR__ . '/../../../../clases/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../alarmas.php');
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
$tipo_alarma            = limpiar($_POST['tipo_alarma'] ?? null);
$marca                  = limpiar($_POST['marca'] ?? null);
$modelo                 = limpiar($_POST['modelo'] ?? null);
$estado_fisico          = limpiar($_POST['estado_fisico'] ?? null);
$fecha_instalacion      = limpiar($_POST['fecha_instalacion'] ?? null);
$fecha_ultima_revision  = limpiar($_POST['fecha_ultima_revision'] ?? null);
$fecha_proxima_revision = limpiar($_POST['fecha_proxima_revision'] ?? null);
$observaciones          = limpiar($_POST['observaciones'] ?? null);

/* ---------- Validaciones del lado del servidor ---------- */
if ($id_area <= 0 || !$tipo_alarma || !$marca || !$modelo || !$estado_fisico
    || !$fecha_instalacion || !$fecha_ultima_revision || !$fecha_proxima_revision) {
    volverConError('Completa todos los campos obligatorios.');
}

if (!in_array($tipo_alarma, ['Sísmica', 'Sonora', 'Visual', 'Sonora y visual'], true)) {
    volverConError('El tipo de alarma no es válido.');
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

/* ---------- INSERT ---------- */
/* tb_alarmas_emergencia no tiene numero_folio: el folio ALM-001 se calcula en pantalla con el id */
$ins = pg_query_params($bd, "
    INSERT INTO tb_alarmas_emergencia
        (id_area, tipo_alarma, marca, modelo, estado_fisico,
         fecha_instalacion, fecha_ultima_revision, fecha_proxima_revision,
         observaciones)
    VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
    RETURNING id_alarma
", [
    $id_area, $tipo_alarma, $marca, $modelo, $estado_fisico,
    $fecha_instalacion, $fecha_ultima_revision, $fecha_proxima_revision,
    $observaciones
]);

if ($ins === false) {
    $err = pg_last_error($bd);
    error_log('guardar_alarma.php INSERT: ' . $err);
    volverConError('No se pudo guardar la alarma sísmica: ' . $err);
}

header('Location: ../alarmas.php?ok=1');
exit;