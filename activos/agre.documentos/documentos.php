<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="documentos.css">
    <title>Agregar Tipo de Documento</title>
</head>

<body>

    <div class="overlay">
        <div class="popup scroll-popup">
            <a href="../activos.php" id="close-btn">&times;</a>
            <div class="form">
                <h2>Agregar Tipo de Documento</h2>

                <form action="guardar_tipo_documento.php" method="POST">

                    <!-- NOMBRE TIPO DE DOCUMENTO -->
                    <div class="form-element">
                        <label for="nombre_tipo">Nombre del Tipo de Documento</label>
                        <select id="nombre_tipo" name="nombre_tipo" required>
                            <option value="">Selecciona un tipo de documento</option>
                            <option value="Póliza de seguro">Póliza de seguro</option>
                            <option value="Dictamen eléctrico">Dictamen eléctrico</option>
                            <option value="Estudio de ruido">Estudio de ruido</option>
                            <option value="Contratos PROFECO">Contratos PROFECO</option>
                            <option value="Voto Seguridad y Operación">Voto Seguridad y Operación</option>
                            <option value="Constancia de Seguridad Estructural">Constancia de Seguridad Estructural</option>
                            <option value="Actas mensuales">Actas mensuales</option>
                            <option value="Registros de compradores">Registros de compradores</option>
                            <option value="Programa Interno de Protección Civil">Programa Interno de Protección Civil</option>
                            <option value="Dictámenes/constancias de Protección Civil">Dictámenes/constancias de Protección Civil</option>
                            <option value="Actas o evidencias de simulacros">Actas o evidencias de simulacros</option>
                            <option value="Constancias de capacitación de brigadas">Constancias de capacitación de brigadas</option>
                            <option value="Manifiestos de residuos peligrosos">Manifiestos de residuos peligrosos</option>
                            <option value="Contrato de recolección de residuos">Contrato de recolección de residuos</option>
                            <option value="Bitácoras de mantenimiento">Bitácoras de mantenimiento</option>
                            <option value="Dictámenes de equipos o instalaciones">Dictámenes de equipos o instalaciones</option>
                        </select>
                    </div>

                    <!-- DESCRIPCIÓN -->
                    <div class="form-element">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" placeholder="Detalles sobre el propósito o alcance del documento..."></textarea>
                    </div>

                    <!-- PERIODICIDAD DE REVISIÓN -->
                    <div class="form-element">
                        <label for="periodicidad_revision">Periodicidad de Revisión</label>
                        <select id="periodicidad_revision" name="periodicidad_revision" required>
                            <option value="">Selecciona la periodicidad</option>
                            <option value="Mensual">Mensual</option>
                            <option value="Trimestral">Trimestral</option>
                            <option value="Semestral">Semestral</option>
                            <option value="Anual">Anual</option>
                            <option value="Bienal">Bienal (Cada 2 años)</option>
                            <option value="Eventual / Por evento">Eventual / Por evento</option>
                        </select>
                    </div>

                    <!-- NORMATIVA APLICA -->
                    <div class="form-element">
                        <label for="normativa_aplica">Normativa Aplicable</label>
                        <input type="text" id="normativa_aplica" name="normativa_aplica" placeholder="Ej. NOM-002-STPS-2010, Ley de PC, etc." maxlength="100">
                    </div>

                    <!-- DÍAS DE ALERTA PREVIA -->
                    <div class="form-element">
                        <label for="dias_alerta_previa">Días de Alerta Previa</label>
                        <input type="number" id="dias_alerta_previa" name="dias_alerta_previa" placeholder="Ej. 30" min="1" max="365" required>
                    </div>

                    <!-- BOTÓN GUARDAR -->
                    <div class="form-element">
                        <button type="submit">Guardar Tipo de Documento</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

</body>

</html>