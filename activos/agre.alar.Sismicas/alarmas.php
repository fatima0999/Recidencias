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


// ==============================
// OBTENER PROVEEDORES
// ==============================

$sqlProveedores = "
    SELECT id_proveedor, nombre_proveedor
    FROM tb_proveedores
    WHERE activo = true
    ORDER BY nombre_proveedor ASC
";

$resultadoProveedores = pg_query($bd, $sqlProveedores);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="alarmas.css">

    <title>Agregar Alarma Sísmica</title>

</head>

<body>

    <div class="overlay">

        <div class="popup scroll-popup">

            <a href="../activos.php" id="close-btn">&times;</a>

            <div class="form">

                <h2>Agregar alarma sísmica</h2>

                <form action="alarma.php" method="POST">


                    <!-- ============================== -->
                    <!-- PISO -->
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
                    <!-- ÁREA -->
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
                    <!-- TIPO DE ALARMA -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="tipo_alarma">
                            Tipo de alarma
                        </label>

                        <select
                            id="tipo_alarma"
                            name="tipo_alarma"
                            required>

                            <option value="">
                                Selecciona un tipo
                            </option>

                            <option value="Sonora">
                                Sonora
                            </option>

                            <option value="Visual">
                                Visual
                            </option>

                            <option value="Manual">
                                Manual
                            </option>

                        </select>

                    </div>


                    <!-- ============================== -->
                    <!-- MARCA -->
                    <!-- ============================== -->

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


                    <!-- ============================== -->
                    <!-- MODELO -->
                    <!-- ============================== -->

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


                    <!-- ============================== -->
                    <!-- ESTADO FÍSICO -->
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

                            <option value="Dañado">
                                Dañado
                            </option>

                        </select>

                    </div>


                    <!-- ============================== -->
                    <!-- FECHA DE INSTALACIÓN -->
                    <!-- ============================== -->

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


                    <!-- ============================== -->
                    <!-- FECHA DE ÚLTIMA REVISIÓN -->
                    <!-- ============================== -->

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


                    <!-- ============================== -->
                    <!-- FECHA DE PRÓXIMA REVISIÓN -->
                    <!-- ============================== -->

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


                    <!-- ============================== -->
                    <!-- PROVEEDOR -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="id_proveedor">
                            Proveedor
                        </label>

                        <select
                            id="id_proveedor"
                            name="id_proveedor"
                            required>

                            <option value="">
                                Selecciona un proveedor
                            </option>

                            <?php

                            if ($resultadoProveedores) {

                                while ($proveedor = pg_fetch_assoc($resultadoProveedores)) {

                                    echo '<option value="' .
                                        htmlspecialchars($proveedor['id_proveedor']) .
                                        '">' .
                                        htmlspecialchars($proveedor['nombre_proveedor']) .
                                        '</option>';
                                }

                            }

                            ?>

                        </select>

                    </div>


                    <!-- ============================== -->
                    <!-- BOTÓN GUARDAR -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <button type="submit">
                            Guardar alarma
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

            // Obtener todas las opciones de áreas
            const opciones =
                selectArea.querySelectorAll('option');

            // Reiniciar el select
            selectArea.value = '';

            // Mostrar/ocultar áreas
            opciones.forEach(function(opcion) {

                // La primera opción siempre se mantiene
                if (!opcion.dataset.nivel) {

                    opcion.style.display = 'block';

                    return;
                }

                // Comprobar si pertenece al piso seleccionado
                if (opcion.dataset.nivel === nivelSeleccionado) {

                    opcion.style.display = 'block';

                } else {

                    opcion.style.display = 'none';
                }

            });


            // Habilitar o deshabilitar el área
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

    </script>

</body>

</html>