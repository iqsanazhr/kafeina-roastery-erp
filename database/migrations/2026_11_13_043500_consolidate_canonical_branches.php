<?php

use App\Models\Branch;
use App\Models\InventoryWarehouse;
use App\Models\MaterialRequest;
use App\Models\PosOrder;
use App\Models\PosShift;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure 4 Canonical Branches exist
        $hq = Branch::firstOrCreate(['code' => 'HQ-CENTRAL'], [
            'name' => 'Kafeina Central Roastery & Lab Hub',
            'type' => 'CENTRAL_ROASTERY',
            'address' => 'Kawasan Industri Cilandak Commercial Estate Kav. 12, Jakarta Selatan',
            'phone' => '021-7890123',
            'is_active' => true,
        ]);

        $seno = Branch::firstOrCreate(['code' => 'OUT-SENO'], [
            'name' => 'Kafeina Flagship Senopati',
            'type' => 'RETAIL_BRANCH',
            'address' => 'Jl. Senopati No. 44, Kebayoran Baru, Jakarta Selatan',
            'phone' => '021-5234567',
            'is_active' => true,
        ]);

        $brg = Branch::firstOrCreate(['code' => 'OUT-BRG'], [
            'name' => 'Kafeina Specialty Braga Bandung',
            'type' => 'RETAIL_BRANCH',
            'address' => 'Jl. Braga No. 88, Sumur Bandung, Kota Bandung',
            'phone' => '022-4239876',
            'is_active' => true,
        ]);

        $mlo = Branch::firstOrCreate(['code' => 'OUT-MLO'], [
            'name' => 'Kafeina Heritage Malioboro Jogja',
            'type' => 'RETAIL_BRANCH',
            'address' => 'Jl. Malioboro No. 120, Sosromenduran, Gedong Tengen, Yogyakarta',
            'phone' => '0274-567890',
            'is_active' => true,
        ]);

        $canonicalIds = [$hq->id, $seno->id, $brg->id, $mlo->id];

        // 2. Safely reassign foreign keys and delete legacy branches
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $legacyBranches = Branch::whereNotIn('id', $canonicalIds)->get();
        foreach ($legacyBranches as $lb) {
            $target = $seno;
            if (str_contains($lb->code, 'ROAST') || str_contains($lb->code, 'HQ')) {
                $target = $hq;
            } elseif (str_contains($lb->code, 'BRG')) {
                $target = $brg;
            } elseif (str_contains($lb->code, 'MLO')) {
                $target = $mlo;
            }

            User::where('branch_id', $lb->id)->update(['branch_id' => $target->id]);
            PurchaseOrder::where('branch_id', $lb->id)->update(['branch_id' => $target->id]);
            InventoryWarehouse::where('branch_id', $lb->id)->update(['branch_id' => $target->id]);
            MaterialRequest::where('from_branch_id', $lb->id)->update(['from_branch_id' => $target->id]);
            MaterialRequest::where('to_branch_id', $lb->id)->update(['to_branch_id' => $hq->id]);
            PosShift::where('branch_id', $lb->id)->update(['branch_id' => $target->id]);
            PosOrder::where('branch_id', $lb->id)->update(['branch_id' => $target->id]);

            $lb->delete();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive rollback
    }
};
