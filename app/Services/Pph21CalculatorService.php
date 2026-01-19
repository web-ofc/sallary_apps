<?php
// app/Services/Pph21CalculatorService.php

namespace App\Services;

use App\Models\Payroll;
use App\Models\KaryawanPtkpHistory;
use App\Models\ListPtkp;
use App\Models\JenisTer;
use App\Models\RangeBruto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Pph21CalculatorService
{
    /**
     * Calculate PPh21 untuk single payroll record
     * 
     * @param Payroll $payroll
     * @return array ['success' => bool, 'pph21' => int|null, 'pph21_deduction' => int|null, 'message' => string, 'details' => array]
     */
    public function calculateSingle(Payroll $payroll)
    {
        try {
            // 1. Hitung Total Bruto Dasar
            $totalBrutoDasar = $this->calculateTotalBrutoDasar($payroll);
            
            if ($totalBrutoDasar <= 0) {
                return [
                    'success' => false,
                    'pph21' => null,
                    'pph21_deduction' => null,
                    'message' => 'Total bruto dasar harus lebih dari 0',
                    'details' => [
                        'total_bruto_dasar' => $totalBrutoDasar
                    ]
                ];
            }
            
            // 2. Get PTKP & TER
            $ptkpData = $this->getPtkpData($payroll);
            
            if (!$ptkpData['success']) {
                return [
                    'success' => false,
                    'pph21' => null,
                    'pph21_deduction' => null,
                    'message' => $ptkpData['message'],
                    'details' => [
                        'total_bruto_dasar' => $totalBrutoDasar
                    ]
                ];
            }
            
            // 3. Get TER based on salary type
            $terData = $this->getTerPercentage(
                $ptkpData['jenis_ter_id'], 
                $totalBrutoDasar, 
                $payroll->salary_type
            );
            
            if (!$terData['success']) {
                return [
                    'success' => false,
                    'pph21' => null,
                    'pph21_deduction' => null,
                    'message' => $terData['message'],
                    'details' => [
                        'total_bruto_dasar' => $totalBrutoDasar,
                        'ptkp_status' => $ptkpData['ptkp_status'],
                        'jenis_ter' => $ptkpData['jenis_ter']
                    ]
                ];
            }
            
            // 4. Calculate PPh21
            $pph21 = $this->calculatePph21(
                $totalBrutoDasar,
                $terData['ter_percentage'],
                $payroll->salary_type
            );
            
            // 5. Calculate PPh21 Deduction
            // ✅ GROSS: dipotong dari gaji = pph21
            // ✅ NETT: tidak dipotong (perusahaan tanggung) = 0
            $pph21Deduction = ($payroll->salary_type === 'gross') ? $pph21 : 0;
            
            return [
                'success' => true,
                'pph21' => $pph21,
                'pph21_deduction' => $pph21Deduction,
                'message' => 'PPh21 berhasil dihitung',
                'details' => [
                    'total_bruto_dasar' => $totalBrutoDasar,
                    'ptkp_status' => $ptkpData['ptkp_status'],
                    'jenis_ter' => $ptkpData['jenis_ter'],
                    'ter_percentage' => $terData['ter_percentage'],
                    'ter_category' => $terData['ter_category'],
                    'salary_type' => $payroll->salary_type,
                    'bruto_final' => $payroll->salary_type === 'nett' 
                        ? $totalBrutoDasar + $pph21 
                        : $totalBrutoDasar,
                    'pph21_deduction_explanation' => $payroll->salary_type === 'gross' 
                        ? 'Dipotong dari gaji karyawan' 
                        : 'Ditanggung perusahaan (tidak dipotong)'
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('Error calculating PPh21', [
                'payroll_id' => $payroll->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'pph21' => null,
                'pph21_deduction' => null,
                'message' => 'Error: ' . $e->getMessage(),
                'details' => []
            ];
        }
    }
    
    /**
     * Calculate PPh21 untuk multiple payroll records (batch)
     * 
     * @param array $payrollIds
     * @return array ['success' => int, 'failed' => int, 'details' => array]
     */
    public function calculateBatch(array $payrollIds)
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => []
        ];
        
        $payrolls = Payroll::whereIn('id', $payrollIds)
            ->where('is_released', false) // Hanya yang belum di-lock
            ->get();
        
        foreach ($payrolls as $payroll) {
            // Skip jika sudah ada pph_21
            if ($payroll->pph_21 !== null && $payroll->pph_21 > 0) {
                $results['skipped']++;
                $results['details'][] = [
                    'payroll_id' => $payroll->id,
                    'periode' => $payroll->periode,
                    'karyawan_id' => $payroll->karyawan_id,
                    'status' => 'skipped',
                    'message' => 'PPh21 sudah dihitung sebelumnya'
                ];
                continue;
            }
            
            $calculation = $this->calculateSingle($payroll);
            
            if ($calculation['success']) {
                // ✅ Update pph_21 DAN pph_21_deduction ke database
                $payroll->update([
                    'pph_21' => $calculation['pph21'],
                    'pph_21_deduction' => $calculation['pph21_deduction']
                ]);
                
                $results['success']++;
                $results['details'][] = [
                    'payroll_id' => $payroll->id,
                    'periode' => $payroll->periode,
                    'karyawan_id' => $payroll->karyawan_id,
                    'status' => 'success',
                    'pph21' => $calculation['pph21'],
                    'pph21_deduction' => $calculation['pph21_deduction'],
                    'calculation_details' => $calculation['details']
                ];
            } else {
                $results['failed']++;
                $results['details'][] = [
                    'payroll_id' => $payroll->id,
                    'periode' => $payroll->periode,
                    'karyawan_id' => $payroll->karyawan_id,
                    'status' => 'failed',
                    'message' => $calculation['message']
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Calculate PPh21 untuk semua payroll di periode tertentu
     * 
     * @param string $periode Format: YYYY-MM
     * @return array
     */
    public function calculateByPeriode(string $periode)
    {
        $payrolls = Payroll::where('periode', $periode)
            ->where('is_released', false)
            ->pluck('id')
            ->toArray();
        
        return $this->calculateBatch($payrolls);
    }
    
    /**
     * Recalculate (force update) PPh21
     * 
     * @param array $payrollIds
     * @return array
     */
    public function recalculateBatch(array $payrollIds)
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        $payrolls = Payroll::whereIn('id', $payrollIds)
            ->where('is_released', false)
            ->get();
        
        foreach ($payrolls as $payroll) {
            $calculation = $this->calculateSingle($payroll);
            
            if ($calculation['success']) {
                // ✅ Update pph_21 DAN pph_21_deduction
                $payroll->update([
                    'pph_21' => $calculation['pph21'],
                    'pph_21_deduction' => $calculation['pph21_deduction']
                ]);
                
                $results['success']++;
                $results['details'][] = [
                    'payroll_id' => $payroll->id,
                    'periode' => $payroll->periode,
                    'karyawan_id' => $payroll->karyawan_id,
                    'status' => 'success',
                    'pph21' => $calculation['pph21'],
                    'pph21_deduction' => $calculation['pph21_deduction'],
                    'calculation_details' => $calculation['details']
                ];
            } else {
                $results['failed']++;
                $results['details'][] = [
                    'payroll_id' => $payroll->id,
                    'periode' => $payroll->periode,
                    'karyawan_id' => $payroll->karyawan_id,
                    'status' => 'failed',
                    'message' => $calculation['message']
                ];
            }
        }
        
        return $results;
    }
    
    // ==========================================
    // PRIVATE HELPER METHODS
    // ==========================================
    
    /**
     * Calculate total bruto dasar dari semua komponen
     */

        private function calculateTotalBrutoDasar(Payroll $payroll)
    {
        $base =
            ($payroll->gaji_pokok ?? 0) +
            ($payroll->monthly_kpi ?? 0) +
            ($payroll->rapel ?? 0) +
            ($payroll->overtime ?? 0) +
            ($payroll->insentif_sholat ?? 0) +
            ($payroll->monthly_bonus ?? 0) +
            ($payroll->tunjangan_pulsa ?? 0) +
            ($payroll->tunjangan_kehadiran ?? 0) +
            ($payroll->tunjangan_transport ?? 0) +
            ($payroll->tunjangan_lainnya ?? 0) +
            ($payroll->medical_reimbursement ?? 0) +

            // ✅ BPJS PERUSAHAAN (boleh masuk bruto—sesuai Ortax yang lu pakai)
            ($payroll->bpjs_tk_jkk_0_24_percent ?? 0) +
            ($payroll->bpjs_tk_jkm_0_3_percent ?? 0) +
            ($payroll->bpjs_kes_4_percent ?? 0) +

            // yearly benefit
            ($payroll->yearly_bonus ?? 0) +
            ($payroll->thr ?? 0) +
            ($payroll->other ?? 0) +
            ($payroll->glh ?? 0) +
            ($payroll->lm ?? 0) +
            ($payroll->lainnya ?? 0);

        // ✅ BPJS PEGAWAI hanya untuk NETT (sesuai view lu sebelumnya)
        if ($payroll->salary_type === 'nett') {
            $base +=
                ($payroll->bpjs_tk_jht_2_percent ?? 0) +
                ($payroll->bpjs_tk_jp_1_percent ?? 0) +
                ($payroll->bpjs_kes_1_percent ?? 0);
        }

        return $base;
    }

    
    /**
     * Get PTKP data untuk karyawan
     */
    private function getPtkpData(Payroll $payroll)
    {
        // Extract year from periode (YYYY-MM)
        $year = (int) substr($payroll->periode, 0, 4);
        
        // Get PTKP History
        $ptkpHistory = KaryawanPtkpHistory::where('absen_karyawan_id', $payroll->karyawan_id)
            ->where('tahun', $year)
            ->first();
        
        if (!$ptkpHistory) {
            return [
                'success' => false,
                'message' => 'PTKP history tidak ditemukan untuk karyawan ini di tahun ' . $year
            ];
        }
        
        // Get PTKP detail
        $ptkp = ListPtkp::where('absen_ptkp_id', $ptkpHistory->absen_ptkp_id)->first();
        
        if (!$ptkp) {
            return [
                'success' => false,
                'message' => 'Data PTKP tidak ditemukan'
            ];
        }
        
        // Get Jenis TER
        $jenisTer = JenisTer::where('absen_jenis_ter_id', $ptkp->absen_jenis_ter_id)->first();
        
        if (!$jenisTer) {
            return [
                'success' => false,
                'message' => 'Jenis TER tidak ditemukan'
            ];
        }
        
        return [
            'success' => true,
            'ptkp_status' => $ptkp->status,
            'jenis_ter' => $jenisTer->jenis_ter,
            'jenis_ter_id' => $jenisTer->absen_jenis_ter_id
        ];
    }
    
    /**
     * Get TER percentage berdasarkan jenis TER dan bruto
     */
    private function getTerPercentage($jenisTerid, $totalBrutoDasar, $salaryType)
    {
        if ($salaryType === 'gross') {
            // Untuk GROSS: cek langsung dari total_bruto_dasar
            $rangeBruto = RangeBruto::where('absen_jenis_ter_id', $jenisTerid)
                ->where('min_bruto', '<=', $totalBrutoDasar)
                ->where(function($query) use ($totalBrutoDasar) {
                    $query->whereNull('max_bruto')
                          ->orWhere('max_bruto', '>=', $totalBrutoDasar);
                })
                ->orderBy('min_bruto', 'desc')
                ->first();
        } else {
            // Untuk NETT: cari TER yang setelah gross-up masih dalam range
            $rangeBruto = $this->findTerForNett($jenisTerid, $totalBrutoDasar);
        }
        
        if (!$rangeBruto) {
            return [
                'success' => false,
                'message' => 'TER tidak ditemukan untuk bruto ' . number_format($totalBrutoDasar, 0, ',', '.')
            ];
        }
        
        return [
            'success' => true,
            'ter_percentage' => $rangeBruto->ter,
            'ter_category' => "Range: " . number_format($rangeBruto->min_bruto, 0, ',', '.') . 
                            " - " . ($rangeBruto->max_bruto ? number_format($rangeBruto->max_bruto, 0, ',', '.') : '∞')
        ];
    }
    
    /**
     * Find TER untuk NETT (gross-up)
     */
    private function findTerForNett($jenisTerid, $totalBrutoDasar)
    {
        $ranges = RangeBruto::where('absen_jenis_ter_id', $jenisTerid)
            ->orderBy('min_bruto', 'asc')
            ->get();
        
        foreach ($ranges as $range) {
            // Hitung bruto setelah gross-up
            $terDecimal = $range->ter / 100;
            $brutoDasarSetelahGrossUp = $totalBrutoDasar + floor(
                ($totalBrutoDasar * $terDecimal) / (1 - $terDecimal)
            );
            
            // Cek apakah masih dalam range
            $inRange = $brutoDasarSetelahGrossUp >= $range->min_bruto &&
                      ($range->max_bruto === null || $brutoDasarSetelahGrossUp <= $range->max_bruto);
            
            if ($inRange) {
                return $range;
            }
        }
        
        return null;
    }
    
    /**
     * Calculate PPh21 menggunakan formula gross-up
     */
    private function calculatePph21($totalBrutoDasar, $terPercentage, $salaryType)
    {
        $terDecimal = $terPercentage / 100;
        
        if ($salaryType === 'nett') {
            // Formula gross-up: PPh21 = (N × r) / (1 - r)
            $pph21 = floor(
                ($totalBrutoDasar * $terDecimal) / (1 - $terDecimal)
            );
        } else {
            // Formula gross: PPh21 = Bruto × r
            $pph21 = floor($totalBrutoDasar * $terDecimal);
        }
        
        return $pph21;
    }
}