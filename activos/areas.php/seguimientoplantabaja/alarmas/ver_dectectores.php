<?php
/* ==========================================================
   UBICACIÓN: activos/areas.php/seguimientoplantabaja/sensores/ver_detectores.php
   FICHA DEL DETECTOR DE HUMO - PLANTA BAJA (se abre al escanear el QR en el celular)
   Muestra datos + historial de revisiones. Solo lectura.
   ========================================================== */

require_once __DIR__ . '/../../../../clases/conexion.php';

function h($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function fmt($f): string
{
    return !empty($f) ? date('d/m/Y', strtotime($f)) : 'No registrada';
}

$bd = (new Conexion())->conectar();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$d  = null;

if ($id) {
    $r = pg_query_params($bd,
        "SELECT d.id_detector, d.tipo_detector, d.marca, d.modelo, d.numero_serie,
                d.estado_fisico, d.fecha_instalacion, d.fecha_ultima_revision,
                d.fecha_proxima_revision, d.observaciones,
                a.nombre_area, a.nivel
         FROM tb_detectores_humo d
         INNER JOIN tb_areas a ON a.id_area = d.id_area
         WHERE d.id_detector = $1
           AND a.nivel = 'Planta Baja'", [$id]);
    $d = $r ? pg_fetch_assoc($r) : null;
}

if (!$d) {
    http_response_code(404);
}

/* ---------- ESTADO ---------- */
$estado = 'Sin fecha';
$clase  = 'bg-secondary';

if ($d) {
    $hoy      = date('Y-m-d');
    $en30Dias = date('Y-m-d', strtotime('+30 days'));
    $fecha    = $d['fecha_proxima_revision'] ?? null;

    if (empty($fecha))           { $estado = 'Sin fecha';    $clase = 'bg-secondary'; }
    elseif ($fecha < $hoy)       { $estado = 'Vencido';      $clase = 'bg-danger'; }
    elseif ($fecha <= $en30Dias) { $estado = 'Por vencer';   $clase = 'bg-warning text-dark'; }
    else                         { $estado = 'Al corriente'; $clase = 'bg-success'; }
}

/* ---------- HISTORIAL ---------- */
$historial = [];

if ($d) {
    $rh = pg_query_params($bd,
        "SELECT fecha_revision, fecha_proxima_revision, tipo_revision, resultado,
                observaciones
         FROM tb_revisiones
         WHERE tipo_activo = 'detectores_humo' AND id_activo = $1
         ORDER BY fecha_revision DESC, id_revision DESC", [$id]);

    if ($rh) {
        while ($fila = pg_fetch_assoc($rh)) {
            $historial[] = $fila;
        }
    }
}

$etiquetaResultado = [
    'aprobado'          => ['Aprobado', 'bg-success'],
    'con_observaciones' => ['Con observaciones', 'bg-warning text-dark'],
    'rechazado'         => ['Rechazado', 'bg-danger'],
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $d ? 'Detector #' . (int) $d['id_detector'] : 'Detector no encontrado' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background: #070b14; color: #e5e7eb; }
        .card { background: #0d1220; border: 1px solid #1c2436; color: #e5e7eb; border-radius: 16px; }
        .card-header, .card-header.bg-white { background: transparent !important; border-bottom: 1px solid #1c2436; color: #e5e7eb; }
        .list-group-item { background: transparent; color: #e5e7eb; border-color: #1c2436; }
        .text-muted { color: #9ca3af !important; }
        .dato-etiqueta { color: #9ca3af; font-size: .8rem; margin-bottom: 0; }
        .dato-valor { font-weight: 500; margin-bottom: .75rem; }
    </style>
</head>

<body>

    <div class="container py-3" style="max-width: 640px;">

        <a href="../sensoresdehumo.php" class="btn btn-outline-light btn-sm mb-3">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>

        <?php if (!$d): ?>

            <div class="alert alert-danger mt-4">
                No se encontró el detector solicitado en Planta Baja.
            </div>

        <?php else: ?>

            <!-- ENCABEZADO -->
            <div class="card shadow-sm mb-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Detector #<?= (int) $d['id_detector'] ?></h5>
                        <small class="text-muted"><?= h($d['nombre_area']) ?> · <?= h($d['nivel']) ?></small>
                    </div>
                    <span class="badge <?= $clase ?> fs-6"><?= h($estado) ?></span>
                </div>
            </div>

            <!-- DATOS -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-info-circle me-2 text-primary"></i>Información</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <p class="dato-etiqueta">Tipo</p>
                            <p class="dato-valor"><?= h($d['tipo_detector'] ?: '—') ?></p>
                        </div>
                        <div class="col-6">
                            <p class="dato-etiqueta">Estado físico</p>
                            <p class="dato-valor"><?= h($d['estado_fisico'] ?: '—') ?></p>
                        </div>
                        <div class="col-6">
                            <p class="dato-etiqueta">Marca</p>
                            <p class="dato-valor"><?= h($d['marca'] ?: '—') ?></p>
                        </div>
                        <div class="col-6">
                            <p class="dato-etiqueta">Modelo</p>
                            <p class="dato-valor"><?= h($d['modelo'] ?: '—') ?></p>
                        </div>
                        <div class="col-12">
                            <p class="dato-etiqueta">Número de serie</p>
                            <p class="dato-valor"><?= h($d['numero_serie'] ?: '—') ?></p>
                        </div>
                        <div class="col-4">
                            <p class="dato-etiqueta">Instalación</p>
                            <p class="dato-valor"><?= h(fmt($d['fecha_instalacion'])) ?></p>
                        </div>
                        <div class="col-4">
                            <p class="dato-etiqueta">Última revisión</p>
                            <p class="dato-valor"><?= h(fmt($d['fecha_ultima_revision'])) ?></p>
                        </div>
                        <div class="col-4">
                            <p class="dato-etiqueta">Próxima revisión</p>
                            <p class="dato-valor"><?= h(fmt($d['fecha_proxima_revision'])) ?></p>
                        </div>
                        <?php if (!empty($d['observaciones'])): ?>
                            <div class="col-12">
                                <p class="dato-etiqueta">Observaciones</p>
                                <p class="dato-valor"><?= h($d['observaciones']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- HISTORIAL -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-history me-2 text-primary"></i>Historial de revisiones</strong>
                </div>

                <?php if (empty($historial)): ?>
                    <div class="card-body text-center text-muted">
                        Este detector aún no tiene revisiones registradas.
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($historial as $h): ?>
                            <?php [$txtRes, $claseRes] = $etiquetaResultado[$h['resultado']] ?? [$h['resultado'], 'bg-secondary']; ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong><?= h(fmt($h['fecha_revision'])) ?></strong>
                                    <span class="badge <?= $claseRes ?>"><?= h($txtRes) ?></span>
                                </div>
                                <small class="text-muted d-block">
                                    <?= h(ucfirst($h['tipo_revision'])) ?> · Próxima: <?= h(fmt($h['fecha_proxima_revision'])) ?>
                                </small>
                                <?php if (!empty($h['observaciones'])): ?>
                                    <small class="d-block"><?= h($h['observaciones']) ?></small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </div>

</body>

</html>