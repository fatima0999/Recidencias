
<?php
/* ==========================================================
   SIPV - TODOS LOS EXTINTORES
   ========================================================== */

require_once __DIR__ . '/../../clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();

$listaActivos = [];
$errorConsulta = '';

/* ==========================================================
   CONSULTAR TODOS LOS EXTINTORES
   ========================================================== */

$sql = "
    SELECT
        e.numero_folio AS identificador,
        'Extintor' AS tipo_activo,
        COALESCE(a.nombre_area, 'Sin área asignada') AS area,
        LEAST(
            COALESCE(
                e.fecha_proximo_mantenimiento::date,
                DATE '9999-12-31'
            ),
            COALESCE(
                e.fecha_proxima_recarga::date,
                DATE '9999-12-31'
            )
        ) AS fecha_critica
    FROM public.tb_extintores e
    LEFT JOIN public.tb_areas a
        ON e.id_area = a.id_area
    ORDER BY
        CASE a.nivel
            WHEN 'Sótano' THEN 1
            WHEN 'Planta Baja' THEN 2
            WHEN 'Primer Nivel' THEN 3
            WHEN 'Segundo Nivel' THEN 4
            WHEN 'Tercer Nivel' THEN 5
            WHEN 'Azotea' THEN 6
            ELSE 7
        END,
        a.nombre_area,
        e.numero_folio
";

$resultado = pg_query($conexion, $sql);

/* ==========================================================
   PROCESAR RESULTADOS Y DETERMINAR ESTADOS
   ========================================================== */

if ($resultado) {

    while ($fila = pg_fetch_assoc($resultado)) {

        $fechaCritica = $fila['fecha_critica'];

        if (
            empty($fechaCritica) ||
            $fechaCritica === '9999-12-31'
        ) {
            $fila['fecha_critica'] = 'Sin fecha registrada';
            $fila['estado'] = 'Sin fecha';

        } else {

            $hoy = new DateTime('today');
            $fecha = new DateTime($fechaCritica);

            $dias = (int) $hoy->diff($fecha)->format('%r%a');

            if ($dias < 0) {
                $fila['estado'] = 'Vencido';

            } elseif ($dias <= 30) {
                $fila['estado'] = 'Por vencer';

            } else {
                $fila['estado'] = 'Al corriente';
            }

            $fila['fecha_critica'] = $fecha->format('d/m/Y');
        }

        $listaActivos[] = $fila;
    }

} else {

    $errorConsulta = 'No se pudieron consultar los extintores.';

    error_log(pg_last_error($conexion));
}

/* ==========================================================
   CONTEOS PARA LAS TARJETAS
   ========================================================== */

$total = count($listaActivos);
$alCorriente = 0;
$porVencer = 0;
$vencidos = 0;
$sinFecha = 0;

foreach ($listaActivos as $activo) {

    switch ($activo['estado']) {

        case 'Al corriente':
            $alCorriente++;
            break;

        case 'Por vencer':
            $porVencer++;
            break;

        case 'Vencido':
            $vencidos++;
            break;

        case 'Sin fecha':
            $sinFecha++;
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SIPV | Todos los extintores</title>

    <!-- Bootstrap 5 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>
        body {
            background: #f4f6f9;
            color: #343a40;
            font-family: Arial, sans-serif;
            padding-top: 85px;
        }

        .contenido {
            padding: 30px;
        }

        .titulo-pagina {
            font-weight: 700;
            color: #263547;
        }

        .titulo-audi {
            font-weight: 700;
            color: #263547;
        }

        .subtitulo {
            color: #6c757d;
        }

        .navbar {
            min-height: 65px;
            z-index: 1030;
        }

        .tarjeta-resumen {
            background: #fff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, .06);
            height: 100%;
        }

        .tarjeta-resumen .card-body {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 22px;
        }

        .icono-resumen {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .icono-azul {
            background: #e7f0ff;
            color: #0d6efd;
        }

        .icono-verde {
            background: #dff5e7;
            color: #198754;
        }

        .icono-amarillo {
            background: #fff3cd;
            color: #997404;
        }

        .icono-rojo {
            background: #f8d7da;
            color: #dc3545;
        }

        .icono-gris {
            background: #e9ecef;
            color: #6c757d;
        }

        .numero-resumen {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
        }

        .texto-resumen {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .tarjeta-tabla {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, .06);
            overflow: hidden;
        }

        .tarjeta-tabla .card-header {
            padding: 20px;
            border-bottom: 1px solid #edf0f2;
        }

        .tabla-extintores thead th {
            background: #f8f9fa;
            color: #495057;
            font-size: 13px;
            text-transform: uppercase;
            white-space: nowrap;
            padding: 15px;
        }

        .tabla-extintores tbody td {
            padding: 15px;
            vertical-align: middle;
        }

        .tabla-extintores tbody tr:hover {
            background: #f8fbff;
        }

        .folio {
            font-weight: 700;
            color: #0d6efd;
        }

        .estado-badge {
            border-radius: 20px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .buscador {
            min-width: 230px;
        }

        @media (max-width: 768px) {

            .contenido {
                padding: 15px;
            }

            .numero-resumen {
                font-size: 24px;
            }

            .tarjeta-resumen .card-body {
                padding: 16px;
            }

        }
    </style>

</head>

<body>

<!-- ========================================================
     BARRA SUPERIOR
     ======================================================== -->

<nav class="navbar navbar-light bg-light shadow-sm fixed-top">

    <div class="container-fluid">

        <div class="border-start border-3 border-primary ps-3">

            <h6 class="mb-0 titulo-audi">
                <i class="fas fa-fire-extinguisher text-primary me-2"></i>
                Todos los extintores
            </h6>

        </div>

        <div class="ms-auto d-flex align-items-center gap-2">

            <a href="../activos.php" class="btn btn-outline-danger">
                <i class="fas fa-arrow-left me-1"></i>
                Volver
            </a>

        </div>

    </div>

</nav>

<div class="container-fluid contenido">

    <!-- ====================================================
         ENCABEZADO
         ==================================================== -->

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>

            <h2 class="titulo-pagina mb-1">
                <i class="fas fa-fire-extinguisher text-primary me-2"></i>
                Todos los extintores
            </h2>

            <p class="subtitulo mb-0">
                Sistema Inteligente de Prevención y Verificación (SIPV)
            </p>

        </div>

    </div>

    <?php if ($errorConsulta !== ''): ?>

        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?= htmlspecialchars($errorConsulta, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php else: ?>

        <!-- =================================================
             TARJETAS DE RESUMEN
             ================================================= -->

        <div class="row g-3 mb-4">

            <!-- Total -->

            <div class="col-12 col-sm-6 col-xl">

                <div class="card tarjeta-resumen">

                    <div class="card-body">

                        <div>
                            <p class="texto-resumen">Total de extintores</p>
                            <p class="numero-resumen"><?= $total ?></p>
                        </div>

                        <div class="icono-resumen icono-azul">
                            <i class="fas fa-fire-extinguisher"></i>
                        </div>

                    </div>

                </div>

            </div>

            <!-- Al corriente -->

            <div class="col-12 col-sm-6 col-xl">

                <div class="card tarjeta-resumen">

                    <div class="card-body">

                        <div>
                            <p class="texto-resumen">Al corriente</p>
                            <p class="numero-resumen text-success">
                                <?= $alCorriente ?>
                            </p>
                        </div>

                        <div class="icono-resumen icono-verde">
                            <i class="fas fa-circle-check"></i>
                        </div>

                    </div>

                </div>

            </div>

            <!-- Por vencer -->

            <div class="col-12 col-sm-6 col-xl">

                <div class="card tarjeta-resumen">

                    <div class="card-body">

                        <div>
                            <p class="texto-resumen">Por vencer</p>
                            <p class="numero-resumen text-warning">
                                <?= $porVencer ?>
                            </p>
                        </div>

                        <div class="icono-resumen icono-amarillo">
                            <i class="fas fa-clock"></i>
                        </div>

                    </div>

                </div>

            </div>

            <!-- Vencidos -->

            <div class="col-12 col-sm-6 col-xl">

                <div class="card tarjeta-resumen">

                    <div class="card-body">

                        <div>
                            <p class="texto-resumen">Vencidos</p>
                            <p class="numero-resumen text-danger">
                                <?= $vencidos ?>
                            </p>
                        </div>

                        <div class="icono-resumen icono-rojo">
                            <i class="fas fa-triangle-exclamation"></i>
                        </div>

                    </div>

                </div>

            </div>

            <!-- Sin fecha -->

            <div class="col-12 col-sm-6 col-xl">

                <div class="card tarjeta-resumen">

                    <div class="card-body">

                        <div>
                            <p class="texto-resumen">Sin fecha</p>
                            <p class="numero-resumen text-secondary">
                                <?= $sinFecha ?>
                            </p>
                        </div>

                        <div class="icono-resumen icono-gris">
                            <i class="fas fa-calendar-xmark"></i>
                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- =================================================
             TABLA GENERAL
             ================================================= -->

        <div class="card tarjeta-tabla">

            <div class="card-header bg-white">

                <h5 class="mb-1">
                    <i class="fas fa-list me-2 text-primary"></i>
                    Registro general de extintores
                </h5>

                <small class="text-muted">
                    Consulta y seguimiento de los extintores registrados.
                </small>

            </div>

            <div class="card-body p-3 p-md-4">

                <!-- BUSCADOR Y FILTRO -->

                <div class="row g-3 mb-4">

                    <div class="col-12 col-md-7">

                        <label for="buscadorActivos" class="form-label">
                            Buscar extintor
                        </label>

                        <div class="input-group buscador">

                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>

                            <input
                                type="text"
                                id="buscadorActivos"
                                class="form-control"
                                placeholder="Folio o área..."
                            >

                        </div>

                    </div>

                    <div class="col-12 col-md-5">

                        <label for="filtroEstado" class="form-label">
                            Filtrar por estado
                        </label>

                        <select id="filtroEstado" class="form-select">

                            <option value="">Todos los estados</option>
                            <option value="Al corriente">Al corriente</option>
                            <option value="Por vencer">Por vencer</option>
                            <option value="Vencido">Vencido</option>
                            <option value="Sin fecha">Sin fecha registrada</option>

                        </select>

                    </div>

                </div>

                <!-- TABLA -->

                <div class="table-responsive">

                    <table
                        class="table table-hover tabla-extintores mb-0"
                        id="tablaActivos"
                    >

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

                        <?php if (!empty($listaActivos)): ?>

                            <?php foreach ($listaActivos as $activo): ?>

                                <?php
                                $claseEstado = 'bg-secondary';

                                if ($activo['estado'] === 'Al corriente') {
                                    $claseEstado = 'bg-success';

                                } elseif ($activo['estado'] === 'Por vencer') {
                                    $claseEstado = 'bg-warning text-dark';

                                } elseif ($activo['estado'] === 'Vencido') {
                                    $claseEstado = 'bg-danger';
                                }
                                ?>

                                <tr data-estado="<?= htmlspecialchars(
                                    $activo['estado'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">

                                    <td class="folio">
                                        <?= htmlspecialchars(
                                            $activo['identificador'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <i class="fas fa-fire-extinguisher text-muted me-2"></i>
                                        <?= htmlspecialchars(
                                            $activo['tipo_activo'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $activo['area'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <i class="far fa-calendar me-1 text-muted"></i>
                                        <?= htmlspecialchars(
                                            $activo['fecha_critica'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <span class="badge estado-badge <?= $claseEstado ?>">
                                            <?= htmlspecialchars(
                                                $activo['estado'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox me-2"></i>
                                    No hay extintores registrados.
                                </td>
                            </tr>

                        <?php endif; ?>

                        <tr id="sinResultados" style="display: none;">
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-search me-2"></i>
                                No se encontraron extintores con esos criterios.
                            </td>
                        </tr>

                        </tbody>

                    </table>

                </div>

                <div class="mt-3 text-muted small">
                    Total de registros:
                    <strong id="totalVisible"><?= $total ?></strong>
                </div>

            </div>

        </div>

    <?php endif; ?>

</div>

<!-- ========================================================
     BUSCADOR Y FILTRO POR ESTADO
     ======================================================== -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    const buscador = document.getElementById('buscadorActivos');
    const filtroEstado = document.getElementById('filtroEstado');
    const tabla = document.getElementById('tablaActivos');
    const sinResultados = document.getElementById('sinResultados');
    const totalVisible = document.getElementById('totalVisible');

    if (!buscador || !filtroEstado || !tabla) {
        return;
    }

    const filas = Array.from(
        tabla.querySelectorAll('tbody tr[data-estado]')
    );

    function filtrarExtintores() {

        const texto = buscador.value
            .trim()
            .toLocaleLowerCase('es-MX');

        const estadoSeleccionado = filtroEstado.value;

        let visibles = 0;

        filas.forEach(function (fila) {

            const contenido = fila.textContent
                .toLocaleLowerCase('es-MX');

            const estado = fila.dataset.estado;

            const coincideTexto = contenido.includes(texto);

            const coincideEstado =
                estadoSeleccionado === '' ||
                estado === estadoSeleccionado;

            const mostrar = coincideTexto && coincideEstado;

            fila.style.display = mostrar ? '' : 'none';

            if (mostrar) {
                visibles++;
            }

        });

        if (sinResultados) {
            sinResultados.style.display =
                filas.length > 0 && visibles === 0 ? '' : 'none';
        }

        if (totalVisible) {
            totalVisible.textContent = visibles;
        }
    }

    buscador.addEventListener('input', filtrarExtintores);
    filtroEstado.addEventListener('change', filtrarExtintores);

});
</script>

</body>
</html>
```