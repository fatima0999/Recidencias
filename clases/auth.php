<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../clases/conexion.php';

try {
    $conexion = new Conexion();
    $bd = $conexion->conectar();

    if (!$bd) {
        throw new Exception("No se pudo conectar a la base de datos");
    }

    $accion = isset($_GET['accion']) ? $_GET['accion'] : 'listar';

    // =====================================================
    // LISTAR EVENTOS
    // =====================================================
    if ($accion === 'listar' || empty($accion)) {

        $consulta = "SELECT 
                        id,
                        title,
                        \"start\",
                        \"end\",
                        color,
                        descripcion,
                        textcolor AS \"textColor\"
                     FROM eventos
                     ORDER BY \"start\" ASC";

        $resultado = pg_query($bd, $consulta);

        if (!$resultado) {
            throw new Exception("Error al consultar eventos: " . pg_last_error($bd));
        }

        $eventos = [];
        while ($fila = pg_fetch_assoc($resultado)) {
            $eventos[] = $fila;
        }

        pg_free_result($resultado);
        echo json_encode($eventos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // =====================================================
    // AGREGAR EVENTO
    // =====================================================
    if ($accion === 'agregar') {

        $title       = isset($_POST['title'])       ? trim($_POST['title'])       : '';
        $start       = isset($_POST['start'])       ? trim($_POST['start'])       : '';
        $end         = isset($_POST['end'])         ? trim($_POST['end'])         : $start;
        $color       = isset($_POST['color'])       ? trim($_POST['color'])       : '#ff0000';
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $textcolor   = isset($_POST['textColor'])   ? trim($_POST['textColor'])   : '#FFFFFF';

        if (empty($title) || empty($start)) {
            throw new Exception("El título y la fecha son obligatorios");
        }

        $consulta = "INSERT INTO eventos (title, \"start\", \"end\", color, descripcion, textcolor)
                     VALUES ($1, $2, $3, $4, $5, $6)
                     RETURNING id";

        $resultado = pg_query_params($bd, $consulta, [
            $title,
            $start,
            $end,
            $color,
            $descripcion,
            $textcolor
        ]);

        if (!$resultado) {
            throw new Exception("Error al agregar evento: " . pg_last_error($bd));
        }

        $fila = pg_fetch_assoc($resultado);
        pg_free_result($resultado);

        echo json_encode([
            "success" => true,
            "message" => "Evento agregado correctamente",
            "id"      => $fila['id']
        ]);
        exit;
    }

    // =====================================================
    // MODIFICAR EVENTO
    // =====================================================
    if ($accion === 'modificar') {

        $id          = isset($_POST['id'])          ? (int)$_POST['id']           : 0;
        $title       = isset($_POST['title'])       ? trim($_POST['title'])       : '';
        $start       = isset($_POST['start'])       ? trim($_POST['start'])       : '';
        $end         = isset($_POST['end'])         ? trim($_POST['end'])         : $start;
        $color       = isset($_POST['color'])       ? trim($_POST['color'])       : '#ff0000';
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $textcolor   = isset($_POST['textColor'])   ? trim($_POST['textColor'])   : '#FFFFFF';

        if ($id <= 0) {
            throw new Exception("ID de evento no válido");
        }
        if (empty($title) || empty($start)) {
            throw new Exception("El título y la fecha son obligatorios");
        }

        $consulta = "UPDATE eventos 
                     SET title = $1,
                         \"start\" = $2,
                         \"end\" = $3,
                         color = $4,
                         descripcion = $5,
                         textcolor = $6
                     WHERE id = $7";

        $resultado = pg_query_params($bd, $consulta, [
            $title,
            $start,
            $end,
            $color,
            $descripcion,
            $textcolor,
            $id
        ]);

        if (!$resultado) {
            throw new Exception("Error al modificar evento: " . pg_last_error($bd));
        }

        echo json_encode([
            "success" => true,
            "message" => "Evento modificado correctamente"
        ]);
        exit;
    }

    // =====================================================
    // ELIMINAR EVENTO
    // =====================================================
    if ($accion === 'eliminar') {

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($id <= 0) {
            throw new Exception("ID de evento no válido");
        }

        $consulta = "DELETE FROM eventos WHERE id = $1";
        $resultado = pg_query_params($bd, $consulta, [$id]);

        if (!$resultado) {
            throw new Exception("Error al eliminar evento: " . pg_last_error($bd));
        }

        echo json_encode([
            "success" => true,
            "message" => "Evento eliminado correctamente"
        ]);
        exit;
    }

    throw new Exception("Acción no válida");

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error"   => true,
        "message" => $e->getMessage()
    ]);
}