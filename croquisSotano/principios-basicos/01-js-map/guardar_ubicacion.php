<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../clases/conexion.php';

try {
    $conexion = new Conexion();
    $bd = $conexion->conectar();

    $datos = json_decode(file_get_contents('php://input'), true);

    if (!$datos || !is_array($datos)) {
        throw new Exception('No se recibieron datos válidos.');
    }

    $tablasPermitidas = [
        'extintores',
        'botiquines',
        'luces_emergencia',
        'hidrantes',
        'detectores_humo',
        'alarmas_emergencia',
        'rutas_evacuacion'
    ];

    $tabla = $datos['tabla_origen'] ?? '';

    $idActivo = filter_var(
        $datos['id_activo_origen'] ?? null,
        FILTER_VALIDATE_INT
    );

    $x = $datos['coordenada_x'] ?? null;
    $y = $datos['coordenada_y'] ?? null;
    $croquis = $datos['croquis'] ?? 'sotano';

    if (!in_array($tabla, $tablasPermitidas, true)) {
        throw new Exception('Tipo de activo no permitido.');
    }

    if ($idActivo === false || $idActivo === null || $idActivo <= 0) {
        throw new Exception('El identificador del activo no es válido.');
    }

    if (!is_numeric($x) || !is_numeric($y)) {
        throw new Exception('Las coordenadas no son válidas.');
    }

    if (!preg_match('/^[a-z0-9_]{1,50}$/', $croquis)) {
        throw new Exception('El croquis no es válido.');
    }

    $tipo = $datos['tipo_activo'] ?? '';
    $folio = $datos['numero_folio'] ?? '';
    $nombre = $datos['nombre_activo'] ?? '';
    $ubicacion = $datos['ubicacion'] ?? '';
    $observaciones = $datos['observaciones'] ?? '';
    $color = $datos['color'] ?? '#dc3545';

    // Inserta solo si el activo todavía no está registrado.
    $sql = "
        INSERT INTO tb_ubicaciones_croquis (
            tabla_origen,
            id_activo_origen,
            tipo_activo,
            numero_folio,
            nombre_activo,
            ubicacion,
            observaciones,
            coordenada_x,
            coordenada_y,
            color,
            croquis
        )
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)
        ON CONFLICT (tabla_origen, id_activo_origen)
        DO NOTHING
        RETURNING id_ubicacion;
    ";

    $resultado = pg_query_params($bd, $sql, [
        $tabla,
        $idActivo,
        $tipo,
        $folio,
        $nombre,
        $ubicacion,
        $observaciones,
        $x,
        $y,
        $color,
        $croquis
    ]);

    if (!$resultado) {
        throw new Exception(pg_last_error($bd));
    }

    $fila = pg_fetch_assoc($resultado);

    if (!$fila) {
        echo json_encode([
            'success' => false,
            'duplicado' => true,
            'mensaje' => 'Este activo ya tiene una ubicación guardada. No puedes registrarlo nuevamente.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    echo json_encode([
        'success' => true,
        'mensaje' => 'Ubicación guardada correctamente.',
        'id_ubicacion' => $fila['id_ubicacion']
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}