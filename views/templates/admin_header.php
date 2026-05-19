<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Validación de sucursal: detecta usuario de otra instancia ─────────────────
// Si BRANCH está configurado y el usuario tiene branch, deben coincidir.
if (
    defined('BRANCH') && BRANCH !== ''
    && isset($_SESSION['user']['id'])
    && isset($_SESSION['user']['branch'])
    && $_SESSION['user']['branch'] !== BRANCH
) {
    // Capturar datos ANTES de destruir la sesión
    $_bm_userName  = htmlspecialchars($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']);
    $_bm_userBr    = htmlspecialchars($_SESSION['user']['branch']);
    $_bm_thisBr    = htmlspecialchars(BRANCH);
    $_bm_loginUrl  = defined('APP_BASE') ? APP_BASE : '';

    // Destruir sesión server-side inmediatamente
    session_unset();
    session_destroy();

    // Emitir página standalone con MicroModal de error
    header('Content-Type: text/html; charset=UTF-8');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Acceso incorrecto — SoleiPharma</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ── Reset & base ───────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #111111;
            min-height: 100vh;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        /* ── MicroModal core ────────────────────────────────────── */
        .micromodal-slide { display: none; }
        .micromodal-slide.is-open { display: block; }
        .micromodal-slide[aria-hidden="false"] .modal__overlay  { animation: mmfadeIn  .28s cubic-bezier(0,0,.2,1); }
        .micromodal-slide[aria-hidden="false"] .modal__container { animation: mmslideIn .28s cubic-bezier(0,0,.2,1); }
        .micromodal-slide[aria-hidden="true"]  .modal__overlay  { animation: mmfadeOut  .28s cubic-bezier(0,0,.2,1); }
        .micromodal-slide[aria-hidden="true"]  .modal__container { animation: mmslideOut .28s cubic-bezier(0,0,.2,1); }
        .micromodal-slide .modal__container,
        .micromodal-slide .modal__overlay { will-change: transform; }
        @keyframes mmfadeIn   { from { opacity:0 } to { opacity:1 } }
        @keyframes mmfadeOut  { from { opacity:1 } to { opacity:0 } }
        @keyframes mmslideIn  { from { transform:translateY(-18px); opacity:0 } to { transform:translateY(0); opacity:1 } }
        @keyframes mmslideOut { from { transform:translateY(0); opacity:1 } to { transform:translateY(-18px); opacity:0 } }

        /* ── Overlay ────────────────────────────────────────────── */
        .modal__overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,.65);
            display: flex; align-items: center; justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(3px);
        }

        /* ── Container ──────────────────────────────────────────── */
        .modal__container {
            background: #ffffff;
            border-radius: 6px;
            max-width: 420px;
            width: 92%;
            box-shadow: 0 20px 60px rgba(0,0,0,.5), 0 0 0 1px rgba(255,255,255,.04);
            overflow: hidden;
        }

        /* ── Header — negro corporativo ─────────────────────────── */
        .modal__header {
            background: #1a1a1a;
            color: #fff;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 3px solid #333;
        }
        .modal__header-icon {
            width: 36px; height: 36px;
            background: rgba(255,255,255,.08);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: .95rem;
        }
        .modal__title {
            color: #fff;
            font-size: .95rem;
            font-weight: 700;
            letter-spacing: .3px;
            margin: 0;
            text-transform: uppercase;
        }

        /* ── Content ────────────────────────────────────────────── */
        .modal__content {
            padding: 28px 28px 20px;
        }
        .modal__content .info-block {
            background: #f7f7f7;
            border: 1px solid #e4e4e4;
            border-radius: 5px;
            padding: 16px 18px;
            margin-bottom: 18px;
            font-size: .92rem;
            color: #1a1a1a;
            line-height: 1.7;
        }
        .modal__content .info-block strong {
            color: #000;
            font-weight: 700;
        }
        .modal__content .info-block .label {
            color: #888;
            font-size: .78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .6px;
            display: block;
            margin-bottom: 3px;
        }
        .modal__content .info-row {
            display: flex; align-items: flex-start; gap: 8px;
            padding: 6px 0;
            border-bottom: 1px solid #eee;
        }
        .modal__content .info-row:last-child { border-bottom: none; }
        .modal__content .info-row .ri { color: #999; width: 14px; flex-shrink:0; margin-top:3px; }
        .modal__note {
            font-size: .82rem;
            color: #666;
            text-align: center;
            line-height: 1.6;
            padding-top: 4px;
        }
        .modal__note i { margin-right: 4px; color: #999; }

        /* ── Footer ─────────────────────────────────────────────── */
        .modal__footer {
            padding: 16px 24px;
            background: #f5f5f5;
            border-top: 1px solid #e4e4e4;
            display: flex;
            justify-content: center;
        }
        .modal__btn {
            background: #1a1a1a;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 11px 32px;
            font-size: .9rem;
            font-weight: 600;
            letter-spacing: .3px;
            cursor: pointer;
            transition: background .15s;
            min-width: 200px;
            font-family: inherit;
        }
        .modal__btn:hover { background: #333; }
    </style>
</head>
<body>

<!-- MicroModal de error de sucursal -->
<div class="modal micromodal-slide" id="modalBranchError" aria-hidden="true">
  <div class="modal__overlay" tabindex="-1">
    <div class="modal__container" role="alertdialog" aria-modal="true" aria-labelledby="modalBranchErrorTitle">

      <!-- Header negro corporativo -->
      <div class="modal__header">
        <div class="modal__header-icon">
          <i class="fas fa-shield-alt"></i>
        </div>
        <h5 class="modal__title" id="modalBranchErrorTitle">Acceso Incorrecto &mdash; Sucursal</h5>
      </div>

      <!-- Cuerpo -->
      <div class="modal__content">
        <div class="info-block">
          <div class="info-row">
            <i class="fas fa-user ri"></i>
            <div>
              <span class="label">Usuario</span>
              <strong><?= $_bm_userName ?></strong>
            </div>
          </div>
          <div class="info-row">
            <i class="fas fa-building ri"></i>
            <div>
              <span class="label">Sucursal asignada</span>
              <strong><?= $_bm_userBr ?></strong>
            </div>
          </div>
          <div class="info-row">
            <i class="fas fa-ban ri"></i>
            <div>
              <span class="label">Instancia accedida</span>
              <strong><?= $_bm_thisBr ?></strong>
            </div>
          </div>
        </div>
        <p class="modal__note">
          <i class="fas fa-lock"></i>
          La sesión ha sido cerrada por seguridad.<br>
          Ingrese con un usuario válido para esta sucursal.
        </p>
      </div>

      <!-- Footer -->
      <div class="modal__footer">
        <button id="btnBranchErrorOk" class="modal__btn">
          <i class="fas fa-sign-in-alt"></i>&nbsp; Ir al Inicio de Sesión
        </button>
      </div>

    </div>
  </div>
</div>

<script src="https://unpkg.com/micromodal/dist/micromodal.min.js"></script>
<script>
(function() {
    var loginUrl = '<?= $_bm_loginUrl ?>';
    document.addEventListener('DOMContentLoaded', function() {
        MicroModal.init();
        MicroModal.show('modalBranchError', {
            disableScroll: true,
            disableFocus: false,
            // No permitir cerrar con Esc ni click en overlay — el usuario DEBE hacer clic en el botón
            onClose: function() { window.location.replace(loginUrl); }
        });
        document.getElementById('btnBranchErrorOk').addEventListener('click', function() {
            window.location.replace(loginUrl);
        });
    });
})();
</script>

</body>
</html>
<?php
    exit();
}
$_branchMismatch = false;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <!-- Anti-FOUC: aplica el tema ANTES de renderizar cualquier estilo -->
    <script>!function(){var t=localStorage.getItem('solei_theme');if(t)document.documentElement.setAttribute('data-theme',t);}();</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">

    <meta charset="UTF-8">
    <title>Panel Administrativo - Mi Tienda Online</title>
    <!-- NProgress -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.min.css">
    <script src="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.min.js"></script>
    <script>
        NProgress.configure({ showSpinner: false, trickleSpeed: 80, minimum: 0.08 });
    </script>
    <style>
        /* NProgress — por encima de todo, incluida la navbar de AdminLTE */
        #nprogress               { pointer-events: none; }
        #nprogress .bar          {
            background: #6f42c1 !important;
            position: fixed !important;
            z-index: 999999 !important;
            top: 0; left: 0;
            width: 100%; height: 3px;
        }
        #nprogress .peg {
            box-shadow: 0 0 10px #6f42c1, 0 0 5px #6f42c1 !important;
            right: 0; width: 100px; height: 100%;
            position: absolute; opacity: 1.0;
        }
    </style>
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
    <?php $css_v = filemtime(__DIR__ . '/../../assets/css/style.css'); ?>
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/style.css?v=<?= $css_v ?>">
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/loader.css">
    <!-- UX: APP_BASE disponible para JS -->
    <script>window.SOLEI_APP_BASE = '<?= APP_BASE ?>';</script>

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
    <!-- NProgress: iniciar aquí, después de que body existe -->
    <script>NProgress.start();</script>

    <!-- Skip to main content (accesibilidad) -->
    <a href="#main-content" class="skip-link">Saltar al contenido principal</a>



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

            <!-- ── BÚSQUEDA GLOBAL — Trigger Button (Ctrl+K) ───────── -->
            <button
                id="globalSearchTrigger"
                type="button"
                class="d-none d-md-flex align-items-center"
                onclick="window.GlobalSearch && window.GlobalSearch.open()"
                aria-label="Abrir búsqueda global (Ctrl+K)"
                style="
                    gap: 8px;
                    background: #f8f9fa;
                    border: 1.5px solid #dee2e6;
                    border-radius: 20px;
                    padding: 5px 14px 5px 12px;
                    color: #6c757d;
                    font-size: 0.82rem;
                    cursor: pointer;
                    transition: border-color 0.2s, box-shadow 0.2s;
                    white-space: nowrap;
                "
                onmouseover="this.style.borderColor='#6f42c1'; this.style.boxShadow='0 0 0 3px rgba(111,66,193,0.10)'"
                onmouseout="this.style.borderColor='#dee2e6'; this.style.boxShadow='none'"
            >
                <i class="fas fa-search" style="font-size:11px;"></i>
                <span>Buscar módulo...</span>
                <kbd style="
                    background: #e9ecef;
                    border-radius: 5px;
                    padding: 1px 6px;
                    font-size: 10px;
                    color: #495057;
                    font-family: monospace;
                    margin-left: 4px;
                ">Ctrl+K</kbd>
            </button>
            <!-- ── FIN BÚSQUEDA GLOBAL ─────────────────────────────── -->

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
                        <a class="dropdown-item" href="<?= APP_BASE ?>/admin/myProfile" style="padding:8px 16px;">
                            <i class="fas fa-user-circle mr-2" style="color:#6f42c1;width:16px;"></i> Mi Perfil
                        </a>
                        <a class="dropdown-item" href="#" onclick="manualLockSession()" style="padding:8px 16px;">
                            <i class="fas fa-lock mr-2" style="color:#ffc107;width:16px;"></i> Bloquear Sesión
                        </a>
                        <!-- ── MODO OSCURO TOGGLE ─────────────────────── -->
                        <div
                            class="dropdown-item d-flex align-items-center justify-content-between"
                            style="padding:8px 16px; cursor:pointer;"
                            onclick="window.DarkMode && window.DarkMode.toggle(); return false;"
                            id="darkModeToggleItem"
                        >
                            <span>
                                <i class="fas fa-moon mr-2" id="darkModeNavIcon" style="color:#6f42c1;width:16px;"></i>
                                <span id="darkModeNavLabel">Modo Oscuro</span>
                            </span>
                            <div class="dark-mode-switch" id="darkModeSwitch">
                                <div class="dark-mode-switch-thumb"></div>
                            </div>
                        </div>
                        <!-- ── FIN MODO OSCURO TOGGLE ─────────────────── -->
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="<?= APP_BASE ?>/auth/logout" style="padding:8px 16px;">
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
                    <?php $userRole = $_SESSION['user']['role'] ?? ''; ?>
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

                        <?php if ($userRole !== 'cajero'): ?>
                        <li class="nav-item">
                            <a href="<?= APP_BASE ?>/admin/index" class="nav-link">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= APP_BASE ?>/admin/salesReport" class="nav-link">
                                <i class="nav-icon fas fa-chart-line"></i>
                                <p>Reporte de Ventas</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= APP_BASE ?>/admin/inventory" class="nav-link">
                                <i class="nav-icon fas fa-warehouse"></i>
                                <p>Inventario</p>
                            </a>
                        </li>

                        <?php if ($userRole === 'admin'): ?>
                        <li class="nav-item">
                            <a href="<?= APP_BASE ?>/discard/create" class="nav-link">
                                <i class="nav-icon fas fa-trash-alt"></i>
                                <p>Solicitar Descarte</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= APP_BASE ?>/discard/myHistory" class="nav-link">
                                <i class="nav-icon fas fa-folder-open"></i>
                                <p>Mis Descartes</p>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if ($userRole === 'superadmin'): ?>
                        <li class="nav-item">
                            <a href="<?= APP_BASE ?>/discard/listPending" class="nav-link">
                                <i class="nav-icon fas fa-hourglass-half"></i>
                                <p>Solicitudes Pendientes</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= APP_BASE ?>/admin/lockedUsers" class="nav-link bg-danger text-white mt-1 mb-1" style="border-radius: .25rem;">
                                <i class="nav-icon fas fa-user-lock"></i>
                                <p>Usuarios Bloqueados</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= APP_BASE ?>/admin/manageRoles" class="nav-link bg-indigo text-white mt-1 mb-1" style="border-radius: .25rem;">
                                <i class="nav-icon fas fa-user-shield"></i>
                                <p>Gestión de Usuarios</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= APP_BASE ?>/discard/history" class="nav-link">
                                <i class="nav-icon fas fa-history"></i>
                                <p>Historial de Descartes</p>
                            </a>
                        </li>
                        <?php endif; ?>

                        <li class="nav-item">
                            <a href="<?= APP_BASE ?>/order/index" class="nav-link">
                                <i class="nav-icon fas fa-shopping-cart"></i>
                                <p>Pedidos de Productos</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= APP_BASE ?>/supplier/index" class="nav-link">
                                <i class="nav-icon fas fa-truck"></i>
                                <p>Proveedores</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= APP_BASE ?>/carousel/index" class="nav-link">
                                <i class="nav-icon fas fa-images"></i>
                                <p>Gestionar Carousel</p>
                            </a>
                        </li>
                        <!-- Inventario -->
                        <li class="nav-item" style="margin-top: 8px;">
                            <a href="<?= APP_BASE ?>/inventory/movements" class="nav-link" style="border-top:1px solid rgba(255,255,255,0.07);padding-top:12px;">
                                <i class="nav-icon fas fa-exchange-alt"></i>
                                <p>Movimientos de Inventario</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= APP_BASE ?>/inventory/report" class="nav-link">
                                <i class="nav-icon fas fa-print"></i>
                                <p>Reporte de Bodega</p>
                            </a>
                        </li>

                        <!-- Gestión de Bodegas -->
                        <li class="nav-item has-treeview">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-warehouse"></i>
                                <p>
                                    Bodegas
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview" style="padding-left:8px;">
                                <li class="nav-item">
                                    <a href="<?= APP_BASE ?>/bodega/stock" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Ver Stock por Bodega</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= APP_BASE ?>/bodega/transfer" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Registrar Traslado</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= APP_BASE ?>/bodega/history" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Historial de Traslados</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <!-- Traslados entre Sucursales -->
                        <?php if (in_array($userRole, ['admin', 'superadmin'])): ?>
                        <li class="nav-item">
                            <a href="<?= APP_BASE ?>/branchTransfer/index" class="nav-link">
                                <i class="nav-icon fas fa-random"></i>
                                <p>
                                    Traslados entre Sucursales
                                    <?php
                                        // Badge con traslados pendientes para esta sucursal
                                        try {
                                            global $pdo;
                                            $branch = defined('BRANCH') ? BRANCH : '';
                                            if ($branch && $pdo) {
                                                $stBT = $pdo->prepare("SELECT COUNT(*) FROM branch_transfers WHERE to_branch = ? AND status = 'pendiente'");
                                                $stBT->execute([$branch]);
                                                $btCount = (int)$stBT->fetchColumn();
                                                if ($btCount > 0) {
                                                    echo "<span class=\"badge badge-warning float-right\" style=\"font-size:9px;\">{$btCount}</span>";
                                                }
                                            }
                                        } catch (Exception $e) {}
                                    ?>
                                </p>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php endif; /* fin bloque no-cajero */?>

                        <!-- Caja — solo para cajero, admin, superadmin -->
                        <?php if (in_array($userRole, ['cajero', 'admin', 'superadmin'])):
                            try {
                                global $pdo;
                                $currentUserId = $_SESSION['user']['id'] ?? 0;
                                $csOpen = $pdo->prepare("SELECT id FROM cash_sessions WHERE status='open' AND opened_by=? LIMIT 1");
                                $csOpen->execute([$currentUserId]);
                                $csOpen = $csOpen->fetch();
                            } catch (Exception $e) { $csOpen = false; }
                        ?>
                        <li class="nav-item" style="margin-top:8px;">
                            <a href="<?= APP_BASE ?>/cash/index" class="nav-link" style="border-top:1px solid rgba(255,255,255,0.07);padding-top:12px;">
                                <i class="nav-icon fas fa-cash-register" style="color:#28a745;"></i>
                                <p>
                                    Caja
                                    <?php if ($csOpen): ?>
                                    <span class="badge badge-success float-right" style="font-size:9px;">ABIERTA</span>
                                    <?php else: ?>
                                    <span class="badge badge-secondary float-right" style="font-size:9px;">CERRADA</span>
                                    <?php endif; ?>
                                </p>
                            </a>
                        </li>
                        <?php if (in_array($userRole, ['admin', 'superadmin'])): ?>
                        <li class="nav-item">
                            <a href="<?= APP_BASE ?>/cash/history" class="nav-link">
                                <i class="nav-icon fas fa-history" style="color:#6c9fd8;"></i>
                                <p>Historial de Caja</p>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a href="<?= APP_BASE ?>/cash/pos" class="nav-link">
                                <i class="nav-icon fas fa-receipt" style="color:#17a2b8;"></i>
                                <p>Facturar (POS)</p>
                            </a>
                        </li>
                        <?php endif; /* fin bloque caja */ ?>

                        <!-- Mi Perfil — oculto para cajero -->
                        <?php if ($userRole !== 'cajero'): ?>
                        <li class="nav-item" style="margin-top: 8px;">
                            <a href="<?= APP_BASE ?>/admin/myProfile" class="nav-link" style="border-top:1px solid rgba(255,255,255,0.07);padding-top:12px;">
                                <i class="nav-icon fas fa-user-circle" style="color:#a78bda;"></i>
                                <p>Mi Perfil</p>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->

        <div class="content-wrapper">