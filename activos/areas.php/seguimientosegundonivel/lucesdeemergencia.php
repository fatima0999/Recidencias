<?php
/* ==========================================================
   SIPV - LUCES DE EMERGENCIA DEL SEGUNDO NIVEL
   ========================================================== */

/* CONEXIÓN */
require_once __DIR__ . '/../../../clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();

/* ==========================================================
   MÉTRICAS DE LUCES DE EMERGENCIA DEL SEGUNDO NIVEL
   ========================================================== */

$sqlConteos = "
    SELECT
        COUNT(*) AS total,

        COUNT(*) FILTER (
            WHERE fecha_proxima_revision > CURRENT_DATE + INTERVAL '30 days'
        ) AS al_corriente,

        COUNT(*) FILTER (
            WHERE fecha_proxima_revision BETWEEN CURRENT_DATE
              AND CURRENT_DATE + INTERVAL '30 days'
        ) AS por_vencer,

        COUNT(*) FILTER (
            WHERE fecha_proxima_revision < CURRENT_DATE
        ) AS vencidos

    FROM public.tb_luces_emergencia l
    INNER JOIN public.tb_areas a
        ON l.id_area = a.id_area

    WHERE a.nivel = 'Segundo Nivel'
";

$resultadoConteos = pg_query($conexion, $sqlConteos);

if ($resultadoConteos === false) {
    die('Error al consultar las métricas de luces de emergencia. Verifica tb_luces_emergencia y la conexión.');
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
   LISTADO DE LUCES DE EMERGENCIA DEL SEGUNDO NIVEL
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

    FROM public.tb_luces_emergencia l

    INNER JOIN public.tb_areas a
        ON l.id_area = a.id_area

    WHERE a.nivel = 'Segundo Nivel'

    ORDER BY l.id_luz ASC
";

$resultadoLuces = pg_query($conexion, $sqlLuces);

if ($resultadoLuces === false) {
    die('Error al consultar las luces de emergencia. Verifica las columnas de tb_luces_emergencia y tb_areas.');
}

$listaLuces = [];

$hoy = date('Y-m-d');
$limite = date('Y-m-d', strtotime('+30 days'));

while ($fila = pg_fetch_assoc($resultadoLuces)) {

    $fecha = $fila['fecha_proxima_revision'];

    if (empty($fecha)) {

        $estadoSeguimiento = 'Sin fecha';
        $fechaFormateada = '—';

    } else {

        $fecha = substr($fecha, 0, 10);
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

    <title>Luces de Emergencia - Segundo Nivel | SIPV</title>

    <!-- Bootstrap 5 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
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

        .tarjeta-metrica {
            background: #fff;
            border-radius: 8px;
        }

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

        .titulo-audi {
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

        <div class="border-start border-4 border-primary ps-3">
            <h6 class="mb-0 titulo-audi">
                Luces de emergencia - Segundo Nivel
            </h6>
            <small class="text-muted">SIPV | Seguimiento de activos</small>
        </div>

        <div class="ms-auto">
            <a
                href="../../areas.php/segundoNivel.php"
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

        <div class="col-lg-3 col-md-6">
            <div class="card tarjeta-metrica p-3 border-start border-success border-4 shadow-sm">
                <small class="text-muted">Cumplimiento</small>
                <h3 class="fw-bold text-success m-0">
                    <?= $cumplimiento ?>%
                </h3>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card tarjeta-metrica p-3 border-start border-success border-4 shadow-sm">
                <small class="text-muted">Al corriente</small>
                <h3 class="fw-bold text-success m-0">
                    <?= $al_corriente ?>
                </h3>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card tarjeta-metrica p-3 border-start border-warning border-4 shadow-sm">
                <small class="text-muted">Por vencer</small>
                <h3 class="fw-bold text-warning m-0">
                    <?= $por_vencer ?>
                </h3>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card tarjeta-metrica p-3 border-start border-danger border-4 shadow-sm">
                <small class="text-muted">Vencidos</small>
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

<div class="container-fluid d-flex justify-content-between align-items-center mt-1 pt-3 mb-3">

    <h5 class="mb-0">
        <i class="fas fa-lightbulb text-warning me-2"></i>
        ACTIVOS
    </h5>

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

    <div class="card shadow-sm mb-5">

        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i class="fas fa-list me-2 text-primary"></i>
                Luces de emergencia del Segundo Nivel
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

                <table class="table table-hover align-middle" id="tablaLuces">

                    <thead class="table-light">
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

                    <?php if (!empty($listaLuces)): ?>

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

                            $idLuz = (int) $luz['id_luz'];
                            ?>

                            <tr data-estado="<?= htmlspecialchars($luz['estado_seguimiento'], ENT_QUOTES, 'UTF-8') ?>">

                                <td>
                                    <strong><?= $idLuz ?></strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($luz['nombre_area'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($luz['tipo_iluminacion'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($luz['marca'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($luz['modelo'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($luz['duracion_bateria'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($luz['estado_fisico'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($luz['fecha_formateada'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <span class="badge <?= $claseEstado ?>">
                                        <?= htmlspecialchars($luz['estado_seguimiento'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="d-flex gap-1">

                                        <a
                                            href="ver_luces.php?id=<?= $idLuz ?>"
                                            class="btn btn-outline-primary btn-sm"
                                            title="Ver luz de emergencia"
                                        >
                                            <i class="fas fa-eye me-1"></i>
                                            Ver
                                        </a>

                                        <button
                                            type="button"
                                            class="btn btn-outline-dark btn-sm"
                                            onclick="mostrarQR(<?= $idLuz ?>)"
                                            title="Generar código QR"
                                        >
                                            <i class="fas fa-qrcode me-1"></i>
                                            QR
                                        </button>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr data-estado="">
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="fas fa-info-circle me-1"></i>
                                No hay luces de emergencia registradas en el Segundo Nivel.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="text-muted small">
                Total de luces registradas: <?= count($listaLuces) ?>
            </div>

        </div>

    </div>

</div>

<!-- =====================================================
     MODAL DEL CÓDIGO QR
     ===================================================== -->

<div class="modal fade" id="modalQR" tabindex="-1" aria-hidden="true">

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
                    Escanea este código para consultar la información de la luz de emergencia.
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

<!-- =====================================================
     LIBRERÍAS
     ===================================================== -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- =====================================================
     BUSCADOR, FILTRO Y QR
     ===================================================== -->

<script>
/* ==========================================================
   BUSCADOR Y FILTRO
   ========================================================== */

const buscador = document.getElementById('buscadorLuces');
const filtroEstado = document.getElementById('filtroEstado');

function filtrarTabla() {

    const textoBusqueda = buscador.value.toLowerCase().trim();
    const estadoSeleccionado = filtroEstado.value;

    const filas = document.querySelectorAll('#tablaLuces tbody tr');

    filas.forEach(function (fila) {

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


/* ==========================================================
   GENERAR CÓDIGO QR
   ========================================================== */

function mostrarQR(idLuz) {

    const contenedorQR = document.getElementById('contenedorQR');

    // Limpiar el código QR anterior.
    contenedorQR.innerHTML = '';

    // Mostrar identificador.
    document.getElementById('folioQR').textContent =
        'Luz de emergencia: ' + idLuz;

    /*
     * Generar la URL desde la dirección actual.
     * Así se conserva el host y el puerto con los que se abrió el módulo.
     */
    const urlLuz = new URL('ver_luces.php', window.location.href);
    urlLuz.searchParams.set('id', idLuz);

    // Crear el código QR.
    new QRCode(contenedorQR, {
        text: urlLuz.href,
        width: 250,
        height: 250
    });

    // Abrir la ventana modal.
    const modal = bootstrap.Modal.getOrCreateInstance(
        document.getElementById('modalQR')
    );

    modal.show();
}
</script>

</body>
</html>