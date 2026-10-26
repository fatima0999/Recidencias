<?php

/* ==========================================================
   CONEXIÓN
   ========================================================== */

require_once __DIR__ . '/../../../clases/conexion.php';

date_default_timezone_set('America/Mexico_City');

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();

pg_query($conexion, "SET TIME ZONE 'America/Mexico_City'");


/* ==========================================================
   CONSULTA ÚNICA: PUNTOS DE REUNIÓN DEL SÓTANO
   El estado de cumplimiento se calcula solo aquí (SQL).
   La relación punto <-> área es muchos a muchos, por eso se
   usa la tabla intermedia tb_areas_puntos_reunion con EXISTS
   (así un punto compartido por varias áreas sale una sola vez).
   ========================================================== */

$sql = "
    SELECT
        p.id_punto AS identificador,
        p.nombre_punto,
        p.ubicacion,
        p.capacidad_personas,
        p.senalizacion,
        p.estado AS estado_punto,
        p.fecha_proxima_revision::date AS fecha_critica,

        CASE
            WHEN p.fecha_proxima_revision IS NULL
                THEN 'Sin fecha'
            WHEN p.fecha_proxima_revision::date < CURRENT_DATE
                THEN 'Vencido'
            WHEN p.fecha_proxima_revision::date <= CURRENT_DATE + 30
                THEN 'Por vencer'
            ELSE 'Al corriente'
        END AS estado_cumplimiento

    FROM tb_puntos_reunion p

    WHERE EXISTS (
        SELECT 1
        FROM tb_areas_puntos_reunion ap
        JOIN tb_areas a ON a.id_area = ap.id_area
        WHERE ap.id_punto = p.id_punto
          AND a.nivel = 'Sótano'
    )

    ORDER BY p.id_punto ASC
";

$resultado = pg_query($conexion, $sql);

if ($resultado === false) {
    error_log('Error SIREV puntos_reunion: ' . pg_last_error($conexion));
    die('Error al consultar los puntos de reunión.');
}


/* ==========================================================
   LISTA + CONTEOS
   ========================================================== */

$meses = [
    1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
];

$listaPuntos  = [];
$al_corriente = 0;
$por_vencer   = 0;
$vencidos     = 0;
$sin_fecha    = 0;

while ($fila = pg_fetch_assoc($resultado)) {

    if (!empty($fila['fecha_critica'])) {
        $f = new DateTime($fila['fecha_critica']);
        $fila['fecha_formateada'] =
            $f->format('d') . ' ' . $meses[(int) $f->format('n')] . ' ' . $f->format('Y');
    } else {
        $fila['fecha_formateada'] = null;
    }

    switch ($fila['estado_cumplimiento']) {
        case 'Al corriente': $al_corriente++; break;
        case 'Por vencer':   $por_vencer++;   break;
        case 'Vencido':      $vencidos++;     break;
        default:             $sin_fecha++;    break;
    }

    $listaPuntos[] = $fila;
}

$total_puntos = count($listaPuntos);

$cumplimiento = $total_puntos > 0
    ? round(($al_corriente / $total_puntos) * 100)
    : 0;
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Puntos de Reunión - Sótano</title>

    <!-- BOOTSTRAP 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- FONT AWESOME -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
    >

    <style>

        body {
            padding-top: 100px;
        }

        .titulo-audi {
            font-weight: 600;
        }

        .contenedor-metricas {
            padding-left: 20px;
            padding-right: 20px;
        }

        @media (max-width: 768px) {

            body {
                padding-top: 90px;
            }

            .contenedor-metricas {
                padding-left: 12px;
                padding-right: 12px;
            }

        }

    </style>

</head>


<body>

    <!-- =====================================================
         NAVBAR
         ===================================================== -->

    <nav class="navbar navbar-light bg-light shadow-sm fixed-top">

        <div class="container-fluid">

            <div class="border-start ps-3">
                <h6 class="mb-0 titulo-audi">Sótano</h6>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">

                <!-- Cambia esta ruta por tu generador de PDF real -->
                <a
                    href="../../reportes/sotano_puntos_reunion.php"
                    class="btn btn-outline-secondary"
                >
                    <i class="fas fa-file-pdf me-1"></i>
                    PDF
                </a>

                <button
                    class="navbar-toggler"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvasDerecho"
                    aria-controls="offcanvasDerecho"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>

                <a href="../../activos.php" class="btn btn-outline-danger">
                    <i class="fas fa-arrow-left me-1"></i>
                    Volver
                </a>

            </div>

        </div>

    </nav>


    <!-- =====================================================
         OFFCANVAS DERECHO
         ===================================================== -->

    <div
        class="offcanvas offcanvas-end"
        tabindex="-1"
        id="offcanvasDerecho"
        aria-labelledby="offcanvasDerechoLabel"
    >

        <div class="offcanvas-header">

            <h5 class="offcanvas-title" id="offcanvasDerechoLabel">
                Menú
            </h5>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="offcanvas"
                aria-label="Cerrar"
            ></button>

        </div>

        <div class="offcanvas-body">
            <!-- Aquí va tu contenido / menú -->
        </div>

    </div>


    <!-- =====================================================
         MÉTRICAS
         ===================================================== -->

    <div class="container-fluid contenedor-metricas">

        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3 mb-4">

            <!-- CUMPLIMIENTO -->
            <div class="col">
                <div class="card p-3 border-start border-success border-4 shadow-sm h-100">
                    <small class="text-muted">Cumplimiento</small>
                    <h3 class="fw-bold text-success m-0">
                        <?= $cumplimiento ?>%
                    </h3>
                </div>
            </div>

            <!-- AL CORRIENTE -->
            <div class="col">
                <div class="card p-3 border-start border-success border-4 shadow-sm h-100">
                    <small class="text-muted">Al corriente</small>
                    <h3 class="fw-bold m-0">
                        <?= $al_corriente ?>
                    </h3>
                </div>
            </div>

            <!-- POR VENCER -->
            <div class="col">
                <div class="card p-3 border-start border-warning border-4 shadow-sm h-100">
                    <small class="text-muted">Por vencer</small>
                    <h3 class="fw-bold text-warning m-0">
                        <?= $por_vencer ?>
                    </h3>
                </div>
            </div>

            <!-- VENCIDOS -->
            <div class="col">
                <div class="card p-3 border-start border-danger border-4 shadow-sm h-100">
                    <small class="text-muted">Vencidos</small>
                    <h3 class="fw-bold text-danger m-0">
                        <?= $vencidos ?>
                    </h3>
                </div>
            </div>

            <!-- SIN FECHA -->
            <div class="col">
                <div class="card p-3 border-start border-secondary border-4 shadow-sm h-100">
                    <small class="text-muted">Sin fecha</small>
                    <h3 class="fw-bold text-secondary m-0">
                        <?= $sin_fecha ?>
                    </h3>
                </div>
            </div>

        </div>

    </div>


    <!-- =====================================================
         TABLA DE PUNTOS DE REUNIÓN
         ===================================================== -->

    <div class="container-fluid">

        <div class="row g-4 mt-1">

            <div class="col-12">

                <div class="card shadow-sm">

                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2 text-primary"></i>
                            Puntos de Reunión del Sótano
                        </h5>
                    </div>

                    <div class="card-body">

                        <!-- BUSCADOR Y FILTRO -->
                        <div class="row g-2 mb-3">

                            <div class="col-md-6">
                                <input
                                    type="text"
                                    id="buscadorPuntos"
                                    class="form-control"
                                    placeholder="Buscar en la tabla..."
                                >
                            </div>

                            <div class="col-md-4">
                                <select id="filtroEstado" class="form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="Al corriente">Al corriente</option>
                                    <option value="Por vencer">Por vencer</option>
                                    <option value="Vencido">Vencido</option>
                                    <option value="Sin fecha">Sin fecha</option>
                                </select>
                            </div>

                        </div>


                        <!-- TABLA -->
                        <div class="table-responsive">

                            <table class="table table-hover align-middle" id="tablaPuntos">

                                <thead>
                                    <tr>
                                        <th>ID Punto</th>
                                        <th>Nombre</th>
                                        <th>Ubicación</th>
                                        <th>Capacidad</th>
                                        <th>Señalización</th>
                                        <th>Estado del punto</th>
                                        <th>Próxima revisión</th>
                                        <th>Cumplimiento</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php foreach ($listaPuntos as $punto): ?>

                                        <?php
                                        $claseEstado = 'bg-secondary';

                                        if ($punto['estado_cumplimiento'] === 'Al corriente') {
                                            $claseEstado = 'bg-success';
                                        } elseif ($punto['estado_cumplimiento'] === 'Por vencer') {
                                            $claseEstado = 'bg-warning text-dark';
                                        } elseif ($punto['estado_cumplimiento'] === 'Vencido') {
                                            $claseEstado = 'bg-danger';
                                        }
                                        ?>

                                        <tr data-estado="<?= htmlspecialchars($punto['estado_cumplimiento']) ?>">

                                            <td><?= htmlspecialchars($punto['identificador']) ?></td>

                                            <td><?= htmlspecialchars($punto['nombre_punto'] ?? '—') ?></td>

                                            <td><?= htmlspecialchars($punto['ubicacion'] ?? '—') ?></td>

                                            <td><?= htmlspecialchars($punto['capacidad_personas'] ?? '—') ?></td>

                                            <td><?= htmlspecialchars($punto['senalizacion'] ?? '—') ?></td>

                                            <td><?= htmlspecialchars($punto['estado_punto'] ?? '—') ?></td>

                                            <td><?= htmlspecialchars($punto['fecha_formateada'] ?? '—') ?></td>

                                            <td>
                                                <span class="badge <?= $claseEstado ?>">
                                                    <?= htmlspecialchars($punto['estado_cumplimiento']) ?>
                                                </span>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>


                                    <?php if (empty($listaPuntos)): ?>

                                        <tr>
                                            <td colspan="8" class="text-center text-muted">
                                                No hay puntos de reunión registrados en el Sótano.
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


    <!-- BOOTSTRAP JS -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js">
    </script>


    <!-- =====================================================
         BUSCADOR Y FILTRO
         ===================================================== -->

    <script>

        const buscador = document.getElementById('buscadorPuntos');
        const filtroEstado = document.getElementById('filtroEstado');

        // Solo filas con datos (ignora la fila "No hay puntos...")
        const filasTabla = document.querySelectorAll('#tablaPuntos tbody tr[data-estado]');

        function filtrarTabla() {

            const textoBusqueda = buscador.value.toLowerCase();
            const estadoSeleccionado = filtroEstado.value;

            filasTabla.forEach(function (fila) {

                const texto = fila.textContent.toLowerCase();
                const estadoFila = fila.dataset.estado || '';

                const coincideTexto = texto.includes(textoBusqueda);

                const coincideEstado =
                    (estadoSeleccionado === '') ||
                    (estadoFila === estadoSeleccionado);

                fila.style.display = (coincideTexto && coincideEstado) ? '' : 'none';

            });

        }

        buscador.addEventListener('input', filtrarTabla);
        filtroEstado.addEventListener('change', filtrarTabla);

    </script>

</body>

</html>