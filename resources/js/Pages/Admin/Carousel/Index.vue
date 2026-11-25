<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    carousels: Array,
});

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    title: '',
    description: '',
    image: null,
    active: true,
});

const previewUrl = ref(null);

function openCreateModal() {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.active = true;
    previewUrl.value = null;
    showModal.value = true;
}

function openEditModal(item) {
    isEditing.value = true;
    editingId.value = item.id;
    form.clearErrors();
    form.title = item.title || '';
    form.description = item.description || '';
    form.image = null;
    form.active = Boolean(item.active);
    previewUrl.value = item.image_url || null;
    showModal.value = true;
}

function handleImageChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        previewUrl.value = URL.createObjectURL(file);
    }
}

function submitForm() {
    if (isEditing.value && editingId.value) {
        form.post(route('carousel.update', editingId.value), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
                previewUrl.value = null;
            },
        });
    } else {
        form.post(route('carousel.store'), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
                previewUrl.value = null;
            },
        });
    }
}

function toggleStatus(id) {
    router.patch(route('carousel.toggle', id), {}, { preserveScroll: true });
}

function deleteBanner(id) {
    if (confirm('¿Estás seguro de eliminar este banner del carousel?')) {
        router.delete(route('carousel.destroy', id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Gestionar Carousel" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                        <i class="fas fa-images text-violet-400"></i> Gestionar Carousel
                    </h1>
                    <p class="text-xs text-gray-400 mt-0.5">Banners e imágenes publicitarias para la pantalla de clientes y bloqueo</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white font-semibold text-xs shadow-lg shadow-violet-600/20 transition-all flex items-center gap-2 cursor-pointer"
                >
                    <i class="fas fa-plus"></i> Agregar Banner
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Grid de Banners -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="item in carousels"
                    :key="item.id"
                    class="bg-gray-900 border border-white/5 rounded-2xl overflow-hidden shadow-xl group hover:border-violet-500/30 transition-all flex flex-col"
                >
                    <div class="relative h-48 bg-gray-950 overflow-hidden flex items-center justify-center">
                        <img
                            :src="item.image_url"
                            :alt="item.title"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            @error="$event.target.style.display = 'none'"
                        />
                        <div class="absolute top-3 right-3 flex items-center gap-2">
                            <span
                                :class="[
                                    'px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wide uppercase shadow-lg backdrop-blur-md',
                                    item.active
                                        ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
                                        : 'bg-red-500/20 text-red-300 border border-red-500/30'
                                ]"
                            >
                                {{ item.active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                    </div>

                    <div class="p-4 flex-1 flex flex-col justify-between space-y-4">
                        <div>
                            <h3 class="font-bold text-white text-base leading-tight">{{ item.title || 'Sin título' }}</h3>
                            <p class="text-gray-400 text-xs mt-1.5 line-clamp-2 leading-relaxed">{{ item.description || 'Sin descripción' }}</p>
                        </div>

                        <div class="pt-3 border-t border-white/5 flex items-center justify-between gap-2">
                            <button
                                @click="toggleStatus(item.id)"
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5 cursor-pointer',
                                    item.active
                                        ? 'bg-amber-500/10 text-amber-300 hover:bg-amber-500/20 border border-amber-500/20'
                                        : 'bg-emerald-500/10 text-emerald-300 hover:bg-emerald-500/20 border border-emerald-500/20'
                                ]"
                            >
                                <i :class="['fas', item.active ? 'fa-eye-slash' : 'fa-eye']"></i>
                                {{ item.active ? 'Desactivar' : 'Activar' }}
                            </button>

                            <div class="flex items-center gap-1.5">
                                <button
                                    @click="openEditModal(item)"
                                    class="px-3 py-1.5 rounded-lg bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 text-xs font-semibold border border-blue-500/20 transition-colors flex items-center gap-1.5 cursor-pointer"
                                    title="Editar Banner"
                                >
                                    <i class="fas fa-edit"></i> Editar
                                </button>

                                <button
                                    @click="deleteBanner(item.id)"
                                    class="px-3 py-1.5 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 text-xs font-semibold border border-red-500/20 transition-colors flex items-center gap-1.5 cursor-pointer"
                                    title="Eliminar Banner"
                                >
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="carousels.length === 0" class="col-span-full py-16 text-center bg-gray-900 rounded-2xl border border-white/5">
                    <i class="fas fa-images text-4xl text-gray-700 mb-3"></i>
                    <p class="text-gray-400 text-sm font-semibold">No hay banners registrados en el carousel.</p>
                    <p class="text-gray-600 text-xs mt-1">Haz clic en "Agregar Banner" para subir una imagen publicitaria.</p>
                </div>
            </div>

            <!-- Modal Crear / Editar Banner -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
                <div class="bg-gray-900 border border-white/10 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5">
                    <div class="flex items-center justify-between border-b border-white/5 pb-3">
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <i :class="['fas text-violet-400', isEditing ? 'fa-edit' : 'fa-plus']"></i>
                            {{ isEditing ? 'Editar Banner del Carousel' : 'Nuevo Banner para Carousel' }}
                        </h3>
                        <button @click="showModal = false" class="text-gray-500 hover:text-white transition-colors cursor-pointer">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Título de la Promoción</label>
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="Ej: 2x1 en marca adiuvo"
                                class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors"
                            />
                            <p v-if="form.errors.title" class="text-red-400 text-xs mt-1">{{ form.errors.title }}</p>
                        </div>

                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Descripción Detallada</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                placeholder="Ej: ¡Por la compra de un producto de la marca adiuvo llévate otro producto gratis!"
                                class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors leading-relaxed"
                            ></textarea>
                            <p v-if="form.errors.description" class="text-red-400 text-xs mt-1">{{ form.errors.description }}</p>
                        </div>

                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">
                                Imagen Publicitaria {{ isEditing ? '(Opcional para conservar la actual)' : '*' }}
                            </label>
                            <input
                                type="file"
                                accept="image/*"
                                @change="handleImageChange"
                                class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-2 text-gray-300 text-xs file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-violet-600 file:text-white hover:file:bg-violet-500 transition-colors cursor-pointer"
                            />
                            <p v-if="form.errors.image" class="text-red-400 text-xs mt-1">{{ form.errors.image }}</p>
                        </div>

                        <div v-if="previewUrl" class="mt-2 rounded-xl overflow-hidden border border-white/10 h-32 bg-gray-950 relative group">
                            <img :src="previewUrl" class="w-full h-full object-cover" />
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-medium">
                                Vista previa de imagen
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input
                                id="active"
                                v-model="form.active"
                                type="checkbox"
                                class="w-4 h-4 rounded border-white/10 bg-gray-800 text-violet-600 focus:ring-violet-500 cursor-pointer"
                            />
                            <label for="active" class="text-xs text-gray-300 font-medium cursor-pointer">Banner Activo en Pantalla</label>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-white/5">
                            <button
                                type="button"
                                @click="showModal = false"
                                class="px-4 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs font-semibold transition-colors cursor-pointer"
                            >
                                Cancelar
                            </button>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-xs font-semibold shadow-lg shadow-violet-600/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
                            >
                                <i :class="['fas', isEditing ? 'fa-check' : 'fa-save']"></i>
                                {{ isEditing ? 'Guardar Cambios' : 'Guardar Banner' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
