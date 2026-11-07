<?php
/* ==========================================================
   ELIMINAR EXTINTOR (PLANTA BAJA)
   Ruta: recidencias/activos/areas.php/seguimientoplantabaja/extintores_pb/eliminar_extintor.php
   ========================================================== */

require_once __DIR__ . '/../../../../clases/conexion.php';

$LISTADO = '../extintores.php';

/* Solo se acepta POST (nunca eliminar por URL/GET) */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $LISTADO);
    exit;
}

$idDetector = $_POST['id_extintor'] ?? '';

if (!ctype_digit($idDetector)) {
    header('Location: ' . $LISTADO . '?err=1');
    exit;
}

$idDetector = (int) $idDetector;
$bd = (new Conexion())->conectar();

pg_query($bd, 'BEGIN');

/* 1. Borra el extintor solo si pertenece a un área de Planta Baja */
$resultado = @pg_query_params($bd, "
    DELETE FROM public.tb_extintores d
    USING public.tb_areas a
    WHERE d.id_area = a.id_area
      AND a.nivel = 'Planta Baja'
      AND d.id_extintor = $1
", [$idDetector]);

if ($resultado === false || pg_affected_rows($resultado) === 0) {
    if ($resultado === false) {
        error_log('eliminar_extintor.php: ' . pg_last_error($bd));
    }
    pg_query($bd, 'ROLLBACK');
    header('Location: ' . $LISTADO . '?err=1');
    exit;
}

/* 2. Borra su historial de revisiones */
$historial = @pg_query_params($bd, "
    DELETE FROM public.tb_revisiones
    WHERE tipo_activo = 'extintores'
      AND id_activo = $1
", [$idDetector]);

if ($historial === false) {
    error_log('eliminar_extintor.php (historial): ' . pg_last_error($bd));
    pg_query($bd, 'ROLLBACK');
    header('Location: ' . $LISTADO . '?err=1');
    exit;
}

pg_query($bd, 'COMMIT');

header('Location: ' . $LISTADO . '?ok=3');
exit;