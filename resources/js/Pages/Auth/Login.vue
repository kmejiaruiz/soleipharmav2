<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const showPass  = ref(false);
const loginInput = ref(null);
const showLockedModal = ref(false);
const lockedMessage = ref('');

onMounted(() => loginInput.value?.focus());

const submit = () => {
    form.post(route('login'), {
        onError: (errors) => {
            if (errors.user_locked) {
                lockedMessage.value = errors.user_locked;
                showLockedModal.value = true;
            }
        },
        onFinish: () => form.reset('password'),
    });
};

watch(() => form.errors.user_locked, (val) => {
    if (val) {
        lockedMessage.value = val;
        showLockedModal.value = true;
    }
}, { immediate: true });
</script>

<template>
    <Head title="Iniciar Sesión — Farmacia Solei" />

    <div class="min-h-screen flex items-center justify-center bg-gray-950 p-4"
         style="background: radial-gradient(ellipse at 60% 20%, #1e1033 0%, #0a0a12 70%);">

        <!-- Card -->
        <div class="w-full max-w-md">
            <!-- Branding -->
            <div class="text-center mb-8">
                <img src="/assets/images/logo.jpg"
                     class="w-20 h-20 rounded-full object-cover mx-auto mb-4 ring-4 ring-violet-500/30 shadow-xl"
                     alt="Logo Farmacia Solei"
                     onerror="this.style.display='none'" />
                <h1 class="text-white text-2xl font-bold tracking-tight">Farmacia Solei</h1>
                <p class="text-gray-500 text-sm mt-1">Sistema de Gestión v3</p>
            </div>

            <!-- Status message -->
            <div v-if="status" class="mb-4 text-sm text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 rounded-xl px-4 py-3 text-center">
                {{ status }}
            </div>

            <!-- Login card -->
            <div class="bg-gray-900/80 backdrop-blur border border-white/5 rounded-2xl p-8 shadow-2xl">
                <h2 class="text-white font-semibold text-lg mb-6">Iniciar sesión</h2>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Username -->
                    <div>
                        <label for="login" class="block text-gray-400 text-xs font-medium mb-1.5 uppercase tracking-wide">
                            Nombre de usuario
                        </label>
                        <div class="relative">
                            <i class="fas fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-600 text-sm"></i>
                            <input
                                id="login"
                                ref="loginInput"
                                v-model="form.login"
                                type="text"
                                autocomplete="username"
                                required
                                placeholder="usuario"
                                class="w-full pl-10 pr-4 py-3 bg-gray-800 border rounded-xl text-white placeholder-gray-600 text-sm outline-none transition-all"
                                :class="form.errors.login
                                    ? 'border-red-500/50 focus:border-red-500'
                                    : 'border-white/10 hover:border-white/20 focus:border-violet-500 focus:ring-1 focus:ring-violet-500/20'"
                            />
                        </div>
                        <p v-if="form.errors.login" class="text-red-400 text-xs mt-1.5 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ form.errors.login }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-gray-400 text-xs font-medium mb-1.5 uppercase tracking-wide">
                            Contraseña
                        </label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-600 text-sm"></i>
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPass ? 'text' : 'password'"
                                autocomplete="current-password"
                                required
                                placeholder="••••••••"
                                class="w-full pl-10 pr-11 py-3 bg-gray-800 border rounded-xl text-white placeholder-gray-600 text-sm outline-none transition-all"
                                :class="form.errors.password
                                    ? 'border-red-500/50 focus:border-red-500'
                                    : 'border-white/10 hover:border-white/20 focus:border-violet-500 focus:ring-1 focus:ring-violet-500/20'"
                            />
                            <button type="button" @click="showPass = !showPass"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-400 transition-colors p-1">
                                <i :class="['fas text-sm', showPass ? 'fa-eye-slash' : 'fa-eye']"></i>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="text-red-400 text-xs mt-1.5 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Remember me -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.remember"
                                class="w-4 h-4 rounded border-white/20 bg-gray-800 text-violet-600 focus:ring-violet-500 focus:ring-offset-gray-900" />
                            <span class="text-gray-500 text-sm">Recordarme</span>
                        </label>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3.5 rounded-xl font-semibold text-sm transition-all flex items-center justify-center gap-2"
                        :class="form.processing
                            ? 'bg-violet-700/50 text-violet-300 cursor-not-allowed'
                            : 'bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white shadow-lg shadow-violet-900/40 active:scale-95'"
                    >
                        <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-sign-in-alt"></i>
                        {{ form.processing ? 'Ingresando...' : 'Ingresar' }}
                    </button>
                </form>
            </div>

            <p class="text-center text-gray-700 text-xs mt-6">
                Farmacia Solei © {{ new Date().getFullYear() }} — Sistema interno
            </p>
        </div>

        <!-- Modal Usuario Bloqueado -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showLockedModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
                @click.self="showLockedModal = false"
            >
                <div class="bg-gray-900 border border-red-500/30 rounded-2xl max-w-sm w-full p-6 shadow-2xl relative text-center space-y-4">
                    <!-- Icono de Alerta / Bloqueo -->
                    <div class="w-16 h-16 rounded-full bg-red-500/10 border border-red-500/30 text-red-400 mx-auto flex items-center justify-center text-2xl shadow-lg shadow-red-950/40">
                        <i class="fas fa-user-lock"></i>
                    </div>

                    <!-- Mensaje Solicitado -->
                    <div class="space-y-1">
                        <p class="text-gray-200 font-medium text-sm leading-relaxed">
                            {{ lockedMessage || 'Ha ocurrido un error, contacte con el administrador del sistema.' }}
                        </p>
                    </div>

                    <!-- Botón de Cierre -->
                    <div class="pt-2">
                        <button
                            type="button"
                            @click="showLockedModal = false"
                            class="w-full py-2.5 px-4 rounded-xl bg-gray-800 hover:bg-gray-700 text-white font-semibold text-sm border border-white/10 transition-colors shadow-sm active:scale-95"
                        >
                            Entendido
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
