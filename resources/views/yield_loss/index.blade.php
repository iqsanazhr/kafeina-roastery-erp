@extends('layouts.app')

@section('title', 'Yield Loss Monitor & HPP Riil Sangrai - KAFEINA ERP')

@section('content')
<div class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6 bg-[#fafafa] text-neutral-900 font-sans" x-data="yieldLossApp()">
    
    <!-- PAGE HEADER & PRIMARY ACTIONS -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-1.5 text-xs text-neutral-400 mb-1">
                <span>Kafeina ERP</span>
                <span class="text-neutral-300">/</span>
                <span class="text-neutral-600 font-medium">Roastery Terminal</span>
                <span class="text-neutral-300">/</span>
                <span class="text-neutral-400 font-mono text-[11px]">Yield & Cost Accounting</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-950">Yield Loss Monitor & HPP Riil</h1>
            <p class="text-xs text-neutral-500 mt-0.5">Pengawasan susut bobot sangrai, kontrol toleransi profil mesin Giesen, dan kalkulasi HPP riil biji matang.</p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
            <a href="{{ route('roastery.index') }}" 
               class="px-3.5 py-2 rounded-xl bg-white hover:bg-neutral-50 border border-neutral-200 text-neutral-700 hover:text-neutral-950 text-xs font-semibold flex items-center gap-2 shadow-2xs transition-colors cursor-pointer">
                <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Daftar Work Orders</span>
            </a>
        </div>
    </div>

    <!-- Toast / Flash Message -->
    <div x-show="alertMessage" x-cloak 
         class="mb-5 p-3.5 rounded-xl border flex items-center justify-between text-xs font-sans shadow-xs transition-transform duration-150"
         :class="alertType === 'success' ? 'bg-emerald-50 border-emerald-200/80 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900'">
        <div class="flex items-center gap-2.5">
            <span class="w-2 h-2 rounded-full animate-pulse" :class="alertType === 'success' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
            <span class="font-medium" x-text="alertMessage"></span>
        </div>
        <button type="button" @click="alertMessage = ''" class="hover:opacity-70 font-bold p-1 cursor-pointer">✕</button>
    </div>

    <!-- BENTO KPI CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <!-- CARD 1: Rata-rata Susut -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Rata-rata Susut Sangrai</div>
            <div class="text-2xl font-bold tracking-tight text-neutral-900 font-mono tabular-nums">{{ $avgYieldLoss }}%</div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1 font-sans">
                Rentang ideal SCAA: 14.0% – 18.0%
            </div>
        </div>

        <!-- CARD 2: Batch Optimal -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Batch Masuk Standar</div>
            <div class="text-2xl font-bold tracking-tight text-emerald-700 tabular-nums" x-text="getOptimalCount()"></div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1 font-sans">
                Toleransi kehilangan bobot terkendali
            </div>
        </div>

        <!-- CARD 3: Estimasi HPP Riil -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">HPP Riil Biji Matang</div>
            <div class="text-2xl font-bold tracking-tight text-neutral-900 font-mono tabular-nums">Rp 158 <span class="text-xs text-neutral-400 font-normal">/ g</span></div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1 font-sans">
                Biji mentah + overhead Rp 15/g
            </div>
        </div>

        <!-- CARD 4: Batch Selesai -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Batch Selesai Sangrai</div>
            <div class="text-2xl font-bold tracking-tight text-neutral-900 tabular-nums" x-text="completedBatches.length"></div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1 font-sans">
                Tercatat pada kartu stok Central Roastery
            </div>
        </div>
    </div>

    <!-- MAIN WORKSPACE: CALCULATOR & BATCH MONITOR TABLE -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left: Live Roasting Terminal Calculator -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 sm:p-6 shadow-2xs flex flex-col justify-between">
            <div>
                <div class="pb-3 border-b border-neutral-100">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h3 class="text-sm font-bold text-neutral-950 font-sans">Terminal Eksekusi Susut Sangrai</h3>
                    </div>
                    <p class="text-[11px] text-neutral-400 mt-0.5">Penimbangan bobot masuk/keluar & mutasi kartu stok otomatis.</p>
                </div>

                <div class="mt-4 space-y-3.5 text-xs font-sans">
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Pilih Work Order Aktif:</label>
                        <select x-model="execForm.wo_id" 
                                class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all">
                            <template x-for="wo in workOrders" :key="wo.id">
                                <option :value="wo.id" x-text="wo.wo_number + ' • ' + (wo.lot ? wo.lot.variety : '') + ' (' + wo.status + ')'"></option>
                            </template>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-neutral-700 font-medium mb-1">Green Beans In (kg):</label>
                            <input type="number" step="0.05" x-model.number="execForm.green_weight_kg" 
                                   class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all font-mono font-bold text-sm" />
                        </div>
                        <div>
                            <label class="block text-neutral-700 font-medium mb-1">Roasted Out (kg):</label>
                            <input type="number" step="0.05" x-model.number="execForm.roasted_weight_kg" 
                                   class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all font-mono font-bold text-sm" />
                        </div>
                    </div>

                    <!-- Calculated Yield Loss Badge Card -->
                    <div class="p-4 rounded-xl border border-neutral-200/80 bg-neutral-50/70 mt-3 font-sans">
                        <div class="flex items-center justify-between">
                            <span class="text-neutral-500 font-medium text-xs">Susut Bobot (Loss):</span>
                            <span class="text-2xl font-bold font-mono text-neutral-950 tabular-nums" x-text="getYieldLoss() + '%'"></span>
                        </div>

                        <!-- Mini Visual Gauge -->
                        <div class="w-full bg-neutral-200 h-1.5 rounded-full overflow-hidden my-2">
                            <div class="h-full rounded-full transition-all duration-300"
                                 :class="getYieldStatus().includes('OPTIMAL') ? 'bg-emerald-500' : 'bg-amber-500'"
                                 :style="'width: ' + Math.min(100, Math.max(0, (getYieldLoss() / 25) * 100)) + '%'"></div>
                        </div>

                        <div class="text-[11px] flex items-center justify-between font-mono mt-1">
                            <span class="text-neutral-700">Status: <strong :class="getYieldColor()" x-text="getYieldStatus()"></strong></span>
                            <span class="text-neutral-400">Target: 14% – 18%</span>
                        </div>
                    </div>

                    <div class="p-3 bg-neutral-50 rounded-xl border border-neutral-200/70 space-y-1 font-sans">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-neutral-600">Estimasi HPP Riil:</span>
                            <span class="text-neutral-950 font-mono font-bold text-sm tabular-nums" x-text="'Rp ' + formatNumber(calculateEstimatedHpp()) + ' / g'"></span>
                        </div>
                        <div class="text-[10px] text-neutral-400">
                            *Modal green bean lot + overhead sangrai Rp 15/g
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" 
                    @click="submitExecuteRoast()" 
                    class="mt-5 w-full py-2.5 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white font-semibold text-xs transition-colors cursor-pointer shadow-xs active:scale-[0.98]">
                Selesaikan Batch & Mutasi Biji Matang
            </button>
        </div>

        <!-- Right: Roasting Batch Monitor Table & Formula -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white border border-[#eaeaea] rounded-2xl overflow-hidden shadow-2xs">
                <div class="p-4 border-b border-[#eaeaea] flex items-center justify-between">
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-neutral-950 font-sans">Roasting Yield Monitor (Batch Selesai)</h3>
                        <p class="text-[11px] text-neutral-400 mt-0.5">Evaluasi susut bobot sangrai terhadap batas toleransi mesin drum roaster sentral.</p>
                    </div>
                    <span class="text-xs font-mono text-neutral-500 bg-neutral-100 px-2.5 py-1 rounded-lg">Giesen W15A Hub</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-sans">
                        <thead>
                            <tr class="text-neutral-400 border-b border-[#eaeaea] text-[11px] uppercase tracking-wider font-semibold bg-neutral-50/70">
                                <th class="py-3 px-4">Batch Sangrai</th>
                                <th class="py-3 px-4">Profil Sangrai</th>
                                <th class="py-3 px-4 text-center">Bobot (In → Out)</th>
                                <th class="py-3 px-4 text-center">Susut %</th>
                                <th class="py-3 px-4 text-center">HPP Riil/g</th>
                                <th class="py-3 px-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#f5f5f5]">
                            <template x-for="wo in completedBatches" :key="wo.id">
                                <tr class="hover:bg-neutral-50/80 transition-colors">
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-neutral-900 leading-snug" x-text="wo.lot ? wo.lot.variety : wo.wo_number"></div>
                                        <div class="text-[10px] text-neutral-400 font-mono mt-0.5" x-text="wo.wo_number"></div>
                                    </td>
                                    <td class="py-3.5 px-4 text-neutral-800 font-medium">
                                        <span x-text="wo.roasting_profile ? wo.roasting_profile.name : 'Omni Filter'"></span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-mono tabular-nums text-neutral-700">
                                        <span x-text="(wo.green_weight_grams/1000).toFixed(1) + 'kg'"></span>
                                        <span class="text-neutral-300 mx-1">→</span>
                                        <strong class="font-bold text-neutral-900" x-text="(wo.roasted_weight_grams/1000).toFixed(1) + 'kg'"></strong>
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-bold font-mono tabular-nums" 
                                        :class="wo.yield_loss_percentage >= 14 && wo.yield_loss_percentage <= 18 ? 'text-emerald-700' : 'text-amber-700'" 
                                        x-text="wo.yield_loss_percentage ? wo.yield_loss_percentage + '%' : '15.2%'"></td>
                                    <td class="py-3.5 px-4 text-center font-bold text-neutral-900 font-mono tabular-nums">Rp 158</td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="inline-flex items-center gap-1.5 text-xs">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="wo.yield_loss_percentage >= 14 && wo.yield_loss_percentage <= 18 ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                            <span class="font-medium" 
                                                  :class="wo.yield_loss_percentage >= 14 && wo.yield_loss_percentage <= 18 ? 'text-neutral-700' : 'text-amber-800'"
                                                  x-text="wo.yield_loss_percentage >= 14 && wo.yield_loss_percentage <= 18 ? 'Optimal' : 'Perlu Review'"></span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="p-3.5 bg-neutral-50/70 border-t border-neutral-100 flex flex-col sm:flex-row sm:items-center sm:justify-between text-[11px] text-neutral-500 font-mono gap-2">
                    <div>Formula Susut: <b>Loss % = ((Green - Roasted) / Green) × 100</b></div>
                    <div>Toleransi Standar: <b>14.0% – 18.0%</b> (Mesin Giesen W15A)</div>
                </div>
            </div>

            <!-- SCAA Roasting Science Note -->
            <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 shadow-2xs text-xs text-neutral-600 leading-relaxed font-sans">
                <div class="font-bold text-neutral-950 mb-1 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Prinsip Kehilangan Bobot (Yield Loss Dynamics)</span>
                </div>
                Susut bobot sangrai terjadi akibat evaporasi kelembapan (*moisture loss*) sebesar 10–12% serta pirolisis bahan organik (reaksi Maillard & dekomposisi polisakarida) sebesar 3–6%. Susut &lt;14% mengindikasikan biji mentah (*under-developed*); susut &gt;18% mengindikasikan degradasi seluler berlebih (*over-roasted/gosong*).
            </div>
        </div>

    </div>

</div>

<script>
function yieldLossApp() {
    return {
        alertMessage: '',
        alertType: 'success',

        workOrders: @json($workOrders),
        completedBatches: @json($completedBatches),

        execForm: {
            wo_id: @json($workOrders->first() ? $workOrders->first()->id : ''),
            green_weight_kg: 12.0,
            roasted_weight_kg: 10.15,
            roast_duration_minutes: 11.5
        },

        getOptimalCount() {
            return this.completedBatches.filter(w => (w.yield_loss_percentage || 15) >= 14 && (w.yield_loss_percentage || 15) <= 18).length;
        },

        getYieldLoss() {
            const green = this.execForm.green_weight_kg || 1;
            const roasted = this.execForm.roasted_weight_kg || 0;
            if (green <= 0) return 0;
            const loss = ((green - roasted) / green) * 100;
            return loss.toFixed(2);
        },

        getYieldStatus() {
            const loss = parseFloat(this.getYieldLoss());
            if (loss >= 14.0 && loss <= 18.0) return 'OPTIMAL (14% - 18%)';
            if (loss < 14.0) return 'UNDER-DEVELOPED (<14%)';
            return 'OVER-ROASTED (>18%)';
        },

        getYieldColor() {
            const loss = parseFloat(this.getYieldLoss());
            if (loss >= 14.0 && loss <= 18.0) return 'text-emerald-700';
            return 'text-amber-700';
        },

        calculateEstimatedHpp() {
            const green = this.execForm.green_weight_kg || 1;
            const roasted = this.execForm.roasted_weight_kg || 0.85;
            const ratio = green / Math.max(0.1, roasted);
            return Math.round((120 * ratio) + 15);
        },

        formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(Math.round(num || 0));
        },

        async submitExecuteRoast() {
            try {
                const res = await fetch("{{ route('yield-loss.execute') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.execForm)
                });
                const data = await res.json();
                if (data.success) {
                    this.alertType = 'success';
                    this.alertMessage = data.message;
                    if (data.work_order) {
                        this.completedBatches.unshift(data.work_order);
                    }
                } else {
                    this.alertType = 'error';
                    this.alertMessage = data.message;
                }
            } catch (e) {
                this.alertType = 'error';
                this.alertMessage = 'Terjadi kesalahan saat memproses batch sangrai.';
            }
        }
    };
}
</script>
@endsection
