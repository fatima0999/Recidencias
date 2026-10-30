<?php

require_once __DIR__ . '/../../../../clases/conexion.php';

// Crear conexión
$conexion = new Conexion();
$bd = $conexion->conectar();


// ==============================
// OBTENER ÁREAS
// ==============================

$sqlAreas = "
    SELECT id_area, nombre_area, nivel
    FROM tb_areas
    WHERE activo = true
      AND nivel = 'Sótano'
    ORDER BY nombre_area ASC
";

$resultadoAreas = pg_query($bd, $sqlAreas);


// Las áreas ya están filtradas para mostrar únicamente las del Sótano.

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="botiquin.css">

    <title>Agregar Botiquín</title>

</head>
<body>

    <div class="overlay">

        <div class="popup scroll-popup">

            <a href="../../sotano.php" id="close-btn">&times;</a>

            <div class="form">

                <h2>Agregar botiquín</h2>


                <form
                    action="guardar_botiquin.php"
                    method="POST">


                    <!-- ============================== -->
                    <!-- 1. TIPO DE BOTIQUÍN -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="tipo_botiquin">
                            Tipo de botiquín
                        </label>

                        <select
                            id="tipo_botiquin"
                            name="tipo_botiquin"
                            required>

                            <option value="">
                                Selecciona un tipo
                            </option>

                            <option value="Básico">
                                Básico
                            </option>

                            <option value="Intermedio">
                                Intermedio
                            </option>

                            <option value="Avanzado">
                                Avanzado
                            </option>

                            <option value="Portátil">
                                Portátil
                            </option>

                        </select>

                    </div>


                    <!-- ============================== -->
                    <!-- 2. ESTADO FÍSICO -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="estado_fisico">
                            Estado físico
                        </label>

                        <select
                            id="estado_fisico"
                            name="estado_fisico"
                            required>

                            <option value="">
                                Selecciona un estado
                            </option>

                            <option value="Bueno">
                                Bueno
                            </option>

                            <option value="Regular">
                                Regular
                            </option>

                            <option value="Malo">
                                Malo
                            </option>

                        </select>

                    </div>


                    <!-- ============================== -->
                    <!-- 3. CAPACIDAD -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="capacidad">
                            Capacidad
                        </label>

                        <input
                            type="text"
                            id="capacidad"
                            name="capacidad"
                            placeholder="Ej"
                            maxlength="50"
                            required>

                    </div>


                    <!-- ============================== -->
                    <!-- 4. ÁREA DEL SÓTANO -->
                    <!-- ============================== -->

                    <div class="form-element">
                        <label for="id_area">Área del Sótano</label>
                        <select id="id_area" name="id_area" required>
                            <option value="">Selecciona un área del Sótano</option>
                            <?php if ($resultadoAreas): ?>
                                <?php while ($area = pg_fetch_assoc($resultadoAreas)): ?>
                                    <option value="<?= htmlspecialchars((string)$area['id_area'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($area['nombre_area'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>


                    <!-- ============================== -->
                    <!-- 6. FECHA DE INSTALACIÓN -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="fecha_instalacion">
                            Fecha de instalación
                        </label>

                        <input
                            type="date"
                            id="fecha_instalacion"
                            name="fecha_instalacion"
                            min="2026-01-01"
                            max="2030-12-31"
                            required>

                    </div>


                    <!-- ============================== -->
                    <!-- 7. FECHA DE ÚLTIMA REVISIÓN -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="fecha_ultima_revision">
                            Fecha de última revisión
                        </label>

                        <input
                            type="date"
                            id="fecha_ultima_revision"
                            name="fecha_ultima_revision"
                            min="2026-01-01"
                            max="2030-12-31"
                            required>

                    </div>


                    <!-- ============================== -->
                    <!-- 8. FECHA DE PRÓXIMA REVISIÓN -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="fecha_proxima_revision">
                            Fecha de próxima revisión
                        </label>

                        <input
                            type="date"
                            id="fecha_proxima_revision"
                            name="fecha_proxima_revision"
                            min="2026-01-01"
                            max="2030-12-31"
                            required>

                    </div>


                    <!-- ============================== -->
                    <!-- 9. OBSERVACIONES -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="observaciones">
                            Observaciones
                        </label>

                        <textarea
                            id="observaciones"
                            name="observaciones"
                            placeholder="Observaciones (opcional)"
                            rows="3"
                            style="resize: none; width: 100%; box-sizing: border-box;"></textarea>

                    </div>


                    <!-- ============================== -->
                    <!-- 10. MEDICAMENTOS / INSUMOS -->
                    <!-- ============================== -->

                    <hr>

                    <h3>Medicamentos / insumos</h3>

                    <div id="contenedor-medicamentos">

                        <!-- Aquí se van a agregar filas dinámicamente con JS -->

                    </div>

                    <button
                        type="button"
                        id="btn-agregar-medicamento"
                        onclick="agregarMedicamento()">
                        + Agregar medicamento
                    </button>


                    <!-- ============================== -->
                    <!-- BOTÓN GUARDAR -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <button type="submit">
                            Guardar botiquín
                        </button>

                    </div>


                </form>

            </div>

        </div>

    </div>


    <!-- ============================== -->
    <!-- JAVASCRIPT -->
    <!-- ============================== -->

    <script>

        // ======================================
        // LIMITACIÓN DE FECHAS HASTA 2030
        // ======================================

        const fechaInstalacion = document.getElementById('fecha_instalacion');
        const fechaUltima = document.getElementById('fecha_ultima_revision');
        const fechaProxima = document.getElementById('fecha_proxima_revision');
        const formulario = document.querySelector('form');
        const fechaMinima = '2026-01-01';
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
            const nuevaFecha = new Date(anio, mes - 1, dia);
            nuevaFecha.setDate(nuevaFecha.getDate() + 1);

            const nuevoAnio = nuevaFecha.getFullYear();
            const nuevoMes = String(nuevaFecha.getMonth() + 1).padStart(2, '0');
            const nuevoDia = String(nuevaFecha.getDate()).padStart(2, '0');
            return `${nuevoAnio}-${nuevoMes}-${nuevoDia}`;
        }

        function actualizarLimitesFechas() {
            const hoy = obtenerFechaHoy();
            const maximoHistorico = hoy < fechaMaxima ? hoy : fechaMaxima;

            // Instalación y última revisión no pueden ser fechas futuras.
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

        fechaInstalacion.addEventListener('change', function() {
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

        fechaUltima.addEventListener('change', function() {
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

        fechaProxima.addEventListener('change', function() {
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

        formulario.addEventListener('submit', function(evento) {
            actualizarLimitesFechas();

            if (!fechaInstalacion.value || !fechaUltima.value || !fechaProxima.value) {
                alert('Completa las tres fechas del botiquín.');
                evento.preventDefault();
                return;
            }

            const hoy = obtenerFechaHoy();
            if (
                fechaInstalacion.value < fechaMinima || fechaInstalacion.value > hoy ||
                fechaUltima.value < fechaInstalacion.value || fechaUltima.value > hoy ||
                fechaProxima.value <= fechaUltima.value || fechaProxima.value > fechaMaxima
            ) {
                alert('Revisa las fechas: deben respetar los límites establecidos hasta 2030.');
                evento.preventDefault();
                return;
            }

            // Validar fechas de caducidad de los medicamentos.
            const fechasCaducidad = document.querySelectorAll('input[name$="[fecha_caducidad]"]');
            for (const campo of fechasCaducidad) {
                if (!campo.value || campo.value < fechaMinima || campo.value > fechaMaxima) {
                    alert('La fecha de caducidad de cada medicamento debe estar entre 2026 y 2030.');
                    campo.focus();
                    evento.preventDefault();
                    return;
                }
            }
        });

        actualizarLimitesFechas();

        // ======================================
        // MEDICAMENTOS DINÁMICOS
        // ======================================

        let contadorMedicamentos = 0;

        function agregarMedicamento() {

            const contenedor =
                document.getElementById('contenedor-medicamentos');

            const indice = contadorMedicamentos;

            const fila = document.createElement('div');

            fila.classList.add('fila-medicamento');
            fila.id = 'medicamento-' + indice;

            fila.innerHTML = `
                <div class="form-element">
                    <label>Nombre del medicamento</label>
                    <input
                        type="text"
                        name="medicamentos[${indice}][nombre_medicamento]"
                        placeholder="Ej. Paracetamol"
                        maxlength="100"
                        required>
                </div>

                <div class="form-element">
                    <label>Presentación</label>
                    <input
                        type="text"
                        name="medicamentos[${indice}][presentacion]"
                        placeholder="Ej. Tabletas 500mg"
                        maxlength="50"
                        required>
                </div>

                <div class="form-element">
                    <label>Cantidad</label>
                    <input
                        type="number"
                        name="medicamentos[${indice}][cantidad]"
                        min="0"
                        required>
                </div>

                <div class="form-element">
                    <label>Cantidad mínima</label>
                    <input
                        type="number"
                        name="medicamentos[${indice}][cantidad_minima]"
                        min="0"
                        required>
                </div>

                <div class="form-element">
                    <label>Fecha de caducidad</label>
                    <input
                        type="date"
                        name="medicamentos[${indice}][fecha_caducidad]"
                        min="2026-01-01"
                        max="2030-12-31"
                        required>
                </div>

                <div class="form-element">
                    <label>Estado</label>
                    <select name="medicamentos[${indice}][estado]" required>
                        <option value="">Selecciona un estado</option>
                        <option value="Vigente">Vigente</option>
                        <option value="Próximo a caducar">Próximo a caducar</option>
                        <option value="Caducado">Caducado</option>
                    </select>
                </div>

                <hr>
            `;

            contenedor.appendChild(fila);

            contadorMedicamentos++;

        }

        function quitarMedicamento(indice) {

            const fila = document.getElementById('medicamento-' + indice);

            if (fila) {

                fila.remove();

            }

        }


        // Agregar automáticamente el primer medicamento al cargar la página
        window.onload = function() {

            agregarMedicamento();

        };

    </script>

</body>

</html>