<?php
/* ==========================================================
   UBICACIÓN: activos/areas.php/seguimientoplantabaja/EXTINTORES/actualizar_extintor.php
   ACTUALIZAR EXTINTOR (registra revisión en tb_revisiones)
   - Si el tipo es "Recarga": actualiza fecha_recarga / fecha_proxima_recarga
   - Si es otro tipo:         actualiza fecha_ultimo_mantenimiento / fecha_proximo_mantenimiento
   ========================================================== */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../../../clases/conexion.php';

$conexion = new Conexion();
$cn = $conexion->conectar();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

/* Busca la ficha (ver_*.php) en esta carpeta aunque el nombre cambie; si no existe, vuelve al listado */
$VOLVER = '../extintoresplantabaja.php';
foreach (glob(__DIR__ . '/ver_*.php') ?: [] as $f) {
    $VOLVER = basename($f) . '?id=' . $id;
    break;
}

$r = pg_query_params($cn, "SELECT * FROM tb_extintores WHERE id_extintor = $1", [$id]);
$ext = pg_fetch_assoc($r);
if (!$ext) { http_response_code(404); exit('Extintor no encontrado'); }

/* Valores que acepta la tabla tb_revisiones (valor => texto que se ve) */
$TIPOS_REVISION = [
    'preventiva' => 'Preventiva',
    'correctiva' => 'Correctiva',
    'recarga'    => 'Recarga',
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
    $fecha_rev   = $_POST['fecha_revision'] ?? '';
    $fecha_prox  = $_POST['fecha_proxima'] ?? '';
    $tipo_rev    = $_POST['tipo_revision'] ?? '';
    $resultado   = $_POST['resultado'] ?? '';
    $estado_fis  = $_POST['estado_fisico'] ?? '';
    $peso        = trim($_POST['peso_actual'] ?? '');
    $obs         = trim($_POST['observaciones'] ?? '');
    $hoy         = date('Y-m-d');

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
    } elseif ($peso !== '' && (!is_numeric($peso) || (float)$peso < 0 || (float)$peso > 200)) {
        $error = 'El peso actual debe ser un número válido (en kg).';
    } elseif ($fecha_rev > $hoy) {
        $error = 'La fecha de revisión no puede ser futura.';
    } elseif ($fecha_prox <= $fecha_rev) {
        $error = 'La próxima fecha debe ser posterior a la fecha de revisión.';
    } elseif ($fecha_prox > '2030-12-31') {
        $error = 'La próxima fecha no puede superar el 31 de diciembre de 2030.';
    } else {
        $esRecarga = ($tipo_rev === 'recarga');
        $pesoSql   = ($peso === '') ? null : $peso;

        pg_query($cn, 'BEGIN');

        $ok1 = @pg_query_params($cn, "INSERT INTO tb_revisiones
            (tipo_activo, id_activo, fecha_revision, fecha_proxima_revision, tipo_revision, resultado, id_usuario, observaciones)
            VALUES ('extintores', $1, $2, $3, $4, $5, $6, $7)",
            [$id, $fecha_rev, $fecha_prox, $tipo_rev, $resultado, $id_usuario, ($obs === '' ? null : $obs)]);

        $ok2 = false;
        if ($ok1) {
            if ($esRecarga) {
                $sql = "UPDATE tb_extintores
                           SET fecha_recarga = $1, fecha_proxima_recarga = $2, estado_fisico = $3,
                               peso_actual = COALESCE($4::numeric, peso_actual),
                               observaciones = COALESCE(NULLIF($5::text, ''), observaciones),
                               updated_at = now()
                         WHERE id_extintor = $6";
            } else {
                $sql = "UPDATE tb_extintores
                           SET fecha_ultimo_mantenimiento = $1, fecha_proximo_mantenimiento = $2, estado_fisico = $3,
                               peso_actual = COALESCE($4::numeric, peso_actual),
                               observaciones = COALESCE(NULLIF($5::text, ''), observaciones),
                               updated_at = now()
                         WHERE id_extintor = $6";
            }
            $ok2 = @pg_query_params($cn, $sql, [$fecha_rev, $fecha_prox, $estado_fis, $pesoSql, $obs, $id]);
        }

        if ($ok1 && $ok2) {
            pg_query($cn, 'COMMIT');
            header('Location: ' . $VOLVER);
            exit;
        }

        /* Leer el error ANTES del ROLLBACK */
        $error = pg_last_error($cn);
        pg_query($cn, 'ROLLBACK');
    }
}

$prox_default = date('Y-m-d', strtotime('+30 days'));
$tipo_actual  = $_POST['tipo_revision'] ?? 'preventiva';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Actualizar extintor #<?= $id ?></title>
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
  <h5 class="mb-1">Actualizar extintor <?= htmlspecialchars($ext['numero_folio'] ?? ('#' . $id)) ?></h5>
  <p class="mb-3" style="color:#8b93a7;">
    Mantenimiento: próximo <?= htmlspecialchars($ext['fecha_proximo_mantenimiento'] ?? '—') ?>
    · Recarga: próxima <?= htmlspecialchars($ext['fecha_proxima_recarga'] ?? '—') ?>
  </p>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" class="card-s p-3">
    <input type="hidden" name="id" value="<?= $id ?>">

    <label class="form-label">Tipo de revisión</label>
    <select name="tipo_revision" id="tipo_revision" class="form-select mb-3" required>
      <?php foreach ($TIPOS_REVISION as $valor => $texto): ?>
        <option value="<?= $valor ?>" <?= $tipo_actual === $valor ? 'selected' : '' ?>><?= $texto ?></option>
      <?php endforeach; ?>
    </select>

    <label class="form-label">Fecha de revisión</label>
    <input type="date" name="fecha_revision" id="fecha_revision" class="form-control mb-3"
           value="<?= htmlspecialchars($_POST['fecha_revision'] ?? date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>" required>

    <label class="form-label" id="lblProxima">Próximo mantenimiento</label>
    <input type="date" name="fecha_proxima" id="fecha_proxima" class="form-control mb-1"
           value="<?= htmlspecialchars($_POST['fecha_proxima'] ?? $prox_default) ?>" max="2030-12-31" required>
    <div class="form-text mb-3" id="ayudaProxima" style="color:#8b93a7;">
      Se guarda como próximo mantenimiento del extintor.
    </div>

    <label class="form-label">Resultado</label>
    <select name="resultado" class="form-select mb-3" required>
      <?php foreach ($RESULTADOS as $valor => $texto): ?>
        <option value="<?= $valor ?>" <?= ($_POST['resultado'] ?? '') === $valor ? 'selected' : '' ?>><?= $texto ?></option>
      <?php endforeach; ?>
    </select>

    <label class="form-label">Estado físico</label>
    <select name="estado_fisico" class="form-select mb-3">
      <?php foreach ($ESTADOS_FISICOS as $op): ?>
        <option <?= (($_POST['estado_fisico'] ?? $ext['estado_fisico'] ?? '') === $op) ? 'selected' : '' ?>><?= $op ?></option>
      <?php endforeach; ?>
    </select>

    <label class="form-label">Peso actual en kg (opcional)</label>
    <input type="number" name="peso_actual" class="form-control mb-3" min="0" max="200" step="0.01"
           placeholder="Actual: <?= htmlspecialchars($ext['peso_actual'] ?? '—') ?>"
           value="<?= htmlspecialchars($_POST['peso_actual'] ?? '') ?>">

    <label class="form-label">Observaciones</label>
    <textarea name="observaciones" class="form-control mb-3" rows="3"><?= htmlspecialchars($_POST['observaciones'] ?? '') ?></textarea>

    <div class="d-flex gap-2">
      <button class="btn btn-sirev flex-fill">Guardar actualización</button>
      <a href="<?= htmlspecialchars($VOLVER) ?>" class="btn btn-outline-light">Cancelar</a>
    </div>
  </form>
</div>

<script>
  /* El texto de la fecha cambia según el tipo de revisión */
  const tipo   = document.getElementById('tipo_revision');
  const lbl    = document.getElementById('lblProxima');
  const ayuda  = document.getElementById('ayudaProxima');
  const fRev   = document.getElementById('fecha_revision');
  const fProx  = document.getElementById('fecha_proxima');

  function ajustar() {
    const esRecarga = tipo.value === 'recarga';
    lbl.textContent = esRecarga ? 'Próxima recarga' : 'Próximo mantenimiento';
    ayuda.textContent = esRecarga
      ? 'Se guarda como fecha de recarga y próxima recarga del extintor.'
      : 'Se guarda como último y próximo mantenimiento del extintor.';

    if (fRev.value) {
      const d = new Date(fRev.value + 'T00:00:00');
      d.setDate(d.getDate() + 1);
      fProx.min = d.toLocaleDateString('en-CA');
    }
  }

  tipo.addEventListener('change', ajustar);
  fRev.addEventListener('change', ajustar);
  ajustar();
</script>
</body>
</html>