
<script>
    function mostrarAreas() {
        const nivelSeleccionado =
            document.getElementById('nivel_origen').value;

        const selectArea =
            document.getElementById('id_area');

        const opciones = selectArea.querySelectorAll('option');

        selectArea.value = '';

        opciones.forEach(function(opcion) {
            if (!opcion.dataset.nivel) {
                opcion.style.display = 'block';
                return;
            }

            opcion.style.display =
                opcion.dataset.nivel === nivelSeleccionado
                    ? 'block'
                    : 'none';
        });

        if (nivelSeleccionado !== '') {
            selectArea.disabled = false;
            selectArea.options[0].text = 'Selecciona un área';
        } else {
            selectArea.disabled = true;
            selectArea.options[0].text = 'Primero selecciona un piso';
        }
    }

    // =========================================
    // LIMITACIÓN Y VALIDACIÓN DE FECHAS
    // =========================================

    const fechaInstalacion =
        document.getElementById('fecha_instalacion');

    const fechaUltima =
        document.getElementById('fecha_ultima_revision');

    const fechaProxima =
        document.getElementById('fecha_proxima_revision');

    const formulario = document.querySelector('form');

    // Obtener la fecha de hoy en formato YYYY-MM-DD,
    // usando la fecha local del equipo.
    function obtenerFechaHoy() {
        const hoy = new Date();
        const anio = hoy.getFullYear();
        const mes = String(hoy.getMonth() + 1).padStart(2, '0');
        const dia = String(hoy.getDate()).padStart(2, '0');

        return `${anio}-${mes}-${dia}`;
    }

    // Sumar un día a una fecha YYYY-MM-DD.
    function sumarUnDia(fecha) {
        const [anio, mes, dia] = fecha.split('-').map(Number);
        const fechaNueva = new Date(anio, mes - 1, dia);

        fechaNueva.setDate(fechaNueva.getDate() + 1);

        const nuevoAnio = fechaNueva.getFullYear();
        const nuevoMes = String(fechaNueva.getMonth() + 1).padStart(2, '0');
        const nuevoDia = String(fechaNueva.getDate()).padStart(2, '0');

        return `${nuevoAnio}-${nuevoMes}-${nuevoDia}`;
    }

    // La instalación y la última revisión no pueden ser futuras.
    function actualizarLimites() {
        const hoy = obtenerFechaHoy();

        fechaInstalacion.max = hoy;
        fechaUltima.max = hoy;

        // La última revisión debe ser el día de instalación
        // o una fecha posterior.
        if (fechaInstalacion.value) {
            fechaUltima.min = fechaInstalacion.value;
        } else {
            fechaUltima.removeAttribute('min');
        }

        // La próxima revisión debe ser posterior a la última revisión.
        if (fechaUltima.value) {
            fechaProxima.min = sumarUnDia(fechaUltima.value);
        } else {
            fechaProxima.removeAttribute('min');
        }
    }

    // Actualizar límites cuando cambie una fecha.
    fechaInstalacion.addEventListener('change', function() {
        actualizarLimites();

        if (
            fechaUltima.value &&
            fechaInstalacion.value &&
            fechaUltima.value < fechaInstalacion.value
        ) {
            fechaUltima.value = '';
            fechaProxima.value = '';
        }

        actualizarLimites();
    });

    fechaUltima.addEventListener('change', function() {
        if (
            fechaUltima.value &&
            fechaInstalacion.value &&
            fechaUltima.value < fechaInstalacion.value
        ) {
            alert('La última revisión no puede ser anterior a la instalación.');
            fechaUltima.value = '';
            fechaProxima.value = '';
        } else if (
            fechaUltima.value &&
            fechaUltima.value > obtenerFechaHoy()
        ) {
            alert('La última revisión no puede ser una fecha futura.');
            fechaUltima.value = '';
            fechaProxima.value = '';
        } else if (
            fechaUltima.value &&
            fechaProxima.value &&
            fechaProxima.value <= fechaUltima.value
        ) {
            fechaProxima.value = '';
        }

        actualizarLimites();
    });

    fechaProxima.addEventListener('change', function() {
        if (
            fechaUltima.value &&
            fechaProxima.value &&
            fechaProxima.value <= fechaUltima.value
        ) {
            alert('La próxima revisión debe ser posterior a la última revisión.');
            fechaProxima.value = '';
        }
    });

    // Comprobar nuevamente las fechas al guardar.
    formulario.addEventListener('submit', function(evento) {
        actualizarLimites();

        if (
            !fechaInstalacion.value ||
            !fechaUltima.value ||
            !fechaProxima.value
        ) {
            alert('Debes completar las tres fechas.');
            evento.preventDefault();
            return;
        }

        if (
            fechaInstalacion.value > obtenerFechaHoy() ||
            fechaUltima.value > obtenerFechaHoy()
        ) {
            alert('La instalación y la última revisión no pueden ser futuras.');
            evento.preventDefault();
            return;
        }

        if (fechaUltima.value < fechaInstalacion.value) {
            alert('La última revisión no puede ser anterior a la instalación.');
            evento.preventDefault();
            return;
        }

        if (fechaProxima.value <= fechaUltima.value) {
            alert('La próxima revisión debe ser posterior a la última revisión.');
            evento.preventDefault();
            return;
        }
    });

    // Aplicar los límites al cargar el formulario.
    actualizarLimites();
</script>
