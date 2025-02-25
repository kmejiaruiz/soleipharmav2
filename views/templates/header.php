<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es" x-data="{ 
    openLogin: <?= isset($error) && !empty($error) ? 'true' : 'false' ?>, 
    openRegister: false, 
    openCart: false 
}">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<head>
    <meta charset="UTF-8">
    <title>Mi Tienda Online</title>
    <!-- css propio -->
    <link rel="stylesheet" href="assets/css/index.css">
    <!-- Tailwind CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Splide JS  -->
    <script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@latest/dist/js/splide.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@splidejs/splide@latest/dist/css/splide.min.css">
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
    [x-cloak] {
        display: none !important;
    }
    </style>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="bg-gray-50">
    <!-- Loading Wrapper -->
    <!-- <div id="loading-wrapper" style="
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: #f9fafb;
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.1s ease-in;">
      <div style="font-size: 24px; color: #000;">
        <i class="fas fa-spinner fa-spin"></i> Cargando...
      </div> -->
    </div> <!-- Barra de navegación con degradado morado oscuro -->
    <nav class="bg-gradient-to-r from-purple-500 to-indigo-500 shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-white text-2xl font-bold flex items-center">
                        <!-- Imagen solo en móviles -->
                        <img src="http://soleipharma.ct.ws/images/logo.jpg" class="w-10 h-auto md:hidden" alt="Logo">

                        <!-- Texto solo en pantallas medianas o mayores -->
                        <span class="hidden md:inline">Farmacia Solei</span>
                    </a>

                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <button onclick="location.href='index.php?controller=product&action=index'"
                            class="text-white hover:text-gray-100 inline-flex items-center px-1 pt-1">Productos</button>
                        <button onclick="location.href='index.php?controller=cart&action=view'"
                            class="text-white hover:text-gray-100 inline-flex items-center px-1 pt-1">Carrito</button>
                        <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                        <button onclick="location.href='index.php?controller=admin&action=index'"
                            class="text-white hover:text-gray-100 inline-flex items-center px-1 pt-1">Panel
                            Admin</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if(isset($_SESSION['user'])): ?>
                    <span class="text-white">Hola, <?= htmlspecialchars($_SESSION['user']['username']) ?></span>
                    <a href="index.php?controller=auth&action=logout"
                        class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Cerrar sesión</a>
                    <?php else: ?>
                    <button @click="openLogin = true"
                        class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">Iniciar sesión</button>
                    <button @click="openRegister = true"
                        class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Registrarse</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Toast de notificación (ver bloque anterior) -->
    <?php if(isset($_SESSION['flash'])): ?>
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
        class="fixed top-4 right-4 bg-purple-700 text-white px-4 py-2 rounded shadow space-y-2">
        <div><?= $_SESSION['flash']; ?></div>
        <?php if(isset($_SESSION['flash_type']) && $_SESSION['flash_type'] === 'cart'): ?>
        <div class="flex space-x-2">
            <a href="index.php?controller=cart&action=view"
                class="bg-green-600 hover:bg-green-700 px-3 py-1 rounded">Ver Carrito</a>
            <button @click="show = false" class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded">Continuar</button>
        </div>
        <?php endif; ?>
    </div>
    <?php 
    unset($_SESSION['flash']);
    unset($_SESSION['flash_type']);
    endif;
    ?>

    <!-- Modal de Login -->
    <div x-show="openLogin" x-cloak
        class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 z-50">
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-6 rounded-lg shadow-lg w-full max-w-md relative">
            <button @click="openLogin = false" class="absolute top-2 right-2 text-gray-200 hover:text-gray-50">
                <!-- SVG de cierre -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <?php include __DIR__ . '/../../views/login_modal.php'; ?>
        </div>
    </div>

    <!-- Modal de Registro -->
    <div x-show="openRegister" x-cloak
        class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 z-50">
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-6 rounded-lg shadow-lg w-full max-w-md relative">
            <button @click="openRegister = false" class="absolute top-2 right-2 text-gray-200 hover:text-gray-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <?php include __DIR__ . '/../../views/register_modal.php'; ?>
        </div>
    </div>

    <!-- Modal del Carrito -->
    <div x-show="openCart" x-cloak
        class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 z-50">
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-6 rounded-lg shadow-lg w-full max-w-lg relative">
            <button @click="openCart = false" class="absolute top-2 right-2 text-gray-200 hover:text-gray-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <?php include __DIR__ . '/../../views/cart_modal.php'; ?>
        </div>
    </div>

    <div class="container mx-auto p-6">