<?php

/* ==========================================================
   CONEXIÓN
   ========================================================== */

require_once __DIR__ . '/../../../clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();


/* ==========================================================
   VALIDAR ID DEL EXTINTOR
   ========================================================== */

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {

    die('ID de extintor no válido.');

}


$idExtintor = (int) $_GET['id'];


/* ==========================================================
   CONSULTAR EXTINTOR
   ========================================================== */

$sql = "
    SELECT
        e.id_extintor,
        e.numero_folio,
        e.tipo_agente,
        e.capacidad,
        e.marca,
        e.modelo,
        e.id_area,
        e.id_proveedor,
        e.estado_fisico,
        e.fecha_ultimo_mantenimiento,
        e.fecha_proximo_mantenimiento

    FROM tb_extintores e

    WHERE e.id_extintor = $1
";


$resultado = pg_query_params(
    $conexion,
    $sql,
    [$idExtintor]
);


if ($resultado === false) {

    die('Error al consultar el extintor.');

}


$extintor = pg_fetch_assoc($resultado);


if (!$extintor) {

    die('Extintor no encontrado.');

}


/* ==========================================================
   CONSULTAR ÁREAS
   ========================================================== */

$sqlAreas = "
    SELECT
        id_area,
        nombre_area,
        nivel

    FROM tb_areas

    WHERE activo = true

    ORDER BY
        nivel,
        nombre_area
";


$resultadoAreas = pg_query(
    $conexion,
    $sqlAreas
);


if ($resultadoAreas === false) {

    die('Error al consultar las áreas.');

}


/* ==========================================================
   CONSULTAR PROVEEDORES
   ========================================================== */

$sqlProveedores = "
    SELECT
        id_proveedor,
        nombre_proveedor

    FROM tb_proveedores

    WHERE activo = true

    ORDER BY nombre_proveedor
";


$resultadoProveedores = pg_query(
    $conexion,
    $sqlProveedores
);


if ($resultadoProveedores === false) {

    die('Error al consultar los proveedores.');

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
        Actualizar extintor
    </title>


    <!-- =====================================================
        BOOTSTRAP
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


    <style>

        body {

            background-color: #f8f9fa;

            padding-top: 80px;

        }


        .contenedor-formulario {

            max-width: 900px;

            margin: auto;

        }


        .titulo {

            font-weight: bold;

        }

    </style>

</head>


<body>


    <!-- =====================================================
        NAVBAR
        ===================================================== -->

    <nav
        class="navbar navbar-light bg-white shadow-sm fixed-top"
    >

        <div class="container-fluid">

            <div>

                <h6 class="mb-0">

                    SIPV

                </h6>

                <small class="text-muted">

                    Actualizar extintor

                </small>

            </div>


            <a
                href="ver_extinguidores.php?id=<?= $idExtintor ?>"
                class="btn btn-outline-danger"
            >

                <i class="fas fa-arrow-left me-1"></i>

                Volver

            </a>

        </div>

    </nav>



    <!-- =====================================================
        CONTENEDOR
        ===================================================== -->

    <div class="container py-4">

        <div class="contenedor-formulario">


            <!-- =================================================
                TÍTULO
                ================================================= -->

            <div class="mb-4">

                <h3 class="titulo">

                    <i
                        class="fas fa-edit text-primary me-2"
                    ></i>

                    Actualizar extintor

                </h3>

                <p class="text-muted mb-0">

                    Modifica la información del extintor
                    y guarda los cambios.

                </p>

            </div>



            <!-- =================================================
                FORMULARIO
                ================================================= -->

            <form
                action="actualizar_extintor.php"
                method="POST"
            >


                <!-- =================================================
                    ID OCULTO
                    ================================================= -->

                <input
                    type="hidden"
                    name="id_extintor"
                    value="<?= (int) $extintor['id_extintor'] ?>"
                >



                <div class="card shadow-sm">

                    <div class="card-body">

                        <div class="row g-3">


                            <!-- =====================================
                                FOLIO
                                ===================================== -->

                            <div class="col-md-6">

                                <label
                                    class="form-label fw-bold"
                                >

                                    Folio

                                </label>

                                <input
                                    type="text"
                                    name="numero_folio"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $extintor['numero_folio']
                                    ) ?>"
                                    required
                                >

                            </div>



                            <!-- =====================================
                                TIPO DE AGENTE
                                ===================================== -->

                            <div class="col-md-6">

                                <label
                                    class="form-label fw-bold"
                                >

                                    Tipo de agente

                                </label>

                                <input
                                    type="text"
                                    name="tipo_agente"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $extintor['tipo_agente'] ?? ''
                                    ) ?>"
                                >

                            </div>



                            <!-- =====================================
                                CAPACIDAD
                                ===================================== -->

                            <div class="col-md-6">

                                <label
                                    class="form-label fw-bold"
                                >

                                    Capacidad

                                </label>

                                <input
                                    type="number"
                                    step="0.01"
                                    name="capacidad"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $extintor['capacidad'] ?? ''
                                    ) ?>"
                                >

                            </div>



                            <!-- =====================================
                                MARCA
                                ===================================== -->

                            <div class="col-md-6">

                                <label
                                    class="form-label fw-bold"
                                >

                                    Marca

                                </label>

                                <input
                                    type="text"
                                    name="marca"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $extintor['marca'] ?? ''
                                    ) ?>"
                                >

                            </div>



                            <!-- =====================================
                                MODELO
                                ===================================== -->

                            <div class="col-md-6">

                                <label
                                    class="form-label fw-bold"
                                >

                                    Modelo

                                </label>

                                <input
                                    type="text"
                                    name="modelo"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $extintor['modelo'] ?? ''
                                    ) ?>"
                                >

                            </div>



                            <!-- =====================================
                                ESTADO FÍSICO
                                ===================================== -->

                            <div class="col-md-6">

                                <label
                                    class="form-label fw-bold"
                                >

                                    Estado físico

                                </label>

                                <select
                                    name="estado_fisico"
                                    class="form-select"
                                >

                                    <option
                                        value="Bueno"
                                        <?= (
                                            $extintor['estado_fisico']
                                            === 'Bueno'
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        Bueno

                                    </option>


                                    <option
                                        value="Regular"
                                        <?= (
                                            $extintor['estado_fisico']
                                            === 'Regular'
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        Regular

                                    </option>


                                    <option
                                        value="Malo"
                                        <?= (
                                            $extintor['estado_fisico']
                                            === 'Malo'
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        Malo

                                    </option>

                                </select>

                            </div>



                            <!-- =====================================
                                ÁREA
                                ===================================== -->

                            <div class="col-md-6">

                                <label
                                    class="form-label fw-bold"
                                >

                                    Área

                                </label>

                                <select
                                    name="id_area"
                                    class="form-select"
                                    required
                                >

                                    <option value="">

                                        Seleccionar área

                                    </option>


                                    <?php while (
                                        $area =
                                        pg_fetch_assoc(
                                            $resultadoAreas
                                        )
                                    ): ?>

                                        <option
                                            value="<?= (int) $area['id_area'] ?>"
                                            <?= (
                                                (int) $extintor['id_area']
                                                ===
                                                (int) $area['id_area']
                                            )
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >

                                            <?= htmlspecialchars(
                                                $area['nivel']
                                                . ' - '
                                                . $area['nombre_area']
                                            ) ?>

                                        </option>

                                    <?php endwhile; ?>

                                </select>

                            </div>



                            <!-- =====================================
                                PROVEEDOR
                                ===================================== -->

                            <div class="col-md-6">

                                <label
                                    class="form-label fw-bold"
                                >

                                    Proveedor

                                </label>

                                <select
                                    name="id_proveedor"
                                    class="form-select"
                                >

                                    <option value="">

                                        Seleccionar proveedor

                                    </option>


                                    <?php while (
                                        $proveedor =
                                        pg_fetch_assoc(
                                            $resultadoProveedores
                                        )
                                    ): ?>

                                        <option
                                            value="<?= (int) $proveedor['id_proveedor'] ?>"
                                            <?= (
                                                (int) $extintor['id_proveedor']
                                                ===
                                                (int) $proveedor['id_proveedor']
                                            )
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >

                                            <?= htmlspecialchars(
                                                $proveedor['nombre_proveedor']
                                            ) ?>

                                        </option>

                                    <?php endwhile; ?>

                                </select>

                            </div>



                            <!-- =====================================
                                ÚLTIMO MANTENIMIENTO
                                ===================================== -->

                            <div class="col-md-6">

                                <label
                                    class="form-label fw-bold"
                                >

                                    Último mantenimiento

                                </label>

                                <input
                                    type="date"
                                    name="fecha_ultimo_mantenimiento"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $extintor[
                                            'fecha_ultimo_mantenimiento'
                                        ] ?? ''
                                    ) ?>"
                                >

                            </div>



                            <!-- =====================================
                                PRÓXIMO MANTENIMIENTO
                                ===================================== -->

                            <div class="col-md-6">

                                <label
                                    class="form-label fw-bold"
                                >

                                    Próximo mantenimiento

                                </label>

                                <input
                                    type="date"
                                    name="fecha_proximo_mantenimiento"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $extintor[
                                            'fecha_proximo_mantenimiento'
                                        ] ?? ''
                                    ) ?>"
                                >

                            </div>


                        </div>

                    </div>


                    <!-- =================================================
                        BOTONES
                        ================================================= -->

                    <div class="card-footer bg-white">

                        <div
                            class="d-flex justify-content-end gap-2"
                        >

                            <a
                                href="ver_extinguidores.php?id=<?= $idExtintor ?>"
                                class="btn btn-secondary"
                            >

                                Cancelar

                            </a>


                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i
                                    class="fas fa-save me-1"
                                ></i>

                                Guardar cambios

                            </button>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>


</body>

</html>