<?php
/* ==========================================================
   UBICACIÓN: activos/areas.php/seguimientoSotano.php/sencores/eliminar_detector.php
   ELIMINA UN DETECTOR DE HUMO Y SU HISTORIAL (tb_revisiones)
   ========================================================== */

require_once __DIR__ . '/../../../../clases/conexion.php';

const URL_LISTADO = '../DectectoresdeHumo.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . URL_LISTADO);
    exit;
}

$id = filter_input(INPUT_POST, 'id_detector', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    exit('Detector no válido.');
}

$bd = (new Conexion())->conectar();

/* tb_revisiones no tiene llave foránea al detector, por eso se borra su historial aquí */
pg_query($bd, 'BEGIN');

$r1 = pg_query_params($bd,
    "DELETE FROM tb_revisiones WHERE tipo_activo = 'detectores_humo' AND id_activo = $1", [$id]);

$r2 = pg_query_params($bd,
    "DELETE FROM tb_detectores_humo WHERE id_detector = $1", [$id]);

if (!$r1 || !$r2) {
    pg_query($bd, 'ROLLBACK');
    http_response_code(500);
    exit('No se pudo eliminar el detector: ' . pg_last_error($bd));
}

pg_query($bd, 'COMMIT');

header('Location: ' . URL_LISTADO . '?ok=3');
exit;