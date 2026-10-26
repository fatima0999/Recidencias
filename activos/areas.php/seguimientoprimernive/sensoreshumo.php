<?php
/* ==========================================================
   SIPV - REGISTRO Y SEGUIMIENTO DE DETECTORES DE HUMO
   Módulo exclusivo de PRIMER NIVEL
   ========================================================== */

require_once __DIR__ . '/../../../clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();

/* ==========================================================
   MÉTRICAS: SOLO DETECTORES UBICADOS EN EL PRIMER NIVEL
   ========================================================== */

$sqlMetricas = "
    SELECT
        COUNT(*) AS total,
        COUNT(*) FILTER (
            WHERE d.fecha_proxima_revision IS NOT NULL
              AND d.fecha_proxima_revision > CURRENT_DATE + INTERVAL '30 days'
        ) AS al_corriente,
        COUNT(*) FILTER (
            WHERE d.fecha_proxima_revision IS NOT NULL
              AND d.fecha_proxima_revision >= CURRENT_DATE
              AND d.fecha_proxima_revision <= CURRENT_DATE + INTERVAL '30 days'
        ) AS por_vencer,
        COUNT(*) FILTER (
            WHERE d.fecha_proxima_revision IS NOT NULL
              AND d.fecha_proxima_revision < CURRENT_DATE
        ) AS vencidos
    FROM tb_detectores_humo d
    INNER JOIN tb_areas a ON a.id_area = d.id_area
    WHERE a.nivel = 'Primer Nivel'
";

$resultadoMetricas = pg_query($conexion, $sqlMetricas);

$metricas = $resultadoMetricas ? pg_fetch_assoc($resultadoMetricas) : [
    'total' => 0,
    'al_corriente' => 0,
    'por_vencer' => 0,
    'vencidos' => 0
];

$total        = (int) ($metricas['total'] ?? 0);
$al_corriente = (int) ($metricas['al_corriente'] ?? 0);
$por_vencer   = (int) ($metricas['por_vencer'] ?? 0);
$vencidos     = (int) ($metricas['vencidos'] ?? 0);

$cumplimiento = $total > 0
    ? round(($al_corriente / $total) * 100)
    : 0;

/* ==========================================================
   CONSULTA DE DETECTORES DE HUMO DEL PRIMER NIVEL
   ========================================================== */

$sqlDetectores = "
    SELECT
        d.id_detector,
        d.tipo_detector,
        d.marca,
        d.modelo,
        d.numero_serie,
        d.estado_fisico,
        d.fecha_ultima_revision,
        d.fecha_proxima_revision,
        a.nombre_area
    FROM tb_detectores_humo d
    INNER JOIN tb_areas a ON a.id_area = d.id_area
    WHERE a.nivel = 'Primer Nivel'
    ORDER BY d.id_detector ASC
";

$resultadoDetectores = pg_query($conexion, $sqlDetectores);
$listaDetectores = [];

$hoy      = date('Y-m-d');
$en30Dias = date('Y-m-d', strtotime('+30 days'));

if ($resultadoDetectores) {
    while ($detector = pg_fetch_assoc($resultadoDetectores)) {

        $fecha = $detector['fecha_proxima_revision'] ?? null;

        if (empty($fecha)) {
            $detector['estado_seguimiento'] = 'Sin fecha';
        } elseif ($fecha < $hoy) {
            $detector['estado_seguimiento'] = 'Vencido';
        } elseif ($fecha <= $en30Dias) {
            $detector['estado_seguimiento'] = 'Por vencer';
        } else {
            $detector['estado_seguimiento'] = 'Al corriente';
        }

        $detector['fecha_formateada'] = !empty($fecha)
            ? date('d/m/Y', strtotime($fecha))
            : 'No registrada';

        $detector['ultima_formateada'] = !empty($detector['fecha_ultima_revision'])
            ? date('d/m/Y', strtotime($detector['fecha_ultima_revision']))
            : 'No registrada';

        $listaDetectores[] = $detector;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detectores de humo - Primer Nivel</title>

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

    <!-- LIBRERÍA PARA GENERAR QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        body {
            padding-top: 100px;
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

        /* CONTENEDOR DEL QR */
        #contenedorQR {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        #contenedorQR img {
            max-width: 250px;
            height: auto;
        }

        .folio-qr {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 15px;
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
                <h6 class="mb-0 titulo-audi">
                    Primer Nivel
                </h6>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">
                <a
                    href="../../areas.php/primerNivel.php"
                    class="btn btn-outline-danger"
                >
                    <i class="fas fa-arrow-left me-1"></i>
                    Volver
                </a>
            </div>
        </div>
    </nav>

    <!-- =====================================================
        CONTENEDOR DE MÉTRICAS
        ===================================================== -->
    <div class="container-fluid contenedor-metricas">
        <div class="row g-3 mb-4">

            <!-- CUMPLIMIENTO -->
            <div class="col-lg-3 col-md-6">
                <div class="card p-3 border-start border-success border-4 shadow-sm">
                    <small class="text-muted">Cumplimiento</small>
                    <h3 class="fw-bold text-success m-0">
                        <?= $cumplimiento ?>%
                    </h3>
                </div>
            </div>

            <!-- AL CORRIENTE -->
            <div class="col-lg-3 col-md-6">
                <div class="card p-3 border-start border-success border-4 shadow-sm">
                    <small class="text-muted">Al corriente</small>
                    <h3 class="fw-bold m-0">
                        <?= $al_corriente ?>
                    </h3>
                </div>
            </div>

            <!-- POR VENCER -->
            <div class="col-lg-3 col-md-6">
                <div class="card p-3 border-start border-warning border-4 shadow-sm">
                    <small class="text-muted">Por vencer</small>
                    <h3 class="fw-bold text-warning m-0">
                        <?= $por_vencer ?>
                    </h3>
                </div>
            </div>

            <!-- VENCIDOS -->
            <div class="col-lg-3 col-md-6">
                <div class="card p-3 border-start border-danger border-4 shadow-sm">
                    <small class="text-muted">Vencidos</small>
                    <h3 class="fw-bold text-danger m-0">
                        <?= $vencidos ?>
                    </h3>
                </div>
            </div>

        </div>
    </div>

    <!-- =====================================================
        ENCABEZADO ACTIVOS + BOTÓN AGREGAR
        ===================================================== -->
    <div class="container d-flex justify-content-between align-items-center mt-1 pt-3">
        <h5>ACTIVOS</h5>

        <a
            href="../../agre.senHumo/sen.Humo.php"
            class="btn btn-primary"
        >
            <i class="fas fa-plus me-1"></i>
            Agregar
        </a>
    </div>

    <!-- =====================================================
        TABLA DE DETECTORES
        ===================================================== -->
    <div class="container-fluid">
        <div class="row g-4 mt-1">
            <div class="col-12">
                <div class="card shadow-sm mb-5">

                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2 text-primary"></i>
                            Detectores de humo del Primer Nivel
                        </h5>
                    </div>

                    <div class="card-body">

                        <!-- BUSCADOR Y FILTRO -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <input
                                    type="text"
                                    id="buscadorDetectores"
                                    class="form-control"
                                    placeholder="Buscar por ID, área, marca, modelo o serie..."
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
                            <table
                                class="table table-hover align-middle"
                                id="tablaDetectores"
                            >
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Área</th>
                                        <th>Tipo</th>
                                        <th>Marca</th>
                                        <th>Modelo</th>
                                        <th>Núm. de serie</th>
                                        <th>Estado físico</th>
                                        <th>Última revisión</th>
                                        <th>Próx. revisión</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($listaDetectores as $detector): ?>
                                        <?php
                                        $claseEstado = 'bg-secondary';

                                        if ($detector['estado_seguimiento'] === 'Al corriente') {
                                            $claseEstado = 'bg-success';
                                        } elseif ($detector['estado_seguimiento'] === 'Por vencer') {
                                            $claseEstado = 'bg-warning text-dark';
                                        } elseif ($detector['estado_seguimiento'] === 'Vencido') {
                                            $claseEstado = 'bg-danger';
                                        }
                                        ?>

                                        <tr data-estado="<?= htmlspecialchars($detector['estado_seguimiento'], ENT_QUOTES, 'UTF-8') ?>">
                                            <td><?= htmlspecialchars((string) $detector['id_detector'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($detector['nombre_area'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($detector['tipo_detector'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($detector['marca'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($detector['modelo'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($detector['numero_serie'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($detector['estado_fisico'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($detector['ultima_formateada'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($detector['fecha_formateada'], ENT_QUOTES, 'UTF-8') ?></td>

                                            <td>
                                                <span class="badge <?= $claseEstado ?>">
                                                    <?= htmlspecialchars($detector['estado_seguimiento'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>

                                            <!-- ACCIONES (VER + QR) -->
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a
                                                        href="../../areas.php/seguimientoPrimerNivel.php/ver_detectores.php?id=<?= (int) $detector['id_detector'] ?>"
                                                        class="btn btn-outline-primary btn-sm"
                                                        title="Ver detector"
                                                    >
                                                        <i class="fas fa-eye me-1"></i>
                                                        Ver
                                                    </a>

                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-dark btn-sm"
                                                        onclick="mostrarQR(
                                                            <?= (int) $detector['id_detector'] ?>,
                                                            '<?= htmlspecialchars(
                                                                (string) $detector['id_detector'],
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>'
                                                        )"
                                                    >
                                                        <i class="fas fa-qrcode me-1"></i>
                                                        QR
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <!-- SIN REGISTROS -->
                                    <?php if (empty($listaDetectores)): ?>
                                        <tr data-estado="">
                                            <td colspan="11" class="text-center text-muted py-4">
                                                No hay detectores de humo registrados en el Primer Nivel.
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

    <!-- =====================================================
        MODAL DEL QR
        ===================================================== -->
    <div
        class="modal fade"
        id="modalQR"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-qrcode me-2"></i>
                        Código QR del detector
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar"
                    ></button>
                </div>

                <div class="modal-body text-center">
                    <div id="folioQR" class="folio-qr"></div>
                    <div id="contenedorQR"></div>

                    <p class="text-muted mt-3 mb-0">
                        Escanea este código para consultar
                        la información del detector.
                    </p>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cerrar
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- BOOTSTRAP JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- =====================================================
        BUSCADOR, FILTRO Y QR
        ===================================================== -->
    <script>
        /* BUSCADOR */
        const buscador = document.getElementById('buscadorDetectores');

        /* FILTRO DE ESTADO */
        const filtroEstado = document.getElementById('filtroEstado');

        /* FILAS DE LA TABLA */
        const filasTabla = document.querySelectorAll('#tablaDetectores tbody tr');

        /* FUNCIÓN PARA FILTRAR TABLA */
        function filtrarTabla() {
            const textoBusqueda = buscador.value.toLowerCase();
            const estadoSeleccionado = filtroEstado.value;

            filasTabla.forEach(function (fila) {
                const texto = fila.textContent.toLowerCase();
                const estadoFila = fila.dataset.estado || '';

                const coincideTexto = texto.includes(textoBusqueda);
                const coincideEstado =
                    estadoSeleccionado === '' ||
                    estadoFila === estadoSeleccionado;

                fila.style.display =
                    (coincideTexto && coincideEstado) ? '' : 'none';
            });
        }

        buscador.addEventListener('input', filtrarTabla);
        filtroEstado.addEventListener('change', filtrarTabla);

        /* GENERAR QR DEL DETECTOR */
        function mostrarQR(idDetector, folio) {
            const contenedorQR = document.getElementById('contenedorQR');

            /* LIMPIAR QR ANTERIOR */
            contenedorQR.innerHTML = '';

            /* MOSTRAR IDENTIFICADOR */
            document.getElementById('folioQR').textContent =
                'Detector: ' + folio;

            /* IP DE LA COMPUTADORA CON XAMPP */
            const ipServidor = '192.168.1.68';

            /* URL DE LA FICHA DEL DETECTOR DEL PRIMER NIVEL */
            const urlDetector =
                'http://' +
                ipServidor +
                '/recidencias/activos/areas.php/seguimientoPrimerNivel.php/ver_detectores.php?id=' +
                encodeURIComponent(idDetector);

            console.log('URL DEL QR:', urlDetector);

            /* GENERAR QR */
            new QRCode(contenedorQR, {
                text: urlDetector,
                width: 250,
                height: 250
            });

            /* ABRIR MODAL */
            const modal = new bootstrap.Modal(
                document.getElementById('modalQR')
            );

            modal.show();
        }
    </script>

</body>
</html>
