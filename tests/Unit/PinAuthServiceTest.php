<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\PinAuthService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PinAuthServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected PinAuthService $pinService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pinService = new PinAuthService;

        $role = Role::firstOrCreate(
            ['name' => 'cashier'],
            ['display_name' => 'Cashier']
        );

        $branch = Branch::firstOrCreate(
            ['code' => 'FLAG-01'],
            ['name' => 'Flagship Cafe', 'type' => 'RETAIL_BRANCH']
        );

        $this->user = User::create([
            'role_id' => $role->id,
            'branch_id' => $branch->id,
            'name' => 'Test Cashier',
            'email' => 'cashier_unit@kafeina.test',
            'password' => Hash::make('password123'),
            'pin_code' => Hash::make('123456'),
            'is_active' => true,
        ]);
    }

    public function test_verify_pin_success_with_bcrypt_hash(): void
    {
        $this->assertTrue($this->pinService->verifyPin($this->user, '123456'));
        $this->assertEquals(0, $this->pinService->getFailedAttempts($this->user));
        $this->assertFalse($this->pinService->isLockedOut($this->user));
    }

    public function test_verify_pin_success_with_plain_legacy_pin(): void
    {
        $this->user->pin_code = '654321';
        $this->user->save();

        $this->assertTrue($this->pinService->verifyPin($this->user, '654321'));
    }

    public function test_verify_pin_fails_with_invalid_pin(): void
    {
        $this->assertFalse($this->pinService->verifyPin($this->user, '999999'));
        $this->assertEquals(1, $this->pinService->getFailedAttempts($this->user));
    }

    public function test_verify_pin_rejects_non_6_digit_format(): void
    {
        $this->assertFalse($this->pinService->verifyPin($this->user, '12345'));
        $this->assertFalse($this->pinService->verifyPin($this->user, '1234567'));
        $this->assertFalse($this->pinService->verifyPin($this->user, 'abcdef'));
    }

    public function test_five_strikes_trigger_lockout_and_audit_log(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->assertFalse($this->pinService->verifyPin($this->user, '000000', '10.0.0.1'));
            $this->assertFalse($this->pinService->isLockedOut($this->user));
        }

        // 5th attempt
        $this->assertFalse($this->pinService->verifyPin($this->user, '000000', '10.0.0.1'));
        $this->assertTrue($this->pinService->isLockedOut($this->user));
        $this->assertGreaterThan(0, $this->pinService->getRemainingLockoutSeconds($this->user));

        // 6th attempt throws DomainException
        $this->expectException(\DomainException::class);
        $this->pinService->verifyPin($this->user, '123456');
    }

    public function test_set_pin_validates_and_hashes(): void
    {
        $this->pinService->setPin($this->user, '888888');
        $this->user->refresh();

        $this->assertTrue(Hash::check('888888', $this->user->pin_code));
        $this->assertTrue($this->pinService->verifyPin($this->user, '888888'));
    }

    public function test_reset_failed_attempts(): void
    {
        $this->pinService->verifyPin($this->user, '000000');
        $this->pinService->verifyPin($this->user, '000000');
        $this->assertEquals(2, $this->pinService->getFailedAttempts($this->user));

        $this->pinService->resetFailedAttempts($this->user);
        $this->assertEquals(0, $this->pinService->getFailedAttempts($this->user));
    }
}
