<script setup>
function formatDate(dateStr) {
    if (!dateStr) return '\u2014';
    return new Date(dateStr).toLocaleString('es-NI', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', hour12: true,
    });
}

import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';
import AuthModal from '@/Components/AuthModal.vue';

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const props = defineProps({
    openSession: Object,
    metrics:     Object,
});

const showOpenModal       = ref(false);
const showWithdrawModal   = ref(false);
const showCloseModal      = ref(false);
const showAuthModal       = ref(false);
const authError           = ref('');

const denominationValues = [1000, 500, 200, 100, 50, 20, 10, 5, 1];

function createEmptyDenominations() {
    return { 1000: 0, 500: 0, 200: 0, 100: 0, 50: 0, 20: 0, 10: 0, 5: 0, 1: 0 };
}

const openDenoms     = ref(createEmptyDenominations());
const withdrawDenoms = ref(createEmptyDenominations());
const closeDenoms    = ref(createEmptyDenominations());

const openMode     = ref('denominations');
const withdrawMode = ref('denominations');
const closeMode    = ref('denominations');

const openForm = useForm({ opening_amount: 0, denominations: {}, notes: '' });
const withdrawForm = useForm({ total_amount: 0, denominations: {}, reason: '' });
const closeForm = useForm({ counted_amount: 0, denominations: {}, admin_username: '', admin_password: '' });

watch(openDenoms, (vals) => {
    if (openMode.value === 'denominations') {
        let sum = 0;
        for (const [d, c] of Object.entries(vals)) sum += (parseInt(d)||0) * (parseInt(c)||0);
        openForm.opening_amount = sum;
        openForm.denominations  = { ...vals };
    }
}, { deep: true });

watch(withdrawDenoms, (vals) => {
    if (withdrawMode.value === 'denominations') {
        let sum = 0;
        for (const [d, c] of Object.entries(vals)) sum += (parseInt(d)||0) * (parseInt(c)||0);
        withdrawForm.total_amount  = sum;
        withdrawForm.denominations = { ...vals };
    }
}, { deep: true });

watch(closeDenoms, (vals) => {
    if (closeMode.value === 'denominations') {
        let sum = 0;
        for (const [d, c] of Object.entries(vals)) sum += (parseInt(d)||0) * (parseInt(c)||0);
        closeForm.counted_amount = sum;
        closeForm.denominations  = { ...vals };
    }
}, { deep: true });

function openOpeningModal()    { openForm.reset(); openForm.clearErrors(); openDenoms.value = createEmptyDenominations(); openMode.value = 'denominations'; showOpenModal.value = true; }
function closeOpeningModal()   { showOpenModal.value = false; openForm.reset(); }
function openWithdrawalModal() { withdrawForm.reset(); withdrawForm.clearErrors(); withdrawDenoms.value = createEmptyDenominations(); withdrawMode.value = 'denominations'; showWithdrawModal.value = true; }
function closeWithdrawalModal(){ showWithdrawModal.value = false; withdrawForm.reset(); }
function openClosingModal() {
    closeForm.clearErrors();
    closeDenoms.value = createEmptyDenominations();
    closeMode.value = 'denominations';
    closeForm.counted_amount = props.metrics?.expectedCash || 0;
    showCloseModal.value = true;
}
function closeClosingModal()   { showCloseModal.value = false; closeForm.reset(); }

function submitOpen() {
    openForm.post(route('cash.open'), { onSuccess: () => closeOpeningModal() });
}
function submitWithdrawal() {
    withdrawForm.post(route('cash.withdraw'), {
        onSuccess: (page) => {
            closeWithdrawalModal();
            if (page.props.flash?.withdrawal_id) {
                window.open(route('cash.withdrawal.pdf', page.props.flash.withdrawal_id), '_blank');
            }
        }
    });
}
const isAuthValidating = ref(false);

function openClosingReport() {
    if (props.openSession?.id) {
        window.open(route('cash.session.pdf', props.openSession.id), '_blank');
    }
}

function handleCloseFormSubmit() {
    authError.value = '';
    closeForm.admin_username = '';
    closeForm.admin_password = '';
    showAuthModal.value = true;
}

async function handleAuthConfirm({ username, password }) {
    authError.value = '';
    isAuthValidating.value = true;

    try {
        const { data } = await axios.post(route('cash.validateAuth'), {
            username,
            password,
        });

        if (data.success) {
            closeForm.admin_username = username;
            closeForm.admin_password = password;
            showAuthModal.value = false;
            submitClose();
        } else {
            authError.value = data.message || 'Credenciales inválidas o no autorizadas.';
        }
    } catch (err) {
        authError.value = err.response?.data?.message || 'Credenciales inválidas o el usuario no tiene permisos de Administrador.';
    } finally {
        isAuthValidating.value = false;
    }
}

function submitClose() {
    closeForm.post(route('cash.close'), {
        onSuccess: (page) => {
            closeClosingModal();
            if (page.props.flash?.closed_session_id) {
                window.open(route('cash.session.pdf', page.props.flash.closed_session_id), '_blank');
            }
        },
        onError: (errors) => {
            if (errors.auth) {
                authError.value = errors.auth;
                showAuthModal.value = true;
            }
        },
    });
}

function handleKeydown(e) {
    if (e.key === 'Escape') {
        if (showOpenModal.value)      closeOpeningModal();
        if (showWithdrawModal.value)  closeWithdrawalModal();
        if (showCloseModal.value)     closeClosingModal();
    }
}
onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));

const fmtMoney   = (n) => 'C$ ' + (parseFloat(n) || 0).toFixed(2);
const fmtMonoAmt = (n) => {
    const s = (parseFloat(n) || 0).toFixed(2);
    return s.padStart(10, ' ');
};
const nowStr = () => new Date().toLocaleString('es-NI');
</script>

<template>
    <Head title="Control de Caja – Farmacia Solei" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3 w-full">
                <div class="flex items-center gap-3 shrink-0">
                    <div class="w-10 h-10 rounded-xl bg-violet-600/15 border border-violet-500/20 flex items-center justify-center text-violet-400 shrink-0">
                        <i class="fas fa-cash-register text-base"></i>
                    </div>
                    <div>
                        <h1 class="text-white font-bold text-lg leading-tight">Control de Caja y Arqueos</h1>
                        <p class="text-xs text-gray-400 mt-0.5 hidden xl:block">
                            Apertura de turno, retiros de efectivo y arqueo diario de caja
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 ml-auto">
                    <Link :href="route('cash.history')"
                        class="shrink-0 whitespace-nowrap inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-gray-800/90 hover:bg-gray-700 text-gray-300 hover:text-white border border-white/5 text-xs font-semibold shadow-sm transition-all active:scale-95">
                        <i class="fas fa-history text-xs"></i>
                        <span>Historial de Cierres</span>
                    </Link>
                    <button v-if="!openSession" @click="openOpeningModal"
                        class="shrink-0 whitespace-nowrap inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-lg shadow-emerald-900/25 border border-emerald-500/20 transition-all active:scale-95 cursor-pointer">
                        <i class="fas fa-key text-xs"></i>
                        <span>Abrir Caja</span>
                    </button>
                    <template v-else>
                        <button @click="openWithdrawalModal"
                            class="shrink-0 whitespace-nowrap inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white font-semibold text-xs shadow-lg shadow-amber-900/25 border border-amber-500/20 transition-all active:scale-95 cursor-pointer">
                            <i class="fas fa-hand-holding-usd text-xs"></i>
                            <span>Retiro de Efectivo</span>
                        </button>
                        <button @click="openClosingModal"
                            class="shrink-0 whitespace-nowrap inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white font-bold text-xs shadow-lg shadow-red-900/25 border border-red-500/20 transition-all active:scale-95 cursor-pointer">
                            <i class="fas fa-lock text-xs"></i>
                            <span>Cerrar Caja</span>
                        </button>
                    </template>
                </div>
            </div>
        </template>

        <!-- Sin Sesión -->
        <div v-if="!openSession" class="bg-gray-900 rounded-2xl border border-white/5 p-12 text-center max-w-xl mx-auto my-12 shadow-2xl">
            <div class="w-20 h-20 rounded-full bg-violet-600/10 border border-violet-500/20 text-violet-400 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-cash-register text-3xl"></i>
            </div>
            <h2 class="text-white text-xl font-bold mb-2">No hay caja abierta actualmente</h2>
            <p class="text-gray-400 text-sm mb-6">Para iniciar a procesar ventas en el Punto de Venta (POS), debes realizar la apertura con el monto inicial en gaveta.</p>
            <button @click="openOpeningModal" class="px-8 py-3.5 rounded-2xl bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white font-bold text-base shadow-xl shadow-violet-900/40 transition-all">
                <i class="fas fa-key mr-2"></i> Abrir Turno de Caja
            </button>
        </div>

        <!-- Dashboard Caja Activa -->
        <div v-else class="space-y-6">
            <div class="bg-gradient-to-r from-violet-950/40 to-gray-900 border border-violet-500/20 rounded-2xl p-6 shadow-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></span>
                        <h2 class="text-white font-bold text-lg">Caja Abierta – Turno Activo</h2>
                    </div>
                    <p class="text-gray-400 text-xs">Abierta por <strong class="text-violet-300">{{ openSession.opener?.username }}</strong> el {{ formatDate(openSession?.opened_at) }}</p>
                </div>
                <div class="text-right">
                    <p class="text-gray-400 text-xs uppercase font-medium">Efectivo Esperado en Gaveta</p>
                    <p class="text-emerald-400 text-3xl font-extrabold font-mono">{{ fmtMoney(metrics?.expectedCash) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-gray-900 border border-white/5 rounded-2xl p-5 shadow-lg">
                    <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-1">Monto Inicial Apertura</p>
                    <p class="text-white text-2xl font-bold font-mono">{{ fmtMoney(metrics?.openingAmount) }}</p>
                </div>
                <div class="bg-gray-900 border border-white/5 rounded-2xl p-5 shadow-lg">
                    <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-1">Ventas en Efectivo (+)</p>
                    <p class="text-emerald-400 text-2xl font-bold font-mono">{{ fmtMoney(metrics?.cashSales) }}</p>
                </div>
                <div class="bg-gray-900 border border-white/5 rounded-2xl p-5 shadow-lg">
                    <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-1">Retiros de Efectivo (-)</p>
                    <p class="text-amber-400 text-2xl font-bold font-mono">{{ fmtMoney(metrics?.totalWithdrawals) }}</p>
                </div>
                <div class="bg-gray-900 border border-white/5 rounded-2xl p-5 shadow-lg">
                    <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-1">Ventas Tarjeta / Transf.</p>
                    <p class="text-violet-400 text-2xl font-bold font-mono">{{ fmtMoney((metrics?.cardSales || 0) + (metrics?.transferSales || 0)) }}</p>
                </div>
            </div>

            <!-- Tabla Retiros -->
            <div class="bg-gray-900 rounded-2xl border border-white/5 overflow-hidden shadow-xl">
                <div class="px-6 py-4 border-b border-white/5">
                    <h3 class="text-white font-semibold text-sm flex items-center gap-2">
                        <i class="fas fa-hand-holding-usd text-amber-400"></i> Retiros de Efectivo – Turno Activo
                    </h3>
                </div>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-white/5 bg-gray-950/40">
                            <th class="px-6 py-3">Hora</th>
                            <th class="px-4 py-3">Motivo</th>
                            <th class="px-4 py-3 text-right">Monto</th>
                            <th class="px-4 py-3">Registrado Por</th>
                            <th class="px-6 py-3 text-right">Ticket</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-gray-300">
                        <tr v-for="w in openSession.withdrawals" :key="w.id">
                            <td class="px-6 py-3 text-xs text-gray-400">{{ formatDate(w?.created_at) }}</td>
                            <td class="px-4 py-3 font-medium text-white">{{ w.reason }}</td>
                            <td class="px-4 py-3 text-right font-bold text-amber-400">-{{ fmtMoney(w.total_amount) }}</td>
                            <td class="px-4 py-3 text-xs text-gray-400">{{ w.withdrawer?.username }}</td>
                            <td class="px-6 py-3 text-right">
                                <a :href="route('cash.withdrawal.pdf', w.id)" target="_blank"
                                    class="px-3 py-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 text-amber-300 text-xs font-semibold transition-colors inline-flex items-center gap-1.5 border border-amber-500/20"
                                    title="Imprimir Comprobante de Retiro en PDF">
                                    <i class="fas fa-file-pdf text-amber-400"></i> Ticket Retiro
                                </a>
                            </td>
                        </tr>
                        <tr v-if="!openSession.withdrawals?.length">
                            <td colspan="5" class="px-6 py-8 text-center text-gray-600 text-sm">
                                No se han registrado retiros en esta sesión.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- â•â• MODAL APERTURA â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <Transition name="fade">
        <div v-if="showOpenModal" @click.self="closeOpeningModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm overflow-y-auto">
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-lg my-6 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/40">
                    <h3 class="text-white font-semibold flex items-center gap-2"><i class="fas fa-key text-violet-400"></i> Apertura de Turno de Caja</h3>
                    <button @click="closeOpeningModal" class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center"><i class="fas fa-times text-sm"></i></button>
                </div>
                <form @submit.prevent="submitOpen" class="p-6 space-y-4">
                    <div class="flex bg-gray-800 p-1 rounded-xl">
                        <button type="button" @click="openMode = 'denominations'" class="flex-1 py-1.5 rounded-lg text-xs font-bold transition-all" :class="openMode==='denominations'?'bg-violet-600 text-white':'text-gray-400'">Conteo por Denominación (C$)</button>
                        <button type="button" @click="openMode = 'direct'" class="flex-1 py-1.5 rounded-lg text-xs font-bold transition-all" :class="openMode==='direct'?'bg-violet-600 text-white':'text-gray-400'">Monto Directo</button>
                    </div>
                    <div v-if="openMode === 'denominations'" class="space-y-2">
                        <label class="block text-gray-400 text-xs font-medium uppercase tracking-wide">Desglose Billetes y Monedas (C$)</label>
                        <div class="grid grid-cols-3 gap-2 bg-gray-950/40 p-3 rounded-xl border border-white/5">
                            <div v-for="denom in denominationValues" :key="denom" class="bg-gray-800/80 p-2 rounded-xl border border-white/5">
                                <div class="flex justify-between items-center text-xs mb-1">
                                    <span class="font-bold text-violet-300">C$ {{ denom.toLocaleString() }}</span>
                                    <span class="text-gray-400 text-[10px]">C$ {{ (openDenoms[denom]||0)*denom }}</span>
                                </div>
                                <input v-model.number="openDenoms[denom]" type="number" min="0" placeholder="0" class="w-full bg-gray-900 border border-white/10 rounded-lg px-2 py-1 text-center text-white font-bold text-xs outline-none focus:border-violet-500" />
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Monto Inicial (C$) *</label>
                        <input v-model.number="openForm.opening_amount" type="number" step="0.01" min="0" required placeholder="0.00" class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-3 text-white text-xl font-bold outline-none" />
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Notas (opcional)</label>
                        <textarea v-model="openForm.notes" rows="2" placeholder="Observaciones..." class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-2 text-white text-sm outline-none"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-white/5">
                        <button type="button" @click="closeOpeningModal" class="px-4 py-2.5 rounded-xl bg-gray-800 text-gray-300 text-sm">Cancelar</button>
                        <button type="submit" :disabled="openForm.processing" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 text-white font-bold text-sm flex items-center gap-2">
                            <i v-if="openForm.processing" class="fas fa-spinner fa-spin"></i><i v-else class="fas fa-check-circle"></i>
                            {{ openForm.processing ? 'Abriendo...' : 'Abrir Caja' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </Transition>

        <!-- â•â• MODAL RETIRO â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <Transition name="fade">
        <div v-if="showWithdrawModal" @click.self="closeWithdrawalModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm overflow-y-auto">
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-lg my-6 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/40">
                    <h3 class="text-white font-semibold flex items-center gap-2"><i class="fas fa-hand-holding-usd text-amber-400"></i> Registrar Retiro de Efectivo</h3>
                    <button @click="closeWithdrawalModal" class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center"><i class="fas fa-times text-sm"></i></button>
                </div>
                <form @submit.prevent="submitWithdrawal" class="p-6 space-y-4">
                    <div class="flex bg-gray-800 p-1 rounded-xl">
                        <button type="button" @click="withdrawMode='denominations'" class="flex-1 py-1.5 rounded-lg text-xs font-bold transition-all" :class="withdrawMode==='denominations'?'bg-amber-600 text-white':'text-gray-400'">Conteo por Denominación (C$)</button>
                        <button type="button" @click="withdrawMode='direct'" class="flex-1 py-1.5 rounded-lg text-xs font-bold transition-all" :class="withdrawMode==='direct'?'bg-amber-600 text-white':'text-gray-400'">Monto Directo</button>
                    </div>
                    <div v-if="withdrawMode === 'denominations'" class="space-y-2">
                        <label class="block text-gray-400 text-xs font-medium uppercase tracking-wide">Billetes a Retirar (C$)</label>
                        <div class="grid grid-cols-3 gap-2 bg-gray-950/40 p-3 rounded-xl border border-white/5">
                            <div v-for="denom in denominationValues" :key="denom" class="bg-gray-800/80 p-2 rounded-xl border border-white/5">
                                <div class="flex justify-between items-center text-xs mb-1">
                                    <span class="font-bold text-amber-300">C$ {{ denom.toLocaleString() }}</span>
                                    <span class="text-gray-400 text-[10px]">C$ {{ (withdrawDenoms[denom]||0)*denom }}</span>
                                </div>
                                <input v-model.number="withdrawDenoms[denom]" type="number" min="0" placeholder="0" class="w-full bg-gray-900 border border-white/10 rounded-lg px-2 py-1 text-center text-white font-bold text-xs outline-none focus:border-amber-500" />
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Monto a Retirar (C$) *</label>
                        <input v-model.number="withdrawForm.total_amount" type="number" step="0.01" min="0.01" required placeholder="0.00" class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-3 text-amber-400 text-xl font-bold outline-none" />
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Motivo del retiro *</label>
                        <textarea v-model="withdrawForm.reason" rows="2" required placeholder="Especifica la razón del retiro..." class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-white/5">
                        <button type="button" @click="closeWithdrawalModal" class="px-4 py-2.5 rounded-xl bg-gray-800 text-gray-300 text-sm">Cancelar</button>
                        <button type="submit" :disabled="withdrawForm.processing" class="px-6 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-sm flex items-center gap-2">
                            <i v-if="withdrawForm.processing" class="fas fa-spinner fa-spin"></i><i v-else class="fas fa-check"></i>
                            {{ withdrawForm.processing ? 'Registrando...' : 'Confirmar Retiro' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </Transition>
        
        <Transition name="fade">
        <div v-if="showCloseModal" @click.self="closeClosingModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm overflow-y-auto">
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-2xl my-4 overflow-hidden flex flex-col max-h-[90vh]">

                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/60 flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center">
                            <i class="fas fa-calculator text-emerald-400 text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-sm">Arqueo y Cierre de Caja</h3>
                            <p class="text-gray-500 text-[11px]">Registra el conteo físico para cerrar el turno</p>
                        </div>
                    </div>
                    <button @click="closeClosingModal" class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-colors">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <!-- Body con scroll -->
                <form @submit.prevent="handleCloseFormSubmit" class="flex flex-col flex-1 overflow-hidden">
                    <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5 custom-scrollbar">

                        <!-- Resumen del turno -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-gray-800/60 rounded-xl p-3.5 border border-white/5">
                                <p class="text-[10px] text-gray-500 uppercase font-semibold tracking-wider mb-1">Esperado en Gaveta</p>
                                <p class="text-emerald-400 font-extrabold text-xl font-mono">{{ fmtMoney(metrics?.expectedCash) }}</p>
                            </div>
                            <div class="bg-gray-800/60 rounded-xl p-3.5 border border-white/5">
                                <p class="text-[10px] text-gray-500 uppercase font-semibold tracking-wider mb-1">Total Ingresos del Turno</p>
                                <p class="text-violet-400 font-extrabold text-xl font-mono">{{ fmtMoney(metrics?.totalSales) }}</p>
                            </div>
                        </div>

                        <!-- Tabs modo conteo -->
                        <div class="flex bg-gray-800/80 p-1 rounded-xl border border-white/5">
                            <button type="button" @click="closeMode='denominations'"
                                class="flex-1 py-2 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5"
                                :class="closeMode==='denominations' ? 'bg-emerald-600 text-white shadow-lg' : 'text-gray-400 hover:text-gray-200'">
                                <i class="fas fa-coins text-[11px]"></i> Conteo por Denominación
                            </button>
                            <button type="button" @click="closeMode='direct'"
                                class="flex-1 py-2 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5"
                                :class="closeMode==='direct' ? 'bg-emerald-600 text-white shadow-lg' : 'text-gray-400 hover:text-gray-200'">
                                <i class="fas fa-keyboard text-[11px]"></i> Monto Directo
                            </button>
                        </div>

                        <!-- Grilla de denominaciones -->
                        <div v-if="closeMode === 'denominations'" class="space-y-2.5">
                            <p class="text-[10px] text-gray-500 uppercase font-semibold tracking-wider">Billetes y Monedas (C$)</p>
                            <div class="grid grid-cols-3 gap-2">
                                <div v-for="denom in denominationValues" :key="denom"
                                    class="bg-gray-800 rounded-xl border border-white/5 hover:border-emerald-500/30 transition-colors overflow-hidden">
                                    <div class="bg-emerald-500/10 px-3 py-1.5 flex items-center justify-between">
                                        <span class="text-emerald-300 font-extrabold text-xs">C$ {{ denom.toLocaleString() }}</span>
                                        <span class="text-emerald-400/60 text-[10px] font-mono">x{{ closeDenoms[denom]||0 }}</span>
                                    </div>
                                    <div class="px-2 py-2">
                                        <input v-model.number="closeDenoms[denom]" type="number" min="0" placeholder="0"
                                            class="w-full bg-gray-900/80 border border-white/5 focus:border-emerald-500 rounded-lg px-2 py-1.5 text-center text-white font-bold text-sm outline-none transition-colors" />
                                        <p class="text-center text-[10px] text-gray-500 mt-1 font-mono">
                                            = C$ {{ ((closeDenoms[denom]||0)*denom).toLocaleString() }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Campo monto total contado -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] text-gray-500 uppercase font-semibold tracking-wider">
                                Total Contado Físicamente (C$) *
                            </label>
                            <input v-model.number="closeForm.counted_amount" type="number" step="0.01" min="0" required placeholder="0.00"
                                class="w-full bg-gray-800 border border-white/10 focus:border-emerald-500 rounded-xl px-5 py-3.5 text-white text-2xl font-extrabold font-mono outline-none transition-colors text-center" />
                        </div>

                        <!-- Diferencia -->
                        <div class="flex items-center justify-between px-5 py-4 rounded-xl border transition-colors"
                            :class="((closeForm.counted_amount||0)-(metrics?.expectedCash||0)) < 0
                                ? 'bg-red-950/40 border-red-500/20'
                                : 'bg-emerald-950/40 border-emerald-500/20'">
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase font-semibold tracking-wider">Diferencia</p>
                                <p class="text-xs text-gray-500 mt-0.5">Contado vs. Esperado en gaveta</p>
                            </div>
                            <div class="text-right">
                                <p class="font-extrabold text-2xl font-mono"
                                    :class="((closeForm.counted_amount||0)-(metrics?.expectedCash||0)) < 0 ? 'text-red-400' : 'text-emerald-400'">
                                    {{ fmtMoney((closeForm.counted_amount||0)-(metrics?.expectedCash||0)) }}
                                </p>
                                <p class="text-[10px] mt-0.5"
                                    :class="((closeForm.counted_amount||0)-(metrics?.expectedCash||0)) < 0 ? 'text-red-500' : 'text-emerald-600'">
                                    {{ ((closeForm.counted_amount||0)-(metrics?.expectedCash||0)) < 0 ? 'Faltante' : 'Sobrante / En regla' }}
                                </p>
                            </div>
                        </div>

                    </div>

                    <!-- Footer acciones -->
                    <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-white/5 bg-gray-950/40 flex-shrink-0">
                        <button type="button" @click="closeClosingModal"
                            class="px-4 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm font-medium transition-colors">
                            Cancelar
                        </button>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="openClosingReport"
                                class="px-4 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-violet-300 border border-violet-500/30 text-sm font-semibold flex items-center gap-2 transition-colors">
                                <i class="fas fa-print text-xs"></i> Ver Reporte
                            </button>
                            <button type="submit" :disabled="closeForm.processing"
                                class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white font-bold text-sm flex items-center gap-2 shadow-lg shadow-red-900/30 transition-all active:scale-95 disabled:opacity-60">
                                <i v-if="closeForm.processing" class="fas fa-spinner fa-spin text-xs"></i>
                                <i v-else class="fas fa-lock text-xs"></i>
                                {{ closeForm.processing ? 'Cerrando...' : 'Finalizar y Cerrar Caja' }}
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
        </Transition>

        <!-- â•â• TICKET TÉRMICO: RETIRO DE EFECTIVO â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
             El div .thermal-receipt es el ÚNICO visible al imprimir (ver app.css)
        â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->


        <!-- â•â• TICKET TÉRMICO: REPORTE DE CIERRE Y ARQUEO â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->

    
        <!-- ══ MODAL DE AUTORIZACIÓN PARA CIERRE (ADMIN / SUPERADMIN) ═══════ -->
        <AuthModal
            :show="showAuthModal"
            title="Autorización para Cierre de Caja"
            subtitle="Ingrese las credenciales de un Administrador o Superadmin para autorizar el arqueo y cierre definitivo de la caja."
            actionLabel="Autorizar y Cerrar Caja"
            :processing="isAuthValidating || closeForm.processing"
            :errorMessage="authError"
            @close="showAuthModal = false"
            @confirm="handleAuthConfirm"
        />
    </AdminLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>


