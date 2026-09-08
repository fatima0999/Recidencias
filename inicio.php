<?php

session_start();

if(!isset($_SESSION['id_usuario'])){
    header("Location: index.php");
    exit();
}

?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <title>SIPV</title>
</head>

<body>
<nav class="navbar bg-light fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Offcanvas navbar</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
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
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Mantenimientos
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#">Action</a></li>
              <li><a class="dropdown-item" href="#">Another action</a></li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li><a class="dropdown-item" href="#">Something else here</a></li>
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
                <h3 class="text-primary">Bienvenida, usuario #<?= $_SESSION['id_usuario']; ?></h3>
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

    <div class="card">

        <div class="card-body">

            <h3 class="text-primary">
                Estado de Extintores
            </h3>


            <div id="chart"></div>


        </div>

    </div>

</div>

              
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>