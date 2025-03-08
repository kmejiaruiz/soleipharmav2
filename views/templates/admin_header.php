<!DOCTYPE html>
<html lang="es">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta charset="UTF-8">
    <title>Panel Administrativo - Mi Tienda Online</title>
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/css/adminlte.min.css">
    <!-- Estilos personalizados (opcional) -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="hold-transition sidebar-mini">
    <!-- Loading Wrapper -->
    <div id="loading-wrapper" style="
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: #fff;
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease-out;">
        <div style="font-size: 24px; color: #000;">
            <i class="fas fa-spinner fa-spin"></i> Cargando...
        </div>
    </div>
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-dark" style="background-color: #4B0082;">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="index.php" class="nav-link">Inicio</a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <span class="nav-link">Bienvenido, <?= htmlspecialchars($_SESSION['user']['username']) ?></span>
                </li>
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a href="index.php?controller=auth&action=logout" class="nav-link"><i
                                class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </li>
                </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4" style="background-color: #4B0082;">
            <!-- Brand Logo -->
            <a href="index.php" class="brand-link">
                <img src="http://soleipharma.ct.ws/images/logo.jpg" alt="Logo"
                    class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light">Farmacia Solei</span>
            </a>
            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item">
                            <a href="index.php?controller=admin&action=index" class="nav-link">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="index.php?controller=admin&action=salesReport" class="nav-link">
                                <i class="nav-icon fas fa-chart-line"></i>
                                <p>Reporte de Ventas</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="index.php?controller=admin&action=inventory" class="nav-link">
                                <i class="nav-icon fas fa-warehouse"></i>
                                <p>Inventario</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="index.php?controller=admin&action=invoice" class="nav-link">
                                <i class="nav-icon fas fa-file-invoice"></i>
                                <p>Generar Facturas/Boletas</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="index.php?controller=notes&action=index" class="nav-link">
                                <i class="nav-icon fas fa-sticky-note"></i>
                                <p>Notas de Crédito/Débito</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="index.php?controller=admin&action=bulkUpload" class="nav-link">
                                <i class="nav-icon fas fa-file-excel"></i>
                                <p>Carga Masiva de Productos</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="index.php?controller=carousel&action=index" class="nav-link">
                                <i class="nav-icon fas fa-images"></i>
                                <p>Gestionar Carousel</p>
                            </a>
                        </li>
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">