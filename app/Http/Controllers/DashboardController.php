<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\CashSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today  = now()->startOfDay();
        $user   = Auth::user();
        $branch = $user->branch;

        $salesToday = Order::where('created_at', '>=', $today)
            ->when($branch, fn($q) => $q->where('branch', $branch))
            ->sum('total');

        $ordersToday = Order::where('created_at', '>=', $today)
            ->when($branch, fn($q) => $q->where('branch', $branch))
            ->count();

        $lowStockCount = Product::where('stock', '<=', 5)
            ->when($branch, fn($q) => $q->where(fn($sub) => $sub->where('branch', $branch)->orWhereNull('branch')))
            ->count();

        $totalProducts = Product::when($branch, fn($q) => $q->where(fn($sub) => $sub->where('branch', $branch)->orWhereNull('branch')))
            ->count();

        $openSession = CashSession::with('opener:id,username,name')
            ->where('status', 'open')
            ->when($branch, fn($q) => $q->where('branch', $branch))
            ->latest('opened_at')
            ->first();

        $lowStock = Product::where('stock', '<=', 5)
            ->when($branch, fn($q) => $q->where(fn($sub) => $sub->where('branch', $branch)->orWhereNull('branch')))
            ->orderBy('stock')
            ->limit(8)
            ->get(['id', 'name', 'price', 'stock', 'image']);

        $recentSales = Order::with('user:id,username')
            ->when($branch, fn($q) => $q->where('branch', $branch))
            ->latest()
            ->limit(8)
            ->get(['id', 'user_id', 'total', 'status', 'created_at']);

        return Inertia::render('Dashboard', [
            'stats' => [
                'salesToday'    => (float) $salesToday,
                'ordersToday'   => $ordersToday,
                'lowStockCount' => $lowStockCount,
                'totalProducts' => $totalProducts,
            ],
            'lowStock'     => $lowStock,
            'recentSales'  => $recentSales,
            'openSession'  => $openSession,
        ]);
    }
}