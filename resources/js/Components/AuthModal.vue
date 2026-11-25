<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Autorización Requerida',
    },
    subtitle: {
        type: String,
        default: 'Ingrese usuario y contraseña para autorizar esta operación.',
    },
    actionLabel: {
        type: String,
        default: 'Autorizar y Guardar',
    },
    processing: {
        type: Boolean,
        default: false,
    },
    errorMessage: {
        type: String,
        default: '',
    },
    initialUsername: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'confirm']);

const username = ref(props.initialUsername || '');
const password = ref('');
const passwordInput = ref(null);

watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.initialUsername && !username.value) {
            username.value = props.initialUsername;
        }
        password.value = '';
        setTimeout(() => {
            if (passwordInput.value) {
                passwordInput.value.focus();
            }
        }, 100);
    }
});

function handleClose() {
    emit('close');
}

function handleConfirm() {
    if (!username.value || !password.value) return;
    emit('confirm', {
        username: username.value,
        password: password.value,
    });
}
</script>

<template>
    <Transition name="fade">
        <div
            v-if="show"
            @click.self="handleClose"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        >
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/50">
                    <h3 class="text-white font-semibold text-sm flex items-center gap-2">
                        <i class="fas fa-shield-alt text-violet-400"></i>
                        {{ title }}
                    </h3>
                    <button
                        @click="handleClose"
                        class="w-7 h-7 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-colors"
                    >
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                <!-- Formulario -->
                <form @submit.prevent="handleConfirm" class="p-6 space-y-4">
                    <p class="text-xs text-gray-400">
                        {{ subtitle }}
                    </p>

                    <!-- Mensaje de Error -->
                    <div
                        v-if="errorMessage"
                        class="p-3 rounded-xl bg-red-500/15 border border-red-500/30 text-red-400 text-xs flex items-center gap-2"
                    >
                        <i class="fas fa-exclamation-circle flex-shrink-0"></i>
                        <span>{{ errorMessage }}</span>
                    </div>

                    <!-- Input Usuario -->
                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">
                            Usuario Autoriza *
                        </label>
                        <div class="relative">
                            <i class="fas fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
                            <input
                                v-model="username"
                                type="text"
                                required
                                placeholder="ej: admin"
                                class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl pl-9 pr-4 py-2.5 text-white text-xs outline-none transition-colors"
                            />
                        </div>
                    </div>

                    <!-- Input Contraseña -->
                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">
                            Contraseña *
                        </label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
                            <input
                                ref="passwordInput"
                                v-model="password"
                                type="password"
                                required
                                placeholder="••••••••"
                                class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl pl-9 pr-4 py-2.5 text-white text-xs outline-none transition-colors"
                            />
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end gap-3 pt-3 border-t border-white/5">
                        <button
                            type="button"
                            @click="handleClose"
                            class="px-4 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs font-semibold transition-colors"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="processing || !username || !password"
                            class="px-5 py-2 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white font-bold text-xs shadow-lg shadow-violet-900/30 flex items-center gap-2 transition-all disabled:opacity-50"
                        >
                            <i v-if="processing" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-check-circle"></i>
                            {{ processing ? 'Validando...' : actionLabel }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
