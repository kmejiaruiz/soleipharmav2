<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">

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
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <!-- Estilos personalizados (opcional) -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/loader.css">

    <!-- Micromodal CSS base -->
    <style>
        .modal {
            font-family: -apple-system, BlinkMacSystemFont, avenir next, avenir, helvetica neue, helvetica, ubuntu, roboto, noto, segoe ui, arial, sans-serif;
        }
        .modal__overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 99999;
        }
        .modal__container {
            background-color: #fff;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 100vh;
            border-radius: 4px;
            overflow-y: auto;
            box-sizing: border-box;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.2);
        }
        .modal__header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal__title {
            margin-top: 0; margin-bottom: 0;
            font-weight: 600; font-size: 1.25rem; line-height: 1.25;
            color: #343a40; box-sizing: border-box;
        }
        .modal__close {
            background: transparent; border: 0; margin: 0; padding: 0; cursor: pointer;
        }
        .modal__header .modal__close:before { content: "\2715"; font-size: 20px; color: #999; }
        .modal__content {
            margin-bottom: 25px; line-height: 1.5; color: #495057;
        }
        .modal__btn {
            font-size: .875rem; padding: 0.5rem 1rem; border-radius: 4px;
            cursor: pointer; font-weight: 500; border: none;
        }
        .modal__btn-primary { background-color: #343a40; color: #fff; }
        .modal__btn-primary:focus, .modal__btn-primary:hover { background-color: #111111; }
        
        /* Icon styles for unified alert */
        .modal-icon { width: 50px; height: 50px; margin: 0 auto 15px; display: block; }
        .icon-success { color: #28a745; }
        .icon-error { color: #dc3545; }
        .icon-warning { color: #ffc107; }
        .icon-info { color: #17a2b8; }
        
        /* Input styles for sweetalert-like prompts */
        .micromodal-input {
            width: 100%; padding: 10px; margin-top: 10px;
            border: 1px solid #dee2e6; border-radius: 4px;
            box-sizing: border-box; font-size: 14px;
        }
        .micromodal-input:focus { border-color: #343a40; outline: none; }
        .micromodal-validation { color: #dc3545; font-size: 13px; margin-top: 8px; display: none; text-align: left; }
        
        /* Animations */
        @keyframes mmfadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes mmfadeOut { from { opacity: 1; } to { opacity: 0; } }
        @keyframes mmslideIn { from { transform: translateY(15%); } to { transform: translateY(0); } }
        @keyframes mmslideOut { from { transform: translateY(0); } to { transform: translateY(-10%); } }
        .micromodal-slide { display: none; }
        .micromodal-slide.is-open { display: block; }
        .micromodal-slide[aria-hidden="false"] .modal__overlay { animation: mmfadeIn .3s cubic-bezier(0.0, 0.0, 0.2, 1); }
        .micromodal-slide[aria-hidden="false"] .modal__container { animation: mmslideIn .3s cubic-bezier(0, 0, .2, 1); }
        .micromodal-slide[aria-hidden="true"] .modal__overlay { animation: mmfadeOut .3s cubic-bezier(0.0, 0.0, 0.2, 1); }
        .micromodal-slide[aria-hidden="true"] .modal__container { animation: mmslideOut .3s cubic-bezier(0, 0, .2, 1); }
        .micromodal-slide .modal__container, .micromodal-slide .modal__overlay { will-change: transform; }

        /* Minimalist Bootstrap Modal Overrides */
        .modal-content {
            border: none;
            border-radius: 4px;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.2);
        }
        .modal-header {
            border-bottom: none;
        }
        .modal-title {
            font-weight: 600;
            color: #343a40;
        }
        .modal-footer {
            border-top: none;
        }
        
        /* Minimalist Bootstrap Buttons inside Modals */
        .modal-footer .btn-primary {
            background-color: #343a40;
            border-color: #343a40;
            color: #fff;
        }
        .modal-footer .btn-primary:hover, .modal-footer .btn-primary:focus {
            background-color: #111111;
            border-color: #111111;
        }
        .modal-footer .btn-secondary {
            background-color: transparent;
            color: #343a40;
            border: 1px solid #dee2e6;
        }
        .modal-footer .btn-secondary:hover, .modal-footer .btn-secondary:focus {
            background-color: #f8f9fa;
            color: #111111;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini">

    <!-- Loader Overlay (al inicio de <body>) -->
    <div id="loading-wrapper" style="
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.4);
        z-index: 9999;
        opacity: 1; visibility: visible;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    ">
        <!-- Top Progress Bar -->
        <div class="top-loader-bar"></div>
    </div>
    <style>
        .top-loader-bar {
            position: absolute;
            top: 0;
            left: 0;
            width: 0%;
            height: 4px;
            background-color: #495057; /* Sleek medium-dark gray */
            box-shadow: 0 0 8px rgba(73, 80, 87, 0.4);
            animation: loadProgress 1.5s ease-out infinite;
            transform-origin: left;
        }

        @keyframes loadProgress {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; opacity: 0; }
        }
    </style>

    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-light" style="background-color: #ffffff; border-bottom: 1px solid #dee2e6;">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="index.php" class="nav-link">Inicio</a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <!-- Profile Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="gap: 8px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6f42c1,#343a40);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;flex-shrink:0;">
                            <?= strtoupper(substr($_SESSION['user']['first_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span class="d-none d-md-inline" style="font-weight:500;color:#343a40;">
                            <?= htmlspecialchars($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']) ?>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow-sm" aria-labelledby="profileDropdown" style="border:none;border-radius:8px;min-width:200px;padding:8px 0;">
                        <div class="px-3 py-2 mb-1" style="border-bottom:1px solid #f1f1f1;">
                            <small class="text-muted d-block" style="font-size:11px;">Sesión activa como</small>
                            <span class="font-weight-bold" style="font-size:13px;color:#343a40;"><?= strtoupper(htmlspecialchars($_SESSION['user']['role'] ?? '')) ?></span>
                        </div>
                        <a class="dropdown-item" href="/soleipharmav2/admin/myProfile" style="padding:8px 16px;">
                            <i class="fas fa-user-circle mr-2" style="color:#6f42c1;width:16px;"></i> Mi Perfil
                        </a>
                        <a class="dropdown-item" href="#" onclick="manualLockSession()" style="padding:8px 16px;">
                            <i class="fas fa-lock mr-2" style="color:#ffc107;width:16px;"></i> Bloquear Sesión
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="/soleipharmav2/auth/logout" style="padding:8px 16px;">
                            <i class="fas fa-sign-out-alt mr-2" style="width:16px;"></i> Cerrar Sesión
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4" style="background-color: #343a40;"> <!-- Deep charcoal gray -->
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
                            <a href="/soleipharmav2/admin/index" class="nav-link">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/soleipharmav2/admin/salesReport" class="nav-link">
                                <i class="nav-icon fas fa-chart-line"></i>
                                <p>Reporte de Ventas</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/soleipharmav2/admin/inventory" class="nav-link">
                                <i class="nav-icon fas fa-warehouse"></i>
                                <p>Inventario</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                                <a href="/soleipharmav2/discard/create" class="nav-link">
                                    <i class="nav-icon fas fa-trash-alt"></i>
                                    <p>Solicitar Descarte</p>
                                </a>
                            <?php endif; ?>
                        </li>
                        <li class="nav-item">
                            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                                <a href="/soleipharmav2/discard/myHistory" class="nav-link">
                                    <i class="nav-icon fas fa-folder-open"></i>
                                    <p>Mis Descartes</p>
                                </a>
                            <?php endif; ?>
                        </li>
                        <li class="nav-item">
                            <?php if ($_SESSION['user']['role'] === 'superadmin'): ?>
                                <a href="/soleipharmav2/discard/listPending" class="nav-link">
                                    <i class="nav-icon fas fa-hourglass-half"></i>
                                    <p>Solicitudes Pendientes</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/soleipharmav2/admin/lockedUsers" class="nav-link bg-danger text-white mt-1 mb-1" style="border-radius: .25rem;">
                                    <i class="nav-icon fas fa-user-lock"></i>
                                    <p>Usuarios Bloqueados</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/soleipharmav2/admin/manageRoles" class="nav-link bg-indigo text-white mt-1 mb-1" style="border-radius: .25rem;">
                                    <i class="nav-icon fas fa-user-shield"></i>
                                    <p>Gestión de Usuarios</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/soleipharmav2/discard/history" class="nav-link">
                                    <i class="nav-icon fas fa-history"></i>
                                    <p>Historial de Descartes</p>
                                </a>
                            </li>
                        <?php endif; ?>

                        <li class="nav-item">
                            <a href="/soleipharmav2/order/index" class="nav-link">
                                <i class="nav-icon fas fa-shopping-cart"></i>
                                <p>Pedidos de Productos</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/soleipharmav2/supplier/index" class="nav-link">
                                <i class="nav-icon fas fa-truck"></i>
                                <p>Proveedores</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="/soleipharmav2/carousel/index" class="nav-link">
                                <i class="nav-icon fas fa-images"></i>
                                <p>Gestionar Carousel</p>
                            </a>
                        </li>
                        <!-- Inventario -->
                        <li class="nav-item" style="margin-top: 8px;">
                            <a href="/soleipharmav2/inventory/movements" class="nav-link" style="border-top:1px solid rgba(255,255,255,0.07);padding-top:12px;">
                                <i class="nav-icon fas fa-exchange-alt"></i>
                                <p>Movimientos de Inventario</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/soleipharmav2/inventory/report" class="nav-link">
                                <i class="nav-icon fas fa-print"></i>
                                <p>Reporte de Bodega</p>
                            </a>
                        </li>
                        <!-- Mi Perfil -->
                        <li class="nav-item" style="margin-top: 8px;">
                            <a href="/soleipharmav2/admin/myProfile" class="nav-link" style="border-top:1px solid rgba(255,255,255,0.07);padding-top:12px;">
                                <i class="nav-icon fas fa-user-circle" style="color:#a78bda;"></i>
                                <p>Mi Perfil</p>
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