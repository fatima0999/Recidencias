<?php
$usuario = "Fatima";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Administración Elegante</title>

    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome 5 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; overflow-x: hidden; }
        .wrapper { display: flex; width: 100%; min-height: 100vh; }
        
        /* Sidebar styles */
        #sidebar { min-width: 260px; max-width: 260px; background: #2b303e; color: #fff; transition: all 0.3s ease; }
        #sidebar.active { margin-left: -260px; }
        #sidebar .sidebar-header { padding: 20px; background: #0f4c81; text-align: center; }
        #sidebar .profile-section { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        #sidebar .profile-img { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; }
        #sidebar ul.components { padding: 15px 0; }
        #sidebar ul li a { padding: 12px 20px; font-size: 0.9em; display: block; color: #a6b0cf; text-decoration: none; }
        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: #232733; }
        #sidebar ul li a i { margin-right: 10px; width: 20px; text-align: center; }

        /* Content styles */
        #content { width: 100%; transition: all 0.3s ease; }
        .top-navbar { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 10px 25px; }
        .search-box { border-radius: 20px; background: #f0f2f5; border: none; padding-left: 15px; }
        
        /* Metric Cards */
        .card-metric { border: none; border-radius: 4px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .icon-shape { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.2rem; }
        .bg-blue-accent { background-color: #0d47a1; color: white; }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h5 class="m-0 font-weight-bold"><i class="fas fa-rocket mr-2"></i>Administración elegante</h5>
        </div>

        <div class="profile-section">
            <img src="https://via.placeholder.com/150" alt="Usuario" class="profile-img">
            <h6 class="m-0 font-weight-bold text-white"><?php echo $usuario; ?></h6>
            <small class="text-info">Diseñador de UI/UX</small>
        </div>

        <ul class="list-unstyled components">
            <li class="active">
                <a href="#panelSubmenu" data-toggle="collapse" aria-expanded="true" class="dropdown-toggle d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-tachometer-alt"></i> Panel</span>
                </a>
                <ul class="collapse show list-unstyled pl-3" id="panelSubmenu">
                    <li><a href="#">&rsaquo; Por defecto</a></li>
                    <li><a href="#">&rsaquo; Analítica</a></li>
                    <li><a href="#">&rsaquo; Comercio electrónico</a></li>
                    <li><a href="#">&rsaquo; Criptomoneda</a></li>
                </ul>
            </li>
            <li><a href="#"><i class="fas fa-cubes"></i> Widget</a></li>
            <li><a href="#"><i class="fas fa-layer-group"></i> Elementos de interfaz</a></li>
            <li><a href="#"><i class="fas fa-edit"></i> Elementos de formulario</a></li>
            <li><a href="#"><i class="fas fa-file-alt"></i> Editores de texto</a></li>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <!-- Navbar Superior -->
        <nav class="navbar navbar-expand-lg top-navbar">
            <div class="container-fluid p-0">
                <!-- Botón de Hamburguesa -->
                <button type="button" id="sidebarCollapse" class="btn text-dark shadow-none">
                    <i class="fas fa-bars fa-lg"></i>
                </button>

                <div class="d-flex align-items-center">
                    <a href="#" class="text-dark mr-3 position-relative">
                        <i class="fas fa-bell"></i>
                        <span class="badge badge-danger badge-pill position-absolute" style="top: -8px; right: -8px;">5</span>
                    </a>
                    <a href="#" class="text-dark mr-3 position-relative">
                        <i class="fas fa-envelope"></i>
                        <span class="badge badge-danger badge-pill position-absolute" style="top: -8px; right: -8px;">4</span>
                    </a>
                    <a href="#" class="text-dark mr-4"><i class="fas fa-th-large"></i></a>

                    <div class="form-inline mr-3">
                        <input class="form-control search-box" type="search" placeholder="Introduzca palabras clave...">
                    </div>

                    <img src="https://via.placeholder.com/150" alt="Avatar" class="rounded-circle" width="35" height="35">
                </div>
            </div>
        </nav>

        <!-- Main Dashboard Content -->
        <div class="container-fluid p-4">
            <h3 class="mb-4">Panel</h3>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card card-metric p-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-shape bg-primary mr-3">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h3 class="font-weight-bold m-0">$300 mil</h3>
                                <small class="text-muted">Ingresos totales</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="card card-metric p-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-shape bg-danger mr-3">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <h3 class="font-weight-bold m-0">3,1 millones</h3>
                                <small class="text-muted">Clientes</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="card card-metric bg-blue-accent p-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-shape bg-white text-primary mr-3">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div>
                                <h3 class="font-weight-bold m-0">1022</h3>
                                <small class="text-white-50">Productos totales</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts requeridos -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script para alternar el menú lateral -->
<script>
    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
        });
    });
</script>

</body>
</html>