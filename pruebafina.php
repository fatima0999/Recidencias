<?php
// Conexiones, sesiones o variables
$usuario = "Fatima";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIPV - Audi Center Insurgentes</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 5 (Iconos) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Tu CSS personal -->
    <link rel="stylesheet" href="bontnes.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="botones.css">
</head>

<body>

    <!-- NAVBAR SUPERIOR -->
    <nav class="navbar navbar-light bg-light shadow-sm fixed-top">
        <div class="container-fluid">

            <!-- Menú Izquierdo (Desplegable) -->
            <button class="navbar-toggler me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Branding e Identificación -->
            <a class="navbar-brand me-3 mb-0" href="#">
                <span class="logo-sipv">SIPV</span>
            </a>

            <div class="border-start ps-3 d-none d-md-block">
                <h6 class="mb-0 titulo-audi">AUDI CENTER INSURGENTES</h6>
                <small class="text-muted">Sistema Inteligente de Prevención y Verificación</small>
            </div>

            <!-- Botones y Acciones de la Derecha -->
            <div class="ms-auto d-flex align-items-center gap-2">

                <!-- Botón PDF -->
                <a href="ARCHIVO" download="Reporte" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-file-pdf text-danger me-1"></i> PDF
                </a>

                <!-- Notificaciones / Dropdown -->
                <a href="#" class="btn btn-light position-relative btn-sm">
                    <i class="fas fa-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">5</span>
                </a>

                <!-- Menú Derecho Offcanvas -->
                <button class="navbar-toggler btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDerecho">
                    <i class="fas fa-user-circle"></i>
                </button>

                <!-- Salir -->
                <a href="logout.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <!-- OFFCANVAS IZQUIERDO (Navegación Principal) -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNavbar">
        <div class="offcanvas-header bg-dark text-white">
            <h5 class="offcanvas-title"><i class="fas fa-shield-alt me-2"></i>SIPV Menu</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                <li class="nav-item">
                    <a class="nav-link active" href="pruebafina.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="calendario/index.html"><i class="fas fa-calendar-alt me-2"></i>Calendario</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/recidencias/activos/activos.php"><i class="fas fa-boxes me-2"></i>Activos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="fas fa-map me-2"></i>Planos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="calendario/index.html"><i class="fas fa-exclamation-triangle me-2"></i>Alertas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="calendario/index.html"><i class="fas fa-tools me-2"></i>Mantenimientos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="calendario/index.html"><i class="fas fa-chart-bar me-2"></i>Reportes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="calendario/index.html"><i class="fas fa-users me-2"></i>Usuarios</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-cog me-2"></i>Configuración
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Roles</a></li>
                        <li><a class="dropdown-item" href="#">Usuarios del sistema</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#">Preferencias generales</a></li>
                    </ul>
                </li>
            </ul>
            <form class="d-flex mt-3" role="search">
                <input class="form-control me-2" type="search" placeholder="Buscar..." aria-label="Search">
                <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <!-- OFFCANVAS DERECHO (Perfil y Ajustes) -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasDerecho">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title"><i class="fas fa-user-cog me-2"></i>Opciones de Perfil</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <a href="#" class="d-block mb-3 text-decoration-none text-dark"><i class="fas fa-user me-2"></i>Perfil</a>
            <a href="#" class="d-block mb-3 text-decoration-none text-dark"><i class="fas fa-sliders-h me-2"></i>Configuración</a>
            <a href="#" class="d-block mb-3 text-decoration-none text-dark"><i class="fas fa-bell me-2"></i>Notificaciones</a>
            <hr>
            <a href="logout.php" class="d-block text-danger text-decoration-none"><i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión</a>
        </div>
    </div>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="container mt-5 pt-5">

        <!-- Mensaje de Bienvenida -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h3 class="text-primary m-0"><i class="fas fa-user-check me-2"></i>Bienvenida, <?= $usuario ?></h3>
                <p class="text-muted m-0 mt-1">Este es el panel principal del sistema SIPV.</p>
            </div>
        </div>

        <!-- Métrica / Indicadores (Tarjetas combinadas) -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="card card-metric p-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-shape bg-success me-3">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <small class="text-muted">Cumplimiento</small>
                            <h4 class="font-weight-bold m-0">%f300 mil</h4>
                            
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card card-metric p-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-shape bg-danger me-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <small class="text-muted">Al corriente</small>
                            <h4 class="font-weight-bold m-0">3.1M</h4>
                            
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card card-metric bg-white p-3">
                    <div class="d-flex align-items-center">
                        <img width="60" height="60" src="r (2).gif" alt="expired"/>
                        <div>
                            <small class="text-white-50">Por vencer < 15 dias</small>
                            <h4 class="font-weight-bold m-0">1022</h4>
                            
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card card-metric p-3">
                    <div class="d-flex align-items-center">
                        <img width="48" height="48" src="https://img.icons8.com/fluency/48/expired.png" alt="expired"/>
                        <div>
                            <small class="text-muted">Vencidos</small>
                            <h4 class="font-weight-bold m-0">8%</h4>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos y Tablas -->
        <div class="row g-4">
            <div class="col-lg-6 col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i>Estado de equipos</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="modelsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- JS de Bootstrap 5 y Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="graficos/app.js"></script>

</body>
</html>