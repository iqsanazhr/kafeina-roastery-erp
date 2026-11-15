<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PinAuthService
{
    public const MAX_ATTEMPTS = 5;

    public const LOCKOUT_MINUTES = 15;

    public const LOCKOUT_SECONDS = 900;

    /**
     * Check if user is currently locked out from PIN verification.
     */
    public function isLockedOut(User $user): bool
    {
        if (Cache::has($this->getLockoutKey($user))) {
            return true;
        }

        if ($user->pin_locked_until && $user->pin_locked_until->isFuture()) {
            return true;
        }

        return false;
    }

    /**
     * Get remaining lockout time in seconds.
     */
    public function getRemainingLockoutSeconds(User $user): int
    {
        $lockoutUntil = Cache::get($this->getLockoutKey($user));

        if ($lockoutUntil) {
            return max(0, (int) $lockoutUntil - time());
        }

        if ($user->pin_locked_until && $user->pin_locked_until->isFuture()) {
            return max(0, $user->pin_locked_until->diffInSeconds(now()));
        }

        return 0;
    }

    /**
     * Get current count of failed attempts.
     */
    public function getFailedAttempts(User $user): int
    {
        $cached = Cache::get($this->getAttemptsKey($user));
        if ($cached !== null) {
            return (int) $cached;
        }

        return (int) ($user->pin_failed_attempts ?? 0);
    }

    /**
     * Verify 6-digit cashier/supervisor PIN.
     *
     * @throws \DomainException if locked out
     */
    public function verifyPin(User $user, string $pin, ?string $ip = null): bool
    {
        if ($this->isLockedOut($user)) {
            $remaining = (int) ceil($this->getRemainingLockoutSeconds($user) / 60);
            throw new \DomainException(
                "Akun terkunci sementara karena 5x kesalahan PIN berturut-turut. Coba lagi dalam {$remaining} menit."
            );
        }

        // Validate 6 digits format
        if (! preg_match('/^\d{6}$/', $pin)) {
            $this->recordFailedAttempt($user, $ip);

            return false;
        }

        // Verify hash or plain fallback
        $valid = false;
        if (! empty($user->pin_code)) {
            if (strlen((string) $user->pin_code) >= 20) {
                $valid = Hash::check($pin, $user->pin_code);
            } else {
                $valid = hash_equals((string) $user->pin_code, (string) $pin);
            }
        }

        if ($valid) {
            $this->resetFailedAttempts($user);

            return true;
        }

        $this->recordFailedAttempt($user, $ip);

        return false;
    }

    /**
     * Record a failed PIN attempt. Triggers lockout on 5th strike.
     */
    public function recordFailedAttempt(User $user, ?string $ip = null): void
    {
        $attemptsKey = $this->getAttemptsKey($user);
        $attempts = (int) Cache::get($attemptsKey, $user->pin_failed_attempts ?? 0) + 1;

        Cache::put($attemptsKey, $attempts, now()->addMinutes(self::LOCKOUT_MINUTES));

        try {
            $user->pin_failed_attempts = $attempts;
            $user->saveQuietly();
        } catch (\Throwable) {
        }

        if ($attempts >= self::MAX_ATTEMPTS) {
            $lockoutUntil = time() + self::LOCKOUT_SECONDS;
            Cache::put($this->getLockoutKey($user), $lockoutUntil, now()->addMinutes(self::LOCKOUT_MINUTES));
            Cache::forget($attemptsKey);

            try {
                $user->pin_failed_attempts = 0;
                $user->pin_locked_until = now()->addSeconds(self::LOCKOUT_SECONDS);
                $user->saveQuietly();
            } catch (\Throwable) {
            }

            // Log security breach to audit_logs
            $this->logSecurityBreach($user, $ip);
        }
    }

    /**
     * Reset failed attempts after successful authentication.
     */
    public function resetFailedAttempts(User $user): void
    {
        Cache::forget($this->getAttemptsKey($user));
        Cache::forget($this->getLockoutKey($user));

        try {
            if ($user->pin_failed_attempts > 0 || $user->pin_locked_until !== null) {
                $user->pin_failed_attempts = 0;
                $user->pin_locked_until = null;
                $user->saveQuietly();
            }
        } catch (\Throwable) {
        }
    }

    /**
     * Set/update user 6-digit PIN (Bcrypt hashed).
     */
    public function setPin(User $user, string $pin): void
    {
        if (! preg_match('/^\d{6}$/', $pin)) {
            throw new \InvalidArgumentException('PIN kasir wajib berupa 6 digit angka numerik.');
        }

        $user->pin_code = Hash::make($pin);
        $user->pin_failed_attempts = 0;
        $user->pin_locked_until = null;
        $user->save();

        $this->resetFailedAttempts($user);
    }

    /**
     * Log a SECURITY_BREACH event to the audit_logs table.
     */
    protected function logSecurityBreach(User $user, ?string $ip = null): void
    {
        try {
            DB::table('audit_logs')->insert([
                'user_id' => $user->id,
                'event_type' => 'SECURITY_BREACH',
                'auditable_type' => get_class($user),
                'auditable_id' => $user->id,
                'old_values' => null,
                'new_values' => json_encode([
                    'event' => 'PIN_BRUTE_FORCE_LOCKOUT_TRIGGERED',
                    'reason' => '5 consecutive failed PIN attempts detected within 15-minute window',
                    'lockout_duration_minutes' => self::LOCKOUT_MINUTES,
                    'user_email' => $user->email,
                ], JSON_UNESCAPED_UNICODE),
                'ip_address' => $ip ?? (request()?->ip() ?? '127.0.0.1'),
                'user_agent' => request()?->userAgent() ?? 'System',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // Fail-safe
        }
    }

    protected function getAttemptsKey(User $user): string
    {
        return "cashier_pin_attempts_{$user->id}";
    }

    protected function getLockoutKey(User $user): string
    {
        return "cashier_pin_lockout_{$user->id}";
    }
}
