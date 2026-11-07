<?php
/* ==========================================================
   ELIMINAR ALARMA SÍSMICA (PLANTA BAJA)
   Ruta: recidencias/activos/areas.php/seguimientoplantabaja/sismicas/eliminar_alarma.php
   ========================================================== */

require_once __DIR__ . '/../../../../clases/conexion.php';

$LISTADO = '../alarmas.php';

/* Solo se acepta POST (nunca eliminar por URL/GET) */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $LISTADO);
    exit;
}

$idAlarma = $_POST['id_alarma'] ?? '';

if (!ctype_digit($idAlarma)) {
    header('Location: ' . $LISTADO . '?err=1');
    exit;
}

$idAlarma = (int) $idAlarma;
$bd = (new Conexion())->conectar();

pg_query($bd, 'BEGIN');

/* 1. Borra la alarma solo si pertenece a un área de Planta Baja */
$resultado = @pg_query_params($bd, "
    DELETE FROM public.tb_alarmas_emergencia d
    USING public.tb_areas a
    WHERE d.id_area = a.id_area
      AND a.nivel = 'Planta Baja'
      AND d.id_alarma = $1
", [$idAlarma]);

if ($resultado === false || pg_affected_rows($resultado) === 0) {
    if ($resultado === false) {
        error_log('eliminar_alarma.php: ' . pg_last_error($bd));
    }
    pg_query($bd, 'ROLLBACK');
    header('Location: ' . $LISTADO . '?err=1');
    exit;
}

/* 2. Borra su historial de revisiones */
$historial = @pg_query_params($bd, "
    DELETE FROM public.tb_revisiones
    WHERE tipo_activo = 'alarmas_emergencia'
      AND id_activo = $1
", [$idAlarma]);

if ($historial === false) {
    error_log('eliminar_alarma.php (historial): ' . pg_last_error($bd));
    pg_query($bd, 'ROLLBACK');
    header('Location: ' . $LISTADO . '?err=1');
    exit;
}

pg_query($bd, 'COMMIT');

header('Location: ' . $LISTADO . '?ok=3');
exit;