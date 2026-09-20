<?php
require_once '../../clases/conexion.php';

// Crear conexión
$conexion = new Conexion();
$bd = $conexion->conectar();

// =====================================================
// OBTENER ÁREAS ACTIVAS
// =====================================================

$sqlAreas = "
    SELECT id_area, nombre_area, nivel
    FROM tb_areas
    WHERE activo = true
    AND nivel IS NOT NULL
    AND nivel <> ''
    ORDER BY nombre_area ASC
";

$resultadoAreas = pg_query($bd, $sqlAreas);

// =====================================================
// GUARDAR LAS ÁREAS POR NIVEL
// =====================================================

$areasPorNivel = [];

if ($resultadoAreas) {

    while ($area = pg_fetch_assoc($resultadoAreas)) {

        $nivel = $area['nivel'];

        $areasPorNivel[$nivel][] = $area;
    }
}

// =====================================================
// PISOS DISPONIBLES
// =====================================================

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

    <link rel="stylesheet" href="rut.Evacuacion.css">

    <title>Agregar Ruta de Evacuación</title>

</head>

<body>

    <div class="overlay">

        <div class="popup scroll-popup">

            <a href="../activos.php" id="close-btn">&times;</a>

            <div class="form">

                <h2>Agregar Ruta de Evacuación</h2>

                <form action="guardar_ruta_evacuacion.php" method="POST" enctype="multipart/form-data">


                    <!-- =====================================================
                         PISO DE ORIGEN
                    ====================================================== -->

                    <div class="form-element">

                        <label for="nivel_origen">
                            Piso de Origen
                        </label>

                        <select
                            id="nivel_origen"
                            name="nivel_origen"
                            required
                            onchange="mostrarAreasOrigen()"
                        >

                            <option value="">
                                Selecciona el piso de origen
                            </option>

                            <?php foreach ($ordenNiveles as $nivel): ?>

                                <?php if (isset($areasPorNivel[$nivel])): ?>

                                    <option value="<?php echo htmlspecialchars($nivel); ?>">

                                        <?php echo htmlspecialchars($nivel); ?>

                                    </option>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- =====================================================
                         ÁREA DE ORIGEN
                    ====================================================== -->

                    <div class="form-element">

                        <label for="id_area_origen">
                            Área de Origen
                        </label>

                        <select
                            id="id_area_origen"
                            name="id_area_origen"
                            required
                            disabled
                        >

                            <option value="">
                                Primero selecciona un piso
                            </option>

                            <?php foreach ($ordenNiveles as $nivel): ?>

                                <?php if (isset($areasPorNivel[$nivel])): ?>

                                    <?php foreach ($areasPorNivel[$nivel] as $area): ?>

                                        <option
                                            value="<?php echo htmlspecialchars($area['id_area']); ?>"
                                            data-nivel="<?php echo htmlspecialchars($nivel); ?>"
                                        >

                                            <?php echo htmlspecialchars($area['nombre_area']); ?>

                                        </option>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- =====================================================
                         ÁREA DE DESTINO
                    ====================================================== -->

                    <div class="form-element">

                        <label for="id_area_destino">
                            Área de Destino
                        </label>

                        <select
                            id="id_area_destino"
                            name="id_area_destino"
                            required
                        >

                            <option value="">
                                Selecciona el área de destino
                            </option>

                            <?php foreach ($ordenNiveles as $nivel): ?>

                                <?php if (isset($areasPorNivel[$nivel])): ?>

                                    <optgroup
                                        label="<?php echo htmlspecialchars($nivel); ?>"
                                    >

                                        <?php foreach ($areasPorNivel[$nivel] as $area): ?>

                                            <option
                                                value="<?php echo htmlspecialchars($area['id_area']); ?>"
                                            >

                                                <?php echo htmlspecialchars($area['nombre_area']); ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </optgroup>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- =====================================================
                         DESCRIPCIÓN DE LA RUTA
                    ====================================================== -->

                    <div class="form-element">

                        <label for="descripcion_ruta">
                            Descripción de la Ruta
                        </label>

                        <textarea
                            id="descripcion_ruta"
                            name="descripcion_ruta"
                            placeholder="Ej. Desde el taller dirigirse hacia las escaleras de emergencia"
                            required
                        ></textarea>

                    </div>


                    <!-- =====================================================
                         DISTANCIA
                    ====================================================== -->

                    <div class="form-element">

                        <label for="distancia_metros">
                            Distancia (Metros)
                        </label>

                        <input
                            type="number"
                            id="distancia_metros"
                            name="distancia_metros"
                            placeholder="Ej. 25"
                            min="1"
                            required
                        >

                    </div>


                    <!-- =====================================================
                         ESTADO
                    ====================================================== -->

                    <div class="form-element">

                        <label for="estado">
                            Estado
                        </label>

                        <select
                            id="estado"
                            name="estado"
                            required
                        >

                            <option value="">
                                Selecciona un estado
                            </option>

                            <option value="Despejada">
                                Despejada
                            </option>

                            <option value="Parcialmente Obstruida">
                                Parcialmente Obstruida
                            </option>

                            <option value="Obstruida">
                                Obstruida
                            </option>

                            <option value="En Mantenimiento">
                                En Mantenimiento
                            </option>

                        </select>

                    </div>


                    <!-- =====================================================
                         SEÑALIZACIÓN
                    ====================================================== -->

                    <div class="form-element">

                        <label for="senalizacion">
                            Estado de Señalización
                        </label>

                        <select
                            id="senalizacion"
                            name="senalizacion"
                            required
                        >

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


                    <!-- =====================================================
                         FECHA ÚLTIMA REVISIÓN
                    ====================================================== -->

                    <div class="form-element">

                        <label for="fecha_ultima_revision">
                            Fecha de Última Revisión
                        </label>

                        <input
                            type="date"
                            id="fecha_ultima_revision"
                            name="fecha_ultima_revision"
                            required
                        >

                    </div>


                    <!-- =====================================================
                         FECHA PRÓXIMA REVISIÓN
                    ====================================================== -->

                    <div class="form-element">

                        <label for="fecha_proxima_revision">
                            Fecha de Próxima Revisión
                        </label>

                        <input
                            type="date"
                            id="fecha_proxima_revision"
                            name="fecha_proxima_revision"
                            required
                        >

                    </div>


                    <!-- =====================================================
                         EVIDENCIA FOTOGRÁFICA
                    ====================================================== -->

                    <div class="form-element">

                        <label for="evidencia_foto">
                            Evidencia Fotográfica
                        </label>

                        <input
                            type="file"
                            id="evidencia_foto"
                            name="evidencia_foto"
                            accept="image/*"
                        >

                    </div>


                    <!-- =====================================================
                         OBSERVACIONES
                    ====================================================== -->

                    <div class="form-element">

                        <label for="observaciones">
                            Observaciones
                        </label>

                        <textarea
                            id="observaciones"
                            name="observaciones"
                            placeholder="Detalles adicionales sobre iluminación, señalamientos o pendientes..."
                        ></textarea>

                    </div>


                    <!-- =====================================================
                         BOTÓN GUARDAR
                    ====================================================== -->

                    <div class="form-element">

                        <button type="submit">
                            Guardar Ruta de Evacuación
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


    <!-- =====================================================
         JAVASCRIPT
         FILTRAR ÁREAS SEGÚN EL PISO
    ====================================================== -->

    <script>

        function mostrarAreasOrigen() {

            // Obtener el piso seleccionado
            const nivelSeleccionado =
                document.getElementById("nivel_origen").value;

            // Obtener el select de áreas
            const selectArea =
                document.getElementById("id_area_origen");

            // Obtener todas las opciones
            const opciones =
                selectArea.querySelectorAll("option");

            // Limpiar selección
            selectArea.value = "";

            // Si no se ha seleccionado piso
            if (nivelSeleccionado === "") {

                selectArea.disabled = true;

                opciones.forEach(function(opcion) {

                    if (opcion.value !== "") {

                        opcion.style.display = "none";

                    }

                });

                return;
            }

            // Activar el selector de áreas
            selectArea.disabled = false;

            // Revisar cada área
            opciones.forEach(function(opcion) {

                // La primera opción siempre permanece
                if (opcion.value === "") {

                    opcion.style.display = "block";

                    return;
                }

                // Obtener el piso del área
                const nivelArea =
                    opcion.getAttribute("data-nivel");

                // Mostrar únicamente las áreas
                // pertenecientes al piso seleccionado
                if (nivelArea === nivelSeleccionado) {

                    opcion.style.display = "block";

                } else {

                    opcion.style.display = "none";

                }

            });

            // Cambiar el texto de la primera opción
            opciones[0].textContent =
                "Selecciona el área de origen";

        }

    </script>

</body>

</html>

