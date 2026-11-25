<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from '@/composables/useToast';
import LockScreenModal from '@/Components/LockScreenModal.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { toasts, show, dismiss } = useToast();

// Auto-show toast on Inertia flash (server redirect with ->with(...))
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) show(flash.success, 'success');
        if (flash?.error)   show(flash.error,   'error');
        if (flash?.warning) show(flash.warning, 'warning');
        if (flash?.info)    show(flash.info,    'info');
    },
    { deep: true }
);

const sidebarOpen = ref(true);
const isTerminalLocked = ref(localStorage.getItem('solei_terminal_locked') === 'true');

function lockTerminal() {
    isTerminalLocked.value = true;
    localStorage.setItem('solei_terminal_locked', 'true');
}

function unlockTerminal() {
    isTerminalLocked.value = false;
    localStorage.removeItem('solei_terminal_locked');
}

// Shortcut Ctrl + L para bloquear terminal de caja
function handleKeydown(e) {
    if (e.ctrlKey && (e.key === 'l' || e.key === 'L')) {
        e.preventDefault();
        lockTerminal();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});

// -- Menú agrupado y ordenado por secciones operativas ------------------------
const menuSections = [
    {
        title: 'PRINCIPAL',
        items: [
            { label: 'Dashboard',                 icon: 'fa-tachometer-alt',      route: 'dashboard',               roles: ['admin','superadmin','cajero'] },
            { label: 'Facturar (POS)',            icon: 'fa-receipt',             route: 'pos.index',                roles: ['admin','superadmin','cajero'] },
        ]
    },
    {
        title: 'CAJA & TRANSACCIONES',
        items: [
            { label: 'Caja',                      icon: 'fa-money-bill-wave',     route: 'cash.index',               roles: ['admin','superadmin','cajero'] },
            { label: 'Historial de Caja',         icon: 'fa-history',             route: 'cash.history',             roles: ['admin','superadmin','cajero'] },
            { label: 'Movimientos de Inventario', icon: 'fa-exchange-alt',        route: 'inventory-logs.index',     roles: ['admin','superadmin'] },
            { label: 'Traslados entre Sucursales', icon: 'fa-dolly-flatbed',       route: 'branch-transfers.index',   roles: ['admin','superadmin'] },
        ]
    },
    {
        title: 'INVENTARIO & PRODUCTOS',
        items: [
            { label: 'Productos',                  icon: 'fa-pills',               route: 'products.index',           roles: ['admin','superadmin'] },
            { label: 'Bodegas',                    icon: 'fa-warehouse',           route: 'bodega.index',             roles: ['admin','superadmin'] },
            { label: 'Pedidos de Productos',       icon: 'fa-shopping-cart',       route: 'product-orders.index',     roles: ['admin','superadmin'] },
            { label: 'Solicitudes Pendientes',    icon: 'fa-hourglass-half',      route: 'discards.index',           params: { status: 'pending' }, roles: ['admin','superadmin'] },
            { label: 'Historial de Descartes',    icon: 'fa-trash-alt',           route: 'discards.index',           roles: ['admin','superadmin'] },
        ]
    },
    {
        title: 'PROVEEDORES & REPORTES',
        items: [
            { label: 'Proveedores',               icon: 'fa-truck',               route: 'suppliers.index',          roles: ['admin','superadmin'] },
            { label: 'Reporte de Ventas',          icon: 'fa-chart-bar',           route: 'reports.sales',            roles: ['admin','superadmin'] },
            { label: 'Reporte de Bodega',          icon: 'fa-file-alt',            route: 'reports.inventory',        roles: ['admin','superadmin'] },
        ]
    },
    {
        title: 'ADMINISTRACIÓN & SISTEMA',
        items: [
            { label: 'Gestionar Carousel',        icon: 'fa-images',              route: 'carousel.index',           roles: ['admin','superadmin'] },
            { label: 'Gestión de Usuarios',        icon: 'fa-users-cog',           route: 'users.index',              roles: ['superadmin'] },
            { label: 'Sucursales',                icon: 'fa-store',               route: 'branches.index',           roles: ['superadmin'] },
            { label: 'Usuarios Bloqueados',       icon: 'fa-user-lock',           route: 'users.index',              params: { status: 'locked' },  roles: ['admin', 'superadmin'] },
            { label: 'Mi Perfil',                 icon: 'fa-user-circle',         route: 'profile.edit',             roles: ['admin','superadmin','cajero'] },
        ]
    }
];

const visibleSections = computed(() => {
    return menuSections.map(section => ({
        ...section,
        items: section.items.filter(item => item.roles.includes(user.value?.role))
    })).filter(section => section.items.length > 0);
});

// -- Breadcrumbs divididos dinámicos ------------------------------------------
const breadcrumbs = computed(() => {
    const comp = page.component;

    const items = [
        { label: 'Inicio', route: 'dashboard', icon: 'fa-home' }
    ];

    if (comp === 'Dashboard') {
        items.push({ label: 'Dashboard', route: 'dashboard', active: true });
    } else if (comp === 'Pos/Index' || comp === 'Admin/Cash/Pos') {
        items.push({ label: 'Punto de Venta', route: 'pos.index', active: true });
    } else if (comp === 'Admin/Products/Index') {
        items.push({ label: 'Productos', route: 'products.index', active: true });
    } else if (comp === 'Admin/Suppliers/Index') {
        items.push({ label: 'Proveedores', route: 'suppliers.index', active: true });
    } else if (comp === 'Admin/Suppliers/Catalog') {
        items.push({ label: 'Proveedores', route: 'suppliers.index' });
        items.push({ label: 'Catálogo & Costos por Proveedor', active: true });
    } else if (comp === 'Admin/ProductOrders/Index') {
        items.push({ label: 'Pedidos & Resurtido', route: 'product-orders.index', active: true });
    } else if (comp === 'Admin/ProductOrders/GoodsEntry') {
        items.push({ label: 'Pedidos & Resurtido', route: 'product-orders.index' });
        items.push({ label: 'Entrada de Mercadería', active: true });
    } else if (comp === 'Admin/Discards/Index') {
        items.push({ label: 'Bajas de Productos', route: 'discards.index', active: true });
    } else if (comp === 'Admin/Carousel/Index') {
        items.push({ label: 'Gestionar Carousel', route: 'carousel.index', active: true });
    } else if (comp === 'Admin/InventoryLogs/Index') {
        items.push({ label: 'Movimientos de Inventario', route: 'inventory-logs.index', active: true });
    } else if (comp === 'Admin/Cash/Index') {
        items.push({ label: 'Caja', route: 'cash.index', active: true });
    } else if (comp === 'Admin/Cash/History') {
        items.push({ label: 'Caja', route: 'cash.index' });
        items.push({ label: 'Historial de Arqueos', route: 'cash.history', active: true });
    } else if (comp === 'Admin/BranchTransfers/Index') {
        items.push({ label: 'Traslados entre Sucursales', route: 'branch-transfers.index', active: true });
    } else if (comp === 'Admin/BranchTransfers/Show') {
        items.push({ label: 'Traslados entre Sucursales', route: 'branch-transfers.index' });
        items.push({ label: 'Boleta de Traslado', active: true });
    } else if (comp === 'Admin/Bodega/Index') {
        items.push({ label: 'Bodega', route: 'bodega.index', active: true });
    } else if (comp === 'Admin/Reports/Inventory') {
        items.push({ label: 'Reportes', route: 'reports.sales' });
        items.push({ label: 'Reporte de Bodega', active: true });
    } else if (comp?.startsWith('Admin/Reports')) {
        items.push({ label: 'Reportes', route: 'reports.sales' });
        items.push({ label: 'Ventas', active: true });
    } else if (comp === 'Admin/Users/Index') {
        items.push({ label: 'Usuarios', route: 'users.index', active: true });
    } else if (comp === 'Admin/Branches/Index') {
        items.push({ label: 'Sucursales', route: 'branches.index', active: true });
    } else if (comp === 'Profile/Edit') {
        items.push({ label: 'Mi Perfil', route: 'profile.edit', active: true });
    } else {
        const parts = (comp || '').split('/');
        const lastPart = parts[parts.length - 1] || 'Vista';
        items.push({ label: lastPart, active: true });
    }

    return items;
});

function logout() {
    router.post(route('logout'));
}

// ── Detección de sesión de caja abierta para cajeros ─────────────────────────
const openCashSession = computed(() => page.props.openCashSession ?? null);

// Mostrar modal bloqueante si cajero entra y hay sesión abierta
// Mostrar modal bloqueante si un cajero entra y hay una sesión abierta perteneciente a OTRO usuario
const showCashBlockModal = computed(() => {
    if (user.value?.role !== 'cajero' || !openCashSession.value) return false;
    const sessionOwnerId = openCashSession.value.opened_by ?? openCashSession.value.user_id;
    return Number(sessionOwnerId) !== Number(user.value?.id);
});

// ── Modal de restricción de sucursal vía URL (Sucursal León) ────────────────
const showBranchDeniedModal = ref(false);
const branchDeniedMessage = ref('No tiene permisos para este cambio');

function closeBranchDeniedModal() {
    showBranchDeniedModal.value = false;
    const currentUrl = new URL(window.location.href);
    if (currentUrl.searchParams.has('branch') || currentUrl.searchParams.has('sucursal')) {
        currentUrl.searchParams.delete('branch');
        currentUrl.searchParams.delete('sucursal');
        router.replace({ url: currentUrl.pathname + (currentUrl.search ? currentUrl.search : '') });
    }
}

watch(
    () => page.props.flash?.branchDeniedModal,
    (msg) => {
        if (msg) {
            branchDeniedMessage.value = typeof msg === 'string' ? msg : 'No tiene permisos para este cambio';
            showBranchDeniedModal.value = true;
        }
    },
    { immediate: true }
);

// ── Solicitudes de Acceso a Bodega para Administrador de Sucursal ──
const pendingBodegaRequest = ref(null);
const isRespondingBodega = ref(false);
let bodegaPollInterval = null;

async function checkPendingBodegaRequests() {
    if (user.value?.role !== 'admin' || !user.value?.branch) return;
    if (document.hidden) return;
    try {
        const res = await axios.get(route('bodega.pending-requests'));
        pendingBodegaRequest.value = res.data?.pending || null;
    } catch (e) {
        // Silencio en errores de red
    }
}

async function respondBodegaRequest(decision) {
    if (!pendingBodegaRequest.value || isRespondingBodega.value) return;
    isRespondingBodega.value = true;
    try {
        await axios.post(route('bodega.access-requests.respond', pendingBodegaRequest.value.id), { decision });
        show(decision === 'approved' ? 'Acceso a bodega concedido al Superadmin.' : 'Solicitud de acceso a bodega rechazada.', decision === 'approved' ? 'success' : 'info');
        pendingBodegaRequest.value = null;
    } catch (err) {
        show(err.response?.data?.error || 'Error al responder a la solicitud.', 'error');
    } finally {
        isRespondingBodega.value = false;
    }
}

// ── Control de Pestaña Única (Single Tab Guard) ────────────────────────────
const isDuplicateTab = ref(false);
let tabHeartbeatTimer = null;
let singleTabChannel = null;
const peerTabs = new Map(); // tabId => { createdAt: number, lastSeen: number }

function getTabInfo() {
    let tabId = sessionStorage.getItem('solei_tab_instance_id');
    let createdAt = Number(sessionStorage.getItem('solei_tab_created_at'));
    if (!tabId || !createdAt) {
        tabId = 'tab_' + Date.now() + '_' + Math.random().toString(36).substring(2, 8);
        createdAt = Date.now();
        sessionStorage.setItem('solei_tab_instance_id', tabId);
        sessionStorage.setItem('solei_tab_created_at', String(createdAt));
    }
    return { tabId, createdAt };
}

function evaluatePrimaryTab(myTabId, myCreatedAt) {
    const now = Date.now();
    let hasOlderTab = false;

    for (const [peerId, peer] of peerTabs.entries()) {
        // Descartar peers inactivos por más de 20 segundos
        if (now - peer.lastSeen > 20000) {
            peerTabs.delete(peerId);
            continue;
        }
        // Si hay una pestaña registrada antes que la actual
        if (peer.createdAt < myCreatedAt || (peer.createdAt === myCreatedAt && peerId < myTabId)) {
            hasOlderTab = true;
        }
    }

    isDuplicateTab.value = hasOlderTab;
}

function closeDuplicateTab() {
    window.close();
}

function retryTabActivation() {
    const { tabId, createdAt } = getTabInfo();
    const now = Date.now();
    for (const [peerId, peer] of peerTabs.entries()) {
        if (now - peer.lastSeen > 5000) {
            peerTabs.delete(peerId);
        }
    }
    evaluatePrimaryTab(tabId, createdAt);
    if (isDuplicateTab.value && singleTabChannel) {
        peerTabs.clear();
        singleTabChannel.postMessage({ type: 'PING', tabId, createdAt });
        setTimeout(() => {
            evaluatePrimaryTab(tabId, createdAt);
        }, 500);
    }
}

function initSingleTabGuard() {
    if (typeof window === 'undefined') return;

    const { tabId, createdAt } = getTabInfo();

    if (typeof BroadcastChannel !== 'undefined') {
        singleTabChannel = new BroadcastChannel('solei_single_tab_channel');

        singleTabChannel.onmessage = (event) => {
            const data = event.data;
            if (!data || data.tabId === tabId) return;

            if (data.type === 'PING') {
                peerTabs.set(data.tabId, { createdAt: data.createdAt, lastSeen: Date.now() });
                singleTabChannel.postMessage({
                    type: 'PONG',
                    tabId,
                    createdAt,
                });
                evaluatePrimaryTab(tabId, createdAt);
            } else if (data.type === 'PONG' || data.type === 'HEARTBEAT') {
                peerTabs.set(data.tabId, { createdAt: data.createdAt, lastSeen: Date.now() });
                evaluatePrimaryTab(tabId, createdAt);
            } else if (data.type === 'BYE') {
                peerTabs.delete(data.tabId);
                evaluatePrimaryTab(tabId, createdAt);
            }
        };

        singleTabChannel.postMessage({ type: 'PING', tabId, createdAt });

        tabHeartbeatTimer = setInterval(() => {
            if (singleTabChannel) {
                singleTabChannel.postMessage({ type: 'HEARTBEAT', tabId, createdAt });
            }
            evaluatePrimaryTab(tabId, createdAt);
        }, 3000);
    }

    const onExit = () => {
        if (singleTabChannel) {
            singleTabChannel.postMessage({ type: 'BYE', tabId });
        }
    };

    window.addEventListener('beforeunload', onExit);
    window.addEventListener('pagehide', onExit);
}

onMounted(() => {
    const rawBranch = user.value?.branch || '';
    const norm = (s) => (s || '').toLowerCase().replace(/^sucursal\s+/, '').trim();
    const userBranch = norm(rawBranch);
    if (userBranch) {
        const params = new URLSearchParams(window.location.search);
        const branchParam = params.get('branch') || params.get('sucursal');
        if (branchParam && (branchParam === 'all' || norm(branchParam) !== userBranch)) {
            branchDeniedMessage.value = 'No tiene permisos para este cambio';
            showBranchDeniedModal.value = true;
            params.delete('branch');
            params.delete('sucursal');
            const cleanSearch = params.toString() ? `?${params.toString()}` : '';
            router.replace({ url: window.location.pathname + cleanSearch });
        }
    }

    if (user.value?.role === 'admin' && user.value?.branch) {
        checkPendingBodegaRequests();
        bodegaPollInterval = setInterval(checkPendingBodegaRequests, 15000);
        window.addEventListener('focus', checkPendingBodegaRequests);
    }

    initSingleTabGuard();
});

onUnmounted(() => {
    if (bodegaPollInterval) {
        clearInterval(bodegaPollInterval);
    }
    window.removeEventListener('focus', checkPendingBodegaRequests);

    if (tabHeartbeatTimer) {
        clearInterval(tabHeartbeatTimer);
    }
    if (singleTabChannel) {
        singleTabChannel.close();
    }
});
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-gray-950 font-sans">
        <!-- -- SIDEBAR (ESTÁTICO AL SCROLL / FIXED POSITION) ------------- -->
        <aside
            :class="[
                'flex flex-col flex-shrink-0 sticky top-0 h-screen transition-all duration-300 ease-in-out z-30 select-none',
                sidebarOpen ? 'w-64' : 'w-16',
                'bg-gradient-to-b from-gray-900 via-gray-900 to-gray-950 border-r border-white/5 shadow-2xl'
            ]"
        >
            <!-- Brand / Logo -->
            <div class="flex items-center gap-3 px-4 py-4 border-b border-white/5 flex-shrink-0 h-16">
                <img src="/assets/images/logo.jpg" class="w-9 h-9 rounded-full object-cover ring-2 ring-violet-500/50 shadow-md flex-shrink-0" alt="Logo" />
                <span v-if="sidebarOpen" class="text-white font-black text-base tracking-tight leading-none">
                    Farmacia<br><span class="text-violet-400">Solei</span>
                </span>
            </div>

            <!-- Nav links agrupados por secciones -->
            <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-4 custom-scrollbar">
                <div v-for="(section, sIdx) in visibleSections" :key="sIdx" class="space-y-1">
                    <!-- Título de sección -->
                    <p
                        v-if="sidebarOpen"
                        class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-violet-400/70 mb-1.5"
                    >
                        {{ section.title }}
                    </p>
                    <div v-else class="h-px bg-white/5 my-2"></div>

                    <!-- Lista de ítems de la sección -->
                    <template v-for="item in section.items" :key="item.label">
                        <Link
                            :href="route(item.route, item.params || {})"
                            class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 transition-all group relative"
                            :class="{ 'bg-violet-600/20 text-violet-300 font-semibold shadow-inner border border-violet-500/20': $page.url.startsWith('/' + item.route.split('.')[0]) }"
                            :title="!sidebarOpen ? item.label : ''"
                        >
                            <i :class="['fas', item.icon, 'w-5 text-center text-xs flex-shrink-0 group-hover:text-violet-400 transition-colors']"></i>
                            <span v-if="sidebarOpen" class="text-xs font-medium truncate flex-1">{{ item.label }}</span>
                        </Link>
                    </template>
                </div>
            </nav>

            <!-- Info de Usuario & Cerrar Sesión -->
            <div class="border-t border-white/5 px-3 py-3 flex-shrink-0 bg-gray-950/60">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-violet-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0 ring-2 ring-violet-500/30">
                        {{ user?.username?.charAt(0)?.toUpperCase() }}
                    </div>
                    <div v-if="sidebarOpen" class="flex-1 min-w-0">
                        <p class="text-white text-xs font-semibold truncate">{{ user?.username }}</p>
                        <p class="text-gray-500 text-[10px] capitalize">{{ user?.role }}</p>
                    </div>
                    <button v-if="sidebarOpen" @click="logout" class="text-gray-500 hover:text-red-400 transition-colors p-1" title="Cerrar sesión">
                        <i class="fas fa-sign-out-alt text-sm"></i>
                    </button>
                </div>
            </div>
        </aside>

        <!-- -- MAIN CONTENT (ÁREA DE CONTENIDO CON SCROLL INDEPENDIENTE) -- -->
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
            <!-- Topbar -->
            <header class="flex items-center gap-3 px-6 py-3 bg-gray-900/60 backdrop-blur border-b border-white/5 flex-shrink-0 min-h-16">
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    class="text-gray-400 hover:text-white transition-colors p-1 rounded"
                    title="Alternar barra lateral"
                >
                    <i class="fas fa-bars"></i>
                </button>

                <div class="flex-1">
                    <slot name="header" />
                </div>

                <div class="flex items-center gap-3">
                    <!-- Botón Bloquear Caja / Terminal -->
                    <button
                        @click="lockTerminal"
                        class="px-3 py-1.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 text-xs font-semibold border border-amber-500/20 transition-all flex items-center gap-1.5 shadow"
                        title="Bloquear terminal de caja (Ctrl + L)"
                    >
                        <i class="fas fa-lock text-[11px]"></i>
                        <span class="hidden sm:inline">Bloquear Caja</span>
                    </button>

                    <div class="flex items-center gap-2 text-xs text-gray-400 bg-gray-900/90 px-3.5 py-1.5 rounded-xl border border-white/5">
                        <span class="text-white font-bold tracking-wide">Farmacia Solei</span>
                        <span class="text-gray-600">/</span>
                        <span class="text-violet-300 font-semibold flex items-center gap-1.5">
                            <i class="fas fa-building text-violet-400 text-[11px]"></i>
                            {{ user?.branch ? (user.branch.toLowerCase().startsWith('sucursal') ? user.branch : `Sucursal ${user.branch}`) : 'Acceso Global' }}
                        </span>
                        <span class="text-gray-600">/</span>
                        <span class="text-gray-300 font-semibold flex items-center gap-1.5">
                            <i class="fas fa-user text-violet-400 text-[10px]"></i>
                            {{ user?.username }}
                        </span>
                    </div>
                </div>
            </header>

            <!-- Page Content (scrollable) -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-950">
                <!-- -- BREADCRUMBS DIVIDERS NAV BAR ---------------------------- -->
                <nav class="flex items-center gap-1.5 text-xs text-gray-400 mb-4 px-1 select-none">
                    <template v-for="(crumb, idx) in breadcrumbs" :key="idx">
                        <Link
                            v-if="crumb.route && !crumb.active"
                            :href="route(crumb.route)"
                            class="hover:text-violet-400 transition-colors flex items-center gap-1.5 font-medium"
                        >
                            <i v-if="crumb.icon" :class="['fas', crumb.icon, 'text-gray-500 text-[11px]']"></i>
                            <span>{{ crumb.label }}</span>
                        </Link>
                        <span v-else class="font-semibold" :class="crumb.active ? 'text-white font-bold' : 'text-gray-400'">
                            {{ crumb.label }}
                        </span>

                        <!-- Divider Icon -->
                        <i v-if="idx < breadcrumbs.length - 1" class="fas fa-chevron-right text-[9px] text-gray-600 mx-1"></i>
                    </template>
                </nav>

                <slot />
            </main>
        </div>
    </div>

    <!-- -- TOAST STACK ----------------------------------------------- -->
    <teleport to="body">
        <div class="fixed bottom-6 right-6 z-[9999] flex flex-col gap-2.5 items-end pointer-events-none">
            <transition-group name="toast">
                <div
                    v-for="t in toasts"
                    :key="t.id"
                    class="pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-2xl shadow-2xl border backdrop-blur-md min-w-[280px] max-w-[360px] cursor-pointer select-none"
                    :class="{
                        'bg-emerald-950/90 border-emerald-500/30 text-emerald-200': t.type === 'success',
                        'bg-red-950/90 border-red-500/30 text-red-200':             t.type === 'error',
                        'bg-amber-950/90 border-amber-500/30 text-amber-200':       t.type === 'warning',
                        'bg-blue-950/90 border-blue-500/30 text-blue-200':          t.type === 'info',
                    }"
                    @click="dismiss(t.id)"
                >
                    <!-- Icon -->
                    <div class="flex-shrink-0 mt-0.5">
                        <i v-if="t.type === 'success'" class="fas fa-check-circle text-emerald-400 text-base"></i>
                        <i v-else-if="t.type === 'error'"   class="fas fa-times-circle text-red-400 text-base"></i>
                        <i v-else-if="t.type === 'warning'" class="fas fa-exclamation-triangle text-amber-400 text-base"></i>
                        <i v-else                           class="fas fa-info-circle text-blue-400 text-base"></i>
                    </div>
                    <!-- Message -->
                    <p class="flex-1 text-sm font-medium leading-snug">{{ t.message }}</p>
                    <!-- Dismiss x -->
                    <button class="flex-shrink-0 opacity-50 hover:opacity-100 transition-opacity text-xs mt-0.5">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </transition-group>
        </div>
    </teleport>

    <!-- -- PANTALLA DE BLOQUEO DE CAJA CON CAROUSEL ------------------ -->
    <LockScreenModal
        :isLocked="isTerminalLocked"
        @unlock="unlockTerminal"
    />

    <!-- ── MODAL BLOQUEANTE: SESIÓN DE CAJA ABIERTA (solo cajeros) ──────── -->
    <teleport to="body">
        <Transition name="fade">
        <div v-if="showCashBlockModal"
            class="fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-black/85 backdrop-blur-md">
            <div class="bg-gray-900 border border-amber-500/30 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

                <!-- Header -->
                <div class="flex items-center gap-3 px-6 py-5 bg-amber-950/40 border-b border-amber-500/20">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/15 border border-amber-500/25 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-cash-register text-amber-400 text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-white font-bold text-base leading-tight">Sesión de Caja Activa</h2>
                        <p class="text-amber-400/80 text-xs mt-0.5">Acción requerida antes de continuar</p>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-6 py-6 space-y-4">
                    <div class="bg-gray-800/60 rounded-xl p-4 border border-white/5">
                        <p class="text-gray-400 text-sm leading-relaxed">
                            Se ha detectado una sesión abierta de:
                        </p>
                        <p class="mt-2 flex items-center gap-2">
                            <i class="fas fa-user-circle text-amber-400"></i>
                            <span class="text-white font-bold text-base">
                                {{ openCashSession?.opener?.username ?? 'Usuario desconocido' }}
                            </span>
                        </p>
                        <p class="text-gray-500 text-xs mt-1">
                            Apertura: {{ openCashSession?.opened_at
                                ? new Date(openCashSession.opened_at).toLocaleString('es-NI', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit', hour12:true })
                                : '—' }}
                        </p>
                    </div>

                    <div class="bg-amber-950/30 border border-amber-500/20 rounded-xl px-4 py-3 flex items-start gap-3">
                        <i class="fas fa-exclamation-triangle text-amber-400 mt-0.5 flex-shrink-0"></i>
                        <p class="text-amber-200 text-sm">
                            Para continuar, un <strong>administrador</strong> debe cerrar esta caja primero.
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-white/5 bg-gray-950/40 flex justify-end gap-3">
                    <button @click="logout"
                        class="px-5 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm font-medium transition-colors flex items-center gap-2">
                        <i class="fas fa-sign-out-alt text-xs"></i> Cerrar Sesión
                    </button>
                    <a :href="route('cash.index')"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white font-bold text-sm flex items-center gap-2 shadow-lg shadow-amber-900/25 transition-all">
                        <i class="fas fa-cash-register text-xs"></i> Ver Caja
                    </a>
                </div>

            </div>
        </div>
        </Transition>
    </teleport>

    <!-- ── MODAL DE ALERTA: RESTRICCIÓN DE SUCURSAL VÍA URL ────────────── -->
    <teleport to="body">
        <Transition name="fade">
        <div v-if="showBranchDeniedModal"
            class="fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-black/85 backdrop-blur-md">
            <div class="bg-gray-900 border border-red-500/40 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">

                <!-- Header -->
                <div class="flex items-center gap-3 px-6 py-5 bg-red-950/40 border-b border-red-500/20">
                    <div class="w-12 h-12 rounded-xl bg-red-500/15 border border-red-500/30 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-ban text-red-400 text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-white font-bold text-base leading-tight">Acceso Restringido</h2>
                        <p class="text-red-400/90 text-xs mt-0.5">Control de Seguridad por Sucursal</p>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-6 py-6 space-y-4">
                    <div class="bg-red-950/30 border border-red-500/20 rounded-xl px-4 py-3.5 flex items-start gap-3">
                        <i class="fas fa-exclamation-triangle text-red-400 mt-0.5 flex-shrink-0 text-base"></i>
                        <div>
                            <p class="text-white font-bold text-sm">
                                {{ branchDeniedMessage || 'No tiene permisos para este cambio' }}
                            </p>
                            <p class="text-red-200/90 text-xs mt-1.5 leading-relaxed">
                                Tu usuario está asignado a <strong>{{ user?.branch || 'tu sucursal asignada' }}</strong>. No tienes autorización para consultar ni acceder a información de otras sucursales vía URL.
                            </p>
                        </div>
                    </div>

                    <div class="bg-gray-800/60 rounded-xl p-3.5 border border-white/5 flex items-center justify-between text-xs">
                        <span class="text-gray-400">Sucursal de tu sesión:</span>
                        <span class="text-violet-300 font-bold flex items-center gap-1.5">
                            <i class="fas fa-store text-violet-400 text-[11px]"></i>
                            {{ user?.branch || 'Sucursal Asignada' }}
                        </span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-white/5 bg-gray-950/50 flex justify-end">
                    <button
                        type="button"
                        @click="closeBranchDeniedModal"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white font-bold text-sm flex items-center gap-2 shadow-lg shadow-red-950/30 transition-all cursor-pointer"
                    >
                        <i class="fas fa-check text-xs"></i> Entendido
                    </button>
                </div>

            </div>
        </div>
        </Transition>

        <!-- ══ MODAL ALERTA: SOLICITUD DE ACCESO A BODEGA (ADMIN DE SUCURSAL) ══ -->
        <Transition name="fade">
        <div v-if="pendingBodegaRequest" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-gray-900 border border-violet-500/40 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden animate-scaleIn">
                <!-- Header -->
                <div class="flex items-center gap-3 px-6 py-5 bg-gradient-to-r from-violet-950/80 to-purple-950/50 border-b border-violet-500/20">
                    <div class="w-12 h-12 rounded-xl bg-violet-500/20 border border-violet-500/40 flex items-center justify-center flex-shrink-0 animate-pulse">
                        <i class="fas fa-warehouse text-violet-300 text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-white font-bold text-base leading-tight">Solicitud de Acceso a Bodega</h2>
                        <p class="text-violet-300/80 text-xs mt-0.5">Autorización Requerida</p>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-6 py-5 space-y-4">
                    <p class="text-gray-300 text-xs leading-relaxed">
                        Un usuario con cargo <strong>Superadmin</strong> solicita ingresar y operar la bodega de su sucursal:
                    </p>

                    <div class="bg-gray-800/80 border border-white/10 rounded-xl p-4 space-y-2 text-xs">
                        <div class="flex justify-between items-center py-1 border-b border-white/5">
                            <span class="text-gray-400">Solicitante:</span>
                            <span class="text-white font-bold text-xs">{{ pendingBodegaRequest.user.name }} (@{{ pendingBodegaRequest.user.username }})</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-white/5">
                            <span class="text-gray-400">Cargo:</span>
                            <span class="text-violet-400 font-bold uppercase tracking-wider">{{ pendingBodegaRequest.user.role }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-white/5">
                            <span class="text-gray-400">Sucursal:</span>
                            <span class="text-emerald-400 font-semibold">{{ pendingBodegaRequest.branch }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1">
                            <span class="text-gray-400">Fecha y Hora:</span>
                            <span class="text-gray-200 font-mono">{{ pendingBodegaRequest.created_at }}</span>
                        </div>
                    </div>

                    <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-3 flex items-start gap-2.5 text-xs text-amber-300">
                        <i class="fas fa-question-circle text-amber-400 mt-0.5 flex-shrink-0 text-sm"></i>
                        <span>¿Desea conceder el acceso a la bodega de esta sucursal al Superadmin solicitante?</span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-white/5 bg-gray-950/60 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        :disabled="isRespondingBodega"
                        @click="respondBodegaRequest('rejected')"
                        class="px-4 py-2.5 rounded-xl bg-gray-800 hover:bg-red-600/20 hover:text-red-400 text-gray-300 text-xs font-semibold border border-white/10 transition-all cursor-pointer disabled:opacity-50"
                    >
                        Rechazar
                    </button>
                    <button
                        type="button"
                        :disabled="isRespondingBodega"
                        @click="respondBodegaRequest('approved')"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-lg shadow-emerald-950/30 flex items-center gap-1.5 transition-all cursor-pointer disabled:opacity-50"
                    >
                        <i class="fas fa-check text-xs"></i> Conceder Acceso
                    </button>
                </div>
            </div>
        </div>
        </Transition>

        <!-- ══ MODAL BLOQUEANTE: PESTAÑAS MÚLTIPLES DETECTADAS ═══════════════ -->
        <Transition name="fade">
        <div v-if="isDuplicateTab" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/90 backdrop-blur-md select-none">
            <div class="bg-gray-900 border border-amber-500/40 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden animate-scaleIn text-center">
                <!-- Header -->
                <div class="px-6 pt-8 pb-4">
                    <div class="w-16 h-16 rounded-2xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center mx-auto mb-4 animate-pulse">
                        <i class="fas fa-window-restore text-amber-400 text-2xl"></i>
                    </div>
                    <h2 class="text-white font-black text-lg leading-snug">
                        Sesión Duplicada Detectada
                    </h2>
                    <p class="text-amber-400/90 text-xs font-semibold mt-1">Control de Seguridad de Navegación</p>
                </div>

                <!-- Body -->
                <div class="px-6 pb-6 space-y-4">
                    <div class="bg-amber-500/10 border border-amber-500/25 rounded-xl p-4 text-left">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-triangle text-amber-400 text-lg mt-0.5 flex-shrink-0"></i>
                            <p class="text-white font-bold text-sm leading-relaxed">
                                No es posible tener varias vistas abiertas por temas de seguridad. Favor cierre las adicionales.
                            </p>
                        </div>
                    </div>

                    <p class="text-gray-400 text-xs leading-relaxed text-left">
                        El sistema ha detectado que ya tienes otra pestaña activa en este navegador. Para evitar conflictos de datos y proteger las operaciones, esta pestaña secundaria se mantiene inactiva.
                    </p>

                    <div class="pt-2 flex flex-col gap-2">
                        <button
                            type="button"
                            @click="closeDuplicateTab"
                            class="w-full py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white font-bold text-xs shadow-lg shadow-red-950/40 transition-all flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i class="fas fa-times-circle"></i> Cerrar esta Pestaña
                        </button>
                        <button
                            type="button"
                            @click="retryTabActivation"
                            class="w-full py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs font-semibold border border-white/10 transition-colors flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i class="fas fa-sync-alt text-violet-400"></i> ¿Cerró la otra pestaña? Activar esta
                        </button>
                    </div>
                </div>
            </div>
        </div>
        </Transition>
    </teleport>
</template>

