<?php

/* ==========================================================
   CONEXIÓN
   ========================================================== */

require_once __DIR__ . '/../../../clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();


/* ==========================================================
   CONSULTA DE LUCES DE EMERGENCIA DEL SÓTANO — CONTEOS
   ========================================================== */

$sql = "
    SELECT
        COUNT(*) AS total,

        COUNT(*) FILTER (
            WHERE fecha_proxima_revision >
                CURRENT_DATE + INTERVAL '30 days'
        ) AS al_corriente,

        COUNT(*) FILTER (
            WHERE fecha_proxima_revision
                BETWEEN CURRENT_DATE
                AND CURRENT_DATE + INTERVAL '30 days'
        ) AS por_vencer,

        COUNT(*) FILTER (
            WHERE fecha_proxima_revision < CURRENT_DATE
        ) AS vencidos

    FROM tb_luces_emergencia l

    JOIN tb_areas a
        ON l.id_area = a.id_area

    WHERE a.nivel = 'Sótano'
";

$resultado = pg_query($conexion, $sql);

if ($resultado === false) {
    die('Error al consultar los conteos de luces de emergencia.');
}

$conteos = pg_fetch_assoc($resultado);


/* ==========================================================
   CONTEOS
   ========================================================== */

$total_luces = $conteos['total'];
$al_corriente = $conteos['al_corriente'];
$por_vencer   = $conteos['por_vencer'];
$vencidos     = $conteos['vencidos'];


/* ==========================================================
   PORCENTAJE DE CUMPLIMIENTO
   ========================================================== */

$cumplimiento = $total_luces > 0
    ? round(($al_corriente / $total_luces) * 100)
    : 0;


/* ==========================================================
   OBTENER LAS LUCES DE EMERGENCIA DEL SÓTANO
   ========================================================== */

$sqlLuces = "
    SELECT
        l.id_luz AS identificador,

        l.marca,
        l.modelo,
        l.tipo_iluminacion,
        l.estado_fisico,
        l.duracion_bateria,

        l.fecha_proxima_revision AS fecha_critica

    FROM tb_luces_emergencia l

    JOIN tb_areas a
        ON l.id_area = a.id_area

    WHERE a.nivel = 'Sótano'

    ORDER BY l.id_luz ASC
";

$resultadoLuces = pg_query($conexion, $sqlLuces);

if ($resultadoLuces === false) {
    die('Error al consultar el listado de luces de emergencia.');
}


/* ==========================================================
   LISTA DE LUCES DE EMERGENCIA
   ========================================================== */

$listaLuces = [];

while ($fila = pg_fetch_assoc($resultadoLuces)) {

    $fechaCritica = $fila['fecha_critica'];


    /* ======================================================
       DETERMINAR ESTADO
       ====================================================== */

    if (empty($fechaCritica)) {

        $estado = 'Sin fecha';
        $fechaFormateada = null;

    } else {

        $fechaFormateada = date(
            'd M Y',
            strtotime($fechaCritica)
        );


        /* ==================================================
           VENCIDO
           ================================================== */

        if ($fechaCritica < date('Y-m-d')) {

            $estado = 'Vencido';

        }


        /* ==================================================
           POR VENCER
           ================================================== */

        elseif (
            $fechaCritica >= date('Y-m-d') &&
            $fechaCritica <= date(
                'Y-m-d',
                strtotime('+30 days')
            )
        ) {

            $estado = 'Por vencer';

        }


        /* ==================================================
           AL CORRIENTE
           ================================================== */

        else {

            $estado = 'Al corriente';

        }

    }


    $fila['estado'] = $estado;
    $fila['fecha_formateada'] = $fechaFormateada;

    $listaLuces[] = $fila;
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
        Luces de Emergencia - Sótano
    </title>


    <!-- =====================================================
         BOOTSTRAP 5 CSS
         ===================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         FONT AWESOME
         ===================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
    >


    <!-- =====================================================
         ESTILOS
         ===================================================== -->

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

    </style>

</head>


<body>


    <!-- =====================================================
         NAVBAR
         ===================================================== -->

    <nav
        class="navbar navbar-light bg-light shadow-sm fixed-top"
    >

        <div class="container-fluid">

            <div class="border-start ps-3">

                <h6 class="mb-0 titulo-audi">
                    Sótano
                </h6>

            </div>


            <div
                class="ms-auto d-flex align-items-center gap-2"
            >

                <a
                    href="ARCHIVO"
                    download="Reporte"
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


                <a
                    href="../../activos.php"
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


            <!-- =================================================
                 CUMPLIMIENTO
                 ================================================= -->

            <div class="col-lg-3 col-md-6">

                <div
                    class="card p-3 border-start border-success border-4 shadow-sm"
                >

                    <small class="text-muted">
                        Cumplimiento
                    </small>

                    <h3 class="fw-bold text-success m-0">

                        <?= $cumplimiento ?>%

                    </h3>

                </div>

            </div>


            <!-- =================================================
                 AL CORRIENTE
                 ================================================= -->

            <div class="col-lg-3 col-md-6">

                <div
                    class="card p-3 border-start border-success border-4 shadow-sm"
                >

                    <small class="text-muted">
                        Al corriente
                    </small>

                    <h3 class="fw-bold m-0">

                        <?= $al_corriente ?>

                    </h3>

                </div>

            </div>


            <!-- =================================================
                 POR VENCER
                 ================================================= -->

            <div class="col-lg-3 col-md-6">

                <div
                    class="card p-3 border-start border-warning border-4 shadow-sm"
                >

                    <small class="text-muted">
                        Por vencer
                    </small>

                    <h3 class="fw-bold text-warning m-0">

                        <?= $por_vencer ?>

                    </h3>

                </div>

            </div>


            <!-- =================================================
                 VENCIDOS
                 ================================================= -->

            <div class="col-lg-3 col-md-6">

                <div
                    class="card p-3 border-start border-danger border-4 shadow-sm"
                >

                    <small class="text-muted">
                        Vencidos
                    </small>

                    <h3 class="fw-bold text-danger m-0">

                        <?= $vencidos ?>

                    </h3>

                </div>

            </div>

        </div>

    </div>



    <!-- =====================================================
         TABLA DE BOTIQUINES
         ===================================================== -->

    <div class="container-fluid">

        <div class="row g-4 mt-1">

            <div class="col-12">

                <div class="card shadow-sm">


                    <!-- =================================================
                         ENCABEZADO
                         ================================================= -->

                    <div class="card-header bg-white">

                        <h5 class="mb-0">

                            <i
                                class="fas fa-first-aid me-2 text-primary"
                            ></i>

                            Botiquines del Sótano

                        </h5>

                    </div>


                    <div class="card-body">


                        <!-- =================================================
                             BUSCADOR Y FILTRO
                             ================================================= -->

                        <div class="row g-2 mb-3">

                            <div class="col-md-6">

                                <input
                                    type="text"
                                    id="buscadorBotiquines"
                                    class="form-control"
                                    placeholder="Buscar por ID de botiquín..."
                                >

                            </div>


                            <div class="col-md-4">

                                <select
                                    id="filtroEstado"
                                    class="form-select"
                                >

                                    <option value="">
                                        Todos los estados
                                    </option>

                                    <option value="Al corriente">
                                        Al corriente
                                    </option>

                                    <option value="Por vencer">
                                        Por vencer
                                    </option>

                                    <option value="Vencido">
                                        Vencido
                                    </option>

                                    <option value="Sin fecha">
                                        Sin fecha
                                    </option>

                                </select>

                            </div>

                        </div>



                        <!-- =================================================
                             TABLA
                             ================================================= -->

                        <div class="table-responsive">

                            <table
                                class="table table-hover align-middle"
                                id="tablaBotiquines"
                            >

                                <thead>

                                    <tr>

                                        <th>
                                            ID Botiquín
                                        </th>

                                        <th>
                                            Próxima revisión
                                        </th>

                                        <th>
                                            Estado
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>


                                    <?php foreach (
                                        $listaLuces
                                        as $luz
                                    ): ?>


                                        <?php

                                        $claseEstado = 'bg-secondary';


                                        if (
                                            $luz['estado']
                                            === 'Al corriente'
                                        ) {

                                            $claseEstado =
                                                'bg-success';

                                        } elseif (
                                            $luz ['estado']
                                            === 'Por vencer'
                                        ) {

                                            $claseEstado =
                                                'bg-warning text-dark';

                                        } elseif (
                                            $luz['estado']
                                            === 'Vencido'
                                        ) {

                                            $claseEstado =
                                                'bg-danger';

                                        }

                                        ?>


                                        <tr
                                            data-estado="<?= htmlspecialchars(
                                                $luz['estado']
                                            ) ?>"
                                        >


                                            <!-- ID LUZ -->

                                            <td>

                                                <?= htmlspecialchars(
                                                    $luz['identificador']
                                                ) ?>

                                            </td>


                                            <!-- FECHA PRÓXIMA REVISIÓN -->

                                            <td>

                                                <?= htmlspecialchars(
                                                    $luz['fecha_formateada']
                                                    ?? '—'
                                                ) ?>

                                            </td>


                                            <!-- ESTADO -->

                                            <td>

                                                <span
                                                    class="badge <?= $claseEstado ?>"
                                                >

                                                    <?= htmlspecialchars(
                                                        $luz['estado']
                                                    ) ?>

                                                </span>

                                            </td>

                                        </tr>


                                    <?php endforeach; ?>


                                    <?php if (
                                        empty($listaLuces)
                                    ): ?>

                                        <tr>

                                            <td
                                                colspan="3"
                                                class="text-center text-muted"
                                            >

                                                No hay luces registradas
                                                en el Sótano.

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
         BOOTSTRAP JS
         ===================================================== -->

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js">
    </script>



    <!-- =====================================================
         BUSCADOR Y FILTRO
         ===================================================== -->

    <script>

        const buscador =
            document.getElementById(
                'buscadorBotiquines'
            );


        const filtroEstado =
            document.getElementById(
                'filtroEstado'
            );


        const filasTabla =
            document.querySelectorAll(
                '#tablaBotiquines tbody tr'
            );


        function filtrarTabla() {

            const textoBusqueda =
                buscador.value.toLowerCase();


            const estadoSeleccionado =
                filtroEstado.value;


            filasTabla.forEach(function(fila) {


                const texto =
                    fila.textContent.toLowerCase();


                const estadoFila =
                    fila.dataset.estado || '';


                const coincideTexto =
                    texto.includes(
                        textoBusqueda
                    );


                const coincideEstado =
                    (estadoSeleccionado === '') ||
                    (estadoFila === estadoSeleccionado);


                if (
                    coincideTexto &&
                    coincideEstado
                ) {

                    fila.style.display = '';

                } else {

                    fila.style.display = 'none';

                }

            });

        }


        buscador.addEventListener(
            'input',
            filtrarTabla
        );


        filtroEstado.addEventListener(
            'change',
            filtrarTabla
        );

    </script>


</body>

</html>