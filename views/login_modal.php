<!-- views/login_modal.php -->
<h2 class="text-3xl font-bold text-white mb-4 text-center">Iniciar Sesión</h2>
<?php if(isset($error) && !isset($modalAlert)): ?>
    <div class="bg-red-400 text-white p-2 rounded mb-4 text-center"><?= $error ?></div>
<?php endif; ?>

<?php if(isset($modalAlert) && $modalAlert && isset($error)): ?>
<!-- Alert Modal Overlay -->
<div id="lockoutAlertModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-60 transition-opacity">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm mx-4 transform transition-all">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2">Aviso</h3>
            <p class="text-sm text-gray-500 mb-6"><?= $error ?></p>
            <button type="button" onclick="document.getElementById('lockoutAlertModal').remove()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:text-sm">
                Entendido
            </button>
        </div>
    </div>
</div>
<?php endif; ?>
<form action="/soleipharmav2/auth/login" method="POST" class="space-y-4">
    <div>
        <label for="username" class="block text-white font-semibold">Usuario</label>
        <input type="text" name="username" id="username" required class="w-full px-4 py-2 rounded focus:outline-none focus:ring focus:border-purple-300">
    </div>
    <div>
        <label for="password" class="block text-white font-semibold">Contraseña</label>
        <input type="password" name="password" id="password" required class="w-full px-4 py-2 rounded focus:outline-none focus:ring focus:border-purple-300">
    </div>

    <!-- Enlace recuperar contraseña -->
    <div class="flex justify-end">
        <button
            type="button"
            @click="openLogin = false; openForgot = true"
            class="text-purple-200 hover:text-white text-sm underline transition-colors"
        >
            ¿Olvidaste tu contraseña?
        </button>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="w-full py-2 bg-yellow-500 text-white font-bold rounded hover:bg-yellow-600 transition-colors">
            Entrar
        </button>
    </div>
</form>
