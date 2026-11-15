<?php

namespace Tests\Feature\E2E\Tier2;

use App\Http\Middleware\RoleMiddleware;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CashierPinAuthService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Tests\TestCase;

class BoundarySecurityTier2Test extends TestCase
{
    use DatabaseTransactions;

    protected CashierPinAuthService $pinService;

    protected Branch $branch;

    protected Role $cashierRole;

    protected User $cashierUser;

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

        $this->cashierRole = Role::firstOrCreate(
            ['name' => 'branch_cashier'],
            ['display_name' => 'Branch Cashier']
        );

        $this->cashierUser = User::firstOrCreate(
            ['email' => 'cashier_bva@kafeina.test'],
            [
                'role_id' => $this->cashierRole->id,
                'branch_id' => $this->branch->id,
                'name' => 'Cashier BVA',
                'password' => Hash::make('password123'),
                'pin_code' => Hash::make('045678'),
                'is_active' => true,
            ]
        );
    }

    /**
     * BVA: PIN with 5 digits (lower boundary - 1) is rejected.
     */
    public function test_pin_with_5_digits_is_rejected(): void
    {
        $this->assertFalse($this->pinService->verifyPin($this->cashierUser, '04567'));
        $this->assertEquals(1, $this->pinService->getFailedAttempts($this->cashierUser));
    }

    /**
     * BVA: PIN with exactly 6 digits starting with 0 is accepted.
     */
    public function test_pin_with_exactly_6_digits_zero_prefixed_is_accepted(): void
    {
        $this->assertTrue($this->pinService->verifyPin($this->cashierUser, '045678'));
        $this->assertEquals(0, $this->pinService->getFailedAttempts($this->cashierUser));
    }

    /**
     * BVA: PIN with 7 digits (upper boundary + 1) is rejected.
     */
    public function test_pin_with_7_digits_is_rejected(): void
    {
        $this->assertFalse($this->pinService->verifyPin($this->cashierUser, '0456789'));
        $this->assertEquals(1, $this->pinService->getFailedAttempts($this->cashierUser));
    }

    /**
     * BVA: Empty PIN string is rejected.
     */
    public function test_empty_pin_string_is_rejected(): void
    {
        $this->assertFalse($this->pinService->verifyPin($this->cashierUser, ''));
        $this->assertEquals(1, $this->pinService->getFailedAttempts($this->cashierUser));
    }

    /**
     * BVA: Setting PIN with non-6 digits throws InvalidArgumentException.
     */
    public function test_set_pin_with_invalid_length_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PIN kasir wajib berupa 6 digit angka');

        $this->pinService->setPin($this->cashierUser, '12345'); // 5 digits
    }

    /**
     * BVA: Exactly 4 failed attempts leaves account unlocked.
     */
    public function test_strike_four_leaves_account_unlocked(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->assertFalse($this->pinService->verifyPin($this->cashierUser, '999999'));
        }

        $this->assertEquals(4, $this->pinService->getFailedAttempts($this->cashierUser));
        $this->assertFalse($this->pinService->isLockedOut($this->cashierUser));
        $this->assertEquals(0, $this->pinService->getRemainingLockoutSeconds($this->cashierUser));
    }

    /**
     * BVA: Exactly 5th failed attempt triggers immediate lockout for 15 minutes (900s).
     */
    public function test_strike_five_triggers_lockout_with_proper_duration(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->pinService->verifyPin($this->cashierUser, '999999');
        }

        $this->assertTrue($this->pinService->isLockedOut($this->cashierUser));
        $remaining = $this->pinService->getRemainingLockoutSeconds($this->cashierUser);

        // Lockout duration should be between 800 and 900 seconds
        $this->assertGreaterThan(800, $remaining);
        $this->assertLessThanOrEqual(900, $remaining);
    }

    /**
     * BVA: Success on 4th attempt prevents lockout and clears counter.
     */
    public function test_successful_auth_on_fourth_strike_resets_counter(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->pinService->verifyPin($this->cashierUser, '999999');
        }
        $this->assertEquals(4, $this->pinService->getFailedAttempts($this->cashierUser));

        // 5th attempt is valid
        $this->assertTrue($this->pinService->verifyPin($this->cashierUser, '045678'));

        // Counter is reset
        $this->assertEquals(0, $this->pinService->getFailedAttempts($this->cashierUser));
        $this->assertFalse($this->pinService->isLockedOut($this->cashierUser));
    }

    /**
     * Boundary Security: Sensitive attributes (password, pin_code) are stripped from audit trail.
     */
    public function test_audit_log_filters_sensitive_attributes(): void
    {
        $supplier = Supplier::create([
            'code' => 'SUP-BVA-'.uniqid(),
            'name' => 'Supplier Filter Test',
            'contact_person' => 'Budi',
            'phone' => '08111222333',
            'address' => 'Desa Kopi',
            'farm_location' => 'Ijen',
            'altitude_masl' => 1400,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-SEC-'.uniqid(),
            'supplier_id' => $supplier->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashierUser->id,
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(5),
            'total_amount' => 1000000,
            'status' => 'DRAFT',
        ]);

        $audit = AuditLog::where('auditable_type', PurchaseOrder::class)
            ->where('auditable_id', $po->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $newValues = json_decode(json_encode($audit->new_values), true);

        $this->assertArrayNotHasKey('password', $newValues);
        $this->assertArrayNotHasKey('pin_code', $newValues);
        $this->assertArrayNotHasKey('remember_token', $newValues);
    }

    /**
     * Boundary RBAC: Middleware with empty roles parameter allows request through.
     */
    public function test_role_middleware_with_empty_roles_permits_request(): void
    {
        $middleware = new RoleMiddleware;
        $request = Request::create('/public-endpoint', 'GET');
        $request->setUserResolver(fn () => $this->cashierUser);

        $response = $middleware->handle($request, function ($req) {
            return new Response('Allowed Open', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Allowed Open', $response->getContent());
    }

    /**
     * Boundary RBAC: Unauthenticated user receives HTTP 401.
     */
    public function test_role_middleware_unauthenticated_user_receives_401(): void
    {
        $middleware = new RoleMiddleware;
        $request = Request::create('/secure-endpoint', 'GET');
        $request->setUserResolver(fn () => null);

        $response = $middleware->handle($request, function ($req) {
            return new Response('Should not see', 200);
        }, 'super_admin');

        $this->assertEquals(401, $response->getStatusCode());
    }
}
