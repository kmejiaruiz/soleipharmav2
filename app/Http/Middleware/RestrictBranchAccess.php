<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictBranchAccess
{
    /**
     * Restringe el acceso vía URL a otras sucursales si el usuario tiene una sucursal asignada.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $normalize = function ($str) {
            $s = mb_strtolower(trim((string) $str));
            $s = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $s);
            $s = preg_replace('/^sucursal\s+/', '', $s);
            return trim($s);
        };

        $userBranch = $normalize($user->branch ?? '');

        // Si el usuario no tiene sucursal asignada (ej. superadmin global sin sede), no se restringe
        if (empty($userBranch)) {
            return $next($request);
        }

        $deniedMessage = 'No tiene permisos para este cambio';
        $hasViolation  = false;

        // 1. Acceso directo a administración de sucursales (/branches)
        if ($request->is('branches') || $request->is('branches/*') || $request->routeIs('branches.*')) {
            return redirect()->route('dashboard')->with('branch_denied_modal', $deniedMessage);
        }

        // 2. Parámetros de consulta en la URL (branch, sucursal, etc.)
        $targetBranch = $request->query('branch') ?? $request->query('sucursal');
        if ($targetBranch !== null && $targetBranch !== '') {
            $normalizedTarget = $normalize($targetBranch);
            if ($normalizedTarget === 'all' || $normalizedTarget !== $userBranch) {
                $hasViolation = true;
            }
        }

        // 3. Modelos en la ruta pertenecientes a otra sucursal
        if (!$hasViolation) {
            foreach (['order', 'session', 'discard', 'entry', 'transfer'] as $param) {
                $model = $request->route($param);
                if (!$model) continue;

                if ($param === 'transfer' && isset($model->from_branch, $model->to_branch)) {
                    $from = $normalize($model->from_branch);
                    $to   = $normalize($model->to_branch);
                    if ($from !== $userBranch && $to !== $userBranch) {
                        $hasViolation = true;
                        break;
                    }
                } elseif (isset($model->branch) && !empty($model->branch)) {
                    if ($normalize($model->branch) !== $userBranch) {
                        $hasViolation = true;
                        break;
                    }
                }
            }
        }

        if ($hasViolation) {
            // Si es un recurso directo de otra sucursal o una descarga de PDF, redirigir al dashboard
            $isDirectResource = $request->is('*pdf*') ||
                $request->route('order') ||
                $request->route('session') ||
                $request->route('discard') ||
                $request->route('entry') ||
                $request->route('transfer');

            if ($isDirectResource) {
                return redirect()->route('dashboard')->with('branch_denied_modal', $deniedMessage);
            }

            // Para vistas con filtros de consulta, limpiar parámetros no autorizados de la URL
            $query = $request->query();
            unset($query['branch'], $query['sucursal']);

            $cleanUrl = $request->url() . (!empty($query) ? '?' . http_build_query($query) : '');
            return redirect($cleanUrl)->with('branch_denied_modal', $deniedMessage);
        }

        return $next($request);
    }
}
