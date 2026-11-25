<?php

namespace Tests\Feature;

use App\Http\Middleware\RestrictBranchAccess;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class BranchAccessRestrictionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('users')) {
            Schema::create('users', function ($table) {
                $table->increments('id');
                $table->string('username')->unique();
                $table->string('name')->nullable();
                $table->string('password');
                $table->string('role')->default('user');
                $table->string('branch')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function ($table) {
                $table->increments('id');
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bodega_access_requests')) {
            Schema::create('bodega_access_requests', function ($table) {
                $table->increments('id');
                $table->integer('user_id');
                $table->string('branch');
                $table->string('status')->default('pending');
                $table->integer('responded_by_id')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->timestamps();
            });
        }
    }
    public function test_leon_user_is_redirected_when_querying_managua_via_url(): void
    {
        $middleware = new RestrictBranchAccess();

        $user = new User([
            'username' => 'user_leon',
            'role'     => 'admin',
            'branch'   => 'Sucursal Leon',
        ]);

        $request = Request::create('/products?branch=Sucursal%20Managua', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->assertTrue($response->isRedirection());
        $this->assertEquals(url('/products'), $response->headers->get('Location'));
    }

    public function test_managua_user_is_redirected_when_querying_leon_via_url(): void
    {
        $middleware = new RestrictBranchAccess();

        $user = new User([
            'username' => 'user_managua',
            'role'     => 'cajero',
            'branch'   => 'Sucursal Managua',
        ]);

        $request = Request::create('/reports/sales?branch=Sucursal%20Leon', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->assertTrue($response->isRedirection());
        $this->assertEquals(url('/reports/sales'), $response->headers->get('Location'));
    }

    public function test_branch_user_blocked_from_querying_all_branches(): void
    {
        $middleware = new RestrictBranchAccess();

        $user = new User([
            'username' => 'user_managua',
            'role'     => 'admin',
            'branch'   => 'Sucursal Managua',
        ]);

        $request = Request::create('/reports/sales?branch=all', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->assertTrue($response->isRedirection());
        $this->assertEquals(url('/reports/sales'), $response->headers->get('Location'));
    }

    public function test_branch_user_can_access_own_branch(): void
    {
        $middleware = new RestrictBranchAccess();

        $user = new User([
            'username' => 'user_managua',
            'role'     => 'admin',
            'branch'   => 'Sucursal Managua',
        ]);

        $request = Request::create('/products?branch=Sucursal%20Managua', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_branch_user_blocked_from_branches_admin_url(): void
    {
        $middleware = new RestrictBranchAccess();

        $user = new User([
            'username' => 'user_branch',
            'role'     => 'admin',
            'branch'   => 'Sucursal Chinandega',
        ]);

        $request = Request::create('/branches', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->assertTrue($response->isRedirection());
        $this->assertEquals(route('dashboard'), $response->headers->get('Location'));
    }

    public function test_superadmin_has_null_branch(): void
    {
        $superadmin = new User([
            'username' => 'superadmin_test',
            'role'     => 'superadmin',
            'branch'   => 'Sucursal Leon',
        ]);

        $this->assertNull($superadmin->branch);
    }

    public function test_superadmin_not_restricted_by_middleware(): void
    {
        $middleware = new RestrictBranchAccess();

        $superadmin = new User([
            'username' => 'superadmin_test',
            'role'     => 'superadmin',
        ]);

        $request = Request::create('/products?branch=Sucursal%20Leon', 'GET');
        $request->setUserResolver(fn () => $superadmin);

        $response = $middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_superadmin_can_request_bodega_access_and_branch_admin_can_approve(): void
    {
        $superadmin = User::where('role', 'superadmin')->first();
        if (!$superadmin) {
            $superadmin = User::create([
                'username'   => 'super_tester',
                'name'       => 'Super Tester',
                'password'   => bcrypt('password'),
                'role'       => 'superadmin',
            ]);
        }

        $branchAdmin = User::where('role', 'admin')->where('branch', 'Sucursal Leon')->first();
        if (!$branchAdmin) {
            $branchAdmin = User::create([
                'username'   => 'admin_leon_test',
                'name'       => 'Admin Leon',
                'password'   => bcrypt('password'),
                'role'       => 'admin',
                'branch'     => 'Sucursal Leon',
            ]);
        }
        \App\Models\Branch::firstOrCreate(['name' => 'Sucursal Leon']);
        \App\Models\Branch::firstOrCreate(['name' => 'Sucursal Managua']);

        // 1. Superadmin solicita acceso a Bodega de Sucursal Leon
        $response = $this->actingAs($superadmin)->postJson(route('bodega.request-access'), [
            'branch' => 'Sucursal Leon',
        ]);
        $response->assertOk();
        $this->assertDatabaseHas('bodega_access_requests', [
            'user_id' => $superadmin->id,
            'branch'  => 'Sucursal Leon',
            'status'  => 'pending',
        ]);

        $accessRequest = \App\Models\BodegaAccessRequest::where('user_id', $superadmin->id)
            ->where('branch', 'Sucursal Leon')
            ->where('status', 'pending')
            ->first();

        // 2. Administrador de Sucursal Leon detecta la solicitud pendiente
        $pendingCheck = $this->actingAs($branchAdmin)->getJson(route('bodega.pending-requests'));
        $pendingCheck->assertOk();
        $this->assertEquals($accessRequest->id, $pendingCheck->json('pending.id'));

        // 3. Administrador de Sucursal Leon aprueba la solicitud
        $respond = $this->actingAs($branchAdmin)->postJson(route('bodega.access-requests.respond', $accessRequest->id), [
            'decision' => 'approved',
        ]);
        $respond->assertOk();
        $this->assertDatabaseHas('bodega_access_requests', [
            'id'     => $accessRequest->id,
            'status' => 'approved',
            'responded_by_id' => $branchAdmin->id,
        ]);

        // 4. Superadmin consulta estado -> aprobado y sesión actualizada
        $statusCheck = $this->actingAs($superadmin)->getJson(route('bodega.access-requests.status', $accessRequest->id));
        $statusCheck->assertOk();
        $this->assertEquals('approved', $statusCheck->json('status'));
        $this->assertEquals('Sucursal Leon', session('bodega_authorized_branch'));

        // 5. Superadmin puede cancelar una nueva solicitud
        $accessRequest2 = \App\Models\BodegaAccessRequest::create([
            'user_id' => $superadmin->id,
            'branch'  => 'Sucursal Managua',
            'status'  => 'pending',
        ]);

        $cancel = $this->actingAs($superadmin)->post(route('bodega.access-requests.cancel', $accessRequest2->id));
        $this->assertDatabaseHas('bodega_access_requests', [
            'id'     => $accessRequest2->id,
            'status' => 'cancelled',
        ]);
    }
}
