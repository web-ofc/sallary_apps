<?php
// app/Http/Controllers/KaryawanViewController.php (UPDATED)

namespace App\Http\Controllers;

use App\Services\AttendanceApiService;
use Illuminate\Http\Request;

class KaryawanViewController extends Controller
{
    protected $attendanceApi;
    
    public function __construct(AttendanceApiService $attendanceApi)
    {
        $this->attendanceApi = $attendanceApi;
    }
    
    /**
     * ✅ GET KARYAWAN WITH PAGINATION + SEARCH
     * GET /api/karyawan?page=1&per_page=50&search=john
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 50);
        $search = $request->get('search', '');
        $useCache = !$request->has('refresh');
        
        // Jika ada search query, gunakan search endpoint
        if (!empty($search) && strlen($search) >= 2) {
            $result = $this->attendanceApi->searchKaryawan($search, $page, $perPage);
        } else {
            $result = $this->attendanceApi->getKaryawanPaginated($page, $perPage, $useCache);
        }
        
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error' => $result['error'] ?? null
            ], 500);
        }
        
        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => $result['meta'],
            'search' => $search,
            'cached' => $useCache && empty($search),
            'timestamp' => now()->toDateTimeString()
        ]);
    }
    
    /**
     * ✅ SEARCH KARYAWAN
     * GET /api/karyawan/search?q=john&page=1&per_page=20
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);
        
        $query = $request->get('q');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);
        
        $result = $this->attendanceApi->searchKaryawan($query, $page, $perPage);
        
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 500);
        }
        
        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => $result['meta'],
            'query' => $query
        ]);
    }
    
    /**
     * ✅ GET SINGLE KARYAWAN
     * GET /api/karyawan/123?refresh=1
     */
    public function show($id, Request $request)
    {
        $useCache = !$request->has('refresh');
        
        $result = $this->attendanceApi->getKaryawan($id, $useCache);
        
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'cached' => $useCache
        ]);
    }
    
    /**
     * ✅ GET MINIMAL DATA FOR DROPDOWN
     * GET /api/karyawan/active-minimal?refresh=1
     */
    public function activeMinimal(Request $request)
    {
        $useCache = !$request->has('refresh');
        
        $result = $this->attendanceApi->getActiveKaryawanMinimal($useCache);
        
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 500);
        }
        
        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'total' => count($result['data'])
        ]);
    }
    
    /**
     * ✅ BULK GET BY IDS
     * POST /api/karyawan/bulk
     * Body: {"ids": [1, 2, 3]}
     */
    public function bulk(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer'
        ]);
        
        $ids = $request->get('ids');
        $useCache = !$request->has('refresh');
        
        // ✅ GANTI: getBulk() → getBulkKaryawan()
        $result = $this->attendanceApi->getBulkKaryawan($ids, $useCache);
        
        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'requested' => $result['requested'],
            'found' => $result['found'],
            'from_cache' => $result['from_cache'] ?? 0,
            'fresh_fetch' => $result['fresh_fetch'] ?? 0
        ]);
    }
    
    /**
     * ✅ CLEAR MINIMAL CACHE
     * POST /api/karyawan/clear-cache
     */
    public function clearMinimalCache()
    {
        $this->attendanceApi->clearMinimalCache();
        
        return response()->json([
            'success' => true,
            'message' => 'Minimal cache cleared'
        ]);
    }
    
    /**
     * ✅ CLEAR SPECIFIC CACHE
     * POST /api/karyawan/clear-cache/123
     */
    public function clearSpecificCache($id)
    {
        $this->attendanceApi->clearKaryawanCache($id);
        
        return response()->json([
            'success' => true,
            'message' => "Cache cleared for karyawan {$id}"
        ]);
    }
}