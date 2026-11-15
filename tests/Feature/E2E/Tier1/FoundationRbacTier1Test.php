<?php

namespace Tests\Feature\E2E\Tier1;

use App\Http\Middleware\RoleMiddleware;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CashierPinAuthService;
use DomainException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FoundationRbacTier1Test extends TestCase
{
    use DatabaseTransactions;

    protected CashierPinAuthService $pinService;

    protected Branch $branch;

    protected Role $adminRole;

    protected Role $cashierRole;

    protected Role $roasterRole;

    protected User $cashierUser;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->pinService = new CashierPinAuthService;

        $this->branch = Branch::firstOrCreate(
            ['code' => 'HQ-01'],
            [
                'name' => 'KAFEINA Headquarters',
                'type' => 'HEADQUARTERS',
                'address' => 'Jl. Kopi No. 1, Jakarta',
                'phone' => '021-5551234',
                'is_active' => true,
            ]
        );

        $this->adminRole = Role::firstOrCreate(
            ['name' => 'SUPER_ADMIN'],
            ['display_name' => 'Super Administrator']
        );

        $this->cashierRole = Role::firstOrCreate(
            ['name' => 'CASHIER'],
            ['display_name' => 'Branch Cashier']
        );

        $this->roasterRole = Role::firstOrCreate(
            ['name' => 'HEAD_ROASTER'],
            ['display_name' => 'Head Roaster']
        );

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_e2e_tier1@kafeina.test'],
            [
                'role_id' => $this->adminRole->id,
                'branch_id' => $this->branch->id,
                'name' => 'Admin Tier1',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]
        );
        $this->adminUser->role_id = $this->adminRole->id;
        $this->adminUser->is_active = true;
        $this->adminUser->save();
        $this->adminUser->load('role');

        $this->cashierUser = User::firstOrCreate(
            ['email' => 'cashier_e2e_tier1@kafeina.test'],
            [
                'role_id' => $this->cashierRole->id,
                'branch_id' => $this->branch->id,
                'name' => 'Cashier Tier1',
                'password' => Hash::make('password123'),
                'pin_code' => Hash::make('123456'),
                'is_active' => true,
            ]
        );
        $this->cashierUser->role_id = $this->cashierRole->id;
        $this->cashierUser->is_active = true;
        $this->cashierUser->save();
        $this->cashierUser->load('role');
    }

    /**
     * Feature 30: Happy Path — Valid 6-digit PIN authenticates successfully.
     */
    public function test_valid_6_digit_pin_authenticates_successfully(): void
    {
        $result = $this->pinService->verifyPin($this->cashierUser, '123456');

        $this->assertTrue($result);
        $this->assertFalse($this->pinService->isLockedOut($this->cashierUser));
        $this->assertEquals(0, $this->pinService->getFailedAttempts($this->cashierUser));
    }

    /**
     * Feature 30: Invalid Partition — Incorrect PIN returns false and increments failed attempts.
     */
    public function test_incorrect_pin_fails_authentication(): void
    {
        $result = $this->pinService->verifyPin($this->cashierUser, '999999');

        $this->assertFalse($result);
        $this->assertEquals(1, $this->pinService->getFailedAttempts($this->cashierUser));
        $this->assertFalse($this->pinService->isLockedOut($this->cashierUser));
    }

    /**
     * Feature 30: Invalid Parameter — Non-numeric PIN format is rejected.
     */
    public function test_non_numeric_pin_format_is_rejected(): void
    {
        $result = $this->pinService->verifyPin($this->cashierUser, 'abcdef');

        $this->assertFalse($result);
        $this->assertEquals(1, $this->pinService->getFailedAttempts($this->cashierUser));
    }

    /**
     * Feature 30: Setting new PIN updates hashed pin_code and resets attempts.
     */
    public function test_set_pin_updates_user_pin_and_verifies(): void
    {
        $this->pinService->setPin($this->cashierUser, '654321');
        $this->cashierUser->refresh();

        $this->assertTrue($this->pinService->verifyPin($this->cashierUser, '654321'));
        $this->assertFalse($this->pinService->verifyPin($this->cashierUser, '123456'));
    }

    /**
     * Feature 31: 5-Strike Lockout — 5 consecutive failed attempts trigger account lockout.
     */
    public function test_five_consecutive_failed_pins_triggers_account_lockout(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->assertFalse($this->pinService->verifyPin($this->cashierUser, '000000'));
            $this->assertEquals($i, $this->pinService->getFailedAttempts($this->cashierUser));
            $this->assertFalse($this->pinService->isLockedOut($this->cashierUser));
        }

        // 5th strike
        $this->assertFalse($this->pinService->verifyPin($this->cashierUser, '000000'));
        $this->assertTrue($this->pinService->isLockedOut($this->cashierUser));
        $this->assertGreaterThan(0, $this->pinService->getRemainingLockoutSeconds($this->cashierUser));
    }

    /**
     * Feature 31: State Constraint — Verification during lockout throws DomainException.
     */
    public function test_verification_attempt_during_lockout_throws_exception(): void
    {
        // Force 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->pinService->verifyPin($this->cashierUser, '000000');
        }

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Akun terkunci sementara');

        $this->pinService->verifyPin($this->cashierUser, '123456');
    }

    /**
     * Feature 31: Successful authentication resets previous failed attempts counter.
     */
    public function test_successful_auth_resets_failed_attempts(): void
    {
        // 3 failed attempts
        $this->pinService->verifyPin($this->cashierUser, '000000');
        $this->pinService->verifyPin($this->cashierUser, '000000');
        $this->pinService->verifyPin($this->cashierUser, '000000');
        $this->assertEquals(3, $this->pinService->getFailedAttempts($this->cashierUser));

        // Successful authentication
        $this->assertTrue($this->pinService->verifyPin($this->cashierUser, '123456'));
        $this->assertEquals(0, $this->pinService->getFailedAttempts($this->cashierUser));
    }

    /**
     * Feature 31 & 32: 5th strike logs a SECURITY_BREACH event in audit_logs.
     */
    public function test_lockout_logs_security_breach_in_audit_logs(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->pinService->verifyPin($this->cashierUser, '000000', '192.168.1.50');
        }

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->cashierUser->id,
            'event_type' => 'SECURITY_BREACH',
            'auditable_type' => User::class,
            'auditable_id' => $this->cashierUser->id,
            'ip_address' => '192.168.1.50',
        ]);
    }

    /**
     * Feature 32: Transactional Audit Trail — Observer records INSERT on observed model creation.
     */
    public function test_audit_log_observer_records_insert_event(): void
    {
        Auth::login($this->adminUser);

        $supplier = Supplier::create([
            'code' => 'SUP-AUD-'.uniqid(),
            'name' => 'Supplier Audit Test',
            'contact_person' => 'Pak Tani',
            'phone' => '08123456789',
            'address' => 'Desa Kopi',
            'farm_location' => 'Gayo Highlands',
            'altitude_masl' => 1500,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-AUD-'.uniqid(),
            'supplier_id' => $supplier->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->adminUser->id,
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(7),
            'total_amount' => 5000000,
            'status' => 'DRAFT',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->adminUser->id,
            'event_type' => 'INSERT',
            'auditable_type' => PurchaseOrder::class,
            'auditable_id' => $po->id,
        ]);
    }

    /**
     * Feature 32: Transactional Audit Trail — Observer records UPDATE with old and new values.
     */
    public function test_audit_log_observer_records_update_event(): void
    {
        Auth::login($this->adminUser);

        $supplier = Supplier::create([
            'code' => 'SUP-UPD-'.uniqid(),
            'name' => 'Supplier Update Test',
            'contact_person' => 'Pak Tani',
            'phone' => '08123456789',
            'address' => 'Desa Kopi',
            'farm_location' => 'Gayo Highlands',
            'altitude_masl' => 1500,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-UPD-'.uniqid(),
            'supplier_id' => $supplier->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->adminUser->id,
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(7),
            'total_amount' => 5000000,
            'status' => 'DRAFT',
        ]);

        $po->update(['status' => 'ISSUED', 'total_amount' => 5500000]);

        $audit = AuditLog::where('auditable_type', PurchaseOrder::class)
            ->where('auditable_id', $po->id)
            ->where('event_type', 'UPDATE')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals('ISSUED', $audit->new_values['status']);
    }

    /**
     * Feature 29: RBAC Route Gatekeeper — RoleMiddleware allows authorized role.
     */
    public function test_role_middleware_allows_authorized_role(): void
    {
        $middleware = new RoleMiddleware;
        $request = Request::create('/admin/dashboard', 'GET');
        $request->setUserResolver(fn () => $this->adminUser);

        $response = $middleware->handle($request, function ($req) {
            return new Response('Access Granted', 200);
        }, $this->adminRole->name);

        $this->assertEquals(200, $response->getStatusCode(), 'Role Middleware Failed: '.$response->getContent());
        $this->assertEquals('Access Granted', $response->getContent());
    }

    /**
     * Feature 29: RBAC Route Gatekeeper — RoleMiddleware denies unauthorized role with HTTP 403.
     */
    public function test_role_middleware_denies_unauthorized_role(): void
    {
        $middleware = new RoleMiddleware;
        $request = Request::create('/roastery/work-orders', 'GET');
        $request->setUserResolver(fn () => $this->cashierUser);

        $response = $middleware->handle($request, function ($req) {
            return new Response('Access Granted', 200);
        }, $this->roasterRole->name);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Feature 29: RBAC Route Gatekeeper — RoleMiddleware allows any role in a pipe-delimited list.
     */
    public function test_role_middleware_allows_multiple_authorized_roles(): void
    {
        $middleware = new RoleMiddleware;
        $request = Request::create('/pos', 'GET');
        $request->setUserResolver(fn () => $this->cashierUser);

        $response = $middleware->handle($request, function ($req) {
            return new Response('Access POS', 200);
        }, $this->adminRole->name, $this->cashierRole->name);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Access POS', $response->getContent());
    }

    /**
     * Feature 29: Inactive user is denied access by RoleMiddleware with HTTP 403.
     */
    public function test_role_middleware_denies_inactive_user(): void
    {
        $this->cashierUser->update(['is_active' => false]);

        $middleware = new RoleMiddleware;
        $request = Request::create('/pos', 'GET');
        $request->setUserResolver(fn () => $this->cashierUser);

        $response = $middleware->handle($request, function ($req) {
            return new Response('Access POS', 200);
        }, $this->cashierRole->name);

        $this->assertEquals(403, $response->getStatusCode());
    }
}
