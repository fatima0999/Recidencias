<?php

require_once '../../clases/conexion.php';

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
    AND nivel IS NOT NULL
    AND nivel <> ''
    ORDER BY nombre_area ASC
";

$resultadoAreas = pg_query($bd, $sqlAreas);


// ==============================
// ORGANIZAR ÁREAS POR PISO
// ==============================

$areasPorNivel = [];

if ($resultadoAreas) {

    while ($area = pg_fetch_assoc($resultadoAreas)) {

        $nivel = $area['nivel'];

        $areasPorNivel[$nivel][] = $area;
    }
}


// ==============================
// ORDEN DE LOS PISOS
// ==============================

$ordenNiveles = [
    'Sótano',
    'Planta Baja',
    'Primer Nivel',
    'Segundo Nivel',
    'Tercer Nivel',
    'Azotea'
];

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

            <a href="../activos.php" id="close-btn">&times;</a>

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
                            placeholder="Ej. 20 personas"
                            maxlength="50"
                            required>

                    </div>


                    <!-- ============================== -->
                    <!-- 4. PISO -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="nivel_origen">
                            Piso
                        </label>

                        <select
                            id="nivel_origen"
                            name="nivel_origen"
                            required
                            onchange="mostrarAreas()">

                            <option value="">
                                Selecciona un piso
                            </option>

                            <?php

                            foreach ($ordenNiveles as $nivel) {

                                if (isset($areasPorNivel[$nivel])) {

                                    echo '<option value="' .
                                        htmlspecialchars($nivel) .
                                        '">' .
                                        htmlspecialchars($nivel) .
                                        '</option>';
                                }
                            }

                            ?>

                        </select>

                    </div>


                    <!-- ============================== -->
                    <!-- 5. ÁREA -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="id_area">
                            Área
                        </label>

                        <select
                            id="id_area"
                            name="id_area"
                            required
                            disabled>

                            <option value="">
                                Primero selecciona un piso
                            </option>

                            <?php

                            foreach ($areasPorNivel as $nivel => $areas) {

                                foreach ($areas as $area) {

                                    echo '<option
                                            value="' . htmlspecialchars($area['id_area']) . '"
                                            data-nivel="' . htmlspecialchars($nivel) . '"
                                            style="display:none;">' .
                                            htmlspecialchars($area['nombre_area']) .
                                            '</option>';
                                }
                            }

                            ?>

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
                            rows="3"></textarea>

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

        function mostrarAreas() {

            // Obtener el piso seleccionado
            const nivelSeleccionado =
                document.getElementById('nivel_origen').value;

            // Obtener el select de áreas
            const selectArea =
                document.getElementById('id_area');

            // Obtener todas las opciones
            const opciones =
                selectArea.querySelectorAll('option');


            // Reiniciar el área seleccionada
            selectArea.value = '';


            // Recorrer todas las áreas
            opciones.forEach(function(opcion) {

                // La primera opción no tiene data-nivel
                if (!opcion.dataset.nivel) {

                    opcion.style.display = 'block';

                    return;
                }


                // Mostrar solamente las áreas
                // correspondientes al piso seleccionado
                if (opcion.dataset.nivel === nivelSeleccionado) {

                    opcion.style.display = 'block';

                } else {

                    opcion.style.display = 'none';

                }

            });


            // Habilitar el campo Área
            if (nivelSeleccionado !== '') {

                selectArea.disabled = false;

                selectArea.options[0].text =
                    'Selecciona un área';

            } else {

                selectArea.disabled = true;

                selectArea.options[0].text =
                    'Primero selecciona un piso';

            }

        }


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
                        required>
                </div>

                <div class="form-element">
                    <label>Estado</label>
                    <select name="medicamentos[${indice}][estado]" required>
                        <option value="">Selecciona un estado</option>
                        <option value="Disponible">Disponible</option>
                        <option value="Bajo stock">Bajo stock</option>
                        <option value="Caducado">Caducado</option>
                    </select>
                </div>

                <button
                    type="button"
                    class="btn-quitar-medicamento"
                    onclick="quitarMedicamento(${indice})">
                    Quitar
                </button>

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