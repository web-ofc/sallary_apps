<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PayrollsFake;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use App\Exports\PayrollsFakeExport;
use App\Models\PayrollCalculationFake;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class PayrollsFakeController extends Controller
{
    public function index(Request $request)
    {
        // 🔥 OPTIMASI: Count semua status sekali jalan
        $counts = DB::table('payrolls_fakes')
            ->selectRaw('
                SUM(CASE WHEN is_released = 0 THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN is_released = 1 AND is_released_slip = 0 THEN 1 ELSE 0 END) as released_count,
                SUM(CASE WHEN is_released = 1 AND is_released_slip = 1 THEN 1 ELSE 0 END) as released_slip_count
            ')
            ->first();
        
        return view('dashboard.dashboard-admin.payrolls-fake.index', [
            'pendingCount' => $counts->pending_count ?? 0,
            'releasedCount' => $counts->released_count ?? 0,
            'releasedSlipCount' => $counts->released_slip_count ?? 0,
        ]);
    }
    
    /**
     * 🔥 DataTables PENDING - Optimized POST
     */
    public function datatablePending(Request $request)
    {
        try {
            $query = PayrollCalculationFake::query()
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
                    'is_released', 'glh','lm','lainnya','tunjangan'
                ])
                ->with([
                    'karyawan:absen_karyawan_id,nik,nama_lengkap',
                    'company:absen_company_id,company_name'
                ])
                ->where('is_released', 0);
            
            if ($request->filled('periode')) {
                $query->where('periode', $request->periode);
            }
            
            return DataTables::eloquent($query)
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
                            ->orWhere('periode', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('checkbox', function($row) {
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                            <input class="form-check-input row-checkbox" type="checkbox" value="'.$row->id.'" />
                        </div>';
                })
                ->addColumn('karyawan_nama', fn($p) => $p->karyawan?->nama_lengkap ?? '-')
                ->addColumn('karyawan_nik', fn($p) => $p->karyawan?->nik ?? '-')
                ->addColumn('company_nama', fn($p) => $p->company?->company_name ?? '-')
                ->addColumn('gaji_pokok_formatted', fn($p) => $p->gaji_pokok ?? 0)
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
                ->editColumn('tunjangan', fn($p) => $p->tunjangan ?? 0)
                ->addColumn('salary_formatted', fn($p) => $p->salary ?? 0)
                ->addColumn('total_penerimaan_formatted', fn($p) => $p->total_penerimaan ?? 0)
                ->addColumn('total_potongan_formatted', fn($p) => abs($p->total_potongan ?? 0))
                ->addColumn('gaji_bersih_formatted', fn($p) => $p->gaji_bersih ?? 0)
                ->addColumn('action', function ($payroll) {
                    return '
                        <div class="d-flex gap-2">
                            <a href="' . route('payrolls-fake.edit', $payroll->id) . '" class="btn btn-sm btn-icon btn-light-primary">
                                <i class="ki-outline ki-pencil fs-5"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-icon btn-light-danger btn-delete" 
                                    data-id="' . $payroll->id . '" data-periode="' . $payroll->periode . '">
                                <i class="ki-outline ki-trash fs-5"></i>
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['checkbox', 'action'])
                ->make(true);
                
        } catch (\Exception $e) {
            Log::error('DataTables Pending Error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load data'], 500);
        }
    }
    
    /**
     * 🔥 DataTables RELEASED - Optimized POST (is_released=1, is_released_slip=0)
     */
    public function datatableReleased(Request $request)
    {
        try {
            $query = PayrollCalculationFake::query()
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
                    'is_released', 'is_released_slip', 'glh','lm','lainnya','tunjangan'
                ])
                ->with([
                    'karyawan:absen_karyawan_id,nik,nama_lengkap',
                    'company:absen_company_id,company_name'
                ])
                ->where('is_released', 1)
                ->where('is_released_slip', 0);
            
            if ($request->filled('periode')) {
                $query->where('periode', $request->periode);
            }
            
            return DataTables::eloquent($query)
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
                            ->orWhere('periode', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('checkbox', function($row) {
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                            <input class="form-check-input row-checkbox-released" type="checkbox" value="'.$row->id.'" />
                        </div>';
                })
                ->addColumn('karyawan_nama', fn($p) => $p->karyawan?->nama_lengkap ?? '-')
                ->addColumn('karyawan_nik', fn($p) => $p->karyawan?->nik ?? '-')
                ->addColumn('company_nama', fn($p) => $p->company?->company_name ?? '-')
                ->addColumn('gaji_pokok_formatted', fn($p) => $p->gaji_pokok ?? 0)
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
                ->editColumn('tunjangan', fn($p) => $p->tunjangan ?? 0)
                ->addColumn('salary_formatted', fn($p) => $p->salary ?? 0)
                ->addColumn('total_penerimaan_formatted', fn($p) => $p->total_penerimaan ?? 0)
                ->addColumn('total_potongan_formatted', fn($p) => abs($p->total_potongan ?? 0))
                ->addColumn('gaji_bersih_formatted', fn($p) => $p->gaji_bersih ?? 0)
                ->addColumn('action', function ($payroll) {
                    return '
                        <div class="d-flex gap-2">
                            <a href="' . route('payrolls-fake.show', $payroll->id) . '" class="btn btn-sm btn-icon btn-light-info">
                                <i class="ki-outline ki-eye fs-5"></i>
                            </a>
                        </div>
                    ';
                })
                ->rawColumns(['checkbox', 'action'])
                ->make(true);
                
        } catch (\Exception $e) {
            Log::error('DataTables Released Error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load data'], 500);
        }
    }
    
    /**
     * 🔥 DataTables RELEASED SLIP - Optimized POST (is_released=1, is_released_slip=1)
     */
    public function datatableReleasedSlip(Request $request)
    {
        try {
            $query = PayrollCalculationFake::query()
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
                    'is_released', 'is_released_slip', 'glh','lm','lainnya','tunjangan'
                ])
                ->with([
                    'karyawan:absen_karyawan_id,nik,nama_lengkap',
                    'company:absen_company_id,company_name'
                ])
                ->where('is_released', 1)
                ->where('is_released_slip', 1);
            
            if ($request->filled('periode')) {
                $query->where('periode', $request->periode);
            }
            
            return DataTables::eloquent($query)
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
                            ->orWhere('periode', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('karyawan_nama', fn($p) => $p->karyawan?->nama_lengkap ?? '-')
                ->addColumn('karyawan_nik', fn($p) => $p->karyawan?->nik ?? '-')
                ->addColumn('company_nama', fn($p) => $p->company?->company_name ?? '-')
                ->addColumn('gaji_pokok_formatted', fn($p) => $p->gaji_pokok ?? 0)
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
                ->editColumn('tunjangan', fn($p) => $p->tunjangan ?? 0)
                ->addColumn('salary_formatted', fn($p) => $p->salary ?? 0)
                ->addColumn('total_penerimaan_formatted', fn($p) => $p->total_penerimaan ?? 0)
                ->addColumn('total_potongan_formatted', fn($p) => abs($p->total_potongan ?? 0))
                ->addColumn('gaji_bersih_formatted', fn($p) => $p->gaji_bersih ?? 0)
                ->addColumn('action', function ($payroll) {
                    return '
                        <div class="d-flex gap-2">
                            <a href="' . route('payrolls-fake.show', $payroll->id) . '" class="btn btn-sm btn-icon btn-light-info">
                                <i class="ki-outline ki-eye fs-5"></i>
                            </a>
                        </div>
                    ';
                })
                ->rawColumns(['action'])
                ->make(true);
                
        } catch (\Exception $e) {
            Log::error('DataTables Released Slip Error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load data'], 500);
        }
    }
    
    /**
     * 🔥 Release payroll - Support multi-stage release
     */
    public function releaseData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:payrolls_fakes,id',
            'release_slip' => 'required|in:0,1,true,false'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        
        try {
            $payrolls = PayrollsFake::whereIn('id', $request->ids)->lockForUpdate()->get();
            
            $releaseSlip = filter_var($request->release_slip, FILTER_VALIDATE_BOOLEAN);
            
            $dataToUpdate = ['is_released' => true];
            if ($releaseSlip) {
                $dataToUpdate['is_released_slip'] = true;
            }

            $updated = PayrollsFake::whereIn('id', $payrolls->pluck('id'))->update($dataToUpdate);

            DB::commit();

            $message = $updated . ' payroll berhasil dirilis';
            if ($releaseSlip) {
                $message .= ' dan slip gajinya';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'updated_count' => $updated,
                'released_slip' => $releaseSlip
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Release Payroll Fake Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getStatistics(Request $request)
    {
        try {
            $periode = $request->get('periode');
            
            $query = PayrollCalculationFake::query();
            
            if ($periode) {
                if (strlen($periode) === 7) {
                    $query->where('periode', $periode);
                } else if (strlen($periode) === 4) {
                    $query->where('periode', 'like', $periode . '%');
                }
            }
            
            $pendingCount = (clone $query)->where('is_released', 0)->count();
            $releasedCount = (clone $query)->where('is_released', 1)->where('is_released_slip', 0)->count();
            $releasedSlipCount = (clone $query)->where('is_released', 1)->where('is_released_slip', 1)->count();
            
            return response()->json([
                'success' => true,
                'periode' => $periode,
                'data' => [
                    'pending' => ['count' => $pendingCount],
                    'released' => ['count' => $releasedCount],
                    'released_slip' => ['count' => $releasedSlipCount]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get Statistics Fake Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function export(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        try {
            $periode = $request->get('periode');
            $companyId = $request->get('company_id');
            $status = $request->get('status', 'pending'); // pending, released, released_slip

            // Tentukan is_released dan is_released_slip berdasarkan status
            $isReleased = null;
            $isReleasedSlip = null;
            
            if ($status === 'pending') {
                $isReleased = 0;
            } elseif ($status === 'released') {
                $isReleased = 1;
                $isReleasedSlip = 0;
            } elseif ($status === 'released_slip') {
                $isReleased = 1;
                $isReleasedSlip = 1;
            }

            $filename = 'payroll_fake_' . $status . '_' . date('YmdHis') . '.xlsx';
            
            if ($periode) {
                $filename = 'payroll_fake_' . $status . '_' . $periode . '_' . date('YmdHis') . '.xlsx';
            }

            return Excel::download(
                new PayrollsFakeExport($periode, $companyId, $isReleased, $isReleasedSlip),
                $filename
            );

        } catch (\Exception $e) {
            Log::error('Export Payroll Fake Error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal export data: ' . $e->getMessage());
        }
    }

    public function summary($periode)
    {
        try {
            $payrolls = PayrollCalculationFake::where('periode', $periode)->get();
            
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
            
            return view('dashboard.dashboard-admin.payrolls-fake.summary', compact('summary', 'payrolls', 'periode'));
            
        } catch (\Exception $e) {
            abort(500, 'Error loading summary: ' . $e->getMessage());
        }
    }
}