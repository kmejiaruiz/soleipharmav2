<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    products:  Object,
    suppliers: Array,
    filters:   Object,
});

// ─── Filters & Search ──────────────────────────────────────────────────────────
const search     = ref(props.filters?.q || '');
const status     = ref(props.filters?.status || 'all');
const branch     = ref(props.filters?.branch || '');
const supplierId = ref(props.filters?.supplier_id || '');

let searchTimer = null;
watch([search, status, branch, supplierId], () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route('products.index'), {
            q:           search.value || undefined,
            status:      status.value !== 'all' ? status.value : undefined,
            branch:      branch.value || undefined,
            supplier_id: supplierId.value || undefined,
        }, { preserveState: true, replace: true });
    }, 300);
});

// ─── Modals State ──────────────────────────────────────────────────────────────
const showFormModal   = ref(false);
const showToggleModal = ref(false);
const editingProduct  = ref(null);
const togglingProduct = ref(null);

// Formulario de Crear / Editar
const form = useForm({
    id:                   null,
    name:                 '',
    description:          '',
    price:                '',
    cost:                 '',
    purchase_tax_percent: 15,
    utility_percent:      30,
    tax_percent:          15,
    stock:                0,
    barcode:              '',
    branch:               '',
    available:            true,
    reason_unavailable:   '',
    image_url:            '',
});

// Formulario de disponibilidad
const toggleForm = useForm({
    available:          true,
    reason_unavailable: '',
});

// ─── Handlers de Modales ───────────────────────────────────────────────────────
function openCreateModal() {
    editingProduct.value = null;
    form.reset();
    form.clearErrors();
    showFormModal.value = true;
}

function openEditModal(product) {
    editingProduct.value = product;
    form.clearErrors();
    form.id                   = product.id;
    form.name                 = product.name;
    form.description          = product.description || '';
    form.price                = product.price;
    form.cost                 = product.cost;
    form.purchase_tax_percent = product.purchase_tax_percent ?? 15;
    form.utility_percent      = product.utility_percent;
    form.tax_percent          = product.tax_percent;
    form.stock                = product.stock;
    form.barcode              = product.barcode || '';
    form.branch               = product.branch || '';
    form.available            = product.available;
    form.reason_unavailable   = product.reason_unavailable || '';
    form.image_url            = product.image_url || '';
    showFormModal.value       = true;
}

function closeFormModal() {
    showFormModal.value  = false;
    editingProduct.value = null;
    form.reset();
    form.clearErrors();
}

function openToggleModal(product) {
    togglingProduct.value           = product;
    toggleForm.available            = !product.available;
    toggleForm.reason_unavailable   = '';
    toggleForm.clearErrors();
    showToggleModal.value           = true;
}

function closeToggleModal() {
    showToggleModal.value = false;
    togglingProduct.value = null;
    toggleForm.reset();
}

// ─── Submit Actions ────────────────────────────────────────────────────────────
function submitForm() {
    if (editingProduct.value) {
        form.put(route('products.update', editingProduct.value.id), {
            onSuccess: () => closeFormModal(),
        });
    } else {
        form.post(route('products.store'), {
            onSuccess: () => closeFormModal(),
        });
    }
}

function submitToggle() {
    if (!togglingProduct.value) return;
    toggleForm.patch(route('products.toggleAvailability', togglingProduct.value.id), {
        onSuccess: () => closeToggleModal(),
    });
}

function confirmDelete(product) {
    if (confirm(`¿Estás seguro de eliminar el producto "${product.name}"?`)) {
        router.delete(route('products.destroy', product.id));
    }
}

// ─── ESC Key Listener ──────────────────────────────────────────────────────────
function handleKeydown(e) {
    if (e.key === 'Escape') {
        if (showFormModal.value) closeFormModal();
        if (showToggleModal.value) closeToggleModal();
    }
}

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));

const fmtMoney = (n) => 'C$ ' + (parseFloat(n) || 0).toFixed(2);

// ─── Predicción de Precio Sugerido de Venta al Público ────────────────────────
const suggestedPrice = computed(() => {
    const cost = parseFloat(form.cost) || 0;
    const utility = parseFloat(form.utility_percent) || 30;
    const tax = parseFloat(form.tax_percent) || 15;

    const basePrice = cost * (1 + utility / 100);
    const finalPrice = basePrice * (1 + tax / 100);
    return Number(Math.round(finalPrice + 'e2') + 'e-2');
});

function applySuggestedToForm() {
    form.price = suggestedPrice.value;
}
</script>

<template>
    <Head title="Productos — Farmacia Solei" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-pills text-violet-400 text-xl"></i>
                    <h1 class="text-white font-semibold text-lg">Catálogo de Productos</h1>
                    <span class="text-xs bg-white/5 text-gray-400 px-2.5 py-1 rounded-full border border-white/5">
                        {{ products.total }} en total
                    </span>
                </div>

                <button
                    @click="openCreateModal"
                    class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white font-semibold text-sm shadow-lg shadow-violet-900/30 transition-all flex items-center gap-2"
                >
                    <i class="fas fa-plus"></i>
                    <span>Nuevo Producto</span>
                </button>
            </div>
        </template>

        <!-- ── BARRA DE FILTROS ─────────────────────────────────────────────── -->
        <div class="flex flex-col sm:flex-row gap-3 mb-6">
            <!-- Search -->
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar por nombre, código de barras o ID..."
                    class="w-full pl-11 pr-4 py-2.5 bg-gray-900 border border-white/10 focus:border-violet-500 rounded-xl text-white placeholder-gray-600 text-sm outline-none transition-colors"
                />
            </div>

            <!-- Supplier filter -->
            <select
                v-model="supplierId"
                class="bg-gray-900 border border-white/10 text-gray-300 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-violet-500 transition-colors"
            >
                <option value="">Todos los proveedores</option>
                <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">
                    {{ sup.name }}
                </option>
            </select>

            <!-- Status filter -->
            <select
                v-model="status"
                class="bg-gray-900 border border-white/10 text-gray-300 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-violet-500 transition-colors"
            >
                <option value="all">Todos los estados</option>
                <option value="available">Disponibles</option>
                <option value="unavailable">No disponibles</option>
                <option value="low_stock">Stock bajo (≤5)</option>
            </select>
        </div>

        <!-- ── TABLA DE PRODUCTOS ───────────────────────────────────────────── -->
        <div class="bg-gray-900 rounded-2xl border border-white/5 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-white/5 bg-gray-950/40">
                            <th class="px-6 py-4">Producto</th>
                            <th class="px-4 py-4">Código</th>
                            <th class="px-4 py-4 text-right">Precio</th>
                            <th class="px-4 py-4 text-right">Costo</th>
                            <th class="px-4 py-4 text-center">Stock</th>
                            <th class="px-4 py-4 text-center">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-gray-300">
                        <tr v-for="product in products.data" :key="product.id" class="hover:bg-white/2 transition-colors group">
                            <!-- Producto Info -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-10 h-10 rounded-xl bg-gray-800 border border-white/5 overflow-hidden flex items-center justify-center flex-shrink-0">
                                        <img v-if="product.image_url" :src="product.image_url" class="w-full h-full object-cover" :alt="product.name" />
                                        <i v-else class="fas fa-pills text-gray-600 text-base"></i>
                                    </div>
                                    <div>
                                        <p class="text-white font-medium text-sm group-hover:text-violet-300 transition-colors">{{ product.name }}</p>
                                        <p v-if="product.description" class="text-gray-500 text-xs truncate max-w-xs">{{ product.description }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Barcode -->
                            <td class="px-4 py-4 font-mono text-xs text-gray-400">
                                {{ product.barcode || '—' }}
                            </td>

                            <!-- Precio -->
                            <td class="px-4 py-4 text-right font-bold text-emerald-400">
                                {{ fmtMoney(product.price) }}
                            </td>

                            <!-- Costo -->
                            <td class="px-4 py-4 text-right text-gray-400">
                                {{ fmtMoney(product.cost) }}
                            </td>

                            <!-- Stock -->
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold"
                                    :class="product.stock === 0
                                        ? 'bg-red-500/15 text-red-400 border border-red-500/20'
                                        : product.stock <= 5
                                            ? 'bg-amber-500/15 text-amber-400 border border-amber-500/20'
                                            : 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/20'">
                                    <i class="fas fa-box text-xs"></i>
                                    {{ product.stock }}
                                </span>
                            </td>

                            <!-- Estado -->
                            <td class="px-4 py-4 text-center">
                                <button
                                    @click="openToggleModal(product)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold transition-all"
                                    :class="product.available
                                        ? 'bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25 border border-emerald-500/20'
                                        : 'bg-red-500/15 text-red-400 hover:bg-red-500/25 border border-red-500/20'"
                                    :title="product.available ? 'Click para desactivar' : (product.reason_unavailable || 'Click para activar')"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="product.available ? 'bg-emerald-400' : 'bg-red-400'"></span>
                                    {{ product.available ? 'Disponible' : 'Inactivo' }}
                                </button>
                            </td>

                            <!-- Acciones -->
                            <td class="px-6 py-4 text-right space-x-2">
                                <button
                                    @click="openEditModal(product)"
                                    class="w-8 h-8 rounded-lg bg-gray-800 hover:bg-violet-600/30 text-gray-400 hover:text-violet-300 transition-colors"
                                    title="Editar producto"
                                >
                                    <i class="fas fa-pen text-xs"></i>
                                </button>
                                <button
                                    @click="confirmDelete(product)"
                                    class="w-8 h-8 rounded-lg bg-gray-800 hover:bg-red-600/30 text-gray-400 hover:text-red-400 transition-colors"
                                    title="Eliminar producto"
                                >
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="!products.data.length">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-600">
                                <i class="fas fa-pills text-4xl mb-3 text-gray-700"></i>
                                <p class="text-sm font-medium">No se encontraron productos</p>
                                <p class="text-xs text-gray-600 mt-1">Prueba cambiando los filtros de búsqueda</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div v-if="products.links?.length > 3" class="flex items-center justify-between px-6 py-4 border-t border-white/5 bg-gray-950/20">
                <span class="text-xs text-gray-500">
                    Mostrando {{ products.from }} - {{ products.to }} de {{ products.total }}
                </span>
                <div class="flex gap-1">
                    <template v-for="(link, i) in products.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                            :class="link.active ? 'bg-violet-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-white'"
                        />
                        <span v-else v-html="link.label" class="px-3 py-1.5 rounded-lg text-xs text-gray-700"></span>
                    </template>
                </div>
            </div>
        </div>

        <!-- ══ MODAL: CREAR / EDITAR PRODUCTO ═════════════════════════════════ -->
        <Transition name="fade">
        <div v-if="showFormModal" @click.self="closeFormModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm overflow-y-auto">
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-xl my-8 overflow-hidden">
                <!-- Header del Modal -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/40">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <i :class="['fas text-violet-400', editingProduct ? 'fa-pen' : 'fa-plus']"></i>
                        {{ editingProduct ? 'Editar Producto' : 'Nuevo Producto' }}
                    </h3>

                    <!-- Botón X para cerrar modal -->
                    <button
                        @click="closeFormModal"
                        title="Cerrar modal (X)"
                        class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-all"
                    >
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <!-- Form Body -->
                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <!-- Nombre -->
                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Nombre del producto *</label>
                        <input
                            v-model="form.name"
                            type="text" required placeholder="Ej: Paracetamol 500mg"
                            class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors"
                        />
                        <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>

                    <!-- Código de barras y Stock -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Código de barras (SKU)</label>
                            <input
                                v-model="form.barcode"
                                type="text" placeholder="7501234567890"
                                class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white font-mono text-sm outline-none transition-colors"
                            />
                            <p v-if="form.errors.barcode" class="text-red-400 text-xs mt-1">{{ form.errors.barcode }}</p>
                        </div>
                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide flex items-center justify-between">
                                <span>Stock actual</span>
                                <span class="text-gray-500 text-[10px] lowercase normal-case flex items-center gap-1">
                                    <i class="fas fa-lock text-gray-500"></i> Solo vía Entrada
                                </span>
                            </label>
                            <input
                                v-model.number="form.stock"
                                type="number"
                                readonly
                                disabled
                                class="w-full bg-gray-950/60 border border-white/10 rounded-xl px-4 py-2.5 text-gray-400 font-bold text-sm outline-none cursor-not-allowed"
                            />
                            <p class="text-gray-500 text-[11px] mt-1">El stock se actualiza mediante Entrada de Mercadería.</p>
                        </div>
                    </div>

                    <!-- Costo, % IVA Compra, % Utilidad y % IVA Venta -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Costo Neto (C$)</label>
                            <input
                                v-model.number="form.cost"
                                type="number" step="0.01" min="0" placeholder="0.00"
                                class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-3 py-2.5 text-white text-sm outline-none transition-colors"
                            />
                            <p v-if="form.errors.cost" class="text-red-400 text-xs mt-1">{{ form.errors.cost }}</p>
                        </div>
                        <div>
                            <label class="block text-violet-400 text-xs font-medium mb-1 uppercase tracking-wide">% IVA Compra</label>
                            <input
                                v-model.number="form.purchase_tax_percent"
                                type="number" step="1" min="0" max="100" placeholder="15"
                                class="w-full bg-gray-800 border border-violet-500/30 focus:border-violet-500 rounded-xl px-3 py-2.5 text-violet-300 font-bold text-sm outline-none transition-colors"
                            />
                            <p class="text-gray-600 text-[10px] mt-0.5">IVA del proveedor</p>
                        </div>
                        <div>
                            <label class="block text-amber-400 text-xs font-medium mb-1 uppercase tracking-wide">% Utilidad</label>
                            <input
                                v-model.number="form.utility_percent"
                                type="number" step="1" min="0" max="500" placeholder="30"
                                class="w-full bg-gray-800 border border-white/10 focus:border-amber-500 rounded-xl px-3 py-2.5 text-amber-400 font-bold text-sm outline-none transition-colors"
                            />
                        </div>
                        <div>
                            <label class="block text-violet-400 text-xs font-medium mb-1 uppercase tracking-wide">% IVA Venta</label>
                            <input
                                v-model.number="form.tax_percent"
                                type="number" step="1" min="0" max="100" placeholder="15"
                                class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-3 py-2.5 text-violet-400 font-bold text-sm outline-none transition-colors"
                            />
                            <p class="text-gray-600 text-[10px] mt-0.5">IVA al público</p>
                        </div>
                    </div>

                    <!-- Predicción de Precio Sugerido (Live Preview Box) -->
                    <div v-if="form.cost > 0" class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-amber-300 flex items-center gap-1.5">
                                <i class="fas fa-magic text-amber-400"></i> Precio de Venta Sugerido (Predicción)
                            </p>
                            <p class="text-[11px] text-gray-400 mt-0.5">
                                Costo Neto C$ {{ form.cost }} × (1 + {{ form.utility_percent }}% utilidad) × (1 + {{ form.tax_percent }}% IVA venta)
                            </p>
                            <p class="text-[10px] text-violet-400 mt-1">
                                <i class="fas fa-tag mr-1"></i>Costo c/IVA Compra: C$ {{ ((parseFloat(form.cost)||0) * (1 + (parseFloat(form.purchase_tax_percent)||0)/100)).toFixed(2) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-base font-bold text-amber-300">
                                {{ fmtMoney(suggestedPrice) }}
                            </span>
                            <button
                                type="button"
                                @click="applySuggestedToForm"
                                class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-gray-950 font-bold text-xs shadow transition-all flex items-center gap-1"
                            >
                                <i class="fas fa-check text-[10px]"></i> Usar
                            </button>
                        </div>
                    </div>

                    <!-- Precio de Venta Definido -->
                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Precio de Venta al Público (C$) *</label>
                        <input
                            v-model.number="form.price"
                            type="number" step="0.01" min="0" required placeholder="0.00"
                            class="w-full bg-gray-800 border border-emerald-500/30 focus:border-emerald-500 rounded-xl px-4 py-2.5 text-emerald-400 font-bold text-sm outline-none transition-colors"
                        />
                        <p v-if="form.errors.price" class="text-red-400 text-xs mt-1">{{ form.errors.price }}</p>
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Descripción</label>
                        <textarea
                            v-model="form.description"
                            rows="2" placeholder="Indicaciones, dosis o detalles del producto..."
                            class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors"
                        ></textarea>
                    </div>

                    <!-- URL de Imagen -->
                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">URL de Imagen (opcional)</label>
                        <input
                            v-model="form.image_url"
                            type="url" placeholder="https://ejemplo.com/imagen.jpg"
                            class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors"
                        />
                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-white/5">
                        <button
                            type="button" @click="closeFormModal"
                            class="px-5 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 font-medium text-sm transition-colors"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit" :disabled="form.processing"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white font-bold text-sm shadow-lg shadow-violet-900/30 transition-all flex items-center gap-2"
                        >
                            <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-save"></i>
                            {{ form.processing ? 'Guardando...' : (editingProduct ? 'Actualizar' : 'Guardar') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </Transition>

        <!-- ══ MODAL: CAMBIAR DISPONIBILIDAD ══════════════════════════════════ -->
        <Transition name="fade">
        <div v-if="showToggleModal" @click.self="closeToggleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/40">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <i class="fas fa-toggle-on text-violet-400"></i>
                        Cambiar Estado de Disponibilidad
                    </h3>

                    <!-- Botón X para cerrar modal -->
                    <button
                        @click="closeToggleModal"
                        title="Cerrar modal (X)"
                        class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-all"
                    >
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <form @submit.prevent="submitToggle" class="p-6 space-y-4">
                    <p class="text-gray-300 text-sm">
                        ¿Deseas {{ toggleForm.available ? 'activar' : 'desactivar' }} el producto
                        <strong class="text-white font-semibold">"{{ togglingProduct?.name }}"</strong>?
                    </p>

                    <div v-if="!toggleForm.available">
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Motivo de desactivación</label>
                        <textarea
                            v-model="toggleForm.reason_unavailable"
                            rows="2" required placeholder="Especifica la razón (ej: Agotado en proveedor, retiro de mercado)..."
                            class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-white/5">
                        <button type="button" @click="closeToggleModal" class="px-4 py-2 rounded-xl bg-gray-800 text-gray-300 text-sm">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="toggleForm.processing" class="px-5 py-2 rounded-xl font-bold text-sm text-white"
                            :class="toggleForm.available ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-red-600 hover:bg-red-500'">
                            Confirmar
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
