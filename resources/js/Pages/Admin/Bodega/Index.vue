<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();

const props = defineProps({
    products:             Object,
    recentMovements:      Array,
    filters:              Object,
    branches:             Array,
    activeBranch:         String,
    pendingRequest:       Object,
    needsBranchSelection: Boolean,
    accessRequests:       Object,
});

const search = ref(props.filters?.q || '');
let searchTimer = null;

watch(search, (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route('bodega.index'), { q: val || undefined }, { preserveState: true, replace: true });
    }, 300);
});

// ── Estado de Acceso y Solicitudes para Superadmin ──
const currentPendingRequest = ref(props.pendingRequest || null);
const showSelectBranchModal = ref(props.needsBranchSelection && !props.activeBranch && !props.pendingRequest);
const showLogModal          = ref(false);

const requestBranchForm = useForm({
    branch: props.branches?.[0] || 'Sucursal Managua',
});

let statusPollTimer = null;

function startStatusPolling() {
    stopStatusPolling();
    if (!currentPendingRequest.value) return;
    statusPollTimer = setInterval(async () => {
        if (!currentPendingRequest.value) return;
        try {
            const res = await axios.get(route('bodega.access-requests.status', currentPendingRequest.value.id));
            if (res.data?.status === 'approved') {
                stopStatusPolling();
                currentPendingRequest.value = null;
                router.reload();
            } else if (res.data?.status === 'rejected') {
                stopStatusPolling();
                alert('El Administrador de la sucursal ha rechazado su solicitud de acceso a bodega.');
                currentPendingRequest.value = null;
                showSelectBranchModal.value = true;
                router.reload();
            } else if (res.data?.status === 'cancelled') {
                stopStatusPolling();
                currentPendingRequest.value = null;
                showSelectBranchModal.value = true;
            }
        } catch (e) {
            // Silencio en errores de red momentáneos
        }
    }, 2500);
}

function stopStatusPolling() {
    if (statusPollTimer) {
        clearInterval(statusPollTimer);
        statusPollTimer = null;
    }
}

watch(
    () => props.pendingRequest,
    (val) => {
        currentPendingRequest.value = val || null;
        if (val) startStatusPolling();
    },
    { immediate: true }
);

function submitAccessRequest() {
    requestBranchForm.post(route('bodega.request-access'), {
        onSuccess: () => {
            showSelectBranchModal.value = false;
        }
    });
}

function cancelWaitingRequest() {
    if (!currentPendingRequest.value) return;
    router.post(route('bodega.access-requests.cancel', currentPendingRequest.value.id), {}, {
        onSuccess: () => {
            stopStatusPolling();
            currentPendingRequest.value = null;
            showSelectBranchModal.value = true;
        }
    });
}

function exitActiveBranch() {
    router.post(route('bodega.exit-branch'), {}, {
        onSuccess: () => {
            showSelectBranchModal.value = true;
        }
    });
}

function closeSelectBranchModal() {
    showSelectBranchModal.value = false;
}

// ── Modal de Traslado de Bodega ──
const showModal       = ref(false);
const selectedProduct = ref(null);

const form = useForm({
    product_id:  '',
    from_bodega: 'sucursal',
    to_bodega:   'debito',
    quantity:    1,
    reason:      '',
});

function openTransferModal(product) {
    selectedProduct.value = product;
    form.reset();
    form.clearErrors();
    form.product_id  = product.id;
    showModal.value  = true;
}

function closeModal() {
    showModal.value       = false;
    selectedProduct.value = null;
    form.reset();
    form.clearErrors();
}

function submitTransfer() {
    form.post(route('bodega.transfer'), {
        onSuccess: () => closeModal(),
    });
}

function handleKeydown(e) {
    if (e.key === 'Escape') {
        if (showModal.value) closeModal();
        if (showLogModal.value) showLogModal.value = false;
        if (showSelectBranchModal.value) closeSelectBranchModal();
        if (currentPendingRequest.value) cancelWaitingRequest();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
    if (currentPendingRequest.value) {
        startStatusPolling();
    }
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    stopStatusPolling();
});

const bodegaNames = { sucursal: '🏪 Sucursal (Venta)', debito: '📦 Bodega Débito', merma: '🗑️ Merma' };
</script>

<template>
    <Head title="Control de Bodegas — Farmacia Solei" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <i class="fas fa-warehouse text-violet-400 text-xl"></i>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-white font-semibold text-lg">Control de Bodegas Independientes</h1>
                            <span v-if="activeBranch" class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-violet-500/15 text-violet-300 border border-violet-500/30 flex items-center gap-1.5">
                                <i class="fas fa-store text-[10px]"></i>
                                {{ activeBranch }}
                            </span>
                        </div>
                        <p class="text-gray-400 text-xs">Gestión interna de almacenes (Piso de venta, Débito y Merma)</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Botón Seleccionar Sucursal para Superadmin sin sucursal activa -->
                    <button
                        v-if="!page.props.auth?.user?.branch && !activeBranch"
                        type="button"
                        @click="showSelectBranchModal = true"
                        class="px-3.5 py-1.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white font-bold text-xs shadow-lg shadow-violet-900/30 flex items-center gap-1.5 transition-all cursor-pointer"
                    >
                        <i class="fas fa-store text-xs"></i>
                        <span>Seleccionar Sucursal</span>
                    </button>

                    <!-- Botón Cambiar Sucursal para Superadmin -->
                    <button
                        v-if="!page.props.auth?.user?.branch && activeBranch"
                        type="button"
                        @click="exitActiveBranch"
                        class="px-3 py-1.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs font-medium border border-white/10 flex items-center gap-1.5 transition-colors cursor-pointer"
                        title="Cambiar a otra sucursal"
                    >
                        <i class="fas fa-exchange-alt text-violet-400 text-xs"></i>
                        <span>Cambiar Sucursal</span>
                    </button>

                    <!-- Botón Log de Solicitudes exclusivo para Administrador de Sucursal -->
                    <button
                        v-if="accessRequests"
                        type="button"
                        @click="showLogModal = true"
                        class="px-3.5 py-2 rounded-xl bg-violet-600/20 hover:bg-violet-600/30 text-violet-300 text-xs font-bold border border-violet-500/30 flex items-center gap-2 transition-all cursor-pointer shadow-sm"
                        title="Ver solicitudes de acceso recibidas de Superadmins"
                    >
                        <i class="fas fa-clipboard-list text-violet-400"></i>
                        <span>Historial de Solicitudes</span>
                        <span v-if="accessRequests.total > 0" class="px-1.5 py-0.2 rounded-full bg-violet-500/30 text-[10px]">
                            {{ accessRequests.total }}
                        </span>
                    </button>

                    <span class="text-xs bg-white/5 text-gray-400 px-2.5 py-1.5 rounded-xl border border-white/5 font-mono">
                        {{ products.total }} productos
                    </span>
                </div>
            </div>
        </template>

        <!-- Search Bar -->
        <div class="mb-6">
            <div class="relative max-w-md">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar producto por nombre o código de barras..."
                    class="w-full pl-11 pr-4 py-2.5 bg-gray-900 border border-white/10 focus:border-violet-500 rounded-xl text-white placeholder-gray-600 text-sm outline-none transition-colors"
                />
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- Tabla de Productos por Bodega -->
            <div class="xl:col-span-2 bg-gray-900 rounded-2xl border border-white/5 overflow-hidden shadow-xl">
                <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
                    <h3 class="text-white font-semibold text-sm flex items-center gap-2">
                        <span>Inventario por Bodega</span>
                        <span v-if="activeBranch" class="text-xs font-normal text-gray-400">({{ activeBranch }})</span>
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-white/5 bg-gray-950/40">
                                <th class="px-6 py-4">Producto</th>
                                <th class="px-4 py-4 text-center">Sucursal (Venta)</th>
                                <th class="px-4 py-4 text-center">Débito</th>
                                <th class="px-4 py-4 text-center">Merma</th>
                                <th class="px-6 py-4 text-right">Trasladar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-gray-300">
                            <tr v-for="p in products.data" :key="p.id" class="hover:bg-white/2 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="text-white font-medium text-sm">{{ p.name }}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span v-if="p.barcode" class="text-gray-500 text-xs font-mono">{{ p.barcode }}</span>
                                        <span v-if="p.branch" class="text-[10px] text-gray-400 bg-white/5 px-2 py-0.5 rounded border border-white/5">
                                            {{ p.branch }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Sucursal Stock -->
                                <td class="px-4 py-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-400 font-bold text-xs border border-emerald-500/20">
                                        {{ p.sucursal_stock }}
                                    </span>
                                </td>

                                <!-- Débito Stock -->
                                <td class="px-4 py-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full bg-amber-500/15 text-amber-400 font-bold text-xs border border-amber-500/20">
                                        {{ p.debito_stock }}
                                    </span>
                                </td>

                                <!-- Merma Stock -->
                                <td class="px-4 py-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full bg-red-500/15 text-red-400 font-bold text-xs border border-red-500/20">
                                        {{ p.merma_stock }}
                                    </span>
                                </td>

                                <!-- Botón Trasladar -->
                                <td class="px-6 py-4 text-right">
                                    <button
                                        @click="openTransferModal(p)"
                                        class="px-3 py-1.5 rounded-lg bg-violet-600/20 hover:bg-violet-600/40 text-violet-300 border border-violet-500/30 text-xs font-semibold transition-colors inline-flex items-center gap-1 cursor-pointer"
                                    >
                                        <i class="fas fa-exchange-alt"></i> Trasladar
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!products.data.length">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <template v-if="!activeBranch">
                                        <div class="py-6 space-y-3">
                                            <div class="w-14 h-14 rounded-2xl bg-violet-500/10 border border-violet-500/20 flex items-center justify-center mx-auto text-violet-400">
                                                <i class="fas fa-store text-2xl"></i>
                                            </div>
                                            <div>
                                                <p class="text-white font-bold text-sm">Ninguna sucursal seleccionada</p>
                                                <p class="text-gray-400 text-xs mt-1 max-w-sm mx-auto">Selecciona una sucursal para visualizar su inventario y operar traslados de bodega.</p>
                                            </div>
                                            <button
                                                type="button"
                                                @click="showSelectBranchModal = true"
                                                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white font-bold text-xs shadow-lg shadow-violet-900/30 inline-flex items-center gap-2 cursor-pointer transition-all"
                                            >
                                                <i class="fas fa-store"></i> Seleccionar una Sucursal a Visualizar
                                            </button>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <i class="fas fa-warehouse text-4xl mb-3 text-gray-600"></i>
                                        <p class="text-sm font-medium">No se encontraron productos registrados en esta sucursal</p>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="products.links?.length > 3" class="flex items-center justify-between px-6 py-4 border-t border-white/5 bg-gray-950/20">
                    <span class="text-xs text-gray-500">Mostrando {{ products.from }} - {{ products.to }} de {{ products.total }}</span>
                    <div class="flex gap-1">
                        <template v-for="(link, i) in products.links" :key="i">
                            <Link v-if="link.url" :href="link.url" v-html="link.label"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                :class="link.active ? 'bg-violet-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-white'" />
                            <span v-else v-html="link.label" class="px-3 py-1.5 rounded-lg text-xs text-gray-700"></span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Últimos Movimientos de Bodega -->
            <div class="bg-gray-900 rounded-2xl border border-white/5 overflow-hidden flex flex-col h-fit shadow-xl">
                <div class="px-6 py-4 border-b border-white/5">
                    <h3 class="text-white font-semibold text-sm flex items-center gap-2">
                        <i class="fas fa-history text-violet-400"></i>
                        Últimos Traslados Internos
                    </h3>
                </div>
                <div class="divide-y divide-white/5">
                    <div v-for="m in recentMovements" :key="m.id" class="p-4 space-y-1">
                        <div class="flex items-center justify-between">
                            <p class="text-white font-medium text-xs truncate max-w-44">{{ m.product?.name }}</p>
                            <span class="text-violet-400 font-bold text-xs font-mono">x{{ m.quantity }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-gray-400">
                            <span>{{ bodegaNames[m.from_bodega] }} → {{ bodegaNames[m.to_bodega] }}</span>
                            <span class="text-gray-300 font-medium">@{{ m.user?.username }}</span>
                        </div>
                    </div>
                    <div v-if="!recentMovements.length" class="p-6 text-center text-gray-500 text-xs">
                        Sin movimientos recientes en esta sucursal
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ MODAL 1: SELECCIONAR SUCURSAL PARA SUPERADMIN ═══════════════════ -->
        <Transition name="fade">
        <div v-if="showSelectBranchModal && !currentPendingRequest && !activeBranch" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm">
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-scaleIn">
                <div class="flex items-center justify-between px-6 py-5 bg-gradient-to-r from-violet-950/70 to-gray-950 border-b border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-violet-600/20 border border-violet-500/30 flex items-center justify-center text-violet-400">
                            <i class="fas fa-store text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-base">Seleccionar Sucursal</h3>
                            <p class="text-gray-400 text-xs">Control de Bodegas</p>
                        </div>
                    </div>
                    <!-- Botón X para cerrar modal -->
                    <button
                        type="button"
                        @click="closeSelectBranchModal"
                        title="Cerrar modal (X)"
                        class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-all cursor-pointer"
                    >
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <form @submit.prevent="submitAccessRequest" class="p-6 space-y-4">
                    <p class="text-gray-300 text-xs leading-relaxed">
                        Como <strong>Superadmin</strong> con acceso global, debes seleccionar en qué sucursal deseas operar la bodega.
                        Al solicitarlo, se enviará una notificación en tiempo real al <strong>Administrador local</strong> para que autorice el ingreso.
                    </p>

                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Sucursal a operar *</label>
                        <select
                            v-model="requestBranchForm.branch"
                            required
                            class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors"
                        >
                            <option v-for="b in branches" :key="b" :value="b">{{ b }}</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-white/5">
                        <button
                            type="button"
                            @click="closeSelectBranchModal"
                            class="px-4 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm cursor-pointer transition-colors"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="requestBranchForm.processing"
                            class="flex-1 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white font-bold text-sm shadow-lg shadow-violet-900/30 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                        >
                            <i v-if="requestBranchForm.processing" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-paper-plane"></i>
                            <span>Solicitar Acceso a Bodega</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </Transition>

        <!-- ══ MODAL 2: ESPERANDO AUTORIZACIÓN DE ACCESO (SUPERADMIN) ══════════ -->
        <Transition name="fade">
        <div v-if="currentPendingRequest" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md">
            <div class="bg-gray-900 border border-amber-500/30 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden relative animate-scaleIn">
                <!-- Botón Cancelar X en la esquina superior derecha -->
                <button
                    type="button"
                    @click="cancelWaitingRequest"
                    title="Cancelar solicitud (X)"
                    class="absolute top-4 right-4 w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-all cursor-pointer z-10"
                >
                    <i class="fas fa-times text-sm"></i>
                </button>

                <div class="px-6 py-6 text-center space-y-4">
                    <div class="w-16 h-16 rounded-2xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center mx-auto animate-pulse">
                        <i class="fas fa-clock text-amber-400 text-2xl"></i>
                    </div>

                    <div>
                        <h3 class="text-white font-bold text-lg">Esperando Acceso a Bodega</h3>
                        <p class="text-amber-400 text-xs font-semibold mt-0.5">Solicitud enviada al Administrador</p>
                    </div>

                    <p class="text-gray-300 text-xs leading-relaxed">
                        Se ha enviado una alerta al terminal del Administrador de <strong>{{ currentPendingRequest.branch }}</strong> para autorizar tu ingreso.
                    </p>

                    <div class="bg-gray-800/80 border border-white/5 rounded-xl p-3.5 text-xs text-left space-y-1.5">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Sucursal Solicitada:</span>
                            <span class="text-emerald-400 font-semibold">{{ currentPendingRequest.branch }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Solicitante:</span>
                            <span class="text-white font-medium">{{ page.props.auth.user.name }} (@{{ page.props.auth.user.username }})</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Cargo:</span>
                            <span class="text-violet-400 font-bold uppercase">Superadmin</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Hora de Envío:</span>
                            <span class="text-gray-300 font-mono">{{ currentPendingRequest.created_at ? new Date(currentPendingRequest.created_at).toLocaleTimeString('es-NI') : 'Reciente' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-center gap-2 text-xs text-gray-400 pt-2">
                        <i class="fas fa-spinner fa-spin text-amber-400"></i>
                        <span>Esperando confirmación del Administrador local...</span>
                    </div>

                    <div class="pt-2">
                        <button
                            type="button"
                            @click="cancelWaitingRequest"
                            class="w-full py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white text-xs font-semibold border border-white/10 transition-colors flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i class="fas fa-times"></i> Cancelar Operación
                        </button>
                    </div>
                </div>
            </div>
        </div>
        </Transition>

        <!-- ══ MODAL 3: HISTORIAL DE SOLICITUDES (SÓLO ADMIN DE SUCURSAL) ══════ -->
        <Transition name="fade">
        <div v-if="showLogModal && accessRequests" @click.self="showLogModal = false" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden my-6">
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/40">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-clipboard-list text-violet-400 text-lg"></i>
                        <h3 class="text-white font-bold text-base">
                            Historial de Solicitudes de Acceso — {{ page.props.auth.user.branch }}
                        </h3>
                    </div>
                    <button
                        @click="showLogModal = false"
                        title="Cerrar modal (X)"
                        class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-all cursor-pointer"
                    >
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <div class="p-6 overflow-x-auto max-h-[70vh]">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-gray-500 font-semibold uppercase tracking-wider border-b border-white/5 bg-gray-950/30">
                                <th class="px-4 py-3">Solicitante</th>
                                <th class="px-4 py-3">Cargo</th>
                                <th class="px-4 py-3">Fecha y Hora Solicitud</th>
                                <th class="px-4 py-3 text-center">Estado</th>
                                <th class="px-4 py-3">Respondido Por</th>
                                <th class="px-4 py-3">Fecha Respuesta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-gray-300">
                            <tr v-for="req in accessRequests.data" :key="req.id" class="hover:bg-white/2 transition-colors">
                                <td class="px-4 py-3 font-semibold text-white">
                                    {{ req.user?.name || req.user?.username }}
                                    <span class="block text-[10px] text-gray-500 font-normal">@{{ req.user?.username }}</span>
                                </td>
                                <td class="px-4 py-3 text-violet-400 font-bold uppercase">
                                    {{ req.user?.role || 'Superadmin' }}
                                </td>
                                <td class="px-4 py-3 text-gray-300 font-mono text-[11px]">
                                    {{ req.created_at ? new Date(req.created_at).toLocaleString('es-NI') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        v-if="req.status === 'approved'"
                                        class="px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-400 font-bold text-[11px] border border-emerald-500/20"
                                    >
                                        Aprobado
                                    </span>
                                    <span
                                        v-else-if="req.status === 'rejected'"
                                        class="px-2.5 py-1 rounded-full bg-red-500/15 text-red-400 font-bold text-[11px] border border-red-500/20"
                                    >
                                        Rechazado
                                    </span>
                                    <span
                                        v-else-if="req.status === 'cancelled'"
                                        class="px-2.5 py-1 rounded-full bg-gray-800 text-gray-400 font-medium text-[11px] border border-white/10"
                                    >
                                        Cancelado
                                    </span>
                                    <span
                                        v-else
                                        class="px-2.5 py-1 rounded-full bg-amber-500/15 text-amber-400 font-bold text-[11px] border border-amber-500/20"
                                    >
                                        Pendiente
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-300">
                                    {{ req.responded_by?.name || req.responded_by?.username || '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-400 font-mono text-[11px]">
                                    {{ req.responded_at ? new Date(req.responded_at).toLocaleString('es-NI') : '—' }}
                                </td>
                            </tr>

                            <tr v-if="!accessRequests.data.length">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    No hay solicitudes de acceso registradas para esta sucursal.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-white/5 bg-gray-950/40 flex justify-end">
                    <button
                        type="button"
                        @click="showLogModal = false"
                        class="px-5 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-white text-xs font-semibold transition-colors"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
        </Transition>

        <!-- ══ MODAL: TRASLADO ENTRE BODEGAS ══════════════════════════════════ -->
        <Transition name="fade">
        <div v-if="showModal" @click.self="closeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                <!-- Header del Modal -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/40">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <i class="fas fa-exchange-alt text-violet-400"></i>
                        Traslado Interno de Bodega
                    </h3>

                    <!-- Botón X para cerrar modal -->
                    <button
                        @click="closeModal"
                        title="Cerrar modal (X)"
                        class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-all cursor-pointer"
                    >
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <form @submit.prevent="submitTransfer" class="p-6 space-y-4">
                    <div class="bg-gray-800/60 rounded-xl p-3.5 border border-white/5">
                        <p class="text-white text-sm font-semibold">{{ selectedProduct?.name }}</p>
                        <p class="text-gray-400 text-xs mt-0.5">
                            Stock Sucursal: <strong class="text-emerald-400">{{ selectedProduct?.sucursal_stock }}</strong> |
                            Débito: <strong class="text-amber-400">{{ selectedProduct?.debito_stock }}</strong> |
                            Merma: <strong class="text-red-400">{{ selectedProduct?.merma_stock }}</strong>
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Origen *</label>
                            <select
                                v-model="form.from_bodega"
                                required
                                class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-3 py-2 text-white text-xs outline-none"
                            >
                                <option value="sucursal">Sucursal (Venta)</option>
                                <option value="debito">Bodega Débito</option>
                                <option value="merma">Merma</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Destino *</label>
                            <select
                                v-model="form.to_bodega"
                                required
                                class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-3 py-2 text-white text-xs outline-none"
                            >
                                <option value="sucursal">Sucursal (Venta)</option>
                                <option value="debito">Bodega Débito</option>
                                <option value="merma">Merma</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Cantidad a trasladar *</label>
                        <input
                            v-model.number="form.quantity"
                            type="number" min="1" required placeholder="1"
                            class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors"
                        />
                        <p v-if="form.errors.quantity" class="text-red-400 text-xs mt-1">{{ form.errors.quantity }}</p>
                    </div>

                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Motivo del traslado (opcional)</label>
                        <textarea
                            v-model="form.reason"
                            rows="2" placeholder="Observaciones..."
                            class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-white/5">
                        <button type="button" @click="closeModal" class="px-4 py-2.5 rounded-xl bg-gray-800 text-gray-300 text-sm cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white font-bold text-sm shadow-lg shadow-violet-900/30 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50">
                            <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-exchange-alt"></i>
                            {{ form.processing ? 'Trasladando...' : 'Confirmar Traslado' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </Transition>
    </AdminLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
