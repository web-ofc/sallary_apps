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
    
    /**
     * Display index page
     */
    public function index()
    {
        $companies = Company::orderBy('company_name')->get();
        
        // Ambil bracket info untuk header (default: tahun sekarang)
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
        $query = PeriodeKaryawanMasaJabatan::query()
        ->with(['karyawan:absen_karyawan_id,nama_lengkap,nik', 'company:absen_company_id,company_name,code']);
    
        // Filter by year (periode)
        if ($request->filled('year')) {
            $query->where('periode', $request->year);
        }
        
         
        // Filter by company
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        
        // Filter by search (nama karyawan atau NIK)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('karyawan', function($query) use ($search) {
                    $query->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%");
                });
            });
        }
        
        return DataTables::of($query)
            ->addColumn('karyawan_nama', function($row) {
                return $row->karyawan->nama_lengkap ?? '-';
            })
            ->addColumn('karyawan_nik', function($row) {
                return $row->karyawan->nik ?? '-';
            })
            ->addColumn('company_name', function($row) {
                return $row->company->company_name ?? '-';
            })
            // Bracket 1
            ->addColumn('bracket_1_pkp', function($row) {
                return $this->calculateBracketData($row, 1)['pkp'];
            })
            ->addColumn('bracket_1_pph21', function($row) {
                return $this->calculateBracketData($row, 1)['pph21'];
            })
            // Bracket 2
            ->addColumn('bracket_2_pkp', function($row) {
                return $this->calculateBracketData($row, 2)['pkp'];
            })
            ->addColumn('bracket_2_pph21', function($row) {
                return $this->calculateBracketData($row, 2)['pph21'];
            })
            // Bracket 3
            ->addColumn('bracket_3_pkp', function($row) {
                return $this->calculateBracketData($row, 3)['pkp'];
            })
            ->addColumn('bracket_3_pph21', function($row) {
                return $this->calculateBracketData($row, 3)['pph21'];
            })
            // Bracket 4
            ->addColumn('bracket_4_pkp', function($row) {
                return $this->calculateBracketData($row, 4)['pkp'];
            })
            ->addColumn('bracket_4_pph21', function($row) {
                return $this->calculateBracketData($row, 4)['pph21'];
            })
            // Bracket 5
            ->addColumn('bracket_5_pkp', function($row) {
                return $this->calculateBracketData($row, 5)['pkp'];
            })
            ->addColumn('bracket_5_pph21', function($row) {
                return $this->calculateBracketData($row, 5)['pph21'];
            })
            // PPh21 Tahunan
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
            // PPh21 Masa (dari tunj_pph_21)
            ->addColumn('pph21_masa', function($row) {
                return $row->tunj_pph_21;
            })
            // PPh21 Akhir (dari tunj_pph21_akhir)
            ->addColumn('pph21_akhir', function($row) {
                return $row->tunj_pph21_akhir;
            })
                // ✅ FIX: Gunakan orderColumn untuk kolom yang pakai relationship
            ->orderColumn('karyawan_nama', function ($query, $order) {
                // Tidak perlu ordering di sini karena kita query langsung dari view
                return $query;
            })
            ->orderColumn('company_name', function ($query, $order) {
                // Tidak perlu ordering di sini karena kita query langsung dari view
                return $query;
            })
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
     * Export to Excel
     */
    public function export(Request $request)
    {
        try {
            set_time_limit(300); // 5 menit
            ini_set('memory_limit', '512M');

            // Collect filters
            $filters = [
                'year' => $request->input('year') ?? date('Y'),
                'company_id' => $request->input('company_id'),
                'search' => $request->input('search'),
            ];

            // Generate filename dengan timestamp
            $timestamp = now()->format('Y-m-d_His');
            $filename = "laporan_pph21_tahunan_{$timestamp}.xlsx";

            // Log export activity
            Log::info('Export PPh21 Tahunan initiated', [
                'user_id' => auth()->id(),
                'filters' => $filters,
                'timestamp' => $timestamp
            ]);

            // Export langsung
            return Excel::download(
                new Pph21TahunanExport($filters, $this->pph21Service), 
                $filename,
                \Maatwebsite\Excel\Excel::XLSX
            );

        } catch (\Exception $e) {
            Log::error('Export PPh21 Tahunan failed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export gagal: ' . $e->getMessage()
            ], 500);
        }
    }
}