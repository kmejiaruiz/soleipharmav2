<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $username = trim($request->get('username', ''));
        $name     = trim($request->get('name', ''));
        $q        = trim($request->get('q', ''));
        $branch   = $request->get('branch', 'all');
        $role     = $request->get('role', 'all');
        $status   = $request->get('status', 'all'); // all, locked, active

        $authUser     = $request->user();
        $isSuperAdmin = $authUser && $authUser->isSuperAdmin();

        $query = User::query();

        // Control estricto de visibilidad: Administrador solo ve usuarios de su propia sucursal y no ve superadmins
        if (!$isSuperAdmin) {
            $userBranch = trim($authUser->branch ?? '');
            $query->where('branch', $userBranch)
                  ->where('role', '!=', 'superadmin');
            $branch = $userBranch;
        } else {
            // Filtro por Sucursal exclusivo para Superadmin
            if ($branch !== 'all' && !empty($branch)) {
                $query->where('branch', $branch);
            }
        }

        // Filtro específico por nombre de usuario
        if (!empty($username)) {
            $query->where('username', 'like', "%{$username}%");
        }

        // Filtro específico por nombre completo / apellidos
        if (!empty($name)) {
            $query->where(function ($sub) use ($name) {
                $sub->where('name', 'like', "%{$name}%")
                    ->orWhere('first_name', 'like', "%{$name}%")
                    ->orWhere('last_name', 'like', "%{$name}%")
                    ->orWhere('second_name', 'like', "%{$name}%")
                    ->orWhere('second_surname', 'like', "%{$name}%");
            });
        }

        // Búsqueda general combinada
        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('username', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        // Filtro por Rol (exclusivamente superadmin, admin, cajero)
        if ($role !== 'all' && !empty($role)) {
            $query->where('role', $role);
        }

        // Filtro por Estado (activo / bloqueado)
        if ($status === 'locked') {
            $query->where(fn($sub) => $sub->where('is_locked', true)->orWhere('status', 'locked'));
        } elseif ($status === 'active') {
            $query->where(fn($sub) => $sub->where('is_locked', false)->where('status', '!=', 'locked'));
        }

        $users = $query->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $branches = $isSuperAdmin
            ? Branch::orderBy('name')->pluck('name')
            : collect([$authUser->branch])->filter()->values();

        return Inertia::render('Admin/Users/Index', [
            'users'    => $users,
            'branches' => $branches,
            'filters'  => [
                'username' => $username,
                'name'     => $name,
                'q'        => $q,
                'branch'   => $branch,
                'role'     => $role,
                'status'   => $status,
            ],
        ]);
    }

    public function suggestUsername(Request $request)
    {
        $firstName = $request->query('first_name', '');
        $lastName  = $request->query('last_name', '');

        $username = User::generateUsername($firstName, $lastName);
        $email    = !empty($username) ? ($username . '@soleipharma.com') : '';

        return response()->json([
            'username'  => $username,
            'email'     => $email,
            'available' => !empty($username),
        ]);
    }

    public function store(Request $request)
    {
        $authUser     = $request->user();
        $isSuperAdmin = $authUser && $authUser->isSuperAdmin();

        if (!$isSuperAdmin) {
            $request->merge(['branch' => $authUser->branch]);
            if ($request->role === 'superadmin') {
                return redirect()->back()->withErrors(['role' => 'No tienes permisos para crear usuarios Superadmin.']);
            }
        }

        $firstName = trim($request->input('first_name', ''));
        $lastName  = trim($request->input('last_name', ''));

        if (empty($request->username) && !empty($firstName) && !empty($lastName)) {
            $generated = User::generateUsername($firstName, $lastName);
            $request->merge([
                'username' => $generated,
                'email'    => $generated ? ($generated . '@soleipharma.com') : $request->email,
            ]);
        } elseif (!empty($request->username) && empty($request->email)) {
            $request->merge([
                'email' => $request->username . '@soleipharma.com',
            ]);
        }

        $isSuperAdminRole = $request->role === 'superadmin';

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'username'   => 'required|string|max:255|unique:users,username',
            'name'       => 'nullable|string|max:255',
            'email'      => 'nullable|email|max:255|unique:users,email',
            'password'   => 'required|string|min:6',
            'role'       => 'required|in:cajero,admin,superadmin',
            'branch'     => $isSuperAdminRole ? 'nullable|string' : 'required|string|exists:branches,name',
        ]);

        $fullName = trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? '')) ?: ($validated['name'] ?? null);

        User::create([
            'first_name'            => $validated['first_name'] ?? null,
            'last_name'             => $validated['last_name'] ?? null,
            'name'                  => $fullName,
            'username'              => $validated['username'],
            'email'                 => $validated['email'] ?? null,
            'password'              => Hash::make($validated['password']),
            'role'                  => $validated['role'],
            'branch'                => $isSuperAdminRole ? null : ($validated['branch'] ?? null),
            'is_locked'             => false,
            'status'                => 'active',
            'failed_login_attempts' => 0,
        ]);

        return redirect()->back()->with('success', 'Usuario creado exitosamente.');
    }

    public function update(Request $request, User $user)
    {
        $authUser     = $request->user();
        $isSuperAdmin = $authUser && $authUser->isSuperAdmin();

        if ($authUser->id === $user->id) {
            return redirect()->back()->withErrors(['error' => 'No puedes editar tu propia cuenta desde este panel.']);
        }

        if (!$isSuperAdmin) {
            if ($user->branch !== $authUser->branch || $user->isSuperAdmin()) {
                return redirect()->back()->withErrors(['error' => 'No tienes permisos para modificar usuarios de otra sucursal o Superadmins.']);
            }
            $request->merge(['branch' => $authUser->branch]);
            if ($request->role === 'superadmin') {
                return redirect()->back()->withErrors(['role' => 'No tienes permisos para asignar rol Superadmin.']);
            }
        }

        $isSuperAdminRole = $request->role === 'superadmin';

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'username'   => 'required|string|max:255|unique:users,username,' . $user->id,
            'name'       => 'nullable|string|max:255',
            'email'      => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'role'       => 'required|in:cajero,admin,superadmin',
            'branch'     => $isSuperAdminRole ? 'nullable|string' : 'required|string|exists:branches,name',
            'password'   => 'nullable|string|min:6',
        ]);

        $fullName = trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? '')) ?: ($validated['name'] ?? $user->name);

        $data = [
            'first_name' => $validated['first_name'] ?? $user->first_name,
            'last_name'  => $validated['last_name'] ?? $user->last_name,
            'name'       => $fullName,
            'username'   => $validated['username'],
            'email'      => $validated['email'] ?? null,
            'role'       => $validated['role'],
            'branch'     => $isSuperAdminRole ? null : ($validated['branch'] ?? null),
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()->back()->with('success', 'Usuario actualizado exitosamente.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.required'  => 'La nueva contraseña es requerida.',
            'password.min'       => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $authUser = $request->user();

        if ($authUser->id === $user->id) {
            return redirect()->back()->withErrors([
                'error' => 'No puedes restablecer la contraseña de tu propia cuenta desde este panel.',
            ]);
        }

        // Verificación estricta de permisos:
        // 1. Superadmin puede restablecer cualquier usuario general o administrativo
        // 2. Admin solo puede restablecer usuarios asignados a su misma sucursal
        if (!$authUser->isSuperAdmin()) {
            if ($authUser->isAdmin()) {
                $authBranch = trim($authUser->branch ?? '');
                $targetBranch = trim($user->branch ?? '');

                if (empty($authBranch) || strcasecmp($authBranch, $targetBranch) !== 0) {
                    return redirect()->back()->withErrors([
                        'error' => "No tienes autorización para restablecer contraseñas de usuarios fuera de tu sucursal asignada ({$authBranch}).",
                    ]);
                }

                if ($user->isSuperAdmin()) {
                    return redirect()->back()->withErrors([
                        'error' => 'No tienes autorización para restablecer la contraseña de una cuenta Superadmin.',
                    ]);
                }
            } else {
                return redirect()->back()->withErrors([
                    'error' => 'No tienes privilegios administrativos para realizar esta acción.',
                ]);
            }
        }

        $user->update([
            'password'              => Hash::make($validated['password']),
            'failed_login_attempts' => 0,
            'is_locked'             => false,
            'status'                => 'active',
            'locked_at'             => null,
        ]);

        return redirect()->back()->with('success', "La contraseña del usuario '{$user->username}' ha sido restablecida exitosamente.");
    }

    public function toggleLock(Request $request, User $user)
    {
        $authUser     = $request->user();
        $isSuperAdmin = $authUser && $authUser->isSuperAdmin();

        if ($authUser->id === $user->id) {
            return redirect()->back()->withErrors(['error' => 'No puedes bloquear tu propia cuenta.']);
        }

        if (!$isSuperAdmin) {
            if ($user->branch !== $authUser->branch || $user->isSuperAdmin()) {
                return redirect()->back()->withErrors(['error' => 'No tienes permisos para modificar el estado de usuarios de otra sucursal o Superadmins.']);
            }
        }

        $newLockState = !$user->is_locked;

        $user->update([
            'is_locked'             => $newLockState,
            'status'                => $newLockState ? 'locked' : 'active',
            'failed_login_attempts' => $newLockState ? $user->failed_login_attempts : 0,
            'locked_at'             => $newLockState ? now() : null,
        ]);

        $statusMsg = $newLockState ? 'bloqueado' : 'desbloqueado';
        return redirect()->back()->with('success', "Usuario {$statusMsg} exitosamente.");
    }
}
