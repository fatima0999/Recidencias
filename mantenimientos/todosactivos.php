<?php
// ==========================================
// MANTENIMIENTOS
// Detectores de humo + Luces de emergencia + Alarmas sísmicas
// De TODAS las áreas
// Archivo: recidencias/mantenimientos/todosactivos.php
// ==========================================
require_once $_SERVER['DOCUMENT_ROOT'] . '/recidencias/clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();

// ------------------------------------------
// CONSULTA: las 3 tablas en una sola lista
// ------------------------------------------
$sql = "
    SELECT x.identificador,
           x.tipo_activo,
           a.nombre_area AS area,
           a.nivel,
           x.fecha_ultima_revision,
           x.fecha_proxima_revision,
           (x.fecha_proxima_revision - CURRENT_DATE) AS dias,
           CASE
               WHEN x.fecha_proxima_revision IS NULL THEN 'Sin fecha'
               WHEN x.fecha_proxima_revision < CURRENT_DATE THEN 'Vencido'
               WHEN x.fecha_proxima_revision <= CURRENT_DATE + INTERVAL '30 days' THEN 'Por vencer'
               ELSE 'Al corriente'
           END AS estado
    FROM (
        SELECT 'DET-' || d.id_detector AS identificador,
               'Detector de humo' AS tipo_activo,
               d.id_area,
               d.fecha_ultima_revision,
               d.fecha_proxima_revision
        FROM tb_detectores_humo d

        UNION ALL

        SELECT 'LUZ-' || l.id_luz,
               'Luz de emergencia',
               l.id_area,
               l.fecha_ultima_revision,
               l.fecha_proxima_revision
        FROM tb_luces_emergencia l

        UNION ALL

        SELECT 'ALA-' || al.id_alarma,
               'Alarma sísmica',
               al.id_area,
               al.fecha_ultima_revision,
               al.fecha_proxima_revision
        FROM tb_alarmas_emergencia al
    ) AS x
    LEFT JOIN tb_areas a ON x.id_area = a.id_area
    ORDER BY x.fecha_proxima_revision ASC NULLS LAST
";

$resultado = pg_query($conexion, $sql);
$listaActivos = [];

if ($resultado) {
    while ($fila = pg_fetch_assoc($resultado)) {
        $listaActivos[] = $fila;
    }
}

// ------------------------------------------
// TOTALES (se calculan de la misma lista)
// ------------------------------------------
$total        = count($listaActivos);
$al_corriente = 0;
$por_vencer   = 0;
$vencidos     = 0;

foreach ($listaActivos as $f) {
    if ($f['estado'] === 'Al corriente') $al_corriente++;
    elseif ($f['estado'] === 'Por vencer') $por_vencer++;
    elseif ($f['estado'] === 'Vencido') $vencidos++;
}

$clasesEstado = [
    'Vencido'      => 'danger',
    'Por vencer'   => 'warning',
    'Al corriente' => 'success',
    'Sin fecha'    => 'secondary'
];

function fmtFecha($f) {
    return $f ? date('d/m/Y', strtotime($f)) : '—';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mantenimientos - SIPV</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="/recidencias/style.css">
    <link rel="stylesheet" href="/recidencias/botones.css">
</head>

<body>

    <!-- ========== NAVBAR ========== -->
    <nav class="navbar navbar-light bg-light shadow-sm fixed-top">
        <div class="container-fluid">

            <button class="navbar-toggler me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <a class="navbar-brand me-3 mb-0" href="/recidencias/pruebafina.php">
                <span class="logo-sipv">SIPV</span>
            </a>

            <div class="border-start ps-3 d-none d-md-block">
                <h6 class="mb-0 titulo-audi">AUDI CENTER INSURGENTES</h6>
                <small class="text-muted">Mantenimientos</small>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="/recidencias/alertas/alertas.php" class="btn btn-light position-relative btn-sm">
                    <i class="fas fa-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $vencidos ?></span>
                </a>
                <a href="/recidencias/index.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <!-- ========== MENÚ LATERAL ========== -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNavbar">
        <div class="offcanvas-header bg-dark text-white">
            <h5 class="offcanvas-title"><i class="fas fa-shield-alt me-2"></i>SIPV Menu</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                <li class="nav-item"><a class="nav-link" href="/recidencias/pruebafina.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="/recidencias/calendario/index.html"><i class="fas fa-calendar-alt me-2"></i>Calendario</a></li>
                <li class="nav-item"><a class="nav-link" href="/recidencias/activos/activos.php"><i class="fas fa-boxes me-2"></i>Activos</a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-map me-2"></i>Planos</a></li>
                <li class="nav-item"><a class="nav-link" href="/recidencias/alertas/alertas.php"><i class="fas fa-exclamation-triangle me-2"></i>Alertas</a></li>
                <li class="nav-item"><a class="nav-link active" href="/recidencias/mantenimientos/todosactivos.php"><i class="fas fa-tools me-2"></i>Mantenimientos</a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-users me-2"></i>Usuarios</a></li>
            </ul>
        </div>
    </div>

    <!-- ========== CONTENIDO ========== -->
    <div class="container mt-5 pt-5">

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h3 class="text-primary m-0"><i class="fas fa-tools me-2"></i>Mantenimientos</h3>
                <p class="text-muted m-0 mt-1">Detectores de humo, luces de emergencia y alarmas sísmicas de todas las áreas.</p>
            </div>
        </div>

        <!-- TARJETAS -->
        <div class="row g-3 mb-4">

            <div class="col-lg-3 col-md-6">
                <div class="card card-metric p-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-shape bg-primary me-3"><i class="fas fa-boxes"></i></div>
                        <div>
                            <small class="text-muted">Total</small>
                            <h4 class="font-weight-bold m-0"><?= $total ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card card-metric p-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-shape bg-success me-3"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <small class="text-muted">Al corriente</small>
                            <h4 class="font-weight-bold m-0"><?= $al_corriente ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card card-metric p-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-shape bg-warning me-3"><i class="fas fa-exclamation-triangle"></i></div>
                        <div>
                            <small class="text-muted">Por vencer (30 días)</small>
                            <h4 class="font-weight-bold m-0"><?= $por_vencer ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card card-metric p-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-shape bg-danger me-3"><i class="fas fa-times-circle"></i></div>
                        <div>
                            <small class="text-muted">Vencidos</small>
                            <h4 class="font-weight-bold m-0"><?= $vencidos ?></h4>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- TABLA -->
        <div class="card shadow-sm mb-5">
            <div class="card-header bg-white d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Lista de mantenimientos</h5>

                <div class="d-flex flex-wrap gap-2">
                    <input type="text" id="buscador" class="form-control form-control-sm" placeholder="Buscar...">

                    <select id="filtroTipo" class="form-select form-select-sm">
                        <option value="">Todos los tipos</option>
                        <option value="Detector de humo">Detector de humo</option>
                        <option value="Luz de emergencia">Luz de emergencia</option>
                        <option value="Alarma sísmica">Alarma sísmica</option>
                    </select>

                    <select id="filtroEstado" class="form-select form-select-sm">
                        <option value="">Todos los estados</option>
                        <option value="Al corriente">Al corriente</option>
                        <option value="Por vencer">Por vencer</option>
                        <option value="Vencido">Vencido</option>
                        <option value="Sin fecha">Sin fecha</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaMantenimientos">
                    <thead class="table-light">
                        <tr>
                            <th>Identificador</th>
                            <th>Tipo</th>
                            <th>Área</th>
                            <th>Nivel</th>
                            <th>Última revisión</th>
                            <th>Próxima revisión</th>
                            <th>Días</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($listaActivos)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No hay activos registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($listaActivos as $f): ?>
                            <?php
                            $dias = $f['dias'];
                            if ($dias === null) {
                                $textoDias = '—';
                            } elseif ((int)$dias < 0) {
                                $textoDias = abs((int)$dias) . ' d de atraso';
                            } else {
                                $textoDias = (int)$dias . ' d';
                            }
                            ?>
                            <tr data-estado="<?= htmlspecialchars($f['estado']) ?>"
                                data-tipo="<?= htmlspecialchars($f['tipo_activo']) ?>">
                                <td><?= htmlspecialchars($f['identificador']) ?></td>
                                <td><?= htmlspecialchars($f['tipo_activo']) ?></td>
                                <td><?= htmlspecialchars($f['area'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($f['nivel'] ?? '—') ?></td>
                                <td><?= fmtFecha($f['fecha_ultima_revision']) ?></td>
                                <td><?= fmtFecha($f['fecha_proxima_revision']) ?></td>
                                <td><?= $textoDias ?></td>
                                <td>
                                    <span class="badge bg-<?= $clasesEstado[$f['estado']] ?? 'secondary' ?>">
                                        <?= htmlspecialchars($f['estado']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ========== JAVASCRIPT ========== -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const buscador     = document.getElementById('buscador');
        const filtroTipo   = document.getElementById('filtroTipo');
        const filtroEstado = document.getElementById('filtroEstado');
        const filas        = document.querySelectorAll('#tablaMantenimientos tbody tr[data-estado]');

        function filtrar() {
            const texto  = buscador.value.toLowerCase();
            const tipo   = filtroTipo.value;
            const estado = filtroEstado.value;

            filas.forEach(function (fila) {
                const okTexto  = fila.textContent.toLowerCase().includes(texto);
                const okTipo   = tipo === ''   || fila.dataset.tipo === tipo;
                const okEstado = estado === '' || fila.dataset.estado === estado;

                fila.style.display = (okTexto && okTipo && okEstado) ? '' : 'none';
            });
        }

        buscador.addEventListener('input', filtrar);
        filtroTipo.addEventListener('change', filtrar);
        filtroEstado.addEventListener('change', filtrar);
    </script>

</body>
</html>