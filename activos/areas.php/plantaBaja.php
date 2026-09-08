<?php
// Aquí puedes poner tu lógica PHP si la necesitas
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" 
          rel="stylesheet" 
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" 
          crossorigin="anonymous">

    <link rel="stylesheet" href="activos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">

    <title>PLANTAB</title>
</head>
<body>

    <!-- ==================== NAVBAR ==================== -->
    <nav class="navbar navbar-light bg-light shadow-sm fixed-top">
        <div class="container-fluid">
            <div class="border-start ps-3">
                <h6 class="mb-0 titulo-audi">PLANTA BAJA</h6>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="ARCHIVO" download="Reporte" class="btn btn-outline-secondary">
                    📄 PDF
                </a>

                <button class="navbar-toggler"
                        type="button"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasDerecho"
                        aria-controls="offcanvasDerecho">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <a href="../activos.php" class="btn btn-outline-danger">
                    Volver
                </a>
            </div>
        </div>
    </nav>

    <!-- ==================== OFFCANVAS ==================== -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasDerecho" aria-labelledby="offcanvasDerechoLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasDerechoLabel">Menú</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link active" href="sotano.php">
                        <i class="fa-solid fa-building"></i> Sótano
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="plantaBaja.php">
                        <i class="fa-solid fa-building"></i> Planta baja
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="primerNivel.php">
                        <i class="fa-solid fa-building"></i> Primer nivel
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="SegundoNivel.php">
                        <i class="fa-solid fa-building"></i> Segundo nivel
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="TercerNivel.php">
                        <i class="fa-solid fa-building"></i> Tercer nivel
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="azotea.php">
                        <i class="fa-solid fa-building"></i> Azotea
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- ==================== CONTENIDO PRINCIPAL ==================== -->
    <div class="container mt-5 pt-3">

        <!-- ==================== CARDS ==================== -->
        <div class="row g-3">

            <!-- Extintores -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto mb-2 rounded-circle"
                             style="width: 60px; height: 60px; background-color: #f8d7da;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="35" height="35" fill="#dc3545">
                                <path d="M576 96C576 86.4 571.7 77.3 564.3 71.3C556.9 65.3 547.1 62.7 537.7 64.6L377.7 96.6C365.5 99.1 356 108.3 353 120L288 120L288 96C288 78.3 273.7 64 256 64L224 64C206.3 64 192 78.3 192 96L192 124.4C136.7 136.7 90.7 173.9 66.5 223.5C60.7 235.4 65.6 249.8 77.6 255.6C89.6 261.4 103.9 256.5 109.7 244.5C126 210.9 155.8 185.1 192 174L192 202.8C154.2 220.8 128 259.3 128 304L128 432L352 432L352 304C352 259.3 325.8 220.8 288 202.8L288 168L353 168C356 179.7 365.5 188.9 377.7 191.4L537.7 223.4C547.1 225.3 556.8 222.8 564.3 216.8C571.8 210.8 576 201.6 576 192L576 96zM352 512L352 480L128 480L128 512C128 547.3 156.7 576 192 576L288 576C323.3 576 352 547.3 352 512z"/>
                            </svg>
                        </div>
                        <h5 class="card-title mt-2 mb-1">Extintores</h5>
                        <p class="card-text small text-muted mb-1">Gestión de extintores</p>
                        <a href="#" class="btn btn-sm btn-outline-danger rounded-pill">Ver más</a>
                    </div>
                </div>
            </div>

            <!-- Botiquines -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto rounded-circle"
                             style="width: 60px; height: 60px; background-color: #d1e7dd;">
                            <animated-icons
                                src="https://animatedicons.co/get-icon?name=medicine%20box&style=minimalistic&token=15c6e5ba-de9c-4e4a-a7dc-58484b5043bb"
                                trigger="click"
                                attributes='{"variationThumbColour":"#198754","variationName":"Two Tone","variationNumber":2,"numberOfGroups":2,"backgroundIsGroup":false,"strokeWidth":1.18,"defaultColours":{"group-1":"#000000","group-2":"#198754","background":"#FFFFFF"}}'
                                height="42"
                                width="42">
                            </animated-icons>
                        </div>
                        <h5 class="card-title mt-2 mb-1">Botiquines</h5>
                        <p class="card-text small text-muted mb-1">Gestión de botiquines</p>
                        <a href="#" class="btn btn-sm btn-outline-success rounded-pill">Ver más</a>
                    </div>
                </div>
            </div>

            <!-- Luces de emergencia -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto rounded-circle"
                             style="width: 60px; height: 60px; background-color: #fff3cd;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="40" height="40">
                                <path d="M439.466 416.367l-35.157-244.481c-6.007-41.635-42.262-73.031-84.315-73.031H192.111c-42.053 0-78.307 31.396-84.315 72.979L72.535 416.367c-41.008 6.687-72.509 42.262-72.509 85.15 0 5.799 4.702 10.448 10.448 10.448h491.052c5.799 0 10.448-4.649 10.448-10.448C511.974 458.629 480.526 423.053 439.466 416.367zM128.483 174.811c4.545-31.396 31.918-55.061 63.628-55.061h127.882c31.762 0 59.083 23.665 63.628 55.113l34.53 240.197H266.448V342.97c35.993-5.12 63.732-36.045 63.732-73.397 0-40.904-33.277-74.18-74.18-74.18-40.903 0-74.18 33.277-74.18 74.18 0 37.351 27.791 68.277 63.732 73.397v72.091H93.848L128.483 174.811zM256.012 68.288c5.77 0 10.448-4.678 10.448-10.448V10.483c0-5.77-4.678-10.448-10.448-10.448-5.77 0-10.448 4.678-10.448 10.448V57.84c0 5.77 4.678 10.448 10.448 10.448zM339.479 64.936l29.943-32.354c3.921-4.234 3.663-10.846-.571-14.764-4.237-3.923-10.848-3.663-14.764.571l-29.943 32.354c-3.921 4.234-3.663 10.846.571 14.764 4.237 3.922 10.85 3.665 14.764-.571zM172.547 64.936c3.919 4.235 10.532 4.492 14.764.571 4.234-3.918 4.492-10.53.571-14.764L157.938 18.39c-3.913-4.234-10.53-4.494-14.764-.571-4.234 3.918-4.492 10.53-.571 14.764L172.547 64.936z"
                                      fill="#ffc107"></path>
                            </svg>
                        </div>
                        <h5 class="card-title mt-2 mb-1">Luces de emergencia</h5>
                        <p class="card-text small text-muted mb-1">Gestión de luces</p>
                        <a href="#" class="btn btn-sm btn-outline-warning rounded-pill">Ver más</a>
                    </div>
                </div>
            </div>

            <!-- Sensores de Humo -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto rounded-circle"
                             style="width: 60px; height: 60px; background-color: #e2e3e5;">
                            <animated-icons
                                src="https://animatedicons.co/get-icon?name=Sensor&style=minimalistic&token=d2e63307-9111-4ea1-a108-0fd228efa70c"
                                trigger="click"
                                attributes='{"variationThumbColour":"#495057","variationName":"Two Tone","variationNumber":2,"numberOfGroups":2,"backgroundIsGroup":false,"strokeWidth":1.1,"defaultColours":{"group-1":"#000000","group-2":"#495057","background":"#FFFFFF"}}'
                                height="42"
                                width="42">
                            </animated-icons>
                        </div>
                        <h5 class="card-title mt-2 mb-1">Sensores de Humo</h5>
                        <p class="card-text small text-muted mb-1">Gestión de sensores</p>
                        <a href="#" class="btn btn-sm btn-outline-secondary rounded-pill">Ver más</a>
                    </div>
                </div>
            </div>

            <!-- Punto de reunión -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto rounded-circle"
                             style="width: 60px; height: 60px; background-color: #cfe2ff;">
                            <animated-icons
                                src="https://animatedicons.co/get-icon?name=location&style=minimalistic&token=45b3e531-a5b4-4725-afdf-330d67562c0e"
                                trigger="click"
                                attributes='{"variationThumbColour":"#0d6efd","variationName":"Two Tone","variationNumber":2,"numberOfGroups":2,"backgroundIsGroup":false,"strokeWidth":1.1,"defaultColours":{"group-1":"#000000","group-2":"#0d6efd","background":"#FFFFFF"}}'
                                height="42"
                                width="42">
                            </animated-icons>
                        </div>
                        <h5 class="card-title mt-2 mb-1">Punto de reunión</h5>
                        <p class="card-text small text-muted mb-1">Gestión de puntos</p>
                        <a href="#" class="btn btn-sm btn-outline-primary rounded-pill">Ver más</a>
                    </div>
                </div>
            </div>

            <!-- Señalizaciones -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto rounded-circle"
                             style="width: 60px; height: 60px; background-color: #fff3cd;">
                            <animated-icons
                                src="https://animatedicons.co/get-icon?name=Alert&style=minimalistic&token=4ff92fe8-3f2e-471b-beff-2c28789b1813"
                                trigger="click"
                                attributes='{"variationThumbColour":"#ffc107","variationName":"Two Tone","variationNumber":2,"numberOfGroups":2,"backgroundIsGroup":false,"strokeWidth":1.1,"defaultColours":{"group-1":"#000000","group-2":"#ffc107","background":"#FFFFFF"}}'
                                height="42"
                                width="42">
                            </animated-icons>
                        </div>
                        <h5 class="card-title mt-2 mb-1">Señalizaciones</h5>
                        <p class="card-text small text-muted mb-1">Gestión de señalizaciones</p>
                        <a href="#" class="btn btn-sm btn-outline-warning rounded-pill">Ver más</a>
                    </div>
                </div>
            </div>

            <!-- Alarmas -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto rounded-circle"
                             style="width: 60px; height: 60px; background-color: #f8d7da;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22.511 22.521" width="42" height="42">
                                <path fill="#dc3545" d="M.332,10.919H2.051a.342.342,0,0,1,0,.683H.332A.342.342,0,0,1,.332,10.919Zm19.777.341a.342.342,0,0,0,.342.342H22.17a.342.342,0,0,0,0-.683H20.451A.342.342,0,0,0,20.109,11.26ZM11.251,2.4a.341.341,0,0,0,.341-.342V.341a.341.341,0,1,0-.682,0V2.06A.342.342,0,0,0,11.251,2.4ZM3.113,15.564l-1.489.86a.343.343,0,0,0,.171.637,16,16,0,0,0,1.659-.906A.341.341,0,0,0,3.113,15.564ZM19.219,7a16.388,16.388,0,0,0,1.659-.9.342.342,0,0,0-.342-.592l-1.488.86A.343.343,0,0,0,19.219,7ZM6.356,3.464a.342.342,0,0,0,.591-.342l-.86-1.488a.341.341,0,0,0-.591.341Zm9.324.124a.34.34,0,0,0,.466-.124l.86-1.489a.341.341,0,1,0-.591-.341l-.859,1.488A.34.34,0,0,0,15.68,3.588ZM1.624,6.1l1.489.859a.341.341,0,0,0,.341-.591l-1.488-.86A.342.342,0,0,0,1.624,6.1ZM19.048,16.155l1.488.86a.341.341,0,0,0,.342-.591l-1.489-.86A.341.341,0,0,0,19.048,16.155Zm3.122,6.366H.332a.341.341,0,0,1,0-.682H2.745A2.62,2.62,0,0,1,5,19.588V11.26a6.256,6.256,0,0,1,12.512,0v8.328a2.619,2.619,0,0,1,2.25,2.25H22.17A.342.342,0,0,1,22.17,22.521ZM5.678,19.563H9.09v-8.3a2.161,2.161,0,0,1,4.322,0v8.3h3.412v-8.3c-.26-7.374-10.884-7.377-11.146,0Zm4.094-8.3V19.563H12.73v-8.3A1.479,1.479,0,0,0,9.772,11.26Zm9.3,10.578a1.937,1.937,0,0,0-1.9-1.592H5.337a1.937,1.937,0,0,0-1.9,1.592ZM11.592,7.393a.341.341,0,0,0-.341-.341A4.213,4.213,0,0,0,7.043,11.26a.341.341,0,1,0,.682,0,3.529,3.529,0,0,1,3.526-3.525A.341.341,0,0,0,11.592,7.393Z"/>
                            </svg>
                        </div>
                        <h5 class="card-title mt-2 mb-1">Alarmas</h5>
                        <p class="card-text small text-muted mb-1">Gestión de alarmas</p>
                        <a href="#" class="btn btn-sm btn-outline-danger rounded-pill">Ver más</a>
                    </div>
                </div>
            </div>

            <!-- Hidrantes -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto rounded-circle"
                             style="width: 60px; height: 60px; background-color: #cff4fc;">
                            <i class="fa-solid fa-fire-extinguisher fa-2x text-info"></i>
                        </div>
                        <h5 class="card-title mt-2 mb-1">Hidrantes</h5>
                        <p class="card-text small text-muted mb-1">Gestión de hidrantes</p>
                        <a href="#" class="btn btn-sm btn-outline-info rounded-pill">Ver más</a>
                    </div>
                </div>
            </div>

            <!-- Documentos -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto rounded-circle"
                             style="width: 60px; height: 60px; background-color: #cfe2ff;">
                            <animated-icons
                                src="https://animatedicons.co/get-icon?name=note&style=minimalistic&token=cb234c69-9f56-4cf2-844e-2c2109b81513"
                                trigger="click"
                                attributes='{"variationThumbColour":"#0d6efd","variationName":"Two Tone","variationNumber":2,"numberOfGroups":2,"backgroundIsGroup":false,"strokeWidth":1.1,"defaultColours":{"group-1":"#000000","group-2":"#0d6efd","background":"#FFFFFF"}}'
                                height="42"
                                width="42">
                            </animated-icons>
                        </div>
                        <h5 class="card-title mt-2 mb-1">Documentos</h5>
                        <p class="card-text small text-muted mb-1">Gestión de documentos</p>
                        <a href="#" class="btn btn-sm btn-outline-primary rounded-pill">Ver más</a>
                    </div>
                </div>
            </div>

        </div><!-- /.row -->
    </div><!-- /.container -->

    <!-- ==================== SCRIPTS ==================== -->
    <script src="https://animatedicons.co/scripts/embed-animated-icons.js"></script>
    <script src="js/tu-script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
            crossorigin="anonymous"></script>

    <style>
        .card:hover {
            transform: translateY(-14px) scale(1.05) !important;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2) !important;
            transition: all 0.35s ease !important;
        }
    </style>
</body>
</html>