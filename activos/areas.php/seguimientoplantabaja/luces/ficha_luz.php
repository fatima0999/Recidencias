<?php
/* ==========================================================
   FICHA DE LUZ DE EMERGENCIA (PLANTA BAJA)
   Ruta: recidencias/activos/areas.php/seguimientoplantabaja/luces/ficha_luz.php
   Se abre al escanear el QR. Solo lectura.
   ========================================================== */
require_once __DIR__ . '/../../../../clases/conexion.php';

$conexion = new Conexion();
$cn = $conexion->conectar();

$id = (int)($_GET['id'] ?? 0);

/* Solo luces cuya área sea de Planta Baja */
$r = pg_query_params($cn, "SELECT l.*, a.nombre_area
    FROM tb_luces_emergencia l
    INNER JOIN tb_areas a ON a.id_area = l.id_area
    WHERE l.id_luz = $1 AND a.nivel = 'Planta Baja'", [$id]);
$luz = pg_fetch_assoc($r);
if (!$luz) { http_response_code(404); exit('Luz no encontrada en Planta Baja'); }

$h = pg_query_params($cn, "SELECT fecha_revision, tipo_revision, resultado, observaciones
    FROM tb_revisiones
    WHERE tipo_activo = 'luces_emergencia' AND id_activo = $1
    ORDER BY fecha_revision DESC, id_revision DESC", [$id]);

/* Folio: el guardado o, si no tiene, LUZ-001 */
$folio = !empty($luz['numero_folio'])
    ? $luz['numero_folio']
    : 'LUZ-' . str_pad((string)$id, 3, '0', STR_PAD_LEFT);

/* Estado (mismo criterio que el listado: por vencer = 30 días) */
$hoy  = date('Y-m-d');
$prox = $luz['fecha_proxima_revision'];
if (!$prox)           { $estado = 'Sin fecha';    $cls = 'secondary'; }
elseif ($prox < $hoy) { $estado = 'Vencido';      $cls = 'danger'; }
elseif ($prox <= date('Y-m-d', strtotime('+30 days'))) { $estado = 'Por vencer'; $cls = 'warning'; }
else                  { $estado = 'Al corriente'; $cls = 'success'; }

/* Textos bonitos para los valores guardados en tb_revisiones */
$ETIQUETAS = [
    'aprobado'          => 'Aprobado',
    'con_observaciones' => 'Con observaciones',
    'rechazado'         => 'Rechazado',
    'preventiva'        => 'Preventiva',
    'correctiva'        => 'Correctiva',
    'recarga'           => 'Recarga',
    'inspeccion'        => 'Inspección',
];

function e($v) {
    $v = (string)($v ?? '');
    return $v === '' ? '—' : htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function etq($v) {
    global $ETIQUETAS;
    return e($ETIQUETAS[$v] ?? $v);
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($folio) ?> · Luz de emergencia</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background:#0b0d13; color:#fff; }
  .card-s { background:#10131c; border:1px solid #1f2430; border-radius:14px; }
  .lbl { color:#8b93a7; font-size:.8rem; margin-bottom:0; }
  .val { font-weight:700; margin-bottom:.7rem; }
  .btn-sirev { background:#d6143a; border-color:#d6143a; color:#fff; }
  .btn-sirev:hover { background:#b81031; color:#fff; }
  .rev { padding:14px 16px; border-bottom:1px solid #1f2430; }
  .rev:last-child { border-bottom:0; }
</style>
</head>
<body>
<div class="container py-3" style="max-width:600px">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h5 class="mb-0"><?= e($folio) ?></h5>
      <small class="text-secondary">Luz de emergencia · Planta Baja</small>
    </div>
    <span class="badge text-bg-<?= $cls ?> fs-6"><?= $estado ?></span>
  </div>

  <div class="card-s p-3 mb-3">
    <div class="row">
      <div class="col-6"><p class="lbl">Área</p><p class="val"><?= e($luz['nombre_area']) ?></p></div>
      <div class="col-6"><p class="lbl">Tipo</p><p class="val"><?= e($luz['tipo_iluminacion']) ?></p></div>
      <div class="col-12"><p class="lbl">Ubicación</p><p class="val"><?= e($luz['ubicacion_detalle'] ?? '') ?></p></div>
      <div class="col-6"><p class="lbl">Marca / Modelo</p><p class="val"><?= e($luz['marca']) ?> / <?= e($luz['modelo']) ?></p></div>
      <div class="col-6"><p class="lbl">Duración de batería</p><p class="val"><?= e($luz['duracion_bateria']) ?></p></div>
      <div class="col-6"><p class="lbl">Estado físico</p><p class="val"><?= e($luz['estado_fisico']) ?></p></div>
      <div class="col-6"><p class="lbl">Fecha de instalación</p><p class="val"><?= e($luz['fecha_instalacion']) ?></p></div>
      <div class="col-6"><p class="lbl">Última revisión</p><p class="val"><?= e($luz['fecha_ultima_revision']) ?></p></div>
      <div class="col-6"><p class="lbl">Próxima revisión</p><p class="val"><?= e($luz['fecha_proxima_revision']) ?></p></div>
      <div class="col-12"><p class="lbl">Observaciones</p><p class="val mb-0"><?= e($luz['observaciones']) ?></p></div>
    </div>
  </div>

  <h6 class="mb-2">Historial</h6>
  <div class="card-s mb-3">
  <?php if (pg_num_rows($h) === 0): ?>
    <p class="p-3 mb-0 text-secondary">Sin revisiones registradas</p>
  <?php else: while ($f = pg_fetch_assoc($h)): ?>
    <div class="rev">
      <b><?= e($f['fecha_revision']) ?></b> · <?= etq($f['tipo_revision']) ?> · <?= etq($f['resultado']) ?>
      <?php if (!empty($f['observaciones'])): ?>
        <div class="small text-secondary"><?= e($f['observaciones']) ?></div>
      <?php endif; ?>
    </div>
  <?php endwhile; endif; ?>
  </div>

  <div class="d-flex gap-2 mb-4">
    <a href="actualizar_luz.php?id=<?= $id ?>" class="btn btn-sirev flex-fill">Actualizar</a>
    <a href="../lucesdeemergencia.php" class="btn btn-outline-light">Regresar</a>
  </div>

</div>
</body>
</html>