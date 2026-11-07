<?php
/* ==========================================================
   AGREGAR LUZ DE EMERGENCIA (PLANTA BAJA)
   Ruta: recidencias/activos/areas.php/seguimientoplantabaja/luces/lucesemergencia.php
   ========================================================== */

require_once __DIR__ . '/../../../../clases/conexion.php';

$conexion = new Conexion();
$bd = $conexion->conectar();


/* ==========================================================
   OBTENER ÁREAS DE PLANTA BAJA
   ========================================================== */

$sqlAreas = "
    SELECT id_area, nombre_area
    FROM public.tb_areas
    WHERE activo = TRUE
      AND nivel = 'Planta Baja'
    ORDER BY nombre_area ASC
";

$resultadoAreas = pg_query($bd, $sqlAreas);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Mismo estilo del popup rosa de botiquines -->
    <link rel="stylesheet" href="../botiquines/botiquin.css">

    <title>Agregar luz de emergencia - Planta Baja</title>

</head>

<body>

    <div class="overlay">

        <div class="popup scroll-popup">

            <a href="../lucesdeemergencia.php" id="close-btn">&times;</a>

            <div class="form">

                <h2>Agregar luz de emergencia</h2>


                <form action="guardar_luz.php" method="POST" id="formLuz">


                    <!-- ============================== -->
                    <!-- 1. ÁREA DE PLANTA BAJA -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="id_area">Área de Planta Baja</label>

                        <select id="id_area" name="id_area" required>

                            <option value="">Selecciona un área de Planta Baja</option>

                            <?php if ($resultadoAreas): ?>
                                <?php while ($area = pg_fetch_assoc($resultadoAreas)): ?>
                                    <option value="<?= (int) $area['id_area'] ?>">
                                        <?= htmlspecialchars($area['nombre_area'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>

                        </select>

                    </div>


                    <!-- ============================== -->
                    <!-- 1.1 UBICACIÓN DETALLADA -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="ubicacion_detalle">Ubicación detallada</label>

                        <input
                            type="text"
                            id="ubicacion_detalle"
                            name="ubicacion_detalle"
                            maxlength="255"
                            placeholder="Ej. Junto a la puerta de salida (opcional)">

                    </div>


                    <!-- ============================== -->
                    <!-- 2. TIPO DE ILUMINACIÓN -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="tipo_iluminacion">Tipo de iluminación</label>

                        <select id="tipo_iluminacion" name="tipo_iluminacion" required>

                            <option value="">Selecciona un tipo</option>
                            <option value="LED">LED</option>
                            <option value="Fluorescente">Fluorescente</option>
                            <option value="Halógena">Halógena</option>
                            <option value="Otro">Otro</option>

                        </select>

                    </div>


                    <!-- ============================== -->
                    <!-- 3. MARCA -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="marca">Marca</label>

                        <input
                            type="text"
                            id="marca"
                            name="marca"
                            maxlength="100"
                            placeholder="Ej. Philips">

                    </div>


                    <!-- ============================== -->
                    <!-- 4. MODELO -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="modelo">Modelo</label>

                        <input
                            type="text"
                            id="modelo"
                            name="modelo"
                            maxlength="100"
                            placeholder="Ej. LE-200">

                    </div>


                    <!-- ============================== -->
                    <!-- 5. DURACIÓN DE BATERÍA -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="duracion_bateria">Duración de batería</label>

                        <input
                            type="text"
                            id="duracion_bateria"
                            name="duracion_bateria"
                            maxlength="50"
                            placeholder="Ej. 2 horas"
                            required>

                    </div>


                    <!-- ============================== -->
                    <!-- 6. ESTADO FÍSICO -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="estado_fisico">Estado físico</label>

                        <select id="estado_fisico" name="estado_fisico" required>

                            <option value="">Selecciona un estado</option>
                            <option value="Bueno">Bueno</option>
                            <option value="Regular">Regular</option>
                            <option value="Malo">Malo</option>

                        </select>

                    </div>


                    <!-- ============================== -->
                    <!-- 7. FECHA DE INSTALACIÓN -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="fecha_instalacion">Fecha de instalación</label>

                        <input
                            type="date"
                            id="fecha_instalacion"
                            name="fecha_instalacion"
                            min="2000-01-01"
                            max="2030-12-31"
                            required>

                    </div>


                    <!-- ============================== -->
                    <!-- 8. FECHA DE ÚLTIMA REVISIÓN -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="fecha_ultima_revision">Fecha de última revisión</label>

                        <input
                            type="date"
                            id="fecha_ultima_revision"
                            name="fecha_ultima_revision"
                            min="2000-01-01"
                            max="2030-12-31"
                            required>

                    </div>


                    <!-- ============================== -->
                    <!-- 9. FECHA DE PRÓXIMA REVISIÓN -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="fecha_proxima_revision">Fecha de próxima revisión</label>

                        <input
                            type="date"
                            id="fecha_proxima_revision"
                            name="fecha_proxima_revision"
                            min="2000-01-01"
                            max="2030-12-31"
                            required>

                    </div>


                    <!-- ============================== -->
                    <!-- 10. OBSERVACIONES -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="observaciones">Observaciones</label>

                        <textarea
                            id="observaciones"
                            name="observaciones"
                            placeholder="Observaciones (opcional)"
                            rows="3"
                            style="resize: none; width: 100%; box-sizing: border-box;"></textarea>

                    </div>


                    <!-- ============================== -->
                    <!-- BOTÓN GUARDAR -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <button type="submit">Guardar luz de emergencia</button>

                    </div>


                </form>

            </div>

        </div>

    </div>


    <script>

        // ======================================
        // VALIDACIÓN DE FECHAS
        // ======================================

        const fechaInstalacion = document.getElementById('fecha_instalacion');
        const fechaUltima = document.getElementById('fecha_ultima_revision');
        const fechaProxima = document.getElementById('fecha_proxima_revision');
        const formulario = document.getElementById('formLuz');

        const fechaMinima = '2000-01-01';
        const fechaMaxima = '2030-12-31';

        function obtenerFechaHoy() {
            const hoy = new Date();
            const anio = hoy.getFullYear();
            const mes = String(hoy.getMonth() + 1).padStart(2, '0');
            const dia = String(hoy.getDate()).padStart(2, '0');
            return `${anio}-${mes}-${dia}`;
        }

        function sumarUnDia(fecha) {
            const [anio, mes, dia] = fecha.split('-').map(Number);
            const nueva = new Date(anio, mes - 1, dia);
            nueva.setDate(nueva.getDate() + 1);

            return nueva.getFullYear() + '-' +
                String(nueva.getMonth() + 1).padStart(2, '0') + '-' +
                String(nueva.getDate()).padStart(2, '0');
        }

        function actualizarLimitesFechas() {
            const hoy = obtenerFechaHoy();
            const maximoHistorico = hoy < fechaMaxima ? hoy : fechaMaxima;

            // Instalación y última revisión no pueden ser futuras.
            fechaInstalacion.min = fechaMinima;
            fechaInstalacion.max = maximoHistorico;
            fechaUltima.min = fechaInstalacion.value || fechaMinima;
            fechaUltima.max = maximoHistorico;

            // Próxima revisión posterior a la última, sin superar 2030.
            fechaProxima.min = fechaUltima.value
                ? sumarUnDia(fechaUltima.value)
                : fechaMinima;
            fechaProxima.max = fechaMaxima;
        }

        fechaInstalacion.addEventListener('change', function () {

            if (fechaInstalacion.value > obtenerFechaHoy()) {
                alert('La fecha de instalación no puede ser futura.');
                fechaInstalacion.value = '';
            }

            if (fechaUltima.value && fechaUltima.value < fechaInstalacion.value) {
                fechaUltima.value = '';
                fechaProxima.value = '';
            }

            actualizarLimitesFechas();
        });

        fechaUltima.addEventListener('change', function () {

            if (fechaUltima.value > obtenerFechaHoy()) {
                alert('La fecha de última revisión no puede ser futura.');
                fechaUltima.value = '';
                fechaProxima.value = '';

            } else if (
                fechaInstalacion.value &&
                fechaUltima.value &&
                fechaUltima.value < fechaInstalacion.value
            ) {
                alert('La última revisión no puede ser anterior a la instalación.');
                fechaUltima.value = '';
                fechaProxima.value = '';

            } else if (
                fechaUltima.value &&
                fechaProxima.value &&
                fechaProxima.value <= fechaUltima.value
            ) {
                fechaProxima.value = '';
            }

            actualizarLimitesFechas();
        });

        fechaProxima.addEventListener('change', function () {

            if (fechaProxima.value > fechaMaxima) {
                alert('La próxima revisión no puede superar el 31 de diciembre de 2030.');
                fechaProxima.value = '';

            } else if (
                fechaUltima.value &&
                fechaProxima.value &&
                fechaProxima.value <= fechaUltima.value
            ) {
                alert('La próxima revisión debe ser posterior a la última revisión.');
                fechaProxima.value = '';
            }
        });

        formulario.addEventListener('submit', function (evento) {

            actualizarLimitesFechas();

            if (!fechaInstalacion.value || !fechaUltima.value || !fechaProxima.value) {
                alert('Completa las tres fechas de la luz de emergencia.');
                evento.preventDefault();
                return;
            }

            const hoy = obtenerFechaHoy();

            if (
                fechaInstalacion.value < fechaMinima || fechaInstalacion.value > hoy ||
                fechaUltima.value < fechaInstalacion.value || fechaUltima.value > hoy ||
                fechaProxima.value <= fechaUltima.value || fechaProxima.value > fechaMaxima
            ) {
                alert('Revisa las fechas: deben respetar los límites establecidos.');
                evento.preventDefault();
            }
        });

        actualizarLimitesFechas();

    </script>

</body>

</html>