<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AuthModal from '@/Components/AuthModal.vue';

const props = defineProps({
    discards: Object,
    products: Array,
    branches: Array,
    isSuperAdmin: Boolean,
    userBranch: String,
    filters: Object,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

// ── Modales ──────────────────────────────────────────────────────────────────
const showCreateModal   = ref(false);
const showDecisionModal = ref(false);
const showAuthModal     = ref(false);
const selectedDiscard   = ref(null);
const authError         = ref('');

// ── Filtros ──────────────────────────────────────────────────────────────────
const filterStatus   = ref(props.filters?.status    || 'all');
const filterSearch   = ref(props.filters?.q         || '');
const filterDateFrom = ref(props.filters?.date_from || '');
const filterDateTo   = ref(props.filters?.date_to   || '');
const defaultBranch  = props.isSuperAdmin ? (props.userBranch || 'Sucursal Managua') : (props.userBranch || 'Sucursal Managua');
const filterBranch   = ref(props.filters?.branch || defaultBranch);

function applyFilters() {
    router.get(route('discards.index'), {
        status:    filterStatus.value,
        q:         filterSearch.value,
        date_from: filterDateFrom.value,
        date_to:   filterDateTo.value,
        branch:    filterBranch.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    filterStatus.value   = 'all';
    filterSearch.value   = '';
    filterDateFrom.value = '';
    filterDateTo.value   = '';
    filterBranch.value   = defaultBranch;
    applyFilters();
}

// ── Formulario: Nueva Solicitud ──────────────────────────────────────────────
const form = useForm({
    product_id: '',
    quantity: 1,
    reason: '',
});

const selectedProduct = computed(() =>
    props.products.find(p => p.id === form.product_id) || null
);

function openCreateModal() {
    form.reset();
    form.clearErrors();
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
    form.reset();
}

function submitCreate() {
    form.post(route('discards.store'), {
        onSuccess: () => closeCreateModal(),
    });
}

// ── Formulario: Decisión (Aprobar / Rechazar) ─────────────────────────────────
const decisionForm = useForm({
    status: '',
    decision_reason: '',
    admin_username: '',
    admin_password: '',
});

function openDecisionModal(discard, decisionStatus) {
    selectedDiscard.value = discard;
    decisionForm.reset();
    decisionForm.clearErrors();
    decisionForm.status = decisionStatus;
    decisionForm.decision_reason = '';
    showDecisionModal.value = true;
}

function closeDecisionModal() {
    showDecisionModal.value = false;
    selectedDiscard.value = null;
    decisionForm.reset();
}

function proceedToAuth() {
    authError.value = '';
    showDecisionModal.value = false;
    showAuthModal.value = true;
}

function handleAuthConfirm({ username, password }) {
    decisionForm.admin_username = username;
    decisionForm.admin_password = password;

    decisionForm.patch(route('discards.decide', selectedDiscard.value.id), {
        preserveScroll: true,
        onSuccess: (pageResp) => {
            showAuthModal.value = false;
            decisionForm.reset();
            const approvedId = pageResp?.props?.flash?.approved_discard_id;
            if (approvedId) {
                window.open(route('discards.pdf', approvedId), '_blank');
            }
        },
        onError: (errs) => {
            if (errs.auth) {
                authError.value = errs.auth;
            } else if (errs.error) {
                authError.value = errs.error;
            } else {
                authError.value = 'Error al procesar la autorización.';
            }
        },
    });
}

// ── Helper de Costo con IVA ──────────────────────────────────────────────────
function getProductCostWithTax(product) {
    if (!product) return 0;
    const baseCost = Number(product.cost || 0);
    const taxPercent = Number(product.purchase_tax_percent || product.tax_percent || 0);
    return baseCost * (1 + (taxPercent / 100));
}

function formatCurrency(val) {
    return Number(val || 0).toLocaleString('es-NI', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}
</script>

<template>
    <Head title="Bajas de Productos — Farmacia Solei" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-black text-white tracking-tight flex items-center gap-2">
                        <i class="fas fa-trash-alt text-violet-400"></i> Bajas y Salidas de Inventario
                    </h1>
                    <p class="text-xs text-gray-400 mt-0.5">Gestión de productos dados de baja, descartes y emisión de reportes oficiales</p>
                </div>

                <button
                    @click="openCreateModal"
                    class="px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white font-bold text-xs shadow-lg shadow-violet-600/20 transition-all flex items-center gap-2 cursor-pointer"
                >
                    <i class="fas fa-plus"></i>
                    <span>Nueva Solicitud de Baja</span>
                </button>
            </div>
        </template>

        <div class="space-y-5">
            <!-- ══ PANEL DE FILTROS AVANZADOS ═══════════════════════════════════ -->
            <div class="bg-gray-900/80 border border-white/5 rounded-2xl p-5 shadow-xl space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
                    <!-- Búsqueda -->
                    <div class="lg:col-span-1">
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Buscar</label>
                        <div class="relative">
                            <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
                            <input
                                v-model="filterSearch"
                                @keyup.enter="applyFilters"
                                type="text"
                                placeholder="Producto, usuario, motivo..."
                                class="w-full bg-gray-800/90 border border-white/10 focus:border-violet-500 rounded-xl pl-9 pr-3 py-2 text-white text-xs outline-none transition-colors placeholder:text-gray-600"
                            />
                        </div>
                    </div>

                    <!-- Estado -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Estado</label>
                        <select
                            v-model="filterStatus"
                            @change="applyFilters"
                            class="w-full bg-gray-800/90 border border-white/10 focus:border-violet-500 rounded-xl px-3 py-2 text-white text-xs outline-none transition-colors"
                        >
                            <option value="all">Todos los estados</option>
                            <option value="pending">Pendientes</option>
                            <option value="approved">Aprobadas (Salidas)</option>
                            <option value="rejected">Rechazadas</option>
                        </select>
                    </div>

                    <!-- Sucursal -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1 flex items-center justify-between">
                            <span>Sucursal</span>
                            <span v-if="!isSuperAdmin" class="text-violet-400 font-normal lowercase text-[10px] bg-violet-500/10 px-1.5 py-0.5 rounded border border-violet-500/20">Fija</span>
                        </label>
                        <select
                            v-model="filterBranch"
                            :disabled="!isSuperAdmin"
                            @change="applyFilters"
                            class="w-full bg-gray-800/90 border border-white/10 focus:border-violet-500 rounded-xl px-3 py-2 text-white text-xs outline-none transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            <option v-if="isSuperAdmin" value="all">Todas las sucursales</option>
                            <option v-for="b in branches" :key="b" :value="b">{{ b }}</option>
                        </select>
                    </div>

                    <!-- Fecha Desde -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Desde</label>
                        <input
                            v-model="filterDateFrom"
                            @change="applyFilters"
                            type="date"
                            class="w-full bg-gray-800/90 border border-white/10 focus:border-violet-500 rounded-xl px-3 py-2 text-white text-xs outline-none transition-colors"
                        />
                    </div>

                    <!-- Fecha Hasta -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Hasta</label>
                        <input
                            v-model="filterDateTo"
                            @change="applyFilters"
                            type="date"
                            class="w-full bg-gray-800/90 border border-white/10 focus:border-violet-500 rounded-xl px-3 py-2 text-white text-xs outline-none transition-colors"
                        />
                    </div>
                </div>

                <!-- Botones de Acción de Filtros -->
                <div class="flex items-center justify-between pt-2 border-t border-white/5 text-xs text-gray-400">
                    <div>
                        Mostrando <strong class="text-white">{{ discards.total }}</strong> solicitudes registradas
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="resetFilters"
                            class="px-3 py-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-300 transition-colors flex items-center gap-1.5 cursor-pointer"
                        >
                            <i class="fas fa-undo text-[10px]"></i> Limpiar Filtros
                        </button>
                        <button
                            @click="applyFilters"
                            class="px-4 py-1.5 rounded-lg bg-violet-600 hover:bg-violet-500 text-white font-semibold transition-colors flex items-center gap-1.5 cursor-pointer"
                        >
                            <i class="fas fa-filter text-[10px]"></i> Filtrar
                        </button>
                    </div>
                </div>
            </div>

            <!-- ══ TABLA DE SOLICITUDES Y BAJAS ═════════════════════════════════ -->
            <div class="bg-gray-900 border border-white/5 rounded-2xl overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-300">
                        <thead class="bg-gray-950/60 text-gray-400 uppercase tracking-wider text-[10px] font-bold border-b border-white/5">
                            <tr>
                                <th class="px-5 py-4">N° Boleta</th>
                                <th class="px-4 py-4">Fecha</th>
                                <th class="px-4 py-4">Sucursal</th>
                                <th class="px-5 py-4">Producto</th>
                                <th class="px-4 py-4 text-right">Cant. Salida</th>
                                <th class="px-4 py-4 text-right">Costo Unit (+IVA)</th>
                                <th class="px-4 py-4 text-right">Total Pérdida</th>
                                <th class="px-4 py-4">Solicitado Por</th>
                                <th class="px-4 py-4 text-center">Estado</th>
                                <th class="px-5 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr
                                v-for="d in discards.data"
                                :key="d.id"
                                class="hover:bg-white/[0.02] transition-colors"
                            >
                                <!-- N° Boleta -->
                                <td class="px-5 py-3.5 font-mono font-bold text-violet-400 whitespace-nowrap">
                                    #BAJA-{{ String(d.id).padStart(4, '0') }}
                                </td>

                                <!-- Fecha -->
                                <td class="px-4 py-3.5 whitespace-nowrap text-gray-400">
                                    {{ new Date(d.created_at).toLocaleDateString('es-NI', { day: '2-digit', month: '2-digit', year: 'numeric' }) }}
                                </td>

                                <!-- Sucursal -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-white/5 text-gray-300 text-[11px] font-medium border border-white/5">
                                        <i class="fas fa-building text-[10px] text-violet-400"></i>
                                        {{ d.branch || d.requester?.branch || 'Sucursal General' }}
                                    </span>
                                </td>

                                <!-- Producto -->
                                <td class="px-5 py-3.5 max-w-xs">
                                    <p class="font-bold text-white truncate" :title="d.product?.name">
                                        {{ d.product?.name ?? 'Producto #' + d.product_id }}
                                    </p>
                                    <p class="text-[10px] text-gray-500 font-mono">
                                        {{ d.product?.barcode ?? (d.product?.sku ?? 'Sin código') }}
                                    </p>
                                </td>

                                <!-- Cantidad Salida en negativo -->
                                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                    <span class="inline-block px-2.5 py-1 rounded-lg bg-red-500/10 text-red-400 font-black text-xs border border-red-500/20 font-mono">
                                        -{{ Number(d.quantity).toFixed(0) }} unids
                                    </span>
                                </td>

                                <!-- Costo Unit (+IVA) -->
                                <td class="px-4 py-3.5 text-right whitespace-nowrap font-mono text-gray-400">
                                    C$ {{ formatCurrency(getProductCostWithTax(d.product)) }}
                                </td>

                                <!-- Total Pérdida en negativo -->
                                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                    <span class="font-black text-red-400 font-mono">
                                        -C$ {{ formatCurrency(Number(d.quantity) * getProductCostWithTax(d.product)) }}
                                    </span>
                                </td>

                                <!-- Solicitado Por -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <p class="text-white font-medium">{{ d.requester?.username ?? '—' }}</p>
                                    <p v-if="d.reason" class="text-[10px] text-gray-500 truncate max-w-[150px]" :title="d.reason">
                                        {{ d.reason }}
                                    </p>
                                </td>

                                <!-- Estado -->
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider inline-flex items-center gap-1.5',
                                            d.status === 'approved' ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' :
                                            d.status === 'rejected' ? 'bg-red-500/15 text-red-400 border border-red-500/30' :
                                            'bg-amber-500/15 text-amber-400 border border-amber-500/30'
                                        ]"
                                    >
                                        <i :class="[
                                            'fas text-[8px]',
                                            d.status === 'approved' ? 'fa-check-circle' :
                                            d.status === 'rejected' ? 'fa-times-circle' :
                                            'fa-hourglass-half'
                                        ]"></i>
                                        {{ d.status === 'approved' ? 'Aprobada' : (d.status === 'rejected' ? 'Rechazada' : 'Pendiente') }}
                                    </span>
                                </td>

                                <!-- Acciones -->
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Botones para Pendientes: Aprobar / Rechazar -->
                                        <template v-if="d.status === 'pending'">
                                            <button
                                                @click="openDecisionModal(d, 'approved')"
                                                class="px-2.5 py-1.5 rounded-lg bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-400 text-xs font-bold border border-emerald-500/25 transition-all flex items-center gap-1 cursor-pointer"
                                                title="Aprobar baja"
                                            >
                                                <i class="fas fa-check"></i>
                                                <span>Aprobar</span>
                                            </button>
                                            <button
                                                @click="openDecisionModal(d, 'rejected')"
                                                class="px-2.5 py-1.5 rounded-lg bg-red-500/15 hover:bg-red-500/25 text-red-400 text-xs font-bold border border-red-500/25 transition-all flex items-center gap-1 cursor-pointer"
                                                title="Rechazar baja"
                                            >
                                                <i class="fas fa-times"></i>
                                                <span>Rechazar</span>
                                            </button>
                                        </template>

                                        <!-- Botón Ver Reporte PDF para Aprobadas -->
                                        <a
                                            v-if="d.status === 'approved'"
                                            :href="route('discards.pdf', d.id)"
                                            target="_blank"
                                            class="px-3 py-1.5 rounded-lg bg-violet-600/20 hover:bg-violet-600/35 text-violet-300 text-xs font-bold border border-violet-500/30 transition-all flex items-center gap-1.5 cursor-pointer shadow-sm"
                                            title="Ver Reporte de Salida de Inventario (PDF)"
                                        >
                                            <i class="fas fa-file-pdf text-red-400"></i>
                                            <span>Reporte Salida</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="discards.data.length === 0">
                                <td colspan="10" class="py-12 text-center text-gray-500 text-xs">
                                    <i class="fas fa-inbox text-3xl mb-2 text-gray-700 block"></i>
                                    No se encontraron solicitudes de baja con los filtros seleccionados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="discards.links && discards.links.length > 3" class="px-6 py-4 border-t border-white/5 flex items-center justify-between text-xs text-gray-400">
                    <div>
                        Página {{ discards.current_page }} de {{ discards.last_page }}
                    </div>
                    <div class="flex gap-1">
                        <template v-for="(link, i) in discards.links" :key="i">
                            <a
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                :class="[
                                    'px-3 py-1.5 rounded-lg transition-colors',
                                    link.active ? 'bg-violet-600 text-white font-bold' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'
                                ]"
                            ></a>
                            <span v-else v-html="link.label" class="px-3 py-1.5 text-gray-600"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ MODAL: NUEVA SOLICITUD DE BAJA ═══════════════════════════════════ -->
        <Transition name="fade">
            <div v-if="showCreateModal" @click.self="closeCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
                <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/50">
                        <h3 class="text-white font-bold text-sm flex items-center gap-2">
                            <i class="fas fa-trash-alt text-violet-400"></i> Nueva Solicitud de Baja
                        </h3>
                        <button :disabled="form.processing" @click="closeCreateModal" class="text-gray-500 hover:text-white transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Seleccionar Producto *</label>
                            <select
                                v-model="form.product_id"
                                required
                                class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-xs outline-none transition-colors"
                            >
                                <option value="" disabled>Selecciona un producto...</option>
                                <option v-for="p in products" :key="p.id" :value="p.id">
                                    {{ p.name }} (Stock: {{ p.stock }})
                                </option>
                            </select>
                            <p v-if="form.errors.product_id" class="text-red-400 text-xs mt-1">{{ form.errors.product_id }}</p>
                        </div>

                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Cantidad a dar de baja *</label>
                            <input
                                v-model.number="form.quantity"
                                type="number"
                                min="1"
                                :max="selectedProduct?.stock || 9999"
                                required
                                class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-xs outline-none transition-colors"
                            />
                            <p v-if="selectedProduct" class="text-gray-500 text-[11px] mt-1">Stock disponible en sistema: {{ selectedProduct.stock }}</p>
                            <p v-if="form.errors.quantity" class="text-red-400 text-xs mt-1">{{ form.errors.quantity }}</p>
                        </div>

                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Motivo / Causa de la Baja *</label>
                            <textarea
                                v-model="form.reason"
                                rows="3"
                                required
                                placeholder="Describe el daño, vencimiento o causa de la baja..."
                                class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-xs outline-none transition-colors"
                            ></textarea>
                            <p v-if="form.errors.reason" class="text-red-400 text-xs mt-1">{{ form.errors.reason }}</p>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-white/5">
                            <button type="button" :disabled="form.processing" @click="closeCreateModal" class="px-4 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 font-semibold text-xs cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2 rounded-xl bg-violet-600 hover:bg-violet-500 text-white font-bold text-xs shadow-lg shadow-violet-600/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
                            >
                                <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                                <i v-else class="fas fa-paper-plane"></i>
                                <span>{{ form.processing ? 'Registrando...' : 'Registrar Solicitud' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- ══ MODAL: OBSERVACIONES DE DECISIÓN ═════════════════════════════════ -->
        <Transition name="fade">
            <div v-if="showDecisionModal" @click.self="closeDecisionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
                <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/50">
                        <h3 class="text-white font-bold text-sm flex items-center gap-2">
                            <i :class="['fas', decisionForm.status === 'approved' ? 'fa-check-circle text-emerald-400' : 'fa-times-circle text-red-400']"></i>
                            {{ decisionForm.status === 'approved' ? 'Aprobar Salida de Inventario' : 'Rechazar Solicitud de Baja' }}
                        </h3>
                        <button @click="closeDecisionModal" class="text-gray-500 hover:text-white transition-colors cursor-pointer">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="bg-gray-800/70 rounded-xl p-4 border border-white/5 space-y-1.5 text-xs">
                            <p class="text-white font-bold text-sm">{{ selectedDiscard?.product?.name }}</p>
                            <p class="text-gray-400">
                                Cantidad a dar de baja: <strong class="text-red-400 font-mono">-{{ selectedDiscard?.quantity }} unids</strong>
                            </p>
                            <p class="text-gray-400">
                                Sucursal: <strong class="text-violet-300">{{ selectedDiscard?.branch || selectedDiscard?.requester?.branch || 'Sucursal General' }}</strong>
                            </p>
                            <p class="text-gray-500 italic mt-1">
                                Motivo: "{{ selectedDiscard?.reason }}"
                            </p>
                        </div>

                        <div v-if="decisionForm.status === 'approved'" class="text-xs text-emerald-300 bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-3.5 leading-relaxed">
                            <i class="fas fa-info-circle text-emerald-400 mr-1.5"></i>
                            Al autorizar, se descontarán <strong>{{ selectedDiscard?.quantity }} unidades</strong> del inventario y se generará el <strong>Reporte de Salida de Inventario</strong> oficial.
                        </div>

                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Observaciones / Nota de Decisión (Opcional)</label>
                            <textarea
                                v-model="decisionForm.decision_reason"
                                rows="2"
                                placeholder="Observaciones de la autorización..."
                                class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-xs outline-none transition-colors"
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-3 border-t border-white/5">
                            <button type="button" @click="closeDecisionModal" class="px-4 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 font-semibold text-xs cursor-pointer">
                                Cancelar
                            </button>
                            <button
                                type="button"
                                @click="proceedToAuth"
                                class="px-5 py-2 rounded-xl font-bold text-xs text-white shadow-lg transition-all flex items-center gap-2 cursor-pointer"
                                :class="decisionForm.status === 'approved' ? 'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-900/30' : 'bg-red-600 hover:bg-red-500 shadow-red-900/30'"
                            >
                                <i class="fas fa-shield-alt"></i>
                                <span>Continuar a Autorización</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ══ MODAL DE CREDENCIALES ADMINISTRATIVAS (REUTILIZABLE) ═════════════ -->
        <AuthModal
            :show="showAuthModal"
            title="Autorización Requerida"
            subtitle="Ingrese credenciales de Administrador o Superadmin para autorizar la baja de inventario."
            actionLabel="Autorizar y Procesar Salida"
            :processing="decisionForm.processing"
            :errorMessage="authError"
            :initialUsername="currentUser?.username || ''"
            @close="showAuthModal = false"
            @confirm="handleAuthConfirm"
        />
    </AdminLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
