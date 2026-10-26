
<?php
/* ==========================================================
   SIPV - REGISTRO Y SEGUIMIENTO DE DOCUMENTOS
   MÓDULO EXCLUSIVO DEL PRIMER NIVEL

   Relación entre tablas:
   tb_documentos
       -> tb_areas_documentos
       -> tb_areas

   La alerta de vencimiento se obtiene de
   tb_tipo_documentos.dias_alerta_previa.
   Si no tiene valor, se utilizan 30 días.
   ========================================================== */


/* ==========================================================
   CONEXIÓN A LA BASE DE DATOS
   ========================================================== */

require_once __DIR__ . '/../../../clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();


/* ==========================================================
   MÉTRICAS DE DOCUMENTOS DEL PRIMER NIVEL
   ========================================================== */

$sqlConteos = "
    SELECT
        COUNT(DISTINCT d.id_documento) AS total,

        COUNT(DISTINCT d.id_documento) FILTER (
            WHERE d.fecha_vencimiento IS NOT NULL
              AND (d.fecha_vencimiento - CURRENT_DATE)
                  > COALESCE(t.dias_alerta_previa, 30)
        ) AS al_corriente,

        COUNT(DISTINCT d.id_documento) FILTER (
            WHERE d.fecha_vencimiento IS NOT NULL
              AND (d.fecha_vencimiento - CURRENT_DATE) >= 0
              AND (d.fecha_vencimiento - CURRENT_DATE)
                  <= COALESCE(t.dias_alerta_previa, 30)
        ) AS por_vencer,

        COUNT(DISTINCT d.id_documento) FILTER (
            WHERE d.fecha_vencimiento < CURRENT_DATE
        ) AS vencidos

    FROM public.tb_documentos d

    INNER JOIN public.tb_tipo_documentos t
        ON t.id_tipo_documento = d.id_tipo_documento

    INNER JOIN public.tb_areas_documentos ad
        ON ad.id_documento = d.id_documento

    INNER JOIN public.tb_areas a
        ON a.id_area = ad.id_area

    WHERE a.nivel = 'Primer Nivel'
";

$resultadoConteos = pg_query($conexion, $sqlConteos);

if ($resultadoConteos === false) {
    die(
        'Error al consultar las métricas de documentos del Primer Nivel. ' .
        'Verifica las tablas tb_documentos, tb_tipo_documentos, ' .
        'tb_areas_documentos y tb_areas.'
    );
}

$conteos = pg_fetch_assoc($resultadoConteos);

$total_documentos = (int) ($conteos['total'] ?? 0);
$al_corriente     = (int) ($conteos['al_corriente'] ?? 0);
$por_vencer       = (int) ($conteos['por_vencer'] ?? 0);
$vencidos         = (int) ($conteos['vencidos'] ?? 0);

$cumplimiento = $total_documentos > 0
    ? round(($al_corriente / $total_documentos) * 100)
    : 0;


/* ==========================================================
   LISTADO DE DOCUMENTOS DEL PRIMER NIVEL
   Los documentos más urgentes aparecen primero.
   ========================================================== */

$sqlDocumentos = "
    SELECT
        d.id_documento,
        d.numero_folio,
        d.nombre_documento,
        d.autoridad_emisora,
        d.fecha_emision,
        d.fecha_vencimiento,
        d.ruta_archivo,

        t.nombre_tipo,

        STRING_AGG(
            DISTINCT a.nombre_area,
            ', ' ORDER BY a.nombre_area
        ) AS nombre_area,

        (d.fecha_vencimiento - CURRENT_DATE) AS dias_restantes,

        COALESCE(t.dias_alerta_previa, 30) AS dias_alerta

    FROM public.tb_documentos d

    INNER JOIN public.tb_tipo_documentos t
        ON t.id_tipo_documento = d.id_tipo_documento

    INNER JOIN public.tb_areas_documentos ad
        ON ad.id_documento = d.id_documento

    INNER JOIN public.tb_areas a
        ON a.id_area = ad.id_area

    WHERE a.nivel = 'Primer Nivel'

    GROUP BY
        d.id_documento,
        t.nombre_tipo,
        t.dias_alerta_previa

    ORDER BY
        d.fecha_vencimiento ASC NULLS LAST,
        d.numero_folio ASC
";

$resultadoDocumentos = pg_query($conexion, $sqlDocumentos);

if ($resultadoDocumentos === false) {
    die(
        'Error al consultar los documentos del Primer Nivel. ' .
        'Verifica los nombres de las columnas y las relaciones entre tablas.'
    );
}

$listaDocumentos = [];

while ($fila = pg_fetch_assoc($resultadoDocumentos)) {

    $dias = $fila['dias_restantes'] !== null
        ? (int) $fila['dias_restantes']
        : null;

    $alerta = (int) $fila['dias_alerta'];


    /* ------------------------------------------
       DETERMINAR EL ESTADO DEL DOCUMENTO
       ------------------------------------------ */

    if ($dias === null) {

        $estado = 'Sin fecha';
        $textoDias = 'Sin fecha de vencimiento';

    } elseif ($dias < 0) {

        $estado = 'Vencido';
        $textoDias = 'Venció hace ' . abs($dias) . ' día(s)';

    } elseif ($dias === 0) {

        $estado = 'Por vencer';
        $textoDias = 'Vence hoy';

    } elseif ($dias <= $alerta) {

        $estado = 'Por vencer';
        $textoDias = 'Faltan ' . $dias . ' día(s)';

    } else {

        $estado = 'Al corriente';
        $textoDias = 'Faltan ' . $dias . ' día(s)';

    }


    /* ------------------------------------------
       FORMATEAR FECHAS
       ------------------------------------------ */

    $fila['estado'] = $estado;
    $fila['texto_dias'] = $textoDias;

    $fila['emision_formateada'] = !empty($fila['fecha_emision'])
        ? date('d/m/Y', strtotime($fila['fecha_emision']))
        : '—';

    $fila['vencimiento_formateado'] = !empty($fila['fecha_vencimiento'])
        ? date('d/m/Y', strtotime($fila['fecha_vencimiento']))
        : '—';

    $listaDocumentos[] = $fila;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Documentos - Primer Nivel | SIPV</title>

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
            background-color: #f8f9fa;
        }

        .contenedor-metricas {
            padding-left: 20px;
            padding-right: 20px;
        }

        .titulo-audi {
            color: #343a40;
            font-weight: 600;
        }

        .card {
            border-radius: 8px;
        }

        .tabla-documentos th {
            white-space: nowrap;
            font-size: 13px;
        }

        .tabla-documentos td {
            vertical-align: middle;
        }

        .card-header h5 {
            font-weight: 600;
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
         BARRA DE NAVEGACIÓN
         ===================================================== -->

    <nav class="navbar navbar-light bg-light shadow-sm fixed-top">

        <div class="container-fluid">

            <div class="border-start border-danger border-4 ps-3">

                <h6 class="mb-0 titulo-audi">
                    <i class="fas fa-file-alt me-2 text-danger"></i>
                    Documentos - Primer Nivel
                </h6>

                <small class="text-muted">
                    Sistema Inteligente de Prevención y Verificación
                </small>

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
         MÉTRICAS
         ===================================================== -->

    <div class="container-fluid contenedor-metricas">

        <div class="row g-3 mb-4">

            <!-- CUMPLIMIENTO -->
            <div class="col-lg-3 col-md-6">

                <div class="card p-3 border-start border-success border-4 shadow-sm">

                    <small class="text-muted">
                        Cumplimiento
                    </small>

                    <h3 class="fw-bold text-success m-0">
                        <?= $cumplimiento ?>%
                    </h3>

                </div>

            </div>

            <!-- AL CORRIENTE -->
            <div class="col-lg-3 col-md-6">

                <div class="card p-3 border-start border-success border-4 shadow-sm">

                    <small class="text-muted">
                        Al corriente
                    </small>

                    <h3 class="fw-bold text-success m-0">
                        <?= $al_corriente ?>
                    </h3>

                </div>

            </div>

            <!-- POR VENCER -->
            <div class="col-lg-3 col-md-6">

                <div class="card p-3 border-start border-warning border-4 shadow-sm">

                    <small class="text-muted">
                        Por vencer
                    </small>

                    <h3 class="fw-bold text-warning m-0">
                        <?= $por_vencer ?>
                    </h3>

                </div>

            </div>

            <!-- VENCIDOS -->
            <div class="col-lg-3 col-md-6">

                <div class="card p-3 border-start border-danger border-4 shadow-sm">

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
         ENCABEZADO Y BOTÓN AGREGAR
         ===================================================== -->

    <div class="container-fluid px-4 d-flex justify-content-between
                align-items-center flex-wrap gap-2 mt-1 pt-3 mb-3">

        <div>

            <h5 class="mb-1">
                DOCUMENTOS DEL PRIMER NIVEL
            </h5>

            <small class="text-muted">
                Consulta las fechas de vencimiento y el estado de cada documento.
            </small>

        </div>

        <a
            href="./agre.documentos/documento1.php"
            class="btn btn-primary"
        >
            <i class="fas fa-plus me-1"></i>
            Agregar
        </a>

    </div>


    <!-- =====================================================
         TABLA DE DOCUMENTOS
         ===================================================== -->

    <div class="container-fluid px-4">

        <div class="row g-4">

            <div class="col-12">

                <div class="card shadow-sm mb-5">

                    <div class="card-header bg-white py-3">

                        <h5 class="mb-0">
                            <i class="fas fa-file-alt me-2 text-primary"></i>
                            Registro de documentos
                        </h5>

                    </div>

                    <div class="card-body">

                        <!-- BUSCADOR Y FILTRO -->
                        <div class="row g-2 mb-3">

                            <div class="col-md-6">

                                <input
                                    type="text"
                                    id="buscadorDocumentos"
                                    class="form-control"
                                    placeholder="Buscar por folio, documento, tipo, área o autoridad..."
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


                        <!-- TABLA -->
                        <div class="table-responsive">

                            <table
                                class="table table-hover align-middle tabla-documentos"
                                id="tablaDocumentos"
                            >

                                <thead class="table-light">

                                    <tr>
                                        <th>Folio</th>
                                        <th>Documento</th>
                                        <th>Tipo</th>
                                        <th>Área</th>
                                        <th>Autoridad emisora</th>
                                        <th>Emisión</th>
                                        <th>Vencimiento</th>
                                        <th>Tiempo restante</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach ($listaDocumentos as $doc): ?>

                                        <?php

                                        $claseEstado = 'bg-secondary';
                                        $claseDias = 'text-muted';

                                        if ($doc['estado'] === 'Al corriente') {

                                            $claseEstado = 'bg-success';

                                        } elseif ($doc['estado'] === 'Por vencer') {

                                            $claseEstado = 'bg-warning text-dark';
                                            $claseDias = 'text-warning fw-semibold';

                                        } elseif ($doc['estado'] === 'Vencido') {

                                            $claseEstado = 'bg-danger';
                                            $claseDias = 'text-danger fw-semibold';

                                        } elseif ($doc['estado'] === 'Sin fecha') {

                                            $claseEstado = 'bg-secondary';
                                            $claseDias = 'text-muted fw-semibold';

                                        }

                                        ?>

                                        <tr
                                            data-estado="<?= htmlspecialchars(
                                                $doc['estado'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                        >

                                            <!-- FOLIO -->
                                            <td>
                                                <?= htmlspecialchars(
                                                    $doc['numero_folio'] ?? '—',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <!-- DOCUMENTO -->
                                            <td>
                                                <?= htmlspecialchars(
                                                    $doc['nombre_documento'] ?? '—',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <!-- TIPO -->
                                            <td>
                                                <?= htmlspecialchars(
                                                    $doc['nombre_tipo'] ?? '—',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <!-- ÁREA -->
                                            <td>
                                                <?= htmlspecialchars(
                                                    $doc['nombre_area'] ?? 'Sin área',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <!-- AUTORIDAD EMISORA -->
                                            <td>
                                                <?= htmlspecialchars(
                                                    $doc['autoridad_emisora'] ?? '—',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <!-- EMISIÓN -->
                                            <td>
                                                <?= htmlspecialchars(
                                                    $doc['emision_formateada'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <!-- VENCIMIENTO -->
                                            <td>
                                                <?= htmlspecialchars(
                                                    $doc['vencimiento_formateado'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <!-- TIEMPO RESTANTE -->
                                            <td class="<?= $claseDias ?>">
                                                <?= htmlspecialchars(
                                                    $doc['texto_dias'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <!-- ESTADO -->
                                            <td>

                                                <span class="badge <?= $claseEstado ?>">
                                                    <?= htmlspecialchars(
                                                        $doc['estado'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </span>

                                            </td>

                                            <!-- ACCIONES -->
                                            <td>

                                                <?php if (!empty($doc['ruta_archivo'])): ?>

                                                    <a
                                                        href="<?= htmlspecialchars(
                                                            $doc['ruta_archivo'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="btn btn-outline-primary btn-sm"
                                                        title="Abrir documento"
                                                    >

                                                        <i class="fas fa-eye me-1"></i>
                                                        Ver

                                                    </a>

                                                <?php else: ?>

                                                    <span class="text-muted small">
                                                        <i class="fas fa-file-excel me-1"></i>
                                                        Sin archivo
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>


                                    <!-- SIN REGISTROS -->
                                    <?php if (empty($listaDocumentos)): ?>

                                        <tr id="filaSinDocumentos">

                                            <td
                                                colspan="10"
                                                class="text-center text-muted py-4"
                                            >

                                                <i class="fas fa-folder-open fa-2x mb-2"></i>

                                                <p class="mb-0">
                                                    No hay documentos registrados para el Primer Nivel.
                                                </p>

                                            </td>

                                        </tr>

                                    <?php endif; ?>

                                </tbody>

                            </table>

                        </div>


                        <!-- MENSAJE CUANDO NO HAY COINCIDENCIAS -->
                        <div
                            id="mensajeSinResultados"
                            class="alert alert-light text-center d-none"
                        >
                            No se encontraron documentos con esos criterios.
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
         BUSCADOR Y FILTRO POR ESTADO
         ===================================================== -->

    <script>

        const buscador = document.getElementById('buscadorDocumentos');

        const filtroEstado = document.getElementById('filtroEstado');

        const filasTabla = document.querySelectorAll(
            '#tablaDocumentos tbody tr[data-estado]'
        );

        const mensajeSinResultados = document.getElementById(
            'mensajeSinResultados'
        );

        function filtrarTabla() {

            const textoBusqueda = buscador.value.toLowerCase().trim();

            const estadoSeleccionado = filtroEstado.value;

            let filasVisibles = 0;

            filasTabla.forEach(function (fila) {

                const texto = fila.textContent.toLowerCase();

                const estadoFila = fila.dataset.estado || '';

                const coincideTexto = texto.includes(textoBusqueda);

                const coincideEstado =
                    estadoSeleccionado === '' ||
                    estadoFila === estadoSeleccionado;

                const mostrar = coincideTexto && coincideEstado;

                fila.style.display = mostrar ? '' : 'none';

                if (mostrar) {
                    filasVisibles++;
                }

            });

            if (mensajeSinResultados) {

                mensajeSinResultados.classList.toggle(
                    'd-none',
                    filasVisibles > 0
                );

            }

        }

        buscador.addEventListener('input', filtrarTabla);

        filtroEstado.addEventListener('change', filtrarTabla);

    </script>

</body>
</html>
