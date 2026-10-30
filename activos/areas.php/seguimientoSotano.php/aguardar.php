<?php
/* ==========================================================
   GUARDAR LUZ DE EMERGENCIA (SÓTANO)
   Ruta: recidencias/activos/areas.php/seguimientoSotano.php/luces/guardar_luz.php
   ========================================================== */

require_once __DIR__ . '/../../../../clases/conexion.php';

/* Solo aceptar envíos del formulario */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../lucesDeEmergencia.php');
    exit;
}

$bd = (new Conexion())->conectar();


/* ==========================================================
   FUNCIONES DE APOYO
   ========================================================== */

/* Campo opcional: '' se convierte en NULL */
function valorONulo(string $campo): ?string
{
    $valor = trim($_POST[$campo] ?? '');
    return $valor === '' ? null : $valor;
}

/* Valida una fecha con formato YYYY-MM-DD */
function fechaValida(?string $fecha): bool
{
    if ($fecha === null) {
        return false;
    }

    $d = DateTime::createFromFormat('!Y-m-d', $fecha);

    return $d !== false && $d->format('Y-m-d') === $fecha;
}

function fallar(string $mensaje): void
{
    die($mensaje);
}


/* ==========================================================
   LEER DATOS DEL FORMULARIO
   ========================================================== */

$idArea          = trim($_POST['id_area'] ?? '');
$tipoIluminacion = trim($_POST['tipo_iluminacion'] ?? '');
$marca           = valorONulo('marca');
$modelo          = valorONulo('modelo');
$duracionBateria = valorONulo('duracion_bateria');
$estadoFisico    = trim($_POST['estado_fisico'] ?? '');
$fInstalacion    = valorONulo('fecha_instalacion');
$fUltima         = valorONulo('fecha_ultima_revision');
$fProxima        = valorONulo('fecha_proxima_revision');
$observaciones   = valorONulo('observaciones');


/* ==========================================================
   VALIDACIONES DEL LADO DEL SERVIDOR
   ========================================================== */

$tiposValidos   = ['LED', 'Fluorescente', 'Halógena', 'Otro'];
$estadosValidos = ['Bueno', 'Regular', 'Malo'];

if (!ctype_digit($idArea)) {
    fallar('Área no válida.');
}

if (!in_array($tipoIluminacion, $tiposValidos, true)) {
    fallar('Tipo de iluminación no válido.');
}

if (!in_array($estadoFisico, $estadosValidos, true)) {
    fallar('Estado físico no válido.');
}

if ($duracionBateria === null || mb_strlen($duracionBateria) > 50) {
    fallar('La duración de la batería es obligatoria (máximo 50 caracteres).');
}

if (($marca !== null && mb_strlen($marca) > 100) || ($modelo !== null && mb_strlen($modelo) > 100)) {
    fallar('La marca y el modelo admiten máximo 100 caracteres.');
}

if (!fechaValida($fInstalacion) || !fechaValida($fUltima) || !fechaValida($fProxima)) {
    fallar('Las tres fechas son obligatorias y deben ser válidas.');
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
   INSERTAR EN tb_luces_emergencia
   ========================================================== */

$sql = "
    INSERT INTO public.tb_luces_emergencia (
        id_area,
        tipo_iluminacion,
        marca,
        modelo,
        duracion_bateria,
        estado_fisico,
        fecha_instalacion,
        fecha_ultima_revision,
        fecha_proxima_revision,
        observaciones
    )
    VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
";

$resultado = pg_query_params($bd, $sql, [
    (int) $idArea,
    $tipoIluminacion,
    $marca,
    $modelo,
    $duracionBateria,
    $estadoFisico,
    $fInstalacion,
    $fUltima,
    $fProxima,
    $observaciones,
]);

if ($resultado === false) {
    /* Mientras desarrollas, muestra el error real de PostgreSQL */
    fallar('Error al guardar la luz de emergencia: ' . pg_last_error($bd));
}

header('Location: ../lucesDeEmergencia.php?msg=guardado');
exit;