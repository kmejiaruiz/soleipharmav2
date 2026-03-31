<?php
// views/templates/bare_header.php
// Layout mínimo: solo branding, sin navbar, sin modales, sin carrito
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmacia Solei</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        [x-cloak] { display: none !important; }
        body { background: linear-gradient(135deg, #f3f0ff 0%, #ede9fe 100%); }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Barra mínima: solo el nombre de la empresa / sucursal -->
    <header class="bg-gradient-to-r from-purple-600 to-indigo-600 shadow-md">
        <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-center">
            <a href="index.php" class="text-white text-xl font-bold tracking-wide">
                Farmacia Solei &mdash; Sucursal León
            </a>
        </div>
    </header>

    <main class="flex-1 flex items-center justify-center py-10 px-4">
