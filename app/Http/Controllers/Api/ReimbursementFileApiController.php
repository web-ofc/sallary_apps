<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReimbursementFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReimbursementFileApiController extends Controller
{
    /**
     * Get reimbursement files by karyawan (with pagination)
     * 
     * @param int $karyawan_id - Path parameter
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * Query Parameters:
     * - year (optional): filter by year (default: current year)
     * - per_page (optional): items per page (default: 15, max: 100)
     * - page (optional): page number
     * 
     * Example:
     * GET /api/reimbursement-files/by-karyawan/123?year=2025&per_page=20&page=1
     */
    // Payroll app - ReimbursementFileApiController.php

public function getByKaryawan($karyawan_id, Request $request)
{
    $validator = Validator::make(array_merge(
        ['karyawan_id' => $karyawan_id],
        $request->all()
    ), [
        'karyawan_id' => 'required|exists:karyawans,absen_karyawan_id',
        'year'        => 'nullable|integer|min:2000|max:2100',
        'per_page'    => 'nullable|integer|min:1|max:100',
        'page'        => 'nullable|integer|min:1',
        'search'      => 'nullable|string|max:255',
        'order_by'    => 'nullable|in:id,year,created_at,file',
        'order_dir'   => 'nullable|in:asc,desc',
    ], [
        'per_page.max' => 'Maksimal 100 data per halaman',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors'  => $validator->errors()
        ], 422);
    }

    try {
        $year     = $request->query('year') ?? now()->year;
        $perPage  = (int) ($request->query('per_page') ?? 15);
        $search   = $request->query('search');
        $orderBy  = $request->query('order_by', 'created_at');
        $orderDir = $request->query('order_dir', 'desc');

        $query = ReimbursementFile::query()
            ->select(['id','karyawan_id','year','file','created_at','updated_at'])
            ->where('karyawan_id', $karyawan_id)
            ->where('year', $year);

        // ✅ Search (filename / extension)
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('file', 'like', "%{$search}%");
            });
        }

        // ✅ Order
        $query->orderBy($orderBy, $orderDir);

        $files = $query->paginate($perPage);

        $transformedData = $files->getCollection()->map(function($file) {
            return [
                'id' => $file->id,
                'karyawan_id' => $file->karyawan_id,
                'year' => $file->year,
                'file_name' => basename($file->file),
                'file_url' => asset('storage/' . $file->file),
                'file_path' => $file->file,
                'extension' => strtolower(pathinfo($file->file, PATHINFO_EXTENSION)),
                'created_at' => $file->created_at->timezone('Asia/Jakarta')->toIso8601String(),
                'updated_at' => $file->updated_at->timezone('Asia/Jakarta')->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Data retrieved successfully',
            'data' => $transformedData,
            'meta' => [
                'current_page' => $files->currentPage(),
                'from' => $files->firstItem(),
                'last_page' => $files->lastPage(),
                'per_page' => $files->perPage(),
                'to' => $files->lastItem(),
                'total' => $files->total(),
                'year' => (string) $year,
            ],
            'links' => [
                'first' => $files->url(1),
                'last' => $files->url($files->lastPage()),
                'prev' => $files->previousPageUrl(),
                'next' => $files->nextPageUrl(),
            ]
        ], 200);

    } catch (\Exception $e) {
        \Log::error('Reimbursement API Error', [
            'karyawan_id' => $karyawan_id,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan saat mengambil data',
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Get file count summary by karyawan
     * 
     * @param int $karyawan_id - Path parameter
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * Query Parameters:
     * - year (optional): filter by year
     * 
     * Example:
     * GET /api/reimbursement-files/summary/123?year=2025
     */
    public function getSummary($karyawan_id, Request $request)
    {
        // Validation
        $validator = Validator::make(array_merge(
            ['karyawan_id' => $karyawan_id],
            $request->all()
        ), [
            'karyawan_id' => 'required|exists:karyawans,absen_karyawan_id',
            'year' => 'nullable|integer|min:2000|max:2100',
        ], [
            'karyawan_id.required' => 'Parameter karyawan_id wajib diisi',
            'karyawan_id.exists' => 'Karyawan tidak ditemukan',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $year = $request->year;

            // Build query
            $query = ReimbursementFile::where('karyawan_id', $karyawan_id);

            if ($year) {
                $query->where('year', $year);
            }

            // Get summary
            $totalFiles = $query->count();
            $filesByYear = ReimbursementFile::where('karyawan_id', $karyawan_id)
                ->selectRaw('year, COUNT(*) as count')
                ->groupBy('year')
                ->orderBy('year', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Summary retrieved successfully',
                'data' => [
                    'karyawan_id' => $karyawan_id,
                    'total_files' => $totalFiles,
                    'files_by_year' => $filesByYear->map(function($item) {
                        return [
                            'year' => $item->year,
                            'count' => $item->count
                        ];
                    })
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available years for a karyawan
     * 
     * @param int $karyawan_id - Path parameter
     * @return \Illuminate\Http\JsonResponse
     * 
     * Example:
     * GET /api/reimbursement-files/years/123
     */
    public function getAvailableYears($karyawan_id)
    {
        // Validation
        $validator = Validator::make(['karyawan_id' => $karyawan_id], [
            'karyawan_id' => 'required|exists:karyawans,absen_karyawan_id',
        ], [
            'karyawan_id.required' => 'Parameter karyawan_id wajib diisi',
            'karyawan_id.exists' => 'Karyawan tidak ditemukan',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $years = ReimbursementFile::where('karyawan_id', $karyawan_id)
                ->selectRaw('DISTINCT year')
                ->orderBy('year', 'desc')
                ->pluck('year');

            return response()->json([
                'success' => true,
                'message' => 'Available years retrieved successfully',
                'data' => [
                    'karyawan_id' => $karyawan_id,
                    'years' => $years
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data tahun',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}