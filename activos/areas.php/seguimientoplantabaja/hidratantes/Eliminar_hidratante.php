<?php
/* ==========================================================
   UBICACIÓN: activos/areas.php/seguimientoplantabaja/hidratantes/Eliminar_hidratante.php
   ELIMINAR HIDRANTE - PLANTA BAJA
   Recibe POST (id_hidrante) desde hidratantes.php.
   Borra el hidrante y su historial en tb_revisiones (en una transacción).
   Solo elimina hidrantes cuya área sea de Planta Baja.
   ========================================================== */
require_once __DIR__ . '/../../../../clases/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../hidratantes.php');
    exit;
}

$id = (int) ($_POST['id_hidrante'] ?? 0);
if ($id <= 0) {
    header('Location: ../hidratantes.php?err=1');
    exit;
}

$bd = (new Conexion())->conectar();

/* El hidrante debe existir y ser de Planta Baja */
$chk = pg_query_params(
    $bd,
    "SELECT 1
       FROM tb_hidrantes d
       INNER JOIN tb_areas a ON a.id_area = d.id_area
      WHERE d.id_hidrante = $1 AND a.nivel = 'Planta Baja'",
    [$id]
);

if ($chk === false || pg_num_rows($chk) === 0) {
    header('Location: ../hidratantes.php?err=1');
    exit;
}

pg_query($bd, 'BEGIN');

/* 1) Historial de revisiones (tb_revisiones no tiene FK a tb_hidrantes, se borra a mano) */
$okRev = pg_query_params(
    $bd,
    "DELETE FROM tb_revisiones WHERE tipo_activo = 'hidrantes' AND id_activo = $1",
    [$id]
);

/* 2) El hidrante */
$okHid = $okRev !== false
    ? pg_query_params($bd, "DELETE FROM tb_hidrantes WHERE id_hidrante = $1", [$id])
    : false;

if ($okRev !== false && $okHid !== false && pg_affected_rows($okHid) === 1) {
    pg_query($bd, 'COMMIT');
    header('Location: ../hidratantes.php?ok=3');
    exit;
}

/* Leer el error ANTES del ROLLBACK */
error_log('Eliminar_hidratante.php: ' . pg_last_error($bd));
pg_query($bd, 'ROLLBACK');
header('Location: ../hidratantes.php?err=1');
exit;