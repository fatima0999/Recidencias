<?php

/* ==========================================================
   CONEXIÓN
   ========================================================== */

require_once __DIR__ . '/../../../clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();


/* ==========================================================
   VALIDAR ID DEL EXTINTOR
   ========================================================== */

if (!isset($_POST['id_extintor']) || !is_numeric($_POST['id_extintor'])) {
    die("ID de extintor no válido.");
}

$idExtintor = (int) $_POST['id_extintor'];


/* ==========================================================
   RECIBIR DATOS DEL FORMULARIO
   ========================================================== */

$numeroFolio = trim($_POST['numero_folio'] ?? '');
$tipoAgente = trim($_POST['tipo_agente'] ?? '');
$capacidad = trim($_POST['capacidad'] ?? '');
$marca = trim($_POST['marca'] ?? '');
$modelo = trim($_POST['modelo'] ?? '');
$estadoFisico = trim($_POST['estado_fisico'] ?? '');

$idArea = trim($_POST['id_area'] ?? '');
$idProveedor = trim($_POST['id_proveedor'] ?? '');

$fechaUltimo = trim($_POST['fecha_ultimo_mantenimiento'] ?? '');
$fechaProximo = trim($_POST['fecha_proximo_mantenimiento'] ?? '');


/* ==========================================================
   VALIDAR CAMPOS OBLIGATORIOS
   ========================================================== */

if ($numeroFolio === '') {
    die("El número de folio es obligatorio.");
}

if ($idArea === '' || !is_numeric($idArea)) {
    die("Debes seleccionar un área válida.");
}


/* ==========================================================
   PREPARAR CAMPOS OPCIONALES
   ========================================================== */

$tipoAgenteParam = ($tipoAgente === '') ? null : $tipoAgente;

$capacidadParam = ($capacidad === '')
    ? null
    : (float) $capacidad;

$marcaParam = ($marca === '')
    ? null
    : $marca;

$modeloParam = ($modelo === '')
    ? null
    : $modelo;

$idProveedorParam = (
    $idProveedor === '' ||
    !is_numeric($idProveedor)
)
    ? null
    : (int) $idProveedor;

$estadoFisicoParam = ($estadoFisico === '')
    ? null
    : $estadoFisico;

$fechaUltimoParam = ($fechaUltimo === '')
    ? null
    : $fechaUltimo;

$fechaProximoParam = ($fechaProximo === '')
    ? null
    : $fechaProximo;


/* ==========================================================
   ACTUALIZAR EXTINTOR
   ========================================================== */

$sql = "
    UPDATE tb_extintores
    SET
        numero_folio = $1,
        tipo_agente = $2,
        capacidad = $3,
        marca = $4,
        modelo = $5,
        id_area = $6,
        id_proveedor = $7,
        estado_fisico = $8,
        fecha_ultimo_mantenimiento = $9,
        fecha_proximo_mantenimiento = $10,
        updated_at = CURRENT_TIMESTAMP
    WHERE id_extintor = $11
";


/* ==========================================================
   EJECUTAR ACTUALIZACIÓN
   ========================================================== */

$params = [
    $numeroFolio,
    $tipoAgenteParam,
    $capacidadParam,
    $marcaParam,
    $modeloParam,
    (int) $idArea,
    $idProveedorParam,
    $estadoFisicoParam,
    $fechaUltimoParam,
    $fechaProximoParam,
    $idExtintor
];

$resultado = pg_query_params(
    $conexion,
    $sql,
    $params
);


/* ==========================================================
   VERIFICAR RESULTADO
   ========================================================== */

if ($resultado === false) {

    echo "<h2>Error al actualizar el extintor</h2>";

    echo "<p>";
    echo pg_last_error($conexion);
    echo "</p>";

    echo '<br>';

    echo '<a href="editar_extintor.php?id=' . $idExtintor . '">';
    echo 'Regresar al formulario';
    echo '</a>';

    exit;
}


/* ==========================================================
   REGRESAR AL DETALLE DEL EXTINTOR
   ========================================================== */

header(
    "Location: ver_extinguidores.php?id=" . $idExtintor
);

exit;

?>