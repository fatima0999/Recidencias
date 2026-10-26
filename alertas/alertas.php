<?php
/* ==========================================================
   SIPV - CENTRO DE ALERTAS (SOLO ACTIVOS VENCIDOS)
   Sistema Inteligente de Prevención y Verificación
   Compatible con pg_connect (pg_query / pg_query_params)
   ========================================================== */

require_once __DIR__ . '/../clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();

date_default_timezone_set('America/Mexico_City');

$hoy = date('Y-m-d');

$vencidas = [];
$omitidos = [];   // tablas o columnas que no se encontraron
$error = '';

/* ==========================================================
   CATÁLOGO DE ACTIVOS A REVISAR
   Para agregar otro activo, solo añade un bloque aquí.
   - tabla:    nombre de la tabla
   - tipo:     nombre que se muestra
   - prefijo:  prefijo del folio si la tabla no tiene numero_folio
   - pk:       llave primaria
   - fechas:   columna de fecha => motivo que se muestra
   - etiqueta: (opcional) columna a mostrar como área si la tabla
               no tiene id_area
   ========================================================== */

$activos = [
    [
        'tabla'   => 'tb_extintores',
        'tipo'    => 'Extintor',
        'prefijo' => 'EXT',
        'pk'      => 'id_extintor',
        'fechas'  => [
            'fecha_proximo_mantenimiento' => 'Mantenimiento vencido',
            'fecha_proxima_recarga'       => 'Recarga vencida',
        ],
    ],
    [
        'tabla'   => 'tb_botiquines',
        'tipo'    => 'Botiquín',
        'prefijo' => 'BOT',
        'pk'      => 'id_botiquin',
        'fechas'  => ['fecha_proxima_revision' => 'Revisión vencida'],
    ],
    [
        'tabla'   => 'tb_luces_emergencia',
        'tipo'    => 'Luz de emergencia',
        'prefijo' => 'LUZ',
        'pk'      => 'id_luz',
        'fechas'  => ['fecha_proxima_revision' => 'Revisión vencida'],
    ],
    [
        'tabla'   => 'tb_hidrantes',
        'tipo'    => 'Hidrante',
        'prefijo' => 'HID',
        'pk'      => 'id_hidrante',
        'fechas'  => ['fecha_proxima_revision' => 'Revisión vencida'],
    ],
    [
        'tabla'   => 'tb_detectores_humo',
        'tipo'    => 'Detector de humo',
        'prefijo' => 'DET',
        'pk'      => 'id_detector',
        'fechas'  => ['fecha_proxima_revision' => 'Revisión vencida'],
    ],
    [
        'tabla'   => 'tb_alarmas_emergencia',
        'tipo'    => 'Alarma de emergencia',
        'prefijo' => 'ALA',
        'pk'      => 'id_alarma',
        'fechas'  => ['fecha_proxima_revision' => 'Revisión vencida'],
    ],
    [
        'tabla'   => 'tb_rutas_evacuacion',
        'tipo'    => 'Ruta de evacuación',
        'prefijo' => 'RUT',
        'pk'      => 'id_ruta',
        'fechas'  => ['fecha_proxima_revision' => 'Revisión vencida'],
    ],
    [
        'tabla'   => 'tb_salidas_emergencia',
        'tipo'    => 'Salida de emergencia',
        'prefijo' => 'SAL',
        'pk'      => 'id_salida',
        'fechas'  => ['fecha_proxima_revision' => 'Revisión vencida'],
    ],
    [
        'tabla'    => 'tb_puntos_reunion',
        'tipo'     => 'Punto de reunión',
        'prefijo'  => 'PUN',
        'pk'       => 'id_punto',
        'fechas'   => ['fecha_proxima_revision' => 'Revisión vencida'],
        'etiqueta' => 'nombre_punto',
    ],
    [
        'tabla'    => 'tb_documentos',
        'tipo'     => 'Documento',
        'prefijo'  => 'DOC',
        'pk'       => 'id_documento',
        'fechas'   => ['fecha_vencimiento' => 'Documento vencido'],
        'etiqueta' => 'nombre_documento',
    ],
];

/* ==========================================================
   FUNCIONES AUXILIARES
   ========================================================== */

/**
 * Ejecuta una consulta y lanza excepción si falla.
 */
function consultar($conexion, $sql, $params = [])
{
    $resultado = empty($params)
        ? @pg_query($conexion, $sql)
        : @pg_query_params($conexion, $sql, $params);

    if ($resultado === false) {
        throw new Exception(pg_last_error($conexion));
    }

    return $resultado;
}

function tablaExiste($conexion, $tabla)
{
    $resultado = consultar(
        $conexion,
        "SELECT EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = 'public'
              AND table_name = $1
        )",
        [$tabla]
    );

    return pg_fetch_result($resultado, 0, 0) === 't';
}

function columnaExiste($conexion, $tabla, $columna)
{
    $resultado = consultar(
        $conexion,
        "SELECT EXISTS (
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND table_name = $1
              AND column_name = $2
        )",
        [$tabla, $columna]
    );

    return pg_fetch_result($resultado, 0, 0) === 't';
}

function diasVencido($fecha, $hoy)
{
    if (empty($fecha)) {
        return 0;
    }

    return (int) (new DateTime($fecha))->diff(new DateTime($hoy))->days;
}

function claseGravedad($dias)
{
    if ($dias > 90) {
        return 'bg-danger';               // crítico
    }

    if ($dias > 30) {
        return 'bg-warning text-dark';    // alto
    }

    return 'bg-secondary';                // reciente
}

function escapar($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

/* ==========================================================
   CONSULTAR ACTIVOS VENCIDOS
   ========================================================== */

try {
    foreach ($activos as $activo) {

        $tabla = $activo['tabla'];

        if (!tablaExiste($conexion, $tabla)) {
            $omitidos[] = $tabla;
            continue;
        }

        $tieneArea  = columnaExiste($conexion, $tabla, 'id_area');
        $tieneFolio = columnaExiste($conexion, $tabla, 'numero_folio');
        $tienePk    = columnaExiste($conexion, $tabla, $activo['pk']);
        $tieneEtiqueta = !empty($activo['etiqueta'])
            && columnaExiste($conexion, $tabla, $activo['etiqueta']);

        foreach ($activo['fechas'] as $columna => $motivo) {

            if (!columnaExiste($conexion, $tabla, $columna)) {
                $omitidos[] = $tabla . '.' . $columna;
                continue;
            }

            $colId    = $tienePk ? "t.{$activo['pk']}" : 'NULL';
            $colFolio = $tieneFolio ? 't.numero_folio' : 'NULL';

            if ($tieneArea) {
                $colArea  = 'a.nombre_area';
                $colNivel = 'a.nivel';
                $join     = 'LEFT JOIN public.tb_areas a ON a.id_area = t.id_area';
            } else {
                $colArea  = $tieneEtiqueta ? "t.{$activo['etiqueta']}" : 'NULL';
                $colNivel = 'NULL';
                $join     = '';
            }

            $sql = "
                SELECT
                    $colId    AS id,
                    $colFolio AS folio,
                    $colArea  AS area,
                    $colNivel AS nivel,
                    t.$columna AS fecha
                FROM public.$tabla t
                $join
                WHERE t.$columna IS NOT NULL
                  AND t.$columna < $1
            ";

            $consulta = consultar($conexion, $sql, [$hoy]);

            while ($fila = pg_fetch_assoc($consulta)) {
                $folio = $fila['folio'];

                if (empty($folio)) {
                    $folio = $fila['id'] !== null
                        ? $activo['prefijo'] . '-' . $fila['id']
                        : 'Sin folio';
                }

                $vencidas[] = [
                    'folio'  => $folio,
                    'tipo'   => $activo['tipo'],
                    'area'   => $fila['area'] ?: 'Sin área asignada',
                    'nivel'  => $fila['nivel'] ?: 'Sin nivel',
                    'motivo' => $motivo,
                    'fecha'  => substr($fila['fecha'], 0, 10),
                ];
            }
        }
    }
} catch (Throwable $e) {
    error_log('Error en Centro de alertas SIPV: ' . $e->getMessage());
    $error = 'No fue posible consultar las alertas. Revisa el registro de errores de PHP.';
}

// Los más atrasados primero (fecha más antigua)
usort($vencidas, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));

$vencidasFiltradas = $vencidas;

$totalVencidas = count($vencidas);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activos vencidos | SIPV</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <style>
        :root {
            --azul: #102c46;
            --azul-claro: #174b73;
            --fondo: #f3f6fa;
            --texto: #263445;
        }

        body {
            background: var(--fondo);
            color: var(--texto);
            font-family: Arial, sans-serif;
        }

        .encabezado {
            background: linear-gradient(135deg, var(--azul), var(--azul-claro));
            color: white;
            padding: 28px 0;
            box-shadow: 0 4px 15px #102c4620;
        }

        .contenedor {
            max-width: 1450px;
            margin: auto;
            padding: 25px;
        }

        .tarjeta {
            background: white;
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 18px #152b4010;
        }

        .numero {
            font-size: 32px;
            font-weight: 800;
        }

        .icono-tarjeta {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 23px;
        }

        .filtro {
            background: white;
            padding: 20px;
            border-radius: 14px;
            box-shadow: 0 4px 18px #152b4010;
        }

        .tabla-contenedor {
            background: white;
            border-radius: 14px;
            box-shadow: 0 4px 18px #152b4010;
            overflow: hidden;
        }

        .table thead th {
            background: #edf2f7;
            color: #394c60;
            font-size: 12px;
            text-transform: uppercase;
            white-space: nowrap;
            padding: 15px;
        }

        .table tbody td {
            padding: 14px 15px;
            vertical-align: middle;
        }

        .boton-volver {
            color: white;
            border: 1px solid #ffffff70;
            border-radius: 8px;
            padding: 9px 15px;
            text-decoration: none;
        }

        .boton-volver:hover {
            background: #ffffff20;
            color: white;
        }

        @media (max-width: 768px) {
            .contenedor {
                padding: 15px;
            }

            .encabezado {
                padding: 22px 0;
            }

            .numero {
                font-size: 27px;
            }
        }
    </style>
</head>

<body>

<header class="encabezado">
    <div class="container-fluid px-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="small text-uppercase mb-2">
                    SIPV · Sistema Inteligente de Prevención y Verificación
                </div>

                <h1 class="h3 fw-bold mb-2">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i>
                    Activos vencidos
                </h1>

                <p class="mb-0 text-white-50">
                    Todos los activos con mantenimiento, recarga o revisión ya superada.
                </p>
            </div>

            <a href="../pruebafina.php" class="boton-volver">
                <i class="bi bi-arrow-left me-2"></i>
                Menú principal
            </a>
        </div>
    </div>
</header>

<main class="contenedor">

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= escapar($error) ?>
        </div>
    <?php endif; ?>

    <?php if (count($omitidos) > 0): ?>
        <div class="alert alert-warning small">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            No se encontraron estas tablas o columnas y no se revisaron:
            <strong><?= escapar(implode(', ', $omitidos)) ?></strong>.
            Revisa que el nombre coincida con tu base de datos.
        </div>
    <?php endif; ?>


    <div class="tabla-contenedor">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="h5 fw-bold mb-1">Listado de activos vencidos</h2>
                <p class="small text-secondary mb-0">
                    Total de activos vencidos al <?= date('d/m/Y') ?>: <?= $totalVencidas ?>
                </p>
            </div>

            <i class="bi bi-list-ul fs-4 text-secondary"></i>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Folio / ID</th>
                        <th>Tipo de activo</th>
                        <th>Área</th>
                        <th>Nivel</th>
                        <th>Motivo</th>
                        <th>Venció el</th>
                        <th>Días de atraso</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($vencidasFiltradas) > 0): ?>

                    <?php foreach ($vencidasFiltradas as $v): ?>
                        <?php $dias = diasVencido($v['fecha'], $hoy); ?>
                        <tr>
                            <td class="fw-semibold"><?= escapar($v['folio']) ?></td>
                            <td><?= escapar($v['tipo']) ?></td>
                            <td><?= escapar($v['area']) ?></td>
                            <td><?= escapar($v['nivel']) ?></td>
                            <td><?= escapar($v['motivo']) ?></td>
                            <td>
                                <?= $v['fecha']
                                    ? escapar(date('d/m/Y', strtotime($v['fecha'])))
                                    : 'Sin fecha' ?>
                            </td>
                            <td>
                                <span class="badge <?= claseGravedad($dias) ?>">
                                    <?= $dias ?> día<?= $dias === 1 ? '' : 's' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="bi bi-check-circle fs-1 text-success d-block mb-3"></i>
                            <div class="fw-semibold">No hay activos vencidos</div>
                            <div class="small text-secondary mt-1">
                                Todos tus activos y documentos están al corriente.
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-secondary small mt-4">
        <i class="bi bi-info-circle me-1"></i>
        Los vencidos se calculan al abrir o actualizar esta página. Este módulo no modifica los registros de los activos.
    </p>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>