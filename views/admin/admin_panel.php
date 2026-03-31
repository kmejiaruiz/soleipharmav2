<!-- views/admin/admin_panel.php -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Dashboard</h1>

            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">


        <!-- Tarjetas de información y KPI -->
        <div class="row">
            <!-- Ventas del Día -->
            <div class="col-lg-4 col-12">
                <div class="small-box" style="background-color: #6f42c1; color: #fff;">
                    <div class="inner">
                        <h3>$<?= number_format($dailySales, 2) ?></h3>
                        <p>Ventas del Día</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <a href="/soleipharmav2/admin/salesReport" class="small-box-footer">
                        Más info <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- Top Producto Más Vendido -->
            <div class="col-lg-4 col-12">
                <div class="small-box" style="background-color: #007bff; color: #fff;">
                    <div class="inner">
                        <?php if (!empty($topProducts)):
                            $topProduct = $topProducts[0];
                            ?>
                            <h3 style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 1.5rem;" title="<?= htmlspecialchars($topProduct['name']) ?>">
                                <?= htmlspecialchars($topProduct['name']) ?>
                            </h3>
                            <p>Top Venta (<i class="fas fa-arrow-up"></i> <?= $topProduct['total_quantity'] ?> uds)</p>
                        <?php else: ?>
                            <h3>0</h3>
                            <p>No hay datos</p>
                        <?php endif; ?>
                    </div>
                    <div class="icon">
                        <i class="fas fa-thumbs-up"></i>
                    </div>
                    <a href="/soleipharmav2/admin/topProducts" class="small-box-footer">
                        Ver Top 10 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- Productos con Bajo Stock -->
            <div class="col-lg-4 col-12">
                <div class="small-box" style="background-color: #dc3545; color: #fff;">
                    <div class="inner">
                        <h3><?= isset($lowStockProducts) ? count($lowStockProducts) : 0 ?></h3>
                        <p>Productos con Bajo Stock</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <a href="/soleipharmav2/admin/lowStock" class="small-box-footer">
                        Más info <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Gráfico de Ventas de los últimos 7 días -->
        <?php if (!empty($last7DaysSales)): ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="card shadow-sm" style="border-radius: 12px; border-top: 4px solid #6f42c1;">
                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                        <h3 class="card-title text-muted font-weight-bold"><i class="fas fa-chart-area text-purple mr-1"></i> Tendencia de Ventas (Últimos 7 Días)</h3>
                    </div>
                    <div class="card-body">
                        <div style="height: 250px; width: 100%;">
                            <canvas id="salesTrendsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const ctx = document.getElementById('salesTrendsChart').getContext('2d');
                
                // Preparing data from PHP
                const rawData = <?= json_encode($last7DaysSales) ?>;
                const labels = Object.keys(rawData).map(date => {
                    const [y, m, d] = date.split('-');
                    return `${d}/${m}`;
                });
                const dataPoints = Object.values(rawData);
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Ventas ($)',
                            data: dataPoints,
                            borderColor: '#6f42c1',
                            backgroundColor: 'rgba(111, 66, 193, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.3,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#6f42c1',
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                intersect: false,
                                backgroundColor: 'rgba(0,0,0,0.8)',
                                titleFont: { size: 13 },
                                bodyFont: { size: 14, weight: 'bold' }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false },
                                ticks: {
                                    callback: function(value) { return '$' + value; }
                                }
                            },
                            x: {
                                grid: { display: false, drawBorder: false }
                            }
                        }
                    }
                });
            });
        </script>
        <?php endif; ?>

        <!-- ===== CRITICAL INVENTORY WIDGET ===== -->
        <?php
        global $pdo;
        $criticalStmt = $pdo->query("SELECT id, name, stock FROM products WHERE stock < 5 ORDER BY stock ASC, name ASC LIMIT 20");
        $criticalProducts = $criticalStmt->fetchAll(PDO::FETCH_ASSOC);
        $criticalCount = count($criticalProducts);
        if ($criticalCount > 0):
        ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="card" style="border:none;border-radius:14px;box-shadow:0 4px 20px rgba(220,53,69,0.12);border-left:5px solid #dc3545;overflow:hidden;">
                    <div class="card-header d-flex align-items-center justify-content-between" style="background:linear-gradient(135deg,#fff5f5,#fff);border-bottom:1px solid #fee;padding:16px 20px;">
                        <div class="d-flex align-items-center" style="gap:10px;">
                            <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#dc3545,#c82333);display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px;flex-shrink:0;">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div>
                                <h6 style="margin:0;font-weight:700;color:#c82333;font-size:0.9rem;">⚠️ Alerta de Inventario Crítico</h6>
                                <small class="text-muted"><?= $criticalCount ?> producto(s) con stock bajo o agotado requieren atención inmediata.</small>
                            </div>
                        </div>
                        <a href="/soleipharmav2/admin/lowStock" class="btn btn-sm btn-danger" style="border-radius:8px;font-size:0.8rem;">
                            <i class="fas fa-list mr-1"></i>Ver todos
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless mb-0" style="font-size:0.875rem;">
                                <thead>
                                    <tr style="background:#fff8f8;border-bottom:2px solid #fee;">
                                        <th style="padding:10px 20px;font-size:0.75rem;color:#888;text-transform:uppercase;letter-spacing:0.5px;">Producto</th>
                                        <th style="padding:10px;font-size:0.75rem;color:#888;text-transform:uppercase;letter-spacing:0.5px;text-align:center;">Stock</th>
                                        <th style="padding:10px;font-size:0.75rem;color:#888;text-transform:uppercase;letter-spacing:0.5px;text-align:center;">Estado</th>
                                        <th style="padding:10px 20px;font-size:0.75rem;color:#888;text-transform:uppercase;letter-spacing:0.5px;text-align:right;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($criticalProducts as $cp):
                                        $stock = intval($cp['stock']);
                                        if ($stock === 0) { $badge = 'danger'; $label = 'Agotado'; $icon = '🚫'; }
                                        else if ($stock <= 2) { $badge = 'danger'; $label = 'Crítico'; $icon = '🔴'; }
                                        else { $badge = 'warning'; $label = 'Bajo'; $icon = '🟡'; }
                                    ?>
                                    <tr style="border-bottom:1px solid #fafafa;transition:background 0.15s;" onmouseover="this.style.background='#fff5f5'" onmouseout="this.style.background=''">
                                        <td style="padding:12px 20px;font-weight:600;color:#343a40;">
                                            <i class="fas fa-pills mr-2" style="color:#dc3545;opacity:0.6;"></i>
                                            <?= htmlspecialchars($cp['name']) ?>
                                        </td>
                                        <td style="padding:12px;text-align:center;">
                                            <strong style="font-size:1.1rem;color:<?= $stock === 0 ? '#dc3545' : '#fd7e14' ?>;">
                                                <?= $stock ?>
                                            </strong>
                                        </td>
                                        <td style="padding:12px;text-align:center;">
                                            <span class="badge badge-<?= $badge ?>" style="padding:5px 12px;border-radius:20px;font-size:0.76rem;">
                                                <?= $icon ?> <?= $label ?>
                                            </span>
                                        </td>
                                        <td style="padding:12px 20px;text-align:right;">
                                            <a href="/soleipharmav2/order/create" class="btn btn-sm btn-outline-danger" style="border-radius:8px;font-size:0.78rem;padding:4px 12px;">
                                                <i class="fas fa-cart-plus mr-1"></i>Pedir
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <!-- ===== END CRITICAL INVENTORY WIDGET ===== -->

        <div class="card col-md-12 col-12">
            <div class="card-header">
                <h3 class="card-title">Productos</h3>
                <div class="card-tools">
                    <a href="/soleipharmav2/admin/addProduct" class="btn btn-success btn-sm">Agregar
                        Producto</a>
                    <a href="/soleipharmav2/product/updateCostsForm" class="btn btn-warning btn-sm">Actualizar Costos</a>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Costo</th>
                            <th>Precio Venta</th>
                            <th>Stock</th>
                            <th>Disponible</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td><?= $p['id'] ?></td>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td>$<?= number_format($p['cost'], 2) ?></td>
                                <td>$<?= number_format($p['sale_price'], 2) ?></td>
                                <td><?= (int) $p['stock'] ?></td>
                                <td><?= $p['available'] ? 'Sí' : 'No' ?></td>
                                <td>
                                    <a href="/soleipharmav2/admin/editProduct?id=<?= $p['id'] ?>"
                                        class="btn btn-sm btn-primary">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php if (!empty($lockedUsersAlerts_ParaSuperadmin)): ?>
    <!-- Modal for locked users alert -->
    <div id="dashboardLockedUserModal" class="modal micromodal-slide" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="dashboardLockedUserModal-title" style="max-width: 500px;">
                <header class="modal__header">
                    <h2 class="modal__title text-danger" id="dashboardLockedUserModal-title">
                        <i class="fas fa-ban"></i> ¡Usuarios Bloqueados!
                    </h2>
                    <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                </header>
                <main class="modal__content" id="dashboardLockedUserModal-content">
                    <p class="mb-3">Se han detectado usuarios suspendidos por múltiples intentos de inicio de sesión fallidos:</p>
                    <ul class="list-group mb-4">
                        <?php foreach ($lockedUsersAlerts_ParaSuperadmin as $lockedUser): ?>
                            <li class="list-group-item">
                                <strong><?= htmlspecialchars(trim(($lockedUser['first_name'] ?? '') . ' ' . ($lockedUser['last_name'] ?? '') . ' ' . ($lockedUser['second_surname'] ?? ''))) ?></strong>
                                <br>
                                <small class="text-muted">
                                    Rol: <?= htmlspecialchars($lockedUser['role'] ?? '') ?> | 
                                    Fecha: <?= htmlspecialchars($lockedUser['locked_at'] ?? '') ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </main>
                <footer class="modal-footer">
                    <button type="button" class="btn btn-secondary modal__btn" data-micromodal-close>Cerrar</button>
                    <a href="/soleipharmav2/admin/lockedUsers" class="btn btn-primary modal__btn modal__btn-primary">
                        Gestionar usuarios bloqueados
                    </a>
                </footer>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- Micromodal para Notificaciones (Superadmin y Admin) -->
    <?php if (isset($unreadNotificationsDashboard) && count($unreadNotificationsDashboard) > 0): ?>
    <div class="modal micromodal-slide" id="dashboardNotificationsModal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="dashboardNotificationsModal-title">
                <header class="modal__header border-bottom-0 rounded-top" style="padding-bottom:.5rem;">
                    <h2 class="modal__title" id="dashboardNotificationsModal-title">
                        <i class="fas fa-bell text-warning"></i> ¡Nuevas Notificaciones!
                    </h2>
                    <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                </header>
                <main class="modal__content" id="dashboardNotificationsModal-content">
                    <p class="mb-3">Tienes actividades pendientes de revisar:</p>
                    <ul class="list-group mb-4">
                        <?php foreach ($unreadNotificationsDashboard as $notif): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($notif['message']) ?>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <form id="markNotificationsForm" method="POST" action="/soleipharmav2/admin/markNotificationsRead">
                        <?php foreach ($unreadNotificationsDashboard as $notif): ?>
                            <input type="hidden" name="notification_ids[]" value="<?= $notif['id'] ?>">
                        <?php endforeach; ?>
                    </form>
                </main>
                <footer class="modal-footer">
                    <button type="button" class="btn btn-secondary modal__btn" data-micromodal-close>Cerrar</button>
                    <button type="button" class="btn btn-primary modal__btn modal__btn-primary" id="btnGoToDiscards">
                        Revisar Solicitudes
                    </button>
                </footer>
            </div>
        </div>
    </div>
    <?php $redirectUrl = ($_SESSION['user']['role'] === 'superadmin') ? '/soleipharmav2/discard/listPending' : '/soleipharmav2/discard/myHistory'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.getElementById('btnGoToDiscards');
            if (btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var $form = $('#markNotificationsForm');
                    $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
                    
                    // Enviar AJAX para marcar como leídas y luego redirigir a descartes
                    $.ajax({
                        url: $form.attr('action'),
                        type: 'POST',
                        data: $form.serialize()
                    }).done(function(response) {
                        try {
                            var res = JSON.parse(response);
                            if(res.success) {
                                window.location.href = '<?= $redirectUrl ?>';    
                            } else {
                                console.error(res.message);
                                window.location.href = '<?= $redirectUrl ?>'; 
                            }
                        } catch(e) {
                            window.location.href = '<?= $redirectUrl ?>';
                        }
                    }).fail(function() {
                        // Si falla igual redirigir para que pueda trabajar
                        window.location.href = '<?= $redirectUrl ?>';
                    });
                });
            }
        });
    </script>
    <?php endif; ?>

    <!-- MicroModal setup and ajax logic -->
    <script src="https://cdn.jsdelivr.net/npm/micromodal/dist/micromodal.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var isModalInitialized = false;
            if (typeof MicroModal !== 'undefined') {
                try {
                    MicroModal.init({
                        openTrigger: 'data-micromodal-trigger',
                        closeTrigger: 'data-micromodal-close',
                        openClass: 'is-open',
                        disableScroll: true,
                        disableFocus: false,
                        awaitOpenAnimation: true,
                        awaitCloseAnimation: true,
                        debugMode: false
                    });
                    isModalInitialized = true;
                } catch (e) {
                    console.error("Micromodal init error:", e);
                }
            }
            // Auto open the modal(s)
            var hasLockedUsers = <?= (isset($lockedUsersAlerts_ParaSuperadmin) && count($lockedUsersAlerts_ParaSuperadmin) > 0) ? 'true' : 'false' ?>;
            var hasUnreadNotifs = <?= (isset($unreadNotificationsDashboard) && count($unreadNotificationsDashboard) > 0) ? 'true' : 'false' ?>;
            
            if (hasLockedUsers || hasUnreadNotifs) {
                setTimeout(function() {
                    if(hasLockedUsers) {
                        try {
                            MicroModal.show('dashboardLockedUserModal', {
                                onClose: function() {
                                    // Al cerrar usuarios bloqueados, muestra notificaciones si hay
                                    if(hasUnreadNotifs) {
                                        setTimeout(function() { MicroModal.show('dashboardNotificationsModal'); }, 500);
                                    }
                                }
                            });
                        } catch (e) { console.error("Error opening locked users modal", e); }
                    } else if (hasUnreadNotifs) {
                        try {
                            MicroModal.show('dashboardNotificationsModal');
                        } catch (e) { console.error("Error opening notifications modal", e); }
                    }
                }, 500);
            }
        });
    </script>

    <!-- Role Upgrade Welcome & Tour (Driver.js) -->
    <?php if (isset($roleUpgradeData) && $roleUpgradeData): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css"/>
    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Pantalla de carga superpuesta */
        #roleUpgradeLoader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: #f8f9fa;
            z-index: 999999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: opacity 0.5s ease;
        }
        .ru-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e0e0e0;
            border-top: 4px solid #6f42c1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* Estilos personalizados para Driver.js */
        .driver-popover {
            border-radius: 12px !important;
            padding: 20px !important;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2) !important;
            border: 2px solid #6f42c1 !important;
        }
        .driver-popover-title {
            font-size: 1.25rem !important;
            font-weight: bold !important;
            color: #4a148c !important;
            margin-bottom: 10px !important;
        }
        .driver-popover-description {
            font-size: 1rem !important;
            color: #333 !important;
        }
        .driver-popover-navigation-btns {
            margin-top: 15px !important;
        }
        .driver-popover-btn-next,
        .driver-popover-btn-prev {
            background-color: #6f42c1 !important;
            color: white !important;
            border: none !important;
            text-shadow: none !important;
            border-radius: 6px !important;
            padding: 8px 16px !important;
            font-weight: bold !important;
            transition: background 0.2s !important;
        }
        .driver-popover-btn-next:hover,
        .driver-popover-btn-prev:hover {
            background-color: #512da8 !important;
        }
        .driver-popover-btn-disabled {
            background-color: #d1c4e9 !important;
            color: #7e57c2 !important;
            cursor: not-allowed !important;
        }
    </style>

    <div id="roleUpgradeLoader">
        <div class="ru-spinner"></div>
        <h4 class="text-secondary font-weight-bold">Preparando tu nuevo entorno...</h4>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Give modals a second, then trigger the Welcome Alert
            setTimeout(() => {
                // Ocultar pantalla de carga
                const loader = document.getElementById('roleUpgradeLoader');
                loader.style.opacity = '0';
                setTimeout(() => loader.remove(), 500);

                Swal.fire({
                    title: '¡Felicidades por tu ascenso!',
                    html: `
                        <p class="mb-3">Has sido promovido a <strong><?= strtoupper(htmlspecialchars($roleUpgradeData['new_role'])) ?></strong>.</p>
                        <p class="text-sm text-muted">Autorizado por: <?= htmlspecialchars($roleUpgradeData['admin_name']) ?></p>
                    `,
                    icon: 'success',
                    confirmButtonText: 'Iniciar Tour por el Panel',
                    confirmButtonColor: '#6f42c1',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Mark as acknowledged via AJAX
                        fetch('/soleipharmav2/admin/acknowledgeRoleUpgrade', { method: 'POST' })
                        .then(() => {
                            // Start Driver.js tour
                            const driver = window.driver.js.driver;
                            const driverObj = driver({
                                showProgress: true,
                                progressText: 'Paso {{current}} de {{total}}',
                                nextBtnText: 'Siguiente &rarr;',
                                prevBtnText: '&larr; Anterior',
                                doneBtnText: '¡Comenzar a trabajar!',
                                allowClose: false,
                                overlayColor: 'rgba(0, 0, 0, 0.7)',
                                steps: [
                                    { element: '.brand-link', popover: { title: 'Bienvenido a tu nuevo panel', description: 'Como <?= strtoupper(htmlspecialchars($roleUpgradeData['new_role'])) ?>, ahora tienes acceso a más herramientas.' } },
                                    { element: '.nav-sidebar', popover: { title: 'Menú Lateral', description: 'Aquí encontrarás todos los módulos habilitados para ti. Exploralos para conocer tus nuevas funciones.' } },
                                    <?php if ($roleUpgradeData['new_role'] === 'superadmin'): ?>
                                    { element: 'a[href*="action=listPending"]', popover: { title: 'Solicitudes de Descarte', description: 'Solo los superadmins como tú pueden aprobar o rechazar los descartes solicitados.' } },
                                    { element: 'a[href*="action=manageRoles"]', popover: { title: 'Gestión de Privilegios', description: 'Ahora tienes la autoridad para otorgar o remover permisos a otros usuarios.' } }
                                    <?php endif; ?>
                                ]
                            });
                            driverObj.drive();
                        }).catch(console.error);
                    }
                });
            }, 1800);
        });
    </script>
    <?php endif; ?>

</section>