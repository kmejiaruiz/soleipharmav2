<!-- views/forgot_password_modal.php -->
<!-- Step 1: Ingresa usuario → genera enlace de recuperación -->
<div x-data="{
    step: 'form',
    username: '',
    loading: false,
    errorMsg: '',
    resetUrl: '',
    copied: false,

    async submit() {
        this.loading = true;
        this.errorMsg = '';
        this.resetUrl = '';
        const fd = new FormData();
        fd.append('username', this.username);
        try {
            const r = await fetch('/soleipharmav2/auth/requestReset', { method:'POST', body: fd });
            const data = await r.json();
            if (data.success) {
                this.resetUrl = data.reset_url;
                this.step = 'link';
            } else {
                this.errorMsg = data.message;
            }
        } catch(e) {
            this.errorMsg = 'Error de conexión. Inténtalo de nuevo.';
        }
        this.loading = false;
    },

    copyLink() {
        navigator.clipboard.writeText(this.resetUrl).then(() => {
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        });
    }
}">

    <!-- Encabezado -->
    <h2 class="text-2xl font-bold text-white mb-1 text-center">Recuperar Contraseña</h2>
    <p class="text-purple-200 text-sm text-center mb-4">Ingresa tu usuario para generar el enlace de recuperación.</p>

    <!-- Paso 1: formulario -->
    <div x-show="step === 'form'">
        <div x-show="errorMsg" class="bg-red-500 bg-opacity-80 text-white px-3 py-2 rounded mb-3 text-sm" x-text="errorMsg"></div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-white font-semibold mb-1 text-sm">Usuario</label>
                <input
                    type="text"
                    x-model="username"
                    required
                    placeholder="Ingresa tu nombre de usuario"
                    class="w-full px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-800"
                >
            </div>
            <button
                type="submit"
                :disabled="loading"
                class="w-full py-2 bg-yellow-500 text-white font-bold rounded hover:bg-yellow-600 transition-colors disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
                <span x-show="loading">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </span>
                <span x-show="!loading">Generar Enlace</span>
                <span x-show="loading">Procesando…</span>
            </button>
        </form>
    </div>

    <!-- Paso 2: mostrar enlace generado -->
    <div x-show="step === 'link'" class="space-y-4">
        <div class="bg-green-500 bg-opacity-20 border border-green-400 rounded p-3 text-center">
            <p class="text-green-200 text-sm font-semibold mb-1">Enlace generado correctamente</p>
            <p class="text-purple-100 text-xs">Copia el enlace y pégalo en tu navegador para restablecer tu contraseña.<br>
            <span class="text-yellow-300 font-bold">Expira en 30 minutos.</span></p>
        </div>

        <div>
            <label class="block text-white font-semibold mb-1 text-sm">Enlace de recuperación</label>
            <div class="flex gap-2">
                <input
                    type="text"
                    :value="resetUrl"
                    readonly
                    id="reset-url-input"
                    class="flex-1 px-3 py-2 rounded text-gray-800 text-xs focus:outline-none bg-gray-100 cursor-text select-all"
                    @click="$event.target.select()"
                >
                <button
                    type="button"
                    @click="copyLink()"
                    class="px-3 py-2 rounded font-bold text-sm transition-colors"
                    :class="copied ? 'bg-green-500 text-white' : 'bg-yellow-500 hover:bg-yellow-600 text-white'"
                >
                    <span x-show="!copied">Copiar</span>
                    <span x-show="copied">✓</span>
                </button>
            </div>
        </div>

        <p class="text-purple-200 text-xs text-center">
            Pega el enlace en la barra de tu navegador y presiona <kbd class="bg-gray-700 text-white rounded px-1">Enter</kbd>
        </p>

        <button
            type="button"
            @click="step='form'; username=''; resetUrl=''; errorMsg='';"
            class="w-full py-2 bg-purple-800 bg-opacity-50 text-white rounded hover:bg-opacity-70 transition text-sm"
        >
            Volver
        </button>
    </div>
</div>
