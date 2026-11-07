<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../clases/conexion.php';

try {
    $conexion = new Conexion();
    $bd = $conexion->conectar();

    $sql = "
        WITH activos AS (

            SELECT
                'extintores'::VARCHAR AS tabla_origen,
                e.id_extintor AS id_activo_origen,
                COALESCE(e.numero_folio, '')::VARCHAR AS numero_folio,
                'Extintor'::VARCHAR AS tipo_activo,
                COALESCE(e.tipo_agente, 'Extintor')::VARCHAR AS nombre_activo,
                COALESCE(a.nombre_area, '')::VARCHAR AS ubicacion,
                COALESCE(e.observaciones, '')::TEXT AS observaciones
            FROM tb_extintores e
            LEFT JOIN tb_areas a ON e.id_area = a.id_area

            UNION ALL

            SELECT
                'botiquines'::VARCHAR,
                b.id_botiquin,
                ''::VARCHAR,
                'Botiquín'::VARCHAR,
                COALESCE(b.tipo_botiquin, 'Botiquín')::VARCHAR,
                COALESCE(a.nombre_area, '')::VARCHAR,
                COALESCE(b.observaciones, '')::TEXT
            FROM tb_botiquines b
            LEFT JOIN tb_areas a ON b.id_area = a.id_area

            UNION ALL

            SELECT
                'luces_emergencia'::VARCHAR,
                l.id_luz,
                COALESCE(l.numero_folio, '')::VARCHAR,
                'Luz de emergencia'::VARCHAR,
                COALESCE(l.tipo_iluminacion, 'Luz de emergencia')::VARCHAR,
                COALESCE(a.nombre_area, '')::VARCHAR,
                COALESCE(l.observaciones, '')::TEXT
            FROM tb_luces_emergencia l
            LEFT JOIN tb_areas a ON l.id_area = a.id_area

            UNION ALL

            SELECT
                'hidrantes'::VARCHAR,
                h.id_hidrante,
                COALESCE(h.numero_folio, '')::VARCHAR,
                'Hidrante'::VARCHAR,
                COALESCE(h.tipo_hidrante, 'Hidrante')::VARCHAR,
                COALESCE(a.nombre_area, '')::VARCHAR,
                COALESCE(h.observaciones, '')::TEXT
            FROM tb_hidrantes h
            LEFT JOIN tb_areas a ON h.id_area = a.id_area
        )

        SELECT
            ac.*,
            u.id_ubicacion,
            u.coordenada_x,
            u.coordenada_y,
            u.color,
            u.croquis,

            CASE
                WHEN u.id_ubicacion IS NOT NULL THEN TRUE
                ELSE FALSE
            END AS ya_registrado

        FROM activos ac

        LEFT JOIN tb_ubicaciones_croquis u
            ON u.tabla_origen = ac.tabla_origen
            AND u.id_activo_origen = ac.id_activo_origen

        ORDER BY ac.tipo_activo, ac.numero_folio;
    ";

    $consulta = pg_query($bd, $sql);

    if (!$consulta) {
        throw new Exception(pg_last_error($bd));
    }

    $activos = pg_fetch_all($consulta);

    echo json_encode(
        $activos ?: [],
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