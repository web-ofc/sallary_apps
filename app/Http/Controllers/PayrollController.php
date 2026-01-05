<?php
// app/Http/Controllers/PayrollController.php - FIXED VERSION (NO API CALLS IN DATATABLE)

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Payroll;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use App\Exports\PayrollsExport;
use App\Models\PayrollCalculation;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        // Count pending payrolls
        $pendingCount = PayrollCalculation::where('is_released', 0)->count();
        
        return view('dashboard.dashboard-admin.payrolls.index', compact('pendingCount'));
    }
    
    /**
     * DataTables untuk PENDING (status = 0)
     */
        public function datatablePending(Request $request)
    {
        try {
            // 🔥 OPTIMASI 1: Select only needed columns + eager load dengan select specific
            $query = PayrollCalculation::query()
                ->select([
                    'id', 'karyawan_id', 'company_id', 'periode', 'salary_type',
                    'gaji_pokok', 'salary', 'total_penerimaan', 'total_potongan', 'gaji_bersih',
                    'monthly_kpi', 'overtime', 'medical_reimbursement', 'insentif_sholat', 
                    'monthly_bonus', 'rapel',
                    'tunjangan_pulsa', 'tunjangan_kehadiran', 'tunjangan_transport', 'tunjangan_lainnya',
                    'yearly_bonus', 'thr', 'other',
                    'ca_corporate', 'ca_personal', 'ca_kehadiran', 'pph_21', 'pph_21_deduction',
                    'bpjs_tenaga_kerja', 'bpjs_tk_jht_3_7_percent', 'bpjs_tk_jht_2_percent',
                    'bpjs_tk_jkk_0_24_percent', 'bpjs_tk_jkm_0_3_percent', 'bpjs_tk_jp_2_percent', 
                    'bpjs_tk_jp_1_percent',
                    'bpjs_kesehatan', 'bpjs_kes_4_percent', 'bpjs_kes_1_percent',
                    'is_released', 'glh','lm','lainnya'
                ])
                ->with([
                    'karyawan:absen_karyawan_id,nik,nama_lengkap',
                    'company:absen_company_id,company_name'
                ])
                ->where('is_released', 0);
            
            // Filter by periode
            if ($request->filled('periode')) {
                $query->where('periode', $request->periode);
            }
            
            // Filter by company_id
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
            
            // Filter by karyawan_id
            if ($request->filled('karyawan_id')) {
                $query->where('karyawan_id', $request->karyawan_id);
            }
            
            return DataTables::eloquent($query) // 🔥 OPTIMASI 2: Gunakan ::eloquent() bukan ::of()
                ->addIndexColumn()
                
                // 🔥 OPTIMASI 3: Filter search dipindah ke sini (lebih efficient)
                ->filter(function ($query) use ($request) {
                    if ($search = $request->input('search.value')) {
                        $query->where(function($q) use ($search) {
                            $q->whereHas('karyawan', function($kQuery) use ($search) {
                                $kQuery->where('nama_lengkap', 'like', "%{$search}%")
                                    ->orWhere('nik', 'like', "%{$search}%");
                            })
                            ->orWhereHas('company', function($cQuery) use ($search) {
                                $cQuery->where('company_name', 'like', "%{$search}%");
                            })
                            ->orWhere('periode', 'like', "%{$search}%")
                            ->orWhere('salary_type', 'like', "%{$search}%");
                        });
                    }
                })
                
                // Checkbox untuk batch release
                ->addColumn('checkbox', function($row) {
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                            <input class="form-check-input row-checkbox" type="checkbox" value="'.$row->id.'" />
                        </div>';
                })
                
                // 🔥 OPTIMASI 4: Direct access ke relationship (sudah eager loaded)
                ->addColumn('karyawan_nama', fn($p) => $p->karyawan?->nama_lengkap ?? '-')
                ->addColumn('karyawan_nik', fn($p) => $p->karyawan?->nik ?? '-')
                ->addColumn('company_nama', fn($p) => $p->company?->company_name ?? '-')
                
                // 🔥 OPTIMASI 5: Format currency di frontend (lebih cepat)
                ->addColumn('gaji_pokok_formatted', fn($p) => $p->gaji_pokok ?? 0)
                
                // All numeric columns - return raw number
                ->editColumn('monthly_kpi', fn($p) => $p->monthly_kpi ?? 0)
                ->editColumn('overtime', fn($p) => $p->overtime ?? 0)
                ->editColumn('medical_reimbursement', fn($p) => $p->medical_reimbursement ?? 0)
                ->editColumn('insentif_sholat', fn($p) => $p->insentif_sholat ?? 0)
                ->editColumn('monthly_bonus', fn($p) => $p->monthly_bonus ?? 0)
                ->editColumn('rapel', fn($p) => $p->rapel ?? 0)
                ->editColumn('tunjangan_pulsa', fn($p) => $p->tunjangan_pulsa ?? 0)
                ->editColumn('tunjangan_kehadiran', fn($p) => $p->tunjangan_kehadiran ?? 0)
                ->editColumn('tunjangan_transport', fn($p) => $p->tunjangan_transport ?? 0)
                ->editColumn('tunjangan_lainnya', fn($p) => $p->tunjangan_lainnya ?? 0)
                ->editColumn('yearly_bonus', fn($p) => $p->yearly_bonus ?? 0)
                ->editColumn('thr', fn($p) => $p->thr ?? 0)
                ->editColumn('other', fn($p) => $p->other ?? 0)
                ->editColumn('ca_corporate', fn($p) => $p->ca_corporate ?? 0)
                ->editColumn('ca_personal', fn($p) => $p->ca_personal ?? 0)
                ->editColumn('ca_kehadiran', fn($p) => $p->ca_kehadiran ?? 0)
                ->editColumn('pph_21', fn($p) => $p->pph_21 ?? 0)
                ->editColumn('pph_21_deduction', fn($p) => $p->pph_21_deduction ?? 0)
                ->editColumn('bpjs_tenaga_kerja', fn($p) => $p->bpjs_tenaga_kerja ?? 0)
                ->editColumn('bpjs_tk_jht_3_7_percent', fn($p) => $p->bpjs_tk_jht_3_7_percent ?? 0)
                ->editColumn('bpjs_tk_jht_2_percent', fn($p) => $p->bpjs_tk_jht_2_percent ?? 0)
                ->editColumn('bpjs_tk_jkk_0_24_percent', fn($p) => $p->bpjs_tk_jkk_0_24_percent ?? 0)
                ->editColumn('bpjs_tk_jkm_0_3_percent', fn($p) => $p->bpjs_tk_jkm_0_3_percent ?? 0)
                ->editColumn('bpjs_tk_jp_2_percent', fn($p) => $p->bpjs_tk_jp_2_percent ?? 0)
                ->editColumn('bpjs_tk_jp_1_percent', fn($p) => $p->bpjs_tk_jp_1_percent ?? 0)
                ->editColumn('bpjs_kesehatan', fn($p) => $p->bpjs_kesehatan ?? 0)
                ->editColumn('bpjs_kes_4_percent', fn($p) => $p->bpjs_kes_4_percent ?? 0)
                ->editColumn('bpjs_kes_1_percent', fn($p) => $p->bpjs_kes_1_percent ?? 0)
                ->editColumn('glh', fn($p) => $p->glh ?? 0)
                ->editColumn('lm', fn($p) => $p->lm ?? 0)
                ->editColumn('lainnya', fn($p) => $p->lainnya ?? 0)
                
                // Summary - return raw number (format di frontend)
                ->addColumn('salary_formatted', fn($p) => $p->salary ?? 0)
                ->addColumn('total_penerimaan_formatted', fn($p) => $p->total_penerimaan ?? 0)
                ->addColumn('total_potongan_formatted', fn($p) => abs($p->total_potongan ?? 0))
                ->addColumn('gaji_bersih_formatted', fn($p) => $p->gaji_bersih ?? 0)
                
                // Action buttons
                ->addColumn('action', function ($payroll) {
                    $editUrl = route('payrolls.edit', $payroll->id);
                    
                    return '
                        <div class="d-flex gap-2">
                            <a href="' . $editUrl . '" class="btn btn-sm btn-icon btn-light-primary" title="Edit">
                                <i class="ki-outline ki-pencil fs-5"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-icon btn-light-danger btn-delete" 
                                    data-id="' . $payroll->id . '" 
                                    data-periode="' . $payroll->periode . '" 
                                    title="Delete">
                                <i class="ki-outline ki-trash fs-5"></i>
                            </button>
                        </div>
                    ';
                })
                
                ->rawColumns(['checkbox', 'action'])
                ->make(true);
                
        } catch (\Exception $e) {
            Log::error('DataTables Pending Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to load data: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * DataTables untuk RELEASED (status = 1)
     */
        public function datatableReleased(Request $request)
    {
        try {
            // 🔥 OPTIMASI 1: Select only needed columns
            $query = PayrollCalculation::query()
                ->select([
                    'id', 'karyawan_id', 'company_id', 'periode', 'salary_type',
                    'gaji_pokok', 'salary', 'total_penerimaan', 'total_potongan', 'gaji_bersih',
                    'monthly_kpi', 'overtime', 'medical_reimbursement', 'insentif_sholat', 
                    'monthly_bonus', 'rapel',
                    'tunjangan_pulsa', 'tunjangan_kehadiran', 'tunjangan_transport', 'tunjangan_lainnya',
                    'yearly_bonus', 'thr', 'other',
                    'ca_corporate', 'ca_personal', 'ca_kehadiran', 'pph_21', 'pph_21_deduction',
                    'bpjs_tenaga_kerja', 'bpjs_tk_jht_3_7_percent', 'bpjs_tk_jht_2_percent',
                    'bpjs_tk_jkk_0_24_percent', 'bpjs_tk_jkm_0_3_percent', 'bpjs_tk_jp_2_percent', 
                    'bpjs_tk_jp_1_percent',
                    'bpjs_kesehatan', 'bpjs_kes_4_percent', 'bpjs_kes_1_percent',
                    'is_released', 'glh','lm','lainnya'
                ])
                ->with([
                    'karyawan:absen_karyawan_id,nik,nama_lengkap',
                    'company:absen_company_id,company_name'
                ])
                ->where('is_released', 1);
            
            // Filter by periode
            if ($request->filled('periode')) {
                $query->where('periode', $request->periode);
            }
            
            // Filter by company_id
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
            
            // Filter by karyawan_id
            if ($request->filled('karyawan_id')) {
                $query->where('karyawan_id', $request->karyawan_id);
            }
            
            return DataTables::eloquent($query) // 🔥 OPTIMASI 2: Eloquent instead of of()
                ->addIndexColumn()
                
                ->filter(function ($query) use ($request) {
                    if ($search = $request->input('search.value')) {
                        $query->where(function($q) use ($search) {
                            $q->whereHas('karyawan', function($kQuery) use ($search) {
                                $kQuery->where('nama_lengkap', 'like', "%{$search}%")
                                    ->orWhere('nik', 'like', "%{$search}%");
                            })
                            ->orWhereHas('company', function($cQuery) use ($search) {
                                $cQuery->where('company_name', 'like', "%{$search}%");
                            })
                            ->orWhere('periode', 'like', "%{$search}%")
                            ->orWhere('salary_type', 'like', "%{$search}%");
                        });
                    }
                })
                
                ->addColumn('karyawan_nama', fn($p) => $p->karyawan?->nama_lengkap ?? '-')
                ->addColumn('karyawan_nik', fn($p) => $p->karyawan?->nik ?? '-')
                ->addColumn('company_nama', fn($p) => $p->company?->company_name ?? '-')
                
                // Return raw numbers (format di frontend)
                ->addColumn('gaji_pokok_formatted', fn($p) => $p->gaji_pokok ?? 0)
                
                // All numeric columns
                ->editColumn('monthly_kpi', fn($p) => $p->monthly_kpi ?? 0)
                ->editColumn('overtime', fn($p) => $p->overtime ?? 0)
                ->editColumn('medical_reimbursement', fn($p) => $p->medical_reimbursement ?? 0)
                ->editColumn('insentif_sholat', fn($p) => $p->insentif_sholat ?? 0)
                ->editColumn('monthly_bonus', fn($p) => $p->monthly_bonus ?? 0)
                ->editColumn('rapel', fn($p) => $p->rapel ?? 0)
                ->editColumn('tunjangan_pulsa', fn($p) => $p->tunjangan_pulsa ?? 0)
                ->editColumn('tunjangan_kehadiran', fn($p) => $p->tunjangan_kehadiran ?? 0)
                ->editColumn('tunjangan_transport', fn($p) => $p->tunjangan_transport ?? 0)
                ->editColumn('tunjangan_lainnya', fn($p) => $p->tunjangan_lainnya ?? 0)
                ->editColumn('yearly_bonus', fn($p) => $p->yearly_bonus ?? 0)
                ->editColumn('thr', fn($p) => $p->thr ?? 0)
                ->editColumn('other', fn($p) => $p->other ?? 0)
                ->editColumn('ca_corporate', fn($p) => $p->ca_corporate ?? 0)
                ->editColumn('ca_personal', fn($p) => $p->ca_personal ?? 0)
                ->editColumn('ca_kehadiran', fn($p) => $p->ca_kehadiran ?? 0)
                ->editColumn('pph_21', fn($p) => $p->pph_21 ?? 0)
                ->editColumn('pph_21_deduction', fn($p) => $p->pph_21_deduction ?? 0)
                ->editColumn('bpjs_tenaga_kerja', fn($p) => $p->bpjs_tenaga_kerja ?? 0)
                ->editColumn('bpjs_tk_jht_3_7_percent', fn($p) => $p->bpjs_tk_jht_3_7_percent ?? 0)
                ->editColumn('bpjs_tk_jht_2_percent', fn($p) => $p->bpjs_tk_jht_2_percent ?? 0)
                ->editColumn('bpjs_tk_jkk_0_24_percent', fn($p) => $p->bpjs_tk_jkk_0_24_percent ?? 0)
                ->editColumn('bpjs_tk_jkm_0_3_percent', fn($p) => $p->bpjs_tk_jkm_0_3_percent ?? 0)
                ->editColumn('bpjs_tk_jp_2_percent', fn($p) => $p->bpjs_tk_jp_2_percent ?? 0)
                ->editColumn('bpjs_tk_jp_1_percent', fn($p) => $p->bpjs_tk_jp_1_percent ?? 0)
                ->editColumn('bpjs_kesehatan', fn($p) => $p->bpjs_kesehatan ?? 0)
                ->editColumn('bpjs_kes_4_percent', fn($p) => $p->bpjs_kes_4_percent ?? 0)
                ->editColumn('bpjs_kes_1_percent', fn($p) => $p->bpjs_kes_1_percent ?? 0)
                ->editColumn('glh', fn($p) => $p->glh ?? 0)
                ->editColumn('lm', fn($p) => $p->lm ?? 0)
                ->editColumn('lainnya', fn($p) => $p->lainnya ?? 0)
                
                // Summary
                ->addColumn('salary_formatted', fn($p) => $p->salary ?? 0)
                ->addColumn('total_penerimaan_formatted', fn($p) => $p->total_penerimaan ?? 0)
                ->addColumn('total_potongan_formatted', fn($p) => abs($p->total_potongan ?? 0))
                ->addColumn('gaji_bersih_formatted', fn($p) => $p->gaji_bersih ?? 0)
                
                // Action buttons
                ->addColumn('action', function ($payroll) {
                    $showUrl = route('payrolls.show', $payroll->id);
                    
                    return '
                        <div class="d-flex gap-2">
                            <a href="' . $showUrl . '" class="btn btn-sm btn-icon btn-light-info" title="Detail">
                                <i class="ki-outline ki-eye fs-5"></i>
                            </a>
                        </div>
                    ';
                })
                
                ->rawColumns(['action'])
                ->make(true);
                
        } catch (\Exception $e) {
            Log::error('DataTables Released Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to load data: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Release selected payrolls (Batch Action)
     */
    public function releaseData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:payrolls,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updated = Payroll::whereIn('id', $request->ids)
                ->where('is_released', false)
                ->update(['is_released' => true]);

            return response()->json([
                'success' => true,
                'message' => "{$updated} payroll berhasil dirilis",
                'updated_count' => $updated
            ]);

        } catch (\Exception $e) {
            Log::error('Release Payroll Error', [
                'error' => $e->getMessage(),
                'ids' => $request->ids
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

        public function export(Request $request)
    {
        // Set max execution time untuk export besar
        set_time_limit(300); // 5 menit
        ini_set('memory_limit', '512M');

        try {
            $periode = $request->get('periode');
            $companyId = $request->get('company_id');
            $isReleased = $request->has('is_released') ? (bool) $request->get('is_released') : null;

            $filename = 'payroll_export_' . date('YmdHis') . '.xlsx';
            
            if ($periode) {
                $filename = 'payroll_' . $periode . '_' . date('YmdHis') . '.xlsx';
            }

            return Excel::download(
                new PayrollsExport($periode, $companyId, $isReleased),
                $filename
            );

        } catch (\Exception $e) {
            Log::error('Export Payroll Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Gagal export data: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     * GET /payrolls/create
     */
    public function create(Request $request)
    {
        $karyawanId = $request->get('karyawan_id'); // Ini absen_karyawan_id
        $karyawan = null;
        
        // ✅ Jika ada karyawan_id, ambil dari database lokal
        if ($karyawanId) {
            $karyawan = Karyawan::where('absen_karyawan_id', $karyawanId)
                ->where('status_resign', false)
                ->first();
        }
        
        // ✅ Get list karyawan aktif untuk dropdown (DARI DATABASE LOKAL)
        $karyawans = Karyawan::where('status_resign', false)
            ->select('absen_karyawan_id', 'nik', 'nama_lengkap')
            ->orderBy('nama_lengkap')
            ->get()
            ->map(function($k) {
                return [
                    'id' => $k->absen_karyawan_id, // Frontend pakai absen_id
                    'nik' => $k->nik ?? '-',
                    'nama_lengkap' => $k->nama_lengkap
                ];
            })
            ->toArray();
        
        // ✅ Get list companies untuk dropdown (DARI DATABASE LOKAL)
        $companies = Company::select('absen_company_id', 'company_name', 'code')
            ->orderBy('company_name')
            ->get()
            ->map(function($c) {
                return [
                    'id' => $c->absen_company_id, // Frontend pakai absen_id
                    'company_name' => $c->company_name,
                    'code' => $c->code ?? '-'
                ];
            })
            ->toArray();
        
        return view('dashboard.dashboard-admin.payrolls.create', compact('karyawan', 'karyawans', 'companies'));
    }

    /**
     * Store a newly created resource in storage.
     * POST /payrolls
     */
    public function store(Request $request)
    {
        try {
            // ✅ Validation - SEMUA KOLOM
            $validator = Validator::make($request->all(), [
                'periode' => 'required|string|',
                'karyawan_id' => 'required|integer', // Ini absen_karyawan_id
                'company_id' => 'nullable|integer', // Ini absen_company_id
                'gaji_pokok' => 'nullable|integer|',
                'monthly_kpi' => 'nullable|integer|',
                'overtime' => 'nullable|integer|',
                'medical_reimbursement' => 'nullable|integer|',
                'insentif_sholat' => 'nullable|integer|',
                'monthly_bonus' => 'nullable|integer|',
                'rapel' => 'nullable|integer|',
                'tunjangan_pulsa' => 'nullable|integer|',
                'tunjangan_kehadiran' => 'nullable|integer|',
                'tunjangan_transport' => 'nullable|integer|',
                'tunjangan_lainnya' => 'nullable|integer|',
                'yearly_bonus' => 'nullable|integer|',
                'thr' => 'nullable|integer|',
                'other' => 'nullable|integer|',
                'ca_corporate' => 'nullable|integer|',
                'ca_personal' => 'nullable|integer|',
                'ca_kehadiran' => 'nullable|integer|',
                'pph_21' => 'nullable|integer|',
                'pph_21_deduction' => 'nullable|integer|',
                'bpjs_tenaga_kerja' => 'nullable|integer|',
                'bpjs_kesehatan' => 'nullable|integer|',
                'bpjs_tk_jht_3_7_percent' => 'nullable|integer|',
                'bpjs_tk_jht_2_percent' => 'nullable|integer|',
                'bpjs_tk_jkk_0_24_percent' => 'nullable|integer|',
                'bpjs_tk_jkm_0_3_percent' => 'nullable|integer|',
                'bpjs_tk_jp_2_percent' => 'nullable|integer|',
                'bpjs_tk_jp_1_percent' => 'nullable|integer|',
                'bpjs_kes_4_percent' => 'nullable|integer|',
                'bpjs_kes_1_percent' => 'nullable|integer|',
                'salary_type' => 'nullable|in:gross,nett',
                'glh' => 'nullable|integer|',
                'lm' => 'nullable|integer|',
                'lainnya' => 'nullable|integer|',
                'is_released' => 'nullable|boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // ✅ Validasi karyawan exists dan belum resign
            $karyawan = Karyawan::where('absen_karyawan_id', $request->karyawan_id)
                ->where('status_resign', false)
                ->first();
            
            if (!$karyawan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Karyawan tidak ditemukan atau sudah resign'
                ], 404);
            }
            
            // ✅ Validasi company exists (jika diisi)
            if ($request->filled('company_id')) {
                $company = Company::where('absen_company_id', $request->company_id)->first();
                if (!$company) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Company tidak ditemukan'
                    ], 404);
                }
            }
            
            // ✅ Check duplicate
            $exists = Payroll::where('periode', $request->periode)
                ->where('karyawan_id', $request->karyawan_id)
                ->exists();
            
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payroll untuk karyawan ini di periode tersebut sudah ada'
                ], 409);
            }
            
            // ✅ Create payroll - karyawan_id dan company_id sudah absen_id
            $payroll = Payroll::create($request->all());
            
            Log::info('Payroll created', ['id' => $payroll->id, 'periode' => $payroll->periode]);
            
            return response()->json([
                'success' => true,
                'message' => 'Payroll berhasil dibuat',
                'data' => $payroll
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Error creating payroll', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /payrolls/{id}
     */
    public function show($id)
    {
        try {
            // ✅ PAKAI PayrollCalculation UNTUK SHOW DENGAN RELASI
            $payroll = PayrollCalculation::with(['karyawan', 'company'])->findOrFail($id);
            
            $karyawan = $payroll->karyawan;
            $company = $payroll->company;
            
            return view('dashboard.dashboard-admin.payrolls.show', compact('payroll', 'karyawan', 'company'));
            
        } catch (\Exception $e) {
            abort(404, 'Payroll tidak ditemukan');
        }
    }

    /**
     * Show the form for editing the specified resource.
     * GET /payrolls/{id}/edit
     */
    public function edit($id)
    {
        try {
            // ✅ PAKAI PAYROLL MODEL UNTUK EDIT (BUKAN VIEW)
            $payroll = Payroll::findOrFail($id);
            
            // ✅ Load karyawan yang dipilih (DARI DATABASE LOKAL)
            $karyawan = null;
            if ($payroll->karyawan_id) {
                $karyawan = Karyawan::where('absen_karyawan_id', $payroll->karyawan_id)->first();
            }
            
            // ✅ Load company yang dipilih (DARI DATABASE LOKAL)
            $company = null;
            if ($payroll->company_id) {
                $company = Company::where('absen_company_id', $payroll->company_id)->first();
            }
            
            // ✅ Pre-fill dropdown dengan yang dipilih + list lengkap
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
            return redirect()
                ->route('payrolls.index')
                ->with('error', 'Payroll tidak ditemukan');
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /payrolls/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            // ✅ PAKAI PAYROLL MODEL UNTUK UPDATE (BUKAN VIEW)
            $payroll = Payroll::findOrFail($id);
            
            // ✅ Validation - SEMUA KOLOM
            $validator = Validator::make($request->all(), [
                'periode' => 'required|string|',
                'karyawan_id' => 'required|integer',
                'company_id' => 'nullable|integer',
                'gaji_pokok' => 'nullable|integer|',
                'monthly_kpi' => 'nullable|integer|',
                'overtime' => 'nullable|integer|',
                'medical_reimbursement' => 'nullable|integer|',
                'insentif_sholat' => 'nullable|integer|',
                'monthly_bonus' => 'nullable|integer|',
                'rapel' => 'nullable|integer|',
                'tunjangan_pulsa' => 'nullable|integer|',
                'tunjangan_kehadiran' => 'nullable|integer|',
                'tunjangan_transport' => 'nullable|integer|',
                'tunjangan_lainnya' => 'nullable|integer|',
                'yearly_bonus' => 'nullable|integer|',
                'thr' => 'nullable|integer|',
                'other' => 'nullable|integer|',
                'ca_corporate' => 'nullable|integer|',
                'ca_personal' => 'nullable|integer|',
                'ca_kehadiran' => 'nullable|integer|',
                'pph_21' => 'nullable|integer|',
                'pph_21_deduction' => 'nullable|integer|',
                'bpjs_tenaga_kerja' => 'nullable|integer|',
                'bpjs_kesehatan' => 'nullable|integer|',
                'bpjs_tk_jht_3_7_percent' => 'nullable|integer|',
                'bpjs_tk_jht_2_percent' => 'nullable|integer|',
                'bpjs_tk_jkk_0_24_percent' => 'nullable|integer|',
                'bpjs_tk_jkm_0_3_percent' => 'nullable|integer|',
                'bpjs_tk_jp_2_percent' => 'nullable|integer|',
                'bpjs_tk_jp_1_percent' => 'nullable|integer|',
                'bpjs_kes_4_percent' => 'nullable|integer|',
                'bpjs_kes_1_percent' => 'nullable|integer|',
                'salary_type' => 'nullable|in:gross,nett',
                'glh' => 'nullable|integer|',
                'lm' => 'nullable|integer|',
                'lainnya' => 'nullable|integer|',
                'is_released' => 'nullable|boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // ✅ Validasi karyawan exists dan belum resign
            $karyawan = Karyawan::where('absen_karyawan_id', $request->karyawan_id)
                ->where('status_resign', false)
                ->first();
            
            if (!$karyawan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Karyawan tidak ditemukan atau sudah resign'
                ], 404);
            }
            
            // ✅ Validasi company exists (jika diisi)
            if ($request->filled('company_id')) {
                $company = Company::where('absen_company_id', $request->company_id)->first();
                if (!$company) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Company tidak ditemukan'
                    ], 404);
                }
            }
            
            // ✅ Check duplicate (kecuali diri sendiri)
            $exists = Payroll::where('periode', $request->periode)
                ->where('karyawan_id', $request->karyawan_id)
                ->where('id', '!=', $id)
                ->exists();
            
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payroll untuk karyawan ini di periode tersebut sudah ada'
                ], 409);
            }
            
            // ✅ Update payroll
            $payroll->update($request->all());
            
            Log::info('Payroll updated', ['id' => $payroll->id, 'periode' => $payroll->periode]);
            
            return response()->json([
                'success' => true,
                'message' => 'Payroll berhasil diupdate',
                'data' => $payroll
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating payroll', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error updating payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /payrolls/{id}
     */
    public function destroy($id)
    {
        try {
            // ✅ PAKAI PAYROLL MODEL UNTUK DELETE (BUKAN VIEW)
            $payroll = Payroll::findOrFail($id);
            
            // ✅ Cek apakah sudah released
            if ($payroll->is_released) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus payroll yang sudah di-release'
                ], 403);
            }
            
            $periode = $payroll->periode;
            $payroll->delete();
            
            Log::info('Payroll deleted', ['id' => $id, 'periode' => $periode]);
            
            return response()->json([
                'success' => true,
                'message' => 'Payroll berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error deleting payroll', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error deleting payroll: ' . $e->getMessage()
            ], 500);
        }
    }
    
    
    /**
     * Get summary by periode
     * GET /payrolls/summary/{periode}
     */
    public function summary($periode)
    {
        try {
            // ✅ PAKAI PayrollCalculation UNTUK SUMMARY (SUDAH ADA KALKULASINYA)
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
                'total_bpjs_tk_income' => $payrolls->sum('bpjs_tenaga_kerja_perusahaan_income') + $payrolls->sum('bpjs_tenaga_kerja_pegawai_income'),
                'total_bpjs_kes_income' => $payrolls->sum('bpjs_kesehatan_perusahaan_income') + $payrolls->sum('bpjs_kesehatan_pegawai_income'),
            ];
            
            return view('dashboard.dashboard-admin.payrolls.summary', compact('summary', 'payrolls', 'periode'));
            
        } catch (\Exception $e) {
            abort(500, 'Error loading summary: ' . $e->getMessage());
        }
    }
}