<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $branchRoastery = Branch::where('code', 'HQ-CENTRAL')->first() ?? Branch::first();
        $branchFlagship = Branch::where('code', 'OUT-SENO')->first() ?? $branchRoastery;

        $roleSuperAdmin = Role::whereIn('name', ['super_admin', 'SUPER_ADMIN'])->first();
        $roleProcurement = Role::whereIn('name', ['procurement', 'PROCUREMENT_OFFICER'])->first();
        $roleRoaster = Role::whereIn('name', ['roaster', 'HEAD_ROASTER'])->first();
        $roleWarehouse = Role::whereIn('name', ['warehouse', 'WAREHOUSE_STAFF'])->first();
        $roleCashier = Role::whereIn('name', ['cashier', 'CASHIER'])->first();

        $defaultPassword = Hash::make('password123');

        $users = [
            [
                'email' => 'admin@kafeina.id',
                'name' => 'Budi Pratama (Super Admin)',
                'role_id' => $roleSuperAdmin?->id,
                'branch_id' => $branchRoastery?->id,
                'password' => $defaultPassword,
                'pin_code' => Hash::make('111111'),
                'is_active' => true,
            ],
            [
                'email' => 'procurement@kafeina.id',
                'name' => 'Citra Dewi (Procurement Lead)',
                'role_id' => $roleProcurement?->id,
                'branch_id' => $branchRoastery?->id,
                'password' => $defaultPassword,
                'pin_code' => Hash::make('222222'),
                'is_active' => true,
            ],
            [
                'email' => 'roaster@kafeina.id',
                'name' => 'Dimas Wahyu (Head Roaster)',
                'role_id' => $roleRoaster?->id,
                'branch_id' => $branchRoastery?->id,
                'password' => $defaultPassword,
                'pin_code' => Hash::make('333333'),
                'is_active' => true,
            ],
            [
                'email' => 'warehouse@kafeina.id',
                'name' => 'Eko Nugroho (Warehouse Mgr)',
                'role_id' => $roleWarehouse?->id,
                'branch_id' => $branchRoastery?->id,
                'password' => $defaultPassword,
                'pin_code' => Hash::make('444444'),
                'is_active' => true,
            ],
            [
                'email' => 'cashier@kafeina.id',
                'name' => 'Siti Rahma (Lead Barista & Cashier)',
                'role_id' => $roleCashier?->id,
                'branch_id' => $branchFlagship?->id,
                'password' => $defaultPassword,
                'pin_code' => Hash::make('123456'),
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            if ($userData['role_id']) {
                User::updateOrCreate(
                    ['email' => $userData['email']],
                    $userData
                );
            }
        }
    }
}
