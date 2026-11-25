<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    branches: Array,
});

const showModal = ref(false);

const form = useForm({
    name:    '',
    address: '',
    phone:   '',
});

function openModal() {
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    form.reset();
    form.clearErrors();
}

function submit() {
    form.post(route('branches.store'), {
        onSuccess: () => closeModal(),
    });
}

function deleteBranch(branch) {
    if (!confirm(`¿Eliminar la sucursal "${branch.name}"? Esta acción no se puede deshacer.`)) return;
    router.delete(route('branches.destroy', branch.id));
}
</script>

<template>
    <Head title="Sucursales — Farmacia Solei" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-violet-600/15 border border-violet-500/20 flex items-center justify-center text-violet-400 shrink-0">
                        <i class="fas fa-store text-base"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-white font-bold text-lg leading-tight">Sucursales</h1>
                            <span class="text-xs bg-white/5 text-gray-400 px-2.5 py-0.5 rounded-full border border-white/5">
                                {{ branches.length }} registradas
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">
                            Gestiona las sucursales del sistema y su catálogo de productos
                        </p>
                    </div>
                </div>

                <button
                    @click="openModal"
                    class="shrink-0 whitespace-nowrap inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 via-violet-500 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white font-semibold text-sm shadow-lg shadow-violet-900/25 border border-violet-400/20 active:scale-95 transition-all cursor-pointer"
                >
                    <i class="fas fa-plus text-xs"></i>
                    <span>Nueva Sucursal</span>
                </button>
            </div>
        </template>

        <!-- Cards grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div
                v-for="branch in branches"
                :key="branch.id"
                class="bg-gray-900 border border-white/5 rounded-2xl p-5 shadow-xl hover:border-violet-500/20 transition-all group"
            >
                <!-- Header de la card -->
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-violet-600 to-purple-700 flex items-center justify-center text-white shadow-lg shadow-violet-900/30 shrink-0">
                        <i class="fas fa-store text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-white font-bold text-sm truncate">{{ branch.name }}</h3>
                        <p v-if="branch.address" class="text-gray-400 text-xs truncate mt-0.5">
                            <i class="fas fa-map-marker-alt mr-1"></i>{{ branch.address }}
                        </p>
                        <p v-if="branch.phone" class="text-gray-400 text-xs mt-0.5">
                            <i class="fas fa-phone mr-1"></i>{{ branch.phone }}
                        </p>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 gap-3 py-3 border-t border-b border-white/5 mb-4">
                    <div class="text-center">
                        <div class="text-violet-400 font-bold text-xl">{{ branch.product_count }}</div>
                        <div class="text-gray-500 text-xs uppercase tracking-wider mt-0.5">Productos</div>
                    </div>
                    <div class="text-center">
                        <div class="text-violet-400 font-bold text-xl">{{ branch.user_count }}</div>
                        <div class="text-gray-500 text-xs uppercase tracking-wider mt-0.5">Usuarios</div>
                    </div>
                </div>

                <!-- Acción -->
                <div class="flex justify-end">
                    <button
                        @click="deleteBranch(branch)"
                        :disabled="branch.user_count > 0"
                        :title="branch.user_count > 0 ? 'Tiene usuarios asignados' : 'Eliminar sucursal'"
                        class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg border transition-all cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed text-red-400 border-red-500/20 hover:bg-red-500/10 hover:border-red-400/40"
                    >
                        <i class="fas fa-trash text-[10px]"></i>
                        Eliminar
                    </button>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="!branches.length" class="col-span-full text-center py-20 text-gray-500">
                <i class="fas fa-store text-4xl mb-3 block opacity-30"></i>
                <p class="text-sm">No hay sucursales registradas.</p>
            </div>
        </div>

        <!-- Modal: Nueva Sucursal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeModal">
                <div class="bg-gray-900 border border-white/10 rounded-2xl w-full max-w-md shadow-2xl animate-[fadeSlideUp_0.2s_ease]">
                    <!-- Header modal -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-white/5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-violet-600/15 border border-violet-500/20 flex items-center justify-center text-violet-400">
                                <i class="fas fa-store text-xs"></i>
                            </div>
                            <h2 class="text-white font-bold text-base">Nueva Sucursal</h2>
                        </div>
                        <button @click="closeModal" class="text-gray-500 hover:text-white transition-colors cursor-pointer">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-4">
                        <!-- Nombre -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                                Nombre <span class="text-red-400">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="Ej: Sucursal Granada"
                                class="w-full bg-gray-950/60 border border-white/10 focus:border-violet-500 rounded-xl px-3 py-2.5 text-white placeholder-gray-500 text-sm outline-none transition-colors"
                                :class="{ 'border-red-500/50': form.errors.name }"
                                required
                            />
                            <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>

                        <!-- Dirección -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Dirección</label>
                            <input
                                v-model="form.address"
                                type="text"
                                placeholder="Dirección de la sucursal"
                                class="w-full bg-gray-950/60 border border-white/10 focus:border-violet-500 rounded-xl px-3 py-2.5 text-white placeholder-gray-500 text-sm outline-none transition-colors"
                            />
                        </div>

                        <!-- Teléfono -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Teléfono</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                placeholder="Ej: 2222-3333"
                                class="w-full bg-gray-950/60 border border-white/10 focus:border-violet-500 rounded-xl px-3 py-2.5 text-white placeholder-gray-500 text-sm outline-none transition-colors"
                            />
                        </div>

                        <!-- Info -->
                        <div class="flex items-start gap-2.5 bg-blue-500/5 border border-blue-500/15 rounded-xl px-4 py-3 text-blue-300 text-xs">
                            <i class="fas fa-info-circle mt-0.5 shrink-0"></i>
                            <span>Al crear la sucursal se clonará el catálogo de productos con stock en 0.</span>
                        </div>

                        <!-- Footer -->
                        <div class="flex justify-end gap-3 pt-1">
                            <button type="button" @click="closeModal"
                                class="px-4 py-2 rounded-xl border border-white/10 text-gray-400 hover:text-white hover:border-white/20 text-sm font-medium transition-all cursor-pointer">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white font-semibold text-sm shadow-lg shadow-violet-900/25 border border-violet-400/20 active:scale-95 transition-all cursor-pointer disabled:opacity-50">
                                <i class="fas fa-spinner fa-spin text-xs" v-if="form.processing"></i>
                                <i class="fas fa-check text-xs" v-else></i>
                                Crear Sucursal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
@keyframes fadeSlideUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
