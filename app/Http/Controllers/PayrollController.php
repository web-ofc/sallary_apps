<?php
// app/Http/Controllers/PayrollController.php - OPTIMIZED WITH TABS VERSION

namespace App\Http\Controllers;

use ZipArchive;
use App\Models\Company;
use App\Models\Payroll;
use App\Models\Karyawan;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Exports\PayrollsExport;
use App\Models\PayrollCalculation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class PayrollController extends Controller
{
      public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Gate::denies('manage-payroll')) {
                abort(403, 'Unauthorized action.');
            }

            return $next($request);
        });
    }
    // ========================================
    // HELPER: Get Assigned Company IDs
    // ========================================
    private function getAssignedCompanyIds(): array
    {
        return Auth::user()
            ->assignedCompanies()
            ->pluck('companies.absen_company_id')
            ->toArray();
    }

    // ========================================
    // INDEX
    // ========================================
    public function index(Request $request)
    {
        $companyIds = $this->getAssignedCompanyIds();
        
        if (empty($companyIds)) {
            return view('dashboard.dashboard-admin.payrolls.index', [
                'pendingCount'      => 0,
                'releasedCount'     => 0,
                'releasedSlipCount' => 0,
            ]);
        }
        
        $counts = Payroll::whereIn('company_id', $companyIds)
            ->selectRaw('
                SUM(CASE WHEN is_released = 0 THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN is_released = 1 AND is_released_slip = 0 THEN 1 ELSE 0 END) as released_count,
                SUM(CASE WHEN is_released = 1 AND is_released_slip = 1 THEN 1 ELSE 0 END) as released_slip_count
            ')
            ->first();

        return view('dashboard.dashboard-admin.payrolls.index', [
            'pendingCount'      => $counts->pending_count ?? 0,
            'releasedCount'     => $counts->released_count ?? 0,
            'releasedSlipCount' => $counts->released_slip_count ?? 0,
        ]);
    }

    // ========================================
    // BASE QUERY - Ditambah filter company
    // ========================================
    private function baseSelect(): array
    {
        return [
            'id', 'karyawan_id', 'company_id', 'periode', 'salary_type',
            'gaji_pokok', 'monthly_kpi', 'overtime', 'medical_reimbursement',
            'insentif_sholat', 'monthly_bonus', 'rapel',
            'tunjangan_pulsa', 'tunjangan_kehadiran', 'tunjangan_transport', 'tunjangan_lainnya',
            'yearly_bonus', 'thr', 'other',
            'ca_corporate', 'ca_personal', 'ca_kehadiran',
            'pph_21', 'pph_21_deduction',
            'bpjs_tenaga_kerja', 'bpjs_kesehatan',
            'bpjs_tk_jht_3_7_percent', 'bpjs_tk_jht_2_percent',
            'bpjs_tk_jkk_0_24_percent', 'bpjs_tk_jkm_0_3_percent',
            'bpjs_tk_jp_2_percent', 'bpjs_tk_jp_1_percent',
            'bpjs_kes_4_percent', 'bpjs_kes_1_percent',
            'glh', 'lm', 'lainnya', 'tunjangan',
            'salary', 'total_penerimaan', 'total_potongan', 'gaji_bersih',
            'is_released', 'is_released_slip','ptkp_status'
        ];
    }

    private function baseQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        // ✅ Ambil assigned company IDs
        $companyIds = $this->getAssignedCompanyIds();
        
        $query = PayrollCalculation::query()
            ->select($this->baseSelect())
            ->with([
                'karyawan:absen_karyawan_id,nik,nama_lengkap',
                'company:absen_company_id,company_name',
            ]);

        // ✅ Filter berdasarkan assigned companies
        if (!empty($companyIds)) {
            $query->whereIn('company_id', $companyIds);
        } else {
            // Kalau user ga punya company, return empty query
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('periode')) {
            $query->where('periode', $request->periode);
        }

        return $query;
    }

    private function applySearch(\Illuminate\Database\Eloquent\Builder $query, Request $request): void
    {
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('karyawan', function ($kq) use ($search) {
                    $kq->where('nama_lengkap', 'like', "%{$search}%")
                       ->orWhere('nik', 'like', "%{$search}%");
                })
                ->orWhereHas('company', function ($cq) use ($search) {
                    $cq->where('company_name', 'like', "%{$search}%");
                })
                ->orWhere('periode', 'like', "%{$search}%");
            });
        }
    }

    // ========================================
    // DATATABLE PENDING
    // ========================================
    public function datatablePending(Request $request)
    {
        try {
            $query = $this->baseQuery($request)->where('is_released', 0);

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->filter(function ($query) use ($request) {
                    $this->applySearch($query, $request);
                })
                ->addColumn('checkbox', function ($row) {
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input row-checkbox" type="checkbox" value="' . $row->id . '" />
                            </div>';
                })
                ->addColumn('action', function ($p) {
                    return '
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-icon btn-light-danger btn-delete"
                                    data-id="' . $p->id . '" data-periode="' . $p->periode . '">
                                <i class="ki-outline ki-trash fs-5"></i>
                            </button>
                        </div>';
                })
                ->rawColumns(['checkbox', 'action'])
                ->addColumn('karyawan_nama',  fn($p) => $p->karyawan?->nama_lengkap ?? '-')
                ->addColumn('karyawan_nik',   fn($p) => $p->karyawan?->nik ?? '-')
                ->addColumn('company_nama',   fn($p) => $p->company?->company_name ?? '-')
                ->make(true);

        } catch (\Exception $e) {
            Log::error('DataTables Pending Error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load data'], 500);
        }
    }

    // ========================================
    // DATATABLE RELEASED
    // ========================================
    public function datatableReleased(Request $request)
    {
        try {
            $query = $this->baseQuery($request)
                ->where('is_released', 1)
                ->where('is_released_slip', 0);

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->filter(function ($query) use ($request) {
                    $this->applySearch($query, $request);
                })
                ->addColumn('checkbox', function ($row) {
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input row-checkbox-released" type="checkbox" value="' . $row->id . '" />
                            </div>';
                })
                ->addColumn('action', function ($p) {
                    return '
                        <div class="d-flex gap-2">
                            <a href="' . route('payrolls.show', $p->id) . '" class="btn btn-sm btn-icon btn-light-info" title="Detail">
                                <i class="ki-outline ki-eye fs-5"></i>
                            </a>
                            <a href="' . route('payrolls.download-pdf', $p->id) . '" class="btn btn-sm btn-icon btn-light-success" title="Download PDF">
                                <i class="ki-outline ki-file-down fs-5"></i>
                            </a>
                        </div>';
                })
                ->rawColumns(['checkbox', 'action'])
                ->addColumn('karyawan_nama',  fn($p) => $p->karyawan?->nama_lengkap ?? '-')
                ->addColumn('karyawan_nik',   fn($p) => $p->karyawan?->nik ?? '-')
                ->addColumn('company_nama',   fn($p) => $p->company?->company_name ?? '-')
                ->make(true);

        } catch (\Exception $e) {
            Log::error('DataTables Released Error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load data'], 500);
        }
    }

    // ========================================
    // DATATABLE RELEASED SLIP
    // ========================================
    public function datatableReleasedSlip(Request $request)
    {
        try {
            $query = $this->baseQuery($request)
                ->where('is_released', 1)
                ->where('is_released_slip', 1);

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->filter(function ($query) use ($request) {
                    $this->applySearch($query, $request);
                })
                ->addColumn('checkbox', function ($row) {
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input row-checkbox-released-slip" type="checkbox" value="' . $row->id . '" />
                            </div>';
                })

                ->addColumn('action', function ($p) {
                    return '
                        <div class="d-flex gap-2">
                            <a href="' . route('payrolls.show', $p->id) . '" class="btn btn-sm btn-icon btn-light-info" title="Detail">
                                <i class="ki-outline ki-eye fs-5"></i>
                            </a>
                            <a href="' . route('payrolls.download-pdf', $p->id) . '" class="btn btn-sm btn-icon btn-light-success" title="Download PDF">
                                <i class="ki-outline ki-file-down fs-5"></i>
                            </a>
                        </div>';
                })
                ->rawColumns(['action','checkbox'])
                ->addColumn('karyawan_nama',  fn($p) => $p->karyawan?->nama_lengkap ?? '-')
                ->addColumn('karyawan_nik',   fn($p) => $p->karyawan?->nik ?? '-')
                ->addColumn('company_nama',   fn($p) => $p->company?->company_name ?? '-')
                ->make(true);

        } catch (\Exception $e) {
            Log::error('DataTables Released Slip Error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load data'], 500);
        }
    }

    // ========================================
    // RELEASE (batch)
    // ========================================
    public function releaseData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids'          => 'required|array|min:1',
            'ids.*'        => 'exists:payrolls,id',
            'release_slip' => 'required|in:0,1,true,false',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $ids         = $request->ids;
            $releaseSlip = filter_var($request->release_slip, FILTER_VALIDATE_BOOLEAN);

            // ✅ Pastikan user hanya bisa release payroll dari company yang di-assign
            $companyIds = $this->getAssignedCompanyIds();
            
            // Lock rows + filter by assigned companies
            $payrolls = Payroll::whereIn('id', $ids)
                ->whereIn('company_id', $companyIds)
                ->lockForUpdate()
                ->get();

            if ($payrolls->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false, 
                    'message' => 'Tidak ada data yang bisa dirilis atau data tidak sesuai dengan akses Anda'
                ], 403);
            }

            $dataToUpdate = ['is_released' => true];
            if ($releaseSlip) {
                $dataToUpdate['is_released_slip'] = true;
            }

            $updated = Payroll::whereIn('id', $payrolls->pluck('id'))->update($dataToUpdate);

            DB::commit();

            $message = "{$updated} payroll berhasil dirilis";
            if ($releaseSlip) {
                $message .= ' dan slip gajinya';
            }

            return response()->json([
                'success'        => true,
                'message'        => $message,
                'updated_count'  => $updated,
                'released_slip'  => $releaseSlip,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Release Payroll Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ========================================
    // STATISTICS
    // ========================================
    public function getStatistics(Request $request)
    {
        try {
            $periode = $request->get('periode');
            
            // ✅ Filter by assigned companies
            $companyIds = $this->getAssignedCompanyIds();

            $query = DB::table('payrolls');
            
            if (!empty($companyIds)) {
                $query->whereIn('company_id', $companyIds);
            } else {
                // User ga punya company, return 0
                return response()->json([
                    'success' => true,
                    'periode' => $periode,
                    'data'    => [
                        'pending'       => ['count' => 0],
                        'released'      => ['count' => 0],
                        'released_slip' => ['count' => 0],
                    ],
                ]);
            }

            if ($periode) {
                if (strlen($periode) === 7) {
                    $query->where('periode', $periode);
                } elseif (strlen($periode) === 4) {
                    $query->where('periode', 'like', $periode . '%');
                }
            }

            $stats = $query->selectRaw('
                SUM(CASE WHEN is_released = 0 THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN is_released = 1 AND is_released_slip = 0 THEN 1 ELSE 0 END) as released,
                SUM(CASE WHEN is_released = 1 AND is_released_slip = 1 THEN 1 ELSE 0 END) as released_slip
            ')->first();

            return response()->json([
                'success' => true,
                'periode' => $periode,
                'data'    => [
                    'pending'      => ['count' => (int)($stats->pending ?? 0)],
                    'released'     => ['count' => (int)($stats->released ?? 0)],
                    'released_slip'=> ['count' => (int)($stats->released_slip ?? 0)],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Get Statistics Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ========================================
    // EXPORT
    // ========================================
    public function export(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        try {
            $periode   = $request->input('periode');
            $companyId = $request->input('company_id');
            $status    = $request->input('status', 'pending');

            // ✅ Validasi: company_id harus dalam assigned companies
            $assignedCompanyIds = $this->getAssignedCompanyIds();
            
            if ($companyId && !in_array($companyId, $assignedCompanyIds)) {
                return back()->with('error', 'Anda tidak memiliki akses ke company tersebut');
            }

            [$isReleased, $isReleasedSlip] = match ($status) {
                'pending'       => [0, null],
                'released'      => [1, 0],
                'released_slip' => [1, 1],
                default         => [null, null],
            };

            $filename = 'payroll_' . $status;
            if ($periode) $filename .= '_' . $periode;
            $filename .= '_' . date('YmdHis') . '.xlsx';

            // Pass assigned company IDs ke export class
            return Excel::download(
                new PayrollsExport($periode, $companyId, $isReleased, $isReleasedSlip, $assignedCompanyIds),
                $filename
            );

        } catch (\Throwable $e) {
            Log::error('Export Payroll Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Gagal export data: ' . $e->getMessage());
        }
    }
    
    public function create(Request $request)
    {
        $karyawanId = $request->get('karyawan_id');
        $karyawan = null;
        
        if ($karyawanId) {
            $karyawan = Karyawan::where('absen_karyawan_id', $karyawanId)
                ->where('status_resign', false)
                ->first();
        }
        
        $karyawans = Karyawan::where('status_resign', false)
            ->select('absen_karyawan_id', 'nik', 'nama_lengkap')
            ->orderBy('nama_lengkap')
            ->get()
            ->map(function($k) {
                return [
                    'id' => $k->absen_karyawan_id,
                    'nik' => $k->nik ?? '-',
                    'nama_lengkap' => $k->nama_lengkap
                ];
            })
            ->toArray();
        
        $companies = Company::select('absen_company_id', 'company_name', 'code')
            ->orderBy('company_name')
            ->get()
            ->map(function($c) {
                return [
                    'id' => $c->absen_company_id,
                    'company_name' => $c->company_name,
                    'code' => $c->code ?? '-'
                ];
            })
            ->toArray();
        
        return view('dashboard.dashboard-admin.payrolls.create', compact('karyawan', 'karyawans', 'companies'));
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'periode' => 'required|string',
                'karyawan_id' => 'required|integer',
                'company_id' => 'nullable|integer',
                'gaji_pokok' => 'nullable|integer',
                'monthly_kpi' => 'nullable|integer',
                'overtime' => 'nullable|integer',
                'medical_reimbursement' => 'nullable|integer',
                'insentif_sholat' => 'nullable|integer',
                'monthly_bonus' => 'nullable|integer',
                'rapel' => 'nullable|integer',
                'tunjangan_pulsa' => 'nullable|integer',
                'tunjangan_kehadiran' => 'nullable|integer',
                'tunjangan_transport' => 'nullable|integer',
                'tunjangan_lainnya' => 'nullable|integer',
                'yearly_bonus' => 'nullable|integer',
                'thr' => 'nullable|integer',
                'other' => 'nullable|integer',
                'ca_corporate' => 'nullable|integer',
                'ca_personal' => 'nullable|integer',
                'ca_kehadiran' => 'nullable|integer',
                'pph_21' => 'nullable|integer',
                'pph_21_deduction' => 'nullable|integer',
                'bpjs_tenaga_kerja' => 'nullable|integer',
                'bpjs_kesehatan' => 'nullable|integer',
                'bpjs_tk_jht_3_7_percent' => 'nullable|integer',
                'bpjs_tk_jht_2_percent' => 'nullable|integer',
                'bpjs_tk_jkk_0_24_percent' => 'nullable|integer',
                'bpjs_tk_jkm_0_3_percent' => 'nullable|integer',
                'bpjs_tk_jp_2_percent' => 'nullable|integer',
                'bpjs_tk_jp_1_percent' => 'nullable|integer',
                'bpjs_kes_4_percent' => 'nullable|integer',
                'bpjs_kes_1_percent' => 'nullable|integer',
                'salary_type' => 'nullable|in:gross,nett',
                'glh' => 'nullable|integer',
                'lm' => 'nullable|integer',
                'lainnya' => 'nullable|integer',
                'is_released' => 'nullable|boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            
            $karyawan = Karyawan::where('absen_karyawan_id', $request->karyawan_id)
                ->where('status_resign', false)
                ->first();
            
            if (!$karyawan) {
                return response()->json(['success' => false, 'message' => 'Karyawan tidak ditemukan'], 404);
            }
            
            if ($request->filled('company_id')) {
                $company = Company::where('absen_company_id', $request->company_id)->first();
                if (!$company) {
                    return response()->json(['success' => false, 'message' => 'Company tidak ditemukan'], 404);
                }
            }
            
            $exists = Payroll::where('periode', $request->periode)
                ->where('karyawan_id', $request->karyawan_id)
                ->exists();
            
            if ($exists) {
                return response()->json(['success' => false, 'message' => 'Payroll sudah ada'], 409);
            }
            
            $payroll = Payroll::create($request->all());
            
            return response()->json(['success' => true, 'message' => 'Payroll berhasil dibuat', 'data' => $payroll], 201);
            
        } catch (\Exception $e) {
            Log::error('Error creating payroll', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $payroll = PayrollCalculation::with(['karyawan', 'company'])->findOrFail($id);
            $karyawan = $payroll->karyawan;
            $company = $payroll->company;
            
            return view('dashboard.dashboard-admin.payrolls.show', compact('payroll', 'karyawan', 'company'));
            
        } catch (\Exception $e) {
            abort(404, 'Payroll tidak ditemukan');
        }
    }

    public function edit($id)
    {
        try {
            $payroll = Payroll::findOrFail($id);
            
            $karyawan = null;
            if ($payroll->karyawan_id) {
                $karyawan = Karyawan::where('absen_karyawan_id', $payroll->karyawan_id)->first();
            }
            
            $company = null;
            if ($payroll->company_id) {
                $company = Company::where('absen_company_id', $payroll->company_id)->first();
            }
            
            $karyawans = Karyawan::where('status_resign', false)
                ->select('absen_karyawan_id', 'nik', 'nama_lengkap')
                ->orderBy('nama_lengkap')
                ->get()
                ->map(function($k) {
                    return [
                        'id' => $k->absen_karyawan_id,
                        'nik' => $k->nik ?? '-',
                        'nama_lengkap' => $k->nama_lengkap
                    ];
                })
                ->toArray();
            
            $companies = Company::select('absen_company_id', 'company_name', 'code')
                ->orderBy('company_name')
                ->get()
                ->map(function($c) {
                    return [
                        'id' => $c->absen_company_id,
                        'company_name' => $c->company_name,
                        'code' => $c->code ?? '-'
                    ];
                })
                ->toArray();
            
            return view('dashboard.dashboard-admin.payrolls.edit', compact('payroll', 'karyawan', 'company', 'karyawans', 'companies'));
            
        } catch (\Exception $e) {
            return redirect()->route('payrolls.index')->with('error', 'Payroll tidak ditemukan');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $payroll = Payroll::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'periode' => 'required|string',
                'karyawan_id' => 'required|integer',
                'company_id' => 'nullable|integer',
                // ... semua validasi sama dengan store ...
            ]);
            
            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            
            $payroll->update($request->all());
            
            return response()->json(['success' => true, 'message' => 'Payroll berhasil diupdate', 'data' => $payroll]);
            
        } catch (\Exception $e) {
            Log::error('Error updating payroll', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $payroll = Payroll::findOrFail($id);
            
            if ($payroll->is_released) {
                return response()->json(['success' => false, 'message' => 'Tidak dapat menghapus payroll yang sudah dirilis'], 403);
            }
            
            $payroll->delete();
            
            return response()->json(['success' => true, 'message' => 'Payroll berhasil dihapus']);
            
        } catch (\Exception $e) {
            Log::error('Error deleting payroll', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function summary($periode)
    {
        try {
            $payrolls = PayrollCalculation::where('periode', $periode)->get();
            
            if ($payrolls->isEmpty()) {
                abort(404, 'Tidak ada data untuk periode ini');
            }
            
            $summary = [
                'periode' => $periode,
                'total_karyawan' => $payrolls->count(),
                'total_gaji_pokok' => $payrolls->sum('gaji_pokok'),
                'total_salary' => $payrolls->sum('salary'),
                'total_penerimaan' => $payrolls->sum('total_penerimaan'),
                'total_potongan' => abs($payrolls->sum('total_potongan')),
                'total_gaji_bersih' => $payrolls->sum('gaji_bersih'),
            ];
            
            return view('dashboard.dashboard-admin.payrolls.summary', compact('summary', 'payrolls', 'periode'));
            
        } catch (\Exception $e) {
            abort(500, 'Error loading summary: ' . $e->getMessage());
        }
    }

            public function downloadPdf($id)
    {
        try {
            // Load payroll dengan relasi
            $payroll = PayrollCalculation::with([
                'karyawan', 
                'company'
            ])->findOrFail($id);
            
            // ✅ Security check
            $companyIds = $this->getAssignedCompanyIds();
            if (!in_array($payroll->company_id, $companyIds)) {
                abort(403, 'Unauthorized access');
            }
            
            // Get karyawan detail (udah ada dari relasi)
            $karyawan = Karyawan::where('absen_karyawan_id', $payroll->karyawan_id)->first();
            
            // Get PTKP berdasarkan tahun periode payroll
            $periodeYear = date('Y', strtotime($payroll->periode . '-01'));
            $ptkp = null;
            
            if ($karyawan) {
                $ptkpHistory = \App\Models\KaryawanPtkpHistory::where('absen_karyawan_id', $karyawan->absen_karyawan_id)
                    ->where('tahun', $periodeYear)
                    ->orderBy('tahun', 'desc')
                    ->first();
                    
                if ($ptkpHistory) {
                    $ptkp = \App\Models\ListPtkp::where('absen_ptkp_id', $ptkpHistory->absen_ptkp_id)->first();
                }
            }
            
            // Convert payroll ke array untuk konsistensi dengan template
            $payrollData = [
                'periode' => $payroll->periode,
                'gaji_pokok' => $payroll->gaji_pokok ?? 0,
                'monthly_kpi' => $payroll->monthly_kpi ?? 0,
                'overtime' => $payroll->overtime ?? 0,
                'medical_reimbursement' => $payroll->medical_reimbursement ?? 0,
                'insentif_sholat' => $payroll->insentif_sholat ?? 0,
                'monthly_bonus' => $payroll->monthly_bonus ?? 0,
                'rapel' => $payroll->rapel ?? 0,
                'tunjangan_pulsa' => $payroll->tunjangan_pulsa ?? 0,
                'tunjangan_kehadiran' => $payroll->tunjangan_kehadiran ?? 0,
                'tunjangan_transport' => $payroll->tunjangan_transport ?? 0,
                'tunjangan_lainnya' => $payroll->tunjangan_lainnya ?? 0,
                'yearly_bonus' => $payroll->yearly_bonus ?? 0,
                'thr' => $payroll->thr ?? 0,
                'other' => $payroll->other ?? 0,
                'ca_corporate' => $payroll->ca_corporate ?? 0,
                'ca_personal' => $payroll->ca_personal ?? 0,
                'ca_kehadiran' => $payroll->ca_kehadiran ?? 0,
                'pph_21_deduction' => $payroll->pph_21_deduction ?? 0,
                'tunjangan' => $payroll->tunjangan ?? 0,
                'total_penerimaan' => $payroll->total_penerimaan ?? 0,
                'total_potongan' => $payroll->total_potongan ?? 0,
                'gaji_bersih' => $payroll->gaji_bersih ?? 0,
                'bpjs_tenaga_kerja_perusahaan_income' => $payroll->bpjs_tenaga_kerja_perusahaan_income ?? 0,
                'bpjs_tenaga_kerja_pegawai_income' => $payroll->bpjs_tenaga_kerja_pegawai_income ?? 0,
                'bpjs_kesehatan_perusahaan_income' => $payroll->bpjs_kesehatan_perusahaan_income ?? 0,
                'bpjs_kesehatan_pegawai_income' => $payroll->bpjs_kesehatan_pegawai_income ?? 0,
                'bpjs_tenaga_kerja_perusahaan_deduction' => $payroll->bpjs_tenaga_kerja_perusahaan_deduction ?? 0,
                'bpjs_tenaga_kerja_pegawai_deduction' => $payroll->bpjs_tenaga_kerja_pegawai_deduction ?? 0,
                'bpjs_kesehatan_perusahaan_deduction' => $payroll->bpjs_kesehatan_perusahaan_deduction ?? 0,
                'bpjs_kesehatan_pegawai_deduction' => $payroll->bpjs_kesehatan_pegawai_deduction ?? 0,
                'karyawan' => [
                    'nik' => $karyawan?->nik ?? '-',
                    'nama_lengkap' => $karyawan?->nama_lengkap ?? '-',
                    'area' => 'HO', // Default, bisa disesuaikan jika ada field-nya
                    'department' => 'OFFICE', // Default, bisa disesuaikan jika ada field-nya
                ],
                'company' => [
                    'company_name' => $payroll->company?->company_name ?? '-',
                    'logo' => $this->getImageBase64($payroll->company?->logo, 'logos'),
                    'ttd' => $this->getImageBase64($payroll->company?->ttd, 'ttd'), // ✅ akan jadi ttds
                    'nama_ttd' => $payroll->company?->nama_ttd ?? '-',
                    'jabatan_ttd' => $payroll->company?->jabatan_ttd ?? '-',
                ]
            ];
            
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                'dashboard.dashboard-admin.payrolls.pdf-slip', 
                [
                    'payroll' => $payrollData,
                    'karyawan' => $karyawan,
                    'ptkp' => $ptkp,
                    'jabatanPerusahaan' => (object)['nama_jabatan_terbaru' => '-'] // Placeholder
                ]
            );
            
            $filename = 'slip-gaji-' . ($karyawan?->nik ?? 'unknown') . '-' . $payroll->periode . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            Log::error('Download PDF Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Gagal download PDF: ' . $e->getMessage());
        }
    }

    /**
 * Convert remote image to base64 for PDF
 */
private function getImageBase64($filename, $folder = 'logos')
{
    if (empty($filename)) {
        return null;
    }
    
    try {
        // Mapping folder names (singular -> plural)
        $folderMap = [
            'logo' => 'logos',
            'logos' => 'logos',
            'ttd' => 'ttds',
            'ttds' => 'ttds',
        ];
        
        $folderPath = $folderMap[$folder] ?? $folder;
        
        $url = str_starts_with($filename, 'http') 
            ? $filename 
            : "https://haadhir.id/storage/{$folderPath}/" . basename($filename);
        
        \Log::info("Fetching image from: {$url}");
        
        // Fetch image dengan error handling
        $context = stream_context_create([
            'http' => [
                'timeout' => 10, // 10 second timeout
                'ignore_errors' => true
            ]
        ]);
        
        $imageContent = @file_get_contents($url, false, $context);
        
        if ($imageContent === false) {
            \Log::warning("Failed to fetch image: {$url}");
            return null;
        }
        
        // Get mime type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageContent);
        
        // Convert to base64
        $base64 = base64_encode($imageContent);
        
        \Log::info("Image converted successfully", [
            'url' => $url,
            'mime' => $mimeType,
            'size' => strlen($imageContent)
        ]);
        
        return "data:{$mimeType};base64,{$base64}";
        
    } catch (\Exception $e) {
        \Log::error("Image conversion error", [
            'error' => $e->getMessage(),
            'url' => $url ?? 'unknown',
            'trace' => $e->getTraceAsString()
        ]);
        return null;
    }
}

   public function downloadPdfZip(Request $request)
{
    $validator = Validator::make($request->all(), [
        'ids'   => 'required|array|min:1|max:20',
        'ids.*' => 'integer|exists:payrolls,id',
    ]);

    if ($validator->fails()) {
        return back()->with('error', 'Pilih data valid (max 20).');
    }

    $ids = $request->ids;

    // ✅ Security: hanya company yang di-assign
    $companyIds = $this->getAssignedCompanyIds();

    // Ambil data payroll (pakai PayrollCalculation biar view slip lengkap)
    $payrolls = PayrollCalculation::with(['karyawan', 'company'])
        ->whereIn('id', $ids)
        ->whereIn('company_id', $companyIds)
        ->get();

    if ($payrolls->count() !== count($ids)) {
        abort(403, 'Unauthorized access');
    }

    // Temp folder
    $tmpDir = storage_path('app/tmp-slip-' . Str::uuid());
    File::makeDirectory($tmpDir, 0755, true);

    $zipName = 'slip-gaji-selected-' . date('YmdHis') . '.zip';
    $zipPath = storage_path('app/' . $zipName);

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        File::deleteDirectory($tmpDir);
        return back()->with('error', 'Gagal membuat ZIP');
    }

    try {
        foreach ($payrolls as $p) {

            // ambil karyawan & company (nik kosong jadi ga dipakai)
            $karyawan = Karyawan::where('absen_karyawan_id', $p->karyawan_id)->first();

            // PTKP (copy logic dari downloadPdf)
            $periodeYear = date('Y', strtotime($p->periode . '-01'));
            $ptkp = null;

            if ($karyawan) {
                $ptkpHistory = \App\Models\KaryawanPtkpHistory::where('absen_karyawan_id', $karyawan->absen_karyawan_id)
                    ->where('tahun', $periodeYear)
                    ->orderBy('tahun', 'desc')
                    ->first();

                if ($ptkpHistory) {
                    $ptkp = \App\Models\ListPtkp::where('absen_ptkp_id', $ptkpHistory->absen_ptkp_id)->first();
                }
            }

            // siapkan data sama seperti downloadPdf()
            $payrollData = [
                'periode' => $p->periode,
                'gaji_pokok' => $p->gaji_pokok ?? 0,
                'monthly_kpi' => $p->monthly_kpi ?? 0,
                'overtime' => $p->overtime ?? 0,
                'medical_reimbursement' => $p->medical_reimbursement ?? 0,
                'insentif_sholat' => $p->insentif_sholat ?? 0,
                'monthly_bonus' => $p->monthly_bonus ?? 0,
                'rapel' => $p->rapel ?? 0,
                'tunjangan_pulsa' => $p->tunjangan_pulsa ?? 0,
                'tunjangan_kehadiran' => $p->tunjangan_kehadiran ?? 0,
                'tunjangan_transport' => $p->tunjangan_transport ?? 0,
                'tunjangan_lainnya' => $p->tunjangan_lainnya ?? 0,
                'yearly_bonus' => $p->yearly_bonus ?? 0,
                'thr' => $p->thr ?? 0,
                'other' => $p->other ?? 0,
                'ca_corporate' => $p->ca_corporate ?? 0,
                'ca_personal' => $p->ca_personal ?? 0,
                'ca_kehadiran' => $p->ca_kehadiran ?? 0,
                'pph_21_deduction' => $p->pph_21_deduction ?? 0,
                'tunjangan' => $p->tunjangan ?? 0,
                'total_penerimaan' => $p->total_penerimaan ?? 0,
                'total_potongan' => $p->total_potongan ?? 0,
                'gaji_bersih' => $p->gaji_bersih ?? 0,

                'karyawan' => [
                    'nik' => '-', // NIK kosong, jangan dipakai
                    'nama_lengkap' => $karyawan?->nama_lengkap ?? '-',
                    'area' => 'HO',
                    'department' => 'OFFICE',
                ],
                'company' => [
                    'company_name' => $p->company?->company_name ?? '-',
                    'logo' => $p->company?->logo ?? null,
                    'ttd' => $p->company?->ttd ?? null,
                    'nama_ttd' => $p->company?->nama_ttd ?? '-',
                    'jabatan_ttd' => $p->company?->jabatan_ttd ?? '-',
                ]
            ];

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                'dashboard.dashboard-admin.payrolls.pdf-slip',
                [
                    'payroll' => $payrollData,
                    'karyawan' => $karyawan,
                    'ptkp' => $ptkp,
                    'jabatanPerusahaan' => (object)['nama_jabatan_terbaru' => '-']
                ]
            );

            // ============================
            // ✅ Filename pakai nama + company + periode (aman untuk Windows)
            // ============================
            $periode = str_replace('-', '', $p->periode);

            $nama = $karyawan?->nama_lengkap ?? 'unknown';
            $company = $p->company?->company_name ?? 'company';

            $namaSafe = preg_replace('/[^A-Za-z0-9\-\s]/', '', $nama);
            $namaSafe = preg_replace('/\s+/', ' ', trim($namaSafe));
            $namaSafe = str_replace(' ', '_', $namaSafe);
            $namaSafe = substr($namaSafe, 0, 40);

            $companySafe = preg_replace('/[^A-Za-z0-9\-\s]/', '', $company);
            $companySafe = preg_replace('/\s+/', ' ', trim($companySafe));
            $companySafe = str_replace(' ', '_', $companySafe);
            $companySafe = substr($companySafe, 0, 20);

            $filename = "slip-{$periode}-{$companySafe}-{$namaSafe}-{$p->id}.pdf";

            $filePath = $tmpDir . DIRECTORY_SEPARATOR . $filename;

            file_put_contents($filePath, $pdf->output());

            $zip->addFile($filePath, $filename);
        }

        $zip->close();

        // bersihin temp pdf
        File::deleteDirectory($tmpDir);

        return response()->download($zipPath)->deleteFileAfterSend(true);

    } catch (\Throwable $e) {
        $zip->close();
        File::deleteDirectory($tmpDir);
        if (file_exists($zipPath)) @unlink($zipPath);

        Log::error('Download PDF ZIP Error', ['error' => $e->getMessage()]);
        return back()->with('error', 'Gagal download ZIP: ' . $e->getMessage());
    }
}


    
}