<?php

namespace App\Services;

use App\Models\Pph21TaxBracket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Pph21CalculationService
{
    /**
     * Hitung PPh 21 Tahunan berdasarkan PKP dan tanggal periode
     */
    public function calculatePph21Tahunan(float $pkp, string $periodeDate): array
    {
        // Parse tanggal periode (misal: '2025-01-01')
        $date = Carbon::parse($periodeDate);
        
        // Ambil bracket yang aktif di tanggal tersebut
        $brackets = $this->getActiveBrackets($date);
        
        $totalPph21 = 0;
        $breakdownDetails = [];
        
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
                $pph21InBracket = $pkpInBracket * ($bracket->rate_percent / 100);
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
            'total_pph21_tahunan' => $totalPph21,
            'breakdown' => $breakdownDetails,
            'period_date' => $periodeDate,
        ];
    }
    
    /**
     * Ambil bracket yang aktif di tanggal tertentu
     */
    public function getActiveBrackets($date = null): \Illuminate\Support\Collection
    {
        $date = $date ? Carbon::parse($date) : now();
        
        return Pph21TaxBracket::where('effective_start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_end_date')
                  ->orWhere('effective_end_date', '>=', $date);
            })
            ->orderBy('order_index')
            ->get();
    }
    
    /**
     * Format bracket untuk header display
     */
    public function formatBracketHeaderInfo($date = null): array
    {
        $brackets = $this->getActiveBrackets($date);
        
        return $brackets->map(function($bracket) {
            return [
                'order_index' => $bracket->order_index,
                'rate_percent' => $bracket->rate_percent,
                'max_pkp' => $bracket->max_pkp,
                'description' => $bracket->description,
                'effective_start_date' => Carbon::parse($bracket->effective_start_date)->format('d M Y'),
                'effective_end_date' => $bracket->effective_end_date 
                    ? Carbon::parse($bracket->effective_end_date)->format('d M Y')
                    : 'Sekarang',
                'max_label' => $this->formatMaxLabel($bracket->max_pkp),
            ];
        })->toArray();
    }
    
    /**
     * Format label max PKP
     */
    private function formatMaxLabel($maxPkp): string
    {
        if ($maxPkp === null) {
            return 'unlimited';
        }
        
        if ($maxPkp >= 1000000000) {
            return 'max ' . number_format($maxPkp / 1000000000, 1) . 'M';
        }
        
        if ($maxPkp >= 1000000) {
            return 'max ' . number_format($maxPkp / 1000000, 0) . 'jt';
        }
        
        return 'max ' . number_format($maxPkp, 0);
    }
    
    /**
     * Ambil periode terakhir karyawan di tahun tertentu
     */
    public function getLastPeriodDate(int $karyawanId, int $year): string
    {
        // Ambil periode terakhir dari payroll karyawan di tahun tersebut
        $lastPeriod = DB::table('payrolls')
            ->where('karyawan_id', $karyawanId)
            ->whereYear(DB::raw("STR_TO_DATE(CONCAT(periode, '-01'), '%Y-%m-%d')"), $year)
            ->orderBy('periode', 'desc')
            ->value('periode');
        
        if (!$lastPeriod) {
            // Fallback ke Desember jika tidak ada data
            return "{$year}-12-01";
        }
        
        // Convert periode format '2025-03' ke '2025-03-01'
        return "{$lastPeriod}-01";
    }
    
    /**
     * Format breakdown untuk display di frontend
     */
    public function formatBreakdownForDisplay(array $breakdown): array
    {
        $formatted = [];
        
        foreach ($breakdown as $bracket) {
            $index = $bracket['order_index'];
            $formatted["bracket_{$index}_pkp"] = $bracket['pkp_in_bracket'];
            $formatted["bracket_{$index}_pph21"] = $bracket['pph21_in_bracket'];
        }
        
        return $formatted;
    }
}