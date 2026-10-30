<?php
/* ==========================================================
   GUARDAR BOTIQUÍN (SÓTANO) + SUS MEDICAMENTOS / INSUMOS
   Ruta: recidencias/activos/areas.php/seguimientoSotano.php/botiquines/guardar_botiquin.php
   ========================================================== */

require_once __DIR__ . '/../../../../clases/conexion.php';

/* Nombre de la tabla hija (cámbialo aquí si la tuya se llama distinto) */
const TABLA_INSUMOS = 'public.tb_medicamentos';

/* Solo aceptar envíos del formulario */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../botiquines.php');
    exit;
}

$bd = (new Conexion())->conectar();


/* ==========================================================
   FUNCIONES DE APOYO
   ========================================================== */

/* Campo opcional: '' se convierte en NULL */
function valorONulo(array $datos, string $campo): ?string
{
    $valor = trim($datos[$campo] ?? '');
    return $valor === '' ? null : $valor;
}

/* Valida una fecha con formato YYYY-MM-DD */
function fechaValida(?string $fecha): bool
{
    if ($fecha === null) {
        return false;
    }
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d !== false && $d->format('Y-m-d') === $fecha;
}

function fallar(string $mensaje): void
{
    die($mensaje);
}


/* ==========================================================
   LEER Y VALIDAR LOS DATOS DEL BOTIQUÍN
   ========================================================== */

$tipoBotiquin = trim($_POST['tipo_botiquin'] ?? '');
$estadoFisico = trim($_POST['estado_fisico'] ?? '');
$capacidad    = valorONulo($_POST, 'capacidad');
$idArea       = trim($_POST['id_area'] ?? '');
$fInstalacion = valorONulo($_POST, 'fecha_instalacion');
$fUltima      = valorONulo($_POST, 'fecha_ultima_revision');
$fProxima     = valorONulo($_POST, 'fecha_proxima_revision');
$observaciones = valorONulo($_POST, 'observaciones');

$tiposValidos   = ['Básico', 'Intermedio', 'Avanzado', 'Portátil'];
$estadosValidos = ['Bueno', 'Regular', 'Malo'];

if (!in_array($tipoBotiquin, $tiposValidos, true)) {
    fallar('Tipo de botiquín no válido.');
}

if (!in_array($estadoFisico, $estadosValidos, true)) {
    fallar('Estado físico no válido.');
}

if ($capacidad === null || mb_strlen($capacidad) > 50) {
    fallar('La capacidad es obligatoria (máximo 50 caracteres).');
}

if (!ctype_digit($idArea)) {
    fallar('Área no válida.');
}

if (!fechaValida($fInstalacion) || !fechaValida($fUltima) || !fechaValida($fProxima)) {
    fallar('Las tres fechas del botiquín son obligatorias y deben ser válidas.');
}

$hoy = date('Y-m-d');

if ($fInstalacion > $hoy || $fUltima > $hoy) {
    fallar('La instalación y la última revisión no pueden ser fechas futuras.');
}

if ($fUltima < $fInstalacion) {
    fallar('La última revisión no puede ser anterior a la instalación.');
}

if ($fProxima <= $fUltima) {
    fallar('La próxima revisión debe ser posterior a la última revisión.');
}

/* El área debe existir, estar activa y ser del Sótano */
$resArea = pg_query_params(
    $bd,
    "SELECT 1 FROM public.tb_areas
     WHERE id_area = $1 AND nivel = 'Sótano' AND activo = TRUE",
    [(int) $idArea]
);

if ($resArea === false || pg_num_rows($resArea) === 0) {
    fallar('El área seleccionada no pertenece al Sótano.');
}


/* ==========================================================
   LEER Y VALIDAR LOS MEDICAMENTOS / INSUMOS
   ========================================================== */

$estadosInsumo = ['Vigente', 'Próximo a caducar', 'Caducado'];
$insumos = [];

$medicamentos = $_POST['medicamentos'] ?? [];

if (!is_array($medicamentos)) {
    fallar('Medicamentos no válidos.');
}

foreach ($medicamentos as $m) {

    if (!is_array($m)) {
        continue;
    }

    $nombre       = trim($m['nombre_medicamento'] ?? '');
    $presentacion = trim($m['presentacion'] ?? '');
    $cantidad     = trim($m['cantidad'] ?? '');
    $cantidadMin  = trim($m['cantidad_minima'] ?? '');
    $caducidad    = valorONulo($m, 'fecha_caducidad');
    $estadoInsumo = trim($m['estado'] ?? '');

    if ($nombre === '' || mb_strlen($nombre) > 100) {
        fallar('Cada medicamento necesita un nombre (máximo 100 caracteres).');
    }

    if ($presentacion === '' || mb_strlen($presentacion) > 50) {
        fallar('Cada medicamento necesita una presentación (máximo 50 caracteres).');
    }

    if (!ctype_digit($cantidad) || !ctype_digit($cantidadMin)) {
        fallar('La cantidad y la cantidad mínima deben ser números enteros (0 o más).');
    }

    if (!fechaValida($caducidad)) {
        fallar('Cada medicamento necesita una fecha de caducidad válida.');
    }

    if (!in_array($estadoInsumo, $estadosInsumo, true)) {
        fallar('Estado de medicamento no válido.');
    }

    $insumos[] = [
        $nombre,
        $presentacion,
        (int) $cantidad,
        (int) $cantidadMin,
        $caducidad,
        $estadoInsumo,
    ];
}

if (count($insumos) === 0) {
    fallar('Agrega al menos un medicamento o insumo.');
}


/* ==========================================================
   GUARDAR EN UNA TRANSACCIÓN
   (si algo falla, no queda un botiquín a medias)
   ========================================================== */

pg_query($bd, 'BEGIN');

$sqlBotiquin = "
    INSERT INTO public.tb_botiquines (
        id_area,
        tipo_botiquin,
        estado_fisico,
        capacidad,
        fecha_instalacion,
        fecha_ultima_revision,
        fecha_proxima_revision,
        observaciones
    )
    VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
    RETURNING id_botiquin
";

$resBotiquin = pg_query_params($bd, $sqlBotiquin, [
    (int) $idArea,
    $tipoBotiquin,
    $estadoFisico,
    $capacidad,
    $fInstalacion,
    $fUltima,
    $fProxima,
    $observaciones,
]);

if ($resBotiquin === false) {
    $error = pg_last_error($bd);
    pg_query($bd, 'ROLLBACK');
    fallar('Error al guardar el botiquín: ' . $error);
}

$idBotiquin = (int) pg_fetch_result($resBotiquin, 0, 'id_botiquin');

$sqlInsumo = '
    INSERT INTO ' . TABLA_INSUMOS . ' (
        id_botiquin,
        nombre_medicamento,
        presentacion,
        cantidad,
        cantidad_minima,
        fecha_caducidad,
        estado
    )
    VALUES ($1, $2, $3, $4, $5, $6, $7)
';

foreach ($insumos as $insumo) {

    $resInsumo = pg_query_params($bd, $sqlInsumo, array_merge([$idBotiquin], $insumo));

    if ($resInsumo === false) {
        $error = pg_last_error($bd);
        pg_query($bd, 'ROLLBACK');
        fallar('Error al guardar los medicamentos: ' . $error);
    }
}

pg_query($bd, 'COMMIT');

header('Location: ../botiquines.php?msg=guardado');
exit;