<?php

require_once '../../clases/conexion.php';

// Crear conexión
$conexion = new Conexion();
$bd = $conexion->conectar();


// ==========================================
// OBTENER ÁREAS ACTIVAS CON SU NIVEL
// ==========================================

$sqlAreas = "
    SELECT id_area, nombre_area, nivel
    FROM tb_areas
    WHERE activo = true
    AND nivel IS NOT NULL
    AND nivel <> ''
    ORDER BY nombre_area ASC
";

$resultadoAreas = pg_query($bd, $sqlAreas);


// ==========================================
// AGRUPAR LAS ÁREAS POR PISO
// ==========================================

$areasPorNivel = [];

if ($resultadoAreas) {

    while ($area = pg_fetch_assoc($resultadoAreas)) {

        $nivel = $area['nivel'];

        $areasPorNivel[$nivel][] = $area;
    }
}


// ==========================================
// ORDEN DE LOS PISOS
// ==========================================

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

    <link rel="stylesheet" href="sen.Humo.css">

    <title>Agregar Detector de Humo</title>

</head>

<body>

    <div class="overlay">

        <div class="popup scroll-popup">

            <!-- BOTÓN CERRAR -->
            <a href="../activos.php" id="close-btn">&times;</a>

            <div class="form">

                <h2>Agregar Detector de Humo</h2>


                <form
                    action="guardar_detector_humo.php"
                    method="POST"
                    enctype="multipart/form-data">


                    <!-- ================================= -->
                    <!-- PISO / NIVEL -->
                    <!-- ================================= -->

                    <div class="form-element">

                        <label for="nivel_origen">
                            Piso / Nivel
                        </label>

                        <select
                            id="nivel_origen"
                            name="nivel_origen"
                            required
                            onchange="mostrarAreas()">

                            <option value="">
                                Selecciona un piso
                            </option>

                            <?php foreach ($ordenNiveles as $nivel): ?>

                                <?php if (isset($areasPorNivel[$nivel])): ?>

                                    <option value="<?= htmlspecialchars($nivel) ?>">

                                        <?= htmlspecialchars($nivel) ?>

                                    </option>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </select>

                    </div>



                    <!-- ================================= -->
                    <!-- ÁREA -->
                    <!-- ================================= -->

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


                            <?php foreach ($areasPorNivel as $nivel => $areas): ?>

                                <?php foreach ($areas as $area): ?>

                                    <option
                                        value="<?= htmlspecialchars($area['id_area']) ?>"
                                        data-nivel="<?= htmlspecialchars($nivel) ?>"
                                        style="display:none;">

                                        <?= htmlspecialchars($area['nombre_area']) ?>

                                    </option>

                                <?php endforeach; ?>

                            <?php endforeach; ?>

                        </select>

                    </div>



                    <!-- ================================= -->
                    <!-- TIPO DE DETECTOR -->
                    <!-- ================================= -->

                    <div class="form-element">

                        <label for="tipo_detector">
                            Tipo de Detector
                        </label>

                        <select
                            id="tipo_detector"
                            name="tipo_detector"
                            required>

                            <option value="">
                                Selecciona un tipo
                            </option>

                            <option value="Fotoeléctrico">
                                Fotoeléctrico
                            </option>

                            <option value="Ionización">
                                Ionización
                            </option>

                            <option value="Térmico">
                                Térmico
                            </option>

                            <option value="Combinado">
                                Combinado
                            </option>

                        </select>

                    </div>



                    <!-- ================================= -->
                    <!-- MARCA -->
                    <!-- ================================= -->

                    <div class="form-element">

                        <label for="marca">
                            Marca
                        </label>

                        <input
                            type="text"
                            id="marca"
                            name="marca"
                            placeholder="Ingrese la marca"
                            maxlength="50"
                            required>

                    </div>



                    <!-- ================================= -->
                    <!-- MODELO -->
                    <!-- ================================= -->

                    <div class="form-element">

                        <label for="modelo">
                            Modelo
                        </label>

                        <input
                            type="text"
                            id="modelo"
                            name="modelo"
                            placeholder="Ingrese el modelo"
                            maxlength="50"
                            required>

                    </div>



                    <!-- ================================= -->
                    <!-- NÚMERO DE SERIE -->
                    <!-- ================================= -->

                    <div class="form-element">

                        <label for="numero_serie">
                            Número de Serie
                        </label>

                        <input
                            type="text"
                            id="numero_serie"
                            name="numero_serie"
                            placeholder="Ingrese el número de serie"
                            maxlength="50"
                            required>

                    </div>



                    <!-- ================================= -->
                    <!-- ESTADO FÍSICO -->
                    <!-- ================================= -->

                    <div class="form-element">

                        <label for="estado_fisico">
                            Estado Físico
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

                            <option value="Dañado">
                                Dañado
                            </option>

                        </select>

                    </div>



                    <!-- ================================= -->
                    <!-- FECHA DE INSTALACIÓN -->
                    <!-- ================================= -->

                    <div class="form-element">

                        <label for="fecha_instalacion">
                            Fecha de Instalación
                        </label>

                        <input
                            type="date"
                            id="fecha_instalacion"
                            name="fecha_instalacion"
                            required>

                    </div>



                    <!-- ================================= -->
                    <!-- FECHA DE ÚLTIMA REVISIÓN -->
                    <!-- ================================= -->

                    <div class="form-element">

                        <label for="fecha_ultima_revision">
                            Fecha de Última Revisión
                        </label>

                        <input
                            type="date"
                            id="fecha_ultima_revision"
                            name="fecha_ultima_revision"
                            required>

                    </div>



                    <!-- ================================= -->
                    <!-- FECHA DE PRÓXIMA REVISIÓN -->
                    <!-- ================================= -->

                    <div class="form-element">

                        <label for="fecha_proxima_revision">
                            Fecha de Próxima Revisión
                        </label>

                        <input
                            type="date"
                            id="fecha_proxima_revision"
                            name="fecha_proxima_revision"
                            required>

                    </div>



                    <!-- ================================= -->
                    <!-- BOTÓN GUARDAR -->
                    <!-- ================================= -->

                    <div class="form-element">

                        <button type="submit">
                            Guardar Detector de Humo
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>



    <!-- ========================================= -->
    <!-- JAVASCRIPT PARA FILTRAR LAS ÁREAS -->
    <!-- ========================================= -->

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

                // La primera opción no tiene nivel
                if (!opcion.dataset.nivel) {

                    opcion.style.display = 'block';

                    return;
                }


                // Mostrar solamente las áreas
                // que pertenecen al piso seleccionado
                if (opcion.dataset.nivel === nivelSeleccionado) {

                    opcion.style.display = 'block';

                } else {

                    opcion.style.display = 'none';

                }

            });


            // Si seleccionó un piso
            if (nivelSeleccionado !== '') {

                selectArea.disabled = false;

                selectArea.options[0].text =
                    'Selecciona un área';

            } else {

                // Si no seleccionó piso
                selectArea.disabled = true;

                selectArea.options[0].text =
                    'Primero selecciona un piso';

            }

        }

    </script>

</body>

</html>