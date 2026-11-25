<script setup>
import { watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '¿Confirmar Acción?',
    },
    subtitle: {
        type: String,
        default: '',
    },
    confirmText: {
        type: String,
        default: 'Confirmar',
    },
    cancelText: {
        type: String,
        default: 'Cancelar',
    },
    confirmVariant: {
        type: String,
        default: 'emerald', // 'emerald', 'violet', 'rose'
    },
    icon: {
        type: String,
        default: 'fa-question-circle',
    },
    processing: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: 'max-w-md',
    },
});

const emit = defineEmits(['close', 'cancel', 'confirm']);

function handleClose() {
    if (props.processing) return;
    emit('cancel');
    emit('close');
}

function handleConfirm() {
    if (props.processing) return;
    emit('confirm');
}
</script>

<template>
    <Transition name="fade">
        <div
            v-if="show"
            @click.self="handleClose"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm overflow-y-auto"
        >
            <div
                class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full my-6 overflow-hidden transform transition-all"
                :class="maxWidth"
            >
                <!-- Cabecera -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/50">
                    <h3 class="text-white font-bold text-sm flex items-center gap-2.5">
                        <i :class="['fas', icon, confirmVariant === 'emerald' ? 'text-emerald-400' : 'text-violet-400']"></i>
                        <span>{{ title }}</span>
                    </h3>
                    <button
                        @click="handleClose"
                        :disabled="processing"
                        class="w-7 h-7 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-colors disabled:opacity-50"
                    >
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                <!-- Contenido -->
                <div class="p-6 text-xs text-gray-300 space-y-3">
                    <p v-if="subtitle" class="text-gray-400">
                        {{ subtitle }}
                    </p>
                    <slot />
                </div>

                <!-- Botones Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-white/5 bg-gray-950/50">
                    <button
                        type="button"
                        @click="handleClose"
                        :disabled="processing"
                        class="px-4 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs font-semibold transition-colors disabled:opacity-50 cursor-pointer"
                    >
                        {{ cancelText }}
                    </button>
                    <button
                        type="button"
                        @click="handleConfirm"
                        :disabled="processing"
                        class="px-5 py-2.5 rounded-xl text-white text-xs font-bold shadow-lg flex items-center gap-2 transition-all cursor-pointer disabled:opacity-50"
                        :class="[
                            confirmVariant === 'emerald'
                                ? 'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-900/30'
                                : confirmVariant === 'rose'
                                ? 'bg-rose-600 hover:bg-rose-500 shadow-rose-900/30'
                                : 'bg-violet-600 hover:bg-violet-500 shadow-violet-900/30'
                        ]"
                    >
                        <i v-if="processing" class="fas fa-spinner fa-spin text-xs"></i>
                        <i v-else class="fas fa-check text-xs"></i>
                        <span>{{ processing ? 'Aplicando...' : confirmText }}</span>
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
