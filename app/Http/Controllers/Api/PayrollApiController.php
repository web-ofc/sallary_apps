<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollCalculation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollApiController extends Controller
{
    /**
     * GET /api/payrolls
     * List semua payroll calculations dengan filter & pagination
     * ✅ ONLY SHOW RELEASED PAYROLLS
     */
    public function index(Request $request)
    {
        try {
            $query = PayrollCalculation::where('is_released', true); // ✅ FILTER RELEASED
            
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
            
            // Search by nama karyawan (need join)
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
            $query->orderBy($sortBy, $sortDir);
            
            // Pagination
            $perPage = $request->get('per_page', 15);
            $payrolls = $query->with([
                'karyawan:id,absen_karyawan_id,nama_lengkap,nik', 
                'company:id,absen_company_id,company_name,code,logo,ttd'
            ])->paginate($perPage);

            
            return response()->json([
                'success' => true,
                'message' => 'Data payroll calculations berhasil diambil',
                'data' => $payrolls
            ]);
            
        } catch (\Exception $e) {
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
            $query = DB::table('payroll_calculations')
                      ->select('periode')
                      ->where('is_released', true) // ✅ FILTER RELEASED
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
            
            $exists = PayrollCalculation::where('periode', $request->periode)
                                        ->where('karyawan_id', $request->karyawan_id)
                                        ->where('is_released', true) // ✅ FILTER RELEASED
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
            $summary = PayrollCalculation::where('periode', $periode)
                ->where('is_released', true) // ✅ FILTER RELEASED
                ->select([
                    DB::raw('COUNT(*) as total_karyawan'),
                    DB::raw('SUM(total_penerimaan) as total_penerimaan'),
                    DB::raw('SUM(total_potongan) as total_potongan'),
                    DB::raw('SUM(gaji_bersih) as gaji_bersih')
                ])
                ->first();
            
            // Get breakdown by company
            $byCompany = PayrollCalculation::where('periode', $periode)
                ->where('is_released', true) // ✅ FILTER RELEASED
                ->select([
                    'company_id',
                    DB::raw('COUNT(*) as total_karyawan'),
                    DB::raw('SUM(total_penerimaan) as total_penerimaan'),
                    DB::raw('SUM(total_potongan) as total_potongan'),
                    DB::raw('SUM(gaji_bersih) as gaji_bersih')
                ])
                ->with('company:id,absen_company_id,company_name')
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
            $query = PayrollCalculation::where('periode', $periode)
                                       ->where('is_released', true); // ✅ FILTER RELEASED
            
            // Filter by company_id if needed
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
            
            // Sorting
            $sortBy = $request->get('sort_by', 'gaji_bersih');
            $sortDir = $request->get('sort_dir', 'desc');
            $query->orderBy($sortBy, $sortDir);
            
            // Pagination or all
            if ($request->boolean('all')) {
                $payrolls = $query->with([
                    'karyawan:id,absen_karyawan_id,nama_lengkap,nik', 
                    'company:id,absen_company_id,company_name,logo,ttd'
                ])->get();
            } else {
                $perPage = $request->get('per_page', 15);
                $payrolls = $query->with([
                    'karyawan:id,absen_karyawan_id,nama_lengkap,nik', 
                    'company:id,absen_company_id,company_name,logo,ttd'
                ])->paginate($perPage);
            }
            
            return response()->json([
                'success' => true,
                'message' => "Data payroll periode {$periode} berhasil diambil",
                'data' => $payrolls
            ]);
            
        } catch (\Exception $e) {
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
            $query = PayrollCalculation::where('karyawan_id', $karyawan_id)
                                       ->where('is_released', true); // ✅ FILTER RELEASED
            
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
                'karyawan:id,absen_karyawan_id,nama_lengkap,nik', 
                'company:id,absen_company_id,company_name,logo,ttd'
            ])->get();
            
            // ✅ ALWAYS return array, not pagination object
            return response()->json([
                'success' => true,
                'message' => "Data payroll karyawan ID {$karyawan_id} berhasil diambil",
                'data' => $payrolls->toArray(),
                'total' => $payrolls->count()
            ]);
            
        } catch (\Exception $e) {
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
            $payroll = PayrollCalculation::where('is_released', true) // ✅ FILTER RELEASED
                ->with([
                    'karyawan:id,absen_karyawan_id,nama_lengkap,nik,email_pribadi,telp_pribadi',
                    'company:id,absen_company_id,company_name,code,logo,ttd'
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
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }
}