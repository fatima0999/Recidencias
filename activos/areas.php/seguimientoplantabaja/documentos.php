
<?php
/* ==========================================================
   SIPV - DOCUMENTOS DE PLANTA BAJA
   ========================================================== */

require_once __DIR__ . '/../../../clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();

/* ==========================================================
   MÉTRICAS DE DOCUMENTOS DE PLANTA BAJA
   ========================================================== */

$sqlConteos = "
    SELECT
        COUNT(DISTINCT d.id_documento) AS total,

        COUNT(DISTINCT d.id_documento) FILTER (
            WHERE d.fecha_vencimiento IS NOT NULL
              AND d.fecha_vencimiento >= CURRENT_DATE
              AND d.fecha_vencimiento - CURRENT_DATE
                  > COALESCE(t.dias_alerta_previa, 30)
        ) AS al_corriente,

        COUNT(DISTINCT d.id_documento) FILTER (
            WHERE d.fecha_vencimiento IS NOT NULL
              AND d.fecha_vencimiento >= CURRENT_DATE
              AND d.fecha_vencimiento - CURRENT_DATE
                  <= COALESCE(t.dias_alerta_previa, 30)
        ) AS por_vencer,

        COUNT(DISTINCT d.id_documento) FILTER (
            WHERE d.fecha_vencimiento < CURRENT_DATE
        ) AS vencidos,

        COUNT(DISTINCT d.id_documento) FILTER (
            WHERE d.fecha_vencimiento IS NULL
        ) AS sin_fecha

    FROM public.tb_documentos d

    INNER JOIN public.tb_tipo_documentos t
        ON t.id_tipo_documento = d.id_tipo_documento

    INNER JOIN public.tb_areas_documentos ad
        ON ad.id_documento = d.id_documento

    INNER JOIN public.tb_areas a
        ON a.id_area = ad.id_area

    WHERE a.nivel = 'Planta Baja'
";

$resultadoConteos = pg_query($conexion, $sqlConteos);

if ($resultadoConteos === false) {
    die('Error en las métricas: ' . pg_last_error($conexion));
}

$conteos = pg_fetch_assoc($resultadoConteos);

$total_documentos = (int) $conteos['total'];
$al_corriente     = (int) $conteos['al_corriente'];
$por_vencer       = (int) $conteos['por_vencer'];
$vencidos         = (int) $conteos['vencidos'];
$sin_fecha        = (int) $conteos['sin_fecha'];

$cumplimiento = $total_documentos > 0
    ? round(($al_corriente / $total_documentos) * 100)
    : 0;


/* ==========================================================
   CONSULTA DE DOCUMENTOS DE PLANTA BAJA
   ========================================================== */

$sqlDocumentos = "
    SELECT DISTINCT ON (d.id_documento)
        d.id_documento,
        d.numero_folio,
        d.nombre_documento,
        d.autoridad_emisora,
        d.fecha_emision,
        d.fecha_vencimiento,
        d.ruta_archivo,
        d.estado,
        d.observaciones,
        t.nombre_tipo,
        a.nombre_area,
        d.fecha_vencimiento - CURRENT_DATE AS dias_restantes,
        COALESCE(t.dias_alerta_previa, 30) AS dias_alerta

    FROM public.tb_documentos d

    INNER JOIN public.tb_tipo_documentos t
        ON t.id_tipo_documento = d.id_tipo_documento

    INNER JOIN public.tb_areas_documentos ad
        ON ad.id_documento = d.id_documento

    INNER JOIN public.tb_areas a
        ON a.id_area = ad.id_area

    WHERE a.nivel = 'Planta Baja'

    ORDER BY
        d.id_documento,
        d.fecha_vencimiento ASC NULLS LAST,
        a.nombre_area ASC
";

$resultadoDocumentos = pg_query($conexion, $sqlDocumentos);

if ($resultadoDocumentos === false) {
    die('Error al consultar documentos: ' . pg_last_error($conexion));
}

$listaDocumentos = [];

while ($doc = pg_fetch_assoc($resultadoDocumentos)) {

    if (empty($doc['fecha_vencimiento'])) {
        $doc['estado_calculado'] = 'Sin fecha';
        $doc['texto_dias'] = 'Sin fecha de vencimiento';
    } else {
        $dias = (int) $doc['dias_restantes'];
        $alerta = (int) $doc['dias_alerta'];

        if ($dias < 0) {
            $doc['estado_calculado'] = 'Vencido';
            $doc['texto_dias'] = 'Venció hace ' . abs($dias) . ' día(s)';
        } elseif ($dias <= $alerta) {
            $doc['estado_calculado'] = 'Por vencer';
            $doc['texto_dias'] = $dias === 0
                ? 'Vence hoy'
                : 'Faltan ' . $dias . ' día(s)';
        } else {
            $doc['estado_calculado'] = 'Al corriente';
            $doc['texto_dias'] = 'Faltan ' . $dias . ' día(s)';
        }
    }

    $doc['emision_formateada'] = !empty($doc['fecha_emision'])
        ? date('d/m/Y', strtotime($doc['fecha_emision']))
        : '—';

    $doc['vencimiento_formateado'] = !empty($doc['fecha_vencimiento'])
        ? date('d/m/Y', strtotime($doc['fecha_vencimiento']))
        : '—';

    $listaDocumentos[] = $doc;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Documentos - Planta Baja | SIPV</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>
        body {
            padding-top: 95px;
            background-color: #f5f6f8;
            color: #212529;
        }

        .navbar {
            min-height: 75px;
        }

        .titulo-sipv {
            border-left: 4px solid #dc3545;
            padding-left: 12px;
        }

        .titulo-sipv h6 {
            font-weight: 700;
        }

        .tarjeta-metrica {
            background: #fff;
            border: 0;
            border-left: 4px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
            height: 100%;
        }

        .borde-verde {
            border-left-color: #198754;
        }

        .borde-amarillo {
            border-left-color: #ffc107;
        }

        .borde-rojo {
            border-left-color: #dc3545;
        }

        .borde-azul {
            border-left-color: #0d6efd;
        }

        .tarjeta-metrica h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0;
        }

        .tarjeta-tabla {
            border: 0;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, .06);
        }

        .table thead th {
            background-color: #f1f3f5;
            font-size: .85rem;
            white-space: nowrap;
        }

        .table tbody td {
            vertical-align: middle;
            font-size: .9rem;
        }

        .badge {
            padding: 7px 9px;
        }

        .titulo-seccion {
            font-weight: 700;
        }

        @media (max-width: 768px) {
            body {
                padding-top: 85px;
            }
        }
    </style>
</head>

<body>

<!-- ======================================================
     BARRA SUPERIOR
     ====================================================== -->

<nav class="navbar navbar-light bg-white shadow-sm fixed-top">
    <div class="container-fluid px-3 px-md-4">

        <div class="titulo-sipv">
            <h6 class="mb-0">Documentos - Planta Baja</h6>
            <small class="text-muted">SIPV | Área de Calidad</small>
        </div>

        <a href="../plantabaja.php" class="btn btn-outline-danger btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i>
            Volver
        </a>

    </div>
</nav>


<!-- ======================================================
     TARJETAS DE MÉTRICAS
     ====================================================== -->

<div class="container-fluid px-3 px-md-4">

    <div class="row g-3 mb-4">

        <div class="col-6 col-lg">
            <div class="tarjeta-metrica borde-azul p-3">
                <small class="text-muted">Total documentos</small>
                <h3><?= $total_documentos ?></h3>
            </div>
        </div>

        <div class="col-6 col-lg">
            <div class="tarjeta-metrica borde-verde p-3">
                <small class="text-muted">Al corriente</small>
                <h3 class="text-success"><?= $al_corriente ?></h3>
            </div>
        </div>

        <div class="col-6 col-lg">
            <div class="tarjeta-metrica borde-amarillo p-3">
                <small class="text-muted">Por vencer</small>
                <h3 class="text-warning"><?= $por_vencer ?></h3>
            </div>
        </div>

        <div class="col-6 col-lg">
            <div class="tarjeta-metrica borde-rojo p-3">
                <small class="text-muted">Vencidos</small>
                <h3 class="text-danger"><?= $vencidos ?></h3>
            </div>
        </div>

        <div class="col-6 col-lg">
            <div class="tarjeta-metrica p-3">
                <small class="text-muted">Sin fecha</small>
                <h3><?= $sin_fecha ?></h3>
            </div>
        </div>

    </div>


    <!-- ==================================================
         ENCABEZADO Y ACCIÓN
         ================================================== -->

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">

        <div>
            <h5 class="titulo-seccion mb-1">
                <i class="fa-solid fa-file-lines me-2 text-primary"></i>
                Registro de documentos
            </h5>

            <small class="text-muted">
                Documentos asociados exclusivamente a áreas de Planta Baja
            </small>

            <div class="mt-2">
                <span class="text-muted small">
                    Cumplimiento:
                </span>
                <strong><?= $cumplimiento ?>%</strong>
            </div>
        </div>

        <a href="./agre.documentos/documento1.php" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i>
            Agregar documento
        </a>

    </div>


    <!-- ==================================================
         TABLA Y FILTROS
         ================================================== -->

    <div class="card tarjeta-tabla mb-5">

        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold">Listado de documentos de Planta Baja</h6>
        </div>

        <div class="card-body">

            <div class="row g-2 mb-3">

                <div class="col-md-7">
                    <input
                        type="text"
                        id="buscadorDocumentos"
                        class="form-control"
                        placeholder="Buscar por folio, documento, tipo o área..."
                    >
                </div>

                <div class="col-md-5">
                    <select id="filtroEstado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="Al corriente">Al corriente</option>
                        <option value="Por vencer">Por vencer</option>
                        <option value="Vencido">Vencido</option>
                        <option value="Sin fecha">Sin fecha</option>
                    </select>
                </div>

            </div>


            <div class="table-responsive">

                <table class="table table-hover" id="tablaDocumentos">

                    <thead>
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
                            <th>Archivo</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (empty($listaDocumentos)): ?>

                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="fa-regular fa-folder-open fa-2x mb-2"></i>
                                <br>
                                No hay documentos registrados para Planta Baja.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($listaDocumentos as $doc): ?>

                            <?php
                            $estado = $doc['estado_calculado'];

                            $claseBadge = 'bg-secondary';

                            if ($estado === 'Al corriente') {
                                $claseBadge = 'bg-success';
                            } elseif ($estado === 'Por vencer') {
                                $claseBadge = 'bg-warning text-dark';
                            } elseif ($estado === 'Vencido') {
                                $claseBadge = 'bg-danger';
                            }
                            ?>

                            <tr data-estado="<?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?>">

                                <td>
                                    <?= htmlspecialchars($doc['numero_folio'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($doc['nombre_documento'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($doc['nombre_tipo'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($doc['nombre_area'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($doc['autoridad_emisora'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($doc['emision_formateada'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($doc['vencimiento_formateado'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($doc['texto_dias'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <span class="badge <?= $claseBadge ?>">
                                        <?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if (!empty($doc['ruta_archivo'])): ?>
                                        <a
                                            href="<?= htmlspecialchars($doc['ruta_archivo'], ENT_QUOTES, 'UTF-8') ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="btn btn-outline-primary btn-sm"
                                            title="Abrir documento"
                                        >
                                            <i class="fa-solid fa-eye"></i>
                                            Ver
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Sin archivo</span>
                                    <?php endif; ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="text-muted small mt-2">
                Documentos encontrados:
                <strong id="totalVisible"><?= count($listaDocumentos) ?></strong>
            </div>

        </div>
    </div>

</div>


<!-- ======================================================
     SCRIPTS
     ====================================================== -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const buscador = document.getElementById('buscadorDocumentos');
    const filtroEstado = document.getElementById('filtroEstado');
    const filas = document.querySelectorAll('#tablaDocumentos tbody tr');
    const totalVisible = document.getElementById('totalVisible');

    function filtrarDocumentos() {
        const texto = buscador.value.toLowerCase().trim();
        const estadoSeleccionado = filtroEstado.value;
        let visibles = 0;

        filas.forEach(function (fila) {
            const contenido = fila.textContent.toLowerCase();
            const estado = fila.dataset.estado || '';

            const coincideTexto = contenido.includes(texto);
            const coincideEstado =
                estadoSeleccionado === '' ||
                estado === estadoSeleccionado;

            const mostrar = coincideTexto && coincideEstado;

            fila.style.display = mostrar ? '' : 'none';

            if (mostrar && fila.querySelector('td')) {
                visibles++;
            }
        });

        totalVisible.textContent = visibles;
    }

    buscador.addEventListener('input', filtrarDocumentos);
    filtroEstado.addEventListener('change', filtrarDocumentos);
</script>

</body>
</html>
