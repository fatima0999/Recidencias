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
        <div class="popup">
            <a href="../activos.php" id="close-btn">&times;</a>
            
            <div class="form">
                <h2>Agregar extintor</h2>
                
                <form action="guardar_extintor.php" method="POST">
                    <div class="form-element">
                        <label for="folio">Folio</label>
                        <input type="text" id="folio" name="folio" placeholder="Ingrese el folio" required>
                    </div>

                    <div class="form-element">
                        <label for="tipo">Tipo</label>
                        <input type="text" id="tipo" name="tipo" placeholder="Tipo de extintor" required>
                    </div>

                    <div class="form-element">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre" placeholder="Nombre del extintor" required>
                    </div>

                    <div class="form-element">
                        <label for="area">Área</label>
                        <input type="text" id="area" name="area" placeholder="Área asignada" required>
                    </div>

                    <div class="form-element">
                        <label for="ubicacion">Ubicación</label>
                        <input type="text" id="ubicacion" name="ubicacion" placeholder="Ubicación exacta" required>
                    </div>

                    <div class="form-element">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" required>
                            <option value="">Selecciona un estado</option>
                            <option value="Al corriente">Al corriente</option>
                            <option value="Por vencer">Por vencer</option>
                            <option value="Vencido">Vencido</option>
                            <option value="Mantenimiento">Mantenimiento</option>
                        </select>
                    </div>

                    <div class="form-element">
                        <label for="proximo_vencimiento">Próximo vencimiento</label>
                        <input type="date" id="proximo_vencimiento" name="proximo_vencimiento" required>
                    </div>

                    <div class="form-element">
                        <button type="submit">Guardar extintor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>