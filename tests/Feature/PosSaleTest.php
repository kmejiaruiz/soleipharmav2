<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PosSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_admin_credentials_returns_user_for_valid_admin(): void
    {
        $admin = User::factory()->create([
            'username' => 'superadmin_test',
            'password' => Hash::make('secret123'),
            'role'     => 'superadmin',
        ]);

        $cashier = User::factory()->create([
            'username' => 'cajero_test',
            'password' => Hash::make('secret123'),
            'role'     => 'cajero',
        ]);

        // Credenciales correctas de admin
        $verified = User::verifyAdminCredentials('superadmin_test', 'secret123');
        $this->assertNotNull($verified);
        $this->assertEquals($admin->id, $verified->id);

        // Clave incorrecta
        $this->assertNull(User::verifyAdminCredentials('superadmin_test', 'wrongpassword'));

        // Usuario cajero no es admin
        $this->assertNull(User::verifyAdminCredentials('cajero_test', 'secret123'));
    }

    public function test_pos_sale_rejects_when_no_cash_session_is_open(): void
    {
        $user = User::factory()->create(['role' => 'cajero', 'branch' => 'Sucursal Managua']);

        $product = Product::create([
            'name'       => 'Ibuprofeno 400mg',
            'price'      => 10.00,
            'stock'      => 5,
            'available'  => true,
            'branch'     => 'Sucursal Managua',
        ]);

        $response = $this->actingAs($user)->postJson(route('pos.sale'), [
            'items' => [
                ['id' => $product->id, 'qty' => 1, 'price' => 10.00],
            ],
            'pay_method' => 'efectivo',
            'amount_paid' => 10.00,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'No hay una sesión de caja abierta en esta sucursal. Debe abrir caja antes de facturar.',
        ]);
    }

    public function test_pos_sale_uses_database_price_and_logs_inventory_decrement(): void
    {
        $user = User::factory()->create([
            'role' => 'cajero',
            'branch' => 'Sucursal Managua',
        ]);

        $session = CashSession::create([
            'opened_by'      => $user->id,
            'opening_amount' => 500.00,
            'status'         => 'open',
            'branch'         => 'Sucursal Managua',
            'opened_at'      => now(),
        ]);

        $product = Product::create([
            'name'       => 'Amoxicilina 500mg',
            'price'      => 85.50, // Precio oficial en BD
            'stock'      => 20,
            'available'  => true,
            'branch'     => 'Sucursal Managua',
        ]);

        // Petición alterada enviando price = 0.50 en lugar del precio real 85.50
        $response = $this->actingAs($user)->postJson(route('pos.sale'), [
            'items' => [
                [
                    'id'    => $product->id,
                    'qty'   => 2,
                    'price' => 0.50, // Intento de manipulación de precio
                ],
            ],
            'pay_method'     => 'efectivo',
            'amount_paid'    => 200.00,
            'client_name'    => 'Juan Perez',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'  => true,
            'subtotal' => 171.00, // 85.50 * 2 = 171.00 (ignora 0.50)
            'total'    => 171.00,
            'change'   => 29.00,
        ]);

        // Verificar que el stock se descontó correctamente
        $product->refresh();
        $this->assertEquals(18, $product->stock);

        // Verificar que la orden guardó el client_name y total correcto
        $order = Order::latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals('Juan Perez', $order->client_name);
        $this->assertEquals(171.00, (float) $order->total);

        // Verificar el OrderItem
        $item = OrderItem::where('order_id', $order->id)->first();
        $this->assertEquals(85.50, (float) $item->price);

        // Verificar que se creó el InventoryLog
        $log = InventoryLog::where('product_id', $product->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals('stock_decrease', $log->change_type);
        $this->assertEquals(20, $log->previous_stock);
        $this->assertEquals(18, $log->new_stock);
        $this->assertEquals($user->username, $log->admin_name);
    }

    public function test_validate_auth_is_throttled(): void
    {
        $user = User::factory()->create(['role' => 'cajero']);

        for ($i = 0; $i < 6; $i++) {
            $response = $this->actingAs($user)->postJson(route('cash.validateAuth'), [
                'username' => 'wrong',
                'password' => 'wrong',
            ]);
            $response->assertStatus(422);
        }

        // El séptimo intento consecutivo debe ser bloqueado por rate limit (429)
        $blocked = $this->actingAs($user)->postJson(route('cash.validateAuth'), [
            'username' => 'wrong',
            'password' => 'wrong',
        ]);
        $blocked->assertStatus(429);
    }
}
