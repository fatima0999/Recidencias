<?php
/* ==========================================================
   ELIMINAR HIDRANTE (SÓTANO)
   Ruta: recidencias/activos/areas.php/seguimientoSotano.php/hidrantes/eliminar_hidrante.php
   ========================================================== */

require_once __DIR__ . '/../../../../clases/conexion.php';

$LISTADO = '../Hidratantes.php';

/* Solo se acepta POST (nunca eliminar por URL/GET) */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $LISTADO);
    exit;
}

$idDetector = $_POST['id_hidrante'] ?? '';

if (!ctype_digit($idDetector)) {
    header('Location: ' . $LISTADO . '?err=1');
    exit;
}

$idDetector = (int) $idDetector;
$bd = (new Conexion())->conectar();

pg_query($bd, 'BEGIN');

/* 1. Borra el hidrante solo si pertenece a un área del Sótano */
$resultado = @pg_query_params($bd, "
    DELETE FROM public.tb_hidrantes d
    USING public.tb_areas a
    WHERE d.id_area = a.id_area
      AND a.nivel = 'Sótano'
      AND d.id_hidrante = $1
", [$idDetector]);

if ($resultado === false || pg_affected_rows($resultado) === 0) {
    if ($resultado === false) {
        error_log('eliminar_hidrante.php: ' . pg_last_error($bd));
    }
    pg_query($bd, 'ROLLBACK');
    header('Location: ' . $LISTADO . '?err=1');
    exit;
}

/* 2. Borra su historial de revisiones */
$historial = @pg_query_params($bd, "
    DELETE FROM public.tb_revisiones
    WHERE tipo_activo = 'hidrantes'
      AND id_activo = $1
", [$idDetector]);

if ($historial === false) {
    error_log('eliminar_hidrante.php (historial): ' . pg_last_error($bd));
    pg_query($bd, 'ROLLBACK');
    header('Location: ' . $LISTADO . '?err=1');
    exit;
}

pg_query($bd, 'COMMIT');

header('Location: ' . $LISTADO . '?ok=3');
exit;