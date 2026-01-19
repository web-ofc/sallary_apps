<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\Pph21TaxBracket;
use App\Models\KaryawanPtkpHistory;
use App\Models\ListPtkp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Pph21AnnualCalculationService
{
    /**
     * Calculate dan Update PPh21 Tahunan untuk karyawan
     * 
     * @param int $karyawanId
     * @param int $companyId
     * @param string $salaryType
     * @param int $periode (tahun)
     * @return array
     */
    public function calculateAndUpdate($karyawanId, $companyId, $salaryType, $periode)
    {
        try {
            DB::beginTransaction();

            // 🔒 LOCK row last payroll
            $lastPayroll = Payroll::query()
                ->where('karyawan_id', $karyawanId)
                ->where('company_id', $companyId)
                ->where('salary_type', $salaryType)
                ->whereRaw('YEAR(STR_TO_DATE(CONCAT(periode, "-01"), "%Y-%m-%d")) = ?', [$periode])
                ->where('is_last_period', 1)
                ->where('is_released', 0)
                ->lockForUpdate()
                ->first();

            if (!$lastPayroll) {
                DB::rollBack();
                return [
                    'success' => false, 
                    'message' => 'Payroll last period tidak ditemukan'
                ];
            }

            // ✅ VALIDASI PTKP HISTORY (sama seperti perhitungan bulanan)
            $ptkpValidation = $this->validatePtkpHistory($karyawanId, $periode);
            
            if (!$ptkpValidation['success']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => $ptkpValidation['message'],
                    'error_type' => 'ptkp_not_found'
                ];
            }

            $maxIter = 10;
            $prevAkhir = null;

            $pph21Akhir = 0;
            $pph21Tahunan = 0;
            $pph21Masa = 0;
            $pkp = 0;

            for ($i = 0; $i < $maxIter; $i++) {

                $viewData = DB::table('periode_karyawan_masa_jabatans')
                    ->where('karyawan_id', $karyawanId)
                    ->where('company_id', $companyId)
                    ->where('salary_type', $salaryType)
                    ->where('periode', $periode)
                    ->first();

                if (!$viewData) {
                    DB::rollBack();
                    return [
                        'success' => false, 
                        'message' => 'Data tidak ditemukan di view'
                    ];
                }

                $pkp = (float) $viewData->pkp;
                $pph21Masa = (float) ($viewData->tunj_pph_21 ?? 0);

                $calc = $this->calculatePph21FromPkp($pkp, $karyawanId, $periode);
                if (!$calc['success']) {
                    DB::rollBack();
                    return [
                        'success' => false, 
                        'message' => $calc['message']
                    ];
                }

                $pph21Tahunan = (float) $calc['pph21_tahunan'];

                $pph21Akhir = (int) round(max(0, $pph21Tahunan - $pph21Masa));

                // ✅ kalau sudah stabil
                if ($prevAkhir !== null && $pph21Akhir === $prevAkhir) {
                    break;
                }
                $prevAkhir = $pph21Akhir;

                // ✅ update setiap iterasi
                $lastPayroll->pph_21 = $pph21Akhir;

                // ✅ gross: dipotong dari gaji
                $lastPayroll->pph_21_deduction = ($salaryType === 'gross') ? $pph21Akhir : 0;

                $lastPayroll->save();

                // refresh model supaya iterasi lanjut pakai nilai terbaru
                $lastPayroll->refresh();
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'PPh21 Tahunan berhasil dihitung (converged)',
                'pkp' => (int)$pkp,
                'pph21_masa' => (int)$pph21Masa,
                'pph21_tahunan' => (int)$pph21Tahunan,
                'pph21_akhir' => (int)$pph21Akhir,
                'updated_count' => 1,
                'ptkp_status' => $ptkpValidation['ptkp_status'],
                'ptkp_besaran' => $ptkpValidation['ptkp_besaran']
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in calculateAndUpdate', [
                'karyawan_id' => $karyawanId,
                'company_id' => $companyId,
                'periode' => $periode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ Validasi PTKP History untuk karyawan
     * Sama seperti di Pph21CalculatorService
     * 
     * @param int $karyawanId
     * @param int $year
     * @return array
     */
    private function validatePtkpHistory($karyawanId, $year)
    {
        try {
            // Get PTKP History
            $ptkpHistory = KaryawanPtkpHistory::where('absen_karyawan_id', $karyawanId)
                ->where('tahun', $year)
                ->first();
            
            if (!$ptkpHistory) {
                return [
                    'success' => false,
                    'message' => "PTKP history tidak ditemukan untuk karyawan ID {$karyawanId} di tahun {$year}"
                ];
            }
            
            // Get PTKP detail
            $ptkp = ListPtkp::where('absen_ptkp_id', $ptkpHistory->absen_ptkp_id)->first();
            
            if (!$ptkp) {
                return [
                    'success' => false,
                    'message' => "Data PTKP tidak ditemukan untuk karyawan ID {$karyawanId}"
                ];
            }
            
            return [
                'success' => true,
                'ptkp_status' => $ptkp->status . ' - ' . $ptkp->kriteria,
                'ptkp_besaran' => $ptkp->besaran_ptkp,
                'ptkp_id' => $ptkp->id
            ];
            
        } catch (\Exception $e) {
            Log::error('Error validating PTKP history', [
                'karyawan_id' => $karyawanId,
                'year' => $year,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error saat validasi PTKP: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate PPh21 Tahunan dari PKP menggunakan bracket
     * 
     * @param float $pkp
     * @param int $karyawanId
     * @param int $year
     * @return array
     */
    public function calculatePph21FromPkp($pkp, $karyawanId, $year)
    {
        try {
            // Tentukan tanggal untuk ambil bracket (akhir tahun)
            $date = Carbon::create($year, 12, 31);
            
            // Ambil brackets yang aktif di tanggal tersebut
            $brackets = $this->getActiveBrackets($date);
            
            if ($brackets->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Bracket PPh21 tidak ditemukan untuk tahun ' . $year
                ];
            }
            
            $totalPph21 = 0;
            $breakdownDetails = [];
            
            // Calculate PPh21 per bracket
            foreach ($brackets as $bracket) {
                $pkpInBracket = 0;
                $pph21InBracket = 0;
                
                // Hitung PKP yang masuk di bracket ini
                if ($pkp > $bracket->min_pkp) {
                    if ($bracket->max_pkp === null) {
                        // Bracket terakhir (unlimited)
                        $pkpInBracket = max($pkp - $bracket->min_pkp, 0);
                    } else {
                        // Bracket dengan batas atas
                        $pkpInBracket = max(min($pkp, $bracket->max_pkp) - $bracket->min_pkp, 0);
                    }
                    
                    // Hitung pajak di bracket ini
                    $pph21InBracket = floor($pkpInBracket * ($bracket->rate_percent / 100));
                    $totalPph21 += $pph21InBracket;
                }
                
                $breakdownDetails[] = [
                    'order_index' => $bracket->order_index,
                    'description' => $bracket->description,
                    'min_pkp' => $bracket->min_pkp,
                    'max_pkp' => $bracket->max_pkp,
                    'rate_percent' => $bracket->rate_percent,
                    'pkp_in_bracket' => $pkpInBracket,
                    'pph21_in_bracket' => $pph21InBracket,
                    'effective_start_date' => $bracket->effective_start_date,
                    'effective_end_date' => $bracket->effective_end_date,
                ];
            }
            
            return [
                'success' => true,
                'pph21_tahunan' => $totalPph21,
                'pph21_akhir' => 0, // Will be calculated by caller
                'bracket_details' => $breakdownDetails,
                'period_date' => $date->format('Y-m-d')
            ];
            
        } catch (\Exception $e) {
            Log::error('Error calculating PPh21 from PKP', [
                'pkp' => $pkp,
                'karyawan_id' => $karyawanId,
                'year' => $year,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Ambil bracket yang aktif di tanggal tertentu
     * 
     * @param Carbon $date
     * @return \Illuminate\Support\Collection
     */
    private function getActiveBrackets($date)
    {
        return Pph21TaxBracket::where('effective_start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_end_date')
                  ->orWhere('effective_end_date', '>=', $date);
            })
            ->orderBy('order_index')
            ->get();
    }
    
    /**
     * Get summary untuk dashboard/reporting
     * 
     * @param int $year
     * @return array
     */
    public function getSummary($year = null)
    {
        $year = $year ?? date('Y');
        
        $pendingCount = DB::table('periode_karyawan_masa_jabatans as pkm')
            ->whereExists(function($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('payrolls')
                    ->whereColumn('payrolls.karyawan_id', 'pkm.karyawan_id')
                    ->whereColumn('payrolls.company_id', 'pkm.company_id')
                    ->whereColumn('payrolls.salary_type', 'pkm.salary_type')
                    ->whereRaw('YEAR(STR_TO_DATE(CONCAT(payrolls.periode, "-01"), "%Y-%m-%d")) = pkm.periode')
                    ->where('payrolls.is_last_period', 1)
                    ->where('payrolls.is_released', 0)
                    ->whereNull('payrolls.pph_21');
            })
            ->where('pkm.periode', $year)
            ->count();
        
        $calculatedCount = DB::table('periode_karyawan_masa_jabatans as pkm')
            ->whereExists(function($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('payrolls')
                    ->whereColumn('payrolls.karyawan_id', 'pkm.karyawan_id')
                    ->whereColumn('payrolls.company_id', 'pkm.company_id')
                    ->whereColumn('payrolls.salary_type', 'pkm.salary_type')
                    ->whereRaw('YEAR(STR_TO_DATE(CONCAT(payrolls.periode, "-01"), "%Y-%m-%d")) = pkm.periode')
                    ->where('payrolls.is_last_period', 1)
                    ->where('payrolls.is_released', 0)
                    ->whereNotNull('payrolls.pph_21')
                    ->where('payrolls.pph_21', '>', 0);
            })
            ->where('pkm.periode', $year)
            ->count();
        
        return [
            'year' => $year,
            'pending' => $pendingCount,
            'calculated' => $calculatedCount,
            'total' => $pendingCount + $calculatedCount
        ];
    }
    
    /**
     * ✅ Batch calculate dengan skip untuk yang tidak ada PTKP
     * 
     * @param array $items Array of ['karyawan_id', 'company_id', 'salary_type', 'periode']
     * @return array
     */
    public function calculateBatch(array $items)
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped_no_ptkp' => 0,
            'details' => []
        ];
        
        foreach ($items as $item) {
            // ✅ Validasi PTKP dulu sebelum calculate
            $ptkpValidation = $this->validatePtkpHistory(
                $item['karyawan_id'], 
                $item['periode']
            );
            
            if (!$ptkpValidation['success']) {
                $results['skipped_no_ptkp']++;
                $results['details'][] = [
                    'karyawan_id' => $item['karyawan_id'],
                    'company_id' => $item['company_id'],
                    'salary_type' => $item['salary_type'],
                    'periode' => $item['periode'],
                    'status' => 'skipped',
                    'reason' => 'no_ptkp',
                    'message' => $ptkpValidation['message']
                ];
                continue;
            }
            
            // Calculate jika PTKP ada
            $calculation = $this->calculateAndUpdate(
                $item['karyawan_id'],
                $item['company_id'],
                $item['salary_type'],
                $item['periode']
            );
            
            if ($calculation['success']) {
                $results['success']++;
                $results['details'][] = [
                    'karyawan_id' => $item['karyawan_id'],
                    'company_id' => $item['company_id'],
                    'salary_type' => $item['salary_type'],
                    'periode' => $item['periode'],
                    'status' => 'success',
                    'pph21_akhir' => $calculation['pph21_akhir'],
                    'ptkp_status' => $calculation['ptkp_status']
                ];
            } else {
                $results['failed']++;
                $results['details'][] = [
                    'karyawan_id' => $item['karyawan_id'],
                    'company_id' => $item['company_id'],
                    'salary_type' => $item['salary_type'],
                    'periode' => $item['periode'],
                    'status' => 'failed',
                    'message' => $calculation['message']
                ];
            }
        }
        
        return $results;
    }
}