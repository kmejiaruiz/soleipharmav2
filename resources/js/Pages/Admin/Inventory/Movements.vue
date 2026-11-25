<script setup>
function formatDate(dateStr) {
    if (!dateStr) return '\u2014';
    return new Date(dateStr).toLocaleString('es-NI', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', hour12: true,
    });
}

import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    movements: Object,
    filters:   Object,
});

const changeType = ref(props.filters?.change_type || 'all');
const search     = ref(props.filters?.q || '');
let searchTimer  = null;

watch([changeType, search], () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route('inventory.movements'), {
            change_type: changeType.value !== 'all' ? changeType.value : undefined,
            q:           search.value || undefined,
        }, { preserveState: true, replace: true });
    }, 300);
});

const typeBadges = {
    edit:           { label: 'Edición',          class: 'bg-blue-500/15 text-blue-400 border-blue-500/20' },
    stock_increase: { label: 'Aumento de Stock', class: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/20' },
    stock_decrease: { label: 'Disminución',      class: 'bg-red-500/15 text-red-400 border-red-500/20' },
    supplier_entry: { label: 'Entrada Proveedor',class: 'bg-purple-500/15 text-purple-400 border-purple-500/20' },
};
</script>

<template>
    <Head title="Movimientos de Inventario — Farmacia Solei" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-history text-violet-400 text-xl"></i>
                    <h1 class="text-white font-semibold text-lg">Historial de Movimientos de Inventario</h1>
                    <span class="text-xs bg-white/5 text-gray-400 px-2.5 py-1 rounded-full border border-white/5">
                        {{ movements.total }} registros
                    </span>
                </div>
            </div>
        </template>

        <!-- Filtros -->
        <div class="flex flex-col sm:flex-row gap-3 mb-6">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar por producto, usuario o descripción..."
                    class="w-full pl-11 pr-4 py-2.5 bg-gray-900 border border-white/10 focus:border-violet-500 rounded-xl text-white placeholder-gray-600 text-sm outline-none transition-colors"
                />
            </div>

            <select
                v-model="changeType"
                class="bg-gray-900 border border-white/10 text-gray-300 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-violet-500 transition-colors"
            >
                <option value="all">Todos los tipos de cambio</option>
                <option value="stock_increase">Aumento de stock</option>
                <option value="stock_decrease">Disminución de stock</option>
                <option value="edit">Ediciones</option>
                <option value="supplier_entry">Entradas de proveedor</option>
            </select>
        </div>

        <!-- Tabla de Movimientos -->
        <div class="bg-gray-900 rounded-2xl border border-white/5 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-white/5 bg-gray-950/40">
                            <th class="px-6 py-4">Fecha</th>
                            <th class="px-4 py-4">Producto</th>
                            <th class="px-4 py-4 text-center">Tipo</th>
                            <th class="px-4 py-4 text-center">Stock Previo</th>
                            <th class="px-4 py-4 text-center">Stock Nuevo</th>
                            <th class="px-4 py-4">Descripción / Justificación</th>
                            <th class="px-6 py-4 text-right">Usuario</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-gray-300">
                        <tr v-for="m in movements.data" :key="m.id" class="hover:bg-white/2 transition-colors">
                            <td class="px-6 py-4 text-xs text-gray-400 font-mono">
                                {{ formatDate(m?.created_at) }}
                            </td>
                            <td class="px-4 py-4">
                                <p class="text-white font-medium text-sm">{{ m.product?.name || '—' }}</p>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border"
                                    :class="typeBadges[m.change_type]?.class || 'bg-gray-800 text-gray-400 border-white/10'">
                                    {{ typeBadges[m.change_type]?.label || m.change_type }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center font-mono text-gray-400">
                                {{ m.previous_stock ?? '—' }}
                            </td>
                            <td class="px-4 py-4 text-center font-mono font-bold text-white">
                                {{ m.new_stock ?? '—' }}
                            </td>
                            <td class="px-4 py-4 text-xs text-gray-400 max-w-sm">
                                {{ m.description || '—' }}
                            </td>
                            <td class="px-6 py-4 text-right text-xs text-violet-300 font-medium">
                                {{ m.admin_name }}
                            </td>
                        </tr>

                        <tr v-if="!movements.data.length">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-600">
                                <i class="fas fa-history text-4xl mb-3 text-gray-700"></i>
                                <p class="text-sm font-medium">No se encontraron movimientos de inventario</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div v-if="movements.links?.length > 3" class="flex items-center justify-between px-6 py-4 border-t border-white/5 bg-gray-950/20">
                <span class="text-xs text-gray-500">Mostrando {{ movements.from }} - {{ movements.to }} de {{ movements.total }}</span>
                <div class="flex gap-1">
                    <template v-for="(link, i) in movements.links" :key="i">
                        <Link v-if="link.url" :href="link.url" v-html="link.label"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                            :class="link.active ? 'bg-violet-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-white'" />
                        <span v-else v-html="link.label" class="px-3 py-1.5 rounded-lg text-xs text-gray-700"></span>
                    </template>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
