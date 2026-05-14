<!-- views/reset_password.php -->
<!-- Este archivo se renderiza con header/footer (BaseController::render) -->
<!-- Se muestra al usuario cuando navega al enlace de recuperación          -->

<?php
$safeToken    = htmlspecialchars($token        ?? '', ENT_QUOTES);
$safeUsername = htmlspecialchars($tokenUsername ?? '', ENT_QUOTES);
$safeError    = htmlspecialchars($tokenError   ?? '', ENT_QUOTES);
?>

<div class="w-full flex items-center justify-center -mt-6">
    <div
        x-data="{
            token:           '<?= $safeToken ?>',
            tokenError:      '<?= $safeError ?>',
            username:        '<?= $safeUsername ?>',
            newPassword:     '',
            confirmPassword: '',
            loading:         false,
            errorMsg:        '',
            successMsg:      '',
            showNew:         false,
            showConfirm:     false,

            async submit() {
                this.loading    = true;
                this.errorMsg   = '';
                this.successMsg = '';
                const fd = new FormData();
                fd.append('token',            this.token);
                fd.append('username',         this.username);
                fd.append('new_password',     this.newPassword);
                fd.append('confirm_password', this.confirmPassword);
                try {
                    const r    = await fetch('<?= APP_BASE ?>/auth/resetPassword', { method:'POST', body: fd });
                    const data = await r.json();
                    if (data.success) {
                        this.successMsg      = data.message;
                        this.newPassword     = '';
                        this.confirmPassword = '';
                    } else {
                        this.errorMsg = data.message;
                    }
                } catch(e) {
                    this.errorMsg = 'Error de conexión. Inténtalo de nuevo.';
                }
                this.loading = false;
            }
        }"
        class="bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl shadow-2xl p-8 w-full max-w-lg mx-4"
    >
        <!-- Encabezado -->
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">Nueva Contraseña</h1>
            <p class="text-purple-200 text-sm mt-1">Farmacia Solei</p>
        </div>

        <!-- Error de token inválido / expirado -->
        <div x-show="tokenError" class="bg-red-500 bg-opacity-80 text-white px-4 py-3 rounded-lg mb-4 text-center">
            <p class="font-semibold" x-text="tokenError"></p>
            <a href="../" class="underline text-yellow-300 text-sm mt-1 block">Ir al inicio</a>
        </div>

        <!-- Formulario (solo si token válido) -->
        <div x-show="!tokenError">

            <!-- Mensajes de éxito / error -->
            <div x-show="successMsg"
                class="bg-green-500 bg-opacity-20 border border-green-400 text-green-100 px-4 py-3 rounded-lg mb-4 text-center">
                <p class="font-semibold" x-text="successMsg"></p>
                <a href="../" class="mt-2 inline-block bg-yellow-500 hover:bg-yellow-600 text-white font-bold px-4 py-1.5 rounded transition text-sm">
                    Ir a Iniciar Sesión
                </a>
            </div>
            <div x-show="errorMsg && !successMsg"
                class="bg-red-500 bg-opacity-80 text-white px-4 py-3 rounded-lg mb-4 text-sm" x-text="errorMsg">
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" x-show="!successMsg" class="space-y-4">

                <!-- Usuario (solo lectura, pre-rellenado desde el token) -->
                <div>
                    <label class="block text-white font-semibold text-sm mb-1">Usuario</label>
                    <div class="relative">
                        <input
                            type="text"
                            :value="username"
                            readonly
                            class="w-full px-4 py-2 rounded-lg text-gray-500 bg-gray-100 text-sm cursor-not-allowed select-all"
                        >
                        <span class="absolute right-3 top-1/2 -translate-y-1/2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                    </div>
                    <p class="text-purple-300 text-xs mt-1">Usuario identificado desde el enlace de recuperación.</p>
                </div>

                <!-- Nueva contraseña -->
                <div>
                    <label class="block text-white font-semibold text-sm mb-1">
                        Nueva Contraseña <span class="text-yellow-300">*</span>
                    </label>
                    <div class="relative">
                        <input
                            :type="showNew ? 'text' : 'password'"
                            x-model="newPassword"
                            required
                            minlength="6"
                            placeholder="Mínimo 6 caracteres"
                            class="w-full px-4 py-2 pr-10 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-800 text-sm"
                        >
                        <button type="button" @click="showNew = !showNew"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <svg x-show="!showNew" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showNew" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.99 9.99 0 012.217-3.618M6.61 6.61A9.956 9.956 0 0112 5c4.477 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411M3 3l18 18"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Confirmar contraseña -->
                <div>
                    <label class="block text-white font-semibold text-sm mb-1">
                        Confirmar Contraseña <span class="text-yellow-300">*</span>
                    </label>
                    <div class="relative">
                        <input
                            :type="showConfirm ? 'text' : 'password'"
                            x-model="confirmPassword"
                            required
                            placeholder="Repite la nueva contraseña"
                            class="w-full px-4 py-2 pr-10 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-800 text-sm"
                        >
                        <button type="button" @click="showConfirm = !showConfirm"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <svg x-show="!showConfirm" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showConfirm" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.99 9.99 0 012.217-3.618M6.61 6.61A9.956 9.956 0 0112 5c4.477 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411M3 3l18 18"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Indicador coincidencia -->
                <p x-show="confirmPassword && newPassword !== confirmPassword"
                   class="text-red-300 text-xs">Las contraseñas no coinciden.</p>
                <p x-show="confirmPassword && newPassword === confirmPassword && confirmPassword.length >= 6"
                   class="text-green-300 text-xs">Las contraseñas coinciden.</p>

                <button
                    type="submit"
                    :disabled="loading || (confirmPassword && newPassword !== confirmPassword)"
                    class="w-full py-2.5 bg-yellow-500 text-white font-bold rounded-lg hover:bg-yellow-600 transition-colors disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                    <span x-show="loading">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </span>
                    <span x-text="loading ? 'Guardando...' : 'Actualizar Contraseña'"></span>
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="index.php" class="text-purple-200 hover:text-white text-sm transition">Volver al inicio</a>
            </div>
        </div>
    </div>
</div>
