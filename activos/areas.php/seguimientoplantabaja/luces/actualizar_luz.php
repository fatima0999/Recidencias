<?php
/* ==========================================================
   ACTUALIZAR LUZ DE EMERGENCIA (PLANTA BAJA)
   Ruta: recidencias/activos/areas.php/seguimientoplantabaja/luces/actualizar_luz.php
   Registra la revisión en tb_revisiones y actualiza tb_luces_emergencia
   ========================================================== */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../../../clases/conexion.php';

$conexion = new Conexion();
$cn = $conexion->conectar();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

/* Solo luces cuya área sea de Planta Baja */
$r = pg_query_params($cn, "SELECT l.*
    FROM tb_luces_emergencia l
    INNER JOIN tb_areas a ON a.id_area = l.id_area
    WHERE l.id_luz = $1 AND a.nivel = 'Planta Baja'", [$id]);
$luz = pg_fetch_assoc($r);
if (!$luz) { http_response_code(404); exit('Luz no encontrada en Planta Baja'); }

/* Valores que acepta la tabla tb_revisiones (valor => texto que se ve) */
$TIPOS_REVISION = [
    'preventiva' => 'Preventiva',
    'correctiva' => 'Correctiva',
    'inspeccion' => 'Inspección',
];
$RESULTADOS = [
    'aprobado'          => 'Aprobado',
    'con_observaciones' => 'Con observaciones',
    'rechazado'         => 'Rechazado',
];
$ESTADOS_FISICOS = ['Bueno', 'Regular', 'Malo'];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha_rev  = $_POST['fecha_revision'] ?? '';
    $fecha_prox = $_POST['fecha_proxima_revision'] ?? '';
    $tipo_rev   = $_POST['tipo_revision'] ?? '';
    $resultado  = $_POST['resultado'] ?? '';
    $estado_fis = $_POST['estado_fisico'] ?? '';
    $obs        = trim($_POST['observaciones'] ?? '');
    $hoy        = date('Y-m-d');

    /* id_usuario es llave foránea: solo se guarda si existe en tb_usuarios */
    $id_usuario = null;
    if (!empty($_SESSION['id_usuario'])) {
        $u = pg_query_params($cn, "SELECT 1 FROM tb_usuarios WHERE id_usuario = $1",
            [(int)$_SESSION['id_usuario']]);
        if ($u && pg_num_rows($u) > 0) {
            $id_usuario = (int)$_SESSION['id_usuario'];
        }
    }

    if ($fecha_rev === '' || $fecha_prox === '') {
        $error = 'Completa las dos fechas.';
    } elseif (!isset($TIPOS_REVISION[$tipo_rev])) {
        $error = 'Selecciona un tipo de revisión válido.';
    } elseif (!isset($RESULTADOS[$resultado])) {
        $error = 'Selecciona un resultado válido.';
    } elseif (!in_array($estado_fis, $ESTADOS_FISICOS, true)) {
        $error = 'Selecciona un estado físico válido.';
    } elseif ($fecha_rev > $hoy) {
        $error = 'La fecha de revisión no puede ser futura.';
    } elseif (!empty($luz['fecha_instalacion']) && $fecha_rev < $luz['fecha_instalacion']) {
        $error = 'La revisión no puede ser anterior a la instalación (' . $luz['fecha_instalacion'] . ').';
    } elseif ($fecha_prox <= $fecha_rev) {
        $error = 'La próxima revisión debe ser posterior a la fecha de revisión.';
    } elseif ($fecha_prox > '2030-12-31') {
        $error = 'La próxima revisión no puede superar el 31 de diciembre de 2030.';
    } else {
        pg_query($cn, 'BEGIN');

        $ok1 = @pg_query_params($cn, "INSERT INTO tb_revisiones
            (tipo_activo, id_activo, fecha_revision, fecha_proxima_revision, tipo_revision, resultado, id_usuario, observaciones)
            VALUES ('luces_emergencia', $1, $2, $3, $4, $5, $6, $7)",
            [$id, $fecha_rev, $fecha_prox, $tipo_rev, $resultado, $id_usuario, $obs]);

        $ok2 = false;
        if ($ok1) {
            $ok2 = @pg_query_params($cn, "UPDATE tb_luces_emergencia
                SET fecha_ultima_revision = $1, fecha_proxima_revision = $2, estado_fisico = $3,
                    observaciones = $4, updated_at = now()
                WHERE id_luz = $5",
                [$fecha_rev, $fecha_prox, $estado_fis, $obs, $id]);
        }

        if ($ok1 && $ok2) {
            pg_query($cn, 'COMMIT');
            header("Location: ficha_luz.php?id=$id");
            exit;
        }

        /* Leer el error ANTES del ROLLBACK */
        $error = pg_last_error($cn);
        pg_query($cn, 'ROLLBACK');
    }
}

$prox_default = date('Y-m-d', strtotime('+30 days'));
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Actualizar luz #<?= $id ?> - Planta Baja</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background:#0b0d13; color:#fff; }
  .card-s { background:#10131c; border:1px solid #1f2430; border-radius:14px; }
  .form-control, .form-select { background:#0b0d13; border-color:#2a3040; color:#fff; }
  .form-control:focus, .form-select:focus { background:#0b0d13; color:#fff; border-color:#d6143a; box-shadow:none; }
  .form-label { color:#8b93a7; font-size:.85rem; }
  .btn-sirev { background:#d6143a; border-color:#d6143a; color:#fff; }
  .btn-sirev:hover { background:#b81031; color:#fff; }
</style>
</head>
<body>
<div class="container py-3" style="max-width:600px">
  <h5 class="mb-1">Actualizar luz de emergencia #<?= $id ?></h5>
  <div class="mb-3" style="color:#8b93a7;font-size:.85rem">Planta Baja</div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" class="card-s p-3">
    <input type="hidden" name="id" value="<?= $id ?>">

    <label class="form-label">Fecha de revisión</label>
    <input type="date" name="fecha_revision" class="form-control mb-3"
           value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>

    <label class="form-label">Próxima revisión</label>
    <input type="date" name="fecha_proxima_revision" class="form-control mb-3"
           value="<?= $prox_default ?>" max="2030-12-31" required>

    <label class="form-label">Tipo de revisión</label>
    <select name="tipo_revision" class="form-select mb-3" required>
      <?php foreach ($TIPOS_REVISION as $valor => $texto): ?>
        <option value="<?= $valor ?>"><?= $texto ?></option>
      <?php endforeach; ?>
    </select>

    <label class="form-label">Resultado</label>
    <select name="resultado" class="form-select mb-3" required>
      <?php foreach ($RESULTADOS as $valor => $texto): ?>
        <option value="<?= $valor ?>"><?= $texto ?></option>
      <?php endforeach; ?>
    </select>

    <label class="form-label">Estado físico</label>
    <select name="estado_fisico" class="form-select mb-3">
      <?php foreach ($ESTADOS_FISICOS as $op): ?>
        <option <?= ($luz['estado_fisico'] ?? '') === $op ? 'selected' : '' ?>><?= $op ?></option>
      <?php endforeach; ?>
    </select>

    <label class="form-label">Observaciones</label>
    <textarea name="observaciones" class="form-control mb-3" rows="3"></textarea>

    <div class="d-flex gap-2">
      <button class="btn btn-sirev flex-fill">Guardar actualización</button>
      <a href="ficha_luz.php?id=<?= $id ?>" class="btn btn-outline-light">Cancelar</a>
    </div>
  </form>
</div>
</body>
</html>