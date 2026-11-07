<?php
/* ==========================================================
   DIAGNÓSTICO DEL MÓDULO DE HIDRANTES - PLANTA BAJA  (borrar al terminar)
   UBICACIÓN: activos/areas.php/seguimientoplantabaja/hidratantes/diagnostico_hidratantes.php
   ========================================================== */

$base    = __DIR__;                 // carpeta hidratantes/
$padre   = dirname(__DIR__);        // carpeta seguimientoplantabaja/

function h($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

function fila($ok, $etiqueta, $detalle = '') {
    $icono = $ok ? '✅' : '❌';
    $clase = $ok ? 'text-success' : 'text-danger';
    echo '<tr><td>' . $icono . '</td><td class="' . $clase . '">' . h($etiqueta) . '</td><td class="text-muted small">' . h($detalle) . '</td></tr>';
}

/* Muestra el nombre entre corchetes para que se noten espacios o letras raras */
function nombre($n) { return '[' . $n . ']'; }

/* Busca un archivo por prefijo sin importar mayúsculas (Eliminar_ / eliminar_) */
function buscarPorPrefijo($carpeta, $prefijo) {
    foreach (scandir($carpeta) ?: [] as $n) {
        if ($n !== '.' && $n !== '..' && stripos($n, $prefijo) === 0) return $n;
    }
    return null;
}

$rutaConexion = $base . '/../../../../clases/conexion.php';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Diagnóstico de hidrantes - Planta Baja</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container" style="max-width: 900px;">

<h4>Diagnóstico del módulo de hidrantes · Planta Baja</h4>
<p class="text-muted">Carpeta revisada: <code><?= h($base) ?></code></p>

<h6 class="mt-4">1. Archivos que deben existir</h6>
<table class="table table-sm">
<?php
fila(is_file($padre . '/hidratantes.php'), 'hidratantes.php (listado, en seguimientoplantabaja/)');

$esperados = [
    'alta_'       => 'alta_hidratante.php',
    'aguardar_'   => 'aguardar_hudratante.php (guardar)',
    'actualizar_' => 'actualizar_hidratante.php',
    'ver_'        => 'ver_hidratante.php',
    'eliminar_'   => 'eliminar_hidratante.php',
];
foreach ($esperados as $prefijo => $etiqueta) {
    $enc = buscarPorPrefijo($base, $prefijo);
    $okPhp = $enc !== null && strtolower(pathinfo($enc, PATHINFO_EXTENSION)) === 'php';
    fila($okPhp, 'hidratantes/' . $etiqueta,
        $enc === null ? 'No se encontró ningún archivo que empiece con ' . $prefijo
                      : ($okPhp ? 'Encontrado: ' . $enc : 'Encontrado "' . $enc . '" pero NO termina en .php (no se ejecutaría)'));
}

fila(is_file($rutaConexion), 'clases/conexion.php (visto desde hidratantes/)');
?>
</table>

<h6 class="mt-4">2. Lo que realmente hay en el disco</h6>
<p class="small text-muted">Los nombres van entre corchetes para que se vea cualquier espacio de más.</p>
<div class="row">
<?php
$enPadre = array_filter(array_values(array_diff(scandir($padre), ['.', '..'])), function ($n) {
    return stripos($n, 'hidr') !== false || stripos($n, 'hidat') !== false;
});
?>
<div class="col-md-6">
    <strong>En seguimientoplantabaja/</strong>
    <ul class="small">
        <?php foreach ($enPadre as $n): ?>
            <li><?= h(nombre($n)) ?><?= is_dir($padre . '/' . $n) ? ' 📁' : '' ?></li>
        <?php endforeach; ?>
        <?php if (!$enPadre): ?><li class="text-danger">No hay nada que parezca de hidrantes.</li><?php endif; ?>
    </ul>
</div>
<div class="col-md-6">
    <strong>Dentro de hidratantes/</strong>
    <ul class="small">
        <?php foreach (array_diff(scandir($base), ['.', '..']) as $f): ?>
            <li><?= h(nombre($f)) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
</div>

<h6 class="mt-4">3. Base de datos</h6>
<table class="table table-sm">
<?php
if (!is_file($rutaConexion)) {
    fila(false, 'No se puede revisar la base', 'No se encontró clases/conexion.php');
} else {
    require_once $rutaConexion;
    $bd = (new Conexion())->conectar();

    if (!$bd) {
        fila(false, 'Conexión a PostgreSQL');
    } else {
        fila(true, 'Conexión a PostgreSQL');

        $r = @pg_query($bd, "SELECT COUNT(*) AS n FROM tb_hidrantes");
        fila($r !== false, 'Tabla tb_hidrantes', $r ? 'Registros: ' . pg_fetch_result($r, 0, 'n') : pg_last_error($bd));

        $r = @pg_query($bd, "SELECT COUNT(*) AS n FROM tb_hidrantes h INNER JOIN tb_areas a ON a.id_area = h.id_area WHERE a.nivel = 'Planta Baja'");
        fila($r !== false, 'Hidrantes en áreas de Planta Baja', $r ? 'Registros: ' . pg_fetch_result($r, 0, 'n') : pg_last_error($bd));

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
                 $acepta ? 'Listo para guardar revisiones' : "FALTA: agrega 'hidrantes' al CHECK de tipo_activo");
        }
    }
}
?>
</table>

<p class="text-muted small mt-4">Cuando todo salga ✅, borra este archivo.</p>
</div>
</body>
</html>