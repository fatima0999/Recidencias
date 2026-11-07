
<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../clases/conexion.php';

try {
    $conexion = new Conexion();
    $bd = $conexion->conectar();

    $sql = "
        SELECT
            id_ubicacion,
            tabla_origen,
            id_activo_origen,
            tipo_activo,
            numero_folio,
            nombre_activo,
            ubicacion,
            observaciones,
            coordenada_x,
            coordenada_y,
            color
        FROM tb_ubicaciones_croquis
        ORDER BY id_ubicacion;
    ";

    $consulta = pg_query($bd, $sql);

    if (!$consulta) {
        throw new Exception(pg_last_error($bd));
    }

    $ubicaciones = pg_fetch_all($consulta);

    echo json_encode(
        $ubicaciones ?: [],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    pg_free_result($consulta);
    pg_close($bd);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
