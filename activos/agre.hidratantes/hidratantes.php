
<?php

/* ==========================================================
   CONEXIÓN
   ========================================================== */

require_once __DIR__ . '/../../../clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();


/* ==========================================================
   CONSULTA DE HIDRANTES DEL SÓTANO — CONTEOS
   ========================================================== */

$sql = "
    SELECT
        COUNT(*) AS total,

        COUNT(*) FILTER (
            WHERE fecha_proxima_revision >
                CURRENT_DATE + 30
        ) AS al_corriente,

        COUNT(*) FILTER (
            WHERE fecha_proxima_revision
                BETWEEN CURRENT_DATE
                AND CURRENT_DATE + 30
        ) AS por_vencer,

        COUNT(*) FILTER (
            WHERE fecha_proxima_revision < CURRENT_DATE
        ) AS vencidos

    FROM public.tb_hidrantes h

    JOIN public.tb_areas a
        ON h.id_area = a.id_area

    WHERE a.nivel = 'Sótano'
";

$resultado = pg_query($conexion, $sql);

if ($resultado === false) {
    die('Error al consultar los conteos de hidrantes.');
}

$conteos = pg_fetch_assoc($resultado);


/* ==========================================================
   CONTEOS
   ========================================================== */

$total_hidrantes = (int) $conteos['total'];
$al_corriente    = (int) $conteos['al_corriente'];
$por_vencer      = (int) $conteos['por_vencer'];
$vencidos        = (int) $conteos['vencidos'];


/* ==========================================================
   PORCENTAJE DE CUMPLIMIENTO
   ========================================================== */

$cumplimiento = $total_hidrantes > 0
    ? round(($al_corriente / $total_hidrantes) * 100)
    : 0;


/* ==========================================================
   OBTENER LOS HIDRANTES DEL SÓTANO
   ========================================================== */

$sqlHidrantes = "
    SELECT
        h.id_hidrante,
        h.numero_folio AS identificador,
        h.fecha_proxima_revision AS fecha_critica

    FROM public.tb_hidrantes h

    JOIN public.tb_areas a
        ON h.id_area = a.id_area

    WHERE a.nivel = 'Sótano'

    ORDER BY h.id_hidrante ASC
";

$resultadoHidrantes = pg_query($conexion, $sqlHidrantes);

if ($resultadoHidrantes === false) {
    die('Error al consultar el listado de hidrantes.');
}


/* ==========================================================
   LISTA DE HIDRANTES
   ========================================================== */

$listaHidrantes = [];

while ($fila = pg_fetch_assoc($resultadoHidrantes)) {

    $fechaCritica = $fila['fecha_critica'];


    /* ======================================================
       DETERMINAR ESTADO
       ====================================================== */

    if (empty($fechaCritica)) {

        $estado = 'Sin fecha';
        $fechaFormateada = null;

    } else {

        $fechaFormateada = date(
            'd/m/Y',
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

    $listaHidrantes[] = $fila;
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

    <title>Hidrantes - Sótano</title>


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


            <div class="ms-auto d-flex align-items-center gap-2">

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
         TABLA DE HIDRANTES
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

                            <i class="fas fa-fire-extinguisher me-2 text-primary"></i>

                            Hidrantes del Sótano

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
                                    id="buscadorHidrantes"
                                    class="form-control"
                                    placeholder="Buscar por folio de hidrante..."
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
                                id="tablaHidrantes"
                            >

                                <thead>

                                    <tr>

                                        <th>
                                            Folio del hidrante
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

                                    <?php foreach ($listaHidrantes as $hidrante): ?>

                                        <?php

                                        $claseEstado = 'bg-secondary';

                                        if ($hidrante['estado'] === 'Al corriente') {

                                            $claseEstado = 'bg-success';

                                        } elseif ($hidrante['estado'] === 'Por vencer') {

                                            $claseEstado = 'bg-warning text-dark';

                                        } elseif ($hidrante['estado'] === 'Vencido') {

                                            $claseEstado = 'bg-danger';

                                        }

                                        ?>


                                        <tr
                                            data-estado="<?= htmlspecialchars($hidrante['estado'], ENT_QUOTES, 'UTF-8') ?>"
                                        >


                                            <!-- FOLIO HIDRANTE -->

                                            <td>
                                                <?= htmlspecialchars($hidrante['identificador'], ENT_QUOTES, 'UTF-8') ?>
                                            </td>


                                            <!-- FECHA PRÓXIMA REVISIÓN -->

                                            <td>
                                                <?= htmlspecialchars($hidrante['fecha_formateada'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                            </td>


                                            <!-- ESTADO -->

                                            <td>

                                                <span class="badge <?= $claseEstado ?>">

                                                    <?= htmlspecialchars($hidrante['estado'], ENT_QUOTES, 'UTF-8') ?>

                                                </span>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>


                                    <?php if (empty($listaHidrantes)): ?>

                                        <tr>

                                            <td
                                                colspan="3"
                                                class="text-center text-muted"
                                            >

                                                No hay hidrantes registrados
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

        const buscador = document.getElementById(
            'buscadorHidrantes'
        );

        const filtroEstado = document.getElementById(
            'filtroEstado'
        );

        const filasTabla = document.querySelectorAll(
            '#tablaHidrantes tbody tr[data-estado]'
        );


        function filtrarTabla() {

            const textoBusqueda = buscador.value.toLowerCase();

            const estadoSeleccionado = filtroEstado.value;

            filasTabla.forEach(function(fila) {

                const texto = fila.textContent.toLowerCase();

                const estadoFila = fila.dataset.estado || '';

                const coincideTexto = texto.includes(textoBusqueda);

                const coincideEstado =
                    estadoSeleccionado === '' ||
                    estadoFila === estadoSeleccionado;

                fila.style.display =
                    coincideTexto && coincideEstado ? '' : 'none';

            });

        }


        buscador.addEventListener('input', filtrarTabla);

        filtroEstado.addEventListener('change', filtrarTabla);

    </script>


</body>

</html>
