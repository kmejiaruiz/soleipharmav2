<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    logs: Object,
    branches: Array,
    isSuperAdmin: Boolean,
    filters: Object,
});

const search = ref(props.filters?.q || '');
const selectedType = ref(props.filters?.type || '');
const selectedBranch = ref(props.filters?.branch || (props.isSuperAdmin ? 'all' : ''));

watch([search, selectedType, selectedBranch], () => {
    router.get(
        route('inventory-logs.index'),
        {
            q: search.value,
            type: selectedType.value,
            branch: selectedBranch.value,
        },
        { preserveState: true, replace: true }
    );
});

function getTypeBadge(type) {
    switch (type) {
        case 'stock_increase':
            return { label: 'Entrada / Incremento', class: 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30', icon: 'fa-arrow-up' };
        case 'stock_decrease':
            return { label: 'Baja / Salida', class: 'bg-red-500/15 text-red-300 border-red-500/30', icon: 'fa-arrow-down' };
        case 'supplier_entry':
            return { label: 'Entrada Mercadería', class: 'bg-blue-500/15 text-blue-300 border-blue-500/30', icon: 'fa-truck-loading' };
        default:
            return { label: 'Edición Manual', class: 'bg-amber-500/15 text-amber-300 border-amber-500/30', icon: 'fa-edit' };
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleString('es-NI', {
        dateStyle: 'short',
        timeStyle: 'short',
    });
}
</script>

<template>
    <Head title="Movimientos de Inventario" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                        <i class="fas fa-boxes text-violet-400"></i> Movimientos de Inventario
                    </h1>
                    <p class="text-xs text-gray-400 mt-0.5">Historial y auditoría completa de cambios de stock por sucursal</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Filtros de búsqueda -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="relative flex-1 max-w-md">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por producto, SKU, usuario o descripción..."
                        class="w-full pl-11 pr-4 py-2.5 bg-gray-900 border border-white/10 focus:border-violet-500 rounded-xl text-white placeholder-gray-600 text-sm outline-none transition-colors"
                    />
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto flex-wrap sm:flex-nowrap">
                    <!-- Filtro por Sucursal -->
                    <select
                        v-if="isSuperAdmin"
                        v-model="selectedBranch"
                        class="bg-gray-900 border border-white/10 text-gray-300 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-violet-500 transition-colors w-full sm:w-auto"
                    >
                        <option value="all">Todas las sucursales</option>
                        <option v-for="b in (branches || ['Sucursal Managua', 'Sucursal Leon'])" :key="b" :value="b">
                            {{ b }}
                        </option>
                    </select>

                    <select
                        v-model="selectedType"
                        class="bg-gray-900 border border-white/10 text-gray-300 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-violet-500 transition-colors w-full sm:w-auto"
                    >
                        <option value="">Todos los tipos de movimiento</option>
                        <option value="stock_increase">Incrementos de Stock</option>
                        <option value="stock_decrease">Bajas / Salidas</option>
                        <option value="supplier_entry">Entrada de Mercadería</option>
                        <option value="edit">Ediciones Manuales</option>
                    </select>
                </div>
            </div>

            <!-- Tabla de movimientos -->
            <div class="bg-gray-900 rounded-2xl border border-white/5 overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-white/5 bg-gray-950/40">
                                <th class="px-5 py-4 whitespace-nowrap">Fecha & Hora</th>
                                <th class="px-5 py-4 whitespace-nowrap">Producto</th>
                                <th class="px-4 py-4 text-center whitespace-nowrap">Tipo de Movimiento</th>
                                <th class="px-4 py-4 text-right whitespace-nowrap">Stock Anterior</th>
                                <th class="px-4 py-4 text-right whitespace-nowrap">Nuevo Stock</th>
                                <th class="px-5 py-4 whitespace-nowrap">Usuario / Sucursal</th>
                                <th class="px-5 py-4">Descripción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-gray-300">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-white/2 transition-colors">
                                <td class="px-5 py-4 text-xs font-mono text-gray-400 whitespace-nowrap">
                                    {{ formatDate(log.created_at) }}
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-white text-sm">{{ log.product?.name || '—' }}</p>
                                    <p v-if="log.product?.barcode" class="text-gray-500 text-xs font-mono">SKU: {{ log.product.barcode }}</p>
                                </td>
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-full text-xs font-semibold border inline-flex items-center gap-1.5',
                                            getTypeBadge(log.change_type).class
                                        ]"
                                    >
                                        <i :class="['fas', getTypeBadge(log.change_type).icon, 'text-[10px]']"></i>
                                        {{ getTypeBadge(log.change_type).label }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right font-mono text-sm text-gray-400 whitespace-nowrap">
                                    {{ log.previous_stock ?? '—' }}
                                </td>
                                <td class="px-4 py-4 text-right font-mono text-sm font-bold text-white whitespace-nowrap">
                                    {{ log.new_stock ?? '—' }}
                                </td>
                                <td class="px-5 py-4 text-xs whitespace-nowrap">
                                    <p class="font-medium text-gray-200">{{ log.admin_name }}</p>
                                    <p class="text-violet-400 text-[11px] font-semibold flex items-center gap-1 mt-0.5">
                                        <i class="fas fa-store text-[9px] text-violet-400"></i>
                                        {{ log.branch || log.product?.branch || 'Sucursal Managua' }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 text-xs text-gray-400 max-w-xs truncate" :title="log.description">
                                    {{ log.description || 'Sin detalle' }}
                                </td>
                            </tr>

                            <tr v-if="logs.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-history text-3xl mb-2 block text-gray-700"></i>
                                    No se encontraron movimientos de inventario registrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="logs.links.length > 3" class="px-6 py-4 border-t border-white/5 flex items-center justify-between">
                    <p class="text-xs text-gray-400">
                        Mostrando <span class="font-bold text-white">{{ logs.from ?? 0 }}</span> a <span class="font-bold text-white">{{ logs.to ?? 0 }}</span> de <span class="font-bold text-white">{{ logs.total }}</span> movimientos
                    </p>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, idx) in logs.links" :key="idx">
                            <Component
                                :is="link.url ? Link : 'span'"
                                :href="link.url"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                                :class="[
                                    link.active
                                        ? 'bg-violet-600 text-white'
                                        : link.url
                                            ? 'bg-gray-800 text-gray-400 hover:text-white hover:bg-gray-700'
                                            : 'text-gray-600 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
