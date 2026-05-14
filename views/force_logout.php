<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesión Finalizada</title>
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body { background-color: #f3f4f6; } 
    </style>
</head>
<body class="flex items-center justify-center h-screen overflow-hidden">
    <!-- Backdrop Fixed oscuro que no permite clics por detrás -->
    <div class="fixed inset-0 bg-gray-900 bg-opacity-80 flex items-center justify-center z-50">
        <!-- Modal Panel -->
        <div class="bg-white rounded-lg shadow-2xl p-8 max-w-md w-full mx-4 text-center transform transition-all border-t-8 border-red-600">
            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-red-100 mb-6">
                <!-- Icono de alerta -->
                <svg class="h-10 w-10 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Error de Sesión</h3>
            <p class="text-gray-600 mb-8 text-base">
                Tus privilegios han sido modificados por un Administrador o existe una inconsistencia de seguridad. Por protección, tu sesión actual se cerrará en este momento.
            </p>
            <a href="<?= APP_BASE ?>/index.php?show_login=1" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-md px-4 py-3 bg-red-600 text-lg font-bold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors uppercase tracking-wider">
                Continuar
            </a>
        </div>
    </div>
</body>
</html>
