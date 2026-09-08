<?php
// Aquí van las conexiones, sesiones o variables
$usuario = "Fatima";
?>

<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="bontnes.css">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <title>SIPV</title>
</head>

<body>
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
        </div>
    </div>
    </div>
    </div>
        <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNavbar">
      <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasNavbarLabel">SIPV</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
        <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
          <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="#">Dashboard</a>
          </li>
          <li class="nav-item">
          <a class="nav-link" href="calendario/index.html">Calendario</a>
          </li>
          <li class="nav-item">
          <a class="nav-link" href="activos/activos.php">Activos</a>
          </li>
          <li class="nav-item">
          <a class="nav-link" href="">planos</a>
          </li>
          <li class="nav-item">
          <a class="nav-link" href="calendario/index.html">Alertas</a>
          </li>
         </li>
          <li class="nav-item">
          <a class="nav-link" href="calendario/index.html"> Mantenimientos</a>
          </li>
          <li class="nav-item">
          <a class="nav-link" href="calendario/index.html">Reportes</a>
          </li>
          <li class="nav-item">
          <a class="nav-link" href="calendario/index.html">Usuarios</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">configuracion
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#">R</a></li>
              <li>
             <li><a class="dropdown-item" href="#">Us</a></li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li><a class="dropdown-item" href="#"></a></li>
            </ul>
          </li>
        </ul>
        <form class="d-flex" role="search">
          <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
          <button class="btn btn-outline-success" type="submit">Search</button>
        </form>
      </div>
    </div>
  </div>
</nav>
<p>

</p>
<p>
  
</p>
    <div class="container mt-5 pt-5">
        <div class="card">
            <div class="card-body">
                <h3 class="text-primary"> Bienvenida <?= $usuario ?></h3>
                    <p> Este es el panel principal del sistema SIPV. </p>
                       </div>
                    </div>
                </div>
                <p>

                </p>
<div class="container mt-5">
    <div class="row g-3">
        <!-- Rectángulo 1 -->
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>Rectángulo 1</h5>
                </div>
            </div>
        </div>

        <!-- Rectángulo 2 -->
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>Rectángulo 2</h5>
                </div>
            </div>
        </div>

        <!-- Rectángulo 3 -->
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>Rectángulo 3</h5>
                </div>
            </div>
        </div>

        <!-- Rectángulo 4 -->
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>Rectángulo 4</h5>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container mt-5">
<div class="row g-5">
<div class="col-lg-6 col-md-6">
<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Estado de equipos</h5>
    </div>
    <div class="card-body">
        <canvas id="modelsChart"></canvas>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="graficos/app.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>