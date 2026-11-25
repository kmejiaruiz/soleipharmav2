<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    users:    Object,
    branches: Array,
    filters:  Object,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || {});
const isSuperAdmin = computed(() => currentUser.value.role === 'superadmin');

const standardBranches = computed(() => {
    return props.branches && props.branches.length > 0
        ? props.branches
        : [currentUser.value.branch || 'Sucursal Managua'];
});

// Filtros reactivos
const searchUsername = ref(props.filters?.username || '');
const searchName     = ref(props.filters?.name || '');
const selectedBranch = ref(props.filters?.branch || (isSuperAdmin.value ? 'all' : (currentUser.value.branch || 'all')));
const selectedRole   = ref(props.filters?.role || 'all');
const selectedStatus = ref(props.filters?.status || 'all');

let filterTimer = null;

function applyFilters() {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(() => {
        router.get(route('users.index'), {
            username: searchUsername.value || undefined,
            name:     searchName.value || undefined,
            branch:   (isSuperAdmin.value && selectedBranch.value !== 'all') ? selectedBranch.value : undefined,
            role:     selectedRole.value !== 'all' ? selectedRole.value : undefined,
            status:   selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
        }, { preserveState: true, replace: true });
    }, 300);
}

watch([searchUsername, searchName, selectedBranch, selectedRole, selectedStatus], () => {
    applyFilters();
});

const hasActiveFilters = computed(() => {
    return !!searchUsername.value ||
           !!searchName.value ||
           (isSuperAdmin.value && selectedBranch.value !== 'all') ||
           selectedRole.value !== 'all' ||
           selectedStatus.value !== 'all';
});

function clearFilters() {
    searchUsername.value = '';
    searchName.value     = '';
    if (isSuperAdmin.value) {
        selectedBranch.value = 'all';
    }
    selectedRole.value   = 'all';
    selectedStatus.value = 'all';
    router.get(route('users.index'), {}, { preserveState: true, replace: true });
}

// ── Modales: Crear / Editar Usuario ─────────────────────────────
const showModal    = ref(false);
const editingUser  = ref(null);
const isValidating = ref(false);
let usernameSuggestTimer = null;

const form = useForm({
    id:         null,
    first_name: '',
    last_name:  '',
    username:   '',
    name:       '',
    email:      '',
    password:   '',
    role:       'cajero',
    branch:     'Sucursal Managua',
});

let abortCtrl = null;

function sanitizePart(str) {
    if (!str) return '';
    return str
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]/g, '');
}

function autoSuggestUsername() {
    if (editingUser.value) return;
    form.clearErrors('username', 'email');

    const firstWord = (form.first_name || '').trim().split(/\s+/)[0] || '';
    const lastWord  = (form.last_name || '').trim().split(/\s+/)[0] || '';

    const cleanFirst = sanitizePart(firstWord);
    const cleanLast  = sanitizePart(lastWord);

    if (!cleanFirst || !cleanLast) {
        form.username = '';
        form.email    = '';
        isValidating.value = false;
        if (abortCtrl) abortCtrl.abort();
        return;
    }

    // Candidato instantáneo en 0ms para respuesta inmediata al escribir
    const instantCandidate = cleanFirst[0] + cleanLast;
    if (!form.username || form.username.startsWith(cleanFirst[0])) {
        form.username = instantCandidate;
        form.email    = instantCandidate + '@soleipharma.com';
    }

    isValidating.value = true;
    clearTimeout(usernameSuggestTimer);

    if (abortCtrl) abortCtrl.abort();
    abortCtrl = new AbortController();

    usernameSuggestTimer = setTimeout(async () => {
        try {
            const { data } = await window.axios.get(route('users.suggestUsername'), {
                params: {
                    first_name: form.first_name,
                    last_name:  form.last_name,
                },
                signal: abortCtrl.signal,
            });
            if (!editingUser.value && data.username) {
                form.username = data.username;
                form.email    = data.email;
                form.clearErrors('username', 'email');
            }
        } catch (e) {
            if (e.name !== 'CanceledError' && e.name !== 'AbortError' && e.code !== 'ERR_CANCELED') {
                console.error('Error sugiriendo username/email', e);
            }
        } finally {
            isValidating.value = false;
        }
    }, 60);
}

watch(() => [form.first_name, form.last_name], () => {
    autoSuggestUsername();
});

watch(() => form.role, (newRole) => {
    if (newRole === 'superadmin') {
        form.branch = '';
    } else if (!form.branch) {
        form.branch = currentUser.value?.branch || (standardBranches.value[0] || 'Sucursal Managua');
    }
});

function openCreateModal() {
    editingUser.value = null;
    isValidating.value = false;
    form.reset();
    form.clearErrors();
    form.role = 'cajero';
    form.branch = currentUser.value?.branch || (standardBranches.value[0] || 'Sucursal Managua');
    showModal.value = true;
}

function openEditModal(user) {
    if (user.id === currentUser.value.id) return;
    editingUser.value = user;
    isValidating.value = false;
    form.clearErrors();
    form.id         = user.id;

    const nameParts = (user.name || '').trim().split(/\s+/);
    form.first_name = user.first_name || nameParts[0] || '';
    form.last_name  = user.last_name || nameParts.slice(1).join(' ') || '';
    form.name       = user.name || '';
    form.username   = user.username;
    form.email      = user.email || (user.username ? `${user.username}@soleipharma.com` : '');
    form.password   = '';
    form.role       = user.role || 'cajero';
    form.branch     = user.role === 'superadmin' ? '' : (user.branch || (standardBranches.value[0] || 'Sucursal Managua'));
    showModal.value = true;
}

function closeModal() {
    showModal.value   = false;
    editingUser.value = null;
    isValidating.value = false;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    if (editingUser.value) {
        form.put(route('users.update', editingUser.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => closeModal(),
        });
    }
}

function toggleLock(user) {
    if (user.id === currentUser.value.id) return;
    const action = user.is_locked ? 'desbloquear' : 'bloquear';
    if (confirm(`¿Estás seguro de ${action} al usuario "${user.username}"?`)) {
        router.patch(route('users.toggleLock', user.id));
    }
}

// ── Modal: Reestablecer Contraseña ──────────────────────────────
const showResetModal   = ref(false);
const targetResetUser  = ref(null);
const showNewPassword  = ref(false);

const resetForm = useForm({
    password:              '',
    password_confirmation: '',
});

function canResetPassword(targetUser) {
    if (!targetUser || targetUser.id === currentUser.value.id) return false;
    // Superadmin puede restablecer a cualquier usuario
    if (currentUser.value.role === 'superadmin') return true;

    // Admin puede restablecer a usuarios de su misma sucursal (excepto a un superadmin)
    if (currentUser.value.role === 'admin') {
        if (targetUser.role === 'superadmin') return false;
        const myBranch = (currentUser.value.branch || '').trim().toLowerCase();
        const userBranch = (targetUser.branch || '').trim().toLowerCase();
        return myBranch !== '' && myBranch === userBranch;
    }

    return false;
}

function openResetPasswordModal(user) {
    targetResetUser.value = user;
    resetForm.reset();
    resetForm.clearErrors();
    showNewPassword.value = false;
    showResetModal.value  = true;
}

function closeResetModal() {
    showResetModal.value  = false;
    targetResetUser.value = null;
    resetForm.reset();
    resetForm.clearErrors();
}

function generateRandomPassword() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
    let generated = '';
    for (let i = 0; i < 10; i++) {
        generated += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    resetForm.password = generated;
    resetForm.password_confirmation = generated;
    showNewPassword.value = true;
}

function submitResetPassword() {
    if (!targetResetUser.value) return;

    resetForm.put(route('users.resetPassword', targetResetUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeResetModal();
        },
    });
}

function handleKeydown(e) {
    if (e.key === 'Escape') {
        if (showResetModal.value) closeResetModal();
        else if (showModal.value) closeModal();
    }
}

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));

const roleBadges = {
    superadmin: { label: 'Superadmin', class: 'bg-purple-500/15 text-purple-400 border-purple-500/20' },
    admin:      { label: 'Admin',      class: 'bg-blue-500/15 text-blue-400 border-blue-500/20' },
    cajero:     { label: 'Cajero',     class: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/20' },
};
</script>

<template>
    <Head title="Gestión de Usuarios — Farmacia Solei" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-violet-600/15 border border-violet-500/20 flex items-center justify-center text-violet-400 shrink-0">
                        <i class="fas fa-users-cog text-base"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-white font-bold text-lg leading-tight">Gestión de Usuarios</h1>
                            <span class="text-xs bg-white/5 text-gray-400 px-2.5 py-0.5 rounded-full border border-white/5">
                                {{ users.total }} cuentas
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">
                            Administración de cuentas, sucursales y reestablecimiento de credenciales
                        </p>
                    </div>
                </div>

                <!-- Botón Nuevo Usuario -->
                <button
                    @click="openCreateModal"
                    class="shrink-0 whitespace-nowrap inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 via-violet-500 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white font-semibold text-sm shadow-lg shadow-violet-900/25 border border-violet-400/20 active:scale-95 transition-all cursor-pointer"
                >
                    <i class="fas fa-user-plus text-xs"></i>
                    <span>Nuevo Usuario</span>
                </button>
            </div>
        </template>

        <!-- Barra de Filtros Avanzados (Sucursal, Usuario, Nombre, Rol, Estado) -->
        <div class="bg-gray-900 border border-white/5 rounded-2xl p-4 mb-6 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-white/5 pb-3">
                <div class="flex items-center gap-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    <i class="fas fa-filter text-violet-400"></i>
                    <span>Filtros de Búsqueda</span>
                </div>
                <button
                    v-if="hasActiveFilters"
                    @click="clearFilters"
                    class="text-xs text-violet-400 hover:text-violet-300 font-medium flex items-center gap-1.5 transition-colors cursor-pointer"
                >
                    <i class="fas fa-times-circle"></i>
                    <span>Limpiar filtros</span>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                <!-- Filtro por Usuario -->
                <div class="relative">
                    <i class="fas fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
                    <input
                        v-model="searchUsername"
                        type="text"
                        placeholder="Filtrar por usuario..."
                        class="w-full pl-9 pr-3 py-2 bg-gray-950/60 border border-white/10 focus:border-violet-500 rounded-xl text-white placeholder-gray-500 text-xs outline-none transition-colors"
                    />
                </div>

                <!-- Filtro por Nombre -->
                <div class="relative">
                    <i class="fas fa-id-card absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
                    <input
                        v-model="searchName"
                        type="text"
                        placeholder="Filtrar por nombre..."
                        class="w-full pl-9 pr-3 py-2 bg-gray-950/60 border border-white/10 focus:border-violet-500 rounded-xl text-white placeholder-gray-500 text-xs outline-none transition-colors"
                    />
                </div>

                <!-- Filtro por Sucursal -->
                <div v-if="isSuperAdmin" class="relative">
                    <select
                        v-model="selectedBranch"
                        class="w-full bg-gray-950/60 border border-white/10 text-gray-300 rounded-xl px-3 py-2 text-xs outline-none focus:border-violet-500 transition-colors cursor-pointer"
                    >
                        <option value="all">Todas las sucursales</option>
                        <option v-for="b in standardBranches" :key="b" :value="b">
                            {{ b }}
                        </option>
                    </select>
                </div>
                <div v-else class="relative">
                    <div class="w-full bg-gray-950/40 border border-white/5 text-gray-300 rounded-xl px-3 py-2 text-xs flex items-center gap-2 select-none">
                        <i class="fas fa-store text-violet-400 text-xs"></i>
                        <span class="truncate font-medium">{{ currentUser.branch || 'Mi Sucursal' }}</span>
                    </div>
                </div>

                <!-- Filtro por Rol (Cajero, Admin, Superadmin) -->
                <div class="relative">
                    <select
                        v-model="selectedRole"
                        class="w-full bg-gray-950/60 border border-white/10 text-gray-300 rounded-xl px-3 py-2 text-xs outline-none focus:border-violet-500 transition-colors cursor-pointer"
                    >
                        <option value="all">Todos los roles</option>
                        <option v-if="isSuperAdmin" value="superadmin">Superadmin</option>
                        <option value="admin">Administrador</option>
                        <option value="cajero">Cajero</option>
                    </select>
                </div>

                <!-- Filtro por Estado -->
                <div class="relative">
                    <select
                        v-model="selectedStatus"
                        class="w-full bg-gray-950/60 border border-white/10 text-gray-300 rounded-xl px-3 py-2 text-xs outline-none focus:border-violet-500 transition-colors cursor-pointer"
                    >
                        <option value="all">Todos los estados</option>
                        <option value="active">Activos</option>
                        <option value="locked">Bloqueados</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="bg-gray-900 rounded-2xl border border-white/5 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-white/5 bg-gray-950/40">
                            <th class="px-6 py-4">Usuario</th>
                            <th class="px-4 py-4">Correo</th>
                            <th class="px-4 py-4 text-center">Rol</th>
                            <th class="px-4 py-4 text-center">Sucursal</th>
                            <th class="px-4 py-4 text-center">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-gray-300">
                        <tr v-for="u in users.data" :key="u.id" class="hover:bg-white/2 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-violet-600 to-indigo-600 flex items-center justify-center text-white font-bold text-xs uppercase shadow-md">
                                        {{ (u.username || 'U').substring(0, 2) }}
                                    </div>
                                    <div>
                                        <p class="text-white font-semibold text-sm">
                                            {{ u.username }}
                                        </p>
                                        <p v-if="u.name || u.first_name" class="text-gray-400 text-xs">
                                            {{ u.full_name || u.name }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-xs text-gray-400">
                                {{ u.email || '—' }}
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border"
                                    :class="roleBadges[u.role]?.class || 'bg-gray-800 text-gray-400 border-white/10'">
                                    {{ roleBadges[u.role]?.label || u.role }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center text-xs">
                                <span
                                    v-if="u.role === 'superadmin' && !u.branch"
                                    class="px-2.5 py-1 rounded-lg bg-violet-950/50 text-violet-300 border border-violet-500/20 font-medium"
                                >
                                    Acceso Global
                                </span>
                                <span v-else class="px-2.5 py-1 rounded-lg bg-gray-800 text-gray-300 border border-white/5 font-medium">
                                    {{ u.branch || 'Sin Sucursal' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border"
                                    :class="u.is_locked
                                        ? 'bg-red-500/15 text-red-400 border-red-500/20'
                                        : 'bg-emerald-500/15 text-emerald-400 border-emerald-500/20'">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="u.is_locked ? 'bg-red-400' : 'bg-emerald-400'"></span>
                                    {{ u.is_locked ? 'Bloqueado' : 'Activo' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-1.5">
                                <template v-if="u.id === currentUser.id">
                                    <!-- Propia cuenta: Acciones desactivadas -->
                                    <button
                                        disabled
                                        class="w-8 h-8 rounded-lg bg-gray-800/30 text-gray-600 cursor-not-allowed inline-flex items-center justify-center opacity-40"
                                        title="No puedes restablecer tu propia clave desde la administración"
                                    >
                                        <i class="fas fa-key text-xs"></i>
                                    </button>
                                    <button
                                        disabled
                                        class="w-8 h-8 rounded-lg bg-gray-800/30 text-gray-600 cursor-not-allowed inline-flex items-center justify-center opacity-40"
                                        title="No puedes editar tu propia cuenta desde este panel"
                                    >
                                        <i class="fas fa-pen text-xs"></i>
                                    </button>
                                    <button
                                        disabled
                                        class="w-8 h-8 rounded-lg bg-gray-800/30 text-gray-600 cursor-not-allowed inline-flex items-center justify-center opacity-40"
                                        title="No puedes bloquear tu propia cuenta"
                                    >
                                        <i class="fas fa-lock text-xs"></i>
                                    </button>
                                </template>

                                <template v-else>
                                    <!-- Botón Reestablecer Contraseña -->
                                    <button
                                        v-if="canResetPassword(u)"
                                        @click="openResetPasswordModal(u)"
                                        class="w-8 h-8 rounded-lg bg-gray-800 hover:bg-amber-500/20 text-gray-400 hover:text-amber-300 transition-colors inline-flex items-center justify-center cursor-pointer"
                                        title="Reestablecer contraseña de acceso"
                                    >
                                        <i class="fas fa-key text-xs"></i>
                                    </button>
                                    <button
                                        v-else
                                        disabled
                                        class="w-8 h-8 rounded-lg bg-gray-800/40 text-gray-600 cursor-not-allowed inline-flex items-center justify-center opacity-40"
                                        :title="currentUser.role === 'admin' ? 'Solo puedes restablecer claves de tu misma sucursal' : 'No disponible'"
                                    >
                                        <i class="fas fa-key text-xs"></i>
                                    </button>

                                    <!-- Botón Editar -->
                                    <button
                                        @click="openEditModal(u)"
                                        class="w-8 h-8 rounded-lg bg-gray-800 hover:bg-violet-600/30 text-gray-400 hover:text-violet-300 transition-colors inline-flex items-center justify-center cursor-pointer"
                                        title="Editar usuario"
                                    >
                                        <i class="fas fa-pen text-xs"></i>
                                    </button>

                                    <!-- Botón Bloquear / Desbloquear -->
                                    <button
                                        @click="toggleLock(u)"
                                        class="w-8 h-8 rounded-lg bg-gray-800 transition-colors inline-flex items-center justify-center cursor-pointer"
                                        :class="u.is_locked ? 'hover:bg-emerald-600/30 text-emerald-400' : 'hover:bg-red-600/30 text-red-400'"
                                        :title="u.is_locked ? 'Desbloquear usuario' : 'Bloquear usuario'"
                                    >
                                        <i :class="['fas text-xs', u.is_locked ? 'fa-unlock' : 'fa-lock']"></i>
                                    </button>
                                </template>
                            </td>
                        </tr>

                        <tr v-if="!users.data.length">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-600">
                                <i class="fas fa-users text-4xl mb-3 text-gray-700"></i>
                                <p class="text-sm font-medium">No se encontraron usuarios con los filtros aplicados</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div v-if="users.links?.length > 3" class="flex items-center justify-between px-6 py-4 border-t border-white/5 bg-gray-950/20">
                <span class="text-xs text-gray-500">Mostrando {{ users.from }} - {{ users.to }} de {{ users.total }}</span>
                <div class="flex gap-1">
                    <template v-for="(link, i) in users.links" :key="i">
                        <Link v-if="link.url" :href="link.url" v-html="link.label"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                            :class="link.active ? 'bg-violet-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-white'" />
                        <span v-else v-html="link.label" class="px-3 py-1.5 rounded-lg text-xs text-gray-700"></span>
                    </template>
                </div>
            </div>
        </div>

        <!-- ══ MODAL: REESTABLECER CONTRASEÑA ══════════════════════════════════ -->
        <Transition name="fade">
        <div v-if="showResetModal" @click.self="closeResetModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/40">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400">
                            <i class="fas fa-key text-xs"></i>
                        </div>
                        <span>Reestablecer Contraseña</span>
                    </h3>

                    <button
                        @click="closeResetModal"
                        title="Cerrar modal (X)"
                        class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-all cursor-pointer"
                    >
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <!-- Info del Usuario Objetivo -->
                <div v-if="targetResetUser" class="p-6 space-y-4">
                    <div class="bg-gray-950/60 border border-white/5 rounded-xl p-3.5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-violet-600/30 border border-violet-500/30 flex items-center justify-center text-violet-300 font-bold text-xs uppercase">
                                {{ (targetResetUser.username || 'U').substring(0, 2) }}
                            </div>
                            <div>
                                <p class="text-white font-semibold text-sm">{{ targetResetUser.username }}</p>
                                <p class="text-gray-400 text-xs">{{ targetResetUser.full_name || targetResetUser.name || 'Sin nombre' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold border mb-1" :class="roleBadges[targetResetUser.role]?.class || 'bg-gray-800 text-gray-400 border-white/10'">
                                {{ roleBadges[targetResetUser.role]?.label || targetResetUser.role }}
                            </span>
                            <p class="text-[11px] text-gray-400">{{ targetResetUser.branch || 'Sucursal General' }}</p>
                        </div>
                    </div>

                    <form @submit.prevent="submitResetPassword" class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-gray-400 text-xs font-medium uppercase tracking-wide">Nueva Contraseña *</label>
                                <button
                                    type="button"
                                    @click="generateRandomPassword"
                                    class="text-[11px] text-violet-400 hover:text-violet-300 flex items-center gap-1 font-medium transition-colors cursor-pointer"
                                >
                                    <i class="fas fa-magic"></i>
                                    <span>Generar clave segura</span>
                                </button>
                            </div>
                            <div class="relative">
                                <input
                                    v-model="resetForm.password"
                                    :type="showNewPassword ? 'text' : 'password'"
                                    required
                                    minlength="6"
                                    placeholder="Mínimo 6 caracteres"
                                    class="w-full bg-gray-800 border border-white/10 focus:border-amber-500 rounded-xl pl-4 pr-10 py-2.5 text-white text-sm outline-none transition-colors"
                                />
                                <button
                                    type="button"
                                    @click="showNewPassword = !showNewPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 text-xs transition-colors cursor-pointer"
                                    :title="showNewPassword ? 'Ocultar contraseña' : 'Ver contraseña'"
                                >
                                    <i :class="['fas', showNewPassword ? 'fa-eye-slash' : 'fa-eye']"></i>
                                </button>
                            </div>
                            <p v-if="resetForm.errors.password" class="text-red-400 text-xs mt-1">{{ resetForm.errors.password }}</p>
                        </div>

                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Confirmar Contraseña *</label>
                            <input
                                v-model="resetForm.password_confirmation"
                                :type="showNewPassword ? 'text' : 'password'"
                                required
                                minlength="6"
                                placeholder="Repita la nueva contraseña"
                                class="w-full bg-gray-800 border border-white/10 focus:border-amber-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors"
                            />
                        </div>

                        <div v-if="resetForm.errors.error" class="bg-red-500/10 border border-red-500/20 text-red-400 rounded-xl p-3 text-xs flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>{{ resetForm.errors.error }}</span>
                        </div>

                        <div class="flex justify-end gap-2.5 pt-3 border-t border-white/5">
                            <button
                                type="button"
                                @click="closeResetModal"
                                class="px-4 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 font-medium text-xs transition-colors cursor-pointer"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="resetForm.processing"
                                class="px-5 py-2 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white font-bold text-xs shadow-lg shadow-amber-900/30 transition-all flex items-center gap-2 disabled:opacity-50 cursor-pointer"
                            >
                                <i v-if="resetForm.processing" class="fas fa-spinner fa-spin"></i>
                                <i v-else class="fas fa-key"></i>
                                <span>{{ resetForm.processing ? 'Reestableciendo...' : 'Guardar Nueva Clave' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </Transition>

        <!-- ══ MODAL: CREAR / EDITAR USUARIO ══════════════════════════════════ -->
        <Transition name="fade">
        <div v-if="showModal" @click.self="closeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
                <!-- Header del Modal -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-gray-950/40">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <i :class="['fas text-violet-400', editingUser ? 'fa-user-edit' : 'fa-user-plus']"></i>
                        {{ editingUser ? 'Editar Usuario' : 'Nuevo Usuario' }}
                    </h3>

                    <!-- Botón X para cerrar modal -->
                    <button
                        @click="closeModal"
                        title="Cerrar modal (X)"
                        class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-gray-400 hover:text-white flex items-center justify-center transition-all cursor-pointer"
                    >
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Nombre *</label>
                            <div class="relative">
                                <input
                                    v-model="form.first_name"
                                    type="text"
                                    required
                                    :readonly="!!editingUser"
                                    placeholder="ej: Juan"
                                    :class="[
                                        'w-full border rounded-xl px-4 py-2.5 text-sm outline-none transition-colors',
                                        editingUser
                                            ? 'bg-gray-950/60 border-white/5 text-gray-400 cursor-not-allowed select-none'
                                            : 'bg-gray-800 border-white/10 focus:border-violet-500 text-white'
                                    ]"
                                />
                                <i v-if="editingUser" class="fas fa-lock absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
                            </div>
                            <p v-if="form.errors.first_name" class="text-red-400 text-xs mt-1">{{ form.errors.first_name }}</p>
                        </div>

                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Apellido *</label>
                            <div class="relative">
                                <input
                                    v-model="form.last_name"
                                    type="text"
                                    required
                                    :readonly="!!editingUser"
                                    placeholder="ej: Pérez"
                                    :class="[
                                        'w-full border rounded-xl px-4 py-2.5 text-sm outline-none transition-colors',
                                        editingUser
                                            ? 'bg-gray-950/60 border-white/5 text-gray-400 cursor-not-allowed select-none'
                                            : 'bg-gray-800 border-white/10 focus:border-violet-500 text-white'
                                    ]"
                                />
                                <i v-if="editingUser" class="fas fa-lock absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
                            </div>
                            <p v-if="form.errors.last_name" class="text-red-400 text-xs mt-1">{{ form.errors.last_name }}</p>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-gray-400 text-xs font-medium uppercase tracking-wide flex items-center gap-1.5">
                                <span>Nombre de Usuario *</span>
                                <span v-if="!editingUser" class="text-[10px] text-gray-500 font-normal lowercase">(automático)</span>
                            </label>
                            <span v-if="isValidating" class="text-xs text-violet-400 flex items-center gap-1.5">
                                <i class="fas fa-spinner fa-spin text-xs"></i>
                                <span>Validando...</span>
                            </span>
                            <span v-else-if="!editingUser && form.username" class="text-xs text-emerald-400 flex items-center gap-1.5">
                                <i class="fas fa-check-circle text-xs"></i>
                                <span>Disponible</span>
                            </span>
                        </div>
                        <div class="relative">
                            <input
                                v-model="form.username"
                                type="text"
                                required
                                readonly
                                placeholder="Se generará al ingresar nombre y apellido..."
                                class="w-full bg-gray-950/60 border border-white/5 rounded-xl px-4 py-2.5 text-sm font-mono text-gray-400 cursor-not-allowed select-none"
                            />
                            <i class="fas fa-lock absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
                        </div>
                        <p v-if="form.errors.username" class="text-red-400 text-xs mt-1">{{ form.errors.username }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">Rol *</label>
                            <select v-model="form.role" required class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none cursor-pointer">
                                <option value="cajero">Cajero</option>
                                <option value="admin">Administrador</option>
                                <option v-if="isSuperAdmin" value="superadmin">Superadmin</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">
                                Sucursal Asignada
                                <span v-if="form.role === 'superadmin'" class="text-[10px] text-violet-400 font-normal lowercase">(acceso global)</span>
                                <span v-else>*</span>
                            </label>
                            <select
                                v-model="form.branch"
                                :disabled="!isSuperAdmin || form.role === 'superadmin'"
                                :required="form.role !== 'superadmin'"
                                class="w-full border rounded-xl px-4 py-2.5 text-sm outline-none transition-colors"
                                :class="(!isSuperAdmin || form.role === 'superadmin') ? 'bg-gray-950/60 border-white/5 text-gray-400 cursor-not-allowed select-none' : 'bg-gray-800 border-white/10 focus:border-violet-500 text-white cursor-pointer'"
                            >
                                <option v-if="form.role === 'superadmin'" value="">Sin sucursal asignada (Acceso Global)</option>
                                <option v-for="b in standardBranches" :key="b" :value="b">
                                    {{ b }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-gray-400 text-xs font-medium uppercase tracking-wide flex items-center gap-1.5">
                                <span>Correo Electrónico</span>
                                <span v-if="!editingUser" class="text-[10px] text-gray-500 font-normal lowercase">(automático)</span>
                            </label>
                        </div>
                        <div class="relative">
                            <input
                                v-model="form.email"
                                type="email"
                                readonly
                                placeholder="usuario@soleipharma.com"
                                class="w-full bg-gray-950/60 border border-white/5 rounded-xl px-4 py-2.5 text-sm font-mono text-gray-400 cursor-not-allowed select-none"
                            />
                            <i class="fas fa-lock absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
                        </div>
                        <p v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>

                    <!-- Contraseña solo requerida al crear -->
                    <div v-if="!editingUser">
                        <label class="block text-gray-400 text-xs font-medium mb-1 uppercase tracking-wide">
                            Contraseña *
                        </label>
                        <input v-model="form.password" type="password" :required="!editingUser" placeholder="••••••••"
                            class="w-full bg-gray-800 border border-white/10 focus:border-violet-500 rounded-xl px-4 py-2.5 text-white text-sm outline-none transition-colors" />
                        <p v-if="form.errors.password" class="text-red-400 text-xs mt-1">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-white/5">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 font-medium text-sm transition-colors cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white font-bold text-sm shadow-lg shadow-violet-900/30 transition-all flex items-center gap-2 cursor-pointer">
                            <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-save"></i>
                            {{ form.processing ? 'Guardando...' : (editingUser ? 'Actualizar' : 'Guardar') }}
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
