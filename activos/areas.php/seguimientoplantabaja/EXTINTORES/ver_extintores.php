<?php
/* ==========================================================
   UBICACIÓN: activos/areas.php/seguimientoplantabaja/EXTINTORES/ver_extintores.php
   FICHA DEL EXTINTOR (se abre al escanear el QR en el celular)
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

function kg($v): string
{
    if ($v === null || $v === '') return '—';
    return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.') . ' kg';
}

$bd = (new Conexion())->conectar();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$d  = null;

if ($id) {
    $r = pg_query_params($bd,
        "SELECT e.id_extintor, e.numero_folio, e.tipo_agente, e.capacidad, e.marca, e.modelo,
                e.estado_fisico, e.peso_actual,
                e.fecha_ultimo_mantenimiento, e.fecha_proximo_mantenimiento,
                e.fecha_recarga, e.fecha_proxima_recarga, e.observaciones,
                LEAST(e.fecha_proximo_mantenimiento, e.fecha_proxima_recarga) AS proximo_vencimiento,
                a.nombre_area, a.nivel
         FROM tb_extintores e
         INNER JOIN tb_areas a ON a.id_area = e.id_area
         WHERE e.id_extintor = $1", [$id]);
    $d = $r ? pg_fetch_assoc($r) : null;
}

if (!$d) {
    http_response_code(404);
}

/* ---------- ESTADO (fecha más cercana entre mantenimiento y recarga) ---------- */
$estado = 'Sin fecha';
$clase  = 'bg-secondary';

if ($d) {
    $hoy      = date('Y-m-d');
    $en30Dias = date('Y-m-d', strtotime('+30 days'));
    $fecha    = $d['proximo_vencimiento'] ?? null;

    if (empty($fecha))           { $estado = 'Sin fecha';    $clase = 'bg-secondary'; }
    elseif ($fecha < $hoy)       { $estado = 'Vencido';      $clase = 'bg-danger'; }
    elseif ($fecha <= $en30Dias) { $estado = 'Por vencer';   $clase = 'bg-warning text-dark'; }
    else                         { $estado = 'Al corriente'; $clase = 'bg-success'; }

    $mant = $d['fecha_proximo_mantenimiento'] ?? null;
    $rec  = $d['fecha_proxima_recarga'] ?? null;
    if ($mant && $rec)  $venceDe = ($mant <= $rec) ? 'Mantenimiento' : 'Recarga';
    elseif ($mant)      $venceDe = 'Mantenimiento';
    elseif ($rec)       $venceDe = 'Recarga';
    else                $venceDe = '—';
}

/* ---------- HISTORIAL ---------- */
$historial = [];

if ($d) {
    $rh = pg_query_params($bd,
        "SELECT fecha_revision, fecha_proxima_revision, tipo_revision, resultado, observaciones
         FROM tb_revisiones
         WHERE tipo_activo = 'extintores' AND id_activo = $1
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
    <title><?= $d ? 'Extintor ' . h($d['numero_folio']) : 'Extintor no encontrado' ?></title>
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

        <a href="../extintoresplantabaja.php" class="btn btn-outline-light btn-sm mb-3">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>

        <?php if (!$d): ?>

            <div class="alert alert-danger mt-4">
                No se encontró el extintor solicitado.
            </div>

        <?php else: ?>

            <!-- ENCABEZADO -->
            <div class="card shadow-sm mb-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-fire-extinguisher me-2"></i>Extintor <?= h($d['numero_folio']) ?></h5>
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
                            <p class="dato-etiqueta">Tipo de agente</p>
                            <p class="dato-valor"><?= h($d['tipo_agente'] ?: '—') ?></p>
                        </div>
                        <div class="col-6">
                            <p class="dato-etiqueta">Capacidad</p>
                            <p class="dato-valor"><?= h(kg($d['capacidad'])) ?></p>
                        </div>
                        <div class="col-6">
                            <p class="dato-etiqueta">Marca</p>
                            <p class="dato-valor"><?= h($d['marca'] ?: '—') ?></p>
                        </div>
                        <div class="col-6">
                            <p class="dato-etiqueta">Modelo</p>
                            <p class="dato-valor"><?= h($d['modelo'] ?: '—') ?></p>
                        </div>
                        <div class="col-6">
                            <p class="dato-etiqueta">Estado físico</p>
                            <p class="dato-valor"><?= h($d['estado_fisico'] ?: '—') ?></p>
                        </div>
                        <div class="col-6">
                            <p class="dato-etiqueta">Peso actual</p>
                            <p class="dato-valor"><?= h(kg($d['peso_actual'])) ?></p>
                        </div>
                        <div class="col-6">
                            <p class="dato-etiqueta">Último mantenimiento</p>
                            <p class="dato-valor"><?= h(fmt($d['fecha_ultimo_mantenimiento'])) ?></p>
                        </div>
                        <div class="col-6">
                            <p class="dato-etiqueta">Próximo mantenimiento</p>
                            <p class="dato-valor"><?= h(fmt($d['fecha_proximo_mantenimiento'])) ?></p>
                        </div>
                        <div class="col-6">
                            <p class="dato-etiqueta">Última recarga</p>
                            <p class="dato-valor"><?= h(fmt($d['fecha_recarga'])) ?></p>
                        </div>
                        <div class="col-6">
                            <p class="dato-etiqueta">Próxima recarga</p>
                            <p class="dato-valor"><?= h(fmt($d['fecha_proxima_recarga'])) ?></p>
                        </div>
                        <div class="col-12">
                            <p class="dato-etiqueta">Vence primero</p>
                            <p class="dato-valor"><?= h($venceDe) ?></p>
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
                        Este extintor aún no tiene revisiones registradas.
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