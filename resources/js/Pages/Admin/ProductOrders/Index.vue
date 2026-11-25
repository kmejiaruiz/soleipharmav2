<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AuthModal from '@/Components/AuthModal.vue';

const props = defineProps({
    orders: Object,
    goodsEntries: Object,
    suppliers: Array,
    products: Array,
    branches: Array,
    filters: Object,
});

const page = usePage();
const activeTab = ref(props.filters?.tab || 'orders');
const status = ref(props.filters?.status || 'all');

// Modales
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const showEntryModal  = ref(false);
const showAuthModal   = ref(false);
const authError       = ref('');
const selectedOrder   = ref(null);
const selectedEntry   = ref(null);

const form = useForm({
    admin_username: '',
    admin_password: '',
    supplier_id: '',
    branch: '',
    items: [
        { product_id: '', quantity: 1, price: 0 }
    ],
});

// Productos filtrados según el proveedor elegido y sucursal destino
const filteredProducts = computed(() => {
    if (!form.supplier_id) return [];
    const supId = parseInt(form.supplier_id);
    const targetBranch = form.branch || page.props.auth?.user?.branch;
    const linked = props.products.filter(p => {
        const matchesSup = p.supplier_id === supId || (p.supplier_ids && p.supplier_ids.includes(supId));
        if (!matchesSup) return false;
        if (targetBranch && p.branch && p.branch !== targetBranch) return false;
        return true;
    });
    return linked;
});

function addItem() {
    form.items.push({ product_id: '', quantity: 1, price: 0 });
}

function removeItem(index) {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
}

function onProductSelect(index) {
    const item = form.items[index];
    const product = props.products.find(p => p.id === parseInt(item.product_id));
    if (product) {
        item.price = product.cost || product.price || 0;
    }
}

function onPriceChange(index, val) {
    if (typeof val === 'string') {
        const cleaned = val.replace(',', '.');
        form.items[index].price = parseFloat(cleaned) || 0;
    }
}

function openCreateModal() {
    form.reset();
    form.clearErrors();
    authError.value  = '';
    form.supplier_id = props.suppliers[0]?.id || '';
    form.branch      = page.props.auth?.user?.branch || props.branches?.[0] || 'Sucursal Managua';
    form.items       = [{ product_id: '', quantity: 1, price: 0 }];
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
    showAuthModal.value   = false;
    authError.value       = '';
    form.reset();
    form.clearErrors();
}

function openDetailModal(order) {
    selectedOrder.value   = order;
    showDetailModal.value = true;
}

function closeDetailModal() {
    showDetailModal.value = false;
    selectedOrder.value   = null;
}

function openEntryModal(entry) {
    selectedEntry.value  = entry;
    showEntryModal.value = true;
}

function closeEntryModal() {
    showEntryModal.value = false;
    selectedEntry.value  = null;
}

function openOrderPdf(orderId) {
    if (orderId) {
        window.open(route('product-orders.pdf', orderId), '_blank');
    }
}

// Paso 1: Validar pedido y abrir modal de credenciales de autorización
function startOrderAuthorization() {
    form.clearErrors();
    authError.value = '';

    if (!form.supplier_id) {
        form.setError('supplier_id', 'Seleccione un proveedor.');
        return;
    }
    const hasInvalidItem = form.items.some(it => !it.product_id || it.quantity < 1);
    if (hasInvalidItem) {
        form.setError('items', 'Todos los productos deben estar seleccionados con cantidad mayor a 0.');
        return;
    }

    // Limpiar precios
    form.items.forEach(item => {
        if (typeof item.price === 'string') {
            item.price = parseFloat(item.price.replace(',', '.')) || 0;
        }
    });

    showAuthModal.value = true;
}

// Paso 2: Confirmar credenciales y enviar formulario
function confirmOrderCreation({ username, password }) {
    form.admin_username = username;
    form.admin_password = password;
    authError.value = '';

    form.post(route('product-orders.store'), {
        preserveScroll: true,
        onSuccess: () => {
            const newId = page.props.flash?.new_order_id;
            closeCreateModal();
            if (newId) {
                openOrderPdf(newId);
            }
        },
        onError: (errs) => {
            if (errs.admin_credentials) {
                authError.value = errs.admin_credentials;
            } else if (errs.admin_password) {
                authError.value = errs.admin_password;
            } else if (errs.error) {
                authError.value = errs.error;
            } else {
                authError.value = 'Ocurrió un error en la autorización o validación.';
            }
        }
    });
}

// Menú de acciones desplegable
const openDropdownId = ref(null);

function toggleActionMenu(id) {
    openDropdownId.value = openDropdownId.value === id ? null : id;
}

function closeActionMenu() {
    openDropdownId.value = null;
}

function handleGlobalClick(e) {
    if (!e.target.closest('.action-menu-container')) {
        closeActionMenu();
    }
}

function handleKeydown(e) {
    if (e.key === 'Escape') {
        if (openDropdownId.value !== null) {
            closeActionMenu();
            return;
        }
        if (showAuthModal.value) {
            showAuthModal.value = false;
            return;
        }
        if (showCreateModal.value) closeCreateModal();
        if (showDetailModal.value) closeDetailModal();
        if (showEntryModal.value)  closeEntryModal();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
    window.addEventListener('click', handleGlobalClick);
    if (page.props.flash?.new_entry_id) {
        openPdf(page.props.flash.new_entry_id);
    }
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    window.removeEventListener('click', handleGlobalClick);
});

const statusBadges = {
    pending:  { label: 'Pendiente', class: 'bg-amber-500/15 text-amber-400 border-amber-500/20' },
    applied:  { label: 'Aplicado',  class: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/20' },
    received: { label: 'Recibido',  class: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/20' },
};

const fmtMoney = (n) => 'C$ ' + (parseFloat(n) || 0).toFixed(2);
const printEntry = () => window.print();
const openPdf = (entryId) => {
    if (entryId) window.open(route('goods-entries.pdf', entryId), '_blank');
};
</script>

<template>
    <Head title="Pedidos y Entradas de Mercadería — Farmacia Solei" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-boxes text-violet-400 text-xl"></i>
                    <h1 class="text-white font-semibold text-lg">Pedidos & Entradas de Mercadería</h1>
                </div>

                <button
                    @click="openCreateModal"
                    class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white font-semibold text-sm shadow-lg shadow-violet-900/30 transition-all flex items-center gap-2"
                >
                    <i class="fas fa-plus"></i>
                    <span>Nuevo Pedido</span>
                </button>
            </div>
        </template>

        <!-- Pestañas principales (Tabs) -->
        <div class="flex border-b border-white/10 mb-6 gap-2">
            <button
                @click="activeTab = 'orders'"
                class="px-5 py-3 font-semibold text-sm border-b-2 transition-all flex items-center gap-2"
                :class="activeTab === 'orders' ? 'border-violet-500 text-white bg-white/5 rounded-t-xl' : 'border-transparent text-gray-400 hover:text-white'"
            >
                <i class="fas fa-shopping-cart"></i>
                Pedidos de Resurtido
                <span class="px-2 py-0.5 rounded-full text-xs bg-violet-600/30 text-violet-300 font-bold">
                    {{ orders.total }}
                </span>
            </button>

            <button
                @click="activeTab = 'entries'"
                class="px-5 py-3 font-semibold text-sm border-b-2 transition-all flex items-center gap-2"
                :class="activeTab === 'entries' ? 'border-emerald-500 text-emerald-400 bg-white/5 rounded-t-xl' : 'border-transparent text-gray-400 hover:text-white'"
            >
                <i class="fas fa-truck-loading"></i>
                Entradas de Mercadería Aplicadas
                <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-600/30 text-emerald-300 font-bold">
                    {{ goodsEntries.total }}
                </span>
            </button>
        </div>

        <!-- ══ TAB 1: PEDIDOS DE RESURTIDO ════════════════════════════════════ -->
        <div v-if="activeTab === 'orders'">
            <!-- Filtros -->
            <div class="mb-6 flex gap-3">
                <select
                    v-model="status"
                    class="bg-gray-900 border border-white/10 text-gray-300 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-violet-500 transition-colors"
                >
                    <option value="all">Todos los estados</option>
                    <option value="pending">Pendientes</option>
                    <option value="applied">Aplicados</option>
                </select>
            </div>

            <!-- Tabla de Pedidos -->
            <div class="bg-gray-900 rounded-2xl border border-white/5 overflow-hidden shadow-xl">
                <div class="overflow-x-auto min-h-[300px]">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-white/5 bg-gray-950/40">
                                <th class="px-6 py-4 whitespace-nowrap"># Pedido</th>
                                <th class="px-4 py-4 whitespace-nowrap">Proveedor</th>
                                <th class="px-4 py-4 text-center whitespace-nowrap">Items</th>
                                <th class="px-4 py-4 whitespace-nowrap">Fecha</th>
                                <th class="px-4 py-4 whitespace-nowrap">Usuario Autoriza</th>
                                <th class="px-4 py-4 text-center whitespace-nowrap">Estado</th>
                                <th class="px-6 py-4 text-right whitespace-nowrap">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-gray-300">
                            <tr v-for="(o, idx) in orders.data" :key="o.id" class="hover:bg-white/2 transition-colors">
                                <td class="px-6 py-4 font-mono text-xs text-violet-400 font-bold whitespace-nowrap">
                                    #{{ String(o.id).padStart(6, '0') }}
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-white font-semibold text-sm">{{ o.supplier?.name || 'Proveedor General' }}</p>
                                    <span v-if="o.branch" class="inline-flex items-center gap-1 text-[11px] text-violet-300 font-medium">
                                        <i class="fas fa-store text-[10px]"></i> {{ o.branch }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gray-800/90 text-gray-200 text-xs font-medium border border-white/10 whitespace-nowrap shadow-sm">
                                        <span class="font-bold text-violet-400 font-mono">{{ o.items?.length || 0 }}</span>
                                        <span class="text-gray-300">{{ (o.items?.length === 1) ? 'producto' : 'productos' }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-xs text-gray-400 whitespace-nowrap">
                                    {{ o.order_date ? (new Date(o.order_date)).toLocaleString('es-NI') : '—' }}
                                </td>
                                <td class="px-4 py-4 text-xs whitespace-nowrap">
                                    <div class="text-violet-300 font-semibold" title="Autorizó el pedido">
                                        {{ o.admin?.username || o.admin_name || 'System' }}
                                    </div>
                                    <div v-if="o.goods_entry?.creator || o.applied_by" class="text-[11px] text-emerald-400 font-medium mt-0.5" title="Autorizó la recepción">
                                        <i class="fas fa-truck-loading text-[10px] mr-1"></i>Recibió: {{ o.goods_entry?.creator?.username || o.applied_by?.username }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border"
                                        :class="statusBadges[o.status]?.class">
                                        {{ statusBadges[o.status]?.label || o.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="relative inline-block text-left action-menu-container">
                                        <!-- Botón Único Acción -->
                                        <button
                                            type="button"
                                            @click.stop="toggleActionMenu(o.id)"
                                            class="px-3.5 py-1.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-200 hover:text-white font-semibold text-xs border border-white/10 transition-all inline-flex items-center gap-2 cursor-pointer shadow-sm active:scale-95"
                                            :class="{ 'bg-gray-700 text-white ring-2 ring-violet-500/50': openDropdownId === o.id }"
                                            title="Ver opciones de acción"
                                        >
                                            <span>Acción</span>
                                            <i class="fas fa-chevron-down text-[9px] text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': openDropdownId === o.id }"></i>
                                        </button>

                                        <!-- Menú Desplegable -->
                                        <Transition
                                            enter-active-class="transition duration-150 ease-out"
                                            enter-from-class="transform scale-95 opacity-0"
                                            enter-to-class="transform scale-100 opacity-100"
                                            leave-active-class="transition duration-100 ease-in"
                                            leave-from-class="transform scale-100 opacity-100"
                                            leave-to-class="transform scale-95 opacity-0"
                                        >
                                            <div
                                                v-if="openDropdownId === o.id"
                                                class="absolute right-0 w-48 rounded-xl bg-gray-900/95 border border-white/10 shadow-2xl p-1.5 z-50 flex flex-col gap-1 backdrop-blur-md text-left"
                                                :class="(idx >= orders.data.length - 2 && orders.data.length > 2) ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                            >
                                                <!-- Botón Ir a Entrada (si pending) -->
                                                <Link
                                                    v-if="o.status === 'pending'"
                                                    :href="route('product-orders.goods-entry', o.id)"
                                                    class="px-3 py-2 rounded-lg text-xs font-semibold text-emerald-300 hover:text-white hover:bg-emerald-600/20 flex items-center gap-2.5 transition-colors cursor-pointer"
                                                    @click="closeActionMenu"
                                                >
                                                    <i class="fas fa-truck-loading text-emerald-400 text-xs w-4 text-center"></i>
                                                    <span>Entrada Mercadería</span>
                                                </Link>

                                                <!-- Botón Detalle Modal -->
                                                <button
                                                    type="button"
                                                    class="px-3 py-2 rounded-lg text-xs font-semibold text-gray-300 hover:text-white hover:bg-white/10 flex items-center gap-2.5 transition-colors cursor-pointer w-full text-left"
                                                    @click="openDetailModal(o); closeActionMenu();"
                                                >
                                                    <i class="fas fa-eye text-violet-400 text-xs w-4 text-center"></i>
                                                    <span>Ver Detalle</span>
                                                </button>

                                                <!-- Botón Boleta PDF -->
                                                <button
                                                    type="button"
                                                    class="px-3 py-2 rounded-lg text-xs font-semibold text-rose-300 hover:text-white hover:bg-rose-600/20 flex items-center gap-2.5 transition-colors cursor-pointer w-full text-left"
                                                    @click="openOrderPdf(o.id); closeActionMenu();"
                                                >
                                                    <i class="fas fa-file-pdf text-rose-400 text-xs w-4 text-center"></i>
                                                    <span>Boleta Pedido PDF</span>
                                                </button>

                                                <!-- Botón Boleta Entrada PDF (si ya fue recibido) -->
                                                <button
                                                    v-if="o.goods_entry || o.status === 'received'"
                                                    type="button"
                                                    class="px-3 py-2 rounded-lg text-xs font-semibold text-emerald-300 hover:text-white hover:bg-emerald-600/20 flex items-center gap-2.5 transition-colors cursor-pointer w-full text-left"
                                                    @click="openPdf(o.goods_entry?.id); closeActionMenu();"
                                                >
                                                    <i class="fas fa-file-invoice text-emerald-400 text-xs w-4 text-center"></i>
                                                    <span>Boleta Entrada PDF</span>
                                                </button>
                                            </div>
                                        </Transition>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="orders.data.length === 0">
                                <td colspan="7" class="text-center py-12 text-gray-500 text-sm">
                                    No se encontraron pedidos de resurtido registrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="orders.links?.length > 3" class="flex items-center justify-between px-6 py-4 border-t border-white/5 bg-gray-950/20">
                    <span class="text-xs text-gray-500">Mostrando {{ orders.from }} - {{ orders.to }} de {{ orders.total }}</span>
                    <div class="flex gap-1">
                        <template v-for="(link, i) in orders.links" :key="i">
                            <Link v-if="link.url" :href="link.url" v-html="link.label"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                :class="link.active ? 'bg-violet-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-white'" />
                            <span v-else v-html="link.label" class="px-3 py-1.5 rounded-lg text-xs text-gray-700"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ TAB 2: ENTRADAS DE MERCADERÍA APLICADAS ══════════════════════ -->
        <div v-if="activeTab === 'entries'">
            <div class="bg-gray-900 rounded-2xl border border-white/5 overflow-hidden shadow-xl">
                <div class="overflow-x-auto min-h-[300px]">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-white/5 bg-gray-950/40">
                                <th class="px-6 py-4 whitespace-nowrap"># Entrada</th>
                                <th class="px-4 py-4 whitespace-nowrap"># Pedido</th>
                                <th class="px-4 py-4 whitespace-nowrap">Proveedor</th>
                                <th class="px-4 py-4 whitespace-nowrap">Factura Ref.</th>
                                <th class="px-4 py-4 whitespace-nowrap">Total Factura</th>
                                <th class="px-4 py-4 whitespace-nowrap">Total Sistema</th>
                                <th class="px-4 py-4 whitespace-nowrap">Fecha</th>
                                <th class="px-6 py-4 text-right whitespace-nowrap">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-gray-300">
                            <tr v-for="(e, idx) in goodsEntries.data" :key="e.id" class="hover:bg-white/2 transition-colors">
                                <td class="px-6 py-4 font-mono text-xs text-emerald-400 font-bold whitespace-nowrap">
                                    #EN-{{ String(e.id).padStart(6, '0') }}
                                </td>
                                <td class="px-4 py-4 font-mono text-xs text-violet-400 whitespace-nowrap">
                                    #{{ String(e.order_id).padStart(6, '0') }}
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-white font-semibold text-sm">{{ e.supplier?.name || 'Proveedor' }}</p>
                                    <span v-if="e.order?.branch || e.branch" class="inline-flex items-center gap-1 text-[11px] text-emerald-300 font-medium">
                                        <i class="fas fa-store text-[10px]"></i> {{ e.order?.branch || e.branch }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-xs font-mono text-gray-400 whitespace-nowrap">
                                    {{ e.invoice_reference || '—' }}
                                </td>
                                <td class="px-4 py-4 text-xs font-mono text-gray-300 whitespace-nowrap">
                                    {{ fmtMoney((parseFloat(e.invoice_subtotal) || 0) + (parseFloat(e.invoice_tax) || 0)) }}
                                </td>
                                <td class="px-4 py-4 text-xs font-mono text-emerald-400 font-bold whitespace-nowrap">
                                    {{ fmtMoney(e.system_total) }}
                                </td>
                                <td class="px-4 py-4 text-xs text-gray-400 whitespace-nowrap">
                                    {{ e.created_at ? (new Date(e.created_at)).toLocaleString('es-NI') : '—' }}
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="relative inline-block text-left action-menu-container">
                                        <!-- Botón Único Acción -->
                                        <button
                                            type="button"
                                            @click.stop="toggleActionMenu('entry-' + e.id)"
                                            class="px-3.5 py-1.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-200 hover:text-white font-semibold text-xs border border-white/10 transition-all inline-flex items-center gap-2 cursor-pointer shadow-sm active:scale-95"
                                            :class="{ 'bg-gray-700 text-white ring-2 ring-emerald-500/50': openDropdownId === 'entry-' + e.id }"
                                            title="Ver opciones de acción"
                                        >
                                            <span>Acción</span>
                                            <i class="fas fa-chevron-down text-[9px] text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': openDropdownId === 'entry-' + e.id }"></i>
                                        </button>

                                        <!-- Menú Desplegable -->
                                        <Transition
                                            enter-active-class="transition duration-150 ease-out"
                                            enter-from-class="transform scale-95 opacity-0"
                                            enter-to-class="transform scale-100 opacity-100"
                                            leave-active-class="transition duration-100 ease-in"
                                            leave-from-class="transform scale-100 opacity-100"
                                            leave-to-class="transform scale-95 opacity-0"
                                        >
                                            <div
                                                v-if="openDropdownId === 'entry-' + e.id"
                                                class="absolute right-0 w-44 rounded-xl bg-gray-900/95 border border-white/10 shadow-2xl p-1.5 z-50 flex flex-col gap-1 backdrop-blur-md text-left"
                                                :class="(idx >= goodsEntries.data.length - 2 && goodsEntries.data.length > 2) ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                            >
                                                <button
                                                    type="button"
                                                    class="px-3 py-2 rounded-lg text-xs font-semibold text-emerald-300 hover:text-white hover:bg-emerald-600/20 flex items-center gap-2.5 transition-colors cursor-pointer w-full text-left"
                                                    @click="openEntryModal(e); closeActionMenu();"
                                                >
                                                    <i class="fas fa-file-invoice text-emerald-400 text-xs w-4 text-center"></i>
                                                    <span>Ver Boleta</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="px-3 py-2 rounded-lg text-xs font-semibold text-rose-300 hover:text-white hover:bg-rose-600/20 flex items-center gap-2.5 transition-colors cursor-pointer w-full text-left"
                                                    @click="openPdf(e.id); closeActionMenu();"
                                                >
                                                    <i class="fas fa-file-pdf text-rose-400 text-xs w-4 text-center"></i>
                                                    <span>Imprimir PDF</span>
                                                </button>
                                            </div>
                                        </Transition>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="goodsEntries.data.length === 0">
                                <td colspan="8" class="text-center py-12 text-gray-500 text-sm">
                                    No hay entradas de mercadería registradas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="goodsEntries.links?.length > 3" class="flex items-center justify-between px-6 py-4 border-t border-white/5 bg-gray-950/20">
                    <span class="text-xs text-gray-500">Mostrando {{ goodsEntries.from }} - {{ goodsEntries.to }} de {{ goodsEntries.total }}</span>
                    <div class="flex gap-1">
                        <template v-for="(link, i) in goodsEntries.links" :key="i">
                            <Link v-if="link.url" :href="link.url" v-html="link.label"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                :class="link.active ? 'bg-emerald-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-white'" />
                            <span v-else v-html="link.label" class="px-3 py-1.5 rounded-lg text-xs text-gray-700"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ MODAL 1: NUEVO PEDIDO ═════════════════════════════════════════ -->
        <Transition name="fade">
        <div v-if="showCreateModal" @click.self="closeCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm overflow-y-auto">
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-2xl my-8 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/40">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <i class="fas fa-cart-plus text-violet-400"></i>
                        Nuevo Pedido de Resurtido
                    </h3>
                    <button @click="closeCreateModal" title="Cerrar modal (X)" class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-all">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <form @submit.prevent="startOrderAuthorization" class="p-6 space-y-4">
                    <!-- Errores de Validación Globales -->
                    <div v-if="Object.keys(form.errors).length > 0" class="p-3.5 rounded-xl bg-red-500/15 border border-red-500/30 text-red-400 text-xs space-y-1">
                        <div class="font-bold flex items-center gap-1.5">
                            <i class="fas fa-exclamation-circle"></i> Por favor verifique los siguientes campos:
                        </div>
                        <ul class="list-disc list-inside space-y-0.5">
                            <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                        </ul>
                    </div>

                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Proveedor *</label>
                        <select v-model="form.supplier_id" required class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors">
                            <option value="" disabled>Selecciona un proveedor obligatorio...</option>
                            <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>

                    <div v-if="!page.props.auth?.user?.branch && branches?.length">
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">
                            Sucursal de Destino *
                        </label>
                        <select v-model="form.branch" required class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors">
                            <option value="" disabled>Selecciona la sucursal de destino...</option>
                            <option v-for="b in branches" :key="b" :value="b">{{ b }}</option>
                        </select>
                        <p class="text-[11px] text-gray-500 mt-1">Como Superadmin con acceso global, selecciona a qué sucursal ingresará este pedido de mercadería.</p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-gray-400 text-xs font-medium uppercase tracking-wide">Productos del Pedido *</label>
                            <button type="button" @click="addItem" class="text-xs text-violet-400 hover:text-violet-300 font-semibold flex items-center gap-1">
                                <i class="fas fa-plus"></i> Agregar Fila
                            </button>
                        </div>

                        <div v-if="!form.supplier_id" class="text-center py-6 bg-gray-800/40 rounded-xl border border-dashed border-white/10 text-gray-500 text-xs">
                            Selecciona primero un proveedor para habilitar la lista de productos
                        </div>

                        <div v-else class="space-y-2 max-h-60 overflow-y-auto pr-1">
                            <div v-for="(item, idx) in form.items" :key="idx" class="flex gap-2 items-center">
                                <select v-model="item.product_id" @change="onProductSelect(idx)" required class="flex-1 bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-3 py-2 text-white text-xs outline-none">
                                    <option value="" disabled>Selecciona producto...</option>
                                    <option v-for="p in filteredProducts" :key="p.id" :value="p.id">
                                        {{ p.name }} (Stock: {{ p.stock }})
                                    </option>
                                </select>
                                <input v-model.number="item.quantity" type="number" min="1" required placeholder="Cant." class="w-20 bg-gray-800 border border-white/10 rounded-xl px-3 py-2 text-white text-xs text-center outline-none" />
                                <input
                                    :value="item.price"
                                    @input="item.price = $event.target.value"
                                    @change="onPriceChange(idx, $event.target.value)"
                                    type="text"
                                    inputmode="decimal"
                                    placeholder="Costo C$"
                                    class="w-24 bg-gray-800 border border-white/10 rounded-xl px-3 py-2 text-white text-xs text-right outline-none"
                                />
                                <button type="button" @click="removeItem(idx)" :disabled="form.items.length === 1" class="w-8 h-8 rounded-lg bg-gray-800 hover:bg-red-600/30 text-gray-400 hover:text-red-400 flex items-center justify-center flex-shrink-0 disabled:opacity-30">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Total Estimado del Modal -->
                    <div class="flex justify-between items-center px-4 py-3 rounded-xl bg-gray-800/80 border border-white/5">
                        <span class="text-xs text-gray-400 font-medium">Total Estimado Pedido:</span>
                        <span class="text-white font-extrabold text-sm font-mono">
                            {{ fmtMoney(form.items.reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(String(it.price).replace(',', '.')) || 0)), 0)) }}
                        </span>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-white/5">
                        <button type="button" @click="closeCreateModal" class="px-5 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm transition-colors">Cancelar</button>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white font-bold text-sm shadow-lg shadow-violet-900/30 flex items-center gap-2 transition-all">
                            <i class="fas fa-shield-alt"></i>
                            Continuar y Autorizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </Transition>

        <!-- ══ MODAL REUTILIZABLE DE AUTORIZACIÓN ═════════════════════════════ -->
        <AuthModal
            :show="showAuthModal"
            title="Autorizar Creación de Pedido"
            subtitle="Ingrese credenciales autorizadas (Usuario y Contraseña) para generar la orden oficial."
            actionLabel="Autorizar y Crear Pedido"
            :processing="form.processing"
            :errorMessage="authError"
            :initialUsername="page.props.auth?.user?.username || ''"
            @close="showAuthModal = false"
            @confirm="confirmOrderCreation"
        />

        <!-- ══ MODAL 2: BOLETA DE ENTRADA APLICADA (FORMATO CRYSTAL REPORTS) ══ -->
        <Transition name="fade">
        <div v-if="showEntryModal && selectedEntry" @click.self="closeEntryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm overflow-y-auto">
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-4xl my-6 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/40 print:hidden">
                    <h3 class="text-white font-semibold flex items-center gap-2 text-sm">
                        <i class="fas fa-file-contract text-emerald-400"></i>
                        Boleta de Entrada de Mercadería — Formato Crystal Reports
                    </h3>
                    <div class="flex items-center gap-2">
                        <button @click="openPdf(selectedEntry.id)" class="px-3.5 py-2 rounded-xl bg-violet-600 hover:bg-violet-500 text-white font-bold text-xs shadow-lg transition-all flex items-center gap-1.5">
                            <i class="fas fa-file-pdf text-red-400"></i> PDF Dompdf (Crystal Reports)
                        </button>
                        <button @click="printEntry" class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg transition-all flex items-center gap-1.5">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                        <button @click="closeEntryModal" class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-all ml-2">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>
                </div>

                <!-- Contenido Imprimible de Entrada -->
                <div class="p-8 bg-white text-black font-sans print:p-0">
                    <div class="flex justify-between items-start border-b-2 border-black pb-3 mb-4">
                        <div>
                            <h2 class="text-xl font-black tracking-tight text-gray-900">FARMACIA SOLEI</h2>
                            <p class="text-xs text-gray-600">Entrada de Mercadería — Comprobante Oficial</p>
                            <p class="text-xs font-bold text-gray-800">SUCURSAL PRINCIPAL</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-black text-gray-900">ENTRADA: #EN-{{ String(selectedEntry.id).padStart(6, '0') }}</p>
                            <p class="text-xs text-gray-600">Pedido Origen: #{{ String(selectedEntry.order_id).padStart(6, '0') }}</p>
                            <p class="text-xs text-gray-600">Fecha: {{ selectedEntry.created_at ? (new Date(selectedEntry.created_at)).toLocaleString('es-NI') : '—' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4 text-xs bg-gray-50 p-3 border border-gray-200">
                        <div>
                            <p><span class="font-bold">Proveedor:</span> {{ selectedEntry.supplier?.name || 'Proveedor' }}</p>
                            <p><span class="font-bold">Factura Proveedor:</span> {{ selectedEntry.invoice_reference || 'N/A' }}</p>
                        </div>
                        <div>
                            <p><span class="font-bold">Usuario Autoriza:</span> {{ selectedEntry.creator?.username || 'Usuario' }}</p>
                            <p><span class="font-bold">Total Sistema:</span> C$ {{ fmtMoney(selectedEntry.system_total) }}</p>
                        </div>
                    </div>

                    <table class="w-full text-left text-xs border-collapse border border-black mb-4">
                        <thead>
                            <tr class="bg-gray-100 border-b border-black">
                                <th class="border border-black p-1.5 text-center">#</th>
                                <th class="border border-black p-1.5">Producto</th>
                                <th class="border border-black p-1.5 text-center">Cant. Recibida</th>
                                <th class="border border-black p-1.5 text-right">Costo Unit.</th>
                                <th class="border border-black p-1.5 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, idx) in selectedEntry.items" :key="item.id">
                                <td class="border border-black p-1.5 text-center">{{ idx + 1 }}</td>
                                <td class="border border-black p-1.5 font-bold">{{ item.product?.name || 'Producto #' + item.product_id }}</td>
                                <td class="border border-black p-1.5 text-center font-bold">{{ item.quantity_received }}</td>
                                <td class="border border-black p-1.5 text-right">C$ {{ (parseFloat(item.cost_unit) || 0).toFixed(2) }}</td>
                                <td class="border border-black p-1.5 text-right font-bold">C$ {{ (parseFloat(item.cost_total) || ((item.quantity_received || 0) * (item.cost_unit || 0))).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Firma Única de Autorización -->
                    <div class="text-center text-xs mt-10">
                        <div class="border-t border-black pt-1 w-64 mx-auto font-bold">Usuario Autoriza</div>
                        <div class="text-gray-700 mt-0.5">{{ selectedEntry.creator?.username || 'Administración' }}</div>
                    </div>
                </div>
            </div>
        </div>
        </Transition>

        <!-- ══ MODAL 3: VER DETALLE DE PEDIDO DE RESURTIDO ════════════════════ -->
        <Transition name="fade">
        <div v-if="showDetailModal && selectedOrder" @click.self="closeDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm overflow-y-auto">
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-xl my-6 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/40">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <i class="fas fa-shopping-cart text-violet-400"></i>
                        Detalle del Pedido de Resurtido #{{ String(selectedOrder.id).padStart(6, '0') }}
                    </h3>
                    <button @click="closeDetailModal" title="Cerrar modal (X)" class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-all">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Resumen del pedido -->
                    <div class="grid grid-cols-2 gap-3 bg-gray-800/60 rounded-xl p-4 border border-white/5 text-xs text-gray-300">
                        <div>
                            <p class="text-gray-500">Proveedor:</p>
                            <p class="text-white font-bold text-sm">{{ selectedOrder.supplier?.name || 'Proveedor General' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Estado del Pedido:</p>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border mt-0.5"
                                :class="statusBadges[selectedOrder.status]?.class">
                                {{ statusBadges[selectedOrder.status]?.label || selectedOrder.status }}
                            </span>
                        </div>
                        <div>
                            <p class="text-gray-500">Fecha de Orden:</p>
                            <p class="text-gray-300 font-medium">{{ selectedOrder.order_date ? (new Date(selectedOrder.order_date)).toLocaleString('es-NI') : '—' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Usuario Autoriza Pedido:</p>
                            <p class="text-violet-300 font-medium">{{ selectedOrder.admin?.username || selectedOrder.admin_name || 'System' }}</p>
                        </div>
                        <div v-if="selectedOrder.goods_entry?.creator || selectedOrder.applied_by" class="col-span-2 pt-2 border-t border-white/5">
                            <p class="text-gray-500">Usuario Autoriza Recepción:</p>
                            <p class="text-emerald-400 font-medium flex items-center gap-1.5 mt-0.5">
                                <i class="fas fa-truck-loading text-xs"></i>
                                {{ selectedOrder.goods_entry?.creator?.full_name || selectedOrder.goods_entry?.creator?.username || selectedOrder.applied_by?.username }}
                            </p>
                        </div>
                    </div>

                    <!-- Tabla de productos solicitados -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <label class="text-gray-400 text-xs font-medium uppercase tracking-wide">Artículos Solicitados</label>
                            <span class="text-xs text-gray-500 font-semibold">{{ selectedOrder.items?.length || 0 }} productos</span>
                        </div>

                        <div class="bg-gray-800 rounded-xl divide-y divide-white/5 max-h-64 overflow-y-auto">
                            <div v-for="it in selectedOrder.items" :key="it.id" class="px-4 py-3 flex justify-between items-center text-xs">
                                <div>
                                    <p class="text-white font-semibold text-sm">{{ it.product?.name || 'Producto' }}</p>
                                    <p v-if="it.product?.barcode" class="text-gray-500 text-[10px] font-mono">{{ it.product.barcode }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-violet-300 font-bold text-sm">{{ it.quantity }} uds.</span>
                                    <p class="text-gray-400 text-xs">Precio U: {{ fmtMoney(it.price || it.product?.cost) }}</p>
                                    <p class="text-emerald-400 font-bold text-xs font-mono">Total: {{ fmtMoney((it.quantity || 0) * (it.price || it.product?.cost || 0)) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Estimado del Pedido -->
                    <div class="flex justify-between items-center px-4 py-3 rounded-xl bg-gray-800 border border-white/5">
                        <span class="text-xs text-gray-400 font-medium">Monto Total Estimado:</span>
                        <span class="text-white font-extrabold text-lg font-mono">
                            {{ fmtMoney(selectedOrder.total || selectedOrder.items?.reduce((acc, it) => acc + ((it.quantity || 0) * (it.price || it.product?.cost || 0)), 0)) }}
                        </span>
                    </div>

                    <!-- Acciones Footer -->
                    <div class="flex justify-between items-center pt-3 border-t border-white/5">
                        <div class="flex items-center gap-2">
                            <!-- Botón PDF Pedido -->
                            <button
                                type="button"
                                @click="openOrderPdf(selectedOrder.id)"
                                class="px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold transition-all inline-flex items-center gap-2 shadow-lg shadow-violet-900/30"
                            >
                                <i class="fas fa-file-pdf text-red-300"></i> Boleta Pedido PDF
                            </button>

                            <!-- Botón PDF Entrada si ya está recibido -->
                            <button
                                v-if="selectedOrder.goods_entry || selectedOrder.status === 'received'"
                                type="button"
                                @click="openPdf(selectedOrder.goods_entry?.id || selectedOrder.id)"
                                class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition-all inline-flex items-center gap-2 shadow-lg shadow-emerald-900/30"
                            >
                                <i class="fas fa-file-invoice text-emerald-200"></i> Boleta Entrada PDF
                            </button>

                            <Link
                                v-if="selectedOrder.status === 'pending'"
                                :href="route('product-orders.goods-entry', selectedOrder.id)"
                                class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold transition-all inline-flex items-center gap-2 shadow-lg shadow-emerald-900/30"
                            >
                                <i class="fas fa-truck-loading"></i> Ir a Entrada
                            </Link>
                        </div>

                        <button type="button" @click="closeDetailModal" class="px-5 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm font-semibold transition-colors">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        </Transition>
    </AdminLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
