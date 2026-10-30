<?php
/* ==========================================================
   CONEXIÓN A LA BASE DE DATOS
   Ruta: recidencias/activos/areas.php/seguimientoSotano.php/agregar/extintores.php
   ========================================================== */

require_once __DIR__ . '/../../../../clases/conexion.php';

$conexionClase = new Conexion();
$conexion = $conexionClase->conectar();


/* ==========================================================
   CONSULTAR ÁREAS DEL SÓTANO
   ========================================================== */

$sqlAreas = "
    SELECT id_area, nombre_area
    FROM public.tb_areas
    WHERE activo = TRUE
      AND nivel = $1
    ORDER BY nombre_area ASC
";

$resultadoAreas = pg_query_params(
    $conexion,
    $sqlAreas,
    ['Sótano']
);

if ($resultadoAreas === false) {
    die('Error al consultar las áreas: ' . pg_last_error($conexion));
}


/* ==========================================================
   CONSULTAR PROVEEDORES ACTIVOS
   ========================================================== */

$sqlProveedores = "
    SELECT id_proveedor, nombre_proveedor
    FROM public.tb_proveedores
    WHERE activo = TRUE
    ORDER BY nombre_proveedor ASC
";

$resultadoProveedores = pg_query($conexion, $sqlProveedores);

if ($resultadoProveedores === false) {
    die('Error al consultar los proveedores: ' . pg_last_error($conexion));
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Agregar extintor - Sótano | SIPV</title>

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <!-- Estilos del popup (incluidos aquí para evitar problemas de ruta o caché) -->
    <style>
/* ==========================================================
   POPUP AGREGAR EXTINTOR (mismo estilo que el de botiquín)
   ========================================================== */

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    background: #686868;
    font-family: Arial, Helvetica, sans-serif;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 20px 0;
}

/* Caja rosa del popup */
.modal-box {
    position: relative;
    width: 100%;
    max-width: 560px;
    max-height: calc(100vh - 40px);
    overflow-y: auto;
    background: #bf8181;
    border-radius: 14px;
    padding: 28px 38px 32px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
}

/* Scroll delgado y oscuro */
.modal-box::-webkit-scrollbar {
    width: 8px;
}

.modal-box::-webkit-scrollbar-thumb {
    background: #555;
    border-radius: 6px;
}

.modal-box h2 {
    margin: 0 0 22px;
    text-align: center;
    font-size: 28px;
    color: #000;
}

/* Botón X */
#close-btn {
    position: absolute;
    top: 14px;
    right: 20px;
    color: #fff;
    font-size: 20px;
    text-decoration: none;
}

#close-btn:hover {
    color: #eee;
}

/* Campos */
.campo {
    margin-bottom: 18px;
}

.campo label {
    display: block;
    margin-bottom: 6px;
    font-size: 15px;
    font-weight: bold;
    color: #000;
}

.campo input,
.campo select {
    width: 100%;
    height: 46px;
    padding: 0 14px;
    background: #fff;
    border: 1px solid #000;
    border-radius: 8px;
    font-size: 17px;
    font-family: inherit;
    color: #000;
}

.campo input::placeholder {
    color: #888;
}

.campo input:focus,
.campo select:focus {
    outline: 2px solid #7a3f3f;
    outline-offset: 1px;
}

/* Nota de obligatorios */
.nota {
    margin: 4px 0 18px;
    font-size: 14px;
    color: #000;
}

/* Botones */
.acciones {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn-popup {
    padding: 11px 22px;
    border: 1px solid #000;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    font-family: inherit;
    text-decoration: none;
    cursor: pointer;
}

.btn-guardar {
    background: #333;
    color: #fff;
}

.btn-guardar:hover {
    background: #000;
}

.btn-cancelar {
    background: #fff;
    color: #000;
}

.btn-cancelar:hover {
    background: #eee;
}

    </style>
</head>

<body>

    <div class="modal-box">

        <!-- Regresar al listado de extintores -->
        <a href="../extintores.php" id="close-btn" title="Regresar">
            <i class="fas fa-times"></i>
        </a>

        <h2>Agregar extintor</h2>

        <form action="agurdar_extintores.php" method="POST" id="formExtintor">

            <!-- Identificar el nivel de origen -->
            <input type="hidden" name="nivel_origen" value="Sótano">

            <div class="campo">
                <label for="numero_folio">Número de folio *</label>
                <input
                    type="text"
                    id="numero_folio"
                    name="numero_folio"
                    maxlength="30"
                    placeholder="Ej. 105"
                    required
                >
            </div>

            <div class="campo">
                <label for="tipo_agente">Tipo de agente *</label>
                <select id="tipo_agente" name="tipo_agente" required>
                    <option value="">Selecciona una opción</option>
                    <option value="POLVO QUIMICO SECO (ABC)">Polvo químico seco (ABC)</option>
                    <option value="POLVO QUIMICO SECO (PQS)">Polvo químico seco (PQS)</option>
                    <option value="CO2">Dióxido de carbono (CO₂)</option>
                    <option value="AGUA">Agua</option>
                    <option value="ESPUMA">Espuma</option>
                    <option value="OTRO">Otro</option>
                </select>
            </div>

            <div class="campo">
                <label for="capacidad">Capacidad (kg o L)</label>
                <input
                    type="number"
                    id="capacidad"
                    name="capacidad"
                    min="0"
                    step="0.01"
                    placeholder="Ej. 6"
                >
            </div>

            <div class="campo">
                <label for="marca">Marca</label>
                <input type="text" id="marca" name="marca">
            </div>

            <div class="campo">
                <label for="modelo">Modelo</label>
                <input type="text" id="modelo" name="modelo">
            </div>

            <div class="campo">
                <label for="id_area">Área del Sótano *</label>
                <select id="id_area" name="id_area" required>
                    <option value="">Selecciona un área del Sótano</option>

                    <?php while ($area = pg_fetch_assoc($resultadoAreas)): ?>
                        <option value="<?= (int) $area['id_area'] ?>">
                            <?= htmlspecialchars($area['nombre_area'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="campo">
                <label for="id_proveedor">Proveedor</label>
                <select id="id_proveedor" name="id_proveedor">
                    <option value="">Selecciona un proveedor</option>

                    <?php while ($proveedor = pg_fetch_assoc($resultadoProveedores)): ?>
                        <option value="<?= (int) $proveedor['id_proveedor'] ?>">
                            <?= htmlspecialchars($proveedor['nombre_proveedor'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="campo">
                <label for="fecha_ultimo_mantenimiento">Fecha del último mantenimiento</label>
                <input
                    type="date"
                    id="fecha_ultimo_mantenimiento"
                    name="fecha_ultimo_mantenimiento"
                >
            </div>

            <div class="campo">
                <label for="fecha_proximo_mantenimiento">Fecha del próximo mantenimiento</label>
                <input
                    type="date"
                    id="fecha_proximo_mantenimiento"
                    name="fecha_proximo_mantenimiento"
                >
            </div>

            <p class="nota">Los campos marcados con * son obligatorios.</p>

            <div class="acciones">
                <a href="../extintores.php" class="btn-popup btn-cancelar">Cancelar</a>
                <button type="submit" class="btn-popup btn-guardar">Guardar extintor</button>
            </div>

        </form>

    </div>

    <script>
        /* ======================================================
           VALIDACIÓN DE FECHAS
           ====================================================== */

        document.getElementById('formExtintor').addEventListener('submit', function (evento) {

            const fechaUltimo = document.getElementById(
                'fecha_ultimo_mantenimiento'
            ).value;

            const fechaProximo = document.getElementById(
                'fecha_proximo_mantenimiento'
            ).value;

            /* Fecha de hoy en hora local (toISOString usa UTC y puede adelantar el día) */
            const hoy = new Date();
            const fechaActual =
                hoy.getFullYear() + '-' +
                String(hoy.getMonth() + 1).padStart(2, '0') + '-' +
                String(hoy.getDate()).padStart(2, '0');

            const fechaMinima = '2000-01-01';
            const fechaMaxima = '2030-12-31';

            /* Validar que las fechas estén dentro del rango permitido */
            if (fechaUltimo) {
                if (fechaUltimo < fechaMinima || fechaUltimo > fechaMaxima) {
                    evento.preventDefault();
                    alert('La fecha del último mantenimiento debe estar entre 2000 y 2030.');
                    return;
                }

                if (fechaUltimo > fechaActual) {
                    evento.preventDefault();
                    alert('La fecha del último mantenimiento no puede ser futura.');
                    return;
                }
            }

            if (fechaProximo) {
                if (fechaProximo < fechaMinima || fechaProximo > fechaMaxima) {
                    evento.preventDefault();
                    alert('La fecha del próximo mantenimiento debe estar entre 2000 y 2030.');
                    return;
                }
            }

            /* El próximo mantenimiento debe ser posterior al último */
            if (fechaUltimo && fechaProximo && fechaProximo <= fechaUltimo) {
                evento.preventDefault();
                alert('La fecha del próximo mantenimiento debe ser posterior a la del último mantenimiento.');
                return;
            }
        });
    </script>

</body>
</html>