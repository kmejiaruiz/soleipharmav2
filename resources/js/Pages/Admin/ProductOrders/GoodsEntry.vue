<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AuthModal from '@/Components/AuthModal.vue';

const page = usePage();
const props = defineProps({
    order:   Object,
    ivaRate: { type: Number, default: 0.15 },
});

const showConfirmModal = ref(false);
const showAuthModal    = ref(false);
const authError        = ref('');

// ── Estado del flujo: Calcular -> Facturas -> Aplicar ──
const isCalculated     = ref(false);
const isInvoiceSaved   = ref(false);
const showInvoiceModal = ref(false);

function onItemQtyChange() {
    isCalculated.value   = false;
    isInvoiceSaved.value = false;
}

function calculateEntry() {
    if (shortageItemsMissingReason.value) {
        alert('Por favor, selecciona la justificación de faltante para todos los artículos con cantidad menor a la esperada antes de continuar.');
        return;
    }
    // Pre-llenar valores sugeridos de factura si está en cero
    if ((parseFloat(form.invoice_subtotal) || 0) === 0) {
        form.invoice_subtotal = round(systemSubtotal.value, 2);
        form.invoice_tax      = round(systemTax.value, 2);
    }
    isCalculated.value = true;
}

function openInvoiceModal() {
    if (!isCalculated.value) {
        alert('Primero debes calcular la boleta.');
        return;
    }
    showInvoiceModal.value = true;
}

function closeInvoiceModal() {
    showInvoiceModal.value = false;
}

function saveInvoiceData() {
    if (form.invoice_subtotal === null || form.invoice_subtotal === '' || parseFloat(form.invoice_subtotal) < 0) {
        alert('Por favor ingresa un subtotal válido para la factura física.');
        return;
    }
    if (form.invoice_tax === null || form.invoice_tax === '' || parseFloat(form.invoice_tax) < 0) {
        form.invoice_tax = round((parseFloat(form.invoice_subtotal) || 0) * ivaRate.value, 2);
    }
    isInvoiceSaved.value = true;
    showInvoiceModal.value = false;
}

// ── Razones de faltante (dropdown) ───────────────────────────────────────────
const missingReasons = [
    'Mercadería dañada en tránsito',
    'Diferencia de bulto / empaque',
    'Error del proveedor',
    'Merma reportada',
    'Pedido parcial acordado',
    'Otro',
];

// ── Form principal ───────────────────────────────────────────────────────────
const form = useForm({
    admin_username:   '',
    admin_password:   '',
    invoice_number:   '',
    invoice_subtotal: 0,
    invoice_tax:      0,
    iva_rate:         props.ivaRate,
    notes:            '',
    items: props.order.items.map(it => ({
        product_id:    it.product_id,
        product_name:  it.product?.name || 'Producto',
        barcode:       it.product?.barcode || '',
        expected_qty:  it.quantity,
        scanned_qty:   it.quantity,   // Por defecto = pedido
        factured_qty:  it.quantity,
        unit_cost:     it.price || it.product?.cost || 0,
        missing_reason: '',
    })),
});

// ── Cálculos del Sistema (reactivos) ─────────────────────────────────────────
const ivaRate = ref(props.ivaRate);

// Subtotal del sistema = sum(scanned_qty * unit_cost)
const systemSubtotal = computed(() =>
    form.items.reduce((acc, i) => acc + (parseInt(i.scanned_qty) || 0) * (parseFloat(i.unit_cost) || 0), 0)
);

const systemTax = computed(() =>
    systemSubtotal.value * ivaRate.value
);

const systemTotal = computed(() =>
    systemSubtotal.value + systemTax.value
);

// ── Totales de la Factura Física ────────────────────────────────────────────
const invoiceTotal = computed(() =>
    (parseFloat(form.invoice_subtotal) || 0) + (parseFloat(form.invoice_tax) || 0)
);

// ── Diferencia de costos ────────────────────────────────────────────────────
const costDifference = computed(() =>
    round(invoiceTotal.value - systemTotal.value, 2)
);

// ── Análisis por ítem: estado y excesos ─────────────────────────────────────
const itemStates = computed(() =>
    form.items.map(item => {
        const scanned  = parseInt(item.scanned_qty)  || 0;
        const expected = parseInt(item.expected_qty) || 0;
        const unitCost = parseFloat(item.unit_cost)  || 0;

        if (scanned > expected) {
            const excess       = scanned - expected;
            const extraCost    = excess * unitCost;
            const extraCostIva = round(extraCost * (1 + ivaRate.value), 2);
            return {
                status:      'excess',
                diff:        excess,
                extraCost,
                extraCostIva,
            };
        }
        if (scanned < expected) return { status: 'shortage', diff: expected - scanned };
        return { status: 'ok' };
    })
);

// ── Excesos de cantidad para nota de débito ──────────────────────────────────
const excessItems = computed(() =>
    form.items
        .map((item, idx) => ({ item, state: itemStates.value[idx] }))
        .filter(x => x.state.status === 'excess')
);

const totalQtyExtraCost = computed(() =>
    excessItems.value.reduce((acc, x) => acc + x.state.extraCostIva, 0)
);

// ── Detectar si se generará nota de débito ──────────────────────────────────
const hasCostDebitNote = computed(() => costDifference.value > 0);
const hasQtyDebitNote  = computed(() => excessItems.value.length > 0);
const hasDebitNote     = computed(() => hasCostDebitNote.value || hasQtyDebitNote.value);

const debitNoteType = computed(() => {
    if (hasCostDebitNote.value && hasQtyDebitNote.value) return 'Diferencias de Costos y Cantidades';
    if (hasQtyDebitNote.value)  return 'Diferencia de Cantidades';
    return 'Diferencia de Costos';
});

const totalDebitNote = computed(() => {
    let total = 0;
    if (hasCostDebitNote.value) total += costDifference.value;
    if (hasQtyDebitNote.value)  total += totalQtyExtraCost.value;
    return round(total, 2);
});

// ── Validación faltantes ────────────────────────────────────────────────────
const shortageItemsMissingReason = computed(() =>
    form.items.some((item, idx) =>
        itemStates.value[idx].status === 'shortage' && !item.missing_reason
    )
);

// Auto-cálculo de IVA de factura al cambiar subtotal si IVA está en 0
watch(() => form.invoice_subtotal, (newSub) => {
    if (parseFloat(form.invoice_tax) === 0 || form.invoice_tax === '') {
        form.invoice_tax = round((parseFloat(newSub) || 0) * ivaRate.value, 2);
    }
});

function rowSubtotal(item) {
    return round((parseInt(item.scanned_qty) || 0) * (parseFloat(item.unit_cost) || 0), 2);
}

function rowIva(item) {
    return round(rowSubtotal(item) * ivaRate.value, 2);
}

function rowTotal(item) {
    return round(rowSubtotal(item) + rowIva(item), 2);
}

// ── Modal de Confirmación y Autorización ────────────────────────────────────
function openConfirmModal() {
    if (!isCalculated.value) {
        alert('Primero debes presionar "Calcular" para verificar los montos de la boleta.');
        return;
    }
    if (!isInvoiceSaved.value) {
        alert('Debes ingresar y guardar los datos de las Facturas antes de aplicar la entrada.');
        return;
    }
    if (shortageItemsMissingReason.value) {
        alert('Por favor, selecciona la justificación de faltante para todos los artículos con cantidad menor a la esperada.');
        return;
    }
    form.clearErrors();
    authError.value = '';
    showConfirmModal.value = true;
}

function closeConfirmModal() {
    showConfirmModal.value = false;
}

function onConfirmClick() {
    showConfirmModal.value = false;
    authError.value = '';
    showAuthModal.value = true;
}

function confirmGoodsEntry({ username, password }) {
    form.admin_username = username;
    form.admin_password = password;
    authError.value = '';

    form.post(route('product-orders.goods-entry.store', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
            showAuthModal.value = false;
            closeConfirmModal();
        },
        onError: (errs) => {
            if (errs.admin_credentials) {
                authError.value = errs.admin_credentials;
            } else if (errs.admin_password) {
                authError.value = errs.admin_password;
            } else if (errs.admin_username) {
                authError.value = errs.admin_username;
            } else if (errs.error) {
                authError.value = errs.error;
            } else {
                authError.value = 'Error al autorizar o validar la entrada de mercadería.';
            }
        }
    });
}

function handleKeydown(e) {
    if (e.key === 'Escape') {
        if (showInvoiceModal.value) {
            showInvoiceModal.value = false;
            return;
        }
        if (showAuthModal.value) {
            showAuthModal.value = false;
            return;
        }
        if (showConfirmModal.value) closeConfirmModal();
    }
}

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));

// ── Helpers ─────────────────────────────────────────────────────────────────
const round  = (n, d = 2) => Number(Math.round(n + 'e' + d) + 'e-' + d);
const fmtMny = (n) => 'C$ ' + round(parseFloat(n) || 0, 2).toFixed(2);
const fmtPct = (r) => (r * 100).toFixed(0) + '%';
</script>

<template>
    <Head :title="`Entrada de Mercadería — Boleta #${String(order.id).padStart(6,'0')}`" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-truck-loading text-emerald-400 text-xl"></i>
                    <h1 class="text-white font-semibold text-lg">
                        Entrada de Mercadería — Boleta #{{ String(order.id).padStart(6,'0') }}
                    </h1>
                    <span class="text-xs bg-amber-500/15 text-amber-400 px-2.5 py-1 rounded-full border border-amber-500/20 font-semibold">
                        Pendiente de Recepción
                    </span>
                </div>
                <Link :href="route('product-orders.index')"
                    class="px-4 py-2.5 rounded-xl bg-gray-900 border border-white/10 text-gray-300 hover:text-white text-sm transition-colors flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i> Volver a Pedidos
                </Link>
            </div>
        </template>

        <!-- ── Tarjetas de Datos de Boleta ─────────────────────────────────── -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
            <div class="bg-gray-900 border border-white/5 rounded-2xl p-4">
                <p class="text-gray-500 text-xs uppercase font-medium">Boleta N°</p>
                <p class="text-white text-lg font-bold font-mono">#{{ String(order.id).padStart(6,'0') }}</p>
            </div>
            <div class="bg-gray-900 border border-white/5 rounded-2xl p-4">
                <p class="text-gray-500 text-xs uppercase font-medium">Proveedor</p>
                <p class="text-emerald-400 text-sm font-bold truncate">{{ order.supplier?.name || 'General' }}</p>
            </div>
            <div class="bg-gray-900 border border-white/5 rounded-2xl p-4">
                <p class="text-gray-500 text-xs uppercase font-medium">Sucursal Destino</p>
                <p class="text-cyan-400 text-sm font-bold truncate flex items-center gap-1.5">
                    <i class="fas fa-store text-xs"></i>
                    {{ order.branch || 'Sucursal Managua' }}
                </p>
            </div>
            <div class="bg-gray-900 border border-white/5 rounded-2xl p-4">
                <p class="text-gray-500 text-xs uppercase font-medium">IVA Aplicado</p>
                <p class="text-violet-400 text-lg font-bold">{{ fmtPct(ivaRate) }}</p>
            </div>
            <div class="bg-gray-900 border border-white/5 rounded-2xl p-4">
                <p class="text-gray-500 text-xs uppercase font-medium">Creado Por</p>
                <p class="text-violet-300 text-sm font-medium">{{ order.admin?.full_name || order.admin?.username || 'Admin' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- ── Tabla de Artículos ─────────────────────────────────────── -->
            <div class="xl:col-span-2">
                <div class="bg-gray-900 rounded-2xl border border-white/5 overflow-hidden shadow-xl">
                    <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-gray-950/40">
                        <h3 class="text-white font-semibold text-sm flex items-center gap-2">
                            <i class="fas fa-boxes text-violet-400"></i> Verificación de Artículos Recibidos
                        </h3>
                        <span class="text-xs text-gray-400">
                            IVA: <strong class="text-violet-300">{{ fmtPct(ivaRate) }}</strong>
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="text-gray-500 text-[10px] font-semibold uppercase tracking-wider border-b border-white/5 bg-gray-950/20">
                                    <th class="px-4 py-3">Producto</th>
                                    <th class="px-3 py-3 text-center">Pedido</th>
                                    <th class="px-3 py-3 text-center bg-emerald-500/10 text-emerald-400">Recibido *</th>
                                    <th class="px-3 py-3 text-center">Facturado</th>
                                    <th class="px-3 py-3 text-right">Costo Unit.</th>
                                    <th class="px-3 py-3 text-right">IVA Unit.</th>
                                    <th class="px-3 py-3 text-right">Total Fila</th>
                                    <th class="px-3 py-3 text-center whitespace-nowrap">Estado</th>
                                    <th class="px-4 py-3">Justificación Faltante</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5 text-gray-300">
                                <tr v-for="(item, idx) in form.items" :key="idx"
                                    class="hover:bg-white/2 transition-colors"
                                    :class="{
                                        'bg-red-950/20':    itemStates[idx].status === 'excess',
                                        'bg-amber-950/10':  itemStates[idx].status === 'shortage',
                                    }">
                                    <!-- Producto -->
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-white text-xs">{{ item.product_name }}</p>
                                        <p v-if="item.barcode" class="text-gray-600 text-[10px] font-mono">{{ item.barcode }}</p>
                                    </td>

                                    <!-- Pedido -->
                                    <td class="px-3 py-3 text-center font-bold text-gray-400">
                                        {{ item.expected_qty }}
                                    </td>

                                    <!-- Recibido (EDITABLE) -->
                                    <td class="px-3 py-3 text-center bg-emerald-500/5">
                                        <input
                                            v-model.number="item.scanned_qty"
                                            @input="onItemQtyChange"
                                            type="number" min="0" required
                                            class="w-16 bg-gray-800 border border-emerald-500/30 focus:border-emerald-500 rounded-lg px-2 py-1 text-center text-emerald-400 font-bold outline-none transition-colors"
                                        />
                                    </td>

                                    <!-- Facturado (NO EDITABLE) -->
                                    <td class="px-3 py-3 text-center font-bold text-gray-300">
                                        {{ item.factured_qty }}
                                    </td>

                                    <!-- Costo Unitario (NO EDITABLE) -->
                                    <td class="px-3 py-3 text-right font-mono">
                                        <div class="flex items-center justify-end gap-1">
                                            <span class="text-gray-500 text-[10px]">C$</span>
                                            <span class="font-bold text-white text-xs">{{ Number(item.unit_cost || 0).toFixed(2) }}</span>
                                        </div>
                                    </td>

                                    <!-- IVA Unitario (calculado) -->
                                    <td class="px-3 py-3 text-right text-violet-400 font-mono">
                                        {{ fmtMny(rowIva(item) / Math.max(1, item.scanned_qty)) }}
                                    </td>

                                    <!-- Total Fila -->
                                    <td class="px-3 py-3 text-right font-bold text-emerald-400 font-mono whitespace-nowrap">
                                        {{ fmtMny(rowTotal(item)) }}
                                    </td>

                                    <!-- Estado Badge (Pill corregido sin saltos de linea) -->
                                    <td class="px-3 py-3 text-center whitespace-nowrap">
                                        <span v-if="itemStates[idx].status === 'ok'"
                                            class="inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-400 text-[10px] font-bold border border-emerald-500/25 leading-none shadow-sm">
                                            <i class="fas fa-check text-[9px]"></i>
                                            <span>OK</span>
                                        </span>
                                        <span v-else-if="itemStates[idx].status === 'excess'"
                                            class="inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-full bg-red-500/20 text-red-300 text-[10px] font-bold border border-red-500/30 leading-none shadow-sm">
                                            <i class="fas fa-arrow-up text-[9px]"></i>
                                            <span>+{{ itemStates[idx].diff }} EXCESO</span>
                                        </span>
                                        <span v-else
                                            class="inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-full bg-amber-500/15 text-amber-300 text-[10px] font-bold border border-amber-500/25 leading-none shadow-sm">
                                            <i class="fas fa-arrow-down text-[9px]"></i>
                                            <span>-{{ itemStates[idx].diff }} FALTANTE</span>
                                        </span>
                                    </td>

                                    <!-- Justificación Faltante (solo si falta) -->
                                    <td class="px-4 py-3">
                                        <template v-if="itemStates[idx].status === 'shortage'">
                                            <select
                                                v-model="item.missing_reason"
                                                class="w-full bg-gray-800 border border-amber-500/30 focus:border-amber-500 rounded-lg px-2 py-1.5 text-white text-xs outline-none"
                                                :class="!item.missing_reason ? 'border-red-500/50' : ''"
                                            >
                                                <option value="">— Selecciona motivo —</option>
                                                <option v-for="r in missingReasons" :key="r" :value="r">{{ r }}</option>
                                            </select>
                                        </template>
                                        <span v-else class="text-gray-700 text-[10px]">N/A</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer tabla -->
                    <div class="px-6 py-4 border-t border-white/5 bg-gray-950/40 flex items-center justify-between">
                        <div class="text-xs text-gray-400 space-y-0.5">
                            <div>Artículos en la boleta: <strong class="text-white">{{ form.items.length }}</strong></div>
                            <div class="flex gap-3">
                                <span>Excesos: <strong class="text-red-400">{{ excessItems.length }}</strong></span>
                                <span>Faltantes: <strong class="text-amber-400">{{ form.items.filter((_,i)=>itemStates[i].status==='shortage').length }}</strong></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <!-- 1. Botón Calcular -->
                            <button
                                @click="calculateEntry"
                                type="button"
                                :class="isCalculated
                                    ? 'bg-blue-600/20 text-blue-300 border border-blue-500/40 hover:bg-blue-600/30'
                                    : 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white shadow-lg shadow-blue-900/30'"
                                class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                                <i class="fas fa-calculator"></i>
                                {{ isCalculated ? 'Boleta Calculada' : 'Calcular' }}
                            </button>

                            <!-- 2. Botón Facturas (Se habilita al calcular) -->
                            <button
                                @click="openInvoiceModal"
                                type="button"
                                :disabled="!isCalculated"
                                :class="!isCalculated
                                    ? 'opacity-40 cursor-not-allowed bg-gray-800 text-gray-500 border border-white/5'
                                    : isInvoiceSaved
                                        ? 'bg-violet-600/20 text-violet-300 border border-violet-500/40 hover:bg-violet-600/30'
                                        : 'bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white shadow-lg shadow-purple-900/40 animate-pulse'"
                                class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                                <i class="fas fa-file-invoice-dollar"></i>
                                Facturas
                                <span v-if="isInvoiceSaved" class="text-[10px] bg-violet-500/30 px-1.5 py-0.5 rounded text-violet-200">✓ Guardada</span>
                            </button>

                            <!-- 3. Botón Aplicar Entrada (Se habilita al guardar facturas) -->
                            <button
                                @click="openConfirmModal"
                                type="button"
                                :disabled="!isInvoiceSaved"
                                :class="!isInvoiceSaved
                                    ? 'opacity-40 cursor-not-allowed bg-gray-800 text-gray-500 border border-white/5'
                                    : 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white shadow-xl shadow-emerald-900/40'"
                                class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                                <i class="fas fa-check-double text-sm"></i>
                                Aplicar Entrada de Mercadería
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Panel Derecho: Resumen Financiero ────────────────────────── -->
            <div class="space-y-4">
                <!-- Comparativa Financiera -->
                <div class="bg-gray-900 rounded-2xl border border-white/5 p-5 shadow-xl space-y-3">
                    <h3 class="text-white font-semibold text-sm flex items-center gap-2">
                        <i class="fas fa-balance-scale text-emerald-400"></i>
                        Comparativa de Totales
                    </h3>
                    <div class="space-y-2 text-xs divide-y divide-white/5">
                        <div class="flex justify-between text-gray-300 pt-1">
                            <span>Subtotal Sistema:</span>
                            <span class="font-mono">{{ fmtMny(systemSubtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-violet-300 pt-2">
                            <span>IVA Sistema ({{ fmtPct(ivaRate) }}):</span>
                            <span class="font-mono">{{ fmtMny(systemTax) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-emerald-400 text-sm pt-2">
                            <span>Total Entrada Sistema:</span>
                            <span class="font-mono">{{ fmtMny(systemTotal) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-white pt-2">
                            <span>Total Factura Física:</span>
                            <span class="font-mono">{{ fmtMny(invoiceTotal) }}</span>
                        </div>
                    </div>
                    <div v-if="hasCostDebitNote"
                        class="mt-3 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-xs space-y-1">
                        <div class="text-red-400 font-bold flex items-center gap-1.5">
                            <i class="fas fa-arrow-up"></i> Factura mayor que el Sistema:
                        </div>
                        <div class="text-white font-mono font-bold text-sm">{{ fmtMny(costDifference) }}</div>
                        <div class="text-gray-400 text-[11px]">Se agregará a la Nota de Débito</div>
                    </div>
                </div>

                <!-- Resumen Nota de Débito (si aplica) -->
                <div v-if="hasDebitNote" class="bg-red-950/40 border border-red-500/30 rounded-2xl p-5 shadow-xl space-y-2">
                    <h3 class="text-red-300 font-bold text-xs uppercase flex items-center gap-2">
                        <i class="fas fa-file-invoice text-red-400"></i> Nota de Débito Proyectada
                    </h3>
                    <div class="text-xs space-y-1">
                        <div v-if="hasCostDebitNote" class="flex justify-between text-red-300">
                            <span>Por Costos:</span><span class="font-mono">{{ fmtMny(costDifference) }}</span>
                        </div>
                        <div v-if="hasQtyDebitNote" class="flex justify-between text-red-300">
                            <span>Por Cantidades (c/IVA):</span><span class="font-mono">{{ fmtMny(totalQtyExtraCost) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-red-200 border-t border-red-500/20 pt-2">
                            <span>Total Nota:</span>
                            <span class="font-mono">{{ fmtMny(totalDebitNote) }}</span>
                        </div>
                    </div>
                </div>


            </div>
        </div>

        
        <!-- ════ MODAL DEDICADO: DATOS DE FACTURA FÍSICA ════ -->
        <Transition name="fade">
        <div v-if="showInvoiceModal" @click.self="closeInvoiceModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm overflow-y-auto">
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-2xl my-6 overflow-hidden">
                <!-- Header modal -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-violet-600/20 border border-violet-500/30 flex items-center justify-center">
                            <i class="fas fa-file-invoice-dollar text-violet-400 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-base">Datos de Factura Física</h3>
                            <p class="text-gray-400 text-xs">Ingrese los montos de la factura física del proveedor para compararlos con el sistema</p>
                        </div>
                    </div>
                    <button @click="closeInvoiceModal"
                        class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-colors">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    <!-- 1. Montos calculados del Sistema -->
                    <div class="bg-gray-950/60 border border-white/5 rounded-2xl p-4">
                        <div class="flex items-center justify-between mb-3 border-b border-white/5 pb-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                                <i class="fas fa-desktop"></i> Montos Calculados del Sistema
                            </span>
                            <span class="text-[11px] text-gray-400">IVA Aplicado: <strong class="text-violet-300">{{ fmtPct(ivaRate) }}</strong></span>
                        </div>
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div class="bg-gray-900/80 rounded-xl p-2.5 border border-white/5">
                                <div class="text-[10px] uppercase font-medium text-gray-400">Subtotal Sistema</div>
                                <div class="text-sm font-bold text-white font-mono mt-0.5">{{ fmtMny(systemSubtotal) }}</div>
                            </div>
                            <div class="bg-gray-900/80 rounded-xl p-2.5 border border-white/5">
                                <div class="text-[10px] uppercase font-medium text-violet-400">IVA Sistema</div>
                                <div class="text-sm font-bold text-violet-300 font-mono mt-0.5">{{ fmtMny(systemTax) }}</div>
                            </div>
                            <div class="bg-emerald-950/40 rounded-xl p-2.5 border border-emerald-500/20">
                                <div class="text-[10px] uppercase font-medium text-emerald-400">Total Sistema</div>
                                <div class="text-sm font-extrabold text-emerald-300 font-mono mt-0.5">{{ fmtMny(systemTotal) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Campos de la Factura Física -->
                    <div class="bg-gray-800/60 border border-white/10 rounded-2xl p-5 space-y-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-violet-400 flex items-center gap-1.5">
                            <i class="fas fa-receipt"></i> Factura Física a Ingresar
                        </span>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-gray-400 text-xs font-medium mb-1.5 uppercase">N° de Factura</label>
                                <input v-model="form.invoice_number" type="text" placeholder="Ej: F-99482"
                                    class="w-full bg-gray-900 border border-white/10 focus:border-violet-500 rounded-xl px-3 py-2 text-white text-sm outline-none" />
                            </div>
                            <div>
                                <label class="block text-gray-400 text-xs font-medium mb-1.5 uppercase">Subtotal Factura (C$) *</label>
                                <input v-model.number="form.invoice_subtotal" type="number" step="0.01" min="0" placeholder="0.00"
                                    class="w-full bg-gray-900 border border-white/10 focus:border-violet-500 rounded-xl px-3 py-2 text-white text-sm font-mono font-bold outline-none" />
                            </div>
                            <div>
                                <label class="block text-gray-400 text-xs font-medium mb-1.5 uppercase">IVA Factura (C$)</label>
                                <input v-model.number="form.invoice_tax" type="number" step="0.01" min="0" placeholder="0.00"
                                    class="w-full bg-gray-900 border border-white/10 focus:border-violet-500 rounded-xl px-3 py-2 text-white text-sm font-mono font-bold outline-none" />
                            </div>
                        </div>

                        <!-- Total Factura Calculado -->
                        <div class="pt-3 border-t border-white/10 flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-300">Total Factura Física:</span>
                            <span class="text-lg font-extrabold text-white font-mono">{{ fmtMny(invoiceTotal) }}</span>
                        </div>
                    </div>

                    <!-- 3. Alerta de Diferencia / Nota de Débito en el Modal si aplica -->
                    <div v-if="hasCostDebitNote" class="p-3.5 rounded-xl bg-red-950/40 border border-red-500/30 text-xs space-y-1">
                        <div class="text-red-300 font-bold flex items-center gap-1.5">
                            <i class="fas fa-exclamation-triangle text-red-400"></i>
                            Factura mayor que el Sistema por {{ fmtMny(costDifference) }}
                        </div>
                        <p class="text-red-200/70 text-[11px]">Se generará automáticamente una Nota de Débito al aplicar la entrada.</p>
                    </div>
                    <div v-else class="p-3 rounded-xl bg-emerald-950/30 border border-emerald-500/20 text-xs text-emerald-400 flex items-center gap-2">
                        <i class="fas fa-check-circle"></i>
                        <span>Los montos coinciden o no generan diferencia de costo desfavorable.</span>
                    </div>
                </div>

                <!-- Footer modal -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-white/5 bg-gray-950/50">
                    <button @click="closeInvoiceModal" type="button"
                        class="px-4 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white text-xs font-bold transition-colors">
                        Cancelar
                    </button>
                    <button @click="saveInvoiceData" type="button"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white text-xs font-bold shadow-lg shadow-violet-900/40 transition-all flex items-center gap-2">
                        <i class="fas fa-check"></i>
                        Guardar Facturas
                    </button>
                </div>
            </div>
        </div>
        </Transition>

        <!-- ══ MODAL DE CONFIRMACIÓN: ¿Desea aplicar la entrada de mercadería? ══ -->
        <Transition name="fade">
        <div v-if="showConfirmModal" @click.self="closeConfirmModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm overflow-y-auto">
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-xl my-6 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/40">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <i class="fas fa-check-circle text-emerald-400"></i>
                        Confirmar Entrada de Mercadería
                    </h3>
                    <button @click="closeConfirmModal"
                        class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-colors">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Resumen de stock -->
                    <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 text-xs text-emerald-400">
                        <p class="font-bold text-sm mb-1">✅ Confirmación de Acreditación de Stock</p>
                        <p>Se acreditarán las unidades recibidas de <strong>{{ form.items.length }} producto(s)</strong> al inventario del sistema.</p>
                    </div>

                    <!-- Resumen financiero -->
                    <div class="bg-gray-800 rounded-xl p-4 space-y-1.5 text-xs">
                        <div class="flex justify-between text-gray-300"><span>Subtotal Sistema:</span><span class="font-mono">{{ fmtMny(systemSubtotal) }}</span></div>
                        <div class="flex justify-between text-violet-300"><span>IVA Sistema ({{ fmtPct(ivaRate) }}):</span><span class="font-mono">{{ fmtMny(systemTax) }}</span></div>
                        <div class="flex justify-between font-bold text-white border-t border-white/10 pt-2"><span>Total Sistema:</span><span class="font-mono text-emerald-400">{{ fmtMny(systemTotal) }}</span></div>
                        <div class="flex justify-between text-gray-300 border-t border-white/10 pt-2"><span>Total Factura Física:</span><span class="font-mono">{{ fmtMny(invoiceTotal) }}</span></div>
                    </div>

                    <!-- Aviso de Nota de Débito -->
                    <div v-if="hasDebitNote"
                        class="bg-red-950/40 border border-red-500/30 rounded-xl p-4 text-xs">
                        <p class="text-red-300 font-bold mb-2">
                            ⚠️ Se generará automáticamente una Nota de Débito:
                        </p>
                        <div class="text-red-200/80 space-y-1">
                            <div><strong>Tipo:</strong> {{ debitNoteType }}</div>
                            <div v-if="hasCostDebitNote">• Por costos: <span class="font-mono font-bold">{{ fmtMny(costDifference) }}</span></div>
                            <div v-if="hasQtyDebitNote">• Por cantidades (c/IVA): <span class="font-mono font-bold">{{ fmtMny(totalQtyExtraCost) }}</span></div>
                            <div class="font-bold text-red-300 border-t border-red-500/20 pt-1 mt-1">Total Nota: {{ fmtMny(totalDebitNote) }}</div>
                        </div>
                    </div>

                    <div v-else class="bg-emerald-950/20 border border-emerald-500/20 rounded-xl p-3 text-xs text-emerald-400">
                        <i class="fas fa-check-circle mr-1"></i> Sin diferencias detectadas. No se generará nota de débito.
                    </div>

                    <!-- Pregunta destacada solicitada por el usuario -->
                    <div class="p-4 rounded-xl bg-violet-600/15 border border-violet-500/30 text-center space-y-1">
                        <p class="text-white font-bold text-base">¿Desea aplicar la entrada de mercadería?</p>
                        <p class="text-gray-300 text-xs">Al presionar "Sí", se solicitarán las credenciales de autorización para aplicar los cambios de inventario.</p>
                    </div>

                    <!-- Botones Cancelar / Sí -->
                    <div class="flex justify-end gap-3 pt-2 border-t border-white/5">
                        <button type="button" @click="closeConfirmModal"
                            class="px-5 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm font-semibold transition-colors">
                            Cancelar
                        </button>
                        <button type="button" @click="onConfirmClick"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-sm shadow-lg transition-all flex items-center gap-2">
                            <i class="fas fa-check"></i>
                            Sí
                        </button>
                    </div>
                </div>
            </div>
        </div>
        </Transition>

        <!-- ══ MODAL REUTILIZABLE DE AUTORIZACIÓN ═════════════════════════════ -->
        <AuthModal
            :show="showAuthModal"
            title="Autorizar Entrada de Mercadería"
            subtitle="Ingrese credenciales autorizadas (Usuario y Contraseña) para aplicar la entrada de mercadería al inventario."
            actionLabel="Autorizar y Aplicar Entrada"
            :processing="form.processing"
            :errorMessage="authError"
            :initialUsername="page.props.auth?.user?.username || ''"
            @close="showAuthModal = false"
            @confirm="confirmGoodsEntry"
        />
    </AdminLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
