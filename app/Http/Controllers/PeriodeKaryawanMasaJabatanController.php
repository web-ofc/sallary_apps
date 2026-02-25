<?php

namespace App\Http\Controllers;

use App\Models\PeriodeKaryawanMasaJabatan;
use App\Models\Karyawan;
use App\Models\Company;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
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

    // =========================================================================
    // HELPER — sama persis seperti di PayrollController
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

        $periodes = PeriodeKaryawanMasaJabatan::selectRaw('DISTINCT periode')
            ->when(!empty($companyIds), fn($q) => $q->whereIn('company_id', $companyIds))
            ->when(empty($companyIds),  fn($q) => $q->whereRaw('1 = 0'))
            ->orderBy('periode', 'desc')
            ->pluck('periode');

        // Company filter hanya tampilkan yang assigned
        $companies = Company::select('absen_company_id', 'company_name', 'code')
            ->when(!empty($companyIds), fn($q) => $q->whereIn('absen_company_id', $companyIds))
            ->when(empty($companyIds),  fn($q) => $q->whereRaw('1 = 0'))
            ->orderBy('company_name')
            ->get();

        $karyawans = Karyawan::select('absen_karyawan_id', 'nama_lengkap', 'nik')
            ->active()
            ->orderBy('nama_lengkap')
            ->get();

        return view('dashboard.dashboard-admin.periode-karyawan-masa-jabatan.index', compact('periodes', 'karyawans', 'companies'));
    }

    // =========================================================================
    // DATATABLES
    // =========================================================================
    public function datatables(Request $request)
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

        // Filter by periode
        if ($request->filled('periode')) {
            $query->where('periode', $request->periode);
        }

        // Filter by karyawan
        if ($request->filled('karyawan_id')) {
            $query->where('karyawan_id', $request->karyawan_id);
        }

        // Filter by company — hanya boleh dari assigned companies
        if ($request->filled('company_id')) {
            // Pastikan company_id yang diminta memang assigned ke user ini
            if (!empty($companyIds) && in_array($request->company_id, $companyIds)) {
                $query->where('company_id', $request->company_id);
            }
        }

        // Filter by salary type
        if ($request->filled('salary_type')) {
            $query->where('salary_type', $request->salary_type);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('karyawan', fn($kq) =>
                    $kq->where('nama_lengkap', 'like', "%{$search}%")
                       ->orWhere('nik', 'like', "%{$search}%")
                )
                ->orWhereHas('company', fn($cq) =>
                    $cq->where('company_name', 'like', "%{$search}%")
                       ->orWhere('code', 'like', "%{$search}%")
                )
                ->orWhere('periode', 'like', "%{$search}%")
                ->orWhere('salary_type', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('periode_with_tooltip', function ($row) {
                $payrollIds = explode(',', $row->payroll_ids);
                $payrolls   = \App\Models\Payroll::whereIn('id', $payrollIds)
                    ->orderBy('periode')->get(['id', 'periode']);
                $periodeList = $payrolls->map(fn($p) =>
                    \Carbon\Carbon::parse($p->periode . '-01')->format('M Y')
                )->implode(', ');

                return view('dashboard.dashboard-admin.periode-karyawan-masa-jabatan.partials.periode-tooltip', [
                    'periode'     => $row->periode,
                    'periodeList' => $periodeList,
                    'payrolls'    => $payrolls,
                ])->render();
            })
            ->addColumn('karyawan_info',  fn($row) => view('dashboard.dashboard-admin.periode-karyawan-masa-jabatan.partials.karyawan-info',  compact('row'))->render())
            ->addColumn('company_info',   fn($row) => view('dashboard.dashboard-admin.periode-karyawan-masa-jabatan.partials.company-info',   compact('row'))->render())
            ->addColumn('salary_type_badge', fn($row) =>
                '<span class="badge ' . ($row->salary_type === 'nett' ? 'badge-light-success' : 'badge-light-primary') . '">'
                . strtoupper($row->salary_type) . '</span>'
            )
            ->addColumn('formatted_salary',            fn($row) => 'Rp ' . number_format($row->salary,            0, ',', '.'))
            ->addColumn('formatted_overtime',           fn($row) => 'Rp ' . number_format($row->overtime,          0, ',', '.'))
            ->addColumn('formatted_tunjangan',          fn($row) => 'Rp ' . number_format($row->tunjangan,         0, ',', '.'))
            ->addColumn('formatted_natura',             fn($row) => 'Rp ' . number_format($row->natura,            0, ',', '.'))
            ->addColumn('formatted_tunj_pph_21',        fn($row) => 'Rp ' . number_format($row->tunj_pph_21,       0, ',', '.'))
            ->addColumn('formatted_tunjangan_asuransi', fn($row) => 'Rp ' . number_format($row->tunjangan_asuransi,0, ',', '.'))
            ->addColumn('formatted_bpjs_asuransi',      fn($row) => 'Rp ' . number_format($row->bpjs_asuransi,     0, ',', '.'))
            ->addColumn('formatted_thr_bonus',          fn($row) => 'Rp ' . number_format($row->thr_bonus,         0, ',', '.'))
            ->addColumn('formatted_total_bruto',        fn($row) => 'Rp ' . number_format($row->total_bruto,       0, ',', '.'))
            ->addColumn('formatted_biaya_jabatan',      fn($row) => 'Rp ' . number_format($row->biaya_jabatan,     0, ',', '.'))
            ->addColumn('formatted_premi_asuransi',     fn($row) => 'Rp ' . number_format($row->premi_asuransi,    0, ',', '.'))
            ->addColumn('formatted_besaran_ptkp',       fn($row) => 'Rp ' . number_format($row->besaran_ptkp,      0, ',', '.'))
            ->addColumn('formatted_pkp', fn($row) =>
                '<span class="' . ($row->pkp < 0 ? 'text-danger' : 'text-success') . '">Rp '
                . number_format($row->pkp, 0, ',', '.') . '</span>'
            )
            ->addColumn('action', fn($row) => view('dashboard.dashboard-admin.periode-karyawan-masa-jabatan.partials.action', compact('row'))->render())
            ->rawColumns(['salary_type_badge', 'formatted_pkp', 'action', 'karyawan_info', 'company_info', 'periode_with_tooltip'])
            ->make(true);
    }

    // =========================================================================
    // SHOW
    // =========================================================================
    public function show($periode, $karyawanId, $companyId, $salaryType)
    {
        $companyIds = $this->getAssignedCompanyIds();

        // ✅ Pastikan company yang diminta adalah assigned company
        if (!empty($companyIds) && !in_array($companyId, $companyIds)) {
            abort(403, 'Unauthorized action.');
        }

        $data = PeriodeKaryawanMasaJabatan::where('periode', $periode)
            ->where('karyawan_id', $karyawanId)
            ->where('company_id', $companyId)
            ->where('salary_type', $salaryType)
            ->with(['karyawan', 'company'])
            ->firstOrFail();

        return view('dashboard.dashboard-admin.periode-karyawan-masa-jabatan.show', compact('data'));
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
                'periode'     => $request->input('periode'),
                'karyawan_id' => $request->input('karyawan_id'),
                'company_id'  => $request->input('company_id'),
                'salary_type' => $request->input('salary_type'),
                'search'      => $request->input('search'),
                'company_ids' => $companyIds, // ✅ pass ke Export class
            ];

            $timestamp = now()->format('Y-m-d_His');
            $filename  = "periode_karyawan_masa_jabatan_{$timestamp}.xlsx";

            Log::info('Export Periode Karyawan initiated', [
                'user_id'    => auth()->id(),
                'filters'    => $filters,
                'timestamp'  => $timestamp,
            ]);

            return Excel::download(
                new PeriodeKaryawanExport($filters),
                $filename,
                \Maatwebsite\Excel\Excel::XLSX
            );

        } catch (\Exception $e) {
            Log::error('Export failed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function create()  {}
    public function store(Request $request) {}
    public function edit($id) {}
    public function update(Request $request, $id) {}
    public function destroy($id) {}
}