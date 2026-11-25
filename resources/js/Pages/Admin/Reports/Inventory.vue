<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AuthModal from '@/Components/AuthModal.vue';
import axios from 'axios';

const props = defineProps({
    products:     Object,
    suppliers:    Array,
    categories:   Array,
    branches:     Array,
    activeCount:  Object,
    recentCounts: Array,
    summary:      Object,
    filters:      Object,
});

const q           = ref(props.filters?.q || '');
const supplierId  = ref(props.filters?.supplier_id || 'all');
const categoryId  = ref(props.filters?.category_id || 'all');
const branch      = ref(props.filters?.branch || 'all');
const eligibility = ref(props.filters?.eligibility || 'all');
const showCost    = ref(props.filters?.show_cost ?? true);

// Mapa reactivo para conteo físico: { [productId]: countedQty }
const countedMap  = ref({});
const notesMap    = ref({});

// Conteo Preliminar Activo
const currentCount = ref(props.activeCount || null);
const countNotes   = ref(props.activeCount?.notes || '');
const isSavingDraft = ref(false);
const isApplying    = ref(false);
const toastMessage  = ref(null);
const toastType     = ref('success');
const isCountFinalized = ref(props.activeCount?.status === 'aplicado');
const isFinalized   = computed(() => isCountFinalized.value || currentCount.value?.status === 'aplicado');

// Modal de confirmación y autorización para aplicar conteo
const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const showApplyModal   = ref(false);
const showAuthModal    = ref(false);
const authErrorMessage = ref('');
const initialAuthUsername = computed(() => {
    if (currentUser.value && ['admin', 'superadmin'].includes(currentUser.value.role)) {
        return currentUser.value.username;
    }
    return '';
});
const showSuccessModal = ref(false);
const appliedResult    = ref(null);
const showHistoryModal = ref(false);

// Filtros intuitivos del Historial de Conteos
const historyStatusFilter = ref('all'); // 'all' | 'aplicado' | 'preliminar' | 'discrepancies'
const historySearchQuery  = ref('');
const historyBranchFilter = ref('all');

const countsStats = computed(() => {
    const list = props.recentCounts || [];
    return {
        total: list.length,
        aplicados: list.filter(c => c.status === 'aplicado').length,
        preliminares: list.filter(c => c.status === 'preliminar').length,
        discrepancies: list.filter(c => Number(c.total_discrepancies) > 0).length,
    };
});

const filteredCounts = computed(() => {
    if (!props.recentCounts) return [];
    return props.recentCounts.filter(cnt => {
        if (historyStatusFilter.value === 'aplicado' && cnt.status !== 'aplicado') return false;
        if (historyStatusFilter.value === 'preliminar' && cnt.status !== 'preliminar') return false;
        if (historyStatusFilter.value === 'discrepancies' && (!cnt.total_discrepancies || Number(cnt.total_discrepancies) <= 0)) return false;

        if (historyBranchFilter.value !== 'all' && cnt.branch !== historyBranchFilter.value) return false;

        if (historySearchQuery.value.trim()) {
            const q = historySearchQuery.value.trim().toLowerCase();
            const matchNumber = cnt.count_number?.toLowerCase().includes(q);
            const matchNotes  = cnt.notes?.toLowerCase().includes(q);
            const matchBranch = cnt.branch?.toLowerCase().includes(q);
            const matchDate   = cnt.applied_at?.toLowerCase().includes(q) || cnt.created_at?.toLowerCase().includes(q);
            if (!matchNumber && !matchNotes && !matchBranch && !matchDate) return false;
        }

        return true;
    });
});

// Inicializar countedMap si hay un preliminar activo cargado
function initCountFromActive() {
    if (props.activeCount?.items) {
        props.activeCount.items.forEach(it => {
            countedMap.value[it.product_id] = it.counted_stock;
            if (it.notes) notesMap.value[it.product_id] = it.notes;
        });
    }
}
onMounted(() => {
    initCountFromActive();
});

let searchTimer = null;
function triggerFilter() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route('reports.inventory'), {
            q:           q.value || undefined,
            supplier_id: supplierId.value !== 'all' ? supplierId.value : undefined,
            category_id: categoryId.value !== 'all' ? categoryId.value : undefined,
            branch:      branch.value !== 'all' ? branch.value : undefined,
            show_cost:   showCost.value ? 1 : 0,
            count_id:    currentCount.value?.id || undefined,
        }, { preserveState: true, replace: true });
    }, 250);
}

watch(q, triggerFilter);
watch(supplierId, triggerFilter);
watch(categoryId, triggerFilter);
watch(branch, (newBranch, oldBranch) => {
    if (newBranch !== oldBranch) {
        // Al cambiar de sucursal, reiniciamos el borrador local si era de otra sucursal
        if (currentCount.value && currentCount.value.branch !== newBranch) {
            currentCount.value = null;
            countedMap.value = {};
            notesMap.value = {};
        }
        triggerFilter();
    }
});
watch(showCost, triggerFilter);

function formatMoney(val) {
    const n = Number(val || 0);
    return 'C$ ' + n.toLocaleString('es-NI', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('es-NI', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }) + ' • ' + d.toLocaleTimeString('es-NI', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
}

function showToast(msg, type = 'success') {
    toastMessage.value = msg;
    toastType.value = type;
    setTimeout(() => {
        toastMessage.value = null;
    }, 4500);
}

// Cálculo de diferencias en el conteo interactivo
function getDiff(p) {
    const counted = countedMap.value[p.id];
    if (counted === undefined || counted === null || counted === '') return null;
    return Number(counted) - Number(p.stock || 0);
}

function getDiffCost(p) {
    const diff = getDiff(p);
    if (diff === null) return null;
    return diff * Number(p.cost || 0);
}

const itemsCountedTotal = computed(() => {
    return Object.keys(countedMap.value).filter(k => countedMap.value[k] !== '' && countedMap.value[k] !== undefined).length;
});

const totalCountedUnits = computed(() => {
    let sum = 0;
    for (const key in countedMap.value) {
        const val = countedMap.value[key];
        if (val !== undefined && val !== null && val !== '') {
            sum += Number(val);
        }
    }
    return sum;
});

// Resumen de discrepancias calculadas en vivo
const liveDiscrepancies = computed(() => {
    let faltantesUnits = 0;
    let faltantesCost  = 0;
    let sobrantesUnits = 0;
    let sobrantesCost  = 0;
    let itemsWithDiff  = 0;
    let netCost        = 0;

    const list = props.products?.data || [];
    list.forEach(p => {
        const diff = getDiff(p);
        if (diff !== null && diff !== 0) {
            itemsWithDiff++;
            const cost = Number(p.cost || 0);
            const subCost = diff * cost;
            netCost += subCost;

            if (diff < 0) {
                faltantesUnits += Math.abs(diff);
                faltantesCost  += Math.abs(subCost);
            } else {
                sobrantesUnits += diff;
                sobrantesCost  += subCost;
            }
        }
    });

    return {
        itemsWithDiff,
        faltantesUnits,
        faltantesCost,
        sobrantesUnits,
        sobrantesCost,
        netCost,
    };
});

// ── ACCIÓN: Guardar Preliminar ────────────────────────────────────────────────
async function handleSaveDraft() {
    if (branch.value === 'all') {
        alert('Por favor selecciona una sucursal específica para guardar un preliminar de conteo.');
        return;
    }

    const items = [];
    const list = props.products?.data || [];
    list.forEach(p => {
        const val = countedMap.value[p.id];
        if (val !== undefined && val !== null && val !== '') {
            items.push({
                product_id:    p.id,
                system_stock:  Number(p.stock || 0),
                counted_stock: Number(val),
                notes:         notesMap.value[p.id] || null,
            });
        }
    });

    if (items.length === 0) {
        alert('Ingresa al menos una cantidad contada para guardar el preliminar.');
        return;
    }

    isSavingDraft.value = true;
    try {
        const res = await axios.post(route('reports.inventory.count.saveDraft'), {
            branch:      branch.value,
            count_id:    currentCount.value?.id,
            category_id: categoryId.value,
            notes:       countNotes.value,
            items:       items,
        });

        if (res.data.success) {
            currentCount.value = res.data.count;
            showToast(res.data.message || 'Preliminar guardado exitosamente.');
        } else {
            alert(res.data.message || 'Error al guardar preliminar');
        }
    } catch (err) {
        console.error(err);
        alert(err.response?.data?.message || 'Error al guardar preliminar');
    } finally {
        isSavingDraft.value = false;
    }
}

// ── ACCIÓN: Abrir Modal para Aplicar Conteo ──────────────────────────────────
function openApplyModal() {
    if (branch.value === 'all') {
        alert('Debes seleccionar una sucursal específica para aplicar el conteo en bodega.');
        return;
    }
    if (itemsCountedTotal.value === 0) {
        alert('Debes ingresar las cantidades contadas antes de aplicar el conteo.');
        return;
    }
    showApplyModal.value = true;
}

function proceedToAuth() {
    showApplyModal.value = false;
    authErrorMessage.value = '';
    showAuthModal.value = true;
}

function handleCloseAuthModal() {
    showAuthModal.value = false;
    authErrorMessage.value = '';
}

// ── ACCIÓN: Autorizar con Credenciales y Aplicar Conteo en Bodega ────────────
async function handleAuthorizeAndApply({ username, password }) {
    isApplying.value = true;
    authErrorMessage.value = '';
    try {
        // 1. Primero aseguramos que el preliminar esté guardado
        const items = [];
        const list = props.products?.data || [];
        list.forEach(p => {
            const val = countedMap.value[p.id];
            if (val !== undefined && val !== null && val !== '') {
                items.push({
                    product_id:    p.id,
                    system_stock:  Number(p.stock || 0),
                    counted_stock: Number(val),
                    notes:         notesMap.value[p.id] || null,
                });
            }
        });

        const saveRes = await axios.post(route('reports.inventory.count.saveDraft'), {
            branch:      branch.value,
            count_id:    currentCount.value?.id,
            category_id: categoryId.value,
            notes:       countNotes.value,
            items:       items,
        });

        const countId = saveRes.data.count.id;

        // 2. Aplicar validando credenciales de admin o superadmin y permisos de sucursal
        const applyRes = await axios.post(route('reports.inventory.count.apply', countId), {
            username,
            password,
        });

        if (applyRes.data.success) {
            appliedResult.value = applyRes.data;
            isCountFinalized.value = true;
            showAuthModal.value = false;
            showSuccessModal.value = true;
            if (saveRes.data.count) {
                currentCount.value = { ...saveRes.data.count, status: 'aplicado' };
            }
        } else {
            authErrorMessage.value = applyRes.data.message || 'Error al aplicar conteo';
        }
    } catch (err) {
        console.error(err);
        authErrorMessage.value = err.response?.data?.message || 'Error al verificar credenciales o aplicar el conteo en bodega';
    } finally {
        isApplying.value = false;
    }
}

function handleCloseSuccessModal() {
    showSuccessModal.value = false;
    countedMap.value = {};
    notesMap.value = {};
    currentCount.value = null;
    isCountFinalized.value = false;
    router.reload();
}

// Cargar un preliminar anterior
function loadExistingCount(cnt) {
    if (cnt.branch && branch.value !== cnt.branch) {
        branch.value = cnt.branch;
    }
    currentCount.value = cnt;
    countNotes.value   = cnt.notes || '';
    countedMap.value   = {};
    notesMap.value     = {};

    if (cnt.items) {
        cnt.items.forEach(it => {
            countedMap.value[it.product_id] = it.counted_stock;
            if (it.notes) notesMap.value[it.product_id] = it.notes;
        });
    }
    showHistoryModal.value = false;
    showToast(`Conteo ${cnt.count_number} cargado en pantalla (${cnt.branch}).`);
}
</script>

<template>
    <Head title="Reporte de Bodega — Conteo Cíclico" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                        <i class="fas fa-clipboard-check text-violet-400"></i>
                        Reporte de Bodega / Conteo Cíclico
                    </h1>
                    <p class="text-xs text-gray-400 mt-1">
                        Toma física de inventario por sucursal, categoría y control valorizado de discrepancias
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Botón Historial de Conteos -->
                    <button
                        v-if="recentCounts?.length"
                        @click="showHistoryModal = true"
                        class="px-3.5 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white font-medium text-xs border border-white/10 flex items-center gap-2 transition-all cursor-pointer"
                        :title="branch !== 'all' ? `Ver historial de conteos de ${branch}` : 'Ver historial de conteos de todas las sucursales'"
                    >
                        <i class="fas fa-history text-gray-400"></i>
                        <span>Historial / Preliminares ({{ recentCounts.length }})</span>
                    </button>

                    <!-- Botón Imprimir Hoja de Conteo / PDF -->
                    <a
                        :href="route('reports.inventory.pdf', {
                            supplier_id: supplierId !== 'all' ? supplierId : undefined,
                            category_id: categoryId !== 'all' ? categoryId : undefined,
                            branch: branch !== 'all' ? branch : undefined,
                            eligibility: eligibility !== 'all' ? eligibility : undefined,
                            show_cost: showCost ? 1 : 0
                        })"
                        target="_blank"
                        class="px-3.5 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-200 hover:text-white font-semibold text-xs border border-white/10 flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap shadow-sm"
                        title="Imprimir Hoja Formal de Conteo Cíclico en PDF"
                    >
                        <i class="fas fa-print text-gray-400"></i>
                        <span>Hoja de Conteo PDF</span>
                    </a>

                    <!-- Botón Guardar Preliminar -->
                    <button
                        v-if="branch !== 'all'"
                        @click="handleSaveDraft"
                        :disabled="isSavingDraft || itemsCountedTotal === 0 || isFinalized"
                        class="px-4 py-2 rounded-xl bg-amber-600/90 hover:bg-amber-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold text-xs shadow-lg shadow-amber-600/20 flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap"
                        :title="isFinalized ? 'El conteo ya ha sido finalizado y aplicado' : 'Guardar borrador de conteo preliminar para editar más adelante'"
                    >
                        <i v-if="isSavingDraft" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-save"></i>
                        <span>{{ isFinalized ? 'Conteo Finalizado' : (currentCount ? 'Actualizar Preliminar' : 'Guardar Preliminar') }}</span>
                    </button>

                    <!-- Botón Aplicar Conteo en Bodega -->
                    <button
                        v-if="branch !== 'all'"
                        @click="openApplyModal"
                        :disabled="itemsCountedTotal === 0 || isFinalized"
                        class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-xs shadow-lg shadow-emerald-600/25 flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap"
                        :title="isFinalized ? 'El conteo ya ha sido finalizado y aplicado' : 'Finalizar y aplicar el conteo directamente en las existencias de la bodega'"
                    >
                        <i class="fas fa-check-double"></i>
                        <span>{{ isFinalized ? 'Conteo Aplicado' : 'Aplicar en Bodega' }}</span>
                    </button>
                </div>
            </div>
        </template>

        <!-- Toast Flotante -->
        <div
            v-if="toastMessage"
            class="fixed bottom-6 right-6 z-50 px-4 py-3 rounded-xl shadow-2xl flex items-center gap-3 border transition-all text-xs font-semibold backdrop-blur-md"
            :class="toastType === 'success' ? 'bg-emerald-950/90 border-emerald-500/30 text-emerald-300' : 'bg-red-950/90 border-red-500/30 text-red-300'"
        >
            <i class="fas" :class="toastType === 'success' ? 'fa-check-circle text-emerald-400' : 'fa-exclamation-triangle text-red-400'"></i>
            <span>{{ toastMessage }}</span>
        </div>

        <div class="space-y-6">

            <!-- ══ BANNER DE PRELIMINAR ACTIVO / FINALIZADO ═════════════════════════════════════ -->
            <div
                v-if="currentCount"
                class="p-4 rounded-2xl border flex flex-col md:flex-row items-start md:items-center justify-between gap-4"
                :class="isFinalized ? 'bg-emerald-500/10 border-emerald-500/30' : 'bg-amber-500/10 border-amber-500/30'"
            >
                <div class="flex items-center gap-3.5">
                    <div
                        class="w-10 h-10 rounded-xl flex items-center justify-center text-lg flex-shrink-0"
                        :class="isFinalized ? 'bg-emerald-500/20 border border-emerald-500/30 text-emerald-400' : 'bg-amber-500/20 border border-amber-500/30 text-amber-400'"
                    >
                        <i :class="isFinalized ? 'fas fa-check-circle' : 'fas fa-edit'"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="text-xs font-bold uppercase tracking-wide"
                                :class="isFinalized ? 'text-emerald-300' : 'text-amber-300'"
                            >
                                {{ isFinalized ? 'Conteo Finalizado y Aplicado' : 'Borrador Preliminar Activo' }}
                            </span>
                            <span
                                class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border"
                                :class="isFinalized ? 'bg-emerald-400/20 text-emerald-200 border-emerald-400/30' : 'bg-amber-400/20 text-amber-200 border-amber-400/30'"
                            >
                                {{ currentCount.count_number }}
                            </span>
                            <span class="text-[11px] text-gray-400">en {{ currentCount.branch }}</span>
                        </div>
                        <p class="text-[11px] text-gray-300 mt-0.5">
                            <template v-if="isFinalized">
                                Este conteo ya fue aplicado en las existencias de bodega. Las existencias se encuentran actualizadas.
                            </template>
                            <template v-else>
                                Puedes seguir modificando las cantidades contadas y presionar <strong>Actualizar Preliminar</strong> o finalizarlo con <strong>Aplicar en Bodega</strong>.
                            </template>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto justify-end">
                    <input
                        v-if="!isFinalized"
                        v-model="countNotes"
                        type="text"
                        placeholder="Observación o motivo del conteo..."
                        class="bg-gray-900 border border-amber-500/30 rounded-xl px-3 py-1.5 text-white text-xs outline-none focus:border-amber-400 w-64"
                    />
                    <button
                        @click="handleCloseSuccessModal"
                        class="px-2.5 py-1.5 text-xs text-gray-400 hover:text-white rounded-lg transition-colors cursor-pointer"
                        :title="isFinalized ? 'Limpiar pantalla e iniciar nuevo conteo' : 'Limpiar campos de conteo en pantalla'"
                    >
                        <i :class="isFinalized ? 'fas fa-redo mr-1' : 'fas fa-times mr-1'"></i>
                        {{ isFinalized ? 'Iniciar Nuevo Conteo' : 'Descartar' }}
                    </button>
                </div>
            </div>

            <!-- ══ TARJETAS RESUMEN / KPIS ═══════════════════════════════════════ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 rounded-2xl bg-gray-900 border border-white/5 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-violet-500/10 border border-violet-500/20 flex items-center justify-center text-violet-400 flex-shrink-0 text-lg">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 font-medium">Artículos en Consulta</span>
                        <h4 class="text-xl font-bold text-white mt-0.5">{{ summary?.totalProducts || 0 }}</h4>
                        <span class="text-[11px] text-gray-500">productos</span>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-gray-900 border border-white/5 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 flex-shrink-0 text-lg">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 font-medium">Existencia Sistema</span>
                        <h4 class="text-xl font-bold text-white mt-0.5">{{ Number(summary?.totalUnits || 0).toLocaleString() }}</h4>
                        <span class="text-[11px] text-emerald-400 font-semibold">unidades registradas</span>
                    </div>
                </div>

                <div v-if="showCost" class="p-5 rounded-2xl bg-gray-900 border border-white/5 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 flex-shrink-0 text-lg">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 font-medium">Valor Total en Costo</span>
                        <h4 class="text-xl font-bold text-white mt-0.5">{{ formatMoney(summary?.totalCostVal) }}</h4>
                        <span class="text-[11px] text-gray-500">capital en bodega</span>
                    </div>
                </div>

                <!-- Tarjeta de Progreso y Discrepancias del Conteo Físico -->
                <div class="p-5 rounded-2xl bg-gray-900 border border-white/5 flex items-center gap-4">
                    <div
                        class="w-12 h-12 rounded-xl flex items-center justify-center text-lg flex-shrink-0"
                        :class="liveDiscrepancies.itemsWithDiff > 0 ? 'bg-amber-500/10 border border-amber-500/20 text-amber-400' : 'bg-gray-800 text-gray-400'"
                    >
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 font-medium">Captura Física</span>
                        <h4 class="text-xl font-bold text-white mt-0.5">{{ itemsCountedTotal }} / {{ products?.total || products?.data?.length || 0 }}</h4>
                        <div class="flex items-center gap-1.5 text-[11px] mt-0.5">
                            <span v-if="liveDiscrepancies.itemsWithDiff > 0" class="text-amber-400 font-semibold">
                                {{ liveDiscrepancies.itemsWithDiff }} discrepancias
                            </span>
                            <span v-else class="text-gray-500">
                                {{ totalCountedUnits }} unidades contadas
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══ BARRA DE PARÁMETROS Y FILTROS ═════════════════════════════════ -->
            <div class="p-4 rounded-2xl bg-gray-900 border border-white/5 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 items-center">
                    <!-- Buscador -->
                    <div class="relative lg:col-span-1">
                        <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
                        <input
                            v-model="q"
                            type="text"
                            placeholder="Buscar producto, SKU..."
                            class="w-full bg-gray-800 border border-white/10 rounded-xl pl-9 pr-3 py-2 text-white text-xs outline-none focus:border-violet-500 transition-colors"
                        />
                    </div>

                    <!-- Filtro Proveedor -->
                    <div>
                        <select
                            v-model="supplierId"
                            class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2 text-white text-xs outline-none focus:border-violet-500 transition-colors cursor-pointer"
                        >
                            <option value="all">🏢 Todos los Proveedores</option>
                            <option v-for="s in suppliers" :key="s.id" :value="s.id">
                                {{ s.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Filtro Localización / Categoría -->
                    <div>
                        <select
                            v-model="categoryId"
                            class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2 text-white text-xs outline-none focus:border-violet-500 transition-colors cursor-pointer"
                        >
                            <option value="all">📁 Todas las Categorías</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">
                                {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Filtro Sucursal / Bodega -->
                    <div>
                        <select
                            v-model="branch"
                            class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2 text-white text-xs outline-none focus:border-violet-500 transition-colors cursor-pointer"
                        >
                            <option value="all">📍 Todas las Sucursales</option>
                            <option v-for="b in branches" :key="b" :value="b">
                                {{ b }}
                            </option>
                        </select>
                    </div>

                    <!-- Filtro Regla de 10 Ventas / Elegibilidad -->
                    <div>
                        <select
                            v-model="eligibility"
                            class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2 text-white text-xs outline-none focus:border-violet-500 transition-colors cursor-pointer"
                        >
                            <option value="all">🔄 Todos los Artículos</option>
                            <option value="eligible">✅ Habilitados para Conteo (≥ 10 ventas)</option>
                            <option value="locked">🔒 Bloqueados (&lt; 10 ventas tras conteo)</option>
                        </select>
                    </div>

                    <!-- Toggle Mostrar Costos -->
                    <div class="flex items-center justify-between sm:justify-end gap-2">
                        <label class="flex items-center gap-2 px-3 py-2 rounded-xl bg-gray-800/80 border border-white/10 cursor-pointer select-none text-xs text-gray-300 hover:text-white">
                            <input
                                type="checkbox"
                                v-model="showCost"
                                class="rounded border-gray-600 text-violet-600 focus:ring-violet-500 cursor-pointer"
                            />
                            <span class="font-medium whitespace-nowrap">Mostrar Costos</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- ══ TABLA DE CONTEO FÍSICO Y PRODUCTOS ════════════════════════════ -->
            <div class="bg-gray-900 border border-white/5 rounded-2xl overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/10 bg-gray-950/60 text-[11px] uppercase tracking-wider text-gray-400 font-semibold">
                                <th class="py-3 px-4 w-12 text-center">#</th>
                                <th class="py-3 px-4">Producto</th>
                                <th class="py-3 px-4">Categoría</th>
                                <th class="py-3 px-4 text-center">Exist. Sistema</th>
                                <th class="py-3 px-4 text-center w-36">
                                    <span class="text-amber-400 font-bold">Físico Contado</span>
                                </th>
                                <th class="py-3 px-4 text-center">Diferencia</th>
                                <th v-if="showCost" class="py-3 px-4 text-right">Costo Unit.</th>
                                <th v-if="showCost" class="py-3 px-4 text-right">Impacto Costo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-xs">
                            <tr
                                v-for="(p, index) in products.data"
                                :key="p.id"
                                class="hover:bg-white/[0.02] transition-colors"
                                :class="getDiff(p) !== null && getDiff(p) !== 0 ? 'bg-amber-500/[0.03]' : ''"
                            >
                                <td class="py-3 px-4 text-center text-gray-500 font-mono">
                                    {{ (products.from || 1) + index }}
                                </td>
                                
                                <!-- Producto -->
                                <td class="py-3 px-4">
                                    <div class="font-bold text-white text-sm tracking-tight">{{ p.name }}</div>
                                    <div class="text-[11px] text-gray-400 flex items-center gap-2 mt-0.5">
                                        <span v-if="p.barcode" class="font-mono bg-gray-800 px-1.5 py-0.5 rounded text-[10px] text-gray-300">
                                            {{ p.barcode }}
                                        </span>
                                        <span v-else-if="p.sku" class="font-mono bg-gray-800 px-1.5 py-0.5 rounded text-[10px] text-gray-300">
                                            {{ p.sku }}
                                        </span>
                                        <span v-if="p.branch" class="text-violet-400 text-[10px]">📍 {{ p.branch }}</span>
                                    </div>
                                    <!-- Badge de Estado de Conteo (Regla 10 Ventas) -->
                                    <div v-if="p.can_count === false" class="mt-1 flex items-center gap-1.5 flex-wrap">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/15 border border-amber-500/30 text-amber-300">
                                            <i class="fas fa-lock text-[9px]"></i>
                                            Bloqueado: {{ p.sales_since_last_count ?? 0 }}/10 ventas
                                        </span>
                                        <span class="text-[10px] text-gray-400">
                                            (Faltan {{ p.sales_needed }} ventas tras último conteo)
                                        </span>
                                    </div>
                                    <div v-else-if="p.last_counted_at" class="mt-1 flex items-center gap-1.5 flex-wrap">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 border border-emerald-500/30 text-emerald-300">
                                            <i class="fas fa-check-circle text-[9px]"></i>
                                            Habilitado ({{ p.sales_since_last_count }} ventas tras conteo)
                                        </span>
                                    </div>
                                </td>

                                <!-- Categoría / Localización -->
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-gray-200">
                                        {{ p.category ? p.category.name : 'General' }}
                                    </div>
                                </td>

                                <!-- Existencia Sistema -->
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-block px-2.5 py-1 rounded-lg font-bold font-mono text-xs bg-gray-800 text-gray-200 border border-white/5">
                                        {{ Number(p.stock || 0).toLocaleString() }}
                                    </span>
                                </td>

                                <!-- Input Conteo Físico -->
                                <td class="py-3 px-4 text-center">
                                    <div class="relative inline-block w-28">
                                        <div v-if="p.can_count === false" class="w-full text-center">
                                            <span
                                                class="inline-flex items-center justify-center gap-1 w-full py-1.5 px-2 rounded-xl bg-gray-800/80 border border-amber-500/30 text-amber-400/80 font-mono text-xs font-bold select-none cursor-not-allowed"
                                                :title="'Este producto fue contado recientemente en ' + (p.last_count_number || 'conteo previo') + ' y requiere al menos 10 ventas para volver a contarlo (lleva ' + (p.sales_since_last_count ?? 0) + ' ventas).'"
                                            >
                                                <i class="fas fa-lock text-[10px]"></i>
                                                {{ p.sales_since_last_count ?? 0 }}/10
                                            </span>
                                        </div>
                                        <input
                                            v-else
                                            type="number"
                                            min="0"
                                            v-model.number="countedMap[p.id]"
                                            :disabled="isFinalized"
                                            placeholder="—"
                                            class="w-full text-center font-mono font-bold text-sm bg-gray-800/90 border rounded-xl py-1.5 px-2 outline-none transition-all shadow-inner disabled:opacity-50 disabled:cursor-not-allowed"
                                            :class="[
                                                countedMap[p.id] !== undefined && countedMap[p.id] !== ''
                                                    ? (getDiff(p) === 0 
                                                        ? 'border-emerald-500/50 text-emerald-300 bg-emerald-950/20' 
                                                        : 'border-amber-500/50 text-amber-300 bg-amber-950/20')
                                                    : 'border-white/10 text-white focus:border-violet-500'
                                            ]"
                                        />
                                    </div>
                                </td>

                                <!-- Diferencia (Uds) -->
                                <td class="py-3 px-4 text-center">
                                    <template v-if="getDiff(p) !== null">
                                        <span
                                            v-if="getDiff(p) > 0"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold font-mono bg-emerald-500/15 text-emerald-400 border border-emerald-500/30"
                                            title="Sobrante en conteo físico"
                                        >
                                            <i class="fas fa-arrow-up text-[10px]"></i> +{{ getDiff(p) }} sobrante
                                        </span>
                                        <span
                                            v-else-if="getDiff(p) < 0"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold font-mono bg-red-500/15 text-red-400 border border-red-500/30"
                                            title="Faltante en conteo físico"
                                        >
                                            <i class="fas fa-arrow-down text-[10px]"></i> {{ getDiff(p) }} faltante
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold font-mono bg-gray-800 text-gray-400 border border-white/5"
                                        >
                                            <i class="fas fa-check text-emerald-400 text-[10px]"></i> 0 exacto
                                        </span>
                                    </template>
                                    <span v-else class="text-gray-600 font-mono">—</span>
                                </td>

                                <!-- Costo Unitario -->
                                <td v-if="showCost" class="py-3 px-4 text-right font-mono text-gray-300">
                                    {{ formatMoney(p.cost) }}
                                </td>

                                <!-- Impacto Costo Discrepancia -->
                                <td v-if="showCost" class="py-3 px-4 text-right font-mono">
                                    <template v-if="getDiffCost(p) !== null">
                                        <span
                                            v-if="getDiffCost(p) > 0"
                                            class="font-bold text-emerald-400"
                                        >
                                            + {{ formatMoney(getDiffCost(p)) }}
                                        </span>
                                        <span
                                            v-else-if="getDiffCost(p) < 0"
                                            class="font-bold text-red-400"
                                        >
                                            - {{ formatMoney(Math.abs(getDiffCost(p))) }}
                                        </span>
                                        <span v-else class="text-gray-500">
                                            C$ 0.00
                                        </span>
                                    </template>
                                    <span v-else class="text-gray-600">—</span>
                                </td>
                            </tr>

                            <tr v-if="!products.data || products.data.length === 0">
                                <td :colspan="showCost ? 8 : 6" class="py-12 text-center text-gray-500">
                                    <i class="fas fa-inbox text-3xl mb-2 text-gray-600"></i>
                                    <p class="text-sm">No se encontraron productos para los parámetros seleccionados.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación Simple -->
                <div v-if="products.links && products.links.length > 3" class="p-4 border-t border-white/5 flex items-center justify-between">
                    <span class="text-xs text-gray-400">
                        Mostrando {{ products.from }} a {{ products.to }} de {{ products.total }} artículos
                    </span>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in products.links"
                            :key="i"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                            :class="[
                                link.active ? 'bg-violet-600 text-white font-bold' : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ MODAL DE CONFIRMACIÓN: APLICAR CONTEO EN BODEGA ═════════════════ -->
        <div
            v-if="showApplyModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        >
            <div class="w-full max-w-lg bg-gray-900 border border-white/10 rounded-2xl p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 text-xl flex-shrink-0">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">¿Aplicar Conteo Físico en Bodega?</h3>
                        <p class="text-xs text-gray-400 mt-1">
                            Esta acción ajustará de forma inmediata las existencias de inventario en la bodega de 
                            <strong class="text-white">{{ branch }}</strong>.
                        </p>
                    </div>
                </div>

                <!-- Resumen de Discrepancias a Aplicar -->
                <div class="p-4 rounded-xl bg-gray-950/70 border border-white/5 space-y-3">
                    <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wide">
                        Resumen de Discrepancias Detectadas
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="p-3 rounded-lg bg-red-950/30 border border-red-500/20">
                            <span class="text-gray-400 block text-[11px]">Faltantes (Mermas / Pérdida):</span>
                            <strong class="text-red-400 text-sm block mt-0.5">
                                {{ liveDiscrepancies.faltantesUnits }} unidades
                            </strong>
                            <span v-if="showCost" class="text-red-300/80 text-[11px] font-mono">
                                - {{ formatMoney(liveDiscrepancies.faltantesCost) }}
                            </span>
                        </div>

                        <div class="p-3 rounded-lg bg-emerald-950/30 border border-emerald-500/20">
                            <span class="text-gray-400 block text-[11px]">Sobrantes (Excedentes):</span>
                            <strong class="text-emerald-400 text-sm block mt-0.5">
                                +{{ liveDiscrepancies.sobrantesUnits }} unidades
                            </strong>
                            <span v-if="showCost" class="text-emerald-300/80 text-[11px] font-mono">
                                + {{ formatMoney(liveDiscrepancies.sobrantesCost) }}
                            </span>
                        </div>
                    </div>

                    <div v-if="showCost" class="pt-2 border-t border-white/5 flex items-center justify-between text-xs">
                        <span class="text-gray-400 font-medium">Impacto Neto en Costo:</span>
                        <strong
                            class="font-mono text-sm"
                            :class="liveDiscrepancies.netCost < 0 ? 'text-red-400' : (liveDiscrepancies.netCost > 0 ? 'text-emerald-400' : 'text-gray-300')"
                        >
                            {{ liveDiscrepancies.netCost < 0 ? '- ' + formatMoney(Math.abs(liveDiscrepancies.netCost)) : '+ ' + formatMoney(liveDiscrepancies.netCost) }}
                        </strong>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        @click="showApplyModal = false"
                        :disabled="isApplying"
                        class="px-4 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 font-semibold text-xs transition-colors cursor-pointer"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="proceedToAuth"
                        :disabled="isApplying"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 flex items-center gap-2 transition-all cursor-pointer"
                    >
                        <i class="fas fa-shield-alt"></i>
                        <span>Autorizar y Ajustar Inventario</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ MODAL DE ÉXITO Y DESCARGA DE ACTA DE DISCREPANCIAS ═══════════════ -->
        <div
            v-if="showSuccessModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm"
        >
            <div class="w-full max-w-md bg-gray-900 border border-emerald-500/30 rounded-2xl p-6 shadow-2xl text-center space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="w-16 h-16 rounded-full bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-2xl mx-auto">
                    <i class="fas fa-check-circle"></i>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-white">¡Conteo Aplicado Exitosamente!</h3>
                    <p class="text-xs text-gray-300 mt-1.5">
                        {{ appliedResult?.message }}
                    </p>
                </div>

                <!-- Aviso si hubo diferencias -->
                <div
                    v-if="appliedResult?.has_discrepancies"
                    class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs text-amber-200"
                >
                    <i class="fas fa-exclamation-circle text-amber-400 mr-1"></i>
                    Se registraron <strong>{{ appliedResult.discrepancies_count }} productos con discrepancias</strong>.
                    Descarga el acta oficial valorizada con las firmas correspondientes.
                </div>

                <div class="flex flex-col gap-2 pt-2">
                    <a
                        v-if="appliedResult?.discrepancies_pdf_url"
                        :href="appliedResult.discrepancies_pdf_url"
                        target="_blank"
                        class="w-full py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-violet-600/30 flex items-center justify-center gap-2 transition-all cursor-pointer"
                    >
                        <i class="fas fa-file-pdf"></i>
                        <span>Imprimir Acta de Discrepancias (PDF)</span>
                    </a>

                    <button
                        @click="handleCloseSuccessModal"
                        class="w-full py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 font-semibold text-xs transition-colors cursor-pointer"
                    >
                        Cerrar y Actualizar Vista
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ MODAL DE HISTORIAL DE CONTEOS / PRELIMINARES ═════════════════════ -->
        <div
            v-if="showHistoryModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        >
            <div class="w-full max-w-2xl bg-gray-900 border border-white/10 rounded-2xl p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <!-- Encabezado del Modal -->
                <div class="flex items-center justify-between border-b border-white/10 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-violet-500/10 border border-violet-500/20 flex items-center justify-center text-violet-400">
                            <i class="fas fa-history text-xs"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white leading-tight">
                                Historial de Conteos {{ branch !== 'all' ? 'en ' + branch : '(Todas las Sucursales)' }}
                            </h3>
                            <p class="text-[11px] text-gray-400">
                                Registro de tomas físicas, actas de discrepancia y borradores
                            </p>
                        </div>
                    </div>
                    <button
                        @click="showHistoryModal = false"
                        class="w-7 h-7 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 flex items-center justify-center transition-colors cursor-pointer"
                        title="Cerrar modal"
                    >
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                <!-- Barra de Filtros Intuitivos -->
                <div class="space-y-2.5 bg-gray-950/60 p-3 rounded-xl border border-white/5">
                    <!-- Buscador en tiempo real -->
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
                        <input
                            v-model="historySearchQuery"
                            type="text"
                            placeholder="Buscar por código (ej. CNT-001), observación o fecha..."
                            class="w-full bg-gray-900 border border-white/10 rounded-lg pl-8 pr-8 py-1.5 text-white text-xs outline-none focus:border-violet-500 transition-colors placeholder:text-gray-500"
                        />
                        <button
                            v-if="historySearchQuery"
                            @click="historySearchQuery = ''"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-500 hover:text-white text-xs cursor-pointer"
                        >
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>

                    <!-- Pills / Chips de Estado Intuitivos -->
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <button
                                type="button"
                                @click="historyStatusFilter = 'all'"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 cursor-pointer"
                                :class="historyStatusFilter === 'all'
                                    ? 'bg-violet-600 text-white shadow-sm shadow-violet-600/30'
                                    : 'bg-gray-800/80 text-gray-400 hover:text-gray-200 hover:bg-gray-800 border border-white/5'"
                            >
                                <span>Todos</span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="historyStatusFilter === 'all' ? 'bg-violet-800 text-violet-100' : 'bg-gray-700 text-gray-300'">
                                    {{ countsStats.total }}
                                </span>
                            </button>

                            <button
                                type="button"
                                @click="historyStatusFilter = 'aplicado'"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 cursor-pointer"
                                :class="historyStatusFilter === 'aplicado'
                                    ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30'
                                    : 'bg-gray-800/80 text-gray-400 hover:text-emerald-300 hover:bg-gray-800 border border-white/5'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                <span>Aplicados</span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="historyStatusFilter === 'aplicado' ? 'bg-emerald-800 text-emerald-100' : 'bg-gray-700 text-gray-300'">
                                    {{ countsStats.aplicados }}
                                </span>
                            </button>

                            <button
                                type="button"
                                @click="historyStatusFilter = 'preliminar'"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 cursor-pointer"
                                :class="historyStatusFilter === 'preliminar'
                                    ? 'bg-amber-600 text-white shadow-sm shadow-amber-600/30'
                                    : 'bg-gray-800/80 text-gray-400 hover:text-amber-300 hover:bg-gray-800 border border-white/5'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                <span>Borradores</span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="historyStatusFilter === 'preliminar' ? 'bg-amber-800 text-amber-100' : 'bg-gray-700 text-gray-300'">
                                    {{ countsStats.preliminares }}
                                </span>
                            </button>

                            <button
                                type="button"
                                @click="historyStatusFilter = 'discrepancies'"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 cursor-pointer"
                                :class="historyStatusFilter === 'discrepancies'
                                    ? 'bg-rose-600 text-white shadow-sm shadow-rose-600/30'
                                    : 'bg-gray-800/80 text-gray-400 hover:text-rose-300 hover:bg-gray-800 border border-white/5'"
                            >
                                <i class="fas fa-exclamation-triangle text-[10px]" :class="historyStatusFilter === 'discrepancies' ? 'text-white' : 'text-amber-400'"></i>
                                <span>Con Diferencias</span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="historyStatusFilter === 'discrepancies' ? 'bg-rose-800 text-rose-100' : 'bg-gray-700 text-gray-300'">
                                    {{ countsStats.discrepancies }}
                                </span>
                            </button>
                        </div>

                        <!-- Selector de Sucursal si está en vista general -->
                        <div v-if="branch === 'all' && branches?.length" class="flex items-center gap-1.5">
                            <span class="text-[10px] text-gray-400 font-medium">Sede:</span>
                            <select
                                v-model="historyBranchFilter"
                                class="bg-gray-900 border border-white/10 rounded-lg px-2 py-1 text-white text-[11px] outline-none focus:border-violet-500 cursor-pointer"
                            >
                                <option value="all">Todas las sedes</option>
                                <option v-for="b in branches" :key="b" :value="b">{{ b }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Lista de Conteos en Tarjetas Estructuradas -->
                <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                    <div
                        v-if="!filteredCounts || filteredCounts.length === 0"
                        class="py-8 text-center text-gray-400 text-xs space-y-2"
                    >
                        <i class="fas fa-filter text-2xl text-gray-600 block"></i>
                        <p>No se encontraron conteos con los filtros seleccionados.</p>
                        <button
                            v-if="historyStatusFilter !== 'all' || historySearchQuery || historyBranchFilter !== 'all'"
                            @click="historyStatusFilter = 'all'; historySearchQuery = ''; historyBranchFilter = 'all';"
                            class="text-violet-400 hover:text-violet-300 text-xs underline cursor-pointer"
                        >
                            Restablecer filtros
                        </button>
                    </div>

                    <div
                        v-for="cnt in filteredCounts"
                        :key="cnt.id"
                        class="p-4 rounded-xl bg-gray-800/70 border border-white/10 hover:border-violet-500/30 transition-all space-y-3"
                    >
                        <!-- Fila 1: Código + Estado + Sede (si all) + Fecha -->
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-xs text-white bg-gray-950/80 px-2 py-0.5 rounded border border-white/10">
                                    {{ cnt.count_number }}
                                </span>
                                <span
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border inline-flex items-center gap-1.5"
                                    :class="cnt.status === 'aplicado' ? 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30' : 'bg-amber-500/15 text-amber-300 border-amber-500/30'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="cnt.status === 'aplicado' ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                                    {{ cnt.status === 'aplicado' ? 'Aplicado' : 'Borrador' }}
                                </span>
                                <span
                                    v-if="branch === 'all'"
                                    class="px-2 py-0.5 rounded text-[10px] bg-gray-900 text-gray-300 border border-white/5 font-medium"
                                >
                                    <i class="fas fa-map-marker-alt text-violet-400 mr-1 text-[9px]"></i>{{ cnt.branch }}
                                </span>
                            </div>

                            <span class="text-[11px] text-gray-400 flex items-center gap-1">
                                <i class="far fa-calendar-alt text-gray-500 text-[10px]"></i>
                                {{ formatDate(cnt.applied_at || cnt.created_at) }}
                            </span>
                        </div>

                        <!-- Fila 2: Indicadores Clave (Items, Discrepancias, Diferencia Neta) -->
                        <div class="grid grid-cols-3 gap-2">
                            <div class="px-2.5 py-1.5 rounded-lg bg-gray-900/60 border border-white/5">
                                <span class="text-[9px] uppercase tracking-wider text-gray-400 font-semibold block">Contados</span>
                                <span class="text-xs font-bold text-white mt-0.5 block">
                                    {{ cnt.total_items_counted ?? 0 }} <span class="text-[10px] text-gray-400 font-normal">items</span>
                                </span>
                            </div>

                            <div class="px-2.5 py-1.5 rounded-lg bg-gray-900/60 border border-white/5">
                                <span class="text-[9px] uppercase tracking-wider text-gray-400 font-semibold block">Discrepancias</span>
                                <span
                                    v-if="cnt.total_discrepancies > 0"
                                    class="text-xs font-bold text-amber-400 flex items-center gap-1 mt-0.5"
                                >
                                    <i class="fas fa-exclamation-triangle text-[10px]"></i>
                                    {{ cnt.total_discrepancies }}
                                </span>
                                <span
                                    v-else
                                    class="text-xs font-semibold text-emerald-400 flex items-center gap-1 mt-0.5"
                                >
                                    <i class="fas fa-check-circle text-[10px]"></i>
                                    0
                                </span>
                            </div>

                            <div class="px-2.5 py-1.5 rounded-lg bg-gray-900/60 border border-white/5">
                                <span class="text-[9px] uppercase tracking-wider text-gray-400 font-semibold block">Diferencia Neta</span>
                                <span
                                    class="text-xs font-bold font-mono mt-0.5 block truncate"
                                    :class="Number(cnt.net_cost_difference || 0) < 0 ? 'text-red-400' : (Number(cnt.net_cost_difference || 0) > 0 ? 'text-emerald-400' : 'text-gray-300')"
                                >
                                    {{ formatMoney(cnt.net_cost_difference) }}
                                </span>
                            </div>
                        </div>

                        <!-- Observaciones / Notas (si existen) -->
                        <div
                            v-if="cnt.notes"
                            class="text-[11px] text-gray-300 italic bg-gray-900/40 px-2.5 py-1 rounded-lg border border-white/5 flex items-center gap-1.5"
                        >
                            <i class="fas fa-comment-dots text-gray-500 text-[10px]"></i>
                            <span>{{ cnt.notes }}</span>
                        </div>

                        <!-- Fila 3: Botones de Acción -->
                        <div class="flex items-center justify-end gap-2 pt-1 border-t border-white/5">
                            <button
                                v-if="cnt.status === 'preliminar'"
                                @click="loadExistingCount(cnt)"
                                class="px-3 py-1.5 rounded-lg bg-violet-600 hover:bg-violet-500 text-white font-semibold text-xs flex items-center gap-1.5 transition-colors cursor-pointer"
                            >
                                <i class="fas fa-edit text-xs"></i>
                                <span>Continuar Borrador</span>
                            </button>

                            <a
                                :href="route('reports.inventory.count.discrepanciesPdf', cnt.id)"
                                target="_blank"
                                class="px-3 py-1.5 rounded-lg bg-gray-700/80 hover:bg-gray-700 text-gray-200 hover:text-white border border-white/10 font-medium text-xs flex items-center gap-1.5 transition-colors cursor-pointer"
                                title="Descargar o Ver Acta de Discrepancias en PDF"
                            >
                                <i class="fas fa-file-pdf text-red-400"></i>
                                <span>Acta PDF</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Pie del Modal -->
                <div class="pt-2 border-t border-white/10 flex items-center justify-between">
                    <span class="text-[11px] text-gray-400">
                        Mostrando {{ filteredCounts.length }} de {{ recentCounts?.length || 0 }} conteo(s)
                    </span>
                    <button
                        @click="showHistoryModal = false"
                        class="px-4 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white text-xs font-semibold cursor-pointer transition-colors"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

            <!-- ══ MODAL DE AUTORIZACIÓN: ADMIN / SUPERADMIN ════════════════════════ -->
        <AuthModal
            :show="showAuthModal"
            title="Autorización de Ajuste de Bodega"
            :subtitle="'Ingrese credenciales de Administrador o Superadministrador asignado a ' + branch + ' para aplicar el ajuste.'"
            action-label="Autorizar y Ajustar"
            :processing="isApplying"
            :error-message="authErrorMessage"
            :initial-username="initialAuthUsername"
            @close="handleCloseAuthModal"
            @confirm="handleAuthorizeAndApply"
        />

    </AdminLayout>
</template>
