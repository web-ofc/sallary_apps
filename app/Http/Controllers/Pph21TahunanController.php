<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PeriodeKaryawanMasaJabatan;
use App\Services\Pph21CalculationService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Exports\Pph21TahunanExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class Pph21TahunanController extends Controller
{
    protected $pph21Service;
    
    public function __construct(Pph21CalculationService $pph21Service)
    {
        $this->pph21Service = $pph21Service;

        $this->middleware(function ($request, $next) {
            if (Gate::denies('pph21-tahunan')) {
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        });
    }

    // =========================================================================
    // HELPER
    // =========================================================================
    private function getAssignedCompanyIds(): array
    {
        return Auth::user()
            ->assignedCompanies()
            ->pluck('companies.absen_company_id')
            ->toArray();
    }
    
    // =========================================================================
    // INDEX
    // =========================================================================
    public function index()
    {
        $companyIds = $this->getAssignedCompanyIds();

        $companies = Company::orderBy('company_name')
            ->when(!empty($companyIds), fn($q) => $q->whereIn('absen_company_id', $companyIds))
            ->when(empty($companyIds),  fn($q) => $q->whereRaw('1 = 0'))
            ->get();
        
        $bracketHeaders = $this->pph21Service->formatBracketHeaderInfo();
        
        return view('dashboard.pph21-tahunan.index', compact('companies', 'bracketHeaders'));
    }
    
    // =========================================================================
    // GET BRACKET HEADERS (AJAX)
    // =========================================================================
    public function getBracketHeaders(Request $request)
    {
        $year = $request->year ?? date('Y');
        $date = "{$year}-12-31";
        
        $bracketHeaders = $this->pph21Service->formatBracketHeaderInfo($date);
        
        return response()->json($bracketHeaders);
    }
    
    // =========================================================================
    // DATATABLES
    // =========================================================================
    public function getData(Request $request)
    {
        $companyIds = $this->getAssignedCompanyIds();

        $query = PeriodeKaryawanMasaJabatan::query()
            ->with(['karyawan:absen_karyawan_id,nama_lengkap,nik', 'company:absen_company_id,company_name,code']);

        // ✅ Filter assigned companies
        if (!empty($companyIds)) {
            $query->whereIn('company_id', $companyIds);
        } else {
            $query->whereRaw('1 = 0');
        }
    
        if ($request->filled('year')) {
            $query->where('periode', $request->year);
        }

        // ✅ Filter company — validasi harus dari assigned list
        if ($request->filled('company_id')) {
            if (!empty($companyIds) && in_array($request->company_id, $companyIds)) {
                $query->where('company_id', $request->company_id);
            }
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('karyawan', fn($kq) =>
                    $kq->where('nama_lengkap', 'like', "%{$search}%")
                       ->orWhere('nik', 'like', "%{$search}%")
                );
            });
        }
        
        return DataTables::of($query)
            ->addColumn('karyawan_nama',  fn($row) => $row->karyawan->nama_lengkap ?? '-')
            ->addColumn('karyawan_nik',   fn($row) => $row->karyawan->nik ?? '-')
            ->addColumn('company_name',   fn($row) => $row->company->company_name ?? '-')
            ->addColumn('bracket_1_pkp',  fn($row) => $this->calculateBracketData($row, 1)['pkp'])
            ->addColumn('bracket_1_pph21',fn($row) => $this->calculateBracketData($row, 1)['pph21'])
            ->addColumn('bracket_2_pkp',  fn($row) => $this->calculateBracketData($row, 2)['pkp'])
            ->addColumn('bracket_2_pph21',fn($row) => $this->calculateBracketData($row, 2)['pph21'])
            ->addColumn('bracket_3_pkp',  fn($row) => $this->calculateBracketData($row, 3)['pkp'])
            ->addColumn('bracket_3_pph21',fn($row) => $this->calculateBracketData($row, 3)['pph21'])
            ->addColumn('bracket_4_pkp',  fn($row) => $this->calculateBracketData($row, 4)['pkp'])
            ->addColumn('bracket_4_pph21',fn($row) => $this->calculateBracketData($row, 4)['pph21'])
            ->addColumn('bracket_5_pkp',  fn($row) => $this->calculateBracketData($row, 5)['pkp'])
            ->addColumn('bracket_5_pph21',fn($row) => $this->calculateBracketData($row, 5)['pph21'])
            ->addColumn('pph21_tahunan', function($row) {
                $lastPeriodDate = $this->pph21Service->getLastPeriodDate($row->karyawan_id, $row->periode);
                $pph21Data      = $this->pph21Service->calculatePph21Tahunan((float) $row->pkp, $lastPeriodDate);
                return $pph21Data['total_pph21_tahunan'];
            })
            ->addColumn('pph21_masa',  fn($row) => $row->tunj_pph_21)
            ->addColumn('pph21_akhir', fn($row) => $row->tunj_pph21_akhir)
            ->orderColumn('karyawan_nama', fn($query, $order) => $query)
            ->orderColumn('company_name',  fn($query, $order) => $query)
            ->make(true);
    }
    
    // =========================================================================
    // HELPER BRACKET
    // =========================================================================
    private function calculateBracketData($row, $bracketIndex)
    {
        $lastPeriodDate = $this->pph21Service->getLastPeriodDate($row->karyawan_id, $row->periode);
        $pph21Data      = $this->pph21Service->calculatePph21Tahunan((float) $row->pkp, $lastPeriodDate);
        $breakdown      = $this->pph21Service->formatBreakdownForDisplay($pph21Data['breakdown']);
        
        return [
            'pkp'   => $breakdown["bracket_{$bracketIndex}_pkp"]   ?? 0,
            'pph21' => $breakdown["bracket_{$bracketIndex}_pph21"] ?? 0,
        ];
    }
    
    // =========================================================================
    // EXPORT
    // =========================================================================
    public function export(Request $request)
    {
        try {
            set_time_limit(300);
            ini_set('memory_limit', '512M');

            $companyIds = $this->getAssignedCompanyIds();

            $filters = [
                'year'        => $request->input('year') ?? date('Y'),
                'company_id'  => $request->input('company_id'),
                'search'      => $request->input('search'),
                'company_ids' => $companyIds, // ✅ pass ke Export class
            ];

            $timestamp = now()->format('Y-m-d_His');
            $filename  = "laporan_pph21_tahunan_{$timestamp}.xlsx";

            Log::info('Export PPh21 Tahunan initiated', [
                'user_id'   => auth()->id(),
                'filters'   => $filters,
                'timestamp' => $timestamp,
            ]);

            return Excel::download(
                new Pph21TahunanExport($filters, $this->pph21Service), 
                $filename,
                \Maatwebsite\Excel\Excel::XLSX
            );

        } catch (\Exception $e) {
            Log::error('Export PPh21 Tahunan failed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export gagal: ' . $e->getMessage(),
            ], 500);
        }
    }
}