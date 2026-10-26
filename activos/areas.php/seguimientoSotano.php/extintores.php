<?php

/* ==========================================================
   CONEXIÓN
   ========================================================== */

require_once __DIR__ . '/../../../clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();


/* ==========================================================
   CONSULTA DE EXTINTORES DEL SÓTANO — CONTEOS
   ========================================================== */

$sql = "
    SELECT
        COUNT(*) AS total,

        COUNT(*) FILTER (
            WHERE LEAST(
                fecha_proximo_mantenimiento,
                fecha_proxima_recarga
            ) > CURRENT_DATE + INTERVAL '30 days'
        ) AS al_corriente,

        COUNT(*) FILTER (
            WHERE LEAST(
                fecha_proximo_mantenimiento,
                fecha_proxima_recarga
            ) BETWEEN CURRENT_DATE
            AND CURRENT_DATE + INTERVAL '30 days'
        ) AS por_vencer,

        COUNT(*) FILTER (
            WHERE LEAST(
                fecha_proximo_mantenimiento,
                fecha_proxima_recarga
            ) < CURRENT_DATE
        ) AS vencidos

    FROM tb_extintores e

    JOIN tb_areas a
        ON e.id_area = a.id_area

    WHERE a.nivel = 'Sótano'
";

$resultado = pg_query($conexion, $sql);

if ($resultado === false) {
    die('Error al consultar los conteos de extintores.');
}

$conteos = pg_fetch_assoc($resultado);


/* ==========================================================
   CONTEOS
   ========================================================== */

$total_extintores = (int) $conteos['total'];
$al_corriente     = (int) $conteos['al_corriente'];
$por_vencer       = (int) $conteos['por_vencer'];
$vencidos         = (int) $conteos['vencidos'];


/* ==========================================================
   PORCENTAJE DE CUMPLIMIENTO
   ========================================================== */

$cumplimiento = $total_extintores > 0
    ? round(($al_corriente / $total_extintores) * 100)
    : 0;


/* ==========================================================
   OBTENER LOS EXTINTORES DEL SÓTANO
   ========================================================== */

$sqlExtintores = "
    SELECT
        e.id_extintor,
        e.numero_folio AS identificador,

        LEAST(
            e.fecha_proximo_mantenimiento,
            e.fecha_proxima_recarga
        ) AS fecha_critica

    FROM tb_extintores e

    JOIN tb_areas a
        ON e.id_area = a.id_area

    WHERE a.nivel = 'Sótano'

    ORDER BY e.numero_folio ASC
";

$resultadoExtintores = pg_query($conexion, $sqlExtintores);

if ($resultadoExtintores === false) {
    die('Error al consultar el listado de extintores.');
}


/* ==========================================================
   LISTA DE EXTINTORES
   ========================================================== */

$listaExtintores = [];

$hoy      = date('Y-m-d');
$en30Dias = date('Y-m-d', strtotime('+30 days'));

while ($fila = pg_fetch_assoc($resultadoExtintores)) {

    $fechaCritica = $fila['fecha_critica'];

    /* DETERMINAR ESTADO */

    if (empty($fechaCritica)) {

        $estado = 'Sin fecha';
        $fechaFormateada = null;

    } else {

        $fechaFormateada = date('d M Y', strtotime($fechaCritica));

        if ($fechaCritica < $hoy) {
            $estado = 'Vencido';
        } elseif ($fechaCritica <= $en30Dias) {
            $estado = 'Por vencer';
        } else {
            $estado = 'Al corriente';
        }
    }

    $fila['estado'] = $estado;
    $fila['fecha_formateada'] = $fechaFormateada;

    $listaExtintores[] = $fila;
}

?>


<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Extintores - Sótano
    </title>


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


    <!-- ESTILOS -->
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

        <a
            href="../../agre.extintores/extintore1.php"
            class="btn btn-primary"
        >
            <i class="fas fa-plus me-1"></i>
            Agregar
        </a>

    </div>



    <!-- =====================================================
        TABLA DE EXTINTORES
        ===================================================== -->

    <div class="container-fluid">

        <div class="row g-4 mt-1">

            <div class="col-12">

                <div class="card shadow-sm">

                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2 text-primary"></i>
                            Extintores del Sótano
                        </h5>
                    </div>


                    <div class="card-body">

                        <!-- BUSCADOR Y FILTRO -->
                        <div class="row g-2 mb-3">

                            <div class="col-md-6">
                                <input
                                    type="text"
                                    id="buscadorExtintores"
                                    class="form-control"
                                    placeholder="Buscar por folio..."
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
                                id="tablaExtintores"
                            >

                                <thead>
                                    <tr>
                                        <th>Folio</th>
                                        <th>Próx. mantenimiento/recarga</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>


                                <tbody>

                                    <?php foreach ($listaExtintores as $extintor): ?>

                                        <?php

                                        $claseEstado = 'bg-secondary';

                                        if ($extintor['estado'] === 'Al corriente') {
                                            $claseEstado = 'bg-success';
                                        } elseif ($extintor['estado'] === 'Por vencer') {
                                            $claseEstado = 'bg-warning text-dark';
                                        } elseif ($extintor['estado'] === 'Vencido') {
                                            $claseEstado = 'bg-danger';
                                        }

                                        ?>

                                        <tr
                                            data-estado="<?= htmlspecialchars($extintor['estado']) ?>"
                                        >

                                            <!-- FOLIO -->
                                            <td>
                                                <?= htmlspecialchars($extintor['identificador']) ?>
                                            </td>

                                            <!-- FECHA -->
                                            <td>
                                                <?= htmlspecialchars($extintor['fecha_formateada'] ?? '—') ?>
                                            </td>

                                            <!-- ESTADO -->
                                            <td>
                                                <span class="badge <?= $claseEstado ?>">
                                                    <?= htmlspecialchars($extintor['estado']) ?>
                                                </span>
                                            </td>

                                            <!-- ACCIONES (VER + QR) -->
                                            <td>

                                                <div class="d-flex gap-1">

                                                    <a
                                                        href="../../areas.php/seguimientoSotano.php/ver_extinguidores.php?id=<?= (int) $extintor['id_extintor'] ?>"
                                                        class="btn btn-outline-primary btn-sm"
                                                        title="Ver extintor"
                                                    >
                                                        <i class="fas fa-eye me-1"></i>
                                                        Ver
                                                    </a>

                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-dark btn-sm"
                                                        onclick="mostrarQR(
                                                            <?= (int) $extintor['id_extintor'] ?>,
                                                            '<?= htmlspecialchars(
                                                                $extintor['identificador'],
                                                                ENT_QUOTES
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
                                    <?php if (empty($listaExtintores)): ?>

                                        <tr data-estado="">
                                            <td colspan="4" class="text-center text-muted">
                                                No hay extintores registrados en el Sótano.
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
                        Código QR del extintor
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
                        la información del extintor.
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
        const buscador = document.getElementById('buscadorExtintores');

        /* FILTRO DE ESTADO */
        const filtroEstado = document.getElementById('filtroEstado');

        /* FILAS DE LA TABLA */
        const filasTabla = document.querySelectorAll('#tablaExtintores tbody tr');


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


        /* GENERAR QR DEL EXTINTOR */
        function mostrarQR(idExtintor, folio) {

            const contenedorQR = document.getElementById("contenedorQR");

            /* LIMPIAR QR ANTERIOR */
            contenedorQR.innerHTML = "";

            /* MOSTRAR FOLIO */
            document.getElementById("folioQR").textContent =
                "Extintor: " + folio;

            /* IP DE LA COMPUTADORA CON XAMPP */
            const ipServidor = "192.168.1.68";

            /* URL REAL DE LA FICHA DEL EXTINTOR (ver_extinguidores.php) */
            const urlExtintor =
                "http://" +
                ipServidor +
                "/recidencias/activos/areas.php/seguimientoSotano.php/ver_extinguidores.php?id=" +
                idExtintor;

            console.log("URL DEL QR:", urlExtintor);

            /* GENERAR QR */
            new QRCode(contenedorQR, {
                text: urlExtintor,
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