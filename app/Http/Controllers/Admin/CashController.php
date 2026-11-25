<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use App\Models\CashWithdrawal;
use App\Models\CashClosingCount;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Inertia\Inertia;

class CashController extends Controller
{
    // ── Dashboard de Caja ──────────────────────────────────────────────────────
    public function index()
    {
        $user   = Auth::user();
        $branch = $user->branch;

        $openSession = CashSession::with(['opener:id,username,name', 'withdrawals.withdrawer:id,username', 'orders'])
            ->where('status', 'open')
            ->when($branch, fn($q) => $q->where('branch', $branch))
            ->latest('opened_at')
            ->first();

        $metrics = null;
        if ($openSession) {
            $openingAmount   = (float) $openSession->opening_amount;
            $cashSales       = (float) Order::where('cash_session_id', $openSession->id)
                ->where('status', 'completado')
                ->where('payment_method', 'efectivo')
                ->sum('total');

            $cardSales       = (float) Order::where('cash_session_id', $openSession->id)
                ->where('status', 'completado')
                ->where('payment_method', 'tarjeta')
                ->sum('total');

            $transferSales   = (float) Order::where('cash_session_id', $openSession->id)
                ->where('status', 'completado')
                ->where('payment_method', 'transferencia')
                ->sum('total');

            $totalWithdrawals= (float) CashWithdrawal::where('session_id', $openSession->id)->sum('total_amount');
            $expectedCash    = round($openingAmount + $cashSales - $totalWithdrawals, 2);

            $metrics = [
                'openingAmount'    => $openingAmount,
                'cashSales'        => $cashSales,
                'cardSales'        => $cardSales,
                'transferSales'    => $transferSales,
                'totalSales'       => round($cashSales + $cardSales + $transferSales, 2),
                'totalWithdrawals' => $totalWithdrawals,
                'expectedCash'     => $expectedCash,
                'ordersCount'      => Order::where('cash_session_id', $openSession->id)->count(),
            ];
        }

        return Inertia::render('Admin/Cash/Index', [
            'openSession' => $openSession,
            'metrics'     => $metrics,
        ]);
    }

    // ── Abrir Sesión de Caja ───────────────────────────────────────────────────
    public function openSession(Request $request)
    {
        $user = Auth::user();

        // Verificar que no haya una caja ya abierta
        $existing = CashSession::where('status', 'open')
            ->when($user->branch, fn($q) => $q->where('branch', $user->branch))
            ->exists();

        if ($existing) {
            return redirect()->back()->withErrors(['session' => 'Ya existe una sesión de caja abierta.']);
        }

        $validated = $request->validate([
            'opening_amount' => 'required|numeric|min:0',
            'notes'          => 'nullable|string',
        ]);

        CashSession::create([
            'opened_by'      => $user->id,
            'opening_amount' => $validated['opening_amount'],
            'status'         => 'open',
            'notes'          => $validated['notes'] ?? null,
            'branch'         => $user->branch,
            'opened_at'      => now(),
        ]);

        return redirect()->back()->with('success', 'Sesión de caja abierta exitosamente.');
    }

    // ── Registrar Retiro de Efectivo ───────────────────────────────────────────
    public function withdraw(Request $request)
    {
        $user = Auth::user();
        $sessionId = $request->input('session_id');

        if ($sessionId && in_array($user->role, ['admin', 'superadmin'])) {
            $session = CashSession::find($sessionId);
        } else {
            $session = CashSession::where('status', 'open')
                ->when($user->branch, fn($q) => $q->where('branch', $user->branch))
                ->latest('opened_at')
                ->first();
        }

        if (!$session) {
            return redirect()->back()->withErrors(['session' => 'No se encontró la sesión de caja.']);
        }

        $validated = $request->validate([
            'total_amount'  => 'required|numeric|min:0.01',
            'reason'        => 'required|string|min:3',
            'denominations' => 'nullable|array',
            'session_id'    => 'nullable|exists:cash_sessions,id',
        ]);

        $withdrawal = CashWithdrawal::create([
            'session_id'    => $session->id,
            'withdrawn_by'  => $user->id,
            'total_amount'  => $validated['total_amount'],
            'denominations' => $validated['denominations'] ?? [],
            'reason'        => $validated['reason'],
        ]);

        return redirect()->back()
            ->with('success', 'Retiro de caja registrado exitosamente.')
            ->with('withdrawal_id', $withdrawal->id);
    }

    // ── Arqueo y Cierre de Caja ────────────────────────────────────────────────
    public function closeSession(Request $request)
    {
        $user = Auth::user();

        $openSession = CashSession::where('status', 'open')
            ->when($user->branch, fn($q) => $q->where('branch', $user->branch))
            ->latest('opened_at')
            ->first();

        if (!$openSession) {
            return redirect()->back()->withErrors(['session' => 'No hay ninguna sesión de caja abierta.']);
        }

        $validated = $request->validate([
            'counted_amount' => 'required|numeric|min:0',
            'denominations'  => 'nullable|array',
            'admin_username' => 'required|string',
            'admin_password' => 'required|string',
        ]);

        // Es OBLIGATORIO validar credenciales de un admin o superadmin para autorizar el cierre
        $adminUser = User::verifyAdminCredentials($validated['admin_username'], $validated['admin_password']);

        if (!$adminUser) {
            return redirect()->back()->withErrors([
                'auth' => 'Credenciales inválidas o el usuario no tiene permisos de Administrador o Superadmin.',
            ]);
        }

        $closedByUserId = $adminUser->id;

        DB::beginTransaction();
        try {
            $openingAmount    = (float) $openSession->opening_amount;
            $cashSales        = (float) Order::where('cash_session_id', $openSession->id)
                ->where('status', 'completado')
                ->where('payment_method', 'efectivo')
                ->sum('total');

            $totalWithdrawals = (float) CashWithdrawal::where('session_id', $openSession->id)->sum('total_amount');
            $expectedCash     = round($openingAmount + $cashSales - $totalWithdrawals, 2);
            $countedAmount    = round((float) $validated['counted_amount'], 2);
            $difference       = round($countedAmount - $expectedCash, 2);

            CashClosingCount::create([
                'session_id'     => $openSession->id,
                'counted_by'     => $user->id,
                'counted_amount' => $countedAmount,
                'expected_amount'=> $expectedCash,
                'difference'     => $difference,
                'denominations'  => $validated['denominations'] ?? [],
            ]);

            $openSession->update([
                'status'    => 'closed',
                'closed_by' => $closedByUserId,
                'closed_at' => now(),
            ]);

            DB::commit();
            return redirect()->back()
                ->with('success', "Caja cerrada exitosamente. Diferencia registrada: C$ {$difference}")
                ->with('closed_session_id', $openSession->id);
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Error al cerrar caja: ' . $e->getMessage()]);
        }
    }

    // ── Historial de Cierres de Caja ───────────────────────────────────────────
    public function history()
    {
        $user = Auth::user();

        $sessions = CashSession::with(['opener:id,username', 'closer:id,username', 'closingCount', 'withdrawals.withdrawer:id,username'])
            ->where('status', 'closed')
            ->when($user->branch, fn($q) => $q->where('branch', $user->branch))
            ->latest('closed_at')
            ->paginate(15);

        return Inertia::render('Admin/Cash/History', [
            'sessions' => $sessions,
        ]);
    }

    public function sessionPdf(CashSession $session)
    {
        $session->load(['closingCount', 'withdrawals.withdrawer', 'opener', 'closer']);
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Courier');

        $dompdf = new \Dompdf\Dompdf($options);
        $html   = view('reports.cash_session_ticket_pdf', ['session' => $session])->render();

        $dompdf->loadHtml($html);
        $hasDenom = !empty($session->closingCount?->denominations);
        $withCount = $session->withdrawals->count();
        $height = max(550, 480 + ($withCount * 18) + ($hasDenom ? 130 : 0));
        $dompdf->setPaper([0, 0, 226.77, $height], 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Ticket_Cierre_#CS-' . str_pad($session->id, 4, '0', STR_PAD_LEFT) . '.pdf"',
        ]);
    }

    public function withdrawalPdf(CashWithdrawal $withdrawal)
    {
        $withdrawal->load(['session', 'cashSession', 'withdrawer']);
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Courier');

        $dompdf = new \Dompdf\Dompdf($options);
        $html   = view('reports.cash_withdrawal_ticket_pdf', ['w' => $withdrawal])->render();

        $dompdf->loadHtml($html);
        $baseHeight = 490;
        $denominations = is_array($withdrawal->denominations) ? $withdrawal->denominations : json_decode($withdrawal->denominations ?? '[]', true);
        if (is_array($denominations)) {
            foreach ($denominations as $qty) {
                if ((int)$qty > 0) $baseHeight += 14;
            }
        }
        $dompdf->setPaper([0, 0, 226.77, $baseHeight], 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Ticket_Retiro_#RET-' . str_pad($withdrawal->id, 4, '0', STR_PAD_LEFT) . '.pdf"',
        ]);
    }

    public function validateAuth(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $adminUser = User::verifyAdminCredentials($request->username, $request->password);

        if (!$adminUser) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas o no autorizadas.',
            ], 422);
        }

        return response()->json([
            'success'  => true,
            'username' => $adminUser->username,
            'name'     => $adminUser->name,
        ]);
    }
}