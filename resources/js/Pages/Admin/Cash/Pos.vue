<script setup>
function formatDate(dateStr) {
    if (!dateStr) return '\u2014';
    return new Date(dateStr).toLocaleString('es-NI', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', hour12: true,
    });
}

import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const props = defineProps({
    openSession: Object,
});

// ─── State ─────────────────────────────────────────────────────────────────────
const searchQuery   = ref('');
const searchResults = ref([]);
const searching     = ref(false);
const noResults     = ref(false);
let   searchTimer   = null;
const searchInput   = ref(null);

const cart = ref({}); // { id: { id, name, price, qty, stock, image } }

const clientName    = ref('Consumidor Final');
const payMethod     = ref('efectivo');
const discountType  = ref('fixed'); // 'fixed' | 'percent'
const discountValue = ref(0);
const amountPaid    = ref('');
const processing    = ref(false);
const lastSale      = ref(null);

// ── Reimpresiones ──────────────────────────────────────────────────────────────
const showReprintModal  = ref(false);
const reprintTab        = ref('current'); // 'current' | 'history'
const reprintSales      = ref([]);
const reprintLoading    = ref(false);
const reprintSearch     = ref('');
const reprintDateFrom   = ref(new Date().toISOString().slice(0, 10));
const reprintDateTo     = ref(new Date().toISOString().slice(0, 10));
const reprintUserFilter = ref('');
const historySales      = ref([]);
const historyLoading    = ref(false);

// ─── Computeds ─────────────────────────────────────────────────────────────────
const cartItems = computed(() => Object.values(cart.value));
const isEmpty   = computed(() => cartItems.value.length === 0);

const subtotal = computed(() =>
    cartItems.value.reduce((acc, item) => acc + item.price * item.qty, 0)
);

const discountAmt = computed(() => {
    const raw = parseFloat(discountValue.value) || 0;
    if (raw <= 0) return 0;
    if (discountType.value === 'percent') {
        return subtotal.value * (Math.min(raw, 100) / 100);
    }
    return Math.min(raw, subtotal.value);
});

const total = computed(() => Math.max(0, subtotal.value - discountAmt.value));

const change = computed(() => {
    if (payMethod.value !== 'efectivo') return 0;
    const paid = parseFloat(amountPaid.value) || 0;
    return Math.max(0, paid - total.value);
});

const hasOpenSession = computed(() => {
    return !!(props.openSession && props.openSession.status === 'open');
});

const canProcess = computed(() => {
    if (!hasOpenSession.value) return false;
    if (isEmpty.value || processing.value) return false;
    if (payMethod.value === 'efectivo') {
        const paid = parseFloat(amountPaid.value) || 0;
        if (paid > 0 && paid < total.value - 0.001) return false;
    }
    return true;
});

const filteredReprintSales = computed(() => {
    const q = reprintSearch.value.toLowerCase();
    if (!q) return reprintSales.value;
    return reprintSales.value.filter(s =>
        String(s.id).includes(q) ||
        (s.cashier_name ?? '').toLowerCase().includes(q) ||
        (s.client_name ?? '').toLowerCase().includes(q)
    );
});

// ─── Search ────────────────────────────────────────────────────────────────────
function onSearchInput() {
    clearTimeout(searchTimer);
    noResults.value   = false;
    searchResults.value = [];
    const q = searchQuery.value.trim();
    if (q.length < 1) { searching.value = false; return; }
    searching.value = true;
    searchTimer = setTimeout(() => doSearch(q), 280);
}

async function doSearch(q) {
    try {
        const { data } = await axios.get(route('pos.search'), { params: { q } });
        searchResults.value = data;
        noResults.value     = data.length === 0;
    } catch {
        noResults.value = true;
    } finally {
        searching.value = false;
    }
}

function onSearchEnter() {
    clearTimeout(searchTimer);
    const q = searchQuery.value.trim();
    if (!q) return;
    doSearch(q).then(() => {
        if (searchResults.value.length === 1) {
            addToCart(searchResults.value[0]);
            clearSearch();
        }
    });
}

function clearSearch() {
    searchQuery.value    = '';
    searchResults.value  = [];
    noResults.value      = false;
    searching.value      = false;
    searchInput.value?.focus();
}

// ─── Cart ──────────────────────────────────────────────────────────────────────
function addToCart(product) {
    if (!hasOpenSession.value) {
        alert('⚠️ No hay una caja abierta. Debe abrir caja antes de registrar ventas.');
        return;
    }
    const id = product.id;
    if (cart.value[id]) {
        if (cart.value[id].qty >= product.stock) {
            alert(`⚠️ Solo hay ${product.stock} unidades disponibles de "${product.name}".`);
            return;
        }
        cart.value[id].qty++;
    } else {
        cart.value[id] = {
            id:    product.id,
            name:  product.name,
            price: parseFloat(product.price),
            qty:   1,
            stock: parseInt(product.stock),
            image: product.image,
        };
    }
}

function removeFromCart(id) {
    const c = { ...cart.value };
    delete c[id];
    cart.value = c;
}

function updateQty(id, delta) {
    const item = cart.value[id];
    if (!item) return;
    const newQty = item.qty + delta;
    if (newQty <= 0) { removeFromCart(id); return; }
    if (newQty > item.stock) {
        alert(`⚠️ Solo hay ${item.stock} unidades disponibles.`);
        return;
    }
    cart.value[id].qty = newQty;
}

function setQty(id, val) {
    const qty = parseInt(val) || 0;
    if (qty <= 0) { removeFromCart(id); return; }
    if (qty > cart.value[id].stock) {
        cart.value[id].qty = cart.value[id].stock;
    } else {
        cart.value[id].qty = qty;
    }
}

function clearCart() {
    cart.value    = {};
    amountPaid.value    = '';
    discountValue.value = 0;
    clientName.value    = 'Consumidor Final';
}

// ─── Process Sale ──────────────────────────────────────────────────────────────
async function processSale() {
    if (!hasOpenSession.value) {
        alert('⚠️ No hay una caja abierta en esta sucursal. Debe abrir caja antes de procesar una venta.');
        return;
    }
    if (!canProcess.value) return;

    const effectivePaid = payMethod.value === 'efectivo'
        ? (parseFloat(amountPaid.value) || total.value)
        : total.value;

    const confirmed = confirm(
        `¿Confirmar venta?\n\nSubtotal: C$ ${subtotal.value.toFixed(2)}` +
        (discountAmt.value > 0 ? `\nDescuento: -C$ ${discountAmt.value.toFixed(2)}` : '') +
        `\nTotal: C$ ${total.value.toFixed(2)}` +
        (payMethod.value === 'efectivo' ? `\nRecibido: C$ ${effectivePaid.toFixed(2)}\nCambio: C$ ${Math.max(0, effectivePaid - total.value).toFixed(2)}` : '')
    );
    if (!confirmed) return;

    processing.value = true;

    try {
        const { data } = await axios.post(route('pos.sale'), {
            items:          cartItems.value.map(i => ({ id: i.id, qty: i.qty, price: i.price })),
            pay_method:     payMethod.value,
            amount_paid:    effectivePaid,
            discount_type:  discountType.value,
            discount_value: parseFloat(discountValue.value) || 0,
            client_name:    clientName.value,
        });

        if (data.success) {
            lastSale.value = data;
            clearCart();
            clearSearch();
        } else {
            alert('❌ ' + (data.message || 'Error al procesar la venta.'));
        }
    } catch (err) {
        const msg = err.response?.data?.message || 'Error de conexión.';
        alert('❌ ' + msg);
    } finally {
        processing.value = false;
    }
}

function printReceipt(orderId) {
    window.open(route('pos.receipt', orderId), '_blank');
}

function newSale() {
    lastSale.value = null;
    clearCart();
    clearSearch();
}

// ─── Reimpresiones ─────────────────────────────────────────────────────────────
async function openReprint() {
    showReprintModal.value = true;
    await loadCurrentSales();
}

async function loadCurrentSales() {
    reprintLoading.value = true;
    reprintSales.value   = [];
    try {
        const sessionId = props.openSession?.id;
        const { data } = await axios.get(route('pos.salesHistory'), {
            params: sessionId ? { session_id: sessionId } : { date_from: reprintDateFrom.value, date_to: reprintDateTo.value }
        });
        reprintSales.value = data;
    } catch {
        reprintSales.value = [];
    } finally {
        reprintLoading.value = false;
    }
}

async function loadHistorySales() {
    historyLoading.value = true;
    historySales.value   = [];
    try {
        const { data } = await axios.get(route('pos.salesHistory'), {
            params: {
                date_from:   reprintDateFrom.value,
                date_to:     reprintDateTo.value,
                search_user: reprintUserFilter.value || undefined,
            }
        });
        historySales.value = data;
    } catch {
        historySales.value = [];
    } finally {
        historyLoading.value = false;
    }
}

// ─── Lifecycle ─────────────────────────────────────────────────────────────────
function handleKeydown(e) {
    if (e.key === 'Escape') {
        if (lastSale.value) newSale();
        if (showReprintModal.value) showReprintModal.value = false;
    }
}

onMounted(() => {
    searchInput.value?.focus();
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});

const fmtMoney = (n) => 'C$ ' + (parseFloat(n) || 0).toFixed(2);
const payLabels = { efectivo: '💵 Efectivo', tarjeta: '💳 Tarjeta', transferencia: '🏦 Transferencia' };
</script>

<template>
    <Head title="POS — Farmacia Solei" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <i class="fas fa-cash-register text-emerald-400"></i>
                <h1 class="text-white font-semibold text-lg">Punto de Venta</h1>
                <span v-if="hasOpenSession" class="inline-flex items-center gap-1.5 text-xs bg-emerald-500/15 text-emerald-400 px-2.5 py-1 rounded-full font-medium border border-emerald-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Caja abierta {{ openSession?.opener?.username ? `(${openSession.opener.username})` : "" }}
                </span>
                <span v-else class="inline-flex items-center gap-1.5 text-xs bg-red-500/15 text-red-400 px-2.5 py-1 rounded-full font-medium border border-red-500/20">
                    <i class="fas fa-lock text-xs"></i>
                    Sin sesión de caja
                </span>
            </div>
        </template>

        <!-- ── VENTA EXITOSA ────────────────────────────────────────────────── -->
        <Transition name="slide-up">
        <div v-if="lastSale" @click.self="newSale" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="relative bg-gray-900 border border-white/10 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden">
                <!-- Botón X para cerrar modal -->
                <button
                    @click="newSale"
                    title="Cerrar modal (X)"
                    class="absolute top-4 right-4 w-9 h-9 rounded-full bg-black/20 hover:bg-black/40 text-white/80 hover:text-white flex items-center justify-center transition-all z-10"
                >
                    <i class="fas fa-times text-base"></i>
                </button>

                <div class="bg-gradient-to-br from-emerald-600 to-teal-600 px-8 py-8 text-center">
                    <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check text-white text-4xl"></i>
                    </div>
                    <h2 class="text-white text-2xl font-bold">¡Venta procesada!</h2>
                    <p class="text-white/70 mt-1 text-sm">Recibo #{{ String(lastSale.order_id).padStart(6,'0') }}</p>
                </div>
                <div class="p-8 space-y-3">
                    <div class="flex justify-between text-gray-400 text-sm">
                        <span>Subtotal</span>
                        <span class="text-white">{{ fmtMoney(lastSale.subtotal) }}</span>
                    </div>
                    <div v-if="lastSale.discount > 0" class="flex justify-between text-sm">
                        <span class="text-red-400">Descuento</span>
                        <span class="text-red-400">-{{ fmtMoney(lastSale.discount) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-lg border-t border-white/10 pt-3">
                        <span class="text-white">Total</span>
                        <span class="text-emerald-400">{{ fmtMoney(lastSale.total) }}</span>
                    </div>
                    <div v-if="lastSale.change > 0" class="flex justify-between text-sm">
                        <span class="text-gray-400">Cambio</span>
                        <span class="text-cyan-400 font-semibold">{{ fmtMoney(lastSale.change) }}</span>
                    </div>
                </div>
                <div class="flex gap-3 px-8 pb-8">
                    <button @click="printReceipt(lastSale.order_id)"
                        class="flex-1 py-3 rounded-xl bg-gray-800 hover:bg-gray-700 text-white text-sm font-medium transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                    <button @click="newSale"
                        class="flex-1 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-plus"></i> Nueva venta
                    </button>
                </div>
            </div>
        </div>
        </Transition>

        <!-- ── BANNER CAJA CERRADA ─────────────────────────────────── -->
        <div v-if="!hasOpenSession" class="mb-4 p-4 bg-amber-500/10 border border-amber-500/30 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-lg shadow-amber-950/20">
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/30 text-amber-400 flex items-center justify-center shrink-0">
                    <i class="fas fa-lock text-lg"></i>
                </div>
                <div>
                    <h3 class="text-amber-300 font-bold text-sm">Caja Cerrada &mdash; Facturación Inhabilitada</h3>
                    <p class="text-gray-400 text-xs mt-0.5">Para poder registrar productos y facturar ventas en el POS, primero debe iniciar una sesión de caja.</p>
                </div>
            </div>
            <Link :href="route('cash.index')" class="px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold rounded-xl transition-all shadow-md flex items-center justify-center gap-2 shrink-0">
                <i class="fas fa-cash-register"></i>
                Ir a Abrir Caja
            </Link>
        </div>

        <!-- ── MAIN LAYOUT ─────────────────────────────────────────── -->
        <div class="flex gap-5 h-full min-h-0" :style="!hasOpenSession ? 'height: calc(100vh - 205px)' : 'height: calc(100vh - 130px)'">

            <!-- LEFT: Búsqueda de productos -->
            <div class="flex-1 flex flex-col min-w-0 gap-4">
                <!-- Search bar -->
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <i class="fas fa-barcode absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                        <input
                            ref="searchInput"
                            v-model="searchQuery"
                            @input="onSearchInput"
                            @keydown.enter.prevent="onSearchEnter"
                            @keydown.escape="clearSearch"
                            type="text"
                            placeholder="Buscar producto por nombre o código de barras..."
                            class="w-full pl-11 pr-4 py-3.5 bg-gray-900 border border-white/10 hover:border-white/20 focus:border-violet-500 focus:ring-1 focus:ring-violet-500/30 rounded-xl text-white placeholder-gray-600 text-sm outline-none transition-all"
                        />
                        <button v-if="searchQuery" @click="clearSearch"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-400 p-1">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>
                    <button @click="openReprint"
                        class="px-4 py-3.5 rounded-xl bg-gray-900 border border-white/10 hover:border-white/20 text-gray-400 hover:text-white transition-all text-sm flex items-center gap-2">
                        <i class="fas fa-print"></i>
                        <span class="hidden sm:inline">Reimpresiones</span>
                    </button>
                </div>

                <!-- Results area -->
                <div class="flex-1 bg-gray-900 rounded-2xl border border-white/5 overflow-hidden flex flex-col">
                    <!-- Loading -->
                    <div v-if="searching" class="flex-1 flex items-center justify-center text-gray-600">
                        <i class="fas fa-spinner fa-spin text-2xl mr-3"></i>
                        <span class="text-sm">Buscando...</span>
                    </div>

                    <!-- No results -->
                    <div v-else-if="noResults" class="flex-1 flex flex-col items-center justify-center text-gray-600">
                        <i class="fas fa-box-open text-4xl mb-3 text-gray-700"></i>
                        <p class="text-sm">No se encontraron productos para <strong class="text-gray-400">"{{ searchQuery }}"</strong></p>
                    </div>

                    <!-- Empty state -->
                    <div v-else-if="!searchResults.length" class="flex-1 flex flex-col items-center justify-center text-gray-700">
                        <i class="fas fa-search text-5xl mb-4 opacity-30"></i>
                        <p class="text-sm font-medium">Busca un producto para comenzar</p>
                        <p class="text-xs mt-1 text-gray-600">Por nombre, código de barras o ID</p>
                    </div>

                    <!-- Product list -->
                    <div v-else class="overflow-y-auto flex-1">
                        <div
                            v-for="product in searchResults"
                            :key="product.id"
                            @click="addToCart(product)"
                            class="flex items-center gap-4 px-5 py-3.5 hover:bg-white/3 active:bg-white/5 cursor-pointer transition-colors border-b border-white/5 last:border-0 group"
                        >
                            <!-- Image -->
                            <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-800 flex items-center justify-center flex-shrink-0">
                                <img v-if="product.image" :src="product.image" class="w-full h-full object-cover" :alt="product.name" @error="$event.target.style.display='none'" />
                                <i v-else class="fas fa-pills text-gray-600 text-sm"></i>
                            </div>
                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <p class="text-white text-sm font-medium truncate">{{ product.name }}</p>
                                <p v-if="product.sku" class="text-gray-600 text-xs font-mono">{{ product.sku }}</p>
                            </div>
                            <!-- Price -->
                            <div class="text-right flex-shrink-0">
                                <p class="text-emerald-400 font-bold text-sm">C$ {{ product.price.toFixed(2) }}</p>
                                <p class="text-xs" :class="product.stock <= 5 ? 'text-amber-500' : 'text-gray-600'">
                                    <i class="fas fa-box text-xs mr-1"></i>{{ product.stock }}
                                </p>
                            </div>
                            <!-- Add button -->
                            <button class="w-8 h-8 rounded-lg bg-violet-600/0 group-hover:bg-violet-600 flex items-center justify-center transition-all text-gray-600 group-hover:text-white flex-shrink-0">
                                <i class="fas fa-plus text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Cart -->
            <div class="w-80 xl:w-96 flex flex-col flex-shrink-0 gap-0 bg-gray-900 rounded-2xl border border-white/5 overflow-hidden">
                <!-- Cart header -->
                <div class="flex items-center justify-between px-5 py-4 border-b border-white/5">
                    <h2 class="text-white font-semibold text-sm flex items-center gap-2">
                        <i class="fas fa-shopping-cart text-violet-400"></i>
                        Carrito
                        <span v-if="cartItems.length" class="w-5 h-5 rounded-full bg-violet-600 text-white text-xs flex items-center justify-center font-bold">
                            {{ cartItems.length }}
                        </span>
                    </h2>
                    <button v-if="!isEmpty" @click="clearCart"
                        class="text-gray-600 hover:text-red-400 transition-colors text-xs flex items-center gap-1">
                        <i class="fas fa-trash"></i> Limpiar
                    </button>
                </div>

                <!-- Cart items -->
                <div class="flex-1 overflow-y-auto">
                    <!-- Empty cart -->
                    <div v-if="isEmpty" class="h-full flex flex-col items-center justify-center text-gray-700 p-8">
                        <i class="fas fa-shopping-cart text-5xl mb-4 opacity-20"></i>
                        <p class="text-sm text-center font-medium">El carrito está vacío</p>
                        <p class="text-xs text-center text-gray-600 mt-1">Agrega productos buscando a la izquierda</p>
                    </div>

                    <!-- Items list -->
                    <div v-else class="divide-y divide-white/5">
                        <div v-for="item in cartItems" :key="item.id" class="px-4 py-3 group hover:bg-white/2 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="flex-1 min-w-0">
                                    <p class="text-white text-xs font-semibold truncate">{{ item.name }}</p>
                                    <p class="text-gray-600 text-xs">C$ {{ item.price.toFixed(2) }} c/u</p>
                                </div>
                                <button @click="removeFromCart(item.id)"
                                    class="opacity-0 group-hover:opacity-100 text-gray-700 hover:text-red-400 transition-all text-xs p-1">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <!-- Qty controls -->
                                <div class="flex items-center gap-1">
                                    <button @click="updateQty(item.id, -1)"
                                        class="w-6 h-6 rounded-md bg-gray-800 hover:bg-gray-700 text-white text-xs flex items-center justify-center transition-colors">−</button>
                                    <input
                                        :value="item.qty"
                                        @change="setQty(item.id, $event.target.value)"
                                        type="number" min="1" :max="item.stock"
                                        class="w-10 h-6 bg-gray-800 rounded-md text-center text-white text-xs border border-white/10 focus:outline-none focus:border-violet-500"
                                    />
                                    <button @click="updateQty(item.id, 1)"
                                        class="w-6 h-6 rounded-md bg-gray-800 hover:bg-gray-700 text-white text-xs flex items-center justify-center transition-colors">+</button>
                                </div>
                                <p class="text-emerald-400 font-bold text-sm">C$ {{ (item.price * item.qty).toFixed(2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer: totals + payment -->
                <div v-if="!isEmpty" class="border-t border-white/5 p-4 space-y-3">
                    <!-- Subtotal row -->
                    <div class="flex justify-between text-sm text-gray-400">
                        <span>Subtotal</span>
                        <span class="text-white">{{ fmtMoney(subtotal) }}</span>
                    </div>

                    <!-- Discount -->
                    <div class="flex gap-2 items-center">
                        <input
                            v-model.number="discountValue"
                            type="number" min="0" :max="discountType === 'percent' ? 100 : undefined" step="0.01"
                            placeholder="Descuento"
                            class="flex-1 bg-gray-800 border border-white/10 focus:border-violet-500 rounded-lg px-3 py-2 text-white text-xs outline-none transition-colors"
                        />
                        <button @click="discountType = discountType === 'fixed' ? 'percent' : 'fixed'"
                            class="px-3 py-2 rounded-lg bg-gray-800 border border-white/10 text-xs font-mono transition-colors"
                            :class="discountType === 'percent' ? 'text-amber-400 border-amber-500/30' : 'text-gray-400'">
                            {{ discountType === 'fixed' ? 'C$' : '%' }}
                        </button>
                    </div>

                    <!-- Total -->
                    <div class="flex justify-between items-center pt-1">
                        <span class="text-white font-bold text-sm">TOTAL</span>
                        <span class="text-emerald-400 font-bold text-xl">{{ fmtMoney(total) }}</span>
                    </div>

                    <!-- Payment method -->
                    <div>
                        <label class="text-gray-600 text-xs mb-1 block">Método de pago</label>
                        <div class="flex gap-1.5">
                            <button v-for="(label, key) in payLabels" :key="key"
                                @click="payMethod = key"
                                class="flex-1 py-1.5 rounded-lg text-xs font-medium transition-all border"
                                :class="payMethod === key
                                    ? 'bg-violet-600/20 border-violet-500/50 text-violet-300'
                                    : 'bg-gray-800 border-white/5 text-gray-600 hover:text-gray-400'">
                                {{ label }}
                            </button>
                        </div>
                    </div>

                    <!-- Cash: amount paid / change -->
                    <div v-if="payMethod === 'efectivo'" class="flex gap-2">
                        <div class="flex-1">
                            <label class="text-gray-600 text-xs mb-1 block">Monto recibido</label>
                            <input
                                v-model="amountPaid"
                                type="number" min="0" step="0.01" :placeholder="total.toFixed(2)"
                                class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-lg px-3 py-2 text-white text-sm outline-none transition-colors"
                            />
                        </div>
                        <div class="flex-1">
                            <label class="text-gray-600 text-xs mb-1 block">Cambio</label>
                            <div class="bg-gray-800 rounded-lg px-3 py-2 border"
                                :class="change > 0 ? 'border-cyan-500/30 text-cyan-400' : 'border-white/5 text-gray-600'">
                                <span class="font-bold text-sm">{{ fmtMoney(change) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Client name -->
                    <div>
                        <label class="text-gray-600 text-xs mb-1 block">Cliente</label>
                        <input v-model="clientName" type="text" placeholder="Consumidor Final"
                            class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-lg px-3 py-2 text-white text-xs outline-none transition-colors" />
                    </div>

                    <!-- Process button -->
                    <button
                        @click="processSale"
                        :disabled="!canProcess"
                        class="w-full py-3.5 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2"
                        :class="canProcess
                            ? 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white shadow-lg shadow-emerald-900/30 active:scale-95'
                            : 'bg-gray-800 text-gray-600 cursor-not-allowed'"
                    >
                        <i v-if="processing" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-check-circle"></i>
                        {{ processing ? 'Procesando...' : 'Procesar Venta' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ MODAL: Reimpresiones ══════════════════════════════════════════ -->
        <Transition name="fade">
        <div v-if="showReprintModal" @click.self="showReprintModal = false" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-3xl max-h-[85vh] flex flex-col overflow-hidden">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <i class="fas fa-print text-violet-400"></i>
                        Reimpresiones
                    </h3>
                    <button
                        @click="showReprintModal = false"
                        title="Cerrar (X)"
                        class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-all"
                    >
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <!-- Tabs -->
                <div class="flex border-b border-white/5 px-6">
                    <button @click="reprintTab = 'current'; loadCurrentSales()"
                        class="py-3 px-4 text-sm font-medium border-b-2 transition-colors"
                        :class="reprintTab === 'current' ? 'border-violet-500 text-violet-300' : 'border-transparent text-gray-600 hover:text-gray-400'">
                        Sesión actual
                    </button>
                    <button @click="reprintTab = 'history'"
                        class="py-3 px-4 text-sm font-medium border-b-2 transition-colors"
                        :class="reprintTab === 'history' ? 'border-violet-500 text-violet-300' : 'border-transparent text-gray-600 hover:text-gray-400'">
                        Historial
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6">
                    <!-- TAB: current -->
                    <template v-if="reprintTab === 'current'">
                        <div class="mb-4">
                            <input v-model="reprintSearch" type="text" placeholder="🔍 Buscar por # recibo o cajero..."
                                class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors" />
                        </div>
                        <div v-if="reprintLoading" class="text-center py-8 text-gray-600">
                            <i class="fas fa-spinner fa-spin text-2xl"></i>
                        </div>
                        <div v-else-if="!filteredReprintSales.length" class="text-center py-8 text-gray-700">
                            <i class="fas fa-receipt text-3xl mb-2 opacity-30"></i>
                            <p class="text-sm">Sin ventas en esta sesión</p>
                        </div>
                        <table v-else class="w-full text-sm">
                            <thead>
                                <tr class="text-gray-600 text-xs border-b border-white/5">
                                    <th class="text-left pb-2"># Recibo</th>
                                    <th class="text-left pb-2">Fecha/Hora</th>
                                    <th class="text-left pb-2">Cajero</th>
                                    <th class="text-right pb-2">Total</th>
                                    <th class="text-left pb-2 pl-4">Método</th>
                                    <th class="text-center pb-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="s in filteredReprintSales" :key="s.id" class="hover:bg-white/2 transition-colors">
                                    <td class="py-2.5 text-gray-400 font-mono text-xs">#{{ String(s.id).padStart(6,'0') }}</td>
                                    <td class="py-2.5 text-gray-500 text-xs">{{ formatDate(s?.created_at) }}</td>
                                    <td class="py-2.5 text-white text-xs">{{ s.cashier_name }}</td>
                                    <td class="py-2.5 text-emerald-400 font-bold text-right">{{ fmtMoney(s.total) }}</td>
                                    <td class="py-2.5 text-gray-500 text-xs pl-4">{{ payLabels[s.pay_method] || s.pay_method }}</td>
                                    <td class="py-2.5 text-center">
                                        <button @click="printReceipt(s.id)"
                                            class="px-3 py-1 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white text-xs transition-colors">
                                            <i class="fas fa-print"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </template>

                    <!-- TAB: history -->
                    <template v-else>
                        <div class="flex gap-3 mb-4 flex-wrap">
                            <div>
                                <label class="text-gray-600 text-xs block mb-1">Desde</label>
                                <input v-model="reprintDateFrom" type="date"
                                    class="bg-gray-800 border border-white/10 rounded-lg px-3 py-2 text-white text-sm outline-none" />
                            </div>
                            <div>
                                <label class="text-gray-600 text-xs block mb-1">Hasta</label>
                                <input v-model="reprintDateTo" type="date"
                                    class="bg-gray-800 border border-white/10 rounded-lg px-3 py-2 text-white text-sm outline-none" />
                            </div>
                            <div>
                                <label class="text-gray-600 text-xs block mb-1">Cajero</label>
                                <input v-model="reprintUserFilter" type="text" placeholder="Todos"
                                    class="bg-gray-800 border border-white/10 rounded-lg px-3 py-2 text-white text-sm outline-none w-36" />
                            </div>
                            <div class="flex items-end">
                                <button @click="loadHistorySales"
                                    class="px-4 py-2 rounded-lg bg-violet-600 hover:bg-violet-500 text-white text-sm font-medium transition-colors flex items-center gap-2">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                        <div v-if="historyLoading" class="text-center py-8 text-gray-600">
                            <i class="fas fa-spinner fa-spin text-2xl"></i>
                        </div>
                        <div v-else-if="!historySales.length" class="text-center py-8 text-gray-700">
                            <i class="fas fa-search text-3xl mb-2 opacity-30"></i>
                            <p class="text-sm">Sin ventas con esos filtros</p>
                        </div>
                        <table v-else class="w-full text-sm">
                            <thead>
                                <tr class="text-gray-600 text-xs border-b border-white/5">
                                    <th class="text-left pb-2"># Recibo</th>
                                    <th class="text-left pb-2">Fecha/Hora</th>
                                    <th class="text-left pb-2">Cajero</th>
                                    <th class="text-right pb-2">Total</th>
                                    <th class="text-left pb-2 pl-4">Método</th>
                                    <th class="text-center pb-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="s in historySales" :key="s.id" class="hover:bg-white/2 transition-colors">
                                    <td class="py-2.5 text-gray-400 font-mono text-xs">#{{ String(s.id).padStart(6,'0') }}</td>
                                    <td class="py-2.5 text-gray-500 text-xs">{{ formatDate(s?.created_at) }}</td>
                                    <td class="py-2.5 text-white text-xs">{{ s.cashier_name }}</td>
                                    <td class="py-2.5 text-emerald-400 font-bold text-right">{{ fmtMoney(s.total) }}</td>
                                    <td class="py-2.5 text-gray-500 text-xs pl-4">{{ payLabels[s.pay_method] || s.pay_method }}</td>
                                    <td class="py-2.5 text-center">
                                        <button @click="printReceipt(s.id)"
                                            class="px-3 py-1 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white text-xs transition-colors">
                                            <i class="fas fa-print"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </template>
                </div>
            </div>
        </div>
        </Transition>
    </AdminLayout>
</template>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active { transition: all .3s cubic-bezier(.16,1,.3,1); }
.slide-up-enter-from  { opacity: 0; transform: scale(.95) translateY(20px); }
.slide-up-leave-to    { opacity: 0; transform: scale(.95) translateY(20px); }

.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
