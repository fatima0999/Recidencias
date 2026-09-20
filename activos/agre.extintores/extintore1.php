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

    <link rel="stylesheet" href="extintores1.css">

    <title>Agregar Extintor</title>

</head>

<body>

    <div class="overlay">

        <div class="popup scroll-popup">

            <a href="../activos.php" id="close-btn">&times;</a>

            <div class="form">

                <h2>Agregar extintor</h2>


                <form
                    action="guardar_extintor.php"
                    method="POST">


                    <!-- ============================== -->
                    <!-- 1. FOLIO -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="numero_folio">
                            Folio
                        </label>

                        <input
                            type="text"
                            id="numero_folio"
                            name="numero_folio"
                            placeholder="Ingrese el folio"
                            maxlength="30"
                            required>

                    </div>


                    <!-- ============================== -->
                    <!-- 2. TIPO DE AGENTE -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="tipo_agente">
                            Tipo de agente
                        </label>

                        <select
                            id="tipo_agente"
                            name="tipo_agente"
                            required>

                            <option value="">
                                Selecciona un tipo
                            </option>

                            <option value="Polvo Químico Seco">
                                Polvo Químico Seco
                            </option>

                            <option value="Agua">
                                Agua
                            </option>

                            <option value="Dióxido de Carbono">
                                Dióxido de Carbono
                            </option>

                            <option value="Espuma">
                                Espuma
                            </option>

                            <option value="Agente Limpio">
                                Agente Limpio
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
                            type="number"
                            id="capacidad"
                            name="capacidad"
                            placeholder="Ej. 4.50"
                            step="0.01"
                            min="0"
                            required>

                    </div>


                    <!-- ============================== -->
                    <!-- 4. MARCA -->
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
                    <!-- 5. MODELO -->
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
                    <!-- 6. PISO -->
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
                    <!-- 7. ÁREA -->
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
                    <!-- 8. PROVEEDOR -->
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
                    <!-- 9. FECHA DE ÚLTIMO MANTENIMIENTO -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="fecha_ultimo_mantenimiento">
                            Fecha de último mantenimiento
                        </label>

                        <input
                            type="date"
                            id="fecha_ultimo_mantenimiento"
                            name="fecha_ultimo_mantenimiento"
                            required>

                    </div>


                    <!-- ============================== -->
                    <!-- 10. FECHA DE PRÓXIMO MANTENIMIENTO -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <label for="fecha_proximo_mantenimiento">
                            Fecha de próximo mantenimiento
                        </label>

                        <input
                            type="date"
                            id="fecha_proximo_mantenimiento"
                            name="fecha_proximo_mantenimiento"
                            required>

                    </div>


                    <!-- ============================== -->
                    <!-- BOTÓN GUARDAR -->
                    <!-- ============================== -->

                    <div class="form-element">

                        <button type="submit">
                            Guardar extintor
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

    </script>

</body>

</html>