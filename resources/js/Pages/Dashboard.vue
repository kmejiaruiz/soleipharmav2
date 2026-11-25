<script setup>
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePage, Link } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    lowStock: Array,
    recentSales: Array,
    openSession: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

// Stat card list
const statCards = computed(() => [
    {
        label: 'Ventas de hoy',
        value: `C$ ${props.stats?.salesToday?.toFixed(2) ?? '0.00'}`,
        icon: 'fa-money-bill-wave',
        color: 'from-emerald-500 to-teal-500',
        sub: `${props.stats?.ordersToday ?? 0} transacciones`,
    },
    {
        label: 'Productos en stock',
        value: props.stats?.totalProducts ?? 0,
        icon: 'fa-pills',
        color: 'from-violet-500 to-purple-500',
        sub: `${props.stats?.lowStockCount ?? 0} con stock bajo`,
    },
    {
        label: 'Stock bajo',
        value: props.stats?.lowStockCount ?? 0,
        icon: 'fa-exclamation-triangle',
        color: 'from-amber-500 to-orange-500',
        sub: 'Requieren atención',
    },
    {
        label: 'Caja',
        value: props.openSession ? 'Abierta' : 'Cerrada',
        icon: props.openSession ? 'fa-lock-open' : 'fa-lock',
        color: props.openSession ? 'from-cyan-500 to-blue-500' : 'from-gray-600 to-gray-700',
        sub: props.openSession ? `Por ${props.openSession.opener?.username}` : 'Sin sesión activa',
    },
]);
</script>

<template>
    <Head title="Dashboard — Farmacia Solei" />

    <AdminLayout>
        <template #header>
            <h1 class="text-white font-semibold text-lg">Dashboard</h1>
        </template>

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
            <div
                v-for="card in statCards"
                :key="card.label"
                class="relative overflow-hidden rounded-2xl p-5 bg-gray-900 border border-white/5 hover:border-white/10 transition-all"
            >
                <div :class="`absolute inset-0 opacity-10 bg-gradient-to-br ${card.color}`"></div>
                <div class="relative flex items-start justify-between gap-3">
                    <div>
                        <p class="text-gray-400 text-xs font-medium uppercase tracking-wide mb-1">{{ card.label }}</p>
                        <p class="text-white text-2xl font-bold">{{ card.value }}</p>
                        <p class="text-gray-500 text-xs mt-1">{{ card.sub }}</p>
                    </div>
                    <div :class="`w-10 h-10 rounded-xl flex items-center justify-center bg-gradient-to-br ${card.color} shadow-lg flex-shrink-0`">
                        <i :class="['fas', card.icon, 'text-white text-sm']"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <!-- Últimas ventas -->
            <div class="bg-gray-900 rounded-2xl border border-white/5 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-white/5">
                    <h2 class="text-white font-semibold text-sm flex items-center gap-2">
                        <i class="fas fa-receipt text-violet-400"></i>
                        Últimas ventas
                    </h2>
                    <Link :href="route('reports.sales')" class="text-violet-400 hover:text-violet-300 text-xs transition-colors">
                        Ver todo →
                    </Link>
                </div>
                <div class="divide-y divide-white/5">
                    <div
                        v-for="sale in recentSales"
                        :key="sale.id"
                        class="flex items-center justify-between px-5 py-3 hover:bg-white/2 transition-colors"
                    >
                        <div>
                            <p class="text-white text-sm font-medium">#{{ sale.id }}</p>
                            <p class="text-gray-500 text-xs">{{ sale.user?.username ?? 'Público' }} · {{ sale.created_at }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-emerald-400 font-semibold text-sm">C$ {{ sale.total }}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="sale.status === 'completado' ? 'bg-emerald-500/15 text-emerald-400' : 'bg-amber-500/15 text-amber-400'">
                                {{ sale.status }}
                            </span>
                        </div>
                    </div>
                    <div v-if="!recentSales?.length" class="px-5 py-8 text-center text-gray-600 text-sm">
                        Sin ventas hoy
                    </div>
                </div>
            </div>

            <!-- Stock bajo -->
            <div class="bg-gray-900 rounded-2xl border border-white/5 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-white/5">
                    <h2 class="text-white font-semibold text-sm flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle text-amber-400"></i>
                        Stock bajo
                    </h2>
                    <Link :href="route('products.index')" class="text-violet-400 hover:text-violet-300 text-xs transition-colors">
                        Gestionar →
                    </Link>
                </div>
                <div class="divide-y divide-white/5">
                    <div
                        v-for="product in lowStock"
                        :key="product.id"
                        class="flex items-center justify-between px-5 py-3"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-pills text-gray-500 text-xs"></i>
                            </div>
                            <div>
                                <p class="text-white text-sm font-medium truncate max-w-36">{{ product.name }}</p>
                                <p class="text-gray-500 text-xs">C$ {{ product.price }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold"
                                :class="product.stock === 0 ? 'bg-red-500/15 text-red-400' : 'bg-amber-500/15 text-amber-400'">
                                <i class="fas fa-box text-xs"></i>
                                {{ product.stock }}
                            </span>
                        </div>
                    </div>
                    <div v-if="!lowStock?.length" class="px-5 py-8 text-center text-gray-600 text-sm">
                        Todos los productos tienen stock suficiente
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
