<?php

/* ==========================================================
   CONEXIÓN A POSTGRESQL
   ========================================================== */

require_once __DIR__ . '/../../../clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();

if (!$conexion) {
    die("Error: No se pudo conectar con PostgreSQL.");
}


/* ==========================================================
   FUNCIÓN PARA PROTEGER LOS DATOS
   ========================================================== */

function e($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}


/* ==========================================================
   IP DE ESTA PC EN LA RED (para que el celular abra el QR)
   Si la detección falla, escribe tu IP a mano en $IP_MANUAL
   (la ves con "ipconfig", adaptador Wi-Fi, "Dirección IPv4")
   ========================================================== */

$IP_MANUAL = '';   // Ej. '192.168.1.68'

function detectarIpLocal() {

    /* Truco UDP: no envía nada, solo pregunta con qué IP saldría a la red */
    $s = @stream_socket_client('udp://8.8.8.8:53', $errno, $errstr, 1);

    if ($s) {
        $nombre = stream_socket_get_name($s, false);
        fclose($s);

        if ($nombre) {
            $ip = explode(':', $nombre)[0];
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ip;
            }
        }
    }

    return gethostbyname(gethostname());
}

$ipServidor = $IP_MANUAL !== '' ? $IP_MANUAL : detectarIpLocal();


/* ==========================================================
   AVISO DESPUÉS DE GUARDAR / ELIMINAR (parámetro ?msg=)
   ========================================================== */

$mensajes = [
    'guardado'  => ['ok',  'Luz de emergencia guardada correctamente.'],
    'eliminado' => ['ok',  'Luz de emergencia eliminada correctamente.'],
    'error'     => ['bad', 'No se pudo completar la acción.'],
];

$aviso = $mensajes[$_GET['msg'] ?? ''] ?? null;


/* ==========================================================
   CONSULTA DE LUCES DE EMERGENCIA DEL SÓTANO
   ========================================================== */

$sql = "
    SELECT
        l.id_luz,
        COALESCE(
            NULLIF(l.numero_folio, ''),
            'LUZ-' || LPAD(l.id_luz::text, 3, '0')
        ) AS folio,
        l.tipo_iluminacion,
        l.marca,
        l.modelo,
        l.duracion_bateria,
        l.estado_fisico,
        l.fecha_instalacion,
        l.fecha_ultima_revision,
        l.fecha_proxima_revision,
        l.observaciones,
        l.ubicacion_detalle,
        a.nombre_area
    FROM public.tb_luces_emergencia l
    INNER JOIN public.tb_areas a
        ON l.id_area = a.id_area
    WHERE a.nivel = 'Sótano'
      AND a.activo = TRUE
    ORDER BY
        l.fecha_proxima_revision ASC NULLS LAST,
        l.id_luz ASC
";

$res = pg_query($conexion, $sql);

if (!$res) {
    die(
        "Error al consultar las luces de emergencia: " .
        pg_last_error($conexion)
    );
}

/* ==========================================================
   PREPARAR LOS DATOS
   ========================================================== */

$meses = [
    'ene', 'feb', 'mar', 'abr', 'may', 'jun',
    'jul', 'ago', 'sep', 'oct', 'nov', 'dic'
];

$hoy = date('Y-m-d');
$limite = date('Y-m-d', strtotime('+30 days'));

$lista = [];
$areas = [];

$conteo = [
    'Al corriente' => 0,
    'Por vencer' => 0,
    'Vencido' => 0,
    'Sin fecha' => 0
];

while ($f = pg_fetch_assoc($res)) {

    $fecha = $f['fecha_proxima_revision'];

    if (empty($fecha)) {

        $estado = 'Sin fecha';
        $f['fecha_txt'] = '—';

    } else {

        $t = strtotime($fecha);

        $f['fecha_txt'] =
            date('j', $t) . ' ' .
            $meses[(int)date('n', $t) - 1] . ' ' .
            date('Y', $t);

        if ($fecha < $hoy) {
            $estado = 'Vencido';
        } elseif ($fecha <= $limite) {
            $estado = 'Por vencer';
        } else {
            $estado = 'Al corriente';
        }
    }

    $f['estado'] = $estado;

    $conteo[$estado]++;

    $areas[$f['nombre_area']] = true;

    $lista[] = $f;
}

ksort($areas);

$total = count($lista);

$cumplimiento = $total > 0
    ? round(($conteo['Al corriente'] / $total) * 100)
    : 0;

$clases = [
    'Al corriente' => 'ok',
    'Por vencer' => 'warn',
    'Vencido' => 'bad',
    'Sin fecha' => 'none'
];

$flagsJson =
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_UNESCAPED_UNICODE;

$jsonLista = json_encode($lista, $flagsJson);


/* ==========================================================
   HISTORIAL DE REVISIONES (tb_revisiones)
   Se agrupa por id de luz: $historial[id_luz] = [revisiones...]
   ========================================================== */

$historial = [];

$rh = pg_query($conexion, "
    SELECT id_activo, fecha_revision, tipo_revision, resultado, observaciones
    FROM public.tb_revisiones
    WHERE tipo_activo = 'luz_emergencia'
    ORDER BY fecha_revision DESC, id_revision DESC
");

if ($rh) {
    while ($h = pg_fetch_assoc($rh)) {
        $historial[$h['id_activo']][] = $h;
    }
}

$jsonHistorial = json_encode($historial, $flagsJson);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Luces de emergencia - Sótano</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
    >

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>

        :root {
            --bg: #05070b;
            --panel: #0d1117;
            --borde: #1c2430;
            --texto: #f2f4f8;
            --suave: #8b95a5;
            --verde: #2fd4a0;
            --amarillo: #f0b429;
            --rojo: #ef5b6b;
            --acento: #d6143c;
        }

        body {
            background: var(--bg);
            color: var(--texto);
            font-family: "Segoe UI", system-ui, sans-serif;
        }

        a {
            text-decoration: none;
        }

        .barra {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 28px;
            border-bottom: 1px solid var(--borde);
        }

        .barra h1 {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0;
        }

        .volver {
            color: var(--suave);
            display: inline-block;
            padding: 14px 28px 0;
        }

        .volver:hover {
            color: var(--texto);
        }

        .cabecera {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            padding: 8px 28px 20px;
        }

        .cabecera h2 {
            font-size: 1.6rem;
            font-weight: 700;
            margin: 0;
        }

        .filtros {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .campo {
            background: var(--bg);
            color: var(--texto);
            border: 1px solid var(--borde);
            border-radius: 10px;
            padding: 10px 14px;
            min-width: 160px;
        }

        .campo:focus {
            outline: 2px solid var(--acento);
            outline-offset: 1px;
        }

        .btn-agregar {
            background: var(--acento);
            color: #fff;
            border-radius: 10px;
            padding: 10px 18px;
            font-weight: 600;
            border: 0;
        }

        .btn-agregar:hover {
            background: #b60f33;
            color: #fff;
        }

        /* AVISO (guardado / eliminado / error) */

        .aviso {
            margin: 0 28px 18px;
            padding: 12px 18px;
            border-radius: 12px;
            font-weight: 600;
        }

        .aviso-ok {
            background: #0f2c26;
            color: var(--verde);
        }

        .aviso-bad {
            background: #34161b;
            color: var(--rojo);
        }

        .tarjetas {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            padding: 0 28px 24px;
        }

        .tarjeta {
            background: var(--panel);
            border: 1px solid var(--borde);
            border-top-width: 3px;
            border-radius: 18px;
            padding: 22px 26px;
        }

        .tarjeta small {
            color: var(--suave);
            font-size: 1rem;
        }

        .tarjeta .num {
            font-size: 2.1rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .t-ok {
            border-top-color: var(--verde);
        }

        .t-warn {
            border-top-color: var(--amarillo);
        }

        .t-bad {
            border-top-color: var(--rojo);
        }

        .c-ok {
            color: var(--verde);
        }

        .c-warn {
            color: var(--amarillo);
        }

        .c-bad {
            color: var(--rojo);
        }

        .lista {
            background: var(--panel);
            border: 1px solid var(--borde);
            border-radius: 22px;
            margin: 0 28px 40px;
            padding: 14px 36px;
        }

        .fila {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 22px 0;
            border-bottom: 1px solid var(--borde);
            cursor: pointer;
        }

        .fila:last-child {
            border-bottom: 0;
        }

        .fila:hover .titulo {
            color: var(--acento);
        }

        .fila:focus-visible {
            outline: 2px solid var(--acento);
            outline-offset: 4px;
        }

        .icono {
            width: 50px;
            height: 50px;
            flex: none;
            display: grid;
            place-items: center;
            background: #161b24;
            border-radius: 12px;
            color: var(--amarillo);
            font-size: 1.2rem;
        }

        .info {
            flex: 1;
            min-width: 0;
        }

        .titulo {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .sub {
            color: var(--suave);
        }

        .lado {
            text-align: right;
            color: var(--suave);
        }

        .badge-e {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 10px;
            font-weight: 600;
            margin-top: 6px;
        }

        /* Estado + botón Eliminar en la misma línea */

        .lado-acciones {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            margin-top: 6px;
        }

        .lado-acciones .badge-e {
            margin-top: 0;
        }

        .btn-eliminar {
            background: transparent;
            color: var(--rojo);
            border: 1px solid #5a2630;
            border-radius: 10px;
            padding: 6px 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-eliminar:hover {
            background: #34161b;
            border-color: var(--rojo);
        }

        .btn-eliminar:focus-visible {
            outline: 2px solid var(--rojo);
            outline-offset: 2px;
        }

        .b-ok {
            background: #0f2c26;
            color: var(--verde);
        }

        .b-warn {
            background: #2b2410;
            color: var(--amarillo);
        }

        .b-bad {
            background: #34161b;
            color: var(--rojo);
        }

        .b-none {
            background: #1c2430;
            color: var(--suave);
        }

        .vacio {
            text-align: center;
            color: var(--suave);
            padding: 40px 0;
        }

        /* MODAL */

        .modal-content {
            background: var(--panel);
            color: var(--texto);
            border: 1px solid var(--borde);
            border-radius: 20px;
        }

        .modal-header,
        .modal-footer {
            border: 0;
        }

        .cerrar {
            background: transparent;
            border: 1px solid var(--borde);
            color: var(--texto);
            border-radius: 10px;
            width: 44px;
            height: 44px;
        }

        .pestanas {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 22px;
        }

        .pestana {
            background: transparent;
            color: var(--suave);
            border: 1px solid var(--borde);
            border-radius: 10px;
            padding: 8px 16px;
        }

        .pestana.activa {
            color: var(--acento);
            border-color: var(--acento);
            background: #1a0d12;
        }

        .rejilla {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 30px;
        }

        .rejilla small {
            display: block;
            color: var(--suave);
        }

        .rejilla strong {
            font-size: 1.1rem;
        }

        .acciones {
            display: flex;
            gap: 10px;
            margin-top: 26px;
            flex-wrap: wrap;
        }

        .btn-sec {
            background: transparent;
            color: var(--texto);
            border: 1px solid var(--borde);
            border-radius: 10px;
            padding: 9px 16px;
            font-weight: 600;
        }

        .btn-sec:hover {
            border-color: var(--acento);
            color: var(--texto);
        }

        #contenedorQR {
            display: flex;
            justify-content: center;
            padding: 16px;
            background: #fff;
            border-radius: 14px;
            width: fit-content;
            margin: 12px auto;
        }

        .panel-tab {
            display: none;
        }

        .panel-tab.activo {
            display: block;
        }

        /* HISTORIAL */

        .rev {
            padding: 14px 0;
            border-bottom: 1px solid var(--borde);
        }

        .rev:last-child {
            border-bottom: 0;
        }

        .rev .fecha {
            font-weight: 700;
        }

        .rev .obs {
            color: var(--suave);
            font-size: .9rem;
            margin-top: 2px;
        }

        @media (max-width: 992px) {
            .tarjetas {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {

            .tarjetas {
                grid-template-columns: 1fr;
                padding: 0 14px 20px;
            }

            .barra,
            .cabecera {
                padding-left: 14px;
                padding-right: 14px;
            }

            .aviso {
                margin-left: 14px;
                margin-right: 14px;
            }

            .lista {
                margin: 0 14px 30px;
                padding: 6px 16px;
            }

            .fila {
                flex-wrap: wrap;
            }

            .lado {
                text-align: left;
                width: 100%;
            }

            .lado-acciones {
                justify-content: flex-start;
            }

            .rejilla {
                grid-template-columns: 1fr;
            }
        }

    </style>

</head>

<body>

    <div class="barra">
        <h1>Activos</h1>
    </div>

    <!-- REGRESAR AL SÓTANO -->
    <a class="volver" href="../sotano.php">
        <i class="fas fa-arrow-left me-1"></i>
        Sótano
    </a>

    <!-- CABECERA Y FILTROS -->

    <div class="cabecera">

        <h2>
            <i class="fas fa-lightbulb me-2" style="color:var(--amarillo)"></i>
            Luces de emergencia
        </h2>

        <div class="filtros">

            <input
                type="text"
                id="buscador"
                class="campo"
                placeholder="Buscar folio, marca, modelo..."
            >

            <select
                id="filtroEstado"
                class="campo"
                aria-label="Filtrar por estado"
            >

                <option value="">Estado</option>

                <?php foreach (array_keys($conteo) as $est): ?>

                    <option value="<?= e($est) ?>">
                        <?= e($est) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <select
                id="filtroArea"
                class="campo"
                aria-label="Filtrar por área"
            >

                <option value="">Área</option>

                <?php foreach (array_keys($areas) as $ar): ?>

                    <option value="<?= e($ar) ?>">
                        <?= e($ar) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <!-- RUTA PARA AGREGAR -->
            <a
                href="./luces/lucesemergencia.php"
                class="btn-agregar"
            >
                + Agregar
            </a>

        </div>

    </div>

    <!-- AVISO (guardado / eliminado / error) -->

    <?php if ($aviso): ?>

        <div class="aviso aviso-<?= e($aviso[0]) ?>" role="alert">
            <?= e($aviso[1]) ?>
        </div>

    <?php endif; ?>

    <!-- TARJETAS DE RESUMEN -->

    <div class="tarjetas">

        <div class="tarjeta t-ok">
            <small>Cumplimiento</small>
            <div class="num c-ok">
                <?= $cumplimiento ?>%
            </div>
        </div>

        <div class="tarjeta t-ok">
            <small>Al corriente</small>
            <div class="num">
                <?= $conteo['Al corriente'] ?>
            </div>
        </div>

        <div class="tarjeta t-warn">
            <small>Por vencer</small>
            <div class="num c-warn">
                <?= $conteo['Por vencer'] ?>
            </div>
        </div>

        <div class="tarjeta t-bad">
            <small>Vencidos</small>
            <div class="num c-bad">
                <?= $conteo['Vencido'] ?>
            </div>
        </div>

    </div>

    <!-- LISTADO DE LUCES -->

    <div class="lista" id="lista">

        <?php foreach ($lista as $i => $l): ?>

            <div
                class="fila"
                tabindex="0"
                role="button"
                data-i="<?= $i ?>"
                data-estado="<?= e($l['estado']) ?>"
                data-area="<?= e($l['nombre_area']) ?>"
                data-texto="<?= e(strtolower(
                    $l['folio'] . ' ' .
                    $l['marca'] . ' ' .
                    $l['modelo'] . ' ' .
                    $l['tipo_iluminacion'] . ' ' .
                    $l['nombre_area'] . ' ' .
                    $l['ubicacion_detalle']
                )) ?>"
            >

                <div class="icono">
                    <i class="fas fa-lightbulb"></i>
                </div>

                <div class="info">

                    <div class="titulo">
                        Luz de emergencia
                        <?= e($l['tipo_iluminacion']) ?>
                    </div>

                    <div class="sub">

                        <?= e($l['folio']) ?>
                        ·
                        <?= e($l['nombre_area']) ?>

                        <?php if (!empty($l['ubicacion_detalle'])): ?>

                            · <?= e($l['ubicacion_detalle']) ?>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="lado">

                    Próx. revisión:
                    <?= e($l['fecha_txt']) ?>

                    <div class="lado-acciones">

                        <span class="badge-e b-<?= $clases[$l['estado']] ?>">
                            <?= e($l['estado']) ?>
                        </span>

                        <!-- BOTÓN ELIMINAR -->
                        <button
                            type="button"
                            class="btn-eliminar"
                            title="Eliminar luz de emergencia"
                            data-id="<?= (int) $l['id_luz'] ?>"
                            data-folio="<?= e($l['folio']) ?>"
                        >
                            <i class="fas fa-trash me-1"></i>
                            Eliminar
                        </button>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

        <div
            class="vacio"
            id="vacio"
            <?= empty($lista) ? '' : 'style="display:none"' ?>
        >

            <?= empty($lista)
                ? 'Aún no hay luces de emergencia en el Sótano. Usa “+ Agregar” para registrar la primera.'
                : 'Ninguna luz coincide con los filtros.'
            ?>

        </div>

    </div>

    <!-- MODAL DE DETALLE -->

    <div
        class="modal fade"
        id="modalLuz"
        tabindex="-1"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered modal-lg">

            <div class="modal-content">

                <div class="modal-header align-items-start p-4 pb-0">

                    <div>

                        <h4 class="mb-1" id="mTitulo"></h4>

                        <div class="sub">

                            <span id="mSub"></span>

                            <span
                                class="badge-e"
                                id="mBadge"
                                style="margin:0 0 0 6px"
                            ></span>

                        </div>

                    </div>

                    <button
                        type="button"
                        class="cerrar"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar"
                    >
                        <i class="fas fa-times"></i>
                    </button>

                </div>

                <div class="modal-body p-4">

                    <div class="pestanas">

                        <button class="pestana activa" data-tab="info">
                            Info
                        </button>

                        <button class="pestana" data-tab="qr">
                            <i class="fas fa-qrcode me-1"></i>
                            QR
                        </button>

                        <button class="pestana" data-tab="hist">
                            <i class="fas fa-history me-1"></i>
                            Historial
                        </button>

                    </div>

                    <!-- INFORMACIÓN -->

                    <div class="panel-tab activo" id="tab-info">

                        <div class="rejilla" id="mInfo"></div>

                        <div class="acciones">

                            <button
                                class="btn-sec"
                                data-tab="qr"
                            >
                                QR
                            </button>

                            <a
                                class="btn-agregar"
                                id="mActualizar"
                                href="#"
                            >
                                Actualizar
                            </a>

                        </div>

                    </div>

                    <!-- CÓDIGO QR -->

                    <div class="panel-tab" id="tab-qr">

                        <div class="text-center">

                            <div
                                class="mb-2"
                                id="mFolioQR"
                                style="font-weight:700"
                            ></div>

                            <div id="contenedorQR"></div>

                            <small class="sub d-block">
                                Escanéalo con el celular (conectado al mismo wifi)
                            </small>

                            <a
                                id="mUrlQR"
                                href="#"
                                target="_blank"
                                class="d-block small mt-2"
                                style="word-break:break-all; color:var(--suave)"
                            ></a>

                        </div>

                    </div>

                    <!-- HISTORIAL -->

                    <div class="panel-tab" id="tab-hist">
                        <div id="mHist"></div>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- MODAL DE CONFIRMACIÓN PARA ELIMINAR -->

    <div
        class="modal fade"
        id="modalEliminar"
        tabindex="-1"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <form action="./luces/eliminar_luz.php" method="POST">

                    <div class="modal-header align-items-start p-4 pb-0">

                        <h5 class="mb-0">
                            <i
                                class="fas fa-exclamation-triangle me-2"
                                style="color:var(--rojo)"
                            ></i>
                            Eliminar luz de emergencia
                        </h5>

                        <button
                            type="button"
                            class="cerrar"
                            data-bs-dismiss="modal"
                            aria-label="Cerrar"
                        >
                            <i class="fas fa-times"></i>
                        </button>

                    </div>

                    <div class="modal-body p-4">

                        <input
                            type="hidden"
                            name="id_luz"
                            id="elimId"
                            value="0"
                        >

                        <p class="mb-1">
                            ¿Seguro que quieres eliminar
                            <strong id="elimFolio"></strong>?
                        </p>

                        <small class="sub">
                            Se eliminará también su historial de revisiones.
                            Esta acción no se puede deshacer.
                        </small>

                    </div>

                    <div class="modal-footer p-4 pt-0">

                        <button
                            type="button"
                            class="btn-sec"
                            data-bs-dismiss="modal"
                        >
                            Cancelar
                        </button>

                        <button type="submit" class="btn-agregar">
                            <i class="fas fa-trash me-1"></i>
                            Sí, eliminar
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>

        const luces = <?= $jsonLista ?>;
        const historial = <?= $jsonHistorial ?>;

        const clases = {
            'Al corriente': 'ok',
            'Por vencer': 'warn',
            'Vencido': 'bad',
            'Sin fecha': 'none'
        };

        /* IP de esta PC en la red (detectada por PHP).
           Se usa solo si abres el sistema por localhost. */
        const IP_SERVIDOR = <?= json_encode($ipServidor) ?>;

        /* ==================================================
           FILTROS
           ================================================== */

        const filas = document.querySelectorAll('#lista .fila');

        const buscador = document.getElementById('buscador');
        const fEstado = document.getElementById('filtroEstado');
        const fArea = document.getElementById('filtroArea');

        function filtrar() {

            const q = buscador.value.toLowerCase().trim();

            let visibles = 0;

            filas.forEach(f => {

                const coincideTexto =
                    f.dataset.texto.includes(q);

                const coincideEstado =
                    !fEstado.value ||
                    f.dataset.estado === fEstado.value;

                const coincideArea =
                    !fArea.value ||
                    f.dataset.area === fArea.value;

                const mostrar =
                    coincideTexto &&
                    coincideEstado &&
                    coincideArea;

                f.style.display = mostrar ? '' : 'none';

                if (mostrar) {
                    visibles++;
                }

            });

            const vacio = document.getElementById('vacio');

            vacio.style.display =
                visibles === 0 ? '' : 'none';

            if (filas.length > 0) {
                vacio.textContent =
                    'Ninguna luz coincide con los filtros.';
            }

        }

        [buscador, fEstado, fArea].forEach(el => {
            el.addEventListener('input', filtrar);
            el.addEventListener('change', filtrar);
        });

        /* ==================================================
           MODAL
           ================================================== */

        const modal = new bootstrap.Modal(
            document.getElementById('modalLuz')
        );

        let actual = null;

        function dato(label, valor) {

            const d = document.createElement('div');

            const s = document.createElement('small');
            s.textContent = label;

            const b = document.createElement('strong');
            b.textContent = valor || '—';

            d.append(s, b);

            return d;
        }

        /* ==================================================
           CAMBIAR PESTAÑAS
           ================================================== */

        function irATab(nombre) {

            document.querySelectorAll('.pestana').forEach(p => {

                p.classList.toggle(
                    'activa',
                    p.dataset.tab === nombre
                );

            });

            document.querySelectorAll('.panel-tab').forEach(p => {

                p.classList.toggle(
                    'activo',
                    p.id === 'tab-' + nombre
                );

            });

            if (nombre === 'qr') {
                generarQR();
            }

        }

        /* ==================================================
           GENERAR CÓDIGO QR
           (abre la ficha pública con su historial)
           ================================================== */

        function generarQR() {

            const cont = document.getElementById('contenedorQR');

            cont.innerHTML = '';

            document.getElementById('mFolioQR').textContent =
                'Luz de emergencia: ' + actual.folio;

            /* Si abres el sistema por localhost usa la IP de la red;
               si ya lo abriste por IP, usa esa misma */
            const host = (
                location.hostname === 'localhost' ||
                location.hostname === '127.0.0.1'
            )
                ? IP_SERVIDOR
                : location.host;

            const urlFicha =
                location.protocol + '//' + host +
                '/recidencias/activos/areas.php/seguimientoSotano.php/luces/ficha_luz.php?id=' +
                encodeURIComponent(actual.id_luz);

            if (typeof QRCode === 'undefined') {
                cont.textContent =
                    'No cargó la librería del QR (revisa tu conexión a internet).';
                return;
            }

            new QRCode(cont, {
                text: urlFicha,
                width: 260,
                height: 260,
                correctLevel: QRCode.CorrectLevel.L
            });

            /* Link escrito debajo del QR para probarlo a mano */
            const enlace = document.getElementById('mUrlQR');

            enlace.href = urlFicha;
            enlace.textContent = urlFicha;

        }

        /* ==================================================
           HISTORIAL DE REVISIONES
           ================================================== */

        function pintarHistorial(idLuz) {

            const cont = document.getElementById('mHist');

            cont.innerHTML = '';

            const revs = historial[idLuz] || [];

            if (revs.length === 0) {

                const p = document.createElement('p');
                p.className = 'sub mb-0';
                p.textContent = 'Sin revisiones registradas.';
                cont.appendChild(p);

                return;
            }

            revs.forEach(r => {

                const d = document.createElement('div');
                d.className = 'rev';

                const f = document.createElement('span');
                f.className = 'fecha';
                f.textContent = r.fecha_revision;

                d.append(
                    f,
                    ' · ' + (r.tipo_revision || '—') +
                    ' · ' + (r.resultado || '—')
                );

                if (r.observaciones) {

                    const o = document.createElement('div');
                    o.className = 'obs';
                    o.textContent = r.observaciones;

                    d.appendChild(o);
                }

                cont.appendChild(d);

            });

        }

        /* ==================================================
           ABRIR INFORMACIÓN DE LA LUZ
           ================================================== */

        function abrir(i) {

            actual = luces[i];

            document.getElementById('mTitulo').textContent =
                actual.folio + ' · Luz de emergencia ' +
                (actual.tipo_iluminacion || '');

            document.getElementById('mSub').textContent =
                actual.nombre_area +
                (actual.ubicacion_detalle
                    ? ' · ' + actual.ubicacion_detalle
                    : '');

            const badge = document.getElementById('mBadge');

            badge.textContent = actual.estado;

            badge.className =
                'badge-e b-' + clases[actual.estado];

            /* INFORMACIÓN GENERAL */

            const info = document.getElementById('mInfo');

            info.innerHTML = '';

            info.append(

                dato('Tipo', actual.tipo_iluminacion),

                dato('Área', actual.nombre_area),

                dato('Ubicación', actual.ubicacion_detalle),

                dato('Estado', actual.estado),

                dato('Fecha de instalación', actual.fecha_instalacion),

                dato('Última revisión', actual.fecha_ultima_revision),

                dato('Próxima revisión', actual.fecha_txt),

                dato(
                    'Marca / Modelo',
                    [actual.marca, actual.modelo]
                        .filter(Boolean)
                        .join(' / ')
                ),

                dato('Duración de batería', actual.duracion_bateria),

                dato('Estado físico', actual.estado_fisico),

                dato('Observaciones', actual.observaciones)

            );

            /* BOTÓN ACTUALIZAR */

            document.getElementById('mActualizar').href =
                './luces/actualizar_luz.php?id=' +
                encodeURIComponent(actual.id_luz);

            /* HISTORIAL */

            pintarHistorial(actual.id_luz);

            irATab('info');

            modal.show();

        }

        /* ==================================================
           EVENTOS DE LAS FILAS
           ================================================== */

        filas.forEach(f => {

            f.addEventListener('click', () => {
                abrir(Number(f.dataset.i));
            });

            f.addEventListener('keydown', ev => {

                if (ev.key === 'Enter' || ev.key === ' ') {

                    ev.preventDefault();

                    abrir(Number(f.dataset.i));

                }

            });

        });

        /* ==================================================
           EVENTOS DE LAS PESTAÑAS
           ================================================== */

        document.querySelectorAll('[data-tab]').forEach(b => {

            b.addEventListener('click', () => {

                irATab(b.dataset.tab);

            });

        });

        /* ==================================================
           ELIMINAR LUZ DE EMERGENCIA
           (el clic en el botón NO debe abrir el detalle)
           ================================================== */

        const modalEliminar = new bootstrap.Modal(
            document.getElementById('modalEliminar')
        );

        document.querySelectorAll('.btn-eliminar').forEach(b => {

            b.addEventListener('click', ev => {

                ev.stopPropagation();

                document.getElementById('elimId').value =
                    b.dataset.id;

                document.getElementById('elimFolio').textContent =
                    b.dataset.folio;

                modalEliminar.show();

            });

            /* Evita que Enter/Espacio abran también el detalle de la fila */
            b.addEventListener('keydown', ev => {
                ev.stopPropagation();
            });

        });

    </script>

</body>
</html>