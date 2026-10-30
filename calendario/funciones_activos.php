<?php
/* calendario/funciones_activos.php */

function obtenerActivosVencimientos($conexion)
{
    $consultas = [

        [
            'tabla' => 'Extintores',
            'sql' => "
                SELECT
                    e.id_extintor::text AS id,
                    COALESCE(e.numero_folio, 'Extintor') AS nombre,
                    a.nombre_area AS ubicacion,
                    LEAST(e.fecha_proximo_mantenimiento, e.fecha_proxima_recarga)::date AS fecha,
                    'Mantenimiento / recarga' AS actividad
                FROM public.tb_extintores e
                LEFT JOIN public.tb_areas a ON a.id_area = e.id_area
                WHERE LEAST(e.fecha_proximo_mantenimiento, e.fecha_proxima_recarga) IS NOT NULL
            "
        ],

        [
            'tabla' => 'Botiquines',
            'sql' => "
                SELECT
                    b.id_botiquin::text AS id,
                    'Botiquín #' || b.id_botiquin AS nombre,
                    a.nombre_area AS ubicacion,
                    b.fecha_proxima_revision::date AS fecha,
                    'Revisión de botiquín' AS actividad
                FROM public.tb_botiquines b
                LEFT JOIN public.tb_areas a ON a.id_area = b.id_area
                WHERE b.fecha_proxima_revision IS NOT NULL
            "
        ],

        [
            'tabla' => 'Detectores de humo',
            'sql' => "
                SELECT
                    d.id_detector::text AS id,
                    COALESCE(NULLIF(d.numero_serie, ''), 'Detector #' || d.id_detector) AS nombre,
                    a.nombre_area AS ubicacion,
                    d.fecha_proxima_revision::date AS fecha,
                    'Revisión de detector de humo' AS actividad
                FROM public.tb_detectores_humo d
                LEFT JOIN public.tb_areas a ON a.id_area = d.id_area
                WHERE d.fecha_proxima_revision IS NOT NULL
            "
        ],

        [
            'tabla' => 'Luces de emergencia',
            'sql' => "
                SELECT
                    l.id_luz::text AS id,
                    'Luz de emergencia #' || l.id_luz AS nombre,
                    a.nombre_area AS ubicacion,
                    l.fecha_proxima_revision::date AS fecha,
                    'Revisión de luz de emergencia' AS actividad
                FROM public.tb_luces_emergencia l
                LEFT JOIN public.tb_areas a ON a.id_area = l.id_area
                WHERE l.fecha_proxima_revision IS NOT NULL
            "
        ],

        [
            'tabla' => 'Hidrantes',
            'sql' => "
                SELECT
                    h.id_hidrante::text AS id,
                    COALESCE(NULLIF(h.numero_folio, ''), 'Hidrante #' || h.id_hidrante) AS nombre,
                    a.nombre_area AS ubicacion,
                    h.fecha_proxima_revision::date AS fecha,
                    'Revisión de hidrante' AS actividad
                FROM public.tb_hidrantes h
                LEFT JOIN public.tb_areas a ON a.id_area = h.id_area
                WHERE h.fecha_proxima_revision IS NOT NULL
            "
        ]
    ];

    $eventos = [];
    $hoy = date('Y-m-d');

    foreach ($consultas as $consulta) {
        $resultado = @pg_query($conexion, $consulta['sql']);

        if ($resultado === false) {
            error_log('SIPV calendario: error al consultar ' . $consulta['tabla'] . ': ' . pg_last_error($conexion));
            continue;
        }

        while ($fila = pg_fetch_assoc($resultado)) {
            if (empty($fila['fecha'])) {
                continue;
            }

            $fecha = $fila['fecha'];
            $dias = (int) ((strtotime($fecha) - strtotime($hoy)) / 86400);

            if ($dias < 0) {
                $estado = 'VENCIDO';
                $color = '#dc3545';
            } elseif ($dias <= 15) {
                $estado = 'POR VENCER';
                $color = '#ffc107';
            } else {
                $estado = 'PROGRAMADO';
                $color = '#198754';
            }

            $ubicacion = $fila['ubicacion'] ?: 'Ubicación no registrada';

            $eventos[] = [
                'id' => 'activo_' . strtolower(str_replace(' ', '_', $consulta['tabla'])) . '_' . $fila['id'],
                'title' => $fila['nombre'] . ' - ' . $estado,
                'start' => $fecha,
                'allDay' => true,
                'color' => $color,
                'textColor' => ($color === '#ffc107') ? '#212529' : '#ffffff',
                'tipo' => 'activo',
                'estado' => $estado,
                'ubicacion' => $ubicacion,
                'actividad' => $fila['actividad'],
                'dias' => $dias,
                'descripcion' =>
                    $fila['actividad'] .
                    ' | Ubicación: ' . $ubicacion .
                    ' | Fecha: ' . date('d/m/Y', strtotime($fecha)) .
                    ' | Estado: ' . $estado
            ];
        }

        pg_free_result($resultado);
    }

    usort($eventos, function ($a, $b) {
        return strcmp($a['start'], $b['start']);
    });

    return $eventos;
}