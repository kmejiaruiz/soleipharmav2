<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const props = defineProps({
    transfers: Object,
    products:  Array,
    branches:  Array,
    filters:   Object,
    userIsSuperAdmin: Boolean,
});

// Lista reactiva de productos (se actualiza al cambiar sucursal origen)
const productList = ref([...props.products]);

const page = usePage();
const user = computed(() => page.props.auth.user);

// Cargar sucursales dinámicas desde las props enviadas por el servidor
const availableBranches = computed(() => {
    if (props.branches && props.branches.length > 0) {
        return props.branches;
    }
    return ['Sucursal Managua', 'Sucursal Leon'];
});

const status = ref(props.filters?.status || 'all');
watch(status, (val) => {
    router.get(route('branch-transfers.index'), {
        status: val !== 'all' ? val : undefined,
    }, { preserveState: true, replace: true });
});

// Modal de Creación
const showModal = ref(false);

const form = useForm({
    from_branch: '',
    to_branch:   '',
    notes:       '',
    items: [
        { product_id: '', quantity: 1 }
    ],
});

// Validación: Evitar que origen y destino sean iguales
const isSameBranch = computed(() => {
    return !!(form.from_branch && form.to_branch && form.from_branch === form.to_branch);
});

// Sucursales permitidas como origen según rol
const originBranches = computed(() => {
    if (props.userIsSuperAdmin) return availableBranches.value;
    // No-superadmin: solo su propia sucursal
    const ub = user.value?.branch;
    return ub ? [ub] : availableBranches.value;
});

// Al cambiar Origen, ajustar destino y recargar stock
watch(() => form.from_branch, async (newFrom) => {
    if (form.to_branch === newFrom) {
        const alt = availableBranches.value.find(b => b !== newFrom);
        form.to_branch = alt || '';
    }
    // Fetch productos de la sucursal origen seleccionada
    if (newFrom) {
        try {
            const { data } = await axios.get(route('branch-transfers.products-by-branch'), { params: { branch: newFrom } });
            productList.value = data;
            // Limpiar selecciones de productos que ya no existan en la nueva sucursal
            form.items.forEach(item => {
                if (item.product_id && !data.find(p => p.id === item.product_id)) {
                    item.product_id = '';
                }
            });
        } catch { /* fallback: keep current list */ }
    }
});

function addItem() {
    form.items.push({ product_id: '', quantity: 1 });
}

function removeItem(idx) {
    if (form.items.length > 1) {
        form.items.splice(idx, 1);
    }
}

function openModal() {
    form.reset();
    form.clearErrors();
    form.from_branch = user.value?.branch || availableBranches.value[0] || 'Sucursal Managua';
    const alt = availableBranches.value.find(b => b !== form.from_branch);
    form.to_branch = alt || availableBranches.value[1] || 'Sucursal Leon';
    form.items = [{ product_id: '', quantity: 1 }];
    if (form.from_branch) {
        axios.get(route('branch-transfers.products-by-branch'), { params: { branch: form.from_branch } })
            .then(res => { productList.value = res.data; })
            .catch(() => {});
    }
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    form.reset();
}

function submitForm() {
    if (isSameBranch.value) return;

    form.post(route('branch-transfers.store'), {
        onSuccess: () => closeModal(),
    });
}

function normalizeBranch(str) {
    if (!str) return '';
    return str.toLowerCase()
        .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
        .trim();
}

// ── REGLA DE RECEPCIÓN DINÁMICA: Solo usuarios Admin o Superadmin de la Sucursal DESTINO ──
function canReceive(t) {
    if (t.status !== 'pendiente') return false;

    const role = user.value?.role;
    const isAdminOrSuperadmin = (role === 'admin' || role === 'superadmin');

    if (!isAdminOrSuperadmin) return false;

    const userB = normalizeBranch(user.value?.branch);
    const toB   = normalizeBranch(t.to_branch);

    if (!userB || !toB) return false;

    // Comparación dinámica flexible sin nombres fijos quemados
    return userB === toB || userB.includes(toB) || toB.includes(userB);
}

function receiveTransfer(id) {
    if (confirm('¿Confirmas la recepción física de este traslado y el incremento de stock en tu sucursal?')) {
        router.patch(route('branch-transfers.receive', id));
    }
}

function hasShortages(t) {
    if (!t || t.status !== 'recibido' || !t.items) return false;
    return t.items.some(it => it.quantity_received !== null && it.quantity_received !== undefined && Number(it.quantity_received) < Number(it.quantity_sent));
}

function getStatusBadge(st) {
    if (st === 'recibido') return { label: 'Recibido', class: 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30', icon: 'fa-check-circle' };
    if (st === 'cancelado') return { label: 'Cancelado', class: 'bg-red-500/15 text-red-300 border-red-500/30', icon: 'fa-times-circle' };
    return { label: 'Pendiente', class: 'bg-amber-500/15 text-amber-300 border-amber-500/30', icon: 'fa-clock' };
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleString('es-NI', { dateStyle: 'short', timeStyle: 'short' });
}

// ── Dropdown de Acción único por fila ──────────────────────────────────────────
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
    if (e.key === 'Escape') closeActionMenu();
}

onMounted(() => {
    window.addEventListener('click', handleGlobalClick);
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('click', handleGlobalClick);
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <Head title="Traslados entre Sucursales" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                        <i class="fas fa-dolly-flatbed text-violet-400"></i> Traslados entre Sucursales
                    </h1>
                    <p class="text-xs text-gray-400 mt-0.5">Envío y recepción de inventario entre sucursales de la farmacia</p>
                </div>
                <button
                    @click="openModal"
                    class="px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white font-semibold text-xs shadow-lg shadow-violet-600/20 transition-all flex items-center gap-2"
                >
                    <i class="fas fa-plus"></i> Nuevo Traslado
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Filtro de estado -->
            <div class="flex items-center justify-between">
                <div class="flex gap-2">
                    <button
                        v-for="st in [
                            { key: 'all', label: 'Todos' },
                            { key: 'pendiente', label: 'Pendientes' },
                            { key: 'recibido', label: 'Recibidos' }
                        ]"
                        :key="st.key"
                        @click="status = st.key"
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all border',
                            status === st.key
                                ? 'bg-violet-600 text-white border-violet-500 shadow-md'
                                : 'bg-gray-900 text-gray-400 border-white/5 hover:text-white hover:bg-gray-800'
                        ]"
                    >
                        {{ st.label }}
                    </button>
                </div>
            </div>

            <!-- Tabla de Traslados -->
            <div class="bg-gray-900 rounded-2xl border border-white/5 overflow-hidden shadow-xl">
                <div class="overflow-x-auto min-h-[300px]">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-white/5 bg-gray-950/40">
                                <th class="px-5 py-4 whitespace-nowrap">ID / Fecha</th>
                                <th class="px-5 py-4 whitespace-nowrap">Origen ➔ Destino</th>
                                <th class="px-5 py-4 min-w-[200px]">Productos</th>
                                <th class="px-4 py-4 text-center whitespace-nowrap">Estado</th>
                                <th class="px-5 py-4 whitespace-nowrap">Solicitado por</th>
                                <th class="px-5 py-4 text-right whitespace-nowrap">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-gray-300">
                            <tr v-for="(t, index) in transfers.data" :key="t.id" class="hover:bg-white/2 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <Link :href="route('branch-transfers.show', t.id)" class="font-bold text-violet-400 hover:text-violet-300 text-xs font-mono transition-colors inline-flex items-center gap-1 group">
                                        <span>#TR-{{ String(t.id).padStart(4, '0') }}</span>
                                        <i class="fas fa-external-link-alt text-[9px] opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                    </Link>
                                    <p class="text-gray-500 text-[11px] font-mono mt-0.5">{{ formatDate(t.created_at) }}</p>
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2 text-xs font-semibold text-white">
                                        <span class="px-2.5 py-1 rounded-lg bg-gray-800 border border-white/10 text-gray-300">{{ t.from_branch }}</span>
                                        <i class="fas fa-long-arrow-alt-right text-violet-400"></i>
                                        <span class="px-2.5 py-1 rounded-lg bg-violet-950 text-violet-300 border border-violet-500/20">{{ t.to_branch }}</span>
                                    </div>
                                    <p v-if="t.notes" class="text-gray-500 text-[11px] mt-1 italic">{{ t.notes }}</p>
                                </td>

                                <td class="px-5 py-4 min-w-[200px]">
                                    <ul class="space-y-1 text-xs">
                                        <li v-for="item in t.items" :key="item.id" class="flex items-center gap-2">
                                            <span class="font-bold text-violet-300 font-mono">{{ item.quantity_sent }}x</span>
                                            <span class="text-gray-200">{{ item.product?.name || 'Producto #' + item.product_id }}</span>
                                        </li>
                                    </ul>
                                </td>

                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <div class="inline-flex flex-col items-center gap-1">
                                        <span
                                            :class="[
                                                'px-2.5 py-1 rounded-full text-xs font-semibold border inline-flex items-center gap-1.5',
                                                getStatusBadge(t.status).class
                                            ]"
                                        >
                                            <i :class="['fas', getStatusBadge(t.status).icon, 'text-[10px]']"></i>
                                            {{ getStatusBadge(t.status).label }}
                                        </span>
                                        <span v-if="t.status === 'recibido' && hasShortages(t)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30 inline-flex items-center gap-1" title="Discrepancia en recepción">
                                            <i class="fas fa-exclamation-triangle text-[9px]"></i> c/ Faltantes
                                        </span>
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-xs whitespace-nowrap">
                                    <p class="font-medium text-gray-200">{{ t.creator?.username || '—' }}</p>
                                    <p v-if="t.receiver" class="text-gray-500 text-[10px]">Recibido por: {{ t.receiver.username }}</p>
                                </td>

                                 <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <div class="relative inline-block text-left action-menu-container">
                                        <!-- Botón Único Acción -->
                                        <button
                                            type="button"
                                            @click.stop="toggleActionMenu(t.id)"
                                            class="px-3.5 py-1.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-200 hover:text-white font-semibold text-xs border border-white/10 transition-all inline-flex items-center gap-2 cursor-pointer shadow-sm active:scale-95"
                                            :class="{ 'bg-gray-700 text-white ring-2 ring-violet-500/50': openDropdownId === t.id }"
                                            title="Ver opciones de acción"
                                        >
                                            <span>Acción</span>
                                            <i class="fas fa-chevron-down text-[9px] text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': openDropdownId === t.id }"></i>
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
                                                v-if="openDropdownId === t.id"
                                                class="absolute right-0 w-48 rounded-xl bg-gray-900/95 border border-white/10 shadow-2xl p-1.5 z-50 flex flex-col gap-1 backdrop-blur-md text-left"
                                                :class="(index >= transfers.data.length - 2 && transfers.data.length > 2) ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                            >
                                                <!-- Verificar y Recibir (si aplica) -->
                                                <Link
                                                    v-if="canReceive(t)"
                                                    :href="route('branch-transfers.show', t.id)"
                                                    class="px-3 py-2 rounded-lg text-xs font-semibold text-emerald-300 hover:text-white hover:bg-emerald-600/20 flex items-center gap-2.5 transition-colors cursor-pointer"
                                                    @click="closeActionMenu"
                                                >
                                                    <i class="fas fa-boxes text-emerald-400 text-xs w-4 text-center"></i>
                                                    <span>Verificar y Recibir</span>
                                                </Link>

                                                <!-- Detalle Boleta (si no es canReceive) -->
                                                <Link
                                                    v-else
                                                    :href="route('branch-transfers.show', t.id)"
                                                    class="px-3 py-2 rounded-lg text-xs font-semibold text-violet-300 hover:text-white hover:bg-violet-600/20 flex items-center gap-2.5 transition-colors cursor-pointer"
                                                    @click="closeActionMenu"
                                                >
                                                    <i class="fas fa-file-invoice text-violet-400 text-xs w-4 text-center"></i>
                                                    <span>Detalle Boleta</span>
                                                </Link>

                                                <!-- Acta de Faltantes (PDF) si tiene faltantes -->
                                                <a
                                                    v-if="t.status === 'recibido' && hasShortages(t)"
                                                    :href="route('branch-transfers.shortage-pdf', t.id)"
                                                    target="_blank"
                                                    class="px-3 py-2 rounded-lg text-xs font-semibold text-rose-300 hover:text-white hover:bg-rose-600/20 flex items-center gap-2.5 transition-colors cursor-pointer"
                                                    @click="closeActionMenu"
                                                >
                                                    <i class="fas fa-file-alt text-rose-400 text-xs w-4 text-center"></i>
                                                    <span>Acta Faltantes (PDF)</span>
                                                </a>
                                            </div>
                                        </Transition>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="transfers.data?.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-exchange-alt text-3xl mb-2 block text-gray-700"></i>
                                    No hay traslados entre sucursales registrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="transfers.links?.length > 3" class="flex items-center justify-between px-6 py-4 border-t border-white/5 bg-gray-950/20">
                    <span class="text-xs text-gray-500">Mostrando {{ transfers.from }} - {{ transfers.to }} de {{ transfers.total }}</span>
                    <div class="flex gap-1">
                        <template v-for="(link, i) in transfers.links" :key="i">
                            <Link v-if="link.url" :href="link.url" v-html="link.label"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                :class="link.active ? 'bg-violet-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-white'" />
                            <span v-else v-html="link.label" class="px-3 py-1.5 rounded-lg text-xs text-gray-700"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ MODAL: NUEVO TRASLADO ENTRE SUCURSALES ═════════════════════════════ -->
        <Transition name="fade">
            <div v-if="showModal" @click.self="closeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm overflow-y-auto">
                <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-xl my-8 overflow-hidden">
                    <!-- Header del Modal -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/40">
                        <h3 class="text-white font-semibold flex items-center gap-2">
                            <i class="fas fa-paper-plane text-violet-400"></i>
                            Nuevo Traslado entre Sucursales
                        </h3>

                        <button
                            @click="closeModal"
                            title="Cerrar modal (X)"
                            class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-all"
                        >
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Origen *</label>
                                <select
                                    v-model="form.from_branch"
                                    required
                                    :disabled="originBranches.length === 1"
                                    class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-3 py-2.5 text-white text-sm outline-none transition-colors disabled:opacity-60"
                                >
                                    <option v-for="b in originBranches" :key="'from-' + b" :value="b">
                                        {{ b }}
                                    </option>
                                </select>
                                <p v-if="!userIsSuperAdmin" class="text-gray-500 text-[10px] mt-0.5">Origen fijado a tu sucursal</p>
                            </div>
                            <div>
                                <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Destino *</label>
                                <select
                                    v-model="form.to_branch"
                                    required
                                    class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-3 py-2.5 text-white text-sm outline-none transition-colors"
                                >
                                    <option
                                        v-for="b in availableBranches"
                                        :key="'to-' + b"
                                        :value="b"
                                        :disabled="b === form.from_branch"
                                        :class="{ 'opacity-40 italic text-gray-600': b === form.from_branch }"
                                    >
                                        {{ b }} {{ b === form.from_branch ? '(Origen actual - no permitido)' : '' }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Advertencia si origen y destino coinciden -->
                        <div v-if="isSameBranch" class="p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-xs font-semibold flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle text-red-400"></i>
                            La sucursal de destino no puede ser la misma que la sucursal de origen.
                        </div>

                        <!-- Items Builder -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-gray-400 text-xs font-medium uppercase tracking-wide">Productos a Enviar *</label>
                                <button type="button" @click="addItem" class="text-xs text-violet-400 hover:text-violet-300 font-semibold flex items-center gap-1">
                                    <i class="fas fa-plus"></i> Agregar Fila
                                </button>
                            </div>

                            <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                                <div v-for="(item, idx) in form.items" :key="idx" class="flex gap-2 items-center">
                                    <select
                                        v-model="item.product_id"
                                        required
                                        class="flex-1 bg-gray-800 border border-white/10 rounded-xl px-3 py-2 text-white text-xs outline-none focus:border-violet-500"
                                    >
                                        <option value="" disabled>Selecciona producto...</option>
                                        <option v-for="p in productList" :key="p.id" :value="p.id">
                                            {{ p.name }} (Stock: {{ p.stock }})
                                        </option>
                                    </select>

                                    <input
                                        v-model.number="item.quantity"
                                        type="number" min="1" required placeholder="Cant."
                                        class="w-24 bg-gray-800 border border-white/10 rounded-xl px-3 py-2 text-white text-xs text-center outline-none focus:border-violet-500"
                                    />

                                    <button type="button" @click="removeItem(idx)" :disabled="form.items.length === 1"
                                        class="w-8 h-8 rounded-lg bg-gray-800 hover:bg-red-600/30 text-gray-400 hover:text-red-400 transition-colors flex items-center justify-center flex-shrink-0 disabled:opacity-30">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Notas del Traslado</label>
                            <textarea v-model="form.notes" rows="2" placeholder="Observaciones de envío..."
                                class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors"></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-white/5">
                            <button type="button" @click="closeModal" class="px-4 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm font-semibold transition-colors">
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing || isSameBranch"
                                class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold text-sm shadow-lg shadow-violet-900/30 transition-all flex items-center gap-2"
                            >
                                <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                                <i v-else class="fas fa-paper-plane"></i>
                                {{ form.processing ? 'Enviando...' : 'Enviar Traslado' }}
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