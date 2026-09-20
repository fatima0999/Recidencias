
<?php

require_once '../../clases/conexion.php';

// Crear conexión
$conexion = new Conexion();
$bd = $conexion->conectar();


// ======================================
// RECIBIR LOS DATOS DEL FORMULARIO
// ======================================

$numero_folio = $_POST['numero_folio'] ?? '';
$tipo_agente = $_POST['tipo_agente'] ?? '';
$capacidad = $_POST['capacidad'] ?? '';
$marca = $_POST['marca'] ?? '';
$modelo = $_POST['modelo'] ?? '';
$id_area = $_POST['id_area'] ?? '';
$id_proveedor = $_POST['id_proveedor'] ?? '';
$fecha_ultimo_mantenimiento = $_POST['fecha_ultimo_mantenimiento'] ?? '';
$fecha_proximo_mantenimiento = $_POST['fecha_proximo_mantenimiento'] ?? '';


// ======================================
// INSERTAR EN TB_EXTINTORES
// ======================================

$sql = "INSERT INTO public.tb_extintores (
            numero_folio,
            tipo_agente,
            capacidad,
            marca,
            modelo,
            id_area,
            id_proveedor,
            fecha_ultimo_mantenimiento,
            fecha_proximo_mantenimiento
        )
        VALUES (
            $1,
            $2,
            $3,
            $4,
            $5,
            $6,
            $7,
            $8,
            $9
        )
        RETURNING id_extintor";


// ======================================
// EJECUTAR INSERT
// ======================================

$resultado = pg_query_params(
    $bd,
    $sql,
    [
        $numero_folio,
        $tipo_agente,
        $capacidad,
        $marca,
        $modelo,
        $id_area,
        $id_proveedor,
        $fecha_ultimo_mantenimiento,
        $fecha_proximo_mantenimiento
    ]
);


// ======================================
// COMPROBAR RESULTADO
// ======================================

if ($resultado) {

    $registro = pg_fetch_assoc($resultado);

    echo "<h2>Extintor guardado correctamente</h2>";

    echo "ID del extintor: " . $registro['id_extintor'] . "<br>";
    echo "Folio: " . htmlspecialchars($numero_folio) . "<br>";
    echo "Tipo de agente: " . htmlspecialchars($tipo_agente) . "<br>";
    echo "Capacidad: " . htmlspecialchars($capacidad) . "<br>";
    echo "Marca: " . htmlspecialchars($marca) . "<br>";
    echo "Modelo: " . htmlspecialchars($modelo) . "<br>";
    echo "Área: " . htmlspecialchars($id_area) . "<br>";
    echo "Proveedor: " . htmlspecialchars($id_proveedor) . "<br>";
    echo "Último mantenimiento: " . htmlspecialchars($fecha_ultimo_mantenimiento) . "<br>";
    echo "Próximo mantenimiento: " . htmlspecialchars($fecha_proximo_mantenimiento) . "<br>";

} else {

    echo "<h2>Error al guardar el extintor</h2>";

    echo pg_last_error($bd);
}

?>
