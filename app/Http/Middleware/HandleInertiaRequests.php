<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Carousel;
use App\Models\CashSession;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'carousels' => fn () => Carousel::where('active', true)->orderBy('order')->orderByDesc('id')->get(),
            'openCashSession' => fn () => $request->user()
                ? CashSession::with('opener:id,username,name')
                    ->where('status', 'open')
                    ->when($request->user()->branch, fn($q) => $q->where('branch', $request->user()->branch))
                    ->latest('opened_at')
                    ->first()
                : null,
            'flash' => [
                'success'           => fn () => $request->session()->get('success'),
                'error'             => fn () => $request->session()->get('error'),
                'warning'           => fn () => $request->session()->get('warning'),
                'info'              => fn () => $request->session()->get('info'),
                'branchDeniedModal' => fn () => $request->session()->get('branch_denied_modal'),
            ],
        ];
    }
}
