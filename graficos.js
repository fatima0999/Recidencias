var ctx = document.getElementById('graficoActivos').getContext('2d');

var graficoActivos = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Al corriente', 'Por vencer', 'Vencidos'],
        datasets: [{
            label: 'Cantidad de Activos',
            data: [datosActivos.alCorriente, datosActivos.porVencer, datosActivos.vencidos],
            backgroundColor: [
                'rgba(40, 167, 69, 0.7)',
                'rgba(255, 193, 7, 0.7)',
                'rgba(220, 53, 69, 0.7)'
            ],
            borderColor: [
                'rgba(40, 167, 69, 1)',
                'rgba(255, 193, 7, 1)',
                'rgba(220, 53, 69, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top'
            },
            title: {
                display: true,
                text: 'Estado de los Activos'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});


// ==========================================
// GRÁFICA: ACTIVOS POR TIPO
// ==========================================

var ctxPorTipo = document.getElementById('graficoPorTipo').getContext('2d');

var graficoPorTipo = new Chart(ctxPorTipo, {
    type: 'bar',
    data: {
        labels: datosPorTipo.labels,
        datasets: [{
            label: 'Cantidad de Activos',
            data: datosPorTipo.valores,
            backgroundColor: 'rgba(13, 110, 253, 0.7)',
            borderColor: 'rgba(13, 110, 253, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            },
            title: {
                display: true,
                text: 'Inventario por Tipo de Activo'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});