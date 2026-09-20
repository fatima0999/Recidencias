<?php

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
    <!-- Font Awesome v6.4.2 (Versión estable) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <title>Activos</title>

</head>
<body>

    <!-- ==================== NAVBAR ==================== -->
    <nav class="navbar navbar-light bg-light shadow-sm fixed-top">
        <div class="container-fluid">
            <div class="border-start ps-3">
                <h6 class="mb-0 titulo-audi">ACTIVOS</h6>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">
                <!-- PDF -->
                <a href="ARCHIVO" download="Reporte" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-file-pdf text-danger me-1"></i> PDF
                </a>

                <a href="#" class="btn btn-light position-relative btn-sm">
                    <i class="fas fa-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">5</span>
                </a>
                <a href="../pruebafina.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Volver
                </a>
            </div>
        </div>
    </nav>

    <div class="container d-flex justify-content-between align-items-center mt-1 pt-3">
        <h5>ACTIVOS</h5>
        <div class="dropdown">
            <button class="bn30 dropdown-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false">
                <i class="fa-solid fa-plus"></i>
                Agregar
            </button>
            <ul class="dropdown-menu">
                <li>
                    <a class="dropdown-item" href="./agre.extintores/extintore1.php">
                        <i class="fa-solid fa-fire-extinguisher me-2"></i>
                        Agregar extintor
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="./agre.botiquines/botiquines.php">
                        <i class="fa-solid fa-kit-medical me-2"></i>
                        Agregar botiquín
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="./agre.luc.Emegen/luc.Emeregencia.php">
                        <i class="fa-solid fa-lightbulb me-2"></i>
                        Agregar luz de emergencia
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="./agre.senHumo/sen.Humo.php">
                        <i class="fa-solid fa-smog me-2"></i>
                        Agregar detector de humo
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="./agre.puntReunion/punt_reunion.php">
                        <i class="fa-solid fa-location-dot me-2"></i>
                        Agregar Puntos de Reunion
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="./agre.documentos/documentos.php">
                        <i class="fa-solid fa-file-lines me-2"></i>
                        Agregar documentos
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="./agre.rutasEvacu/rut.Evacuacion.php">
                        <i class="fa-solid fa-person-walking-arrow-right me-2"></i>
                        Agregar rutas de evacuación
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="./agre.senHumo/sen.Humo.php">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        Agregar señalizaciones
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="./agre.hidrantes/hidrantes.php">
                        <i class="fa-solid fa-faucet-drip me-2"></i>
                        Agregar hidrante
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="./agre.alar.Sismicas/alarmas.php">
                        <i class="fa-solid fa-bell me-2"></i>
                        Agregar alarma de emergencia
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasDerecho" aria-labelledby="offcanvasDerechoLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasDerechoLabel">Menú</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="areas.php/sotano.php">
                        <i class="fa-solid fa-building"></i> Sótano
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="areas.php/plantaBaja.php">
                        <i class="fa-solid fa-building"></i> Planta baja
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="areas.php/primerNivel.php">
                        <i class="fa-solid fa-building"></i> Primer nivel
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="areas.php/SegundoNivel.php">
                        <i class="fa-solid fa-building"></i> Segundo nivel
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="areas.php/TercerNivel.php">
                        <i class="fa-solid fa-building"></i> Tercer nivel
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="areas.php/azotea.php">
                        <i class="fa-solid fa-building"></i> Azotea
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- ==================== CONTENIDO PRINCIPAL ==================== -->
    <div class="container mt-2 pt-2">

        <!-- Filtros de niveles -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex gap-3 flex-wrap">
                    <a href="activos.php" class="btn btn-primary rounded-pill">Todas</a>
                    <a href="areas.php/sotano.php" class="btn btn-primary rounded-pill">Sótano</a>
                    <a href="areas.php/plantaBaja.php" class="btn btn-primary rounded-pill">Planta baja</a>
                    <a href="areas.php/primerNivel.php" class="btn btn-primary rounded-pill">Primer nivel</a>
                    <a href="areas.php/SegundoNivel.php" class="btn btn-primary rounded-pill">Segundo nivel</a>
                    <a href="areas.php/TercerNivel.php" class="btn btn-primary rounded-pill">Tercer nivel</a>
                </div>
            </div>
        </div>

        <!-- ==================== CARDS DE ACTIVOS ==================== -->
        <div class="row g-3">

            <!-- 1. Extintores -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto mb-2 rounded-circle"
                             style="width: 60px; height: 60px; background-color: #f8d7da;">
                            <i class="fa-solid fa-fire-extinguisher fa-2x text-danger"></i>
                        </div>
                        <h5 class="card-title mt-1 mb-1" style="font-size: 0.95rem;">Extintores</h5>
                        <p class="card-text small text-muted mb-1" style="font-size: 0.75rem;">Gestión de extintores</p>
                        <a href="#" class="btn btn-sm btn-outline-danger rounded-pill">Ver más</a>
                    </div>
                </div>
            </div>

            <!-- 2. Botiquines -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto mb-2 rounded-circle"
                             style="width: 60px; height: 60px; background-color: #d1e7dd;">
                            <i class="fa-solid fa-kit-medical fa-2x text-success"></i>
                        </div>
                        <h5 class="card-title mt-1 mb-1" style="font-size: 0.95rem;">Botiquines</h5>
                        <p class="card-text small text-muted mb-1" style="font-size: 0.75rem;">Gestión de botiquines</p>
                        <a href="#" class="btn btn-sm btn-outline-success rounded-pill">Ver más</a>
                    </div>
                </div>
            </div>

            <!-- 3. Luces de emergencia -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto mb-2 rounded-circle"
                             style="width: 60px; height: 60px; background-color: #fff3cd;">
                            <i class="fa-solid fa-lightbulb fa-2x text-warning"></i>
                        </div>
                        <h5 class="card-title mt-1 mb-1" style="font-size: 0.95rem;">Luces Emergencia</h5>
                        <p class="card-text small text-muted mb-1" style="font-size: 0.75rem;">Gestión de luces</p>
                        <a href="#" class="btn btn-sm btn-outline-warning rounded-pill">Ver más</a>
                    </div>
                </div>
            </div>

            <!-- 4. Detectores de Humo -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto mb-2 rounded-circle"
                             style="width: 60px; height: 60px; background-color: #e2e3e5;">
                            <i class="fa-solid fa-smog fa-2x text-secondary"></i>
                        </div>
                        <h5 class="card-title mt-1 mb-1" style="font-size: 0.95rem;">Detectores Humo</h5>
                        <p class="card-text small text-muted mb-1" style="font-size: 0.75rem;">Gestión de detectores</p>
                        <a href="#" class="btn btn-sm btn-outline-secondary rounded-pill">Ver más</a>
                    </div>
                </div>
            </div>

            <!-- 5. Puntos de Reunión -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto mb-2 rounded-circle"
                             style="width: 60px; height: 60px; background-color: #cfe2ff;">
                            <i class="fa-solid fa-location-dot fa-2x text-primary"></i>
                        </div>
                        <h5 class="card-title mt-1 mb-1" style="font-size: 0.95rem;">Puntos de Reunión</h5>
                        <p class="card-text small text-muted mb-1" style="font-size: 0.75rem;">Gestión de puntos</p>
                        <a href="#" class="btn btn-sm btn-outline-primary rounded-pill">Ver más</a>
                    </div>
                </div>
            </div>

            <!-- 6. Documentos -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto mb-2 rounded-circle"
                             style="width: 60px; height: 60px; background-color: #cfe2ff;">
                            <i class="fa-solid fa-file-lines fa-2x text-primary"></i>
                        </div>
                        <h5 class="card-title mt-1 mb-1" style="font-size: 0.95rem;">Documentos</h5>
                        <p class="card-text small text-muted mb-1" style="font-size: 0.75rem;">Gestión de documentos</p>
                        <a href="#" class="btn btn-sm btn-outline-primary rounded-pill">Ver más</a>
                    </div>
                </div>
            </div>

            <!-- 7. Rutas de Evacuación -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto mb-2 rounded-circle"
                             style="width: 60px; height: 60px; background-color: #d1e7dd;">
                            <i class="fa-solid fa-person-walking-arrow-right fa-2x text-success"></i>
                        </div>
                        <h5 class="card-title mt-1 mb-1" style="font-size: 0.95rem;">Rutas Evacuación</h5>
                        <p class="card-text small text-muted mb-1" style="font-size: 0.75rem;">Gestión de rutas</p>
                        <a href="#" class="btn btn-sm btn-outline-success rounded-pill">Ver más</a>
                    </div>
                </div>
            </div>

            <!-- 8. Señalizaciones -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto mb-2 rounded-circle"
                             style="width: 60px; height: 60px; background-color: #fff3cd;">
                            <i class="fa-solid fa-triangle-exclamation fa-2x text-warning"></i>
                        </div>
                        <h5 class="card-title mt-1 mb-1" style="font-size: 0.95rem;">Señalizaciones</h5>
                        <p class="card-text small text-muted mb-1" style="font-size: 0.75rem;">Gestión de señales</p>
                        <a href="#" class="btn btn-sm btn-outline-warning rounded-pill">Ver más</a>
                    </div>
                </div>
            </div>

            <!-- 9. Hidrantes -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto mb-2 rounded-circle"
                             style="width: 60px; height: 60px; background-color: #cff4fc;">
                            <i class="fa-solid fa-faucet-drip fa-2x text-info"></i>
                        </div>
                        <h5 class="card-title mt-1 mb-1" style="font-size: 0.95rem;">Hidrantes</h5>
                        <p class="card-text small text-muted mb-1" style="font-size: 0.75rem;">Gestión de hidrantes</p>
                        <a href="#" class="btn btn-sm btn-outline-info rounded-pill">Ver más</a>
                    </div>
                </div>
            </div>

            <!-- 10. Alarmas de emergencia -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="width: 11rem; height: 11rem;">
                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-2">
                        <div class="d-flex justify-content-center align-items-center mx-auto mb-2 rounded-circle"
                             style="width: 60px; height: 60px; background-color: #f8d7da;">
                            <i class="fa-solid fa-bell fa-2x text-danger"></i>
                        </div>
                        <h5 class="card-title mt-1 mb-1" style="font-size: 0.95rem;">Alarmas</h5>
                        <p class="card-text small text-muted mb-1" style="font-size: 0.75rem;">Gestión de alarmas</p>
                        <a href="#" class="btn btn-sm btn-outline-danger rounded-pill">Ver más</a>
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