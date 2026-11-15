<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Map normalized alias roles to standard system roles.
     *
     * @var array<string, list<string>>
     */
    protected array $roleAliases = [
        'super_admin' => ['super_admin', 'super-admin', 'admin', 'owner'],
        'procurement' => ['procurement', 'procurement_officer', 'purchasing'],
        'qc_inspector' => ['qc_inspector', 'qc', 'cupper', 'quality_control'],
        'roaster' => ['roaster', 'head_roaster', 'roasting_staff', 'production'],
        'warehouse' => ['warehouse', 'warehouse_staff', 'warehouse_manager', 'logistics'],
        'cashier' => ['cashier', 'barista', 'barista_cashier', 'branch_cashier', 'store_cashier'],
        'supervisor' => ['supervisor', 'store_manager', 'manager'],
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // Resolusi pengguna dari sesi aktif jika belum terikat pada Auth guard
        if (! $user && $request->hasSession() && ! $request->is('api/*')) {
            $activeId = $request->session()->get('active_user_id');
            if ($activeId) {
                $user = User::with(['role', 'branch'])->find($activeId);
            } elseif (app()->environment('local', 'testing')) {
                $user = User::with(['role', 'branch'])->find(1) ?? User::first();
            }

            if ($user) {
                auth()->setUser($user);
            }
        }

        // 1. Validasi Autentikasi
        if (! $user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sesi tidak terautentikasi. Silakan masuk terlebih dahulu.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            return response('Tidak terautentikasi.', Response::HTTP_UNAUTHORIZED);
        }

        // 2. Validasi Status Aktif Pengguna
        if (isset($user->is_active) && ! $user->is_active) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akun pengguna dinonaktifkan. Silakan hubungi administrator.',
                ], Response::HTTP_FORBIDDEN);
            }

            return response('Akun pengguna dinonaktifkan. Silakan hubungi administrator.', Response::HTTP_FORBIDDEN);
        }

        // Jika rute tidak mendefinisikan pembatasan peran, izinkan akses
        if (empty($roles)) {
            return $next($request);
        }

        // 3. Normalisasi Nama Peran Pengguna
        $rawUserRole = strtolower(trim((string) ($user->role?->name ?? '')));

        // 4. Super Admin Universal Bypass
        if ($this->isRoleMatch($rawUserRole, ['super_admin'])) {
            return $next($request);
        }

        // 5. Normalisasi Parameter Peran Rute (mendukung pemisah koma atau argumen terpisah)
        $allowedRoles = [];
        foreach ($roles as $roleParam) {
            foreach (explode(',', $roleParam) as $subRole) {
                $allowedRoles[] = strtolower(trim($subRole));
            }
        }
        $allowedRoles = array_unique(array_filter($allowedRoles));

        // 6. Evaluasi Otorisasi
        if ($this->isRoleMatch($rawUserRole, $allowedRoles)) {
            return $next($request);
        }

        // 7. Catat Insiden Keamanan ke audit_logs
        $this->logSecurityBreach($request, $user, $rawUserRole, $allowedRoles);

        // 8. Kembalikan Respons Ditolak (403 Forbidden)
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'error',
                'message' => "Akses ditolak: Peran [{$rawUserRole}] tidak memiliki wewenang untuk mengakses rute ini.",
                'required_roles' => $allowedRoles,
            ], Response::HTTP_FORBIDDEN);
        }

        if (view()->exists('errors.403')) {
            return response()->view('errors.403', [
                'userRole' => $rawUserRole,
                'allowedRoles' => $allowedRoles,
                'currentUser' => $user,
                'message' => 'Peran ['.($user->role?->display_name ?? $rawUserRole).'] tidak memiliki hak akses ke modul ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        return response("Akses ditolak: Peran [{$rawUserRole}] tidak memiliki wewenang untuk mengakses rute ini.", Response::HTTP_FORBIDDEN);
    }

    /**
     * Check if a given user role matches any allowed role taking aliases into account.
     *
     * @param  list<string>  $allowedRoles
     */
    protected function isRoleMatch(string $userRole, array $allowedRoles): bool
    {
        $userRole = strtolower(trim($userRole));

        // Find all equivalent aliases for the user's role
        $userEquivalents = [$userRole];
        foreach ($this->roleAliases as $group => $aliases) {
            $normAliases = array_map('strtolower', $aliases);
            if ($userRole === strtolower($group) || in_array($userRole, $normAliases, true)) {
                $userEquivalents = array_merge($userEquivalents, [$group], $normAliases);
            }
        }
        $userEquivalents = array_unique($userEquivalents);

        foreach ($allowedRoles as $targetRole) {
            $targetRole = strtolower(trim($targetRole));

            // Direct match against user's equivalence set
            if (in_array($targetRole, $userEquivalents, true)) {
                return true;
            }

            // Find all equivalents for targetRole
            foreach ($this->roleAliases as $group => $aliases) {
                $normAliases = array_map('strtolower', $aliases);
                if ($targetRole === strtolower($group) || in_array($targetRole, $normAliases, true)) {
                    if (in_array($userRole, array_merge([$group], $normAliases), true)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Log unauthorized access attempts into audit_logs.
     *
     * @param  list<string>  $requiredRoles
     */
    protected function logSecurityBreach(Request $request, $user, string $userRole, array $requiredRoles): void
    {
        try {
            DB::table('audit_logs')->insert([
                'user_id' => $user->id,
                'event_type' => 'SECURITY_BREACH',
                'auditable_type' => get_class($user),
                'auditable_id' => $user->id,
                'old_values' => null,
                'new_values' => json_encode([
                    'event' => 'UNAUTHORIZED_ROUTE_ACCESS_ATTEMPT',
                    'route' => $request->path(),
                    'method' => $request->method(),
                    'user_role' => $userRole,
                    'required_roles' => $requiredRoles,
                ], JSON_UNESCAPED_UNICODE),
                'ip_address' => $request->ip() ?? '127.0.0.1',
                'user_agent' => $request->userAgent() ?? 'Unknown',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Fail-safe: jangan memicu crash jika logging DB gagal
        }
    }
}
