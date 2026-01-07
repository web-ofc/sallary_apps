<?php

namespace App\Http\Controllers;

use App\Models\PeriodeKaryawanMasaJabatan;
use App\Models\Karyawan;
use App\Models\Company;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Gate;
use App\Exports\PeriodeKaryawanExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class PeriodeKaryawanMasaJabatanController extends Controller
{
    
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Gate::denies('periode-karyawan')) {
                abort(403, 'Unauthorized action.');
            }

            return $next($request);
        });
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get list tahun untuk filter
        $periodes = PeriodeKaryawanMasaJabatan::selectRaw('DISTINCT periode')
            ->orderBy('periode', 'desc')
            ->pluck('periode');
        
        // Get list karyawan untuk filter
        $karyawans = Karyawan::select('absen_karyawan_id', 'nama_lengkap', 'nik')
            ->active()
            ->orderBy('nama_lengkap')
            ->get();
        
        // Get list company untuk filter
        $companies = Company::select('absen_company_id', 'company_name', 'code')
            ->orderBy('company_name')
            ->get();
        
        return view('dashboard.dashboard-admin.periode-karyawan-masa-jabatan.index', compact('periodes', 'karyawans', 'companies'));
    }

    /**
     * DataTables serverside processing
     */
        public function datatables(Request $request)
    {
        $query = PeriodeKaryawanMasaJabatan::query()
            ->with(['karyawan:absen_karyawan_id,nama_lengkap,nik', 'company:absen_company_id,company_name,code']);
        
        // Filter by periode (tahun)
        if ($request->filled('periode')) {
            $query->where('periode', $request->periode);
        }
        
        // Filter by karyawan
        if ($request->filled('karyawan_id')) {
            $query->where('karyawan_id', $request->karyawan_id);
        }
        
        // Filter by company
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        
        // Filter by salary type
        if ($request->filled('salary_type')) {
            $query->where('salary_type', $request->salary_type);
        }
        
        // Custom search untuk nama karyawan dan company
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('karyawan', function($query) use ($search) {
                    $query->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%");
                })
                ->orWhereHas('company', function($query) use ($search) {
                    $query->where('company_name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                })
                ->orWhere('periode', 'like', "%{$search}%")
                ->orWhere('salary_type', 'like', "%{$search}%");
            });
        }
        
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('periode_with_tooltip', function ($row) {
                // Ambil detail payroll berdasarkan payroll_ids
                $payrollIds = explode(',', $row->payroll_ids);
                $payrolls = \App\Models\Payroll::whereIn('id', $payrollIds)
                    ->orderBy('periode')
                    ->get(['id', 'periode']);
                
                // Format periode bulan
                $periodeList = $payrolls->map(function($p) {
                    return \Carbon\Carbon::parse($p->periode . '-01')->format('M Y');
                })->implode(', ');
                
                return view('dashboard.dashboard-admin.periode-karyawan-masa-jabatan.partials.periode-tooltip', [
                    'periode' => $row->periode,
                    'periodeList' => $periodeList,
                    'payrolls' => $payrolls
                ])->render();
            })
            ->addColumn('karyawan_info', function ($row) {
                return view('dashboard.dashboard-admin.periode-karyawan-masa-jabatan.partials.karyawan-info', compact('row'))->render();
            })
            ->addColumn('company_info', function ($row) {
                return view('dashboard.dashboard-admin.periode-karyawan-masa-jabatan.partials.company-info', compact('row'))->render();
            })
            ->addColumn('salary_type_badge', function ($row) {
                $badgeClass = $row->salary_type === 'nett' ? 'badge-light-success' : 'badge-light-primary';
                return '<span class="badge ' . $badgeClass . '">' . strtoupper($row->salary_type) . '</span>';
            })
            ->addColumn('formatted_salary', function ($row) {
                return 'Rp ' . number_format($row->salary, 0, ',', '.');
            })
            ->addColumn('formatted_overtime', function ($row) {
                return 'Rp ' . number_format($row->overtime, 0, ',', '.');
            })
            ->addColumn('formatted_tunjangan', function ($row) {
                return 'Rp ' . number_format($row->tunjangan, 0, ',', '.');
            })
            ->addColumn('formatted_natura', function ($row) {
                return 'Rp ' . number_format($row->natura, 0, ',', '.');
            })
            ->addColumn('formatted_tunj_pph_21', function ($row) {
                return 'Rp ' . number_format($row->tunj_pph_21, 0, ',', '.');
            })
            ->addColumn('formatted_tunjangan_asuransi', function ($row) {
                return 'Rp ' . number_format($row->tunjangan_asuransi, 0, ',', '.');
            })
            ->addColumn('formatted_bpjs_asuransi', function ($row) {
                return 'Rp ' . number_format($row->bpjs_asuransi, 0, ',', '.');
            })
            ->addColumn('formatted_thr_bonus', function ($row) {
                return 'Rp ' . number_format($row->thr_bonus, 0, ',', '.');
            })
            ->addColumn('formatted_total_bruto', function ($row) {
                return 'Rp ' . number_format($row->total_bruto, 0, ',', '.');
            })
            ->addColumn('formatted_biaya_jabatan', function ($row) {
                return 'Rp ' . number_format($row->biaya_jabatan, 0, ',', '.');
            })
            ->addColumn('formatted_premi_asuransi', function ($row) {
                return 'Rp ' . number_format($row->premi_asuransi, 0, ',', '.');
            })
            ->addColumn('formatted_besaran_ptkp', function ($row) {
                return 'Rp ' . number_format($row->besaran_ptkp, 0, ',', '.');
            })
            ->addColumn('formatted_pkp', function ($row) {
                $class = $row->pkp < 0 ? 'text-danger' : 'text-success';
                return '<span class="' . $class . '">Rp ' . number_format($row->pkp, 0, ',', '.') . '</span>';
            })
            ->addColumn('action', function ($row) {
                return view('dashboard.dashboard-admin.periode-karyawan-masa-jabatan.partials.action', compact('row'))->render();
            })
            ->rawColumns(['salary_type_badge', 'formatted_pkp', 'action', 'karyawan_info', 'company_info','periode_with_tooltip'])
            ->make(true);
    }

    /**
     * Show detail
     */
    public function show($periode, $karyawanId, $companyId, $salaryType)
    {
        $data = PeriodeKaryawanMasaJabatan::where('periode', $periode)
            ->where('karyawan_id', $karyawanId)
            ->where('company_id', $companyId)
            ->where('salary_type', $salaryType)
            ->with(['karyawan', 'company'])
            ->firstOrFail();
        
        return view('dashboard.dashboard-admin.periode-karyawan-masa-jabatan.show', compact('data'));
    }

        public function export(Request $request)
    {
        try {
            // Set max execution time untuk export besar
            set_time_limit(300); // 5 menit
            ini_set('memory_limit', '512M'); // Increase memory limit

            // Collect filters
            $filters = [
                'periode' => $request->input('periode'),
                'karyawan_id' => $request->input('karyawan_id'),
                'company_id' => $request->input('company_id'),
                'salary_type' => $request->input('salary_type'),
                'search' => $request->input('search'),
            ];

            // Generate filename dengan timestamp
            $timestamp = now()->format('Y-m-d_His');
            $filename = "periode_karyawan_masa_jabatan_{$timestamp}.xlsx";

            // Log export activity
            Log::info('Export Periode Karyawan initiated', [
                'user_id' => auth()->id(),
                'filters' => $filters,
                'timestamp' => $timestamp
            ]);

            // Export dengan queue untuk file besar (optional)
            // return Excel::queue(new PeriodeKaryawanExport($filters), $filename);

            // Export langsung (untuk file kecil-menengah)
            return Excel::download(
                new PeriodeKaryawanExport($filters), 
                $filename,
                \Maatwebsite\Excel\Excel::XLSX
            );

        } catch (\Exception $e) {
            Log::error('Export failed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit( $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy( $id)
    {
        //
    }
}
