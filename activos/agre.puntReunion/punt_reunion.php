<?php
require_once '../../clases/conexion.php';

// Crear conexión
$conexion = new Conexion();
$bd = $conexion->conectar();


// ==========================================
// OBTENER LAS ÁREAS ACTIVAS ORGANIZADAS
// POR PISO / NIVEL
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
// GUARDAR LAS ÁREAS POR NIVEL
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

    <link rel="stylesheet" href="./punt_reunion.css">

    <title>Agregar Punto de Reunión</title>

</head>

<body>

    <div class="overlay">

        <div class="popup scroll-popup">

            <!-- BOTÓN CERRAR -->
            <a href="../activos.php" id="close-btn">&times;</a>

            <div class="form">

                <h2>Agregar Punto de Reunión</h2>


                <form action="guardar_punto_reunion.php"
                      method="POST"
                      enctype="multipart/form-data">


                    <!-- ===================================== -->
                    <!-- PISO / NIVEL -->
                    <!-- ===================================== -->

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



                    <!-- ===================================== -->
                    <!-- ÁREA -->
                    <!-- ===================================== -->

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
                                        value="<?= $area['id_area'] ?>"
                                        data-nivel="<?= htmlspecialchars($nivel) ?>"
                                        style="display:none;">

                                        <?= htmlspecialchars($area['nombre_area']) ?>

                                    </option>

                                <?php endforeach; ?>

                            <?php endforeach; ?>

                        </select>

                    </div>



                    <!-- ===================================== -->
                    <!-- NOMBRE DEL PUNTO -->
                    <!-- ===================================== -->

                    <div class="form-element">

                        <label for="nombre_punto">
                            Nombre del Punto
                        </label>

                        <input
                            type="text"
                            id="nombre_punto"
                            name="nombre_punto"
                            placeholder="Ej. Punto de Reunión 1"
                            maxlength="100"
                            required>

                    </div>



                    <!-- ===================================== -->
                    <!-- UBICACIÓN -->
                    <!-- ===================================== -->

                    <div class="form-element">

                        <label for="ubicacion">
                            Ubicación
                        </label>

                        <input
                            type="text"
                            id="ubicacion"
                            name="ubicacion"
                            placeholder="Ej. Patio central junto al acceso principal"
                            maxlength="200"
                            required>

                    </div>



                    <!-- ===================================== -->
                    <!-- COORDENADAS GPS -->
                    <!-- ===================================== -->

                    <div class="form-element">

                        <label for="coordenadas_gps">
                            Coordenadas GPS
                        </label>

                        <input
                            type="text"
                            id="coordenadas_gps"
                            name="coordenadas_gps"
                            placeholder="Ej. 19.3512, -99.0623"
                            maxlength="50">

                    </div>



                    <!-- ===================================== -->
                    <!-- ESTADO -->
                    <!-- ===================================== -->

                    <div class="form-element">

                        <label for="estado">
                            Estado
                        </label>

                        <select
                            id="estado"
                            name="estado"
                            required>

                            <option value="">
                                Selecciona un estado
                            </option>

                            <option value="Disponible">
                                Disponible
                            </option>

                            <option value="En Mantenimiento">
                                En Mantenimiento
                            </option>

                            <option value="Obstruido">
                                Obstruido
                            </option>

                            <option value="No Disponible">
                                No Disponible
                            </option>

                        </select>

                    </div>



                    <!-- ===================================== -->
                    <!-- SEÑALIZACIÓN -->
                    <!-- ===================================== -->

                    <div class="form-element">

                        <label for="senalizacion">
                            Estado de Señalización
                        </label>

                        <select
                            id="senalizacion"
                            name="senalizacion"
                            required>

                            <option value="">
                                Selecciona una opción
                            </option>

                            <option value="Buena">
                                Buena
                            </option>

                            <option value="Regular">
                                Regular
                            </option>

                            <option value="Mala">
                                Mala
                            </option>

                            <option value="Ausente">
                                Ausente
                            </option>

                        </select>

                    </div>



                    <!-- ===================================== -->
                    <!-- FECHA ÚLTIMA REVISIÓN -->
                    <!-- ===================================== -->

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



                    <!-- ===================================== -->
                    <!-- FECHA PRÓXIMA REVISIÓN -->
                    <!-- ===================================== -->

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



                    <!-- ===================================== -->
                    <!-- EVIDENCIA FOTOGRÁFICA -->
                    <!-- ===================================== -->

                    <div class="form-element">

                        <label for="evidencia_foto">
                            Evidencia Fotográfica
                        </label>

                        <input
                            type="file"
                            id="evidencia_foto"
                            name="evidencia_foto"
                            accept="image/*">

                    </div>



                    <!-- ===================================== -->
                    <!-- BOTÓN GUARDAR -->
                    <!-- ===================================== -->

                    <div class="form-element">

                        <button type="submit">
                            Guardar Punto de Reunión
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


            // Reiniciar el valor seleccionado
            selectArea.value = '';


            // Recorrer todas las áreas
            opciones.forEach(function(opcion) {

                // La primera opción no tiene piso
                if (!opcion.dataset.nivel) {

                    opcion.style.display = 'block';

                    return;
                }


                // Mostrar únicamente las áreas
                // que pertenecen al piso seleccionado
                if (opcion.dataset.nivel === nivelSeleccionado) {

                    opcion.style.display = 'block';

                } else {

                    opcion.style.display = 'none';

                }

            });


            // Si ya seleccionó un piso
            if (nivelSeleccionado !== '') {

                selectArea.disabled = false;

                selectArea.options[0].text =
                    'Selecciona un área';

            } else {

                // Si todavía no selecciona piso
                selectArea.disabled = true;

                selectArea.options[0].text =
                    'Primero selecciona un piso';

            }

        }

    </script>

</body>

</html>