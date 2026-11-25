<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InventoryLogController extends Controller
{
    public function index(Request $request)
    {
        $user   = Auth::user();
        $q      = $request->get('q');
        $type   = $request->get('type');
        $hasBranch = $user && !empty($user->branch);

        // Superadmin sin sucursal asignada puede filtrar por sucursal o ver 'all'. Otros usuarios solo ven su propia sucursal.
        if ($user && $user->isSuperAdmin() && !$hasBranch) {
            $branch = $request->get('branch', 'all');
            $branches = ['Sucursal Managua', 'Sucursal Leon'];
        } else {
            $branch = $user ? $user->branch : 'all';
            $branches = $hasBranch ? [$user->branch] : ['Sucursal Managua', 'Sucursal Leon'];
        }

        $logs = InventoryLog::query()
            ->with('product')
            ->when($q, function ($query, $q) {
                $query->where(function ($subQ) use ($q) {
                    $subQ->whereHas('product', function ($sub) use ($q) {
                        $sub->where('name', 'like', "%{$q}%")
                            ->orWhere('barcode', 'like', "%{$q}%");
                    })->orWhere('description', 'like', "%{$q}%")
                      ->orWhere('admin_name', 'like', "%{$q}%");
                });
            })
            ->when($type, fn($query) => $query->where('change_type', $type))
            ->when($branch && $branch !== 'all', fn($query) => $query->where('branch', $branch))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/InventoryLogs/Index', [
            'logs'         => $logs,
            'branches'     => $branches,
            'isSuperAdmin' => $user ? $user->isSuperAdmin() : false,
            'filters'      => [
                'q'      => $q,
                'type'   => $type,
                'branch' => $branch,
            ],
        ]);
    }
}
