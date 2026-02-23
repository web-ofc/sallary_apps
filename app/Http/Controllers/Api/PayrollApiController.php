<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollCalculationsMerge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollApiController extends Controller
{
    /**
     * GET /api/payrolls
     * List semua payroll calculations dengan filter & pagination
     * ✅ ONLY SHOW RELEASED PAYROLLS (via VIEW)
     */
    public function index(Request $request)
    {
        try {
            // ✅ FIX: Pakai query() builder, bukan all()
            $query = PayrollCalculationsMerge::query();
            
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
            
            // Search by nama karyawan
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('karyawan', function($q) use ($search) {
                    $q->where('nama_lengkap', 'like', "%{$search}%")
                      ->orWhere('nik', 'like', "%{$search}%");
                });
            }
            
            // Sorting
            $sortBy = $request->get('sort_by', 'periode');
            $sortDir = $request->get('sort_dir', 'desc');
            
            // ✅ Validasi sort_by untuk prevent SQL injection
            $allowedSort = ['periode', 'gaji_bersih', 'total_penerimaan', 'total_potongan', 'karyawan_id', 'company_id'];
            if (!in_array($sortBy, $allowedSort)) {
                $sortBy = 'periode';
            }
            
            $query->orderBy($sortBy, $sortDir);
            
            // Pagination
            $perPage = min($request->get('per_page', 15), 100); // ✅ Max 100
            $payrolls = $query->with([
                'karyawan:absen_karyawan_id,nama_lengkap,nik', 
                'company:absen_company_id,company_name,code,logo,ttd,nama_ttd,jabatan_ttd',
            ])->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Data payroll calculations berhasil diambil',
                'data' => $payrolls
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Payroll API Index Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/payrolls/periodes
     * List semua periode yang tersedia
     * ✅ ONLY SHOW PERIODES WITH RELEASED PAYROLLS
     */
    public function getPeriodes(Request $request)
    {
        try {
            // ✅ FIX: Pakai VIEW merge
            $query = DB::table('payroll_calculations_merge')
                      ->select('periode')
                      ->distinct()
                      ->orderBy('periode', 'desc');
            
            // Filter by company_id if needed
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
            
            $periodes = $query->pluck('periode');
            
            return response()->json([
                'success' => true,
                'message' => 'List periode berhasil diambil',
                'data' => $periodes
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Payroll API Get Periodes Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/payrolls/check
     * Check apakah payroll exists untuk periode & karyawan tertentu
     * ✅ ONLY CHECK RELEASED PAYROLLS
     */
    public function checkExists(Request $request)
    {
        try {
            $request->validate([
                'periode' => 'required|string',
                'karyawan_id' => 'required|integer'
            ]);
            
            $exists = PayrollCalculationsMerge::where('periode', $request->periode)
                                        ->where('karyawan_id', $request->karyawan_id)
                                        ->exists();
            
            return response()->json([
                'success' => true,
                'message' => 'Check berhasil',
                'data' => [
                    'exists' => $exists,
                    'periode' => $request->periode,
                    'karyawan_id' => $request->karyawan_id
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Payroll API Check Exists Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan pengecekan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/payrolls/summary/{periode}
     * Summary payroll by periode (total penerimaan, potongan, gaji bersih)
     * ✅ ONLY SUMMARY FOR RELEASED PAYROLLS
     */
    public function summaryByPeriode($periode)
    {
        try {
            // ✅ FIX: Hapus redundant filter is_released (VIEW udah filter)
            $summary = PayrollCalculationsMerge::where('periode', $periode)
                ->select([
                    DB::raw('COUNT(*) as total_karyawan'),
                    DB::raw('SUM(total_penerimaan) as total_penerimaan'),
                    DB::raw('SUM(total_potongan) as total_potongan'),
                    DB::raw('SUM(gaji_bersih) as gaji_bersih')
                ])
                ->first();
            
            // Get breakdown by company
            $byCompany = PayrollCalculationsMerge::where('periode', $periode)
                ->select([
                    'company_id',
                    DB::raw('COUNT(*) as total_karyawan'),
                    DB::raw('SUM(total_penerimaan) as total_penerimaan'),
                    DB::raw('SUM(total_potongan) as total_potongan'),
                    DB::raw('SUM(gaji_bersih) as gaji_bersih')
                ])
                ->with('company:absen_company_id,company_name')
                ->groupBy('company_id')
                ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Summary periode berhasil diambil',
                'data' => [
                    'periode' => $periode,
                    'summary' => $summary,
                    'by_company' => $byCompany
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Payroll API Summary Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil summary: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/payrolls/by-periode/{periode}
     * Get all payrolls by periode
     * ✅ ONLY SHOW RELEASED PAYROLLS
     */
    public function byPeriode(Request $request, $periode)
    {
        try {
            $query = PayrollCalculationsMerge::where('periode', $periode);
            
            // Filter by company_id if needed
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
            
            // Sorting
            $sortBy = $request->get('sort_by', 'gaji_bersih');
            $sortDir = $request->get('sort_dir', 'desc');
            
            // ✅ Validasi sort column
            $allowedSort = ['gaji_bersih', 'total_penerimaan', 'total_potongan', 'karyawan_id'];
            if (!in_array($sortBy, $allowedSort)) {
                $sortBy = 'gaji_bersih';
            }
            
            $query->orderBy($sortBy, $sortDir);
            
            // Pagination or all
            if ($request->boolean('all')) {
                // ✅ Limit untuk prevent memory issue
                $payrolls = $query->with([
                    'karyawan:absen_karyawan_id,nama_lengkap,nik', 
                    'company:absen_company_id,company_name,logo,ttd,nama_ttd,jabatan_ttd'
                ])->limit(1000)->get();
            } else {
                $perPage = min($request->get('per_page', 15), 100);
                $payrolls = $query->with([
                    'karyawan:absen_karyawan_id,nama_lengkap,nik', 
                    'company:absen_company_id,company_name,logo,ttd,nama_ttd,jabatan_ttd'
                ])->paginate($perPage);
            }
            
            return response()->json([
                'success' => true,
                'message' => "Data payroll periode {$periode} berhasil diambil",
                'data' => $payrolls
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Payroll API By Periode Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/payrolls/by-karyawan/{karyawan_id}
     * Get all payrolls by karyawan_id (history)
     * ✅ ONLY SHOW RELEASED PAYROLLS
     */
    public function byKaryawan(Request $request, $karyawan_id)
    {
        try {
            $query = PayrollCalculationsMerge::where('karyawan_id', $karyawan_id);
            
            // Filter by periode range
            if ($request->filled('start_periode')) {
                $query->where('periode', '>=', $request->start_periode);
            }
            if ($request->filled('end_periode')) {
                $query->where('periode', '<=', $request->end_periode);
            }
            
            // Sorting
            $sortBy = $request->get('sort_by', 'periode');
            $sortDir = $request->get('sort_dir', 'desc');
            $query->orderBy($sortBy, $sortDir);
            
            $payrolls = $query->with([
                'karyawan:absen_karyawan_id,nama_lengkap,nik', 
                'company:absen_company_id,company_name,logo,ttd,nama_ttd,jabatan_ttd'
            ])->get();
            
            return response()->json([
                'success' => true,
                'message' => "Data payroll karyawan ID {$karyawan_id} berhasil diambil",
                'data' => $payrolls,
                'total' => $payrolls->count()
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Payroll API By Karyawan Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

        public function showBySource($source, $id)
    {
        try {
            $allowed = ['payrolls', 'payrolls_fakes'];
            if (!in_array($source, $allowed, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid source_table'
                ], 422);
            }

            $payroll = PayrollCalculationsMerge::with([
                    'karyawan:absen_karyawan_id,nama_lengkap,nik,email_pribadi,telp_pribadi',
                    'company:absen_company_id,company_name,code,logo,ttd,nama_ttd,jabatan_ttd'
                ])
                ->where('source_table', $source)
                ->where('id', $id)
                ->first();

            if (!$payroll) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payroll tidak ditemukan atau belum dirilis'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail payroll berhasil diambil',
                'data' => $payroll
            ]);

        } catch (\Exception $e) {
            \Log::error('Payroll API ShowBySource Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    
    /**
     * GET /api/payrolls/{id}
     * Show detail payroll calculation by ID
     * ✅ ONLY SHOW IF RELEASED
     */
    public function show($id)
    {
        try {
            $payroll = PayrollCalculationsMerge::with([
                    'karyawan:absen_karyawan_id,nama_lengkap,nik,email_pribadi,telp_pribadi',
                    'company:absen_company_id,company_name,code,logo,ttd,nama_ttd,jabatan_ttd'
                ])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Detail payroll berhasil diambil',
                'data' => $payroll
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll tidak ditemukan atau belum dirilis'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Payroll API Show Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }
}