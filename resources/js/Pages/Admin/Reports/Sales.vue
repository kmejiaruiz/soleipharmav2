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
    sales:   Object,
    summary: Object,
    filters: Object,
});

const dateFrom = ref(props.filters?.date_from || '');
const dateTo   = ref(props.filters?.date_to || '');

function filterReports() {
    router.get(route('reports.sales'), {
        date_from: dateFrom.value || undefined,
        date_to:   dateTo.value || undefined,
    }, { preserveState: true, replace: true });
}

const fmtMoney = (n) => 'C$ ' + (parseFloat(n) || 0).toFixed(2);
const payLabels = { efectivo: '💵 Efectivo', tarjeta: '💳 Tarjeta', transferencia: '🏦 Transferencia' };
</script>

<template>
    <Head title="Reporte de Ventas — Farmacia Solei" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-chart-bar text-violet-400 text-xl"></i>
                    <h1 class="text-white font-semibold text-lg">Reporte de Ventas</h1>
                </div>
            </div>
        </template>

        <!-- Filtro por Fechas -->
        <div class="bg-gray-900 rounded-2xl border border-white/5 p-5 mb-6 flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Fecha Desde</label>
                <input
                    v-model="dateFrom"
                    type="date"
                    class="bg-gray-800 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-violet-500 transition-colors"
                />
            </div>
            <div>
                <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Fecha Hasta</label>
                <input
                    v-model="dateTo"
                    type="date"
                    class="bg-gray-800 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-violet-500 transition-colors"
                />
            </div>
            <button
                @click="filterReports"
                class="px-5 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white font-semibold text-sm transition-all flex items-center gap-2"
            >
                <i class="fas fa-filter"></i>
                <span>Filtrar</span>
            </button>
        </div>

        <!-- Tarjetas de Resumen -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="bg-gray-900 border border-white/5 rounded-2xl p-5 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-teal-500/10"></div>
                <p class="text-gray-400 text-xs font-medium uppercase tracking-wide mb-1">Ingresos Totales</p>
                <p class="text-white text-2xl font-bold">{{ fmtMoney(summary?.totalRevenue) }}</p>
                <p class="text-gray-500 text-xs mt-1">En el rango seleccionado</p>
            </div>
            <div class="bg-gray-900 border border-white/5 rounded-2xl p-5 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-violet-500/10 to-purple-500/10"></div>
                <p class="text-gray-400 text-xs font-medium uppercase tracking-wide mb-1">Total de Transacciones</p>
                <p class="text-white text-2xl font-bold">{{ summary?.totalOrders ?? 0 }}</p>
                <p class="text-gray-500 text-xs mt-1">Ventas completadas</p>
            </div>
            <div class="bg-gray-900 border border-white/5 rounded-2xl p-5 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/10 to-blue-500/10"></div>
                <p class="text-gray-400 text-xs font-medium uppercase tracking-wide mb-1">Ticket Promedio</p>
                <p class="text-white text-2xl font-bold">{{ fmtMoney(summary?.avgTicket) }}</p>
                <p class="text-gray-500 text-xs mt-1">Por cada venta realizada</p>
            </div>
        </div>

        <!-- Tabla de Ventas -->
        <div class="bg-gray-900 rounded-2xl border border-white/5 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-white/5 bg-gray-950/40">
                            <th class="px-6 py-4"># Recibo</th>
                            <th class="px-4 py-4">Fecha/Hora</th>
                            <th class="px-4 py-4">Cajero</th>
                            <th class="px-4 py-4">Método</th>
                            <th class="px-4 py-4 text-center">Items</th>
                            <th class="px-4 py-4 text-right">Total</th>
                            <th class="px-6 py-4 text-center">Imprimir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-gray-300">
                        <tr v-for="s in sales.data" :key="s.id" class="hover:bg-white/2 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs text-violet-400 font-bold">
                                #{{ String(s.id).padStart(6, '0') }}
                            </td>
                            <td class="px-4 py-4 text-xs text-gray-400">{{ formatDate(s?.created_at) }}</td>
                            <td class="px-4 py-4 text-white text-xs font-medium">{{ s.cashier }}</td>
                            <td class="px-4 py-4 text-xs text-gray-300">{{ payLabels[s.payment_method] || s.payment_method }}</td>
                            <td class="px-4 py-4 text-center">
                                <span class="px-2.5 py-1 rounded-full bg-gray-800 text-white font-bold text-xs border border-white/10">
                                    {{ s.items_count }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right font-bold text-emerald-400">{{ fmtMoney(s.total) }}</td>
                            <td class="px-6 py-4 text-center">
                                <a :href="s.receipt_url" target="_blank"
                                   class="px-3 py-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs transition-colors inline-flex items-center gap-1">
                                    <i class="fas fa-print"></i> Recibo
                                </a>
                            </td>
                        </tr>

                        <tr v-if="!sales.data.length">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-600">
                                <i class="fas fa-chart-bar text-4xl mb-3 text-gray-700"></i>
                                <p class="text-sm font-medium">No se encontraron ventas en el rango de fechas</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div v-if="sales.links?.length > 3" class="flex items-center justify-between px-6 py-4 border-t border-white/5 bg-gray-950/20">
                <span class="text-xs text-gray-500">Mostrando {{ sales.from }} - {{ sales.to }} de {{ sales.total }}</span>
                <div class="flex gap-1">
                    <template v-for="(link, i) in sales.links" :key="i">
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
