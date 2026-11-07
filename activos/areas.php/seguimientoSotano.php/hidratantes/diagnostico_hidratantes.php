<?php
/* ==========================================================
   DIAGNÓSTICO DEL MÓDULO DE HIDRANTES  (borrar al terminar)
   UBICACIÓN: activos/areas.php/seguimientoSotano.php/diagnostico_hidrantes.php
   ========================================================== */

$base = __DIR__;

function h($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

function fila($ok, $etiqueta, $detalle = '') {
    $icono = $ok ? '✅' : '❌';
    $clase = $ok ? 'text-success' : 'text-danger';
    echo '<tr><td>' . $icono . '</td><td class="' . $clase . '">' . h($etiqueta) . '</td><td class="text-muted small">' . h($detalle) . '</td></tr>';
}

/* Muestra el nombre entre corchetes para que se noten espacios o letras raras */
function nombre($n) { return '[' . $n . ']'; }

$esperados = [
    'Hidratantes.php (listado, suelto)'          => 'Hidratantes.php',
    'hidrantes/alta_hidrante.php'                => 'hidrantes/alta_hidrante.php',
    'hidrantes/guardar_hidrante.php'             => 'hidrantes/guardar_hidrante.php',
    'hidrantes/actualizar_hidrante.php'          => 'hidrantes/actualizar_hidrante.php',
    'hidrantes/ver_hidrantes.php'                => 'hidrantes/ver_hidrantes.php',
    'hidrantes/eliminar_hidrante.php'            => 'hidrantes/eliminar_hidrante.php',
];

$rutaConexionListado = $base . '/../../../clases/conexion.php';
$rutaConexionCarpeta = $base . '/hidrantes/../../../../clases/conexion.php';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Diagnóstico de hidrantes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container" style="max-width: 900px;">

<h4>Diagnóstico del módulo de hidrantes</h4>
<p class="text-muted">Carpeta revisada: <code><?= h($base) ?></code></p>

<h6 class="mt-4">1. Archivos que deben existir</h6>
<table class="table table-sm">
<?php foreach ($esperados as $etiqueta => $rel): ?>
    <?php fila(is_file($base . '/' . $rel), $etiqueta); ?>
<?php endforeach; ?>
<?php fila(is_file($rutaConexionListado), 'clases/conexion.php (visto desde el listado)'); ?>
<?php fila(is_file($rutaConexionCarpeta), 'clases/conexion.php (visto desde hidrantes/)'); ?>
</table>

<h6 class="mt-4">2. Lo que realmente hay en el disco</h6>
<p class="small text-muted">Los nombres van entre corchetes para que se vea cualquier espacio de más.</p>
<div class="row">
<?php
$elementos = array_values(array_diff(scandir($base), ['.', '..']));
$relevantes = array_filter($elementos, function ($n) {
    return stripos($n, 'hidr') !== false || stripos($n, 'ELIMINAR') !== false;
});
?>
<div class="col-md-6">
    <strong>En seguimientoSotano.php/</strong>
    <ul class="small">
        <?php foreach ($relevantes as $n): ?>
            <li><?= h(nombre($n)) ?><?= is_dir($base . '/' . $n) ? ' 📁' : '' ?></li>
        <?php endforeach; ?>
        <?php if (!$relevantes): ?><li class="text-danger">No hay nada que parezca de hidrantes.</li><?php endif; ?>
    </ul>
</div>
<?php foreach ($relevantes as $n): ?>
    <?php if (is_dir($base . '/' . $n)): ?>
    <div class="col-md-6">
        <strong>Dentro de <?= h(nombre($n)) ?></strong>
        <ul class="small">
            <?php foreach (array_diff(scandir($base . '/' . $n), ['.', '..']) as $f): ?>
                <li><?= h(nombre($f)) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
<?php endforeach; ?>
</div>

<h6 class="mt-4">3. Base de datos</h6>
<table class="table table-sm">
<?php
if (!is_file($rutaConexionListado)) {
    fila(false, 'No se puede revisar la base', 'No se encontró clases/conexion.php');
} else {
    require_once $rutaConexionListado;
    $bd = (new Conexion())->conectar();

    if (!$bd) {
        fila(false, 'Conexión a PostgreSQL');
    } else {
        fila(true, 'Conexión a PostgreSQL');

        $r = @pg_query($bd, "SELECT COUNT(*) AS n FROM tb_hidrantes");
        fila($r !== false, 'Tabla tb_hidrantes', $r ? 'Registros: ' . pg_fetch_result($r, 0, 'n') : pg_last_error($bd));

        $r = @pg_query($bd, "SELECT COUNT(*) AS n FROM tb_hidrantes h INNER JOIN tb_areas a ON a.id_area = h.id_area WHERE a.nivel = 'Sótano'");
        fila($r !== false, 'Hidrantes en áreas del Sótano', $r ? 'Registros: ' . pg_fetch_result($r, 0, 'n') : pg_last_error($bd));

        $r = @pg_query($bd, "SELECT pg_get_constraintdef(oid) AS def FROM pg_constraint
                              WHERE conrelid = 'tb_revisiones'::regclass AND contype = 'c'
                                AND pg_get_constraintdef(oid) ILIKE '%tipo_activo%'");
        if ($r === false) {
            fila(false, 'CHECK de tipo_activo en tb_revisiones', pg_last_error($bd));
        } elseif (pg_num_rows($r) === 0) {
            fila(true, 'tb_revisiones no limita tipo_activo', 'No hace falta cambiar nada');
        } else {
            $def = pg_fetch_result($r, 0, 'def');
            $acepta = stripos($def, "'hidrantes'") !== false;
            fila($acepta, "tb_revisiones acepta tipo 'hidrantes'",
                 $acepta ? 'Listo para guardar revisiones' : 'FALTA: corre el SQL permitir_hidrantes_en_revisiones.sql');
        }
    }
}
?>
</table>

<p class="text-muted small mt-4">Cuando todo salga ✅, borra este archivo.</p>
</div>
</body>
</html>