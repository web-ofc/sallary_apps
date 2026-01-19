<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Karyawan;
use App\Services\Pph21CalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class Pph21TahunanController extends Controller
{
    protected $pph21Service;
    
    public function __construct(Pph21CalculationService $pph21Service)
    {
        $this->pph21Service = $pph21Service;
    }
    
    /**
     * Display index page
     */
    public function index()
    {
        $companies = Company::orderBy('company_name')->get();
        
        // Ambil bracket info untuk header (default: sekarang)
        $bracketHeaders = $this->pph21Service->formatBracketHeaderInfo();
        
        return view('dashboard.pph21-tahunan.index', compact('companies', 'bracketHeaders'));
    }
    
    /**
     * Get bracket headers by year (AJAX)
     */
    public function getBracketHeaders(Request $request)
    {
        $year = $request->year ?? date('Y');
        $date = "{$year}-12-31"; // Ambil bracket di akhir tahun
        
        $bracketHeaders = $this->pph21Service->formatBracketHeaderInfo($date);
        
        return response()->json($bracketHeaders);
    }
    
    /**
     * Get data for DataTables (Server-side)
     */
    public function getData(Request $request)
    {
        $query = DB::table('periode_karyawan_masa_jabatans as pkm')
            ->join('karyawans as k', 'pkm.karyawan_id', '=', 'k.absen_karyawan_id')
            ->join('companies as c', 'pkm.company_id', '=', 'c.absen_company_id')
            ->select(
                'pkm.*',
                'k.nama_lengkap as karyawan_nama',
                'k.nik as karyawan_nik',
                'c.company_name'
            );
        
        // Filter by year
        if ($request->filled('year')) {
            $query->where('pkm.periode', $request->year);
        }
        
        // Filter by company
        if ($request->filled('company_id')) {
            $query->where('pkm.company_id', $request->company_id);
        }
        
        // Filter by search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('k.nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('k.nik', 'like', "%{$search}%");
            });
        }
        
        return DataTables::of($query)
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
            ->addColumn('pph21_tahunan', function($row) {
                $lastPeriodDate = $this->pph21Service->getLastPeriodDate(
                    $row->karyawan_id, 
                    $row->periode
                );
                
                $pph21Data = $this->pph21Service->calculatePph21Tahunan(
                    (float) $row->pkp, 
                    $lastPeriodDate
                );
                
                return $pph21Data['total_pph21_tahunan'];
            })
            ->rawColumns(['karyawan_nama'])
            ->make(true);
    }
    
    /**
     * Helper to calculate bracket data
     */
    private function calculateBracketData($row, $bracketIndex)
    {
        $lastPeriodDate = $this->pph21Service->getLastPeriodDate(
            $row->karyawan_id, 
            $row->periode
        );
        
        $pph21Data = $this->pph21Service->calculatePph21Tahunan(
            (float) $row->pkp, 
            $lastPeriodDate
        );
        
        $breakdown = $this->pph21Service->formatBreakdownForDisplay($pph21Data['breakdown']);
        
        return [
            'pkp' => $breakdown["bracket_{$bracketIndex}_pkp"] ?? 0,
            'pph21' => $breakdown["bracket_{$bracketIndex}_pph21"] ?? 0,
        ];
    }
    
    /**
     * Get detail for modal
     */
    public function getDetail(Request $request)
    {
        $karyawanId = $request->karyawan_id;
        $year = $request->year;
        
        $payroll = DB::table('periode_karyawan_masa_jabatans as pkm')
            ->join('karyawans as k', 'pkm.karyawan_id', '=', 'k.absen_karyawan_id')
            ->select('pkm.*', 'k.nama_lengkap as karyawan_nama', 'k.nik as karyawan_nik')
            ->where('pkm.karyawan_id', $karyawanId)
            ->where('pkm.periode', $year)
            ->first();
        
        if (!$payroll) {
            return response()->json(['message' => 'Data not found'], 404);
        }
        
        $lastPeriodDate = $this->pph21Service->getLastPeriodDate($karyawanId, $year);
        
        $pph21Data = $this->pph21Service->calculatePph21Tahunan(
            (float) $payroll->pkp, 
            $lastPeriodDate
        );
        
        return response()->json([
            'karyawan_nama' => $payroll->karyawan_nama,
            'karyawan_nik' => $payroll->karyawan_nik,
            'periode' => $payroll->periode,
            'last_period' => date('F Y', strtotime($lastPeriodDate)),
            'ptkp_status' => $payroll->status . ' - ' . $payroll->kriteria,
            'total_bruto' => $payroll->total_bruto,
            'salary' => $payroll->salary,
            'overtime' => $payroll->overtime,
            'tunjangan' => $payroll->tunjangan,
            'thr_bonus' => $payroll->thr_bonus,
            'biaya_jabatan' => $payroll->biaya_jabatan,
            'iuran_jht' => $payroll->iuran_jht,
            'besaran_ptkp' => $payroll->besaran_ptkp,
            'pkp' => $payroll->pkp,
            'pph21_tahunan' => $pph21Data['total_pph21_tahunan'],
            'bracket_details' => $pph21Data['breakdown'],
            'period_date' => $pph21Data['period_date'],
        ]);
    }
}