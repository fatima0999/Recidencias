<?php

header('Content-Type: application/json; charset=utf-8');

require_once "../clases/conexion.php";

try {

    $conexion = new Conexion();
    $db = $conexion->conectar();

    $accion = isset($_GET['accion']) ? $_GET['accion'] : 'leer';

    switch ($accion) {

        // =====================================================
        // LEER EVENTOS
        // =====================================================
        case 'leer':

            $sql = '
                SELECT
                    id,
                    title,
                    descripcion,
                    color,
                    textcolor,
                    start,
                    "end"
                FROM eventos
                ORDER BY start ASC
            ';

            $resultado = pg_query($db, $sql);

            if (!$resultado) {
                throw new Exception(pg_last_error($db));
            }

            $eventos = [];
            while ($fila = pg_fetch_assoc($resultado)) {
                // Renombramos textcolor → textColor para FullCalendar
                $fila['textColor'] = $fila['textcolor'];
                unset($fila['textcolor']);
                $eventos[] = $fila;
            }

            echo json_encode($eventos);
            break;


        // =====================================================
        // AGREGAR EVENTO
        // =====================================================
        case 'agregar':

            $title       = trim($_POST['title'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $color       = $_POST['color'] ?? '#ff0000';
            $textColor   = $_POST['textColor'] ?? '#FFFFFF';
            $start       = $_POST['start'] ?? null;
            $end         = $_POST['end'] ?? null;

            if ($title === '') {
                echo json_encode([
                    "ok" => false,
                    "mensaje" => "El título es obligatorio"
                ]);
                exit;
            }

            $sql = '
                INSERT INTO eventos
                (
                    title,
                    descripcion,
                    color,
                    textcolor,
                    start,
                    "end"
                )
                VALUES
                ($1, $2, $3, $4, $5, $6)
                RETURNING id
            ';

            $resultado = pg_query_params($db, $sql, [
                $title,
                $descripcion,
                $color,
                $textColor,
                $start,
                $end
            ]);

            if (!$resultado) {
                throw new Exception(pg_last_error($db));
            }

            $fila = pg_fetch_assoc($resultado);

            echo json_encode([
                "ok" => true,
                "mensaje" => "Evento agregado correctamente",
                "id" => $fila['id']
            ]);
            break;


        // =====================================================
        // MODIFICAR EVENTO
        // =====================================================
        case 'modificar':

            $id = $_POST['id'] ?? '';

            if ($id === '' || $id === null) {
                echo json_encode([
                    "ok" => false,
                    "mensaje" => "No se recibió el ID del evento"
                ]);
                exit;
            }

            $title       = trim($_POST['title'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $color       = $_POST['color'] ?? '#ff0000';
            $textColor   = $_POST['textColor'] ?? '#FFFFFF';
            $start       = $_POST['start'] ?? null;
            $end         = $_POST['end'] ?? null;

            $sql = '
                UPDATE eventos
                SET
                    title = $1,
                    descripcion = $2,
                    color = $3,
                    textcolor = $4,
                    start = $5,
                    "end" = $6
                WHERE id = $7
            ';

            $resultado = pg_query_params($db, $sql, [
                $title,
                $descripcion,
                $color,
                $textColor,
                $start,
                $end,
                $id
            ]);

            if (!$resultado) {
                throw new Exception(pg_last_error($db));
            }

            echo json_encode([
                "ok" => true,
                "mensaje" => "Evento modificado correctamente"
            ]);
            break;


        // =====================================================
        // ELIMINAR EVENTO
        // =====================================================
        case 'eliminar':

            $id = $_POST['id'] ?? '';

            if ($id === '' || $id === null) {
                echo json_encode([
                    "ok" => false,
                    "mensaje" => "No se recibió el ID del evento"
                ]);
                exit;
            }

            $sql = 'DELETE FROM eventos WHERE id = $1';

            $resultado = pg_query_params($db, $sql, [$id]);

            if (!$resultado) {
                throw new Exception(pg_last_error($db));
            }

            echo json_encode([
                "ok" => true,
                "mensaje" => "Evento eliminado correctamente"
            ]);
            break;


        // =====================================================
        // ACCIÓN NO VÁLIDA
        // =====================================================
        default:

            echo json_encode([
                "ok" => false,
                "mensaje" => "Acción no válida"
            ]);
            break;
    }

    pg_close($db);

} catch (Exception $e) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "Error en la operación",
        "error" => $e->getMessage()
    ]);
}