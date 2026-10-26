<?php
/* ==========================================================
   SIPV — BOTIQUINES DEL SÓTANO
   ========================================================== */

require_once __DIR__ . '/../../../clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ==========================================================
   CONFIGURACIÓN
   ========================================================== */

const DIAS_REVISION = 14;
const DIAS_AVISO_REVISION = 3;
const DIAS_AVISO_CADUCIDAD = 30;

const ESTADOS_MEDICAMENTO = [
    'Vigente',
    'Próximo a caducar',
    'Caducado'
];

$puedeEditar = true;

if (empty($_SESSION['csrf_botiquines'])) {
    $_SESSION['csrf_botiquines'] = bin2hex(random_bytes(16));
}

$csrf = $_SESSION['csrf_botiquines'];

/* ==========================================================
   FUNCIONES
   ========================================================== */

function formatoFecha($fecha)
{
    if (empty($fecha)) {
        return 'Sin registrar';
    }

    $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);

    return $fechaObj
        ? $fechaObj->format('d/m/Y')
        : $fecha;
}

function diasHasta($fecha)
{
    if (empty($fecha)) {
        return null;
    }

    try {
        $hoy = new DateTime('today');
        $otra = new DateTime($fecha);

        return (int) $hoy->diff($otra)->format('%r%a');
    } catch (Exception $e) {
        return null;
    }
}

function fechaValida($fecha)
{
    if (!is_string($fecha) || $fecha === '') {
        return false;
    }

    $d = DateTime::createFromFormat('!Y-m-d', $fecha);

    return $d && $d->format('Y-m-d') === $fecha;
}

function redirigirDetalle($idBotiquin, $mensaje)
{
    $ruta = strtok($_SERVER['REQUEST_URI'], '?');

    header(
        'Location: ' . $ruta . '?' .
        http_build_query([
            'botiquin' => (int) $idBotiquin,
            'msg' => $mensaje
        ])
    );

    exit;
}

/* ==========================================================
   PROCESAR FORMULARIOS
   ========================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idBot = (int) ($_POST['id_botiquin'] ?? 0);
    $accion = $_POST['accion'] ?? '';

    if (
        !$puedeEditar ||
        !hash_equals($csrf, $_POST['csrf'] ?? '')
    ) {
        redirigirDetalle($idBot, 'error');
    }

    // Verificar que el botiquín exista y pertenezca al Primer Nivel.
    $verifica = pg_query_params(
        $conexion,
        "SELECT 1
         FROM public.tb_botiquines b
         JOIN public.tb_areas a ON a.id_area = b.id_area
         WHERE b.id_botiquin = $1
           AND a.nivel = 'Primer Nivel'",
        [$idBot]
    );

    if (!$verifica || pg_num_rows($verifica) === 0) {
        redirigirDetalle($idBot, 'error');
    }

    /* AGREGAR / EDITAR MEDICAMENTO */

    if ($accion === 'guardar_medicamento') {

        $idMed = (int) ($_POST['id_medicamento'] ?? 0);
        $nombre = trim($_POST['nombre_medicamento'] ?? '');
        $presentacion = trim($_POST['presentacion'] ?? '');
        $cantidad = $_POST['cantidad'] ?? '';
        $minima = $_POST['cantidad_minima'] ?? '';
        $caducidad = trim($_POST['fecha_caducidad'] ?? '');
        $estado = $_POST['estado'] ?? '';

        if (
            $nombre === '' ||
            !ctype_digit((string) $cantidad) ||
            !ctype_digit((string) $minima) ||
            !fechaValida($caducidad) ||
            !in_array($estado, ESTADOS_MEDICAMENTO, true)
        ) {
            redirigirDetalle($idBot, 'invalido');
        }

        $presentacion = $presentacion === ''
            ? null
            : $presentacion;

        if ($idMed > 0) {

            $resultado = pg_query_params(
                $conexion,
                "UPDATE public.tb_medicamentos
                 SET nombre_medicamento = $1,
                     presentacion = $2,
                     cantidad = $3,
                     cantidad_minima = $4,
                     fecha_caducidad = $5,
                     estado = $6
                 WHERE id_medicamento = $7
                   AND id_botiquin = $8",
                [
                    $nombre,
                    $presentacion,
                    (int) $cantidad,
                    (int) $minima,
                    $caducidad,
                    $estado,
                    $idMed,
                    $idBot
                ]
            );

            redirigirDetalle($idBot, $resultado ? 'editado' : 'error');

        } else {

            $resultado = pg_query_params(
                $conexion,
                "INSERT INTO public.tb_medicamentos
                    (id_botiquin, nombre_medicamento, presentacion,
                     cantidad, cantidad_minima, fecha_caducidad, estado)
                 VALUES ($1, $2, $3, $4, $5, $6, $7)",
                [
                    $idBot,
                    $nombre,
                    $presentacion,
                    (int) $cantidad,
                    (int) $minima,
                    $caducidad,
                    $estado
                ]
            );

            redirigirDetalle($idBot, $resultado ? 'agregado' : 'error');
        }
    }

    /* ELIMINAR MEDICAMENTO */

    if ($accion === 'eliminar_medicamento') {

        $idMed = (int) ($_POST['id_medicamento'] ?? 0);

        $resultado = pg_query_params(
            $conexion,
            "DELETE FROM public.tb_medicamentos
             WHERE id_medicamento = $1
               AND id_botiquin = $2",
            [$idMed, $idBot]
        );

        redirigirDetalle($idBot, $resultado ? 'eliminado' : 'error');
    }

    /* REGISTRAR REVISIÓN */

    if ($accion === 'aceptar_revision') {

        $resultado = pg_query_params(
            $conexion,
            "UPDATE public.tb_botiquines
             SET fecha_ultima_revision = CURRENT_DATE,
                 fecha_proxima_revision = CURRENT_DATE + $1::integer
             WHERE id_botiquin = $2",
            [DIAS_REVISION, $idBot]
        );

        redirigirDetalle($idBot, $resultado ? 'revisado' : 'error');
    }

    redirigirDetalle($idBot, 'error');
}

/* ==========================================================
   MODO DETALLE DEL BOTIQUÍN
   ========================================================== */

$modoDetalle = isset($_GET['botiquin'])
    && ctype_digit((string) $_GET['botiquin'])
    && (int) $_GET['botiquin'] > 0;

$botiquinDetalle = null;
$medicamentos = [];
$medCaducados = [];
$medPorCaducar = [];

if ($modoDetalle) {

    $idBotiquin = (int) $_GET['botiquin'];

    $sqlDetalle = "
        SELECT
            b.id_botiquin,
            b.id_area,
            b.tipo_botiquin,
            b.estado_fisico,
            b.capacidad,
            b.fecha_instalacion,
            b.fecha_ultima_revision,
            b.fecha_proxima_revision,
            b.evidencia_foto,
            b.observaciones,
            a.nombre_area,
            a.nivel
        FROM public.tb_botiquines b
        JOIN public.tb_areas a ON a.id_area = b.id_area
        WHERE b.id_botiquin = $1
          AND a.nivel = 'Primer Nivel'
        LIMIT 1
    ";

    $consulta = pg_query_params(
        $conexion,
        $sqlDetalle,
        [$idBotiquin]
    );

    if ($consulta) {
        $botiquinDetalle = pg_fetch_assoc($consulta);
    }

    if ($botiquinDetalle) {

        $consultaMedicamentos = pg_query_params(
            $conexion,
            "SELECT
                id_medicamento,
                nombre_medicamento,
                presentacion,
                cantidad,
                cantidad_minima,
                fecha_caducidad,
                estado,
                evidencia_foto
             FROM public.tb_medicamentos
             WHERE id_botiquin = $1
             ORDER BY nombre_medicamento ASC",
            [$idBotiquin]
        );

        if ($consultaMedicamentos) {

            while ($med = pg_fetch_assoc($consultaMedicamentos)) {

                $dias = diasHasta($med['fecha_caducidad']);
                $med['dias_caducidad'] = $dias;

                if ($dias !== null && $dias < 0) {
                    $medCaducados[] = $med;
                } elseif (
                    $dias !== null &&
                    $dias <= DIAS_AVISO_CADUCIDAD
                ) {
                    $medPorCaducar[] = $med;
                }

                $medicamentos[] = $med;
            }
        }
    }
}

/* ==========================================================
   CONTEOS DE BOTIQUINES DEL SÓTANO
   ========================================================== */

$sqlConteos = "
    SELECT
        COUNT(*) AS total,

        COUNT(*) FILTER (
            WHERE fecha_proxima_revision IS NOT NULL
              AND fecha_proxima_revision >= CURRENT_DATE
              AND fecha_proxima_revision <=
                  CURRENT_DATE + " . DIAS_AVISO_REVISION . "
        ) AS por_vencer,

        COUNT(*) FILTER (
            WHERE fecha_proxima_revision < CURRENT_DATE
        ) AS vencidos,

        COUNT(*) FILTER (
            WHERE fecha_proxima_revision >
                  CURRENT_DATE + " . DIAS_AVISO_REVISION . "
        ) AS al_corriente,

        COUNT(*) FILTER (
            WHERE fecha_proxima_revision IS NULL
        ) AS sin_fecha

    FROM public.tb_botiquines b
    JOIN public.tb_areas a ON a.id_area = b.id_area
    WHERE a.nivel = 'Primer Nivel'
";

$resultadoConteos = pg_query($conexion, $sqlConteos);

$total = 0;
$alCorriente = 0;
$porVencer = 0;
$vencidos = 0;
$sinFecha = 0;

if ($resultadoConteos) {
    $conteos = pg_fetch_assoc($resultadoConteos);

    $total = (int) $conteos['total'];
    $alCorriente = (int) $conteos['al_corriente'];
    $porVencer = (int) $conteos['por_vencer'];
    $vencidos = (int) $conteos['vencidos'];
    $sinFecha = (int) $conteos['sin_fecha'];
}

/* ==========================================================
   PORCENTAJE DE CUMPLIMIENTO
   ========================================================== */

$cumplimiento = $total > 0
    ? round(($alCorriente / $total) * 100)
    : 0;

/* ==========================================================
   ALERTAS DE MEDICAMENTOS POR BOTIQUÍN
   ========================================================== */

$sqlAlertas = "
    SELECT
        b.id_botiquin,

        COUNT(m.id_medicamento) FILTER (
            WHERE m.fecha_caducidad < CURRENT_DATE
        ) AS caducados,

        COUNT(m.id_medicamento) FILTER (
            WHERE m.fecha_caducidad >= CURRENT_DATE
              AND m.fecha_caducidad <=
                  CURRENT_DATE + " . DIAS_AVISO_CADUCIDAD . "
        ) AS por_caducar

    FROM public.tb_botiquines b
    JOIN public.tb_areas a ON a.id_area = b.id_area
    LEFT JOIN public.tb_medicamentos m
        ON m.id_botiquin = b.id_botiquin
    WHERE a.nivel = 'Primer Nivel'
    GROUP BY b.id_botiquin
";

$alertasPorBotiquin = [];
$totalCaducados = 0;
$totalPorCaducar = 0;

$resultadoAlertas = pg_query($conexion, $sqlAlertas);

if ($resultadoAlertas) {
    while ($fila = pg_fetch_assoc($resultadoAlertas)) {

        $id = (int) $fila['id_botiquin'];
        $cad = (int) $fila['caducados'];
        $porCad = (int) $fila['por_caducar'];

        $alertasPorBotiquin[$id] = [
            'caducados' => $cad,
            'por_caducar' => $porCad
        ];

        $totalCaducados += $cad;
        $totalPorCaducar += $porCad;
    }
}

/* ==========================================================
   LISTA DE BOTIQUINES DEL SÓTANO
   ========================================================== */

$sqlBotiquines = "
    SELECT
        b.id_botiquin AS identificador,
        b.fecha_ultima_revision,
        b.fecha_proxima_revision,
        b.estado_fisico,
        b.tipo_botiquin,
        a.nombre_area
    FROM public.tb_botiquines b
    JOIN public.tb_areas a ON a.id_area = b.id_area
    WHERE a.nivel = 'Primer Nivel'
    ORDER BY b.id_botiquin ASC
";

$resultadoBotiquines = pg_query($conexion, $sqlBotiquines);

/* ==========================================================
   MENSAJES
   ========================================================== */

$mensajes = [
    'agregado' => ['success', 'Medicamento agregado correctamente.'],
    'editado' => ['success', 'Medicamento actualizado correctamente.'],
    'eliminado' => ['success', 'Medicamento eliminado correctamente.'],
    'revisado' => [
        'success',
        'Revisión registrada. La próxima revisión quedó a ' .
        DIAS_REVISION . ' días.'
    ],
    'invalido' => [
        'warning',
        'Datos incompletos o no válidos. Revisa el formulario.'
    ],
    'error' => ['danger', 'No se pudo realizar la acción.']
];

$mensajeActual = $mensajes[$_GET['msg'] ?? ''] ?? null;

$rutaActual = strtok($_SERVER['REQUEST_URI'], '?');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Botiquines - Primer Nivel | SIPV</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
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

        .card-header {
            font-weight: 600;
        }

        .qr-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 250px;
        }

        /* Zona de silencio blanca para que el celular lea el QR */
        .qr-container img,
        .qr-container canvas {
            background: #fff;
            padding: 16px;
            max-width: 100%;
            height: auto;
            box-sizing: content-box;
        }

        .botiquin-titulo {
            font-weight: 700;
        }

        .medicamento-vigente {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .medicamento-proximo {
            background-color: #fff3cd;
            color: #664d03;
        }

        .medicamento-caducado {
            background-color: #f8d7da;
            color: #842029;
        }

        .badge-estado {
            padding: 7px 12px;
            border-radius: 20px;
        }

        .fila-caducada > td {
            background-color: #fdf0f1 !important;
        }

        .fila-por-caducar > td {
            background-color: #fffaf0 !important;
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
                    Primer Nivel
                </h6>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">

                <a
                    href="../../areas.php/primerNivel.php"
                    class="btn btn-outline-danger"
                >
                    <i class="bi bi-arrow-left me-1"></i>
                    Volver
                </a>

            </div>

        </div>

    </nav>


<div class="container-fluid contenedor-metricas">

<?php if ($mensajeActual): ?>
    <div class="alert alert-<?php echo $mensajeActual[0]; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($mensajeActual[1]); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>


<?php if ($modoDetalle): ?>

    <?php if ($botiquinDetalle): ?>

        <?php
        $codigoBotiquin = 'BOT-' .
            str_pad($botiquinDetalle['id_botiquin'], 3, '0', STR_PAD_LEFT);

        $diasRevision = diasHasta(
            $botiquinDetalle['fecha_proxima_revision']
        );
        ?>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="botiquin-titulo mb-1">
                    <i class="bi bi-bandaid"></i>
                    Botiquín <?php echo htmlspecialchars($codigoBotiquin); ?>
                </h2>
                <p class="text-muted mb-0">Información del botiquín del Primer Nivel</p>
            </div>

            <div class="d-flex gap-2">
                <?php if ($puedeEditar): ?>
                    <form method="post"
                          onsubmit="return confirmarRevision(<?php echo count($medCaducados); ?>);">
                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                        <input type="hidden" name="accion" value="aceptar_revision">
                        <input type="hidden" name="id_botiquin"
                               value="<?php echo (int) $botiquinDetalle['id_botiquin']; ?>">

                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check2-circle"></i>
                            Aceptar revisión
                        </button>
                    </form>
                <?php endif; ?>

                <a href="<?php echo htmlspecialchars($rutaActual); ?>"
                   class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Regresar
                </a>
            </div>
        </div>

        <?php if ($diasRevision !== null && $diasRevision < 0): ?>
            <div class="alert alert-danger">
                <strong>Revisión vencida.</strong>
                Hace <?php echo abs($diasRevision); ?> día(s).
                Revisa el botiquín y pulsa «Aceptar revisión».
            </div>
        <?php elseif ($diasRevision !== null && $diasRevision <= DIAS_AVISO_REVISION): ?>
            <div class="alert alert-warning">
                La próxima revisión es en <?php echo $diasRevision; ?> día(s).
            </div>
        <?php elseif ($diasRevision === null): ?>
            <div class="alert alert-secondary">
                Este botiquín no tiene fecha de revisión registrada.
            </div>
        <?php endif; ?>

        <?php if (count($medCaducados) > 0): ?>
            <div class="alert alert-danger">
                <h5><?php echo count($medCaducados); ?> medicamento(s) caducado(s)</h5>
                <ul class="mb-0">
                    <?php foreach ($medCaducados as $med): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($med['nombre_medicamento']); ?></strong>
                            — caducó el <?php echo formatoFecha($med['fecha_caducidad']); ?>.
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (count($medPorCaducar) > 0): ?>
            <div class="alert alert-warning">
                <h5><?php echo count($medPorCaducar); ?> medicamento(s) por caducar</h5>
                <ul class="mb-0">
                    <?php foreach ($medPorCaducar as $med): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($med['nombre_medicamento']); ?></strong>
                            — caduca el <?php echo formatoFecha($med['fecha_caducidad']); ?>.
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>


        <div class="row g-4">

            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <i class="bi bi-info-circle"></i>
                        Información del botiquín
                    </div>

                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <strong>Identificador:</strong>
                                <div><?php echo htmlspecialchars($codigoBotiquin); ?></div>
                            </div>

                            <div class="col-md-6">
                                <strong>Área:</strong>
                                <div><?php echo htmlspecialchars($botiquinDetalle['nombre_area'] ?? 'Sin registrar'); ?></div>
                            </div>

                            <div class="col-md-6">
                                <strong>Nivel:</strong>
                                <div><?php echo htmlspecialchars($botiquinDetalle['nivel'] ?? 'Primer Nivel'); ?></div>
                            </div>

                            <div class="col-md-6">
                                <strong>Tipo de botiquín:</strong>
                                <div><?php echo htmlspecialchars($botiquinDetalle['tipo_botiquin'] ?? 'Sin registrar'); ?></div>
                            </div>

                            <div class="col-md-6">
                                <strong>Estado físico:</strong>
                                <div><?php echo htmlspecialchars($botiquinDetalle['estado_fisico'] ?? 'Sin registrar'); ?></div>
                            </div>

                            <div class="col-md-6">
                                <strong>Capacidad:</strong>
                                <div><?php echo htmlspecialchars($botiquinDetalle['capacidad'] ?? 'Sin registrar'); ?></div>
                            </div>

                            <div class="col-md-6">
                                <strong>Fecha de última revisión:</strong>
                                <div><?php echo formatoFecha($botiquinDetalle['fecha_ultima_revision']); ?></div>
                            </div>

                            <div class="col-md-6">
                                <strong>Próxima revisión:</strong>
                                <div><?php echo formatoFecha($botiquinDetalle['fecha_proxima_revision']); ?></div>
                            </div>

                            <div class="col-12">
                                <strong>Observaciones:</strong>
                                <div class="mt-2 p-3 bg-light rounded">
                                    <?php
                                    echo !empty($botiquinDetalle['observaciones'])
                                        ? nl2br(htmlspecialchars($botiquinDetalle['observaciones']))
                                        : 'Sin observaciones registradas.';
                                    ?>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <i class="bi bi-qr-code"></i>
                        Identificador QR
                    </div>

                    <div class="card-body text-center">
                        <div id="qrDetalle" class="qr-container"></div>
                        <h5 class="mt-3"><?php echo htmlspecialchars($codigoBotiquin); ?></h5>
                        <small class="text-muted">Código de identificación del botiquín</small>
                    </div>
                </div>
            </div>

        </div>


        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-capsule"></i>
                    Medicamentos del botiquín
                </span>

                <?php if ($puedeEditar): ?>
                    <button type="button"
                            class="btn btn-primary btn-sm"
                            onclick="abrirModalMedicamento()">
                        <i class="bi bi-plus-circle"></i>
                        Agregar medicamento
                    </button>
                <?php endif; ?>
            </div>

            <div class="card-body">

                <?php if (count($medicamentos) > 0): ?>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Medicamento</th>
                                    <th>Presentación</th>
                                    <th>Cantidad</th>
                                    <th>Cantidad mínima</th>
                                    <th>Fecha de caducidad</th>
                                    <th>Estado</th>
                                    <?php if ($puedeEditar): ?>
                                        <th class="text-center">Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>

                            <tbody>
                            <?php foreach ($medicamentos as $med): ?>

                                <?php
                                $estado = trim($med['estado'] ?? '');
                                $dias = $med['dias_caducidad'];

                                $claseEstado = '';

                                if ($estado === 'Vigente') {
                                    $claseEstado = 'medicamento-vigente';
                                } elseif ($estado === 'Próximo a caducar') {
                                    $claseEstado = 'medicamento-proximo';
                                } elseif ($estado === 'Caducado') {
                                    $claseEstado = 'medicamento-caducado';
                                }

                                $claseFila = '';

                                if ($dias !== null && $dias < 0) {
                                    $claseFila = 'fila-caducada';
                                } elseif ($dias !== null && $dias <= DIAS_AVISO_CADUCIDAD) {
                                    $claseFila = 'fila-por-caducar';
                                }
                                ?>

                                <tr class="<?php echo $claseFila; ?>">
                                    <td><?php echo htmlspecialchars($med['nombre_medicamento']); ?></td>

                                    <td><?php echo htmlspecialchars($med['presentacion'] ?? 'Sin registrar'); ?></td>

                                    <td><?php echo (int) $med['cantidad']; ?></td>

                                    <td><?php echo (int) $med['cantidad_minima']; ?></td>

                                    <td>
                                        <?php echo formatoFecha($med['fecha_caducidad']); ?>

                                        <?php if ($dias !== null && $dias < 0): ?>
                                            <br>
                                            <small class="text-danger fw-semibold">
                                                Caducó hace <?php echo abs($dias); ?> día(s)
                                            </small>
                                        <?php elseif ($dias !== null && $dias <= DIAS_AVISO_CADUCIDAD): ?>
                                            <br>
                                            <small class="text-warning-emphasis fw-semibold">
                                                Caduca en <?php echo $dias; ?> día(s)
                                            </small>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="badge badge-estado <?php echo $claseEstado; ?>">
                                            <?php echo htmlspecialchars($estado); ?>
                                        </span>
                                    </td>

                                    <?php if ($puedeEditar): ?>
                                        <td class="text-center text-nowrap">

                                            <button
                                                type="button"
                                                class="btn btn-outline-primary btn-sm"
                                                title="Editar"
                                                data-id="<?php echo (int) $med['id_medicamento']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($med['nombre_medicamento'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-presentacion="<?php echo htmlspecialchars($med['presentacion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                data-cantidad="<?php echo (int) $med['cantidad']; ?>"
                                                data-minima="<?php echo (int) $med['cantidad_minima']; ?>"
                                                data-caducidad="<?php echo htmlspecialchars($med['fecha_caducidad'] ?? ''); ?>"
                                                data-estado="<?php echo htmlspecialchars($estado, ENT_QUOTES, 'UTF-8'); ?>"
                                                onclick="abrirModalMedicamento(this)">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <button
                                                type="button"
                                                class="btn btn-outline-danger btn-sm"
                                                title="Eliminar"
                                                data-id="<?php echo (int) $med['id_medicamento']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($med['nombre_medicamento'], ENT_QUOTES, 'UTF-8'); ?>"
                                                onclick="abrirModalEliminar(this)">
                                                <i class="bi bi-trash"></i>
                                            </button>

                                        </td>
                                    <?php endif; ?>
                                </tr>

                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php else: ?>

                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle"></i>
                        Este botiquín todavía no tiene medicamentos registrados.
                    </div>

                <?php endif; ?>

            </div>
        </div>


        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white">
                <i class="bi bi-clipboard-check"></i>
                Estados de medicamentos
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-4">
                        <div class="p-3 rounded medicamento-vigente">
                            <strong>Vigente</strong><br>
                            <small>El medicamento está registrado como vigente.</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded medicamento-proximo">
                            <strong>Próximo a caducar</strong><br>
                            <small>Requiere seguimiento por su fecha de caducidad.</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded medicamento-caducado">
                            <strong>Caducado</strong><br>
                            <small>El medicamento se encuentra registrado como caducado.</small>
                        </div>
                    </div>

                </div>
            </div>
        </div>


        <?php if ($puedeEditar): ?>

        <!-- MODAL AGREGAR / EDITAR MEDICAMENTO -->

        <div class="modal fade" id="modalMedicamento" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">

                <form method="post" class="modal-content">

                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="accion" value="guardar_medicamento">
                    <input type="hidden" name="id_botiquin"
                           value="<?php echo (int) $botiquinDetalle['id_botiquin']; ?>">
                    <input type="hidden" name="id_medicamento" id="medId" value="0">

                    <div class="modal-header">
                        <h5 class="modal-title" id="medTitulo">Agregar medicamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label" for="medNombre">Medicamento</label>
                                <input type="text" class="form-control"
                                       name="nombre_medicamento" id="medNombre"
                                       maxlength="150" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="medPresentacion">Presentación</label>
                                <input type="text" class="form-control"
                                       name="presentacion" id="medPresentacion"
                                       maxlength="150" placeholder="Ej. Tabletas 500 mg">
                            </div>

                            <div class="col-6">
                                <label class="form-label" for="medCantidad">Cantidad</label>
                                <input type="number" class="form-control"
                                       name="cantidad" id="medCantidad"
                                       min="0" step="1" required>
                            </div>

                            <div class="col-6">
                                <label class="form-label" for="medMinima">Cantidad mínima</label>
                                <input type="number" class="form-control"
                                       name="cantidad_minima" id="medMinima"
                                       min="0" step="1" required>
                            </div>

                            <div class="col-6">
                                <label class="form-label" for="medCaducidad">Fecha de caducidad</label>
                                <input type="date" class="form-control"
                                       name="fecha_caducidad" id="medCaducidad" required>
                            </div>

                            <div class="col-6">
                                <label class="form-label" for="medEstado">Estado</label>
                                <select class="form-select" name="estado" id="medEstado" required>
                                    <?php foreach (ESTADOS_MEDICAMENTO as $opcion): ?>
                                        <option value="<?php echo htmlspecialchars($opcion); ?>">
                                            <?php echo htmlspecialchars($opcion); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>

                </form>
            </div>
        </div>


        <!-- MODAL ELIMINAR MEDICAMENTO -->

        <div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">

                <form method="post" class="modal-content">

                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="accion" value="eliminar_medicamento">
                    <input type="hidden" name="id_botiquin"
                           value="<?php echo (int) $botiquinDetalle['id_botiquin']; ?>">
                    <input type="hidden" name="id_medicamento" id="elimId" value="0">

                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-trash"></i>
                            Eliminar medicamento
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        ¿Seguro que quieres eliminar
                        <strong id="elimNombre"></strong>?
                        Esta acción no se puede deshacer.
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </div>

                </form>
            </div>
        </div>

        <?php endif; ?>


    <?php else: ?>

        <div class="alert alert-danger">
            <h4><i class="bi bi-exclamation-triangle"></i> Botiquín no encontrado</h4>
            <p>No se encontró el botiquín solicitado o no pertenece al Primer Nivel.</p>
            <a href="<?php echo htmlspecialchars($rutaActual); ?>" class="btn btn-secondary">
                Regresar a botiquines
            </a>
        </div>

    <?php endif; ?>


<?php else: ?>

    <!-- =====================================================
        VISTA PRINCIPAL (mismo diseño que extintores)
        ===================================================== -->

    <?php if ($totalCaducados > 0): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            Hay <strong><?php echo $totalCaducados; ?></strong>
            medicamento(s) caducado(s) en los botiquines del Primer Nivel.
        </div>
    <?php endif; ?>

    <?php if ($totalPorCaducar > 0): ?>
        <div class="alert alert-warning">
            <i class="bi bi-hourglass-split"></i>
            Hay <strong><?php echo $totalPorCaducar; ?></strong>
            medicamento(s) por caducar en los próximos
            <?php echo DIAS_AVISO_CADUCIDAD; ?> días.
        </div>
    <?php endif; ?>


    <!-- MÉTRICAS -->

    <div class="row g-3 mb-4">

        <!-- CUMPLIMIENTO -->
        <div class="col-lg-3 col-md-6">
            <div class="card p-3 border-start border-success border-4 shadow-sm">
                <small class="text-muted">Cumplimiento</small>
                <h3 class="fw-bold text-success m-0">
                    <?php echo $cumplimiento; ?>%
                </h3>
            </div>
        </div>

        <!-- AL CORRIENTE -->
        <div class="col-lg-3 col-md-6">
            <div class="card p-3 border-start border-success border-4 shadow-sm">
                <small class="text-muted">Al corriente</small>
                <h3 class="fw-bold m-0">
                    <?php echo $alCorriente; ?>
                </h3>
            </div>
        </div>

        <!-- POR VENCER -->
        <div class="col-lg-3 col-md-6">
            <div class="card p-3 border-start border-warning border-4 shadow-sm">
                <small class="text-muted">Por vencer</small>
                <h3 class="fw-bold text-warning m-0">
                    <?php echo $porVencer; ?>
                </h3>
            </div>
        </div>

        <!-- VENCIDOS -->
        <div class="col-lg-3 col-md-6">
            <div class="card p-3 border-start border-danger border-4 shadow-sm">
                <small class="text-muted">Vencidos</small>
                <h3 class="fw-bold text-danger m-0">
                    <?php echo $vencidos; ?>
                </h3>
            </div>
        </div>

    </div>


    <!-- ENCABEZADO ACTIVOS + BOTÓN AGREGAR -->

    <div class="d-flex justify-content-between align-items-center mt-1 pt-3">

        <h5>ACTIVOS</h5>

        <!-- Ajusta la ruta a tu archivo para agregar botiquines -->
        <a
            href="../../agre.botiquines/botiquines.php"
            class="btn btn-primary"
        >
            <i class="bi bi-plus-lg me-1"></i>
            Agregar
        </a>

    </div>


    <!-- TABLA DE BOTIQUINES -->

    <div class="row g-4 mt-1">

        <div class="col-12">

            <div class="card shadow-sm">

                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-list-check me-2 text-primary"></i>
                        Botiquines del Primer Nivel
                    </h5>
                </div>

                <div class="card-body">

                    <!-- BUSCADOR Y FILTRO -->
                    <div class="row g-2 mb-3">

                        <div class="col-md-6">
                            <input
                                type="text"
                                id="buscadorBotiquines"
                                class="form-control"
                                placeholder="Buscar por folio o área..."
                            >
                        </div>

                        <div class="col-md-4">
                            <select id="filtroEstado" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="al corriente">Al corriente</option>
                                <option value="por vencer">Por vencer</option>
                                <option value="vencido">Vencido</option>
                                <option value="sin fecha">Sin fecha</option>
                            </select>
                        </div>

                    </div>


                    <!-- TABLA -->
                    <div class="table-responsive">

                        <table class="table table-hover align-middle" id="tablaBotiquines">

                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Área</th>
                                    <th>Última revisión</th>
                                    <th>Próx. revisión</th>
                                    <th>Estado</th>
                                    <th>Medicamentos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php
                            $hayBotiquines = false;

                            if ($resultadoBotiquines):
                                while ($botiquin = pg_fetch_assoc($resultadoBotiquines)):

                                    $hayBotiquines = true;

                                    $numeroBotiquin = (int) $botiquin['identificador'];

                                    $codigo = 'BOT-' .
                                        str_pad($numeroBotiquin, 3, '0', STR_PAD_LEFT);

                                    $diferencia = diasHasta(
                                        $botiquin['fecha_proxima_revision']
                                    );

                                    $estadoRevision = 'Sin fecha';
                                    $claseEstado = 'bg-secondary';

                                    if ($diferencia !== null) {
                                        if ($diferencia < 0) {
                                            $estadoRevision = 'Vencido';
                                            $claseEstado = 'bg-danger';
                                        } elseif ($diferencia <= DIAS_AVISO_REVISION) {
                                            $estadoRevision = 'Por vencer';
                                            $claseEstado = 'bg-warning text-dark';
                                        } else {
                                            $estadoRevision = 'Al corriente';
                                            $claseEstado = 'bg-success';
                                        }
                                    }

                                    $alerta = $alertasPorBotiquin[$numeroBotiquin]
                                        ?? ['caducados' => 0, 'por_caducar' => 0];
                            ?>

                                <tr data-estado="<?php echo strtolower($estadoRevision); ?>">

                                    <!-- FOLIO -->
                                    <td>
                                        <strong><?php echo htmlspecialchars($codigo); ?></strong>
                                    </td>

                                    <!-- ÁREA -->
                                    <td><?php echo htmlspecialchars($botiquin['nombre_area']); ?></td>

                                    <!-- ÚLTIMA REVISIÓN -->
                                    <td><?php echo formatoFecha($botiquin['fecha_ultima_revision']); ?></td>

                                    <!-- PRÓXIMA REVISIÓN -->
                                    <td><?php echo formatoFecha($botiquin['fecha_proxima_revision']); ?></td>

                                    <!-- ESTADO -->
                                    <td>
                                        <span class="badge <?php echo $claseEstado; ?>">
                                            <?php echo htmlspecialchars($estadoRevision); ?>
                                        </span>
                                    </td>

                                    <!-- MEDICAMENTOS -->
                                    <td>
                                        <?php if ($alerta['caducados'] > 0): ?>
                                            <span class="badge bg-danger">
                                                <?php echo $alerta['caducados']; ?> caducado(s)
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($alerta['por_caducar'] > 0): ?>
                                            <span class="badge bg-warning text-dark">
                                                <?php echo $alerta['por_caducar']; ?> por caducar
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($alerta['caducados'] === 0 && $alerta['por_caducar'] === 0): ?>
                                            <span class="text-muted">Sin alertas</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- ACCIONES (VER + QR) -->
                                    <td>

                                        <div class="d-flex gap-1">

                                            <a
                                                class="btn btn-outline-primary btn-sm"
                                                title="Ver botiquín"
                                                href="<?php echo htmlspecialchars($rutaActual); ?>?botiquin=<?php echo $numeroBotiquin; ?>"
                                            >
                                                <i class="bi bi-eye me-1"></i>
                                                Ver
                                            </a>

                                            <button
                                                type="button"
                                                class="btn btn-outline-dark btn-sm"
                                                onclick="mostrarQR(<?php echo $numeroBotiquin; ?>)"
                                            >
                                                <i class="bi bi-qr-code me-1"></i>
                                                QR
                                            </button>

                                        </div>

                                    </td>

                                </tr>

                            <?php
                                endwhile;
                            endif;
                            ?>

                            <!-- SIN REGISTROS -->
                            <?php if (!$hayBotiquines): ?>

                                <tr data-estado="">
                                    <td colspan="7" class="text-center text-muted">
                                        No hay botiquines registrados en el Primer Nivel.
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

<?php endif; ?>

</div>


<!-- MODAL DEL QR -->

<div class="modal fade" id="modalQR" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="tituloQR">QR Botiquín</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center">
                <div id="codigoQR" class="qr-container"></div>

                <p class="text-muted mt-3 mb-0">
                    Escanea este código para consultar la información del botiquín.
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>

        </div>
    </div>
</div>


<!-- LIBRERÍAS -->

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<script>
/* ==========================================================
   CONFIGURACIÓN DEL SERVIDOR
   ========================================================== */

// Usa la IPv4 actual de la computadora donde está XAMPP
// (córrelo con "ipconfig" en CMD si el router la cambió).
const IP_SERVIDOR = '192.168.1.65';


/* ==========================================================
   GENERAR LA URL DEL BOTIQUÍN
   ========================================================== */

function generarUrlBotiquin(numero) {

    let host = window.location.hostname;

    if (host === 'localhost' || host === '127.0.0.1') {
        host = IP_SERVIDOR;
    }

    const protocolo = window.location.protocol;
    const puerto = window.location.port
        ? ':' + window.location.port
        : '';

    return protocolo + '//' + host + puerto +
           window.location.pathname +
           '?botiquin=' + encodeURIComponent(numero);
}


/* ==========================================================
   CREAR QR (una sola función para el modal y el detalle)
   ========================================================== */

function crearQR(contenedor, url) {

    contenedor.innerHTML = '';

    new QRCode(contenedor, {
        text: url,
        width: 300,
        height: 300,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.L
    });
}


/* ==========================================================
   MOSTRAR QR EN EL MODAL
   ========================================================== */

function mostrarQR(identificador) {

    const numero = parseInt(identificador, 10);

    if (!Number.isInteger(numero) || numero <= 0) {
        alert('El identificador del botiquín no es válido.');
        return;
    }

    if (typeof QRCode === 'undefined') {
        alert('No se pudo cargar la librería QR. Revisa tu conexión a Internet.');
        return;
    }

    const contenedor = document.getElementById('codigoQR');
    const titulo = document.getElementById('tituloQR');

    if (!contenedor || !titulo) {
        alert('No se encontró el espacio para mostrar el código QR.');
        return;
    }

    const url = generarUrlBotiquin(numero);

    // Para depurar: abre F12 > Consola y revisa que la URL lleve tu IP.
    console.log('URL generada por QR:', url);

    titulo.textContent = 'BOT-' + String(numero).padStart(3, '0');

    crearQR(contenedor, url);

    bootstrap.Modal.getOrCreateInstance(
        document.getElementById('modalQR')
    ).show();
}


/* ==========================================================
   AGREGAR / EDITAR MEDICAMENTO
   ========================================================== */

function abrirModalMedicamento(boton) {

    const modalEl = document.getElementById('modalMedicamento');

    if (!modalEl) return;

    const esEdicion = Boolean(boton);

    document.getElementById('medTitulo').textContent =
        esEdicion ? 'Editar medicamento' : 'Agregar medicamento';

    document.getElementById('medId').value =
        esEdicion ? boton.dataset.id : '0';

    document.getElementById('medNombre').value =
        esEdicion ? boton.dataset.nombre : '';

    document.getElementById('medPresentacion').value =
        esEdicion ? boton.dataset.presentacion : '';

    document.getElementById('medCantidad').value =
        esEdicion ? boton.dataset.cantidad : '';

    document.getElementById('medMinima').value =
        esEdicion ? boton.dataset.minima : '';

    document.getElementById('medCaducidad').value =
        esEdicion ? boton.dataset.caducidad : '';

    document.getElementById('medEstado').value =
        esEdicion ? boton.dataset.estado : 'Vigente';

    bootstrap.Modal.getOrCreateInstance(modalEl).show();
}


/* ==========================================================
   ELIMINAR MEDICAMENTO
   ========================================================== */

function abrirModalEliminar(boton) {

    const modalEl = document.getElementById('modalEliminar');

    if (!modalEl) return;

    document.getElementById('elimId').value = boton.dataset.id;
    document.getElementById('elimNombre').textContent =
        boton.dataset.nombre;

    bootstrap.Modal.getOrCreateInstance(modalEl).show();
}


/* ==========================================================
   CONFIRMAR REVISIÓN
   ========================================================== */

function confirmarRevision(caducados) {

    let texto =
        '¿Confirmas que ya revisaste este botiquín?\n' +
        'La próxima revisión quedará a 14 días.';

    if (caducados > 0) {
        texto =
            'Todavía hay ' + caducados +
            ' medicamento(s) caducado(s).\n\n' + texto;
    }

    return confirm(texto);
}


/* ==========================================================
   BUSCADOR Y FILTRO
   ========================================================== */

function filtrarBotiquines() {

    const buscador = document.getElementById('buscadorBotiquines');
    const filtroEstado = document.getElementById('filtroEstado');

    if (!buscador || !filtroEstado) return;

    const texto = buscador.value.toLowerCase();
    const filtro = filtroEstado.value.toLowerCase();

    document.querySelectorAll('#tablaBotiquines tbody tr')
        .forEach(function (fila) {

            const contenido = fila.textContent.toLowerCase();
            const estado = fila.dataset.estado || '';

            const coincideTexto = contenido.includes(texto);
            const coincideEstado = filtro === '' || estado === filtro;

            fila.style.display =
                coincideTexto && coincideEstado ? '' : 'none';
        });
}


/* ==========================================================
   INICIALIZAR EVENTOS Y QR DEL DETALLE
   ========================================================== */

document.addEventListener('DOMContentLoaded', function () {

    document.getElementById('buscadorBotiquines')
        ?.addEventListener('input', filtrarBotiquines);

    document.getElementById('filtroEstado')
        ?.addEventListener('change', filtrarBotiquines);

    // Si existe qrDetalle, estamos viendo el detalle del botiquín.
    const contenedorDetalle = document.getElementById('qrDetalle');

    if (contenedorDetalle) {

        if (typeof QRCode === 'undefined') {
            contenedorDetalle.textContent =
                'No se pudo cargar la librería QR.';
            return;
        }

        const parametros = new URLSearchParams(window.location.search);
        const numero = parseInt(parametros.get('botiquin'), 10);

        if (Number.isInteger(numero) && numero > 0) {
            crearQR(contenedorDetalle, generarUrlBotiquin(numero));
        }
    }
});
</script>

</body>
</html>