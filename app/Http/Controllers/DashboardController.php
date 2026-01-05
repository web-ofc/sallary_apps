<?php

namespace App\Http\Controllers;

use App\Models\PayrollCalculation;
use App\Services\AttendanceApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $attendanceApi;

    public function __construct(AttendanceApiService $attendanceApi)
    {
        $this->middleware('auth');
        $this->attendanceApi = $attendanceApi;
    }

    public function adminDashboard()
    {
        if (!in_array(Auth::user()->role, ['admin'])) {
            abort(403, 'Unauthorized action.');
        }

        return view('dashboard.dashboard-admin.dashboard', [
            'title' => 'Dashboard Admin',
        ]);
    }

    /**
     * Get dashboard data via AJAX
     */
    public function getDashboardData(Request $request)
    {
        try {
            $periode = $request->input('periode');
            
            if (!$periode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Periode is required'
                ], 400);
            }

            // Get payroll data from view
            $payrolls = PayrollCalculation::where('periode', $periode)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($payrolls->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No data found for this periode'
                ]);
            }

            // Get unique karyawan_id and company_id
            $karyawanIds = $payrolls->pluck('karyawan_id')->unique()->values()->toArray();
            $companyIds = $payrolls->pluck('company_id')->unique()->values()->toArray();

            // Fetch karyawan and company data from API
            $karyawanData = $this->attendanceApi->getBulkKaryawan($karyawanIds);
            $companyData = $this->attendanceApi->getBulkCompanies($companyIds);

            // Map data
            $karyawanMap = collect($karyawanData['data'] ?? [])->keyBy('id');
            $companyMap = collect($companyData['data'] ?? [])->keyBy('id');

            // Enhance payroll data with karyawan and company info
            $enhancedPayrolls = $payrolls->map(function ($payroll) use ($karyawanMap, $companyMap) {
                $karyawan = $karyawanMap->get($payroll->karyawan_id);
                $company = $companyMap->get($payroll->company_id);

                return [
                    'id' => $payroll->id,
                    'periode' => $payroll->periode,
                    'karyawan_id' => $payroll->karyawan_id,
                    'company_id' => $payroll->company_id,
                    'gaji_pokok' => $payroll->gaji_pokok,
                    'monthly_kpi' => $payroll->monthly_kpi,
                    'overtime' => $payroll->overtime,
                    'medical_reimbursement' => $payroll->medical_reimbursement,
                    'insentif_sholat' => $payroll->insentif_sholat,
                    'monthly_bonus' => $payroll->monthly_bonus,
                    'rapel' => $payroll->rapel,
                    'tunjangan_pulsa' => $payroll->tunjangan_pulsa,
                    'tunjangan_kehadiran' => $payroll->tunjangan_kehadiran,
                    'tunjangan_transport' => $payroll->tunjangan_transport,
                    'tunjangan_lainnya' => $payroll->tunjangan_lainnya,
                    'yearly_bonus' => $payroll->yearly_bonus,
                    'thr' => $payroll->thr,
                    'other' => $payroll->other,
                    'ca_corporate' => $payroll->ca_corporate,
                    'ca_personal' => $payroll->ca_personal,
                    'ca_kehadiran' => $payroll->ca_kehadiran,
                    'pph_21' => $payroll->pph_21,
                    'pph_21_deduction' => $payroll->pph_21_deduction,
                    'bpjs_tenaga_kerja' => $payroll->bpjs_tenaga_kerja,
                    'bpjs_kesehatan' => $payroll->bpjs_kesehatan,
                    'is_released' => $payroll->is_released,
                    'salary' => $payroll->salary,
                    'total_penerimaan' => $payroll->total_penerimaan,
                    'total_potongan' => $payroll->total_potongan,
                    'gaji_bersih' => $payroll->gaji_bersih,
                    'bpjs_tenaga_kerja_perusahaan_income' => $payroll->bpjs_tenaga_kerja_perusahaan_income,
                    'bpjs_tenaga_kerja_pegawai_income' => $payroll->bpjs_tenaga_kerja_pegawai_income,
                    'bpjs_kesehatan_perusahaan_income' => $payroll->bpjs_kesehatan_perusahaan_income,
                    'bpjs_kesehatan_pegawai_income' => $payroll->bpjs_kesehatan_pegawai_income,
                    'karyawan' => $karyawan ? [
                        'id' => $karyawan['id'],
                        'nik' => $karyawan['nik'] ?? '-',
                        'nama_lengkap' => $karyawan['nama_lengkap'] ?? 'Unknown',
                    ] : null,
                    'company' => $company ? [
                        'id' => $company['id'],
                        'company_name' => $company['company_name'] ?? 'Unknown',
                        'company_code' => $company['company_code'] ?? '-',
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $enhancedPayrolls
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available periodes
     */
    public function getPeriodes()
    {
        try {
            $periodes = PayrollCalculation::select('periode')
                ->distinct()
                ->orderBy('periode', 'desc')
                ->pluck('periode');

            return response()->json([
                'success' => true,
                'data' => $periodes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading periodes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getStatistics(Request $request)
    {
        try {
            $periode = $request->input('periode');
            
            if (!$periode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Periode is required'
                ], 400);
            }

            $stats = PayrollCalculation::where('periode', $periode)
                ->select(
                    DB::raw('COUNT(*) as total_payroll'),
                    DB::raw('SUM(CASE WHEN is_released = 1 THEN 1 ELSE 0 END) as released_count'),
                    DB::raw('SUM(CASE WHEN is_released = 0 THEN 1 ELSE 0 END) as draft_count'),
                    DB::raw('SUM(total_penerimaan) as total_income'),
                    DB::raw('ABS(SUM(total_potongan)) as total_deduction'),
                    DB::raw('SUM(gaji_bersih) as total_disbursed'),
                    DB::raw('SUM(bpjs_tenaga_kerja_perusahaan_income + bpjs_tenaga_kerja_pegawai_income + bpjs_kesehatan_perusahaan_income + bpjs_kesehatan_pegawai_income) as total_bpjs')
                )
                ->first();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}