<?php
/* ==========================================================
   UBICACIÓN: activos/areas.php/seguimientoplantabaja/hidratantes.php
   LISTADO DE HIDRANTES - PLANTA BAJA
   Mismo diseño que luces de emergencia (tema oscuro, filas con folio)
   ========================================================== */

require_once __DIR__ . '/../../../clases/conexion.php';

/* IP DE LA COMPUTADORA CON XAMPP (cámbiala aquí si cambia) */
$IP_SERVIDOR = '192.168.1.68';

/* Detecta la carpeta y los archivos del módulo aunque cambie la ortografía
   (hidrantes / hidratantes, alta_hidrante / alta_hidratante, mayúsculas, etc.) */
$CARPETA = 'hidratantes';
foreach (['hidratantes', 'hidrantes'] as $posible) {
    if (is_dir(__DIR__ . '/' . $posible)) { $CARPETA = $posible; break; }
}

/* Busca por prefijo (sin importar mayúsculas) cualquier archivo de la carpeta,
   incluso si le falta la extensión .php */
function archivoModulo(string $carpeta, string $prefijo, string $porDefecto): string
{
    foreach (glob(__DIR__ . '/' . $carpeta . '/*') ?: [] as $f) {
        $n = basename($f);
        if (is_file($f) && stripos($n, $prefijo) === 0) return $carpeta . '/' . $n;
    }
    return $carpeta . '/' . $porDefecto;
}

$URL_ALTA       = archivoModulo($CARPETA, 'alta_',       'alta_hidratante.php');
$URL_ACTUALIZAR = archivoModulo($CARPETA, 'actualizar_', 'actualizar_hidratante.php');
$URL_ELIMINAR   = archivoModulo($CARPETA, 'eliminar_',   'eliminar_hidratante.php');
$URL_VER        = archivoModulo($CARPETA, 'ver_',        'ver_hidratante.php');

$URL_FICHA = "http://{$IP_SERVIDOR}/recidencias/activos/areas.php/seguimientoplantabaja/" . $URL_VER . "?id=";

$conexion = (new Conexion())->conectar();

/* ---------- HELPERS ---------- */
function h($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function fechaCorta($f): string
{
    if (empty($f)) return 'No registrada';
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $t = strtotime($f);
    return date('j', $t) . ' ' . $meses[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
}

function folio($id, $numero = null): string
{
    return !empty($numero) ? (string) $numero : 'HID-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
}

/* ==========================================================
   MÉTRICAS
   ========================================================== */

$sqlMetricas = "
    SELECT
        COUNT(*) AS total,
        COUNT(*) FILTER (WHERE d.fecha_proxima_revision > CURRENT_DATE + INTERVAL '30 days') AS al_corriente,
        COUNT(*) FILTER (WHERE d.fecha_proxima_revision >= CURRENT_DATE
                           AND d.fecha_proxima_revision <= CURRENT_DATE + INTERVAL '30 days') AS por_vencer,
        COUNT(*) FILTER (WHERE d.fecha_proxima_revision < CURRENT_DATE) AS vencidos
    FROM tb_hidrantes d
    INNER JOIN tb_areas a ON a.id_area = d.id_area
    WHERE a.nivel = 'Planta Baja'
";

$resMetricas = pg_query($conexion, $sqlMetricas);
$metricas    = $resMetricas ? pg_fetch_assoc($resMetricas) : [];

$total        = (int) ($metricas['total'] ?? 0);
$al_corriente = (int) ($metricas['al_corriente'] ?? 0);
$por_vencer   = (int) ($metricas['por_vencer'] ?? 0);
$vencidos     = (int) ($metricas['vencidos'] ?? 0);
$cumplimiento = $total > 0 ? round(($al_corriente / $total) * 100) : 0;

/* ==========================================================
   HIDRANTES DE PLANTA BAJA (los más urgentes primero)
   ========================================================== */

$sqlDetectores = "
    SELECT
        d.id_hidrante, d.numero_folio, d.tipo_hidrante, d.marca, d.modelo,
        d.estado_fisico, d.fecha_instalacion, d.fecha_ultima_revision,
        d.fecha_proxima_revision, d.observaciones, a.nombre_area
    FROM tb_hidrantes d
    INNER JOIN tb_areas a ON a.id_area = d.id_area
    WHERE a.nivel = 'Planta Baja'
    ORDER BY d.fecha_proxima_revision ASC NULLS LAST, d.id_hidrante ASC
";

$resDetectores = pg_query($conexion, $sqlDetectores);
$errorBD = $resDetectores ? null : pg_last_error($conexion);

$hoy      = date('Y-m-d');
$en30Dias = date('Y-m-d', strtotime('+30 days'));

$lista   = [];
$datosJS = [];
$areas   = [];

if ($resDetectores) {
    while ($d = pg_fetch_assoc($resDetectores)) {

        $fecha = $d['fecha_proxima_revision'] ?? null;

        if (empty($fecha))           $d['estado_seguimiento'] = 'Sin fecha';
        elseif ($fecha < $hoy)       $d['estado_seguimiento'] = 'Vencido';
        elseif ($fecha <= $en30Dias) $d['estado_seguimiento'] = 'Por vencer';
        else                         $d['estado_seguimiento'] = 'Al corriente';

        $lista[] = $d;
        $areas[$d['nombre_area']] = true;

        $datosJS[$d['id_hidrante']] = [
            'id'          => $d['id_hidrante'],
            'folio'       => folio($d['id_hidrante'], $d['numero_folio']),
            'area'        => $d['nombre_area'],
            'tipo'        => $d['tipo_hidrante'],
            'marca'       => $d['marca'],
            'modelo'      => $d['modelo'],
            'estadoFis'   => $d['estado_fisico'],
            'instalacion' => fechaCorta($d['fecha_instalacion']),
            'ultima'      => fechaCorta($d['fecha_ultima_revision']),
            'proxima'     => fechaCorta($fecha),
            'estado'      => $d['estado_seguimiento'],
            'observ'      => $d['observaciones'],
            'historial'   => [],
        ];
    }
}

ksort($areas);

/* ==========================================================
   HISTORIAL (tb_revisiones)
   ========================================================== */

$sqlHist = "
    SELECT r.id_activo, r.fecha_revision, r.fecha_proxima_revision,
           r.tipo_revision, r.resultado, r.observaciones
    FROM tb_revisiones r
    INNER JOIN tb_hidrantes d ON d.id_hidrante = r.id_activo
    INNER JOIN tb_areas a ON a.id_area = d.id_area
    WHERE r.tipo_activo = 'hidrantes'
      AND a.nivel = 'Planta Baja'
    ORDER BY r.fecha_revision DESC, r.id_revision DESC
";

$resHist = pg_query($conexion, $sqlHist);

if ($resHist) {
    while ($r = pg_fetch_assoc($resHist)) {
        $id = $r['id_activo'];
        if (isset($datosJS[$id])) {
            $datosJS[$id]['historial'][] = [
                'fecha'       => fechaCorta($r['fecha_revision']),
                'proxima'     => fechaCorta($r['fecha_proxima_revision']),
                'tipo'        => $r['tipo_revision'],
                'resultado'   => $r['resultado'],
                'observ'      => $r['observaciones'],
            ];
        }
    }
}

$ok = $_GET['ok'] ?? '';
$mensajesOk = [
    '1' => 'Hidrante guardado correctamente.',
    '2' => 'Revisión registrada correctamente.',
    '3' => 'Hidrante eliminado correctamente.',
];

function claseEstado(string $e): string
{
    return [
        'Al corriente' => 'estado-corriente',
        'Por vencer'   => 'estado-porvencer',
        'Vencido'      => 'estado-vencido',
    ][$e] ?? 'estado-sinfecha';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hidrantes - Planta Baja</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        :root {
            --bg: #070b14;
            --panel: #0d1220;
            --borde: #1c2436;
            --texto: #e5e7eb;
            --suave: #9ca3af;
            --verde: #34d399;
            --ambar: #fbbf24;
            --rojo: #fb7185;
            --acento: #e11d48;
        }

        body { background: var(--bg); color: var(--texto); }

        /* ---------- BARRA SUPERIOR ---------- */
        .barra-superior {
            padding: 16px 28px;
            border-bottom: 1px solid var(--borde);
            font-size: 1.35rem;
            font-weight: 700;
        }

        .contenido { padding: 18px 28px 40px; }

        .enlace-volver {
            color: var(--suave);
            text-decoration: none;
            font-size: 1rem;
        }
        .enlace-volver:hover { color: var(--texto); }

        /* ---------- ENCABEZADO + CONTROLES ---------- */
        .titulo-pagina { font-size: 1.7rem; font-weight: 700; margin: 0; white-space: nowrap; }
        .titulo-pagina i { color: var(--ambar); margin-right: 10px; }

        .form-control, .form-select {
            background-color: var(--panel);
            border: 1px solid var(--borde);
            color: var(--texto);
            height: 46px;
            border-radius: 10px;
            font-size: .95rem;
        }
        .form-control::placeholder { color: var(--suave); }
        .form-control:focus, .form-select:focus {
            background-color: var(--panel);
            color: var(--texto);
            border-color: var(--acento);
            box-shadow: 0 0 0 .2rem rgba(225, 29, 72, .2);
        }
        .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        }
        .form-select option { background: var(--panel); color: var(--texto); }

        .btn-agregar {
            background: var(--acento);
            color: #fff;
            font-weight: 700;
            height: 46px;
            padding: 0 22px;
            border-radius: 10px;
            font-size: .95rem;
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            border: 0;
        }
        .btn-agregar:hover { background: #be123c; color: #fff; }

        /* ---------- TARJETAS ---------- */
        .tarjeta-metrica {
            background: var(--panel);
            border: 1px solid var(--borde);
            border-top: 2px solid var(--borde);
            border-radius: 18px;
            padding: 18px 24px;
            height: 100%;
        }
        .tarjeta-metrica small { color: var(--suave); font-size: .95rem; }
        .tarjeta-metrica h3 { font-size: 2.2rem; font-weight: 700; margin: 4px 0 0; }
        .tm-verde  { border-top-color: var(--verde); }
        .tm-ambar  { border-top-color: var(--ambar); }
        .tm-rojo   { border-top-color: var(--rojo); }
        .tm-verde h3.coloreado { color: var(--verde); }
        .tm-ambar h3 { color: var(--ambar); }
        .tm-rojo h3  { color: var(--rojo); }

        /* ---------- LISTA ---------- */
        .panel-lista {
            background: var(--panel);
            border: 1px solid var(--borde);
            border-radius: 20px;
            padding: 4px 32px;
        }

        .fila-activo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            padding: 20px 0;
            border-bottom: 1px solid var(--borde);
            cursor: pointer;
        }
        .fila-activo:last-child { border-bottom: 0; }
        .fila-activo:hover .fila-titulo { color: #fff; text-decoration: underline; }

        .icono-activo {
            width: 50px; height: 50px;
            border-radius: 13px;
            background: #111827;
            display: flex; align-items: center; justify-content: center;
            color: var(--ambar);
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .fila-titulo { font-size: 1.25rem; font-weight: 700; }
        .fila-sub { color: var(--suave); font-size: .95rem; }
        .fila-derecha { text-align: right; }
        .fila-fecha { color: var(--suave); font-size: .95rem; margin-bottom: 6px; }

        .estado-pill {
            display: inline-block;
            padding: 9px 14px;
            border-radius: 10px;
            font-weight: 700;
            font-size: .95rem;
        }
        .estado-corriente { background: rgba(52, 211, 153, .15); color: var(--verde); }
        .estado-porvencer { background: rgba(251, 191, 36, .15); color: var(--ambar); }
        .estado-vencido   { background: rgba(251, 113, 133, .15); color: var(--rojo); }
        .estado-sinfecha  { background: rgba(156, 163, 175, .15); color: var(--suave); }

        .btn-eliminar {
            background: rgba(251, 113, 133, .08);
            border: 1px solid rgba(251, 113, 133, .5);
            color: var(--rojo);
            font-weight: 700;
            font-size: .95rem;
            padding: 8px 14px;
            border-radius: 10px;
        }
        .btn-eliminar:hover { background: rgba(251, 113, 133, .2); color: var(--rojo); }

        .sin-resultados { text-align: center; color: var(--suave); padding: 36px 0; font-size: 1rem; }

        /* ---------- MODAL ---------- */
        .modal-content { background: var(--panel); color: var(--texto); border: 1px solid var(--borde); border-radius: 20px; }
        .modal-header, .modal-footer { border-color: var(--borde); }
        .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
        .nav-tabs { border-bottom-color: var(--borde); }
        .nav-tabs .nav-link { color: var(--suave); background: transparent; border: 0; border-bottom: 2px solid transparent; }
        .nav-tabs .nav-link:hover { color: var(--texto); }
        .nav-tabs .nav-link.active { color: #fff; background: transparent; border-bottom-color: var(--acento); }

        .dato-etiqueta { color: var(--suave); font-size: .8rem; margin-bottom: 0; }
        .dato-valor { font-weight: 500; margin-bottom: .75rem; }

        .caja-qr { background: #fff; display: inline-block; padding: 16px; border-radius: 12px; }
        #contenedorQR img { max-width: 250px; height: auto; }
        .folio-qr { font-weight: bold; font-size: 18px; margin-bottom: 15px; }

        .item-historial { border-bottom: 1px solid var(--borde); padding: 14px 0; }
        .item-historial:last-child { border-bottom: 0; }

        .btn-actualizar { background: var(--acento); color: #fff; font-weight: 700; border: 0; }
        .btn-actualizar:hover { background: #be123c; color: #fff; }

        .btn-cerrar-modal {
            background: transparent;
            border: 1px solid var(--borde);
            color: var(--texto);
            width: 52px; height: 52px;
            border-radius: 12px;
        }
        .btn-cerrar-modal:hover { background: rgba(255, 255, 255, .06); }

        .btn-cancelar {
            background: var(--panel);
            border: 1px solid var(--borde);
            color: var(--texto);
            font-weight: 600;
            padding: 14px 22px;
            border-radius: 12px;
        }
        .btn-cancelar:hover { background: #111827; color: #fff; }

        .btn-confirmar-eliminar {
            background: var(--acento);
            border: 0;
            color: #fff;
            font-weight: 700;
            padding: 14px 24px;
            border-radius: 12px;
        }
        .btn-confirmar-eliminar:hover { background: #be123c; color: #fff; }

        .alert-ok { background: rgba(52, 211, 153, .12); border: 1px solid rgba(52, 211, 153, .4); color: var(--verde); border-radius: 12px; }

        @media (max-width: 768px) {
            .barra-superior, .contenido { padding-left: 16px; padding-right: 16px; }
            .panel-lista { padding: 4px 18px; }
            .titulo-pagina { font-size: 1.4rem; }
            .fila-derecha { text-align: left; width: 100%; }
            .fila-titulo { font-size: 1.3rem; }
        }
    </style>
</head>

<body>

    <div class="barra-superior">Activos</div>

    <div class="contenido">

        <a href="../../areas.php/plantaBaja.php" class="enlace-volver">
            <i class="fas fa-arrow-left me-2"></i>Planta Baja
        </a>

        <!-- TÍTULO + CONTROLES -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3 mb-4">

            <h1 class="titulo-pagina"><i class="fas fa-tint"></i>Hidrantes</h1>

            <div class="d-flex flex-wrap gap-3 align-items-center">
                <input type="text" id="buscador" class="form-control" style="width: 280px;"
                    placeholder="Buscar folio, marca, modelo">

                <select id="filtroEstado" class="form-select" style="width: 170px;">
                    <option value="">Estado</option>
                    <option value="Al corriente">Al corriente</option>
                    <option value="Por vencer">Por vencer</option>
                    <option value="Vencido">Vencido</option>
                    <option value="Sin fecha">Sin fecha</option>
                </select>

                <select id="filtroArea" class="form-select" style="width: 270px;">
                    <option value="">Área</option>
                    <?php foreach (array_keys($areas) as $nombreArea): ?>
                        <option value="<?= h($nombreArea) ?>"><?= h($nombreArea) ?></option>
                    <?php endforeach; ?>
                </select>

                <a href="<?= h($URL_ALTA) ?>" class="btn-agregar">+ Agregar</a>
            </div>
        </div>

        <!-- AVISOS -->
        <?php if (isset($mensajesOk[$ok])): ?>
            <div class="alert alert-ok alert-dismissible fade show" role="alert">
                <?= h($mensajesOk[$ok]) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <?php if (($_GET['err'] ?? '') === '1'): ?>
            <div class="alert alert-danger">No se pudo eliminar el hidrante (no existe o no pertenece a Planta Baja).</div>
        <?php endif; ?>

        <?php if ($errorBD): ?>
            <div class="alert alert-danger">Error en la consulta: <?= h($errorBD) ?></div>
        <?php endif; ?>

        <!-- MÉTRICAS -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="tarjeta-metrica tm-verde">
                    <small>Cumplimiento</small>
                    <h3 class="coloreado"><?= $cumplimiento ?>%</h3>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="tarjeta-metrica tm-verde">
                    <small>Al corriente</small>
                    <h3><?= $al_corriente ?></h3>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="tarjeta-metrica tm-ambar">
                    <small>Por vencer</small>
                    <h3><?= $por_vencer ?></h3>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="tarjeta-metrica tm-rojo">
                    <small>Vencidos</small>
                    <h3><?= $vencidos ?></h3>
                </div>
            </div>
        </div>

        <!-- LISTA -->
        <div class="panel-lista" id="panelLista">

            <?php foreach ($lista as $d): ?>
                <?php
                $id = (int) $d['id_hidrante'];
                $textoBusqueda = strtolower(
                    folio($id, $d['numero_folio']) . ' ' . $id . ' ' . $d['marca'] . ' ' . $d['modelo'] . ' ' .
                    $d['nombre_area'] . ' ' . $d['tipo_hidrante']
                );
                ?>
                <div class="fila-activo"
                    data-estado="<?= h($d['estado_seguimiento']) ?>"
                    data-area="<?= h($d['nombre_area']) ?>"
                    data-texto="<?= h($textoBusqueda) ?>"
                    onclick="abrirDetector(<?= $id ?>, 'info')">

                    <div class="d-flex align-items-center gap-3">
                        <div class="icono-activo"><i class="fas fa-tint"></i></div>
                        <div>
                            <div class="fila-titulo">Hidrante <?= h($d['tipo_hidrante']) ?></div>
                            <div class="fila-sub">
                                <?= h(implode(' · ', array_filter([folio($id, $d['numero_folio']), $d['nombre_area'], trim(($d['marca'] ?? '') . ' ' . ($d['modelo'] ?? ''))]))) ?>
                            </div>
                        </div>
                    </div>

                    <div class="fila-derecha">
                        <div class="fila-fecha">Próx. revisión: <?= h(fechaCorta($d['fecha_proxima_revision'])) ?></div>
                        <div class="d-flex gap-2 justify-content-md-end">
                            <span class="estado-pill <?= claseEstado($d['estado_seguimiento']) ?>">
                                <?= h($d['estado_seguimiento']) ?>
                            </span>

                            <button type="button" class="btn-eliminar"
                                onclick="event.stopPropagation(); pedirEliminar(<?= $id ?>)">
                                <i class="fas fa-trash me-1"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="sin-resultados" id="sinResultados" style="<?= empty($lista) ? '' : 'display:none;' ?>">
                <?= empty($lista) ? 'No hay hidrantes registrados en Planta Baja.' : 'No hay hidrantes que coincidan con la búsqueda.' ?>
            </div>

        </div>
    </div>

    <!-- =====================================================
         MODAL DEL HIDRANTE (Info / QR / Historial)
         ===================================================== -->
    <div class="modal fade" id="modalDetector" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-tint me-2" style="color: var(--ambar);"></i>
                        <span id="tituloModal">Hidrante</span>
                        <span id="badgeModal" class="estado-pill ms-2" style="padding: 4px 12px; font-size: .85rem;"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">

                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-info" data-bs-toggle="tab"
                                data-bs-target="#pane-info" type="button" role="tab">
                                <i class="fas fa-info-circle me-1"></i> Info
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-qr" data-bs-toggle="tab"
                                data-bs-target="#pane-qr" type="button" role="tab">
                                <i class="fas fa-qrcode me-1"></i> QR
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-historial" data-bs-toggle="tab"
                                data-bs-target="#pane-historial" type="button" role="tab">
                                <i class="fas fa-history me-1"></i> Historial
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">

                        <div class="tab-pane fade show active" id="pane-info" role="tabpanel">
                            <div class="row" id="contenidoInfo"></div>
                        </div>

                        <div class="tab-pane fade text-center" id="pane-qr" role="tabpanel">
                            <div id="folioQR" class="folio-qr"></div>
                            <div class="caja-qr"><div id="contenedorQR"></div></div>
                            <p class="mt-3 mb-0" style="color: var(--suave);">
                                Escanea este código para consultar la ficha y el historial del hidrante.
                            </p>
                        </div>

                        <div class="tab-pane fade" id="pane-historial" role="tabpanel">
                            <div id="contenidoHistorial"></div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <a id="btnActualizar" href="#" class="btn btn-actualizar">
                        <i class="fas fa-sync-alt me-1"></i> Actualizar
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>

            </div>
        </div>
    </div>

    <!-- =====================================================
         MODAL DE CONFIRMACIÓN PARA ELIMINAR
         ===================================================== -->
    <div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <form method="POST" action="<?= h($URL_ELIMINAR) ?>">
                    <input type="hidden" name="id_hidrante" id="idEliminar" value="">

                    <div class="modal-header border-0 pb-0 align-items-start">
                        <h4 class="modal-title d-flex align-items-center gap-3 mt-2">
                            <i class="fas fa-exclamation-triangle" style="color: var(--rojo);"></i>
                            Eliminar hidrante
                        </h4>
                        <button type="button" class="btn-cerrar-modal" data-bs-dismiss="modal" aria-label="Cerrar">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <p class="fs-5 mb-2">¿Seguro que quieres eliminar <strong id="folioEliminar"></strong>?</p>
                        <p class="mb-0" style="color: var(--suave);">
                            Se eliminará también su historial de revisiones. Esta acción no se puede deshacer.
                        </p>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn-cancelar" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-confirmar-eliminar">
                            <i class="fas fa-trash me-2"></i>Sí, eliminar
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        /* ---------- DATOS DESDE PHP ---------- */
        const DETECTORES = <?= json_encode($datosJS, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const URL_FICHA  = <?= json_encode($URL_FICHA) ?>;
        const URL_ACTUALIZAR = <?= json_encode($URL_ACTUALIZAR) ?>;

        /* ---------- FILTROS ---------- */
        const buscador     = document.getElementById('buscador');
        const filtroEstado = document.getElementById('filtroEstado');
        const filtroArea   = document.getElementById('filtroArea');
        const filas        = document.querySelectorAll('.fila-activo');
        const sinResultados = document.getElementById('sinResultados');
        const hayDatos     = filas.length > 0;

        function filtrar() {
            const texto  = buscador.value.toLowerCase().trim();
            const estado = filtroEstado.value;
            const area   = filtroArea.value;
            let visibles = 0;

            filas.forEach(function (fila) {
                const coincide =
                    (texto === '' || (fila.dataset.texto || '').includes(texto)) &&
                    (estado === '' || fila.dataset.estado === estado) &&
                    (area === '' || fila.dataset.area === area);

                fila.style.display = coincide ? '' : 'none';
                if (coincide) visibles++;
            });

            sinResultados.style.display = (hayDatos && visibles === 0) ? '' : 'none';
            if (hayDatos) sinResultados.textContent = 'No hay hidrantes que coincidan con la búsqueda.';
        }

        buscador.addEventListener('input', filtrar);
        filtroEstado.addEventListener('change', filtrar);
        filtroArea.addEventListener('change', filtrar);

        /* ---------- HELPERS ---------- */
        function esc(s) {
            const d = document.createElement('div');
            d.textContent = (s === null || s === undefined || s === '') ? '—' : s;
            return d.innerHTML;
        }

        function claseEstado(e) {
            if (e === 'Al corriente') return 'estado-corriente';
            if (e === 'Por vencer')   return 'estado-porvencer';
            if (e === 'Vencido')      return 'estado-vencido';
            return 'estado-sinfecha';
        }

        function claseResultado(r) {
            if (r === 'aprobado')          return 'estado-corriente';
            if (r === 'con_observaciones') return 'estado-porvencer';
            if (r === 'rechazado')         return 'estado-vencido';
            return 'estado-sinfecha';
        }

        function textoResultado(r) {
            return ({ aprobado: 'Aprobado', con_observaciones: 'Con observaciones', rechazado: 'Rechazado' })[r] || r;
        }

        function textoTipo(t) {
            return t ? t.charAt(0).toUpperCase() + t.slice(1) : '—';
        }

        function dato(etiqueta, valor, col) {
            return '<div class="' + (col || 'col-md-6') + '">' +
                   '<p class="dato-etiqueta">' + etiqueta + '</p>' +
                   '<p class="dato-valor">' + esc(valor) + '</p></div>';
        }

        /* ---------- ABRIR MODAL ---------- */
        let modalDetector = null;

        function abrirDetector(id, pestana) {
            const d = DETECTORES[id];
            if (!d) return;

            document.getElementById('tituloModal').textContent = d.folio;

            const badge = document.getElementById('badgeModal');
            badge.className = 'estado-pill ms-2 ' + claseEstado(d.estado);
            badge.textContent = d.estado;

            /* INFO */
            document.getElementById('contenidoInfo').innerHTML =
                dato('Área', d.area) +
                dato('Tipo de hidrante', d.tipo) +
                dato('Marca', d.marca) +
                dato('Modelo', d.modelo) +
                dato('Estado físico', d.estadoFis) +
                dato('Fecha de instalación', d.instalacion) +
                dato('Última revisión', d.ultima) +
                dato('Próxima revisión', d.proxima) +
                dato('Observaciones', d.observ, 'col-12');

            /* QR */
            const contQR = document.getElementById('contenedorQR');
            contQR.innerHTML = '';
            document.getElementById('folioQR').textContent = 'Hidrante: ' + d.folio;
            new QRCode(contQR, { text: URL_FICHA + d.id, width: 250, height: 250 });

            /* HISTORIAL */
            let html = '';
            if (d.historial.length === 0) {
                html = '<p class="text-center py-3 mb-0" style="color: var(--suave);">Este hidrante aún no tiene revisiones registradas.</p>';
            } else {
                d.historial.forEach(function (h) {
                    html += '<div class="item-historial">' +
                        '<div class="d-flex justify-content-between align-items-center">' +
                        '<strong>' + esc(h.fecha) + '</strong>' +
                        '<span class="estado-pill ' + claseResultado(h.resultado) + '" style="padding:4px 12px;font-size:.85rem;">' +
                        esc(textoResultado(h.resultado)) + '</span></div>' +
                        '<small style="color: var(--suave);" class="d-block">' + esc(textoTipo(h.tipo)) +
                        ' · Próxima: ' + esc(h.proxima) + '</small>' +
                        (h.observ ? '<small class="d-block">' + esc(h.observ) + '</small>' : '') +
                        '</div>';
                });
            }
            document.getElementById('contenidoHistorial').innerHTML = html;

            /* BOTÓN ACTUALIZAR */
            document.getElementById('btnActualizar').href =
                URL_ACTUALIZAR + '?id=' + encodeURIComponent(d.id);

            /* ABRIR */
            if (!modalDetector) {
                modalDetector = new bootstrap.Modal(document.getElementById('modalDetector'));
            }
            modalDetector.show();
            new bootstrap.Tab(document.getElementById('tab-' + (pestana || 'info'))).show();
        }

        /* ---------- CONFIRMAR ELIMINACIÓN ---------- */
        let modalEliminar = null;

        function pedirEliminar(id) {
            const d = DETECTORES[id];
            if (!d) return;

            document.getElementById('idEliminar').value = d.id;
            document.getElementById('folioEliminar').textContent = d.folio;

            if (!modalEliminar) {
                modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminar'));
            }
            modalEliminar.show();
        }

        /* Quita ?ok= de la URL para que el aviso no reaparezca al recargar */
        if (window.history.replaceState && /[?&](ok|err)=/.test(location.search)) {
            window.history.replaceState({}, document.title, location.pathname);
        }
    </script>

</body>

</html>