<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\CashWithdrawal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThermalReceiptPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_withdrawal_thermal_pdf_returns_success(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch' => 'Principal']);
        $session = CashSession::create([
            'opened_by'      => $admin->id,
            'branch'         => 'Principal',
            'opening_amount' => 1000.00,
            'status'         => 'open',
            'opened_at'      => now(),
        ]);
        $withdrawal = CashWithdrawal::create([
            'session_id'    => $session->id,
            'withdrawn_by'  => $admin->id,
            'total_amount'  => 250.00,
            'denominations' => ['100' => 2, '50' => 1],
            'reason'        => 'Pago a proveedor de prueba',
        ]);

        $html = view('reports.cash_withdrawal_ticket_pdf', ['w' => $withdrawal])->render();
        $this->assertStringContainsString('FARMACIA SOLEI', $html);
        $this->assertStringContainsString('COMPROBANTE DE RETIRO DE CAJA', $html);
        $this->assertStringContainsString('Pago a proveedor de prueba', $html);
        $this->assertStringContainsString('Firma Cajero', $html);

        $response = $this->actingAs($admin)->get(route('cash.withdrawal.pdf', $withdrawal->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        // Verificar que el PDF generado es válido
        $pdfOutput = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $pdfOutput);
    }

    public function test_cash_session_thermal_pdf_returns_success(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch' => 'Principal']);
        $session = CashSession::create([
            'opened_by'      => $admin->id,
            'closed_by'      => $admin->id,
            'branch'         => 'Principal',
            'opening_amount' => 500.00,
            'status'         => 'closed',
            'opened_at'      => now()->subHours(8),
            'closed_at'      => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('cash.session.pdf', $session->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_pos_receipt_thermal_pdf_returns_success(): void
    {
        $user = User::factory()->create(['role' => 'cajero', 'branch' => 'Principal']);
        $product = Product::create([
            'name'      => 'Paracetamol 500mg',
            'price'     => 15.00,
            'stock'     => 50,
            'available' => true,
            'branch'    => 'Principal',
        ]);
        $order = Order::create([
            'user_id'        => $user->id,
            'total'          => 30.00,
            'status'         => 'completed',
            'payment_method' => 'cash',
            'amount_paid'    => 50.00,
            'change_given'   => 20.00,
            'branch'         => 'Principal',
            'client_name'    => 'Cliente Ocasional',
        ]);
        OrderItem::create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'quantity'   => 2,
            'price'      => 15.00,
        ]);

        $response = $this->actingAs($user)->get(route('pos.receipt', $order->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_cash_session_close_requires_admin_credentials(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin_close',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role'     => 'admin',
            'branch'   => 'Principal',
        ]);

        $cajero = User::factory()->create([
            'username' => 'cajero_close',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role'     => 'cajero',
            'branch'   => 'Principal',
        ]);

        $session = CashSession::create([
            'opened_by'      => $cajero->id,
            'branch'         => 'Principal',
            'opening_amount' => 500.00,
            'status'         => 'open',
            'opened_at'      => now(),
        ]);

        // 1. Sin credenciales de admin debe fallar
        $responseNoAuth = $this->actingAs($cajero)->post(route('cash.close'), [
            'counted_amount' => 500.00,
        ]);
        $responseNoAuth->assertSessionHasErrors(['admin_username', 'admin_password']);

        // 2. Con credenciales de cajero (no admin) debe fallar
        $responseWrongRole = $this->actingAs($cajero)->post(route('cash.close'), [
            'counted_amount' => 500.00,
            'admin_username' => 'cajero_close',
            'admin_password' => 'password123',
        ]);
        $responseWrongRole->assertSessionHasErrors(['auth']);

        // 3. Con credenciales de admin válidas debe cerrar exitosamente
        $responseSuccess = $this->actingAs($cajero)->post(route('cash.close'), [
            'counted_amount' => 500.00,
            'admin_username' => 'admin_close',
            'admin_password' => 'password123',
        ]);
        $responseSuccess->assertSessionHasNoErrors();
        $responseSuccess->assertSessionHas('closed_session_id', $session->id);

        $this->assertEquals('closed', $session->fresh()->status);
        $this->assertEquals($admin->id, $session->fresh()->closed_by);
    }
}
