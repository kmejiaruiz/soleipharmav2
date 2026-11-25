<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    suppliers: Object,
    filters:   Object,
});

const search = ref(props.filters?.q || '');
let searchTimer = null;

watch(search, (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route('suppliers.index'), { q: val || undefined }, { preserveState: true, replace: true });
    }, 300);
});

// Modales
const showModal        = ref(false);
const editingSupplier  = ref(null);

const form = useForm({
    id:           null,
    name:         '',
    contact_name: '',
    phone:        '',
    email:        '',
    address:      '',
    notes:        '',
});

function openCreateModal() {
    editingSupplier.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEditModal(supplier) {
    editingSupplier.value = supplier;
    form.clearErrors();
    form.id           = supplier.id;
    form.name         = supplier.name;
    form.contact_name = supplier.contact_name || '';
    form.phone        = supplier.phone || '';
    form.email        = supplier.email || '';
    form.address      = supplier.address || '';
    form.notes        = supplier.notes || '';
    showModal.value   = true;
}

function closeModal() {
    showModal.value       = false;
    editingSupplier.value = null;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    if (editingSupplier.value) {
        form.put(route('suppliers.update', editingSupplier.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('suppliers.store'), {
            onSuccess: () => closeModal(),
        });
    }
}

function confirmDelete(supplier) {
    if (confirm(`¿Estás seguro de eliminar el proveedor "${supplier.name}"?`)) {
        router.delete(route('suppliers.destroy', supplier.id));
    }
}

function handleKeydown(e) {
    if (e.key === 'Escape' && showModal.value) closeModal();
}

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));
</script>

<template>
    <Head title="Proveedores — Farmacia Solei" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-truck text-violet-400 text-xl"></i>
                    <h1 class="text-white font-semibold text-lg">Directorio de Proveedores</h1>
                    <span class="text-xs bg-white/5 text-gray-400 px-2.5 py-1 rounded-full border border-white/5">
                        {{ suppliers.total }} registros
                    </span>
                </div>

                <button
                    @click="openCreateModal"
                    class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white font-semibold text-sm shadow-lg shadow-violet-900/30 transition-all flex items-center gap-2"
                >
                    <i class="fas fa-plus"></i>
                    <span>Nuevo Proveedor</span>
                </button>
            </div>
        </template>

        <!-- Search Bar -->
        <div class="mb-6">
            <div class="relative max-w-md">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar proveedor por nombre, contacto o teléfono..."
                    class="w-full pl-11 pr-4 py-2.5 bg-gray-900 border border-white/10 focus:border-violet-500 rounded-xl text-white placeholder-gray-600 text-sm outline-none transition-colors"
                />
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-gray-900 rounded-2xl border border-white/5 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-white/5 bg-gray-950/40">
                            <th class="px-6 py-4">Proveedor</th>
                            <th class="px-4 py-4">Contacto</th>
                            <th class="px-4 py-4">Teléfono / Email</th>
                            <th class="px-4 py-4 text-center">Productos</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-gray-300">
                        <tr v-for="s in suppliers.data" :key="s.id" class="hover:bg-white/2 transition-colors">
                            <td class="px-6 py-4">
                                <p class="text-white font-semibold text-sm">{{ s.name }}</p>
                                <p v-if="s.address" class="text-gray-500 text-xs truncate max-w-xs">{{ s.address }}</p>
                            </td>
                            <td class="px-4 py-4 text-gray-300">
                                {{ s.contact_name || '—' }}
                            </td>
                            <td class="px-4 py-4 text-xs">
                                <p v-if="s.phone" class="text-white"><i class="fas fa-phone text-gray-600 mr-1"></i>{{ s.phone }}</p>
                                <p v-if="s.email" class="text-gray-500"><i class="fas fa-envelope text-gray-600 mr-1"></i>{{ s.email }}</p>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="px-2.5 py-1 rounded-full bg-violet-500/15 text-violet-300 text-xs font-semibold border border-violet-500/20">
                                    {{ s.products_count ?? 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <Link
                                    :href="route('suppliers.catalog', s.id)"
                                    class="inline-flex w-8 h-8 rounded-lg bg-gray-800 hover:bg-violet-600/30 text-gray-400 hover:text-violet-300 transition-colors items-center justify-center"
                                    title="Gestionar catálogo de productos"
                                >
                                    <i class="fas fa-boxes text-xs"></i>
                                </Link>
                                <button
                                    @click="openEditModal(s)"
                                    class="w-8 h-8 rounded-lg bg-gray-800 hover:bg-violet-600/30 text-gray-400 hover:text-violet-300 transition-colors"
                                    title="Editar proveedor"
                                >
                                    <i class="fas fa-pen text-xs"></i>
                                </button>
                                <button
                                    @click="confirmDelete(s)"
                                    class="w-8 h-8 rounded-lg bg-gray-800 hover:bg-red-600/30 text-gray-400 hover:text-red-400 transition-colors"
                                    title="Eliminar proveedor"
                                >
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </td>
                        </tr>

                        <tr v-if="!suppliers.data.length">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-600">
                                <i class="fas fa-truck text-4xl mb-3 text-gray-700"></i>
                                <p class="text-sm font-medium">No se encontraron proveedores</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div v-if="suppliers.links?.length > 3" class="flex items-center justify-between px-6 py-4 border-t border-white/5 bg-gray-950/20">
                <span class="text-xs text-gray-500">Mostrando {{ suppliers.from }} - {{ suppliers.to }} de {{ suppliers.total }}</span>
                <div class="flex gap-1">
                    <template v-for="(link, i) in suppliers.links" :key="i">
                        <Link v-if="link.url" :href="link.url" v-html="link.label"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                            :class="link.active ? 'bg-violet-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-white'" />
                        <span v-else v-html="link.label" class="px-3 py-1.5 rounded-lg text-xs text-gray-700"></span>
                    </template>
                </div>
            </div>
        </div>

        <!-- ══ MODAL: CREAR / EDITAR PROVEEDOR ════════════════════════════════ -->
        <Transition name="fade">
        <div v-if="showModal" @click.self="closeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
                <!-- Header del Modal -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/40">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <i :class="['fas text-violet-400', editingSupplier ? 'fa-pen' : 'fa-plus']"></i>
                        {{ editingSupplier ? 'Editar Proveedor' : 'Nuevo Proveedor' }}
                    </h3>

                    <!-- Botón X para cerrar modal -->
                    <button
                        @click="closeModal"
                        title="Cerrar modal (X)"
                        class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-all"
                    >
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Nombre / Empresa *</label>
                        <input v-model="form.name" type="text" required placeholder="Ej: Laboratorios Ramos S.A."
                            class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Persona de contacto</label>
                            <input v-model="form.contact_name" type="text" placeholder="Lic. Juan Pérez"
                                class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors" />
                        </div>
                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Teléfono</label>
                            <input v-model="form.phone" type="text" placeholder="+505 8888-8888"
                                class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Correo Electrónico</label>
                        <input v-model="form.email" type="email" placeholder="ventas@laboratorio.com"
                            class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors" />
                    </div>

                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Dirección</label>
                        <textarea v-model="form.address" rows="2" placeholder="Dirección del proveedor..."
                            class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-white/5">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 font-medium text-sm">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white font-bold text-sm shadow-lg shadow-violet-900/30 transition-all flex items-center gap-2">
                            <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-save"></i>
                            {{ form.processing ? 'Guardando...' : (editingSupplier ? 'Actualizar' : 'Guardar') }}
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
