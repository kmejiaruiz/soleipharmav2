<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';
import AuthModal from '@/Components/AuthModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    transfer: Object,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const transferNum = computed(() => {
    return String(props.transfer?.id || '').padStart(4, '0');
});

const isReceived = computed(() => props.transfer?.status === 'recibido');

const creatorName = computed(() => {
    const c = props.transfer?.creator;
    if (!c) return '—';
    if (c.first_name || c.last_name) {
        return `${c.first_name || ''} ${c.last_name || ''}`.trim();
    }
    return c.name || c.username || '—';
});

const receiverName = computed(() => {
    const r = props.transfer?.receiver;
    if (!r) return null;
    if (r.first_name || r.last_name) {
        return `${r.first_name || ''} ${r.last_name || ''}`.trim();
    }
    return r.name || r.username;
});

const currentTimestamp = ref('');
function updateTimestamp() {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    currentTimestamp.value =
        `${pad(now.getDate())}/${pad(now.getMonth() + 1)}/${now.getFullYear()} ` +
        `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
}

onMounted(() => {
    updateTimestamp();
});

function formatCurrency(val) {
    const n = Number(val || 0);
    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDateOnly(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    const pad = (n) => String(n).padStart(2, '0');
    return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`;
}

function formatDateTime(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    const pad = (n) => String(n).padStart(2, '0');
    return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

// ── Estado de Recepción de Mercadería (Inputs editables) ──
const receptionItems = ref(
    (props.transfer?.items || []).map(it => ({
        id: it.id,
        product_id: it.product_id,
        name: it.product?.name || `Producto #${it.product_id}`,
        sku: it.product?.sku || it.product?.barcode || '—',
        cost: Number(it.product?.cost || 0),
        quantity_sent: Number(it.quantity_sent || 0),
        // Si ya fue recibido, usa el guardado; si está pendiente, por defecto pre-llena con lo enviado
        quantity_received: it.quantity_received !== null && it.quantity_received !== undefined
            ? Number(it.quantity_received)
            : Number(it.quantity_sent || 0)
    }))
);

const receptionNotes = ref('');

// Totales Enviados
const totalSentQty = computed(() => {
    return receptionItems.value.reduce((acc, it) => acc + it.quantity_sent, 0);
});

const totalSentValue = computed(() => {
    return receptionItems.value.reduce((acc, it) => acc + (it.cost * it.quantity_sent), 0);
});

// Totales Recibidos
const totalRecvQty = computed(() => {
    return receptionItems.value.reduce((acc, it) => acc + Number(it.quantity_received || 0), 0);
});

const totalRecvValue = computed(() => {
    return receptionItems.value.reduce((acc, it) => acc + (it.cost * Number(it.quantity_received || 0)), 0);
});

// Diferencias Totales
const totalDiffQty = computed(() => {
    return totalRecvQty.value - totalSentQty.value;
});

const totalDiffValue = computed(() => {
    return totalRecvValue.value - totalSentValue.value;
});

const hasShortages = computed(() => {
    if (!isReceived.value) return false;
    return receptionItems.value.some(it => Number(it.quantity_received ?? 0) < Number(it.quantity_sent || 0));
});

// Permiso para recibir
function normalizeBranch(str) {
    if (!str) return '';
    return str.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim();
}

const canReceive = computed(() => {
    if (props.transfer?.status !== 'pendiente') return false;
    const role = currentUser.value?.role;
    if (role !== 'admin' && role !== 'superadmin') return false;

    if (role === 'superadmin') return true;

    const userB = normalizeBranch(currentUser.value?.branch);
    const toB   = normalizeBranch(props.transfer?.to_branch);
    return userB === toB || userB.includes(toB) || toB.includes(userB);
});

const isReceiving = ref(false);

// Estado de Modales de Autorización y Confirmación
const showAuthModal = ref(false);
const authProcessing = ref(false);
const authError = ref('');
const authorizedAdmin = ref(null);

const showConfirmModal = ref(false);

function startReceptionFlow() {
    // 1. Validar que no haya cantidades negativas o vacías
    for (const it of receptionItems.value) {
        if (it.quantity_received === null || it.quantity_received === undefined || it.quantity_received < 0) {
            alert(`Por favor ingresa una cantidad válida (mayor o igual a 0) para ${it.name}.`);
            return;
        }
    }

    // 2. Abrir modal de credenciales de admin/superadmin de la sucursal logueada
    authError.value = '';
    authorizedAdmin.value = null;
    showAuthModal.value = true;
}

async function handleAuthorizeCredentials({ username, password }) {
    authProcessing.value = true;
    authError.value = '';

    try {
        const response = await axios.post(route('branch-transfers.validate-auth'), {
            username,
            password,
        });

        if (response.data?.success) {
            authorizedAdmin.value = response.data;
            showAuthModal.value = false;
            // Una vez validadas las credenciales, consultar por medio de modal reutilizable "¿Aplicar traslado?"
            showConfirmModal.value = true;
        }
    } catch (err) {
        if (err.response?.data?.message) {
            authError.value = err.response.data.message;
        } else {
            authError.value = 'Ocurrió un error al verificar las credenciales.';
        }
    } finally {
        authProcessing.value = false;
    }
}

function handleCloseAuthModal() {
    showAuthModal.value = false;
    authError.value = '';
    authorizedAdmin.value = null;
}

function handleCancelConfirmModal() {
    showConfirmModal.value = false;
    authorizedAdmin.value = null;
    // Regresa a la vista tal como estaba antes de presionar confirmar y recibir
}

function executeApplyTransfer() {
    isReceiving.value = true;

    router.patch(route('branch-transfers.receive', props.transfer.id), {
        items: receptionItems.value.map(it => ({
            id: it.id,
            quantity_received: it.quantity_received
        })),
        reception_notes: receptionNotes.value || null,
        authorizing_user_id: authorizedAdmin.value?.user_id || null,
    }, {
        onFinish: () => {
            isReceiving.value = false;
            showConfirmModal.value = false;
        }
    });
}
</script>

<template>
    <Head :title="`Boleta de Traslado #${transferNum}`" />

    <AdminLayout>
        <!-- Header superior con título, breadcrumbs y botones de acción -->
        <div class="no-print-bar mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                    <i class="fas fa-file-invoice text-violet-400"></i>
                    Boleta de Traslado #{{ transferNum }}
                </h1>
                <p class="text-xs text-gray-400 mt-1">
                    Verificación, entrada de mercadería y control de discrepancias
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <!-- Botón Imprimir / PDF oficial -->
                <a
                    :href="route('branch-transfers.pdf', transfer.id)"
                    target="_blank"
                    class="px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white font-semibold text-xs shadow-lg shadow-violet-600/20 flex items-center gap-2 transition-all cursor-pointer"
                >
                    <i class="fas fa-print"></i>
                    <span>Imprimir / PDF</span>
                </a>

                <!-- Botón Acta de Faltantes (PDF) si hay diferencias -->
                <a
                    v-if="hasShortages"
                    :href="route('branch-transfers.shortage-pdf', transfer.id)"
                    target="_blank"
                    class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs shadow-lg shadow-rose-600/20 flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap"
                    title="Descargar Acta Oficial de Faltantes y Pérdidas"
                >
                    <i class="fas fa-file-invoice text-rose-200"></i>
                    <span>Acta de Faltantes (PDF)</span>
                </a>

                <!-- Botón Volver -->
                <Link
                    :href="route('branch-transfers.index')"
                    class="px-4 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 font-semibold text-xs border border-white/5 flex items-center gap-2 transition-all cursor-pointer"
                >
                    <i class="fas fa-arrow-left"></i>
                    <span>Volver</span>
                </Link>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════════
             HOJA OFICIAL DE LA BOLETA (Diseño idéntico a Crystal Reports / A4)
        ══════════════════════════════════════════════════════════════════════ -->
        <div class="w-full flex justify-center pb-8">
            <div id="boleta-print-area" class="w-full bg-white text-black p-8 sm:p-10 rounded-lg shadow-2xl border border-gray-200">
                <!-- Cabecera de Empresa -->
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <div class="b-company">FARMACIA SOLEI</div>
                        <div class="b-subtitle">Sistema de Inventarios — Traslado entre Sucursales</div>
                    </div>
                    <div class="text-right text-[11px] text-gray-600">
                        <span class="font-bold text-gray-700">Hora de Impresión:</span><br>
                        <span class="text-xs font-bold text-red-600">{{ currentTimestamp }}</span>
                    </div>
                </div>

                <!-- Caja de Título -->
                <div class="b-title-box">
                    <h2>BOLETA DE TRASLADO DE INVENTARIO</h2>
                    <p>N° BT-{{ transferNum }} &nbsp;·&nbsp; Estado: {{ transfer.status.toUpperCase() }}</p>
                </div>

                <!-- Tabla de Metadatos -->
                <table class="b-meta">
                    <tbody>
                        <tr>
                            <td class="lbl">Sucursal Origen:</td>
                            <td>{{ transfer.from_branch }}</td>
                            <td class="sep"></td>
                            <td class="lbl text-right">Fecha Impresión:</td>
                            <td class="text-right">{{ currentTimestamp }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">Sucursal Destino:</td>
                            <td>{{ transfer.to_branch }}</td>
                            <td class="sep"></td>
                            <td class="lbl text-right">Fecha Emisión:</td>
                            <td class="text-right">{{ formatDateOnly(transfer.created_at) }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">Emitido por:</td>
                            <td class="capitalize">{{ creatorName }}</td>
                            <td class="sep"></td>
                            <template v-if="isReceived">
                                <td class="lbl text-right">Hora de Recepción:</td>
                                <td class="text-right font-bold">{{ formatDateTime(transfer.received_at) }}</td>
                            </template>
                            <template v-else>
                                <td colspan="2"></td>
                            </template>
                        </tr>
                        <tr v-if="isReceived && receiverName">
                            <td class="lbl">Recibido por:</td>
                            <td class="capitalize">{{ receiverName }}</td>
                            <td colspan="3"></td>
                        </tr>
                        <tr v-if="transfer.notes">
                            <td class="lbl">Observaciones:</td>
                            <td colspan="4" class="italic">{{ transfer.notes }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Si está pendiente y el usuario puede recibir: Alerta de Entrada de Mercadería -->
                <div v-if="canReceive" class="mb-4 p-3 bg-amber-50 border border-amber-300 rounded text-xs text-amber-900 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-boxes text-amber-700 text-sm"></i>
                        <span><strong>Recepción física en proceso:</strong> Ingrese en la columna <strong>"Uds. Recibidas"</strong> las cantidades exactas que llegaron físicamente. Si falta mercadería, el sistema registrará la diferencia y el costo faltante.</span>
                    </div>
                </div>

                <!-- Tabla de Ítems / Productos -->
                <table class="b-items">
                    <thead>
                        <tr>
                            <th style="width: 12%;">SKU</th>
                            <th :style="{ width: (isReceived || canReceive) ? '28%' : '38%' }">Producto</th>
                            <th class="tr" style="width: 12%;">Costo Unit.</th>
                            <th class="tc" style="width: 10%;">Uds. Env.</th>
                            <th class="tr" style="width: 13%;">Val. Enviado</th>

                            <!-- Columnas cuando está recibido O cuando se está recibiendo -->
                            <template v-if="isReceived || canReceive">
                                <th class="tc" style="width: 11%;">Uds. Rec.</th>
                                <th class="tr" style="width: 13%;">Val. Recibido</th>
                                <th class="tc" style="width: 8%;">Dif. (Uds)</th>
                                <th class="tr" style="width: 12%;">Dif. Costo</th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="it in receptionItems" :key="it.id">
                            <td>{{ it.sku }}</td>
                            <td class="font-semibold">{{ it.name }}</td>
                            <td class="tr">{{ it.cost > 0 ? formatCurrency(it.cost) : '—' }}</td>
                            <td class="tc font-bold">{{ it.quantity_sent }}</td>
                            <td class="tr">{{ it.cost > 0 ? formatCurrency(it.cost * it.quantity_sent) : '—' }}</td>

                            <!-- Modo editable si puede recibir, o modo sólo lectura si ya fue recibido -->
                            <template v-if="canReceive">
                                <td class="tc">
                                    <input
                                        type="number"
                                        min="0"
                                        :max="it.quantity_sent"
                                        v-model.number="it.quantity_received"
                                        class="w-16 px-1.5 py-1 text-center font-bold text-xs border rounded bg-white text-gray-900 border-gray-400 focus:border-violet-600 focus:ring-1 focus:ring-violet-600 outline-none"
                                        :class="it.quantity_received < it.quantity_sent ? 'bg-red-50 border-red-500 text-red-700' : ''"
                                    />
                                </td>
                                <td class="tr font-semibold">
                                    {{ it.cost > 0 ? formatCurrency(it.cost * Number(it.quantity_received || 0)) : '—' }}
                                </td>
                                <td class="tc font-bold" :class="(Number(it.quantity_received || 0) - it.quantity_sent) < 0 ? 'diff-neg' : 'diff-ok'">
                                    {{ (Number(it.quantity_received || 0) - it.quantity_sent) === 0 ? '✓' : (Number(it.quantity_received || 0) - it.quantity_sent) }}
                                </td>
                                <td class="tr font-bold" :class="((Number(it.quantity_received || 0) - it.quantity_sent) * it.cost) < 0 ? 'diff-neg' : 'text-gray-700'">
                                    {{ it.cost > 0 ? (((Number(it.quantity_received || 0) - it.quantity_sent) * it.cost) < 0 ? '-C$ ' + formatCurrency(Math.abs((Number(it.quantity_received || 0) - it.quantity_sent) * it.cost)) : 'C$ 0.00') : '—' }}
                                </td>
                            </template>

                            <template v-else-if="isReceived">
                                <td class="tc font-bold">{{ it.quantity_received }}</td>
                                <td class="tr font-semibold">{{ it.cost > 0 ? formatCurrency(it.cost * it.quantity_received) : '—' }}</td>
                                <td class="tc font-bold" :class="(it.quantity_received - it.quantity_sent) < 0 ? 'diff-neg' : 'diff-ok'">
                                    {{ (it.quantity_received - it.quantity_sent) === 0 ? '✓' : (it.quantity_received - it.quantity_sent) }}
                                </td>
                                <td class="tr font-bold" :class="((it.quantity_received - it.quantity_sent) * it.cost) < 0 ? 'diff-neg' : 'text-gray-700'">
                                    {{ it.cost > 0 ? (((it.quantity_received - it.quantity_sent) * it.cost) < 0 ? '-C$ ' + formatCurrency(Math.abs((it.quantity_received - it.quantity_sent) * it.cost)) : 'C$ 0.00') : '—' }}
                                </td>
                            </template>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right">TOTALES:</td>
                            <td class="tc">{{ totalSentQty }}</td>
                            <td class="tr">{{ totalSentValue > 0 ? formatCurrency(totalSentValue) : '—' }}</td>
                            <template v-if="isReceived || canReceive">
                                <td class="tc">{{ totalRecvQty }}</td>
                                <td class="tr">{{ totalRecvValue > 0 ? formatCurrency(totalRecvValue) : '—' }}</td>
                                <td class="tc font-bold" :class="totalDiffQty < 0 ? 'diff-neg' : 'diff-ok'">
                                    {{ totalDiffQty === 0 ? '✓' : totalDiffQty }}
                                </td>
                                <td class="tr font-bold" :class="totalDiffValue < 0 ? 'diff-neg' : ''">
                                    {{ totalDiffValue < 0 ? '-C$ ' + formatCurrency(Math.abs(totalDiffValue)) : 'C$ 0.00' }}
                                </td>
                            </template>
                        </tr>
                    </tfoot>
                </table>

                <!-- Resumen inferior -->
                <div class="b-summary">
                    <div class="b-sum-left">
                        Total de líneas: &nbsp;&nbsp;<u class="font-bold">{{ receptionItems.length }}</u><br>
                        Total unidades enviadas: &nbsp;&nbsp;<u class="font-bold">{{ totalSentQty }}</u>
                        <template v-if="isReceived || canReceive">
                            <br>Total unidades recibidas: &nbsp;&nbsp;<u class="font-bold">{{ totalRecvQty }}</u>
                            <template v-if="totalDiffQty < 0">
                                <br><span class="text-red-600 font-bold">Unidades Faltantes: &nbsp;&nbsp;<u>{{ Math.abs(totalDiffQty) }} unids</u></span>
                            </template>
                        </template>
                    </div>
                    <div v-if="totalSentValue > 0" class="b-sum-right">
                        <table class="b-sum-tbl">
                            <tbody>
                                <tr>
                                    <td class="lbl">Valor Total Enviado</td>
                                    <td style="width: 120px;">C$ {{ formatCurrency(totalSentValue) }}</td>
                                </tr>
                                <tr v-if="isReceived || canReceive">
                                    <td class="lbl">Valor Total Recibido</td>
                                    <td>C$ {{ formatCurrency(totalRecvValue) }}</td>
                                </tr>
                                <tr v-if="(isReceived || canReceive) && totalDiffValue < 0">
                                    <td class="lbl diff-neg">Diferencia de Costo (Faltante)</td>
                                    <td class="diff-neg">-C$ {{ formatCurrency(Math.abs(totalDiffValue)) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Firmas -->
                <div class="b-sigs">
                    <div class="b-sig">
                        <div class="line">
                            <strong class="capitalize">{{ creatorName }}</strong>
                        </div>
                        <div class="role">Emitido por — {{ transfer.from_branch }}</div>
                    </div>
                    <div class="b-sig">
                        <div class="line">
                            <strong v-if="receiverName" class="capitalize">{{ receiverName }}</strong>
                            <span v-else class="text-gray-400">_________________________________</span>
                        </div>
                        <div class="role">Recibido por — {{ transfer.to_branch }}</div>
                    </div>
                </div>

                <!-- Pie de documento -->
                <div class="b-footer">
                    Farmacia Solei &nbsp;·&nbsp; BT-{{ transferNum }} &nbsp;·&nbsp; Generado el {{ currentTimestamp }}
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════════
             BARRA DE RECEPCIÓN / GUARDADO (Si está pendiente y es sucursal destino)
        ══════════════════════════════════════════════════════════════════════ -->
        <div v-if="canReceive" class="max-w-[820px] mx-auto mb-10 p-5 rounded-2xl bg-gray-900 border border-emerald-500/30 shadow-2xl space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 flex-shrink-0">
                    <i class="fas fa-boxes text-lg"></i>
                </div>
                <div>
                    <h4 class="text-white font-bold text-sm">Confirmación de Entrada Física de Mercadería</h4>
                    <p class="text-gray-400 text-xs mt-0.5">
                        Al confirmar, se registrarán las cantidades recibidas en la boleta y el inventario de <strong>{{ transfer.to_branch }}</strong> se actualizará automáticamente.
                    </p>
                </div>
            </div>

            <!-- Advertencia si hay faltante -->
            <div v-if="totalDiffQty < 0" class="p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-xs flex items-center gap-2">
                <i class="fas fa-exclamation-triangle text-red-400 text-base"></i>
                <div>
                    <strong>Se ha detectado un faltante de {{ Math.abs(totalDiffQty) }} unidades (-C$ {{ formatCurrency(Math.abs(totalDiffValue)) }}):</strong>
                    <p class="text-red-400/90 text-[11px] mt-0.5">Quedará registrado en la boleta con el reporte de diferencias.</p>
                </div>
            </div>

            <!-- Nota opcional de recepción -->
            <div>
                <label class="block text-gray-400 text-xs font-medium mb-1">Notas u observaciones de recepción (Opcional)</label>
                <textarea
                    v-model="receptionNotes"
                    rows="2"
                    placeholder="Ej. Producto X recibido con 2 unidades faltantes por avería en traslado..."
                    class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-3 py-2 text-white text-xs outline-none transition-colors"
                ></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-white/5">
                <Link
                    :href="route('branch-transfers.index')"
                    class="px-4 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs font-semibold transition-colors"
                >
                    Cancelar
                </Link>
                <button
                    @click="startReceptionFlow"
                    :disabled="isReceiving"
                    class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-900/30 flex items-center gap-2 transition-all cursor-pointer disabled:opacity-50"
                >
                    <i v-if="isReceiving" class="fas fa-spinner fa-spin"></i>
                    <i v-else class="fas fa-check-double"></i>
                    <span>{{ isReceiving ? 'Aplicando Entrada...' : 'Confirmar y Recibir Mercadería' }}</span>
                </button>
            </div>
        </div>
        <!-- ══ MODAL DE CREDENCIALES (ADMIN / SUPERADMIN) ═══════════════════════ -->
        <AuthModal
            :show="showAuthModal"
            title="Autorización Requerida"
            subtitle="Ingresa tus credenciales"
            actionLabel="Verificar y Continuar"
            :processing="authProcessing"
            :errorMessage="authError"
            :initialUsername="currentUser?.username || ''"
            @close="handleCloseAuthModal"
            @confirm="handleAuthorizeCredentials"
        />

        <!-- ══ MODAL REUTILIZABLE: ¿Aplicar traslado? ══════════════════════════ -->
        <ConfirmModal
            :show="showConfirmModal"
            title="¿Aplicar traslado?"
            subtitle="Se registrará la entrada de mercadería físicamente al inventario."
            confirmText="Confirmar"
            cancelText="Cancelar"
            confirmVariant="emerald"
            icon="fa-dolly-flatbed"
            :processing="isReceiving"
            @close="handleCancelConfirmModal"
            @cancel="handleCancelConfirmModal"
            @confirm="executeApplyTransfer"
        >
            <div class="space-y-3 mt-2">
                <div class="p-3 rounded-xl bg-gray-800/80 border border-white/5 space-y-1.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Sucursal Destino:</span>
                        <span class="text-white font-bold">{{ transfer.to_branch }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Total Unidades a Ingresar:</span>
                        <span class="text-emerald-400 font-bold">{{ totalRecvQty }} unidades</span>
                    </div>
                    <div v-if="authorizedAdmin" class="flex justify-between border-t border-white/5 pt-1.5">
                        <span class="text-gray-400">Autorizado por:</span>
                        <span class="text-violet-300 font-semibold">{{ authorizedAdmin.name || authorizedAdmin.username }}</span>
                    </div>
                </div>

                <!-- Advertencia si hay faltante -->
                <div v-if="totalDiffQty < 0" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs flex items-start gap-2.5">
                    <i class="fas fa-exclamation-triangle text-rose-400 text-sm mt-0.5 flex-shrink-0"></i>
                    <div>
                        <strong class="text-rose-200">Faltante detectado:</strong>
                        <p class="text-[11px] text-rose-300/90 mt-0.5">
                            Faltan <strong>{{ Math.abs(totalDiffQty) }} unidades</strong> (-C$ {{ formatCurrency(Math.abs(totalDiffValue)) }}). Esta diferencia se asentará formalmente en el Acta de Faltantes.
                        </p>
                    </div>
                </div>

                <p class="text-gray-400 text-[11.5px] leading-relaxed">
                    ¿Confirmas que deseas aplicar este traslado e incrementar el stock en <strong>{{ transfer.to_branch }}</strong>?
                </p>
            </div>
        </ConfirmModal>
    </AdminLayout>
</template>

<style scoped>
#boleta-print-area {
    max-width: 820px;
    box-sizing: border-box;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 13px;
    color: #000;
}

.b-company {
    font-size: 18px;
    font-weight: bold;
    text-transform: uppercase;
    margin-bottom: 2px;
    color: #000;
}

.b-subtitle {
    font-size: 12px;
    color: #555;
    margin-bottom: 14px;
}

.b-title-box {
    text-align: center;
    border-top: 2px solid #000;
    border-bottom: 1px solid #000;
    padding: 8px 0;
    margin-bottom: 14px;
}

.b-title-box h2 {
    font-size: 18px;
    margin: 0 0 2px;
    text-transform: uppercase;
    font-weight: bold;
    color: #000;
}

.b-title-box p {
    margin: 0;
    font-size: 13px;
    font-weight: bold;
    color: #000;
}

.b-meta {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 14px;
    font-size: 13px;
    color: #000;
}

.b-meta td {
    padding: 3px 5px;
    vertical-align: top;
}

.b-meta td.lbl {
    font-weight: bold;
    white-space: nowrap;
    width: 130px;
}

.b-meta td.sep {
    width: 20px;
}

.b-items {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 14px;
    font-size: 12.5px;
    color: #000;
}

.b-items thead th {
    border-top: 1px solid #000;
    border-bottom: 1px solid #000;
    padding: 6px 7px;
    font-weight: bold;
    font-size: 12px;
    text-align: left;
    color: #000;
}

.b-items tbody td {
    padding: 5px 7px;
    vertical-align: middle;
    border: none;
    font-size: 12.5px;
    color: #000;
}

.b-items tbody tr:nth-child(even) td {
    background: #f5f5f5;
}

.b-items tfoot td {
    border-top: 1px solid #000;
    font-weight: bold;
    padding: 6px 7px;
    font-size: 12.5px;
    color: #000;
}

.b-summary {
    width: 100%;
    border-top: 1px solid #000;
    padding-top: 8px;
    display: flex;
    justify-content: space-between;
    margin-top: 4px;
    font-size: 12.5px;
    color: #000;
}

.b-sum-left {
    flex: 1;
}

.b-sum-right {
    flex: 1;
    display: flex;
    justify-content: flex-end;
}

.b-sum-tbl {
    border-collapse: collapse;
}

.b-sum-tbl td {
    padding: 3px 6px;
    text-align: right;
    font-size: 12.5px;
}

.b-sum-tbl td.lbl {
    font-weight: bold;
}

.b-sigs {
    width: 100%;
    display: flex;
    justify-content: space-between;
    margin-top: 36px;
}

.b-sig {
    width: 45%;
    text-align: center;
}

.b-sig .line {
    border-top: 1px solid #555;
    padding-top: 6px;
    margin-top: 28px;
    font-size: 12px;
    color: #000;
}

.b-sig .role {
    font-size: 11px;
    color: #555;
}

.b-footer {
    margin-top: 20px;
    border-top: 1px solid #ddd;
    padding-top: 8px;
    text-align: center;
    font-size: 11px;
    color: #777;
}

.tr { text-align: right; }
.tc { text-align: center; }
.diff-neg { color: #c00000 !important; font-weight: bold; }
.diff-ok { color: #155724 !important; font-weight: bold; }
</style>
