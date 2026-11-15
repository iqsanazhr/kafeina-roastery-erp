<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\RoleMiddleware;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use DatabaseTransactions;

    protected RoleMiddleware $middleware;

    protected Branch $branch;

    protected Role $adminRole;

    protected Role $cashierRole;

    protected Role $roasterRole;

    protected User $adminUser;

    protected User $cashierUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new RoleMiddleware;

        $this->branch = Branch::firstOrCreate(
            ['code' => 'ROAST-01'],
            [
                'name' => 'Central Roastery',
                'type' => 'CENTRAL_ROASTERY',
            ]
        );

        $this->adminRole = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name' => 'Super Administrator']
        );

        $this->cashierRole = Role::firstOrCreate(
            ['name' => 'CASHIER'],
            ['display_name' => 'Cashier Barista']
        );

        $this->roasterRole = Role::firstOrCreate(
            ['name' => 'HEAD_ROASTER'],
            ['display_name' => 'Head Roaster']
        );

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_role_test@kafeina.test'],
            [
                'role_id' => $this->adminRole->id,
                'branch_id' => $this->branch->id,
                'name' => 'Admin User',
                'password' => 'secret',
                'is_active' => true,
            ]
        );

        $this->cashierUser = User::firstOrCreate(
            ['email' => 'cashier_role_test@kafeina.test'],
            [
                'role_id' => $this->cashierRole->id,
                'branch_id' => $this->branch->id,
                'name' => 'Cashier User',
                'password' => 'secret',
                'is_active' => true,
            ]
        );
    }

    public function test_unauthenticated_user_denied(): void
    {
        $request = Request::create('/api/pos/orders', 'GET');
        $response = $this->middleware->handle($request, fn () => new Response('OK', 200), 'cashier');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test_inactive_user_denied(): void
    {
        $this->cashierUser->is_active = false;
        $this->cashierUser->save();

        $request = Request::create('/pos', 'GET');
        $request->setUserResolver(fn () => $this->cashierUser);

        $response = $this->middleware->handle($request, fn () => new Response('OK', 200), 'cashier');
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test_super_admin_bypasses_all_role_gates(): void
    {
        $request = Request::create('/roastery/work-orders', 'GET');
        $request->setUserResolver(fn () => $this->adminUser);

        // Even though route requires roaster, super_admin should bypass!
        $response = $this->middleware->handle($request, fn () => new Response('OK', 200), 'roaster');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function test_alias_matching_allows_access(): void
    {
        $request = Request::create('/pos', 'GET');
        $request->setUserResolver(fn () => $this->cashierUser);

        // User role is uppercase CASHIER, route requires 'branch_cashier'
        $response = $this->middleware->handle($request, fn () => new Response('OK', 200), 'branch_cashier');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function test_unauthorized_access_logs_security_breach(): void
    {
        $request = Request::create('/roastery/work-orders', 'GET');
        $request->setUserResolver(fn () => $this->cashierUser);

        $response = $this->middleware->handle($request, fn () => new Response('OK', 200), 'roaster');
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->cashierUser->id,
            'event_type' => 'SECURITY_BREACH',
            'auditable_type' => User::class,
            'auditable_id' => $this->cashierUser->id,
        ]);
    }
}
