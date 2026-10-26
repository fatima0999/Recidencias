<?php
/* ==========================================================
   SIPV - REGISTRO Y SEGUIMIENTO DE DOCUMENTOS
   Cada documento tiene su propia fecha de vencimiento.
   La ventana de "por vencer" sale de tb_tipo_documentos
   (dias_alerta_previa); si viene vacía se usan 30 días.
   ========================================================== */

require_once __DIR__ . '/../../../clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();

/* ==========================================================
   MÉTRICAS
   ========================================================== */

$sqlConteos = "
    SELECT
        COUNT(*) AS total,

        COUNT(*) FILTER (
            WHERE (d.fecha_vencimiento - CURRENT_DATE)
                  > COALESCE(t.dias_alerta_previa, 30)
        ) AS al_corriente,

        COUNT(*) FILTER (
            WHERE (d.fecha_vencimiento - CURRENT_DATE) >= 0
              AND (d.fecha_vencimiento - CURRENT_DATE)
                  <= COALESCE(t.dias_alerta_previa, 30)
        ) AS por_vencer,

        COUNT(*) FILTER (
            WHERE d.fecha_vencimiento < CURRENT_DATE
        ) AS vencidos

    FROM tb_documentos d
    INNER JOIN tb_tipo_documentos t
        ON t.id_tipo_documento = d.id_tipo_documento
";

$resultadoConteos = pg_query($conexion, $sqlConteos);

if ($resultadoConteos === false) {
    die('Error al consultar las métricas de documentos. Verifica las tablas tb_documentos y tb_tipo_documentos.');
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
   LISTADO DE DOCUMENTOS (los más urgentes primero)
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
        (d.fecha_vencimiento - CURRENT_DATE) AS dias_restantes,
        COALESCE(t.dias_alerta_previa, 30)   AS dias_alerta
    FROM tb_documentos d
    INNER JOIN tb_tipo_documentos t
        ON t.id_tipo_documento = d.id_tipo_documento
    ORDER BY d.fecha_vencimiento ASC
";

$resultadoDocumentos = pg_query($conexion, $sqlDocumentos);

if ($resultadoDocumentos === false) {
    die('Error al consultar el listado de documentos. Verifica los nombres de las columnas.');
}

$listaDocumentos = [];

while ($fila = pg_fetch_assoc($resultadoDocumentos)) {

    $dias   = (int) $fila['dias_restantes'];
    $alerta = (int) $fila['dias_alerta'];

    if ($dias < 0) {

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

    <title>Documentos</title>


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

                <h6 class="mb-0 titulo-audi">
                    Documentos
                </h6>

            </div>


            <div class="ms-auto d-flex align-items-center gap-2">

                <!-- CAMBIA ESTA RUTA a donde quieras regresar -->
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
        ENCABEZADO + BOTÓN AGREGAR
        ===================================================== -->

    <div class="container d-flex justify-content-between align-items-center mt-1 pt-3">

        <h5>DOCUMENTOS</h5>

        <!-- CAMBIA ESTA RUTA si tu formulario de alta está en otra carpeta -->
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

    <div class="container-fluid">

        <div class="row g-4 mt-1">

            <div class="col-12">

                <div class="card shadow-sm mb-5">

                    <div class="card-header bg-white">
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
                                    placeholder="Buscar por folio, documento, tipo o autoridad..."
                                >
                            </div>

                            <div class="col-md-4">
                                <select id="filtroEstado" class="form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="Al corriente">Al corriente</option>
                                    <option value="Por vencer">Por vencer</option>
                                    <option value="Vencido">Vencido</option>
                                </select>
                            </div>

                        </div>


                        <!-- TABLA -->
                        <div class="table-responsive">

                            <table
                                class="table table-hover align-middle"
                                id="tablaDocumentos"
                            >

                                <thead>
                                    <tr>
                                        <th>Folio</th>
                                        <th>Documento</th>
                                        <th>Tipo</th>
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
                                        }

                                        ?>

                                        <tr data-estado="<?= htmlspecialchars($doc['estado'], ENT_QUOTES, 'UTF-8') ?>">

                                            <td><?= htmlspecialchars($doc['numero_folio'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>

                                            <td><?= htmlspecialchars($doc['nombre_documento'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>

                                            <td><?= htmlspecialchars($doc['nombre_tipo'], ENT_QUOTES, 'UTF-8') ?></td>

                                            <td><?= htmlspecialchars($doc['autoridad_emisora'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>

                                            <td><?= htmlspecialchars($doc['emision_formateada'], ENT_QUOTES, 'UTF-8') ?></td>

                                            <td><?= htmlspecialchars($doc['vencimiento_formateado'], ENT_QUOTES, 'UTF-8') ?></td>

                                            <td class="<?= $claseDias ?>">
                                                <?= htmlspecialchars($doc['texto_dias'], ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <span class="badge <?= $claseEstado ?>">
                                                    <?= htmlspecialchars($doc['estado'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>

                                            <!-- ACCIONES -->
                                            <td>

                                                <?php if (!empty($doc['ruta_archivo'])): ?>

                                                    <a
                                                        href="<?= htmlspecialchars($doc['ruta_archivo'], ENT_QUOTES, 'UTF-8') ?>"
                                                        target="_blank"
                                                        rel="noopener"
                                                        class="btn btn-outline-primary btn-sm"
                                                        title="Abrir documento"
                                                    >
                                                        <i class="fas fa-eye me-1"></i>
                                                        Ver
                                                    </a>

                                                <?php else: ?>

                                                    <span class="text-muted small">Sin archivo</span>

                                                <?php endif; ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>


                                    <!-- SIN REGISTROS -->
                                    <?php if (empty($listaDocumentos)): ?>

                                        <tr data-estado="">
                                            <td colspan="9" class="text-center text-muted py-4">
                                                No hay documentos registrados todavía.
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

        const buscador = document.getElementById('buscadorDocumentos');
        const filtroEstado = document.getElementById('filtroEstado');
        const filasTabla = document.querySelectorAll('#tablaDocumentos tbody tr');

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

    </script>


</body>

</html>