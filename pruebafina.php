<?php
// ==========================================
// USUARIO
// ==========================================
$usuario = "Fatima";

// ==========================================
// CONEXIÓN
// ==========================================
require_once 'clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();

// ==========================================
// DASHBOARD - TOTALES DE ACTIVOS (EXTINTORES + BOTIQUINES)
// ==========================================

// --- EXTINTORES ---
$sql = "SELECT COUNT(*) AS total FROM tb_extintores";
$total_extintores = pg_fetch_result(pg_query($conexion, $sql), 0, 'total');

$sql = "SELECT COUNT(*) AS total FROM tb_extintores
        WHERE LEAST(fecha_proximo_mantenimiento, fecha_proxima_recarga) > CURRENT_DATE + INTERVAL '30 days'";
$corriente_extintores = pg_fetch_result(pg_query($conexion, $sql), 0, 'total');

$sql = "SELECT COUNT(*) AS total FROM tb_extintores
        WHERE LEAST(fecha_proximo_mantenimiento, fecha_proxima_recarga) BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '30 days'";
$por_vencer_extintores = pg_fetch_result(pg_query($conexion, $sql), 0, 'total');

$sql = "SELECT COUNT(*) AS total FROM tb_extintores
        WHERE LEAST(fecha_proximo_mantenimiento, fecha_proxima_recarga) < CURRENT_DATE";
$vencidos_extintores = pg_fetch_result(pg_query($conexion, $sql), 0, 'total');

// --- BOTIQUINES ---
$sql = "SELECT COUNT(*) AS total FROM tb_botiquines";
$total_botiquines = pg_fetch_result(pg_query($conexion, $sql), 0, 'total');

$sql = "SELECT COUNT(*) AS total FROM tb_botiquines
        WHERE fecha_proxima_revision > CURRENT_DATE + INTERVAL '30 days'";
$corriente_botiquines = pg_fetch_result(pg_query($conexion, $sql), 0, 'total');

$sql = "SELECT COUNT(*) AS total FROM tb_botiquines
        WHERE fecha_proxima_revision BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '30 days'";
$por_vencer_botiquines = pg_fetch_result(pg_query($conexion, $sql), 0, 'total');

$sql = "SELECT COUNT(*) AS total FROM tb_botiquines
        WHERE fecha_proxima_revision < CURRENT_DATE";
$vencidos_botiquines = pg_fetch_result(pg_query($conexion, $sql), 0, 'total');

// --- TOTALES COMBINADOS (los que usan las tarjetas y la gráfica) ---
$total_activos = $total_extintores + $total_botiquines;
$al_corriente  = $corriente_extintores + $corriente_botiquines;
$por_vencer    = $por_vencer_extintores + $por_vencer_botiquines;
$vencidos      = $vencidos_extintores + $vencidos_botiquines;

// ==========================================
// DASHBOARD - ACTIVOS POR TIPO (INVENTARIO)
// ==========================================
// Un conteo simple por cada tabla de activo.
// $total_extintores y $total_botiquines ya los tenemos de arriba,
// solo faltan las 6 tablas restantes.

$sql = "SELECT COUNT(*) AS total FROM tb_detectores_humo";
$total_detectores = pg_fetch_result(pg_query($conexion, $sql), 0, 'total');

$sql = "SELECT COUNT(*) AS total FROM tb_alarmas_emergencia";
$total_alarmas = pg_fetch_result(pg_query($conexion, $sql), 0, 'total');

$sql = "SELECT COUNT(*) AS total FROM tb_luces_emergencia";
$total_luces = pg_fetch_result(pg_query($conexion, $sql), 0, 'total');

$sql = "SELECT COUNT(*) AS total FROM tb_rutas_evacuacion";
$total_rutas = pg_fetch_result(pg_query($conexion, $sql), 0, 'total');

$sql = "SELECT COUNT(*) AS total FROM tb_puntos_reunion";
$total_puntos = pg_fetch_result(pg_query($conexion, $sql), 0, 'total');

$sql = "SELECT COUNT(*) AS total FROM tb_salidas_emergencia";
$total_salidas = pg_fetch_result(pg_query($conexion, $sql), 0, 'total');

// ==========================================
// DASHBOARD - TABLA DE TODOS LOS ACTIVOS
// ==========================================
// Combina extintores y botiquines en una sola lista con
// identificador, tipo, área, fecha crítica y estado ya calculado.
// Cuando agregues otra tabla, se suma aquí con el mismo patrón
// (UNION ALL + su propio SELECT con el CASE de estado).

$sqlTablaActivos = "
    SELECT
        'EXT-' || e.id_extintor AS identificador,
        'Extintor' AS tipo_activo,
        a.nombre_area AS area,
        LEAST(e.fecha_proximo_mantenimiento, e.fecha_proxima_recarga) AS fecha_critica,
        CASE
            WHEN LEAST(e.fecha_proximo_mantenimiento, e.fecha_proxima_recarga) < CURRENT_DATE THEN 'Vencido'
            WHEN LEAST(e.fecha_proximo_mantenimiento, e.fecha_proxima_recarga) <= CURRENT_DATE + INTERVAL '30 days' THEN 'Por vencer'
            ELSE 'Al corriente'
        END AS estado
    FROM tb_extintores e
    JOIN tb_areas a ON e.id_area = a.id_area

    UNION ALL

    SELECT
        'BOT-' || b.id_botiquin AS identificador,
        'Botiquín' AS tipo_activo,
        a.nombre_area AS area,
        b.fecha_proxima_revision AS fecha_critica,
        CASE
            WHEN b.fecha_proxima_revision < CURRENT_DATE THEN 'Vencido'
            WHEN b.fecha_proxima_revision <= CURRENT_DATE + INTERVAL '30 days' THEN 'Por vencer'
            ELSE 'Al corriente'
        END AS estado
    FROM tb_botiquines b
    JOIN tb_areas a ON b.id_area = a.id_area

    ORDER BY fecha_critica ASC
";

$resultadoTablaActivos = pg_query($conexion, $sqlTablaActivos);
$listaActivos = [];

if ($resultadoTablaActivos) {
    while ($fila = pg_fetch_assoc($resultadoTablaActivos)) {
        $listaActivos[] = $fila;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIPV - Audi Center Insurgentes</title>

    <!-- BOOTSTRAP 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- CSS DEL PROYECTO -->
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="botones.css">
</head>

<body>

    <!-- ========== NAVBAR SUPERIOR ========== -->
    <nav class="navbar navbar-light bg-light shadow-sm fixed-top">
        <div class="container-fluid">

            <!-- MENÚ IZQUIERDO -->
            <button class="navbar-toggler me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- LOGO SIPV -->
            <a class="navbar-brand me-3 mb-0" href="#">
                <span class="logo-sipv">SIPV</span>
            </a>

            <!-- INFORMACIÓN DE AUDI -->
            <div class="border-start ps-3 d-none d-md-block">
                <h6 class="mb-0 titulo-audi">AUDI CENTER INSURGENTES</h6>
                <small class="text-muted">Sistema Inteligente de Prevención y Verificación</small>
            </div>

            <!-- BOTONES DE LA DERECHA -->
            <div class="ms-auto d-flex align-items-center gap-2">

                <!-- PDF -->
                <a href="ARCHIVO" download="Reporte" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-file-pdf text-danger me-1"></i>PDF
                </a>

                <!-- NOTIFICACIONES -->
                <a href="#" class="btn btn-light position-relative btn-sm">
                    <i class="fas fa-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">5</span>
                </a>

                <!-- PERFIL -->
                <button class="navbar-toggler btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDerecho">
                    <i class="fas fa-user-circle"></i>
                </button>

                <!-- SALIR -->
                <a href="index.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i>Salir
                </a>

            </div>
        </div>
    </nav>

    <!-- ========== MENÚ LATERAL IZQUIERDO ========== -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNavbar">

        <div class="offcanvas-header bg-dark text-white">
            <h5 class="offcanvas-title"><i class="fas fa-shield-alt me-2"></i>SIPV Menu</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body">
            <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">

                <li class="nav-item">
                    <a class="nav-link active" href="pruebafina.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="calendario/index.html">
                        <i class="fas fa-calendar-alt me-2"></i>Calendario
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/recidencias/activos/activos.php">
                        <i class="fas fa-boxes me-2"></i>Activos
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-map me-2"></i>Planos
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-exclamation-triangle me-2"></i>Alertas
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-tools me-2"></i>Mantenimientos
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-chart-bar me-2"></i>Reportes
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-users me-2"></i>Usuarios
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-cog me-2"></i>Configuración
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Roles</a></li>
                        <li><a class="dropdown-item" href="#">Usuarios del sistema</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#">Preferencias generales</a></li>
                    </ul>
                </li>

            </ul>
        </div>
    </div>

    <!-- ========== OFFCANVAS DERECHO ========== -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasDerecho">

        <div class="offcanvas-header">
            <h5 class="offcanvas-title"><i class="fas fa-user-cog me-2"></i>Opciones de Perfil</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body">
            <a href="#" class="d-block mb-3 text-decoration-none text-dark"><i class="fas fa-user me-2"></i>Perfil</a>
            <a href="#" class="d-block mb-3 text-decoration-none text-dark"><i class="fas fa-sliders-h me-2"></i>Configuración</a>
            <a href="#" class="d-block mb-3 text-decoration-none text-dark"><i class="fas fa-bell me-2"></i>Notificaciones</a>
            <hr>
            <a href="logout.php" class="d-block text-danger text-decoration-none"><i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión</a>
        </div>
    </div>

    <!-- ========== CONTENIDO PRINCIPAL ========== -->
    <div class="container mt-5 pt-5">

        <!-- MENSAJE DE BIENVENIDA -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h3 class="text-primary m-0"><i class="fas fa-user-check me-2"></i>Bienvenida, <?= $usuario ?></h3>
                <p class="text-muted m-0 mt-1">Este es el panel principal del sistema SIPV.</p>
            </div>
        </div>

        <!-- TARJETAS DEL DASHBOARD -->
        <div class="row g-3 mb-4">

            <!-- TOTAL DE ACTIVOS -->
            <div class="col-lg-3 col-md-6">
                <div class="card card-metric p-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-shape bg-primary me-3"><i class="fas fa-boxes"></i></div>
                        <div>
                            <small class="text-muted">Total de activos</small>
                            <h4 class="font-weight-bold m-0"><?= $total_activos ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AL CORRIENTE -->
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

            <!-- POR VENCER -->
            <div class="col-lg-3 col-md-6">
                <div class="card card-metric p-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-shape bg-warning me-3"><i class="fas fa-exclamation-triangle"></i></div>
                        <div>
                            <small class="text-muted">Por vencer</small>
                            <h4 class="font-weight-bold m-0"><?= $por_vencer ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VENCIDOS -->
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

        <!-- GRÁFICAS -->
        <div class="row g-4">

            <!-- ESTADO DE LOS ACTIVOS -->
            <div class="col-lg-6 col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Estado de los Activos</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="graficoActivos"></canvas>
                    </div>
                </div>
            </div>

            <!-- ACTIVOS POR TIPO -->
            <div class="col-lg-6 col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-boxes me-2 text-primary"></i>Activos por Tipo</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="graficoPorTipo"></canvas>
                    </div>
                </div>
            </div>

        </div>

        <!-- TABLA DE TODOS LOS ACTIVOS -->
        <div class="row g-4 mt-1">
            <div class="col-12">
                <div class="card shadow-sm">

                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Todos los Activos</h5>
                    </div>

                    <div class="card-body">

                        <!-- BUSCADOR Y FILTRO -->
                        <div class="row g-2 mb-3">

                            <div class="col-md-6">
                                <input
                                    type="text"
                                    id="buscadorActivos"
                                    class="form-control"
                                    placeholder="Buscar por folio, tipo o área...">
                            </div>

                            <div class="col-md-4">
                                <select id="filtroEstado" class="form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="Al corriente">Al corriente</option>
                                    <option value="Por vencer">Por vencer</option>
                                    <option value="Vencido">Vencido</option>
                                </select>
                            </div>

                        </div>

                        <!-- TABLA -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="tablaActivos">
                                <thead>
                                    <tr>
                                        <th>Folio</th>
                                        <th>Tipo</th>
                                        <th>Área</th>
                                        <th>Fecha crítica</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php foreach ($listaActivos as $activo): ?>

                                        <?php
                                        // Clase de color según el estado, para el pill
                                        $claseEstado = 'bg-secondary';

                                        if ($activo['estado'] === 'Al corriente') {
                                            $claseEstado = 'bg-success';
                                        } elseif ($activo['estado'] === 'Por vencer') {
                                            $claseEstado = 'bg-warning text-dark';
                                        } elseif ($activo['estado'] === 'Vencido') {
                                            $claseEstado = 'bg-danger';
                                        }
                                        ?>

                                        <tr data-estado="<?= htmlspecialchars($activo['estado']) ?>">
                                            <td><?= htmlspecialchars($activo['identificador']) ?></td>
                                            <td><?= htmlspecialchars($activo['tipo_activo']) ?></td>
                                            <td><?= htmlspecialchars($activo['area']) ?></td>
                                            <td><?= htmlspecialchars($activo['fecha_critica']) ?></td>
                                            <td>
                                                <span class="badge <?= $claseEstado ?>">
                                                    <?= htmlspecialchars($activo['estado']) ?>
                                                </span>
                                            </td>
                                        </tr>

                                    <?php endforeach; ?>

                                    <?php if (empty($listaActivos)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">
                                                No hay activos registrados.
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- ========== JAVASCRIPT ========== -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- DATOS PARA LAS GRÁFICAS -->
    <script>
        const datosActivos = {
            alCorriente: <?= $al_corriente ?>,
            porVencer: <?= $por_vencer ?>,
            vencidos: <?= $vencidos ?>
        };

        const datosPorTipo = {
            labels: [
                'Extintores',
                'Botiquines',
                'Detectores',
                'Alarmas',
                'Luces',
                'Rutas',
                'Puntos reunión',
                'Salidas'
            ],
            valores: [
                <?= $total_extintores ?>,
                <?= $total_botiquines ?>,
                <?= $total_detectores ?>,
                <?= $total_alarmas ?>,
                <?= $total_luces ?>,
                <?= $total_rutas ?>,
                <?= $total_puntos ?>,
                <?= $total_salidas ?>
            ]
        };
    </script>
    <script src="graficos.js"></script>

    <!-- BUSCADOR Y FILTRO DE LA TABLA DE ACTIVOS -->
    <script>

        const buscador = document.getElementById('buscadorActivos');
        const filtroEstado = document.getElementById('filtroEstado');
        const filasTabla = document.querySelectorAll('#tablaActivos tbody tr');

        function filtrarTabla() {

            const textoBusqueda = buscador.value.toLowerCase();
            const estadoSeleccionado = filtroEstado.value;

            filasTabla.forEach(function(fila) {

                const texto = fila.textContent.toLowerCase();
                const estadoFila = fila.dataset.estado || '';

                const coincideTexto = texto.includes(textoBusqueda);
                const coincideEstado = (estadoSeleccionado === '') || (estadoFila === estadoSeleccionado);

                if (coincideTexto && coincideEstado) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }

            });

        }

        buscador.addEventListener('input', filtrarTabla);
        filtroEstado.addEventListener('change', filtrarTabla);

    </script>

</body>
</html>