<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../clases/conexion.php';

try {
    $conexion = new Conexion();
    $bd = $conexion->conectar();

    $datos = json_decode(file_get_contents('php://input'), true);

    $tabla = $datos['tabla_origen'] ?? '';
    $idActivo = filter_var(
        $datos['id_activo_origen'] ?? null,
        FILTER_VALIDATE_INT
    );

    $tablasPermitidas = [
        'extintores',
        'botiquines',
        'luces_emergencia',
        'hidrantes',
        'detectores_humo',
        'alarmas_emergencia',
        'rutas_evacuacion'
    ];

    if (!in_array($tabla, $tablasPermitidas, true) || !$idActivo) {
        throw new Exception('Datos incorrectos para eliminar la ubicación.');
    }

    $sql = "
        DELETE FROM tb_ubicaciones_croquis
        WHERE tabla_origen = $1
          AND id_activo_origen = $2;
    ";

    $resultado = pg_query_params($bd, $sql, [
        $tabla,
        $idActivo
    ]);

    if (!$resultado) {
        throw new Exception(pg_last_error($bd));
    }

    echo json_encode([
        'success' => true,
        'mensaje' => 'Se eliminó la ubicación del croquis.'
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}