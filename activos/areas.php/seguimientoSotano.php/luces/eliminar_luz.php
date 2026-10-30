<?php
/* ==========================================================
   ELIMINAR LUZ DE EMERGENCIA (SÓTANO)
   Ruta: recidencias/activos/areas.php/seguimientoSotano.php/luces/eliminar_luz.php
   ========================================================== */

require_once $_SERVER['DOCUMENT_ROOT'] . '/recidencias/clases/conexion.php';

/* Solo se acepta POST (nunca eliminar por URL/GET) */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../lucesDeEmergencia.php');
    exit;
}

$idLuz = $_POST['id_luz'] ?? '';

if (!ctype_digit($idLuz)) {
    header('Location: ../lucesDeEmergencia.php?msg=error');
    exit;
}

$bd = (new Conexion())->conectar();

/* Solo borra si la luz pertenece a un área del Sótano */
$sql = "
    DELETE FROM public.tb_luces_emergencia l
    USING public.tb_areas a
    WHERE l.id_area = a.id_area
      AND a.nivel = 'Sótano'
      AND l.id_luz = $1
";

$resultado = @pg_query_params($bd, $sql, [(int) $idLuz]);

if ($resultado === false) {
    /* Por ejemplo: otra tabla depende de esta luz (llave foránea) */
    error_log('eliminar_luz.php: ' . pg_last_error($bd));
    header('Location: ../lucesDeEmergencia.php?msg=error');
    exit;
}

if (pg_affected_rows($resultado) === 0) {
    header('Location: ../lucesDeEmergencia.php?msg=error');
    exit;
}

header('Location: ../lucesDeEmergencia.php?msg=eliminado');
exit;