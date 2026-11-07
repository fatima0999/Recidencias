<?php
/* ==========================================================
   UBICACIÓN: activos/areas.php/seguimientoplantabaja/EXTINTORES/alta_extintor.php
   FORMULARIO DE ALTA - EXTINTORES (PLANTA BAJA)
   ========================================================== */

require_once __DIR__ . '/../../../../clases/conexion.php';

$conexion = new Conexion();
$bd = $conexion->conectar();

$resultadoAreas = pg_query_params(
    $bd,
    "SELECT id_area, nombre_area
     FROM tb_areas
     WHERE activo = true
       AND nivel = $1
     ORDER BY nombre_area ASC",
    ['Planta Baja']
);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Mismo CSS que usan los formularios del Sótano -->
    <link rel="stylesheet" href="../../seguimientoSotano.php/botiquines/botiquin.css">
    <title>Agregar Extintor</title>
</head>

<body>

    <div class="overlay">
        <div class="popup scroll-popup">
            <a href="../extintoresplantabaja.php" id="close-btn">&times;</a>
            <div class="form">
                <h2>Agregar Extintor</h2>

                <form action="guardar_extintor.php" method="POST">

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
                        <label for="tipo_agente">Tipo de Agente</label>
                        <select id="tipo_agente" name="tipo_agente" required>
                            <option value="">Selecciona un agente</option>
                            <option value="PQS ABC">PQS ABC (polvo químico seco)</option>
                            <option value="PQS BC">PQS BC (polvo químico seco)</option>
                            <option value="CO2">CO2 (bióxido de carbono)</option>
                            <option value="Agua">Agua</option>
                            <option value="Espuma">Espuma</option>
                            <option value="Acetato de potasio">Acetato de potasio (cocinas)</option>
                        </select>
                    </div>

                    <div class="form-element">
                        <label for="capacidad">Capacidad (kg)</label>
                        <input type="number" id="capacidad" name="capacidad" min="0.1" max="100" step="0.1"
                            placeholder="Ej. 4.5" required>
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
                        <label for="estado_fisico">Estado Físico</label>
                        <select id="estado_fisico" name="estado_fisico" required>
                            <option value="">Selecciona un estado</option>
                            <option value="Bueno">Bueno</option>
                            <option value="Regular">Regular</option>
                            <option value="Malo">Malo</option>
                        </select>
                    </div>

                    <div class="form-element">
                        <label for="peso_actual">Peso actual en kg (opcional)</label>
                        <input type="number" id="peso_actual" name="peso_actual" min="0" max="200" step="0.01"
                            placeholder="Ej. 6.2">
                    </div>

                    <div class="form-element">
                        <label for="fecha_ultimo_mantenimiento">Último Mantenimiento</label>
                        <input type="date" id="fecha_ultimo_mantenimiento" name="fecha_ultimo_mantenimiento"
                            max="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-element">
                        <label for="fecha_proximo_mantenimiento">Próximo Mantenimiento</label>
                        <input type="date" id="fecha_proximo_mantenimiento" name="fecha_proximo_mantenimiento"
                            max="2030-12-31" required>
                    </div>

                    <div class="form-element">
                        <label for="fecha_recarga">Última Recarga</label>
                        <input type="date" id="fecha_recarga" name="fecha_recarga"
                            max="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-element">
                        <label for="fecha_proxima_recarga">Próxima Recarga</label>
                        <input type="date" id="fecha_proxima_recarga" name="fecha_proxima_recarga"
                            max="2030-12-31" required>
                    </div>

                    <div class="form-element">
                        <label for="observaciones">Observaciones (opcional)</label>
                        <textarea id="observaciones" name="observaciones" rows="3" maxlength="500"></textarea>
                    </div>

                    <div class="form-element">
                        <button type="submit">Guardar Extintor</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script>
        /* Cada "próxima" fecha debe ser posterior a su fecha anterior */
        function ligar(idAnterior, idProxima) {
            const anterior = document.getElementById(idAnterior);
            const proxima  = document.getElementById(idProxima);

            anterior.addEventListener('change', function () {
                if (!anterior.value) return;
                const d = new Date(anterior.value + 'T00:00:00');
                d.setDate(d.getDate() + 1);
                proxima.min = d.toLocaleDateString('en-CA'); // YYYY-MM-DD
            });
        }

        ligar('fecha_ultimo_mantenimiento', 'fecha_proximo_mantenimiento');
        ligar('fecha_recarga', 'fecha_proxima_recarga');
    </script>

</body>

</html>