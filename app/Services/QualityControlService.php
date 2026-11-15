<?php

namespace App\Services;

use App\Models\GreenBeanLot;
use App\Models\QualityControl;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QualityControlService
{
    public function __construct(
        protected LotGeneratorService $lotGeneratorService
    ) {}

    /**
     * Evaluates green beans lot against SCA specialty coffee standards.
     *
     * Standards:
     * - Moisture Content: 10.00% - 12.00%
     * - Primary Defects: strictly 0
     * - SCA Cupping Score: >= 80.00
     */
    public function evaluateMutu(float $moisture, int $primaryDefects, float $scaScore): string
    {
        if ($moisture >= 10.00 && $moisture <= 12.00 && $primaryDefects === 0 && $scaScore >= 80.00) {
            return 'ACCEPTED';
        }

        // Commercial grade / Downgrade boundary
        if ($primaryDefects === 0 && $scaScore >= 75.00 && $moisture >= 9.50 && $moisture <= 12.50) {
            return 'DOWNGRADED';
        }

        return 'REJECTED';
    }

    /**
     * Perform inspection and record Quality Control outcome.
     */
    public function inspectLot(GreenBeanLot $lot, User $inspector, array $data, ?int $warehouseId = null, ?int $productId = null): QualityControl
    {
        return DB::transaction(function () use ($lot, $inspector, $data, $warehouseId, $productId) {
            $moisture = (float) $data['moisture_content'];
            $bulkDensity = (float) ($data['bulk_density_g_l'] ?? 700.00);
            $primaryDefects = (int) ($data['primary_defects'] ?? 0);
            $secondaryDefects = (int) ($data['secondary_defects'] ?? 0);
            $scaScore = (float) ($data['sca_cupping_score'] ?? 0.00);
            $notes = $data['notes'] ?? null;

            $status = $this->evaluateMutu($moisture, $primaryDefects, $scaScore);

            $qc = QualityControl::updateOrCreate(
                ['lot_id' => $lot->id],
                [
                    'inspector_id' => $inspector->id,
                    'inspection_date' => now(),
                    'moisture_content' => $moisture,
                    'bulk_density_g_l' => $bulkDensity,
                    'primary_defects' => $primaryDefects,
                    'secondary_defects' => $secondaryDefects,
                    'sca_cupping_score' => $scaScore,
                    'qc_status' => $status,
                    'notes' => $notes,
                ]
            );

            // Auto-format lot number if not set or temporary
            if ($status === 'ACCEPTED') {
                if (empty($lot->lot_number) || str_starts_with($lot->lot_number, 'TEMP-')) {
                    $farmCode = $lot->purchaseOrder?->supplier?->code ?? substr($lot->origin_region, 0, 4);
                    $year = (int) ($lot->harvest_year ?? date('Y'));
                    $lot->lot_number = $this->lotGeneratorService->generateLotNumber($farmCode, $year);
                    $lot->save();
                }

                // If warehouse is designated, stock into central raw inventory and create stock card
                if ($warehouseId) {
                    $this->lotGeneratorService->processAcceptedLot($lot, $warehouseId, $productId);
                }
            }

            return $qc;
        });
    }
}
