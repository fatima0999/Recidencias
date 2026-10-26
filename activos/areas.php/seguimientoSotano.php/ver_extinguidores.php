<?php

/* ==========================================================
   CONEXIÓN
   ========================================================== */

require_once __DIR__ . '/../../../clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();


/* ==========================================================
   VALIDAR ID
   ========================================================== */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    die('Extintor no válido.');

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
        e.estado_fisico,
        e.fecha_ultimo_mantenimiento,
        e.fecha_proximo_mantenimiento,

        a.nombre_area,
        a.nivel,

        p.nombre_proveedor

    FROM tb_extintores e

    LEFT JOIN tb_areas a
        ON e.id_area = a.id_area

    LEFT JOIN tb_proveedores p
        ON e.id_proveedor = p.id_proveedor

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

    die('El extintor no existe.');

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
        Extintor <?= htmlspecialchars($extintor['numero_folio']) ?>
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>


<body class="bg-light">


<div class="container py-4">


    <!-- =====================================================
         ENCABEZADO
         ===================================================== -->

    <div class="card shadow-sm mb-4">

        <div class="card-body text-center">

            <h2 class="mb-1">

                Extintor

            </h2>

            <h4 class="text-primary">

                <?= htmlspecialchars(
                    $extintor['numero_folio']
                ) ?>

            </h4>

        </div>

    </div>



    <!-- =====================================================
         INFORMACIÓN
         ===================================================== -->

    <div class="card shadow-sm">

        <div class="card-header">

            <strong>
                Información del extintor
            </strong>

        </div>


        <div class="card-body">


            <div class="row g-3">


                <!-- FOLIO -->

                <div class="col-md-6">

                    <strong>
                        Folio
                    </strong>

                    <p class="mb-0">

                        <?= htmlspecialchars(
                            $extintor['numero_folio']
                        ) ?>

                    </p>

                </div>


                <!-- TIPO -->

                <div class="col-md-6">

                    <strong>
                        Tipo de agente
                    </strong>

                    <p class="mb-0">

                        <?= htmlspecialchars(
                            $extintor['tipo_agente'] ?? '—'
                        ) ?>

                    </p>

                </div>


                <!-- CAPACIDAD -->

                <div class="col-md-6">

                    <strong>
                        Capacidad
                    </strong>

                    <p class="mb-0">

                        <?= htmlspecialchars(
                            $extintor['capacidad'] ?? '—'
                        ) ?>

                    </p>

                </div>


                <!-- MARCA -->

                <div class="col-md-6">

                    <strong>
                        Marca
                    </strong>

                    <p class="mb-0">

                        <?= htmlspecialchars(
                            $extintor['marca'] ?? '—'
                        ) ?>

                    </p>

                </div>


                <!-- MODELO -->

                <div class="col-md-6">

                    <strong>
                        Modelo
                    </strong>

                    <p class="mb-0">

                        <?= htmlspecialchars(
                            $extintor['modelo'] ?? '—'
                        ) ?>

                    </p>

                </div>


                <!-- ESTADO -->

                <div class="col-md-6">

                    <strong>
                        Estado físico
                    </strong>

                    <p class="mb-0">

                        <?= htmlspecialchars(
                            $extintor['estado_fisico'] ?? '—'
                        ) ?>

                    </p>

                </div>


                <!-- PISO -->

                <div class="col-md-6">

                    <strong>
                        Piso
                    </strong>

                    <p class="mb-0">

                        <?= htmlspecialchars(
                            $extintor['nivel'] ?? '—'
                        ) ?>

                    </p>

                </div>


                <!-- ÁREA -->

                <div class="col-md-6">

                    <strong>
                        Área
                    </strong>

                    <p class="mb-0">

                        <?= htmlspecialchars(
                            $extintor['nombre_area'] ?? '—'
                        ) ?>

                    </p>

                </div>


                <!-- PROVEEDOR -->

                <div class="col-md-6">

                    <strong>
                        Proveedor
                    </strong>

                    <p class="mb-0">

                        <?= htmlspecialchars(
                            $extintor['nombre_proveedor'] ?? '—'
                        ) ?>

                    </p>

                </div>


                <!-- ÚLTIMO MANTENIMIENTO -->

                <div class="col-md-6">

                    <strong>
                        Último mantenimiento
                    </strong>

                    <p class="mb-0">

                        <?= htmlspecialchars(
                            $extintor['fecha_ultimo_mantenimiento']
                            ?? '—'
                        ) ?>

                    </p>

                </div>


                <!-- PRÓXIMO MANTENIMIENTO -->

                <div class="col-md-6">

                    <strong>
                        Próximo mantenimiento
                    </strong>

                    <p class="mb-0">

                        <?= htmlspecialchars(
                            $extintor['fecha_proximo_mantenimiento']
                            ?? '—'
                        ) ?>

                    </p>

                </div>


            </div>


        </div>

    </div>



    <!-- =====================================================
         BOTONES
         ===================================================== -->

    <div class="card shadow-sm mt-4">

        <div class="card-body text-center">


            <!-- BOTÓN ACTUALIZAR -->

            <a
                href="editar_extintor.php?id=<?= $idExtintor ?>"
                class="btn btn-primary"
            >

                Actualizar extintor

            </a>


            <!-- BOTÓN REGRESAR -->

            <a
                href="extintores.php"
                class="btn btn-secondary"
            >

                Regresar

            </a>


        </div>

    </div>


</div>


</body>

</html>