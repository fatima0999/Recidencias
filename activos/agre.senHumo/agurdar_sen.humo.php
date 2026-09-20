<?php

require_once '../../clases/conexion.php';

// Crear conexión
$conexion = new Conexion();
$bd = $conexion->conectar();


// ==============================
// OBTENER ÁREAS
// ==============================

$sqlAreas = "SELECT id_area, nombre_area
             FROM tb_areas
             WHERE activo = true
             ORDER BY nombre_area ASC";

$resultadoAreas = pg_query($bd, $sqlAreas);


// ==============================
// OBTENER PROVEEDORES (Opcional si usas proveedores)
// ==============================

$sqlProveedores = "SELECT id_proveedor, nombre_proveedor
                   FROM tb_proveedores
                   WHERE activo = true
                   ORDER BY nombre_proveedor ASC";

$resultadoProveedores = pg_query($bd, $sqlProveedores);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="sen.humo.css">
    <title>Agregar Luz de Emergencia</title>
</head>

<body>

    <div class="overlay">
        <div class="popup scroll-popup">
            <a href="../activos.php" id="close-btn">&times;</a>
            <div class="form">
                <h2>Agregar Luz de Emergencia</h2>

                <form action="luc.Emeregencia.php" method="POST" enctype="multipart/form-data">

                    <!-- ============================== -->
                    <!-- ÁREA -->
                    <!-- ============================== -->
                    <div class="form-element">
                        <label for="id_area">Área</label>
                        <select id="id_area" name="id_area" required>
                            <option value="">Selecciona un área</option>
                            <?php
                            if ($resultadoAreas) {
                                while ($area = pg_fetch_assoc($resultadoAreas)) {
                                    echo '<option value="' . htmlspecialchars($area['id_area']) . '">' . htmlspecialchars($area['nombre_area']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <!-- ============================== -->
                    <!-- TIPO DE DETECTOR -->
                    <!-- ============================== -->
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

                    <!-- ============================== -->
                    <!-- MARCA -->
                    <!-- ============================== -->
                    <div class="form-element">
                        <label for="marca">Marca</label>
                        <input type="text" id="marca" name="marca" placeholder="Ingrese la marca" maxlength="50" required>
                    </div>

                    <!-- ============================== -->
                    <!-- MODELO -->
                    <!-- ============================== -->
                    <div class="form-element">
                        <label for="modelo">Modelo</label>
                        <input type="text" id="modelo" name="modelo" placeholder="Ingrese el modelo" maxlength="50" required>
                    </div>

                    <!-- ============================== -->
                    <!-- NÚMERO DE SERIE -->
                    <!-- ============================== -->
                    <div class="form-element">
                        <label for="numero_serie">Número de Serie</label>
                        <input type="text" id="numero_serie" name="numero_serie" placeholder="Ingrese el número de serie" maxlength="50" required>
                    </div>

                    <!-- ============================== -->
                    <!-- ESTADO FÍSICO -->
                    <!-- ============================== -->
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

                    <!-- ============================== -->
                    <!-- FECHA DE INSTALACIÓN -->
                    <!-- ============================== -->
                    <div class="form-element">
                        <label for="fecha_instalacion">Fecha de Instalación</label>
                        <input type="date" id="fecha_instalacion" name="fecha_instalacion" required>
                    </div>

                    <!-- ============================== -->
                    <!-- FECHA DE ÚLTIMA REVISIÓN -->
                    <!-- ============================== -->
                    <div class="form-element">
                        <label for="fecha_ultima_revision">Fecha de Última Revisión</label>
                        <input type="date" id="fecha_ultima_revision" name="fecha_ultima_revision" required>
                    </div>

                    <!-- ============================== -->
                    <!-- FECHA DE PRÓXIMA REVISIÓN -->
                    <!-- ============================== -->
                    <div class="form-element">
                        <label for="fecha_proxima_revision">Fecha de Próxima Revisión</label>
                        <input type="date" id="fecha_proxima_revision" name="fecha_proxima_revision" required>
                    </div>
        
                    <!-- BOTÓN GUARDAR -->
                    <!-- ============================== -->
                    <div class="form-element">
                        <button type="submit">Guardar Luz de Emergencia</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

</body>

</html>