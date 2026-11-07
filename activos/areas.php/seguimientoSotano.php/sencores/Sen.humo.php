<?php
/* ==========================================================
   UBICACIÓN: activos/areas.php/seguimientoSotano.php/sencores/Sen.humo.php
   FORMULARIO DE ALTA - DETECTORES DE HUMO
   ========================================================== */

require_once __DIR__ . '/../../../../clases/conexion.php';

$conexion = new Conexion();
$bd = $conexion->conectar();

$sqlAreas = "SELECT id_area, nombre_area
             FROM tb_areas
             WHERE activo = true
               AND nivel = 'Sótano'
             ORDER BY nombre_area ASC";

$resultadoAreas = pg_query($bd, $sqlAreas);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../botiquines/botiquin.css">
    <title>Agregar Detector de Humo</title>
</head>

<body>

    <div class="overlay">
        <div class="popup scroll-popup">
            <a href="../DectectoresdeHumo.php" id="close-btn">&times;</a>
            <div class="form">
                <h2>Agregar Detector de Humo</h2>

                <form action="guardar_detector.php" method="POST">

                    <div class="form-element">
                        <label for="id_area">Área</label>
                        <select id="id_area" name="id_area" required>
                            <option value="">Selecciona un área</option>
                            <?php
                            if ($resultadoAreas) {
                                while ($area = pg_fetch_assoc($resultadoAreas)) {
                                    echo '<option value="' . (int) $area['id_area'] . '">'
                                        . htmlspecialchars($area['nombre_area'], ENT_QUOTES, 'UTF-8')
                                        . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-element">
                        <label for="tipo_detector">Tipo de Detector</label>
                        <select id="tipo_detector" name="tipo_detector" required>
                            <option value="">Selecciona un tipo</option>
                            <option value="Fotoeléctrico">Fotoeléctrico</option>
                            <option value="Ionización">Ionización</option>
                            <option value="Térmico">Térmico</option>
                            <option value="Combinado">Combinado</option>
                        </select>
                    </div>

                    <div class="form-element">
                        <label for="marca">Marca</label>
                        <input type="text" id="marca" name="marca" placeholder="Ingrese la marca" maxlength="50" required>
                    </div>

                    <div class="form-element">
                        <label for="modelo">Modelo</label>
                        <input type="text" id="modelo" name="modelo" placeholder="Ingrese el modelo" maxlength="50" required>
                    </div>

                    <div class="form-element">
                        <label for="numero_serie">Número de Serie</label>
                        <input type="text" id="numero_serie" name="numero_serie" placeholder="Ingrese el número de serie" maxlength="50" required>
                    </div>

                    <div class="form-element">
                        <label for="estado_fisico">Estado Físico</label>
                        <select id="estado_fisico" name="estado_fisico" required>
                            <option value="">Selecciona un estado</option>
                            <option value="Bueno">Bueno</option>
                            <option value="Regular">Regular</option>
                            <option value="Malo">Malo</option>
                            <option value="Dañado">Dañado</option>
                        </select>
                    </div>

                    <div class="form-element">
                        <label for="fecha_instalacion">Fecha de Instalación</label>
                        <input type="date" id="fecha_instalacion" name="fecha_instalacion" max="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-element">
                        <label for="fecha_ultima_revision">Fecha de Última Revisión</label>
                        <input type="date" id="fecha_ultima_revision" name="fecha_ultima_revision" max="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-element">
                        <label for="fecha_proxima_revision">Fecha de Próxima Revisión</label>
                        <input type="date" id="fecha_proxima_revision" name="fecha_proxima_revision" required>
                    </div>

                    <div class="form-element">
                        <label for="observaciones">Observaciones (opcional)</label>
                        <textarea id="observaciones" name="observaciones" rows="3" maxlength="500"></textarea>
                    </div>

                    <div class="form-element">
                        <button type="submit">Guardar Detector de Humo</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script>
        /* La próxima revisión debe ser posterior a la última */
        const ultima  = document.getElementById('fecha_ultima_revision');
        const proxima = document.getElementById('fecha_proxima_revision');

        ultima.addEventListener('change', function () {
            if (!ultima.value) return;
            const d = new Date(ultima.value + 'T00:00:00');
            d.setDate(d.getDate() + 1);
            proxima.min = d.toLocaleDateString('en-CA'); // YYYY-MM-DD
        });
    </script>

</body>

</html>