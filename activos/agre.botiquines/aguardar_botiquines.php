<?php

require_once '../../clases/conexion.php';

// Crear conexión
$conexion = new Conexion();
$bd = $conexion->conectar();


// ======================================
// RECIBIR LOS DATOS DEL BOTIQUÍN
// ======================================

$tipo_botiquin = $_POST['tipo_botiquin'] ?? '';
$estado_fisico = $_POST['estado_fisico'] ?? '';
$capacidad = $_POST['capacidad'] ?? '';
$id_area = $_POST['id_area'] ?? '';
$fecha_instalacion = $_POST['fecha_instalacion'] ?? '';
$fecha_ultima_revision = $_POST['fecha_ultima_revision'] ?? '';
$fecha_proxima_revision = $_POST['fecha_proxima_revision'] ?? '';
$observaciones = $_POST['observaciones'] ?? '';

// Lista de medicamentos (arreglo enviado por el formulario dinámico)
$medicamentos = $_POST['medicamentos'] ?? [];


// ======================================
// INICIAR TRANSACCIÓN
// ======================================
// Usamos una transacción porque necesitamos que el botiquín
// y TODOS sus medicamentos se guarden juntos. Si algo falla
// a la mitad, no queremos un botiquín guardado sin insumos.

pg_query($bd, "BEGIN");


// ======================================
// INSERTAR EN TB_BOTIQUINES
// ======================================

$sqlBotiquin = "INSERT INTO public.tb_botiquines (
                    tipo_botiquin,
                    estado_fisico,
                    capacidad,
                    id_area,
                    fecha_instalacion,
                    fecha_ultima_revision,
                    fecha_proxima_revision,
                    observaciones
                )
                VALUES (
                    $1,
                    $2,
                    $3,
                    $4,
                    $5,
                    $6,
                    $7,
                    $8
                )
                RETURNING id_botiquin";

$resultadoBotiquin = pg_query_params(
    $bd,
    $sqlBotiquin,
    [
        $tipo_botiquin,
        $estado_fisico,
        $capacidad,
        $id_area,
        $fecha_instalacion,
        $fecha_ultima_revision,
        $fecha_proxima_revision,
        $observaciones
    ]
);


// ======================================
// COMPROBAR QUE EL BOTIQUÍN SE GUARDÓ
// ======================================

if (!$resultadoBotiquin) {

    pg_query($bd, "ROLLBACK");

    echo "<h2>Error al guardar el botiquín</h2>";
    echo pg_last_error($bd);

    exit;
}

$registroBotiquin = pg_fetch_assoc($resultadoBotiquin);
$id_botiquin = $registroBotiquin['id_botiquin'];


// ======================================
// INSERTAR CADA MEDICAMENTO
// ======================================

$sqlMedicamento = "INSERT INTO public.tb_medicamentos (
                        id_botiquin,
                        nombre_medicamento,
                        presentacion,
                        cantidad,
                        cantidad_minima,
                        fecha_caducidad,
                        estado
                    )
                    VALUES (
                        $1,
                        $2,
                        $3,
                        $4,
                        $5,
                        $6,
                        $7
                    )";

$errorMedicamentos = false;

foreach ($medicamentos as $medicamento) {

    $nombre_medicamento = $medicamento['nombre_medicamento'] ?? '';
    $presentacion = $medicamento['presentacion'] ?? '';
    $cantidad = $medicamento['cantidad'] ?? 0;
    $cantidad_minima = $medicamento['cantidad_minima'] ?? 0;
    $fecha_caducidad = $medicamento['fecha_caducidad'] ?? '';
    $estado = $medicamento['estado'] ?? '';

    $resultadoMedicamento = pg_query_params(
        $bd,
        $sqlMedicamento,
        [
            $id_botiquin,
            $nombre_medicamento,
            $presentacion,
            $cantidad,
            $cantidad_minima,
            $fecha_caducidad,
            $estado
        ]
    );

    if (!$resultadoMedicamento) {

        $errorMedicamentos = true;
        break;
    }
}


// ======================================
// CONFIRMAR O REVERTIR LA TRANSACCIÓN
// ======================================

if ($errorMedicamentos) {

    pg_query($bd, "ROLLBACK");

    echo "<h2>Error al guardar los medicamentos</h2>";
    echo pg_last_error($bd);

    exit;
}

pg_query($bd, "COMMIT");


// ======================================
// MOSTRAR RESULTADO
// ======================================

echo "<h2>Botiquín guardado correctamente</h2>";

echo "ID del botiquín: " . $id_botiquin . "<br>";
echo "Tipo: " . htmlspecialchars($tipo_botiquin) . "<br>";
echo "Estado físico: " . htmlspecialchars($estado_fisico) . "<br>";
echo "Capacidad: " . htmlspecialchars($capacidad) . "<br>";
echo "Área: " . htmlspecialchars($id_area) . "<br>";
echo "Fecha de instalación: " . htmlspecialchars($fecha_instalacion) . "<br>";
echo "Última revisión: " . htmlspecialchars($fecha_ultima_revision) . "<br>";
echo "Próxima revisión: " . htmlspecialchars($fecha_proxima_revision) . "<br>";
echo "Medicamentos guardados: " . count($medicamentos) . "<br>";

?>