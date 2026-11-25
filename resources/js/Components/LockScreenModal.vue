<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    isLocked: Boolean,
});

const emit = defineEmits(['unlock']);

const page = usePage();
const user = computed(() => page.props.auth.user);
const carousels = computed(() => page.props.carousels || []);

// -- Autoplay & Slide State ----------------------------------------------------
const currentIndex = ref(0);
const SLIDE_DURATION = 6000;
let animationFrameId = null;
const progress = ref(0);
let startTime = null;

function startProgress() {
    cancelAnimationFrame(animationFrameId);
    progress.value = 0;
    startTime = performance.now();

    function step(now) {
        if (!props.isLocked || carousels.value.length <= 1) {
            progress.value = 0;
            return;
        }

        const elapsed = now - startTime;
        progress.value = Math.min((elapsed / SLIDE_DURATION) * 100, 100);

        if (elapsed >= SLIDE_DURATION) {
            nextSlide();
        } else {
            animationFrameId = requestAnimationFrame(step);
        }
    }

    animationFrameId = requestAnimationFrame(step);
}

function nextSlide() {
    if (carousels.value.length > 0) {
        currentIndex.value = (currentIndex.value + 1) % carousels.value.length;
        startProgress();
    }
}

function prevSlide() {
    if (carousels.value.length > 0) {
        currentIndex.value = (currentIndex.value - 1 + carousels.value.length) % carousels.value.length;
        startProgress();
    }
}

function goToSlide(idx) {
    currentIndex.value = idx;
    startProgress();
}

// -- Reloj digital moderno ----------------------------------------------------
const timeHourMin = ref('');
const timeAmPm = ref('');
const timeSec = ref('');
const currentDate = ref('');
let clockInterval = null;

function updateClock() {
    const now = new Date();
    
    // Formato 12 horas: HH:MM
    let hours = now.getHours();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; // hora 0 es 12
    const hStr = String(hours).padStart(2, '0');
    const mStr = String(now.getMinutes()).padStart(2, '0');
    const sStr = String(now.getSeconds()).padStart(2, '0');

    timeHourMin.value = `${hStr}:${mStr}`;
    timeSec.value = sStr;
    timeAmPm.value = ampm;

    const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
    const dateFormatted = now.toLocaleDateString('es-NI', options);
    // Capitalizar primera letra
    currentDate.value = dateFormatted.charAt(0).toUpperCase() + dateFormatted.slice(1);
}

// -- Desbloqueo por contraseña ------------------------------------------------
const password = ref('');
const unlockError = ref('');
const isSubmitting = ref(false);
const hasErrorShake = ref(false);

async function handleUnlock() {
    if (!password.value.trim()) {
        triggerError('Ingresa tu contraseña para continuar.');
        return;
    }

    isSubmitting.value = true;
    unlockError.value = '';

    try {
        const url = typeof route === 'function' ? route('unlock.terminal') : '/unlock-terminal';
        await axios.post(url, { password: password.value });
        password.value = '';
        emit('unlock');
    } catch (err) {
        const msg = (err.response && err.response.data && err.response.data.message) 
            ? err.response.data.message 
            : 'Contraseña incorrecta. Intenta nuevamente.';
        triggerError(msg);
    } finally {
        isSubmitting.value = false;
    }
}

function triggerError(msg) {
    unlockError.value = msg;
    hasErrorShake.value = true;
    setTimeout(() => {
        hasErrorShake.value = false;
    }, 600);
}

// Focus input whenever lock screen appears
watch(
    () => props.isLocked,
    (locked) => {
        if (locked) {
            currentIndex.value = 0;
            startProgress();
            setTimeout(() => {
                const el = document.getElementById('lock-password-input');
                if (el) el.focus();
            }, 250);
        } else {
            cancelAnimationFrame(animationFrameId);
            password.value = '';
            unlockError.value = '';
        }
    }
);

onMounted(() => {
    updateClock();
    clockInterval = setInterval(updateClock, 1000);
    if (props.isLocked) {
        startProgress();
    }
});

onUnmounted(() => {
    if (clockInterval) clearInterval(clockInterval);
    cancelAnimationFrame(animationFrameId);
});
</script>

<template>
    <teleport to="body">
        <transition name="screen-fade">
            <div
                v-if="isLocked"
                class="fixed inset-0 z-[99999] bg-gray-950 flex flex-col justify-between overflow-hidden select-none font-sans"
            >
                <!-- ============================================================ -->
                <!-- CAROUSEL BACKGROUND CINEMÁTICO CON KEN-BURNS                 -->
                <!-- ============================================================ -->
                <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
                    <template v-if="carousels.length > 0">
                        <div
                            v-for="(item, idx) in carousels"
                            :key="item.id"
                            class="absolute inset-0 transition-opacity duration-1000 ease-in-out"
                            :class="idx === currentIndex ? 'opacity-100 z-10' : 'opacity-0 z-0'"
                        >
                            <img
                                :src="item.image_url || ('/storage/carousel/' + item.image)"
                                class="w-full h-full object-cover filter brightness-[0.4] contrast-[1.15] ken-burns-img"
                                :class="{ 'ken-burns-active': idx === currentIndex }"
                                @error="$event.target.style.display = 'none'"
                            />
                        </div>
                    </template>

                    <!-- Background fallback -->
                    <div v-else class="absolute inset-0 bg-gradient-to-br from-gray-950 via-violet-950/30 to-gray-950 flex items-center justify-center">
                        <div class="text-center opacity-15">
                            <i class="fas fa-pills text-9xl text-violet-400"></i>
                        </div>
                    </div>

                    <!-- Capas de iluminación y sombras ambientales -->
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/50 to-gray-950/80"></div>
                    <div class="absolute inset-0 bg-radial-vignette"></div>
                </div>

                <!-- ============================================================ -->
                <!-- TOP BAR: BRANDING / SUCURSAL / USUARIO                       -->
                <!-- ============================================================ -->
                <header class="relative z-30 flex items-center justify-between px-8 py-5 backdrop-blur-md bg-gray-950/40 border-b border-white/5">
                    <div class="flex items-center gap-3.5">
                        <div class="relative flex-shrink-0">
                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-violet-600 to-indigo-600 flex items-center justify-center text-white font-black shadow-lg shadow-violet-600/30 ring-2 ring-violet-500/40">
                                <i class="fas fa-pills text-sm text-white"></i>
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 ring-2 ring-gray-950 animate-pulse" title="Sistema Activo"></span>
                        </div>
                        <div class="flex items-center flex-wrap gap-2 text-white font-bold text-sm tracking-wide">
                            <span class="text-white font-black tracking-wider uppercase">
                                Farmacia <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-400 to-indigo-400">Solei</span>
                            </span>
                            <span class="text-gray-600 font-light">/</span>
                            <span class="text-violet-300 font-semibold text-xs capitalize flex items-center gap-1.5">
                                <i class="fas fa-building text-violet-400 text-[10px]"></i>
                                {{ user?.branch ? (user.branch.toLowerCase().startsWith('sucursal') ? user.branch : `Sucursal ${user.branch}`) : 'Sucursal General' }}
                            </span>
                            <span class="text-gray-600 font-light">/</span>
                            <span class="text-gray-300 font-medium text-xs lowercase flex items-center gap-1.5 bg-white/5 px-2.5 py-0.5 rounded-lg border border-white/10">
                                <i class="fas fa-user-circle text-violet-400 text-[11px]"></i>
                                {{ user?.username }}
                            </span>
                        </div>
                    </div>

                    <!-- Estado seguro -->
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-[11px] text-gray-400">
                        <i class="fas fa-shield-alt text-emerald-400"></i>
                        <span>Sesión Segura</span>
                    </div>
                </header>

                <!-- ============================================================ -->
                <!-- CENTER HERO: RELOJ DE IMPACTO + FORMULARIO INTEGRADO        -->
                <!-- ============================================================ -->
                <main class="relative z-30 flex-1 flex flex-col items-center justify-center px-4 py-6">
                    <div class="w-full max-w-md flex flex-col items-center space-y-7">
                        
                        <!-- RELOJ DIGITAL MINIMALISTA DE GRAN IMPACTO -->
                        <div class="text-center flex flex-col items-center space-y-1 drop-shadow-2xl">
                            <div class="flex items-baseline justify-center gap-2">
                                <span class="text-5xl sm:text-7xl font-black text-white tracking-tight font-mono leading-none">
                                    {{ timeHourMin }}
                                </span>
                                <span class="text-xl sm:text-2xl font-black text-violet-400 font-mono self-end pb-1 sm:pb-2">
                                    :{{ timeSec }}
                                </span>
                                <span class="text-xs sm:text-sm font-extrabold uppercase tracking-widest text-violet-300 bg-violet-500/20 px-2 py-0.5 rounded-md border border-violet-500/30 self-center">
                                    {{ timeAmPm }}
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm font-medium text-gray-300/80 tracking-wide capitalize flex items-center gap-2">
                                <i class="far fa-calendar-alt text-violet-400 text-xs"></i>
                                {{ currentDate }}
                            </p>
                        </div>

                        <!-- TARJETA DE DESBLOQUEO ELEGANTE -->
                        <div
                            class="w-full bg-gray-900/80 backdrop-blur-2xl border border-white/10 rounded-3xl p-7 shadow-2xl shadow-black/90 space-y-5 transition-all duration-300 hover:border-violet-500/30"
                            :class="{ 'shake-animation border-red-500/50 shadow-red-500/10': hasErrorShake }"
                        >
                            <!-- Perfil de usuario compacto -->
                            <div class="flex items-center gap-4 pb-4 border-b border-white/5">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-violet-600 via-indigo-600 to-purple-500 flex items-center justify-center text-white text-xl font-black shadow-lg shadow-violet-600/40 ring-2 ring-white/10">
                                        {{ user?.username?.charAt(0)?.toUpperCase() ?? 'U' }}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-black text-white truncate">{{ user?.username }}</h3>
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 mt-1 rounded-full bg-amber-500/15 border border-amber-500/30 text-amber-300 text-[11px] font-semibold">
                                        <i class="fas fa-lock text-[10px] text-amber-400"></i>
                                        <span>Terminal Bloqueada</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Formulario con input de contraseña estilizado -->
                            <form @submit.prevent="handleUnlock" class="space-y-4">
                                <div>
                                    <div class="relative flex items-center">
                                        <div class="absolute left-4 text-gray-500 flex items-center pointer-events-none">
                                            <i class="fas fa-lock text-xs"></i>
                                        </div>
                                        <input
                                            id="lock-password-input"
                                            v-model="password"
                                            type="password"
                                            placeholder="Introduce tu contraseña..."
                                            autocomplete="current-password"
                                            class="w-full bg-gray-950/90 border border-white/10 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/30 rounded-2xl pl-11 pr-4 py-3.5 text-white text-sm outline-none transition-all placeholder:text-gray-600 shadow-inner font-medium"
                                        />
                                    </div>

                                    <transition name="fade">
                                        <p v-if="unlockError" class="text-red-400 text-xs mt-2.5 font-semibold flex items-center gap-1.5">
                                            <i class="fas fa-exclamation-circle text-xs"></i>
                                            {{ unlockError }}
                                        </p>
                                    </transition>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="isSubmitting"
                                    class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-violet-600 via-indigo-600 to-purple-600 hover:from-violet-500 hover:to-indigo-500 text-white font-extrabold text-xs uppercase tracking-widest shadow-xl shadow-violet-600/30 hover:shadow-violet-600/50 transition-all duration-300 flex items-center justify-center gap-2 active:scale-[0.98] disabled:opacity-50 cursor-pointer"
                                >
                                    <i v-if="isSubmitting" class="fas fa-spinner fa-spin text-sm"></i>
                                    <i v-else class="fas fa-unlock text-xs"></i>
                                    <span>{{ isSubmitting ? 'Verificando...' : 'Desbloquear Terminal' }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </main>

                <!-- ============================================================ -->
                <!-- BOTTOM BAR: BANNER INFO + PROGRESS BAR PILLS                 -->
                <!-- ============================================================ -->
                <footer class="relative z-30 flex items-center justify-between px-8 py-5 backdrop-blur-md bg-gray-950/40 border-t border-white/5">
                    <!-- Banner Info: Título y Descripción de la Promoción -->
                    <div class="flex-1 min-w-0 mr-6">
                        <template v-if="carousels.length > 0 && carousels[currentIndex]">
                            <div class="flex items-start sm:items-center gap-3">
                                <span class="px-2.5 py-1 rounded-full bg-violet-500/20 text-violet-300 text-[10px] font-extrabold uppercase tracking-wider border border-violet-500/30 flex-shrink-0 mt-0.5 sm:mt-0 shadow-sm">
                                    Promoción
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-white text-sm font-black tracking-tight leading-snug truncate">
                                        {{ carousels[currentIndex].title }}
                                    </h4>
                                    <p
                                        v-if="carousels[currentIndex].description"
                                        class="text-gray-300 text-xs font-medium leading-relaxed line-clamp-1 mt-0.5"
                                        :title="carousels[currentIndex].description"
                                    >
                                        {{ carousels[currentIndex].description }}
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Progress Bar Pills -->
                    <div class="flex items-center gap-2">
                        <button
                            v-for="(item, idx) in carousels"
                            :key="item.id"
                            @click="goToSlide(idx)"
                            class="relative h-1.5 rounded-full overflow-hidden bg-white/20 hover:bg-white/40 transition-all duration-300 cursor-pointer"
                            :class="idx === currentIndex ? 'w-10' : 'w-3'"
                            :title="item.title || `Banner ${idx + 1}`"
                        >
                            <div
                                v-if="idx === currentIndex"
                                class="absolute top-0 left-0 bottom-0 bg-gradient-to-r from-violet-400 to-indigo-400 rounded-full transition-none"
                                :style="{ width: `${progress}%` }"
                            ></div>
                        </button>
                    </div>

                    <!-- Navigation controls -->
                    <div v-if="carousels.length > 1" class="flex items-center gap-2 ml-4">
                        <button
                            @click="prevSlide"
                            class="w-9 h-9 rounded-xl bg-gray-900/70 hover:bg-violet-600/40 text-gray-300 hover:text-white flex items-center justify-center border border-white/10 hover:border-violet-500/40 backdrop-blur-md transition-all shadow active:scale-95"
                            title="Anterior"
                        >
                            <i class="fas fa-chevron-left text-xs"></i>
                        </button>
                        <button
                            @click="nextSlide"
                            class="w-9 h-9 rounded-xl bg-gray-900/70 hover:bg-violet-600/40 text-gray-300 hover:text-white flex items-center justify-center border border-white/10 hover:border-violet-500/40 backdrop-blur-md transition-all shadow active:scale-95"
                            title="Siguiente"
                        >
                            <i class="fas fa-chevron-right text-xs"></i>
                        </button>
                    </div>
                </footer>
            </div>
        </transition>
    </teleport>
</template>

<style scoped>
/* Animación de entrada/salida modal */
.screen-fade-enter-active,
.screen-fade-leave-active {
    transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1), transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.screen-fade-enter-from,
.screen-fade-leave-to {
    opacity: 0;
    transform: scale(1.02);
}

/* Efecto Ken-Burns cinematográfico */
.ken-burns-img {
    transform: scale(1);
    transition: transform 6.5s cubic-bezier(0.25, 1, 0.5, 1), filter 1s ease;
    will-change: transform;
}
.ken-burns-active {
    transform: scale(1.08);
}

/* Sombra radial ambiental */
.bg-radial-vignette {
    background: radial-gradient(circle at center, transparent 20%, rgba(3, 7, 18, 0.9) 100%);
}

/* Shake de error */
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-8px); }
    40%, 80% { transform: translateX(8px); }
}
.shake-animation {
    animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
