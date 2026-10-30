<?php
/* ==========================================================
   ELIMINAR EXTINTOR (SÓTANO)
   Ruta: recidencias/activos/areas.php/seguimientoSotano.php/eliminar_extintor.php
   ========================================================== */

require_once __DIR__ . '/../../../clases/conexion.php';

/* Solo se acepta POST (nunca eliminar por URL/GET) */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: extintores.php');
    exit;
}

$idExtintor = $_POST['id_extintor'] ?? '';

if (!ctype_digit($idExtintor)) {
    header('Location: extintores.php?error=1');
    exit;
}

$conexion = (new Conexion())->conectar();

/* Solo borra si el extintor pertenece a un área del Sótano */
$sql = "
    DELETE FROM public.tb_extintores e
    USING public.tb_areas a
    WHERE e.id_area = a.id_area
      AND a.nivel = 'Sótano'
      AND e.id_extintor = $1
";

$resultado = @pg_query_params($conexion, $sql, [(int) $idExtintor]);

if ($resultado === false) {
    /* Por ejemplo, otra tabla depende de este extintor (llave foránea) */
    error_log('Error al eliminar extintor: ' . pg_last_error($conexion));
    header('Location: extintores.php?error=3');
    exit;
}

if (pg_affected_rows($resultado) === 0) {
    header('Location: extintores.php?error=2');
    exit;
}

header('Location: extintores.php?eliminado=1');
exit;