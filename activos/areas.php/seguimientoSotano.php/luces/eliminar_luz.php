<?php
/* ==========================================================
   ELIMINAR LUZ DE EMERGENCIA (SÓTANO)
   ========================================================== */

require_once $_SERVER['DOCUMENT_ROOT'] . '/recidencias/clases/conexion.php';

/* ===== AJUSTA SEGÚN LA CARPETA DONDE ESTÁ ESTE ARCHIVO =====
   - Si está dentro de  seguimientoSotano.php/luces/  ->  '../lucesDeEmergencia.php'
   - Si está en         seguimientoSotano.php/        ->  'lucesDeEmergencia.php'
   ============================================================ */
$LISTADO = '../lucesDeEmergencia.php';

/* Solo se acepta POST (nunca eliminar por URL/GET) */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $LISTADO);
    exit;
}

$idLuz = $_POST['id_luz'] ?? '';

if (!ctype_digit($idLuz)) {
    header('Location: ' . $LISTADO . '?msg=error');
    exit;
}

$idLuz = (int) $idLuz;
$bd = (new Conexion())->conectar();

pg_query($bd, 'BEGIN');

/* 1. Borra la luz solo si pertenece a un área del Sótano */
$resultado = @pg_query_params($bd, "
    DELETE FROM public.tb_luces_emergencia l
    USING public.tb_areas a
    WHERE l.id_area = a.id_area
      AND a.nivel = 'Sótano'
      AND l.id_luz = $1
", [$idLuz]);

if ($resultado === false || pg_affected_rows($resultado) === 0) {
    if ($resultado === false) {
        error_log('eliminar_luz.php: ' . pg_last_error($bd));
    }
    pg_query($bd, 'ROLLBACK');
    header('Location: ' . $LISTADO . '?msg=error');
    exit;
}

/* 2. Borra su historial de revisiones */
$historial = @pg_query_params($bd, "
    DELETE FROM public.tb_revisiones
    WHERE tipo_activo = 'luces_emergencia'
      AND id_activo = $1
", [$idLuz]);

if ($historial === false) {
    error_log('eliminar_luz.php (historial): ' . pg_last_error($bd));
    pg_query($bd, 'ROLLBACK');
    header('Location: ' . $LISTADO . '?msg=error');
    exit;
}

pg_query($bd, 'COMMIT');

header('Location: ' . $LISTADO . '?msg=eliminado');
exit;