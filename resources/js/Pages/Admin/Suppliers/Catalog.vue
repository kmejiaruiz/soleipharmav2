<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/composables/useToast';

const { show: toast } = useToast();

const props = defineProps({
    supplier:    Object,
    allProducts: Array,
});

// ── Tab activo: 'linking' (vinculación) o 'costs' (gestión de costos e IVA) ─────
const activeTab = ref('linking');

// ── Tab 1: Vinculación ──────────────────────────────────────────────────────────
const linkedIds = ref(new Set((props.supplier?.products || []).map(p => p.id)));
const search = ref('');

const filteredProducts = computed(() => {
    const q = search.value.toLowerCase().trim();
    if (!q) return props.allProducts || [];
    return (props.allProducts || []).filter(p =>
        p.name.toLowerCase().includes(q) ||
        (p.barcode || '').toLowerCase().includes(q)
    );
});

function toggleProduct(productId) {
    if (linkedIds.value.has(productId)) {
        linkedIds.value.delete(productId);
    } else {
        linkedIds.value.add(productId);
    }
}

function toggleAll() {
    const allLinked = filteredProducts.value.every(p => linkedIds.value.has(p.id));
    filteredProducts.value.forEach(p => {
        if (allLinked) {
            linkedIds.value.delete(p.id);
        } else {
            linkedIds.value.add(p.id);
        }
    });
}

const allFilteredLinked = computed(() =>
    filteredProducts.value.length > 0 && filteredProducts.value.every(p => linkedIds.value.has(p.id))
);

const savingLinking = ref(false);
function saveLinking() {
    savingLinking.value = true;
    const product_ids = [...linkedIds.value];

    router.post(route('suppliers.catalog.sync', props.supplier.id), { product_ids }, {
        preserveScroll: true,
        onSuccess: (page) => {
            const prods = page.props.supplier?.products || props.supplier?.products;
            if (prods) {
                syncCostItems(prods);
                linkedIds.value = new Set(prods.map(p => p.id));
            }
        },
        onError:  () => toast('Error al guardar el catálogo. Intenta de nuevo.', 'error'),
        onFinish: () => { savingLinking.value = false; },
    });
}

// ── Tab 2: Gestión de Costos, IVA Compra/Venta y Predicciones de Precio ────────
const costItems = ref([]);

function syncCostItems(products) {
    const list = products || [];
    costItems.value = list.map(p => ({
        id:                   p.id,
        name:                 p.name,
        barcode:              p.barcode || '—',
        cost:                 parseFloat(p.cost) || 0,
        purchase_tax_percent: parseFloat(p.purchase_tax_percent ?? 15) || 0,
        utility_percent:      parseFloat(p.utility_percent ?? 30) || 0,
        tax_percent:          parseFloat(p.tax_percent ?? 15) || 0,
        price:                parseFloat(p.price) || 0,
    }));
}

// Sincronización reactiva continua cuando se actualicen las props
watch(
    () => props.supplier?.products,
    (newProducts) => {
        if (newProducts) {
            linkedIds.value = new Set(newProducts.map(p => p.id));
            syncCostItems(newProducts);
        }
    },
    { immediate: true, deep: true }
);

// ── Refrescar datos con loader ──────────────────────────────────────────────────
const isRefreshing = ref(false);

function refreshCatalog() {
    isRefreshing.value = true;
    router.reload({
        only: ['supplier', 'allProducts'],
        preserveScroll: true,
        onSuccess: (page) => {
            const prods = page.props.supplier?.products || props.supplier?.products;
            if (prods) {
                linkedIds.value = new Set(prods.map(p => p.id));
                syncCostItems(prods);
            }
            if (!page.props.flash?.success) {
                toast('Catálogo y artículos vinculados actualizados.', 'success');
            }
        },
        onError: () => toast('Error al refrescar los datos.', 'error'),
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
}

const searchCost = ref('');
const filteredCostItems = computed(() => {
    const q = searchCost.value.toLowerCase().trim();
    if (!q) return costItems.value;
    return costItems.value.filter(p =>
        p.name.toLowerCase().includes(q) ||
        (p.barcode || '').toLowerCase().includes(q)
    );
});

// Cálculo del Costo Total de Compra con IVA del Proveedor
function getPurchaseCostWithTax(item) {
    const c = parseFloat(item.cost) || 0;
    const pt = parseFloat(item.purchase_tax_percent) || 0;
    return Number(Math.round((c * (1 + pt / 100)) + 'e2') + 'e-2');
}

// Fórmula de Predicción de Precio Sugerido de Venta al Público:
// (Costo Neto * (1 + %Utilidad/100)) * (1 + %IVA Venta/100)
function getSuggestedPrice(item) {
    const c = parseFloat(item.cost) || 0;
    const u = parseFloat(item.utility_percent) || 0;
    const t = parseFloat(item.tax_percent) || 0;

    const basePrice = c * (1 + u / 100);
    const finalPrice = basePrice * (1 + t / 100);
    return Number(Math.round(finalPrice + 'e2') + 'e-2');
}

function applySuggestedPrice(item) {
    item.price = getSuggestedPrice(item);
}

function applyAllSuggestedPrices() {
    costItems.value.forEach(item => {
        item.price = getSuggestedPrice(item);
    });
    toast(`Precio sugerido aplicado a ${costItems.value.length} artículo(s). Guarda los cambios para persistir.`, 'info');
}

const savingCosts = ref(false);
function saveCosts() {
    savingCosts.value = true;
    router.post(route('suppliers.costs.update', props.supplier.id), {
        items: costItems.value.map(i => ({
            id:                   i.id,
            cost:                 parseFloat(i.cost) || 0,
            purchase_tax_percent: parseFloat(i.purchase_tax_percent) || 0,
            utility_percent:      parseFloat(i.utility_percent) || 0,
            tax_percent:          parseFloat(i.tax_percent) || 0,
            price:                parseFloat(i.price) || 0,
        }))
    }, {
        preserveScroll: true,
        onError:  () => toast('Error al guardar costos e IVA. Verifica los datos.', 'error'),
        onFinish: () => { savingCosts.value = false; },
    });
}

const linkedCount = computed(() => linkedIds.value.size);
const fmtMoney = (n) => 'C$ ' + (parseFloat(n) || 0).toFixed(2);
</script>

<template>
    <Head :title="`Catálogo y Costos — ${supplier.name}`" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('suppliers.index')" class="w-9 h-9 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white flex items-center justify-center transition-colors">
                        <i class="fas fa-arrow-left text-sm"></i>
                    </Link>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-boxes text-violet-400 text-xl"></i>
                        <div>
                            <h1 class="text-white font-semibold text-base leading-tight">Gestión por Proveedor</h1>
                            <p class="text-gray-500 text-xs">Proveedor: <strong class="text-violet-300">{{ supplier.name }}</strong></p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full bg-violet-500/15 text-violet-300 text-xs font-semibold border border-violet-500/20">
                        {{ linkedCount }} artículos vinculados
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        v-if="activeTab === 'linking'"
                        @click="saveLinking"
                        :disabled="savingLinking || isRefreshing"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 disabled:opacity-60 text-white font-bold text-sm shadow-lg shadow-violet-900/30 transition-all flex items-center gap-2 cursor-pointer"
                    >
                        <i v-if="savingLinking" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-save"></i>
                        {{ savingLinking ? 'Guardando...' : 'Guardar Vinculación' }}
                    </button>
                    <button
                        v-else
                        @click="saveCosts"
                        :disabled="savingCosts || isRefreshing"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 disabled:opacity-60 text-white font-bold text-sm shadow-lg shadow-emerald-900/30 transition-all flex items-center gap-2 cursor-pointer"
                    >
                        <i v-if="savingCosts" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-save"></i>
                        {{ savingCosts ? 'Guardando...' : 'Guardar Costos e IVA' }}
                    </button>
                </div>
            </div>
        </template>

        <!-- Selector de Tabs -->
        <div class="flex items-center gap-2 mb-6 border-b border-white/10 pb-3 flex-wrap">
            <button
                @click="activeTab = 'linking'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
                :class="activeTab === 'linking' ? 'bg-violet-600 text-white shadow-lg shadow-violet-900/30' : 'bg-gray-900 text-gray-400 hover:text-white border border-white/5'"
            >
                <i class="fas fa-link"></i>
                1. Vinculación de Productos (Catálogo)
            </button>
            <button
                @click="activeTab = 'costs'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
                :class="activeTab === 'costs' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/30' : 'bg-gray-900 text-gray-400 hover:text-white border border-white/5'"
            >
                <i class="fas fa-calculator"></i>
                2. Vista Dedicada: Costos, IVA Compra/Venta y Predicciones de Precio
            </button>

            <!-- Botón de Refresh con Loader al lado de Vista Dedicada -->
            <button
                type="button"
                @click="refreshCatalog"
                :disabled="isRefreshing"
                class="px-3.5 py-2 rounded-xl bg-gray-900 hover:bg-gray-800 text-gray-300 hover:text-white border border-white/10 text-xs font-bold transition-all flex items-center gap-2 disabled:opacity-50 cursor-pointer shadow-sm"
                title="Refrescar catálogo y artículos vinculados"
            >
                <i :class="['fas fa-sync-alt text-xs', isRefreshing ? 'fa-spin text-emerald-400' : 'text-gray-400']"></i>
                <span>{{ isRefreshing ? 'Refrescando...' : 'Refrescar' }}</span>
            </button>
        </div>

        <!-- ══ TAB 1: VINCULACIÓN DE PRODUCTOS ═════════════════════════════════ -->
        <div v-if="activeTab === 'linking'" class="space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <div class="relative flex-1 max-w-lg">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar producto por nombre o código de barras..."
                        class="w-full pl-11 pr-4 py-2.5 bg-gray-900 border border-white/10 focus:border-violet-500 rounded-xl text-white placeholder-gray-600 text-sm outline-none transition-colors"
                    />
                </div>

                <div class="text-xs text-gray-400 bg-gray-900 px-4 py-2.5 rounded-xl border border-white/5">
                    <i class="fas fa-info-circle text-violet-400 mr-1"></i>
                    Selecciona los productos que distribuye <strong class="text-violet-300">{{ supplier.name }}</strong>.
                </div>
            </div>

            <div class="relative bg-gray-900 rounded-2xl border border-white/5 overflow-hidden shadow-xl min-h-[220px]">
                <!-- Loader overlay mientras se refresca -->
                <div v-if="isRefreshing" class="absolute inset-0 z-20 bg-gray-950/80 backdrop-blur-sm flex flex-col items-center justify-center gap-3 transition-opacity">
                    <div class="relative flex items-center justify-center">
                        <div class="w-12 h-12 rounded-full border-4 border-violet-500/20 border-t-violet-500 animate-spin"></div>
                        <i class="fas fa-sync-alt text-violet-400 text-sm absolute"></i>
                    </div>
                    <p class="text-sm font-semibold text-white">Refrescando catálogo...</p>
                    <p class="text-xs text-gray-400">Actualizando productos vinculados</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-white/5 bg-gray-950/40">
                                <th class="px-4 py-4 w-10">
                                    <button @click="toggleAll" :title="allFilteredLinked ? 'Desvincular todos' : 'Vincular todos'"
                                        class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                                        :class="allFilteredLinked ? 'bg-violet-600 border-violet-600' : 'border-gray-600 hover:border-violet-500'">
                                        <i v-if="allFilteredLinked" class="fas fa-check text-white text-[9px]"></i>
                                    </button>
                                </th>
                                <th class="px-2 py-4">Producto</th>
                                <th class="px-4 py-4">Código de Barras</th>
                                <th class="px-4 py-4 text-right">Costo Actual (C$)</th>
                                <th class="px-6 py-4 text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-gray-300">
                            <tr v-for="product in filteredProducts" :key="product.id"
                                class="hover:bg-white/2 transition-colors cursor-pointer"
                                :class="linkedIds.has(product.id) ? 'bg-violet-900/10' : ''"
                                @click.stop="toggleProduct(product.id)">
                                <td class="px-4 py-3.5" @click.stop="toggleProduct(product.id)">
                                    <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-all"
                                        :class="linkedIds.has(product.id) ? 'bg-violet-600 border-violet-600' : 'border-gray-600'">
                                        <i v-if="linkedIds.has(product.id)" class="fas fa-check text-white text-[9px]"></i>
                                    </div>
                                </td>
                                <td class="px-2 py-3.5" @click.stop="toggleProduct(product.id)">
                                    <span class="font-semibold text-sm" :class="linkedIds.has(product.id) ? 'text-white' : 'text-gray-400'">
                                        {{ product.name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5" @click.stop="toggleProduct(product.id)">
                                    <span v-if="product.barcode" class="font-mono text-xs text-gray-400 bg-gray-800 px-2 py-0.5 rounded">
                                        {{ product.barcode }}
                                    </span>
                                    <span v-else class="text-gray-700 text-xs">—</span>
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-xs text-gray-400">
                                    {{ fmtMoney(product.cost) }}
                                </td>
                                <td class="px-6 py-3.5 text-center" @click.stop="toggleProduct(product.id)">
                                    <span v-if="linkedIds.has(product.id)"
                                        class="px-2.5 py-1 rounded-full bg-violet-500/15 text-violet-300 text-xs font-semibold border border-violet-500/20">
                                        <i class="fas fa-link mr-1 text-[10px]"></i> Vinculado
                                    </span>
                                    <span v-else class="px-2.5 py-1 rounded-full bg-gray-800 text-gray-600 text-xs font-semibold border border-white/5">
                                        Sin vincular
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══ TAB 2: VISTA DEDICADA DE COSTOS, IVA Y PREDICCIONES ═════════════ -->
        <div v-else class="space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="relative flex-1 max-w-lg">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
                    <input
                        v-model="searchCost"
                        type="text"
                        placeholder="Buscar artículo por nombre o SKU..."
                        class="w-full pl-11 pr-4 py-2.5 bg-gray-900 border border-white/10 focus:border-emerald-500 rounded-xl text-white placeholder-gray-600 text-sm outline-none transition-colors"
                    />
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="applyAllSuggestedPrices"
                        class="px-4 py-2.5 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 text-amber-300 text-xs font-bold border border-amber-500/30 transition-all flex items-center gap-2 cursor-pointer"
                        title="Aplica automáticamente el Precio Sugerido en todos los artículos"
                    >
                        <i class="fas fa-magic"></i> Aplicar Todo Sugerido
                    </button>
                    <div class="text-xs text-gray-400 bg-gray-900 px-4 py-2 rounded-xl border border-white/5">
                        <i class="fas fa-info-circle text-emerald-400 mr-1"></i>
                        El **% IVA Compra** (proveedor) es totalmente editable por artículo de forma separada del **% IVA Venta**.
                    </div>
                </div>
            </div>

            <!-- Tabla de Costos, IVA Compra, IVA Venta y Predicción Sugerida -->
            <div class="relative bg-gray-900 rounded-2xl border border-white/5 overflow-hidden shadow-xl min-h-[220px]">
                <!-- Loader overlay mientras se refresca -->
                <div v-if="isRefreshing" class="absolute inset-0 z-20 bg-gray-950/80 backdrop-blur-sm flex flex-col items-center justify-center gap-3 transition-opacity">
                    <div class="relative flex items-center justify-center">
                        <div class="w-12 h-12 rounded-full border-4 border-emerald-500/20 border-t-emerald-500 animate-spin"></div>
                        <i class="fas fa-sync-alt text-emerald-400 text-sm absolute"></i>
                    </div>
                    <p class="text-sm font-semibold text-white">Refrescando costos y artículos vinculados...</p>
                    <p class="text-xs text-gray-400">Actualizando la lista con la base de datos</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-gray-500 text-[10px] font-semibold uppercase tracking-wider border-b border-white/5 bg-gray-950/40">
                                <th class="px-4 py-3">Artículo</th>
                                <th class="px-2 py-3 text-right">Costo Neto</th>
                                <th class="px-2 py-3 text-center">% IVA Compra</th>
                                <th class="px-2 py-3 text-right">c/IVA Compra</th>
                                <th class="px-2 py-3 text-center">% Utilidad</th>
                                <th class="px-2 py-3 text-center">% IVA Venta</th>
                                <th class="px-2 py-3 text-right">Precio Sugerido</th>
                                <th class="px-3 py-3 text-right">Precio Venta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-gray-300">
                            <tr v-for="item in filteredCostItems" :key="item.id" class="hover:bg-white/2 transition-colors">
                                <!-- Artículo -->
                                <td class="px-4 py-2.5">
                                    <p class="font-semibold text-white text-xs leading-tight">{{ item.name }}</p>
                                    <p class="text-gray-600 text-[10px] font-mono mt-0.5">{{ item.barcode }}</p>
                                </td>

                                <!-- Costo Neto (Editable) -->
                                <td class="px-2 py-2.5 text-right">
                                    <div class="flex items-center justify-end gap-0.5">
                                        <span class="text-gray-600 text-[10px]">C$</span>
                                        <input
                                            v-model.number="item.cost"
                                            type="number" step="0.01" min="0"
                                             class="w-20 bg-gray-800 border border-white/10 focus:border-emerald-500 rounded px-1.5 py-0.5 text-right text-white text-xs font-bold outline-none"
                                        />
                                    </div>
                                </td>

                                <!-- % IVA Compra (EDITABLE POR ARTÍCULO Y PROVEEDOR) -->
                                <td class="px-2 py-2.5 text-center bg-violet-950/20 border-x border-violet-500/10">
                                    <div class="inline-flex items-center gap-0.5">
                                        <input
                                            v-model.number="item.purchase_tax_percent"
                                            type="number" step="1" min="0" max="100"
                                            class="w-12 bg-gray-800 border border-violet-500/30 focus:border-violet-500 rounded px-1 py-0.5 text-center text-violet-300 text-xs font-bold outline-none"
                                        />
                                        <span class="text-violet-400 text-[10px] font-bold">%</span>
                                    </div>
                                </td>

                                <!-- Costo Compra c/IVA Proveedor (Calculado en tiempo real) -->
                                <td class="px-2 py-2.5 text-right font-mono text-[11px] text-violet-300 font-bold bg-violet-950/20 border-r border-violet-500/10">
                                    {{ fmtMoney(getPurchaseCostWithTax(item)) }}
                                </td>

                                <!-- Utilidad % (Editable) -->
                                <td class="px-2 py-2.5 text-center">
                                    <div class="inline-flex items-center gap-0.5">
                                        <input
                                            v-model.number="item.utility_percent"
                                            type="number" step="1" min="0" max="500"
                                            class="w-12 bg-gray-800 border border-white/10 focus:border-amber-500 rounded px-1 py-0.5 text-center text-amber-400 text-xs font-bold outline-none"
                                        />
                                        <span class="text-gray-500 text-[10px]">%</span>
                                    </div>
                                </td>

                                <!-- IVA Venta % (Editable) -->
                                <td class="px-2 py-2.5 text-center">
                                    <div class="inline-flex items-center gap-0.5">
                                        <input
                                            v-model.number="item.tax_percent"
                                            type="number" step="1" min="0" max="100"
                                            class="w-12 bg-gray-800 border border-white/10 focus:border-violet-500 rounded px-1 py-0.5 text-center text-violet-400 text-xs font-bold outline-none"
                                        />
                                        <span class="text-gray-500 text-[10px]">%</span>
                                    </div>
                                </td>

                                <!-- Precio Sugerido Venta (Predicción en Tiempo Real) -->
                                <td class="px-2 py-2.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <span class="px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-300 font-mono font-bold text-[11px] border border-amber-500/20">
                                            {{ fmtMoney(getSuggestedPrice(item)) }}
                                        </span>
                                        <button
                                            @click="applySuggestedPrice(item)"
                                            class="w-5 h-5 rounded bg-amber-500/20 hover:bg-amber-500/35 text-amber-300 text-[9px] flex items-center justify-center transition-colors cursor-pointer"
                                            title="Usar precio sugerido"
                                        >
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </div>
                                </td>

                                <!-- Precio Venta Definido (Editable) -->
                                <td class="px-3 py-2.5 text-right">
                                    <div class="flex items-center justify-end gap-0.5">
                                        <span class="text-gray-600 text-[10px]">C$</span>
                                        <input
                                            v-model.number="item.price"
                                            type="number" step="0.01" min="0"
                                            class="w-20 bg-gray-800 border border-emerald-500/30 focus:border-emerald-500 rounded px-1.5 py-0.5 text-right text-emerald-400 text-xs font-bold outline-none"
                                        />
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="filteredCostItems.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-gray-600">
                                    <i class="fas fa-box-open text-4xl mb-3 text-gray-700 block"></i>
                                    <p class="text-sm font-medium">No hay artículos vinculados a este proveedor para gestionar costos</p>
                                    <p class="text-xs text-gray-600 mt-1">Primero vincula productos en la pestaña "1. Vinculación de Productos" y asegúrate de guardar la vinculación.</p>
                                    <div class="mt-4 flex items-center justify-center gap-3">
                                        <button
                                            type="button"
                                            @click="refreshCatalog"
                                            :disabled="isRefreshing"
                                            class="px-4 py-2 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-500/30 text-xs font-bold transition-all inline-flex items-center gap-2 cursor-pointer disabled:opacity-50"
                                        >
                                            <i :class="['fas fa-sync-alt text-xs', isRefreshing ? 'fa-spin text-emerald-300' : '']"></i>
                                            <span>{{ isRefreshing ? 'Refrescando...' : 'Refrescar ahora' }}</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
