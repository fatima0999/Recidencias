<?php
/* ==========================================================
   CONEXIÓN
   ========================================================== */

require_once __DIR__ . '/../../../clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();

/* ==========================================================
   MÉTRICAS DE LUCES DE EMERGENCIA DEL SÓTANO
   Solo cuenta registros asociados a áreas cuyo nivel sea Sótano.
   ========================================================== */

$sqlConteos = "
    SELECT
        COUNT(*) AS total,
        COUNT(*) FILTER (
            WHERE l.fecha_proxima_revision > CURRENT_DATE + INTERVAL '30 days'
        ) AS al_corriente,
        COUNT(*) FILTER (
            WHERE l.fecha_proxima_revision BETWEEN CURRENT_DATE
              AND CURRENT_DATE + INTERVAL '30 days'
        ) AS por_vencer,
        COUNT(*) FILTER (
            WHERE l.fecha_proxima_revision < CURRENT_DATE
        ) AS vencidos
    FROM tb_luces_emergencia l
    INNER JOIN tb_areas a ON l.id_area = a.id_area
    WHERE a.nivel = 'Sótano'
";

$resultadoConteos = pg_query($conexion, $sqlConteos);

if ($resultadoConteos === false) {
    die('Error al consultar las métricas de luces de emergencia. Verifica la tabla tb_luces_emergencia y la conexión.');
}

$conteos = pg_fetch_assoc($resultadoConteos);

$total_luces  = (int) ($conteos['total'] ?? 0);
$al_corriente = (int) ($conteos['al_corriente'] ?? 0);
$por_vencer   = (int) ($conteos['por_vencer'] ?? 0);
$vencidos     = (int) ($conteos['vencidos'] ?? 0);

$cumplimiento = $total_luces > 0
    ? round(($al_corriente / $total_luces) * 100)
    : 0;

/* ==========================================================
   LISTADO DE LUCES DE EMERGENCIA DEL SÓTANO
   ========================================================== */

$sqlLuces = "
    SELECT
        l.id_luz,
        l.tipo_iluminacion,
        l.marca,
        l.modelo,
        l.duracion_bateria,
        l.estado_fisico,
        l.fecha_instalacion,
        l.fecha_ultima_revision,
        l.fecha_proxima_revision,
        l.evidencia_foto,
        l.observaciones,
        a.nombre_area
    FROM tb_luces_emergencia l
    INNER JOIN tb_areas a ON l.id_area = a.id_area
    WHERE a.nivel = 'Sótano'
    ORDER BY l.id_luz ASC
";

$resultadoLuces = pg_query($conexion, $sqlLuces);

if ($resultadoLuces === false) {
    die('Error al consultar el registro de luces de emergencia. Verifica los nombres de las columnas de tb_luces_emergencia y tb_areas.');
}

$listaLuces = [];

$hoy    = date('Y-m-d');
$limite = date('Y-m-d', strtotime('+30 days'));

while ($fila = pg_fetch_assoc($resultadoLuces)) {

    $fecha = $fila['fecha_proxima_revision'];

    if (empty($fecha)) {

        $estadoSeguimiento = 'Sin fecha';
        $fechaFormateada = '—';

    } else {

        $fechaFormateada = date('d/m/Y', strtotime($fecha));

        if ($fecha < $hoy) {
            $estadoSeguimiento = 'Vencido';
        } elseif ($fecha <= $limite) {
            $estadoSeguimiento = 'Por vencer';
        } else {
            $estadoSeguimiento = 'Al corriente';
        }
    }

    $fila['estado_seguimiento'] = $estadoSeguimiento;
    $fila['fecha_formateada'] = $fechaFormateada;

    $listaLuces[] = $fila;
}
?>
<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Luces de Emergencia - Sótano</title>


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
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js">
    </script>


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
                    Sótano
                </h6>

            </div>


            <div class="ms-auto d-flex align-items-center gap-2">

                <a
                    href="../../areas.php/sotano.php"
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

        <!-- CAMBIA ESTA RUTA si tu formulario de alta está en otra carpeta -->
        <a
            href="../../agre.luc.Emegen/luc.Emeregencia.php"
            class="btn btn-primary"
        >
            <i class="fas fa-plus me-1"></i>
            Agregar
        </a>

    </div>



    <!-- =====================================================
        TABLA DE LUCES DE EMERGENCIA
        ===================================================== -->

    <div class="container-fluid">

        <div class="row g-4 mt-1">

            <div class="col-12">

                <div class="card shadow-sm mb-5">

                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2 text-primary"></i>
                            Luces de emergencia del Sótano
                        </h5>
                    </div>


                    <div class="card-body">

                        <!-- BUSCADOR Y FILTRO -->
                        <div class="row g-2 mb-3">

                            <div class="col-md-6">
                                <input
                                    type="text"
                                    id="buscadorLuces"
                                    class="form-control"
                                    placeholder="Buscar por ID, área, marca o modelo..."
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
                                id="tablaLuces"
                            >

                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Área</th>
                                        <th>Tipo de iluminación</th>
                                        <th>Marca</th>
                                        <th>Modelo</th>
                                        <th>Duración de batería</th>
                                        <th>Estado físico</th>
                                        <th>Próx. revisión</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>


                                <tbody>

                                    <?php foreach ($listaLuces as $luz): ?>

                                        <?php

                                        $claseEstado = 'bg-secondary';

                                        if ($luz['estado_seguimiento'] === 'Al corriente') {
                                            $claseEstado = 'bg-success';
                                        } elseif ($luz['estado_seguimiento'] === 'Por vencer') {
                                            $claseEstado = 'bg-warning text-dark';
                                        } elseif ($luz['estado_seguimiento'] === 'Vencido') {
                                            $claseEstado = 'bg-danger';
                                        }

                                        ?>

                                        <tr data-estado="<?= htmlspecialchars($luz['estado_seguimiento'], ENT_QUOTES, 'UTF-8') ?>">

                                            <td><?= htmlspecialchars($luz['id_luz'], ENT_QUOTES, 'UTF-8') ?></td>

                                            <td><?= htmlspecialchars($luz['nombre_area'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>

                                            <td><?= htmlspecialchars($luz['tipo_iluminacion'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>

                                            <td><?= htmlspecialchars($luz['marca'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>

                                            <td><?= htmlspecialchars($luz['modelo'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>

                                            <td><?= htmlspecialchars($luz['duracion_bateria'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>

                                            <td><?= htmlspecialchars($luz['estado_fisico'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>

                                            <td><?= htmlspecialchars($luz['fecha_formateada'], ENT_QUOTES, 'UTF-8') ?></td>

                                            <td>
                                                <span class="badge <?= $claseEstado ?>">
                                                    <?= htmlspecialchars($luz['estado_seguimiento'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>

                                            <!-- ACCIONES (VER + QR) -->
                                            <td>

                                                <div class="d-flex gap-1">

                                                    <a
                                                        href="../../areas.php/seguimientoSotano.php/ver_luces.php?id=<?= (int) $luz['id_luz'] ?>"
                                                        class="btn btn-outline-primary btn-sm"
                                                        title="Ver luz de emergencia"
                                                    >
                                                        <i class="fas fa-eye me-1"></i>
                                                        Ver
                                                    </a>

                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-dark btn-sm"
                                                        onclick="mostrarQR(
                                                            <?= (int) $luz['id_luz'] ?>,
                                                            '<?= htmlspecialchars(
                                                                $luz['id_luz'],
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
                                    <?php if (empty($listaLuces)): ?>

                                        <tr data-estado="">
                                            <td colspan="10" class="text-center text-muted py-4">
                                                No hay luces de emergencia registradas en el Sótano.
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
                        Código QR de la luz de emergencia
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
                        la información de la luz de emergencia.
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
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js">
    </script>



    <!-- =====================================================
        BUSCADOR, FILTRO Y QR
        ===================================================== -->

    <script>

        /* BUSCADOR */
        const buscador = document.getElementById('buscadorLuces');

        /* FILTRO DE ESTADO */
        const filtroEstado = document.getElementById('filtroEstado');

        /* FILAS DE LA TABLA */
        const filasTabla = document.querySelectorAll('#tablaLuces tbody tr');


        /* FUNCIÓN PARA FILTRAR TABLA */
        function filtrarTabla() {

            const textoBusqueda = buscador.value.toLowerCase().trim();
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


        /* GENERAR QR DE LA LUZ DE EMERGENCIA */
        function mostrarQR(idLuz, folio) {

            const contenedorQR = document.getElementById("contenedorQR");

            /* LIMPIAR QR ANTERIOR */
            contenedorQR.innerHTML = "";

            /* MOSTRAR IDENTIFICADOR */
            document.getElementById("folioQR").textContent =
                "Luz de emergencia: " + folio;

            /* IP DE LA COMPUTADORA CON XAMPP */
            const ipServidor = "192.168.1.68";

            /* URL DE LA FICHA DE LA LUZ (ver_luces.php) */
            const urlLuz =
                "http://" +
                ipServidor +
                "/recidencias/activos/areas.php/seguimientoSotano.php/ver_luces.php?id=" +
                idLuz;

            console.log("URL DEL QR:", urlLuz);

            /* GENERAR QR */
            new QRCode(contenedorQR, {
                text: urlLuz,
                width: 250,
                height: 250
            });

            /* ABRIR MODAL */
            const modal = new bootstrap.Modal(
                document.getElementById("modalQR")
            );

            modal.show();

        }

    </script>


</body>

</html>