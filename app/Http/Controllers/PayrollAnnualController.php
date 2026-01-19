<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Services\Pph21AnnualCalculationService;
use App\Services\Pph21CalculationService; // ✅ TAMBAHKAN
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class PayrollAnnualController extends Controller
{
    protected $annualService;
    protected $pph21Service; // ✅ TAMBAHKAN
    
    public function __construct(
        Pph21AnnualCalculationService $annualService,
        Pph21CalculationService $pph21Service // ✅ TAMBAHKAN
    ) {
        $this->annualService = $annualService;
        $this->pph21Service = $pph21Service;
    }
    
    /**
     * Display page untuk hitung PPh21 Tahunan
     */
    public function index()
    {
        // Ambil summary data yang pending
        $pendingCount = Payroll::where('is_last_period', 1)
            ->where('is_released', 0)
            ->count();
        
        // ✅ TAMBAHKAN: Ambil bracket info untuk tahun sekarang
        $currentYear = date('Y');
        $bracketHeaders = $this->pph21Service->formatBracketHeaderInfo("{$currentYear}-12-31");
        
        return view('dashboard.dashboard-admin.payrolls.calculate-annual', compact('pendingCount', 'bracketHeaders'));
    }
    
    /**
     * ✅ BARU: Get bracket headers by year (AJAX)
     */
    public function getBracketHeaders(Request $request)
    {
        $year = $request->year ?? date('Y');
        $date = "{$year}-12-31"; // Ambil bracket di akhir tahun
        
        $bracketHeaders = $this->pph21Service->formatBracketHeaderInfo($date);
        
        return response()->json($bracketHeaders);
    }
    
    /**
     * DataTables untuk data pending annual calculation
     */
    public function datatable(Request $request)
    {
        try {
            $query = DB::table('periode_karyawan_masa_jabatans as pkm')
                ->join('karyawans as k', 'pkm.karyawan_id', '=', 'k.absen_karyawan_id')
                ->join('companies as c', 'pkm.company_id', '=', 'c.absen_company_id')
                ->select(
                    'pkm.*',
                    'k.nama_lengkap as karyawan_nama',
                    'k.nik as karyawan_nik',
                    'c.company_name'
                )
                ->whereExists(function($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('payrolls')
                        ->whereColumn('payrolls.karyawan_id', 'pkm.karyawan_id')
                        ->whereColumn('payrolls.company_id', 'pkm.company_id')
                        ->whereColumn('payrolls.salary_type', 'pkm.salary_type')
                        ->whereRaw('YEAR(STR_TO_DATE(CONCAT(payrolls.periode, "-01"), "%Y-%m-%d")) = pkm.periode')
                        ->where('payrolls.is_last_period', 1)
                        ->where('payrolls.is_released', 0);
                });
            
            // ✅ FILTER BY YEAR (jika ada)
            if ($request->filled('year')) {
                $query->where('pkm.periode', $request->year);
            }
            
            return DataTables::of($query)
                ->addColumn('pph21_masa', function($row) {
                    return $row->tunj_pph_21;
                })
                ->addColumn('pph21_tahunan', function($row) {
                    $calculation = $this->annualService->calculatePph21FromPkp(
                        $row->pkp,
                        $row->karyawan_id,
                        $row->periode
                    );
                    return $calculation['pph21_tahunan'] ?? 0;
                })
                ->addColumn('pph21_akhir', function($row) {
                    $calculation = $this->annualService->calculatePph21FromPkp(
                        $row->pkp,
                        $row->karyawan_id,
                        $row->periode
                    );
                    $pph21Tahunan = $calculation['pph21_tahunan'] ?? 0;
                    $pph21Akhir = $pph21Tahunan - $row->tunj_pph_21;
                    return max(0, $pph21Akhir);
                })
                // ✅ TAMBAHKAN: Bracket columns
                ->addColumn('bracket_1_pkp', function($row) {
                    return $this->calculateBracketData($row, 1)['pkp'];
                })
                ->addColumn('bracket_1_pph21', function($row) {
                    return $this->calculateBracketData($row, 1)['pph21'];
                })
                ->addColumn('bracket_2_pkp', function($row) {
                    return $this->calculateBracketData($row, 2)['pkp'];
                })
                ->addColumn('bracket_2_pph21', function($row) {
                    return $this->calculateBracketData($row, 2)['pph21'];
                })
                ->addColumn('bracket_3_pkp', function($row) {
                    return $this->calculateBracketData($row, 3)['pkp'];
                })
                ->addColumn('bracket_3_pph21', function($row) {
                    return $this->calculateBracketData($row, 3)['pph21'];
                })
                ->addColumn('bracket_4_pkp', function($row) {
                    return $this->calculateBracketData($row, 4)['pkp'];
                })
                ->addColumn('bracket_4_pph21', function($row) {
                    return $this->calculateBracketData($row, 4)['pph21'];
                })
                ->addColumn('bracket_5_pkp', function($row) {
                    return $this->calculateBracketData($row, 5)['pkp'];
                })
                ->addColumn('bracket_5_pph21', function($row) {
                    return $this->calculateBracketData($row, 5)['pph21'];
                })
                ->addColumn('status', function($row) {
                    $hasCalculated = DB::table('payrolls')
                        ->where('karyawan_id', $row->karyawan_id)
                        ->where('company_id', $row->company_id)
                        ->where('salary_type', $row->salary_type)
                        ->whereRaw('YEAR(STR_TO_DATE(CONCAT(periode, "-01"), "%Y-%m-%d")) = ?', [$row->periode])
                        ->where('is_last_period', 1)
                        ->where('is_released', 0)
                        ->whereNotNull('pph_21')
                        ->exists();
                    
                    return $hasCalculated ? 'calculated' : 'pending';
                })
                ->addColumn('action', function($row) {
                    return '
                        <button type="button" class="btn btn-sm btn-info view-detail" 
                                data-karyawan-id="'.$row->karyawan_id.'"
                                data-company-id="'.$row->company_id.'"
                                data-salary-type="'.$row->salary_type.'"
                                data-periode="'.$row->periode.'">
                            <i class="fas fa-eye"></i> Detail
                        </button>
                    ';
                })
                ->addColumn('salary', function($row) {
                    return $row->salary;
                })
                ->addColumn('overtime', function($row) {
                    return $row->overtime;
                })
                ->addColumn('natura', function($row) {
                    return $row->natura;
                })
                ->addColumn('tunj_pph_21', function($row) {
                    return $row->tunj_pph_21;
                })
                ->addColumn('tunj_pph21_akhir', function($row) {
                    return $row->tunj_pph21_akhir;
                })
                ->addColumn('tunjangan', function($row) {
                    return $row->tunjangan;
                })
                ->addColumn('tunjangan_asuransi', function($row) {
                    return $row->tunjangan_asuransi;
                })
                ->addColumn('bpjs_asuransi', function($row) {
                    return $row->bpjs_asuransi;
                })
                ->addColumn('thr_bonus', function($row) {
                    return $row->thr_bonus;
                })
                ->addColumn('masa_jabatan', function($row) {
                    return $row->masa_jabatan;
                })
                ->addColumn('premi_asuransi', function($row) {
                    return $row->premi_asuransi;
                })
                ->addColumn('biaya_jabatan', function($row) {
                    return $row->biaya_jabatan;
                })
                ->addColumn('status', function($row) {
                    return $row->status;
                })
                ->addColumn('iuran_jht', function($row) {
                    return $row->iuran_jht;
                })
                ->addColumn('besaran_ptkp', function($row) {
                    return $row->besaran_ptkp;
                })
                ->rawColumns(['action'])
                ->make(true);
                
        } catch (\Exception $e) {
            Log::error('Error in annual datatable', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * ✅ BARU: Helper untuk calculate bracket data
     */
    private function calculateBracketData($row, $bracketIndex)
    {
        $pph21Data = $this->annualService->calculatePph21FromPkp(
            (float) $row->pkp,
            $row->karyawan_id,
            $row->periode
        );
        
        $breakdown = $this->pph21Service->formatBreakdownForDisplay($pph21Data['bracket_details']);
        
        return [
            'pkp' => $breakdown["bracket_{$bracketIndex}_pkp"] ?? 0,
            'pph21' => $breakdown["bracket_{$bracketIndex}_pph21"] ?? 0,
        ];
    }
    
    /**
     * Process perhitungan PPh21 Tahunan
     */
    public function process(Request $request)
    {
        try {
            set_time_limit(600);
            
            Log::info('========== PPH21 ANNUAL CALCULATION START ==========');
            
            $pendingData = DB::table('periode_karyawan_masa_jabatans as pkm')
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
                ->get();
            
            $results = [
                'success' => 0,
                'failed' => 0,
                'skipped' => 0,
                'total' => $pendingData->count(),
                'details' => []
            ];
            
            foreach ($pendingData as $data) {
                try {
                    $result = $this->annualService->calculateAndUpdate(
                        $data->karyawan_id,
                        $data->company_id,
                        $data->salary_type,
                        $data->periode
                    );
                    
                    if ($result['success']) {
                        if (isset($result['skipped']) && $result['skipped']) {
                            $results['skipped']++;
                            $results['details'][] = [
                                'karyawan_id' => $data->karyawan_id,
                                'periode' => $data->periode,
                                'status' => 'skipped',
                                'message' => $result['message']
                            ];
                        } else {
                            $results['success']++;
                            $results['details'][] = [
                                'karyawan_id' => $data->karyawan_id,
                                'periode' => $data->periode,
                                'status' => 'success',
                                'pph21_akhir' => $result['pph21_akhir'],
                                'updated_count' => $result['updated_count'],
                                'iterations' => $result['iterations'] ?? null
                            ];
                        }
                    } else {
                        $results['failed']++;
                        $results['details'][] = [
                            'karyawan_id' => $data->karyawan_id,
                            'periode' => $data->periode,
                            'status' => 'failed',
                            'message' => $result['message']
                        ];
                    }
                    
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['details'][] = [
                        'karyawan_id' => $data->karyawan_id,
                        'periode' => $data->periode,
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                }
            }
            
            Log::info('========== PPH21 ANNUAL CALCULATION END ==========', [
                'success' => $results['success'],
                'skipped' => $results['skipped'],
                'failed' => $results['failed'],
                'total' => $results['total']
            ]);
            
            $message = "Berhasil: {$results['success']}, Di-skip: {$results['skipped']}, Gagal: {$results['failed']} dari {$results['total']} data";
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error processing annual calculation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get detail untuk modal
     */
    public function getDetail(Request $request)
    {
        try {
            $karyawanId = $request->karyawan_id;
            $companyId = $request->company_id;
            $salaryType = $request->salary_type;
            $periode = $request->periode;
            
            $data = DB::table('periode_karyawan_masa_jabatans as pkm')
                ->join('karyawans as k', 'pkm.karyawan_id', '=', 'k.absen_karyawan_id')
                ->join('companies as c', 'pkm.company_id', '=', 'c.absen_company_id')
                ->select('pkm.*', 'k.nama_lengkap as karyawan_nama', 'k.nik as karyawan_nik', 'c.company_name')
                ->where('pkm.karyawan_id', $karyawanId)
                ->where('pkm.company_id', $companyId)
                ->where('pkm.salary_type', $salaryType)
                ->where('pkm.periode', $periode)
                ->first();
            
            if (!$data) {
                return response()->json(['message' => 'Data not found'], 404);
            }
            
            $calculation = $this->annualService->calculatePph21FromPkp(
                $data->pkp,
                $karyawanId,
                $periode
            );

            $pph21Tahunan = $calculation['pph21_tahunan'] ?? 0;
            $pph21Akhir = max(0, $pph21Tahunan - ($data->tunj_pph_21 ?? 0));
            
            return response()->json([
                'karyawan_nama' => $data->karyawan_nama,
                'karyawan_nik' => $data->karyawan_nik,
                'company_name' => $data->company_name,
                'periode' => $data->periode,
                'salary_type' => $data->salary_type,
                'masa_jabatan' => $data->masa_jabatan,
                
                // ✅ Tambahkan semua kolom
                'salary' => $data->salary,
                'overtime' => $data->overtime,
                'natura' => $data->natura,
                'tunjangan' => $data->tunjangan,
                'thr_bonus' => $data->thr_bonus,
                'tunj_pph_21' => $data->tunj_pph_21,
                'tunj_pph21_akhir' => $data->tunj_pph21_akhir,
                'tunjangan_asuransi' => $data->tunjangan_asuransi,
                'bpjs_asuransi' => $data->bpjs_asuransi,
                'premi_asuransi' => $data->premi_asuransi,
                'biaya_jabatan' => $data->biaya_jabatan,
                'iuran_jht' => $data->iuran_jht,
                'besaran_ptkp' => $data->besaran_ptkp,
                'kriteria' => $data->kriteria,
                'status' => $data->status,
                
                'total_bruto' => $data->total_bruto,
                'pkp' => $data->pkp,
                'pph21_masa' => $data->tunj_pph_21,
                'pph21_tahunan' => $pph21Tahunan,
                'pph21_akhir' => $pph21Akhir,
                'bracket_details' => $calculation['bracket_details'],
                'period_date' => $calculation['period_date']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting detail', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}