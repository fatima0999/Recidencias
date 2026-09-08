<?php
require_once __DIR__ . '/../clases/conexion.php';

$conexion = new Conexion();
$db = $conexion->conectar();

// Consultar áreas
$sql_areas = "
    SELECT id_area, nombre_area
    FROM tb_areas
    WHERE activo = TRUE
    ORDER BY id_area
";
$resultado_areas = pg_query($db, $sql_areas);

if (!$resultado_areas) {
    die("Error al consultar las áreas: " . pg_last_error($db));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Extintor</title>
    <link rel="stylesheet" href="boton.css">
</head>
<body>

    <!-- Fondo oscuro -->
    <div class="overlay"></div>

    <!-- Popup -->
    <div class="popup">

        <a href="activos.php">
            <button class="close-btn" id="cerrar">&times;</button>
        </a>

        <form method="POST" action="guardar_extintor.php">

            <h2>🧯 Nuevo Extintor</h2>

            <!-- Número de Folio -->
            <div class="form-element">
                <label for="numero_folio">Número de Folio *</label>
                <input type="text" name="numero_folio" id="numero_folio" placeholder="Ej: EXT-001" required>
            </div>

            <!-- Tipo de Agente -->
            <div class="form-element">
                <label for="tipo_agente">Tipo de Agente *</label>
                <select name="tipo_agente" id="tipo_agente" class="styled-select" required>
                    <option value="">Seleccionar tipo de agente</option>
                    <option value="PQS">PQS (Polvo Químico Seco)</option>
                    <option value="CO2">CO₂</option>
                    <option value="Agua">Agua</option>
                    <option value="Espuma">Espuma</option>
                    <option value="Halón">Halón</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>

            <!-- Capacidad -->
            <div class="form-element">
                <label for="capacidad">Capacidad (kg / litros)</label>
                <input type="number" step="0.01" name="capacidad" id="capacidad" placeholder="Ej: 6.00">
            </div>

            <!-- Marca -->
            <div class="form-element">
                <label for="marca">Marca</label>
                <input type="text" name="marca" id="marca" placeholder="Ej: Amerex">
            </div>

            <!-- Modelo -->
            <div class="form-element">
                <label for="modelo">Modelo</label>
                <input type="text" name="modelo" id="modelo">
            </div>

            <!-- Estado Físico -->
            <div class="form-element">
                <label for="estado_fisico">Estado Físico</label>
                <select name="estado_fisico" id="estado_fisico" class="styled-select">
                    <option value="Bueno">Bueno</option>
                    <option value="Regular">Regular</option>
                    <option value="Malo">Malo</option>
                    <option value="Fuera de servicio">Fuera de servicio</option>
                </select>
            </div>

            <!-- Área -->
            <div class="form-element">
                <label for="id_area">Área</label>
                <select name="id_area" id="id_area" class="styled-select">
                    <option value="">Seleccionar área</option>
                    <?php while ($area = pg_fetch_assoc($resultado_areas)): ?>
                        <option value="<?= $area['id_area'] ?>">
                            <?= htmlspecialchars($area['nombre_area']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Fechas -->
            <div class="form-element">
                <label for="fecha_ultimo_mantenimiento">Fecha Último Mantenimiento</label>
                <input type="date" name="fecha_ultimo_mantenimiento" id="fecha_ultimo_mantenimiento">
            </div>

            <div class="form-element">
                <label for="fecha_proximo_mantenimiento">Fecha Próximo Mantenimiento</label>
                <input type="date" name="fecha_proximo_mantenimiento" id="fecha_proximo_mantenimiento">
            </div>

            <div class="form-element">
                <label for="fecha_recarga">Fecha de Recarga</label>
                <input type="date" name="fecha_recarga" id="fecha_recarga">
            </div>

            <div class="form-element">
                <label for="fecha_proxima_recarga">Fecha Próxima Recarga</label>
                <input type="date" name="fecha_proxima_recarga" id="fecha_proxima_recarga">
            </div>

            <!-- Peso Actual -->
            <div class="form-element">
                <label for="peso_actual">Peso Actual (kg)</label>
                <input type="number" step="0.01" name="peso_actual" id="peso_actual">
            </div>

            <!-- Observaciones -->
            <div class="form-element">
                <label for="observaciones">Observaciones</label>
                <textarea name="observaciones" id="observaciones" rows="3" placeholder="Observaciones adicionales..."></textarea>
            </div>

            <!-- Botones -->
            <div class="buttons">
                <button type="submit" class="btn guardar">Guardar Extintor</button>
                <a href="activos.php" class="btn cancelar">Cancelar</a>
            </div>

        </form>
    </div>

    <script src="script.js"></script>
</body>
</html>