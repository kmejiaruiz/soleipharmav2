<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    sessions: Object,
});

const fmtMoney = (n) => 'C$ ' + (parseFloat(n) || 0).toFixed(2);

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleString('es-NI', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', hour12: true,
    });
}
</script>

<template>
    <Head title="Historial de Cierres de Caja — Farmacia Solei" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-history text-violet-400 text-xl"></i>
                    <h1 class="text-white font-semibold text-lg">Historial de Cierres de Caja</h1>
                    <span class="text-xs bg-white/5 text-gray-400 px-2.5 py-1 rounded-full border border-white/5">
                        {{ sessions.total }} cierres registrados
                    </span>
                </div>

                <Link :href="route('cash.index')" class="px-4 py-2.5 rounded-xl bg-gray-900 border border-white/10 text-gray-300 hover:text-white text-sm transition-colors flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i> Volver a Caja
                </Link>
            </div>
        </template>

        <!-- Tabla de Cierres -->
        <div class="bg-gray-900 rounded-2xl border border-white/5 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-white/5 bg-gray-950/40">
                            <th class="px-6 py-4"># Sesión</th>
                            <th class="px-4 py-4">Apertura</th>
                            <th class="px-4 py-4">Cierre</th>
                            <th class="px-4 py-4 text-right">Inicial</th>
                            <th class="px-4 py-4 text-right">Esperado</th>
                            <th class="px-4 py-4 text-right">Contado</th>
                            <th class="px-4 py-4 text-right">Diferencia</th>
                            <th class="px-4 py-4">Abierta por</th>
                            <th class="px-4 py-4">Cerrada por</th>
                            <th class="px-6 py-4 text-right">Reporte</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-gray-300">
                        <tr v-for="s in sessions.data" :key="s.id" class="hover:bg-white/2 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs text-violet-400 font-bold">
                                #{{ String(s.id).padStart(6, '0') }}
                            </td>
                            <td class="px-4 py-4 text-xs text-gray-400">{{ formatDate(s.opened_at) }}</td>
                            <td class="px-4 py-4 text-xs text-gray-400">{{ formatDate(s.closed_at) }}</td>
                            <td class="px-4 py-4 text-right font-medium text-gray-300">{{ fmtMoney(s.opening_amount) }}</td>
                            <td class="px-4 py-4 text-right font-medium text-gray-300">{{ fmtMoney(s.closing_count?.expected_amount) }}</td>
                            <td class="px-4 py-4 text-right font-bold text-white">{{ fmtMoney(s.closing_count?.counted_amount) }}</td>
                            <td class="px-4 py-4 text-right font-bold"
                                :class="(s.closing_count?.difference || 0) < 0 ? 'text-red-400' : 'text-emerald-400'">
                                {{ fmtMoney(s.closing_count?.difference) }}
                            </td>
                            <td class="px-4 py-4 text-xs text-gray-300">{{ s.opener?.username }}</td>
                            <td class="px-4 py-4 text-xs text-gray-300">{{ s.closer?.username || '—' }}</td>
                            <td class="px-6 py-4 text-right">
                                <a
                                    :href="route('cash.session.pdf', s.id)"
                                    target="_blank"
                                    class="px-3 py-1.5 rounded-lg bg-violet-600/15 hover:bg-violet-600/25 text-violet-300 border border-violet-500/20 text-xs font-semibold transition-colors inline-flex items-center gap-1.5"
                                    title="Descargar / Imprimir Ticket Térmico en PDF"
                                >
                                    <i class="fas fa-file-pdf text-red-400"></i> Ticket Cierre
                                </a>
                            </td>
                        </tr>

                        <tr v-if="!sessions.data.length">
                            <td colspan="10" class="px-6 py-12 text-center text-gray-600">
                                <i class="fas fa-history text-4xl mb-3 text-gray-700"></i>
                                <p class="text-sm font-medium">No se encontraron sesiones de caja cerradas</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div v-if="sessions.links?.length > 3" class="flex items-center justify-between px-6 py-4 border-t border-white/5 bg-gray-950/20">
                <span class="text-xs text-gray-500">Mostrando {{ sessions.from }} - {{ sessions.to }} de {{ sessions.total }}</span>
                <div class="flex gap-1">
                    <template v-for="(link, i) in sessions.links" :key="i">
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