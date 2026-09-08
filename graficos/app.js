console.log("app.js cargó");
Chart.defaults.color = '#495057';

const canvas = document.getElementById('modelsChart');

if(canvas){

const ctx = canvas.getContext('2d');

new Chart(ctx, {

    type: 'doughnut',

    data: {

        labels:[
            'Al corriente',
            'Por vencer',
            'Vencidos',
            'Mantenimiento'
        ],

        datasets:[{

            data:[42,8,5,17],

            backgroundColor:[
                '#28a745',
                '#ffc107',
                '#dc3545',
                '#0d6efd'
            ],

            borderColor:'#ffffff',
            borderWidth:3

        }]

    },

    options:{

        responsive:true,

        cutout:'65%',

        plugins:{

            legend:{
                position:'bottom'
            }

        }

    }

});

}