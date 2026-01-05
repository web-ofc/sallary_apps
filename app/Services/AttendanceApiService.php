<?php
// app/Services/AttendanceApiService.php (CLEAN & OPTIMIZED)

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AttendanceApiService
{
    protected $baseUrl;
    protected $token;
    protected $listCacheTime = 1800; // 30 menit untuk list
    protected $detailCacheTime = 600; // 10 menit untuk detail
    
    public function __construct()
    {
        $this->baseUrl = config('services.attendance.url');
        $this->token = config('services.attendance.token');
    }
    
    /*
    |--------------------------------------------------------------------------
    | KARYAWAN METHODS
    |--------------------------------------------------------------------------
    */
    
    /**
     * GET KARYAWAN WITH PAGINATION
     */
    public function getKaryawanPaginated($page = 1, $perPage = 50, $useCache = true)
    {
        $perPage = min($perPage, 100);
        
        if (!$useCache) {
            return $this->fetchKaryawanPaginated($page, $perPage);
        }
        
        $cacheKey = "karyawan_page_{$page}_per_{$perPage}";
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $result = $this->fetchKaryawanPaginated($page, $perPage);
        
        // ✅ Cuma cache kalau success
        if ($result['success']) {
            Cache::put($cacheKey, $result, $this->listCacheTime);
        }
        
        return $result;
    }
    
    protected function fetchKaryawanPaginated($page, $perPage)
    {
        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(30)
                ->get("{$this->baseUrl}/karyawan", [
                    'page' => $page,
                    'per_page' => $perPage
                ]);
            
            if ($response->successful()) {
                $json = $response->json();
                
                return [
                    'success' => true,
                    'data' => $json['data'] ?? [],
                    'meta' => $json['meta'] ?? [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => 0,
                        'last_page' => 1,
                    ]
                ];
            }
            
            Log::warning('Failed fetch karyawan paginated', [
                'page' => $page,
                'status' => $response->status()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to fetch karyawan data'
            ];
            
        } catch (\Exception $e) {
            Log::error('API Error karyawan paginated', [
                'page' => $page,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'API connection error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * SEARCH KARYAWAN (SERVER-SIDE)
     */
    public function searchKaryawan($query, $page = 1, $perPage = 20)
    {
        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(30)
                ->get("{$this->baseUrl}/karyawan/search", [
                    'q' => $query,
                    'page' => $page,
                    'per_page' => $perPage
                ]);
            
            if ($response->successful()) {
                $json = $response->json();
                
                return [
                    'success' => true,
                    'data' => $json['data'] ?? [],
                    'meta' => $json['meta'] ?? []
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Search failed'
            ];
            
        } catch (\Exception $e) {
            Log::error('Search karyawan error', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * GET SINGLE KARYAWAN BY ID
     */
    public function getKaryawan($id, $useCache = true)
    {
        if (!$useCache) {
            return $this->fetchKaryawan($id);
        }
        
        $cacheKey = "karyawan_{$id}";
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $result = $this->fetchKaryawan($id);
        
        // ✅ Cuma cache kalau success
        if ($result['success']) {
            Cache::put($cacheKey, $result, $this->detailCacheTime);
        }
        
        return $result;
    }
    
    protected function fetchKaryawan($id)
    {
        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(30)
                ->get("{$this->baseUrl}/karyawan/{$id}");
            
            if ($response->successful()) {
                $data = $response->json('data');
                
                if (empty($data)) {
                    Log::warning('Empty karyawan data', ['id' => $id]);
                    return [
                        'success' => false,
                        'message' => 'Karyawan tidak ditemukan'
                    ];
                }
                
                return [
                    'success' => true,
                    'data' => $data
                ];
            }
            
            Log::warning('Karyawan not found', [
                'id' => $id,
                'status' => $response->status()
            ]);
            
            return [
                'success' => false,
                'message' => 'Karyawan tidak ditemukan'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error fetching karyawan', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * GET KARYAWAN AKTIF (MINIMAL) - Untuk dropdown
     */
    public function getActiveKaryawanMinimal($useCache = true)
    {
        if (!$useCache) {
            return $this->fetchActiveKaryawanMinimal();
        }
        
        $cacheKey = 'karyawan_active_minimal';
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $result = $this->fetchActiveKaryawanMinimal();
        
        if ($result['success']) {
            Cache::put($cacheKey, $result, $this->listCacheTime);
        }
        
        return $result;
    }
    
    protected function fetchActiveKaryawanMinimal()
    {
        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(30)
                ->get("{$this->baseUrl}/karyawan/active-minimal");
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data') ?? []
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to fetch minimal data'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error fetch minimal karyawan', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * BULK GET KARYAWAN BY IDS (OPTIMIZED - PARALLEL)
     */
    public function getBulkKaryawan(array $ids, $useCache = true)
    {
        if (empty($ids)) {
            return [
                'success' => true,
                'data' => [],
                'requested' => 0,
                'found' => 0
            ];
        }
        
        $cached = [];
        $toFetch = [];
        
        // ✅ Check cache dulu
        if ($useCache) {
            foreach ($ids as $id) {
                $cacheKey = "karyawan_{$id}";
                $cachedData = Cache::get($cacheKey);
                
                if ($cachedData !== null && $cachedData['success']) {
                    $cached[$id] = $cachedData['data'];
                } else {
                    $toFetch[] = $id;
                }
            }
        } else {
            $toFetch = $ids;
        }
        
        $fetched = [];
        
        // ✅ Fetch yang belum ada di cache (parallel)
        if (!empty($toFetch)) {
            $promises = [];
            
            foreach ($toFetch as $id) {
                $promises[$id] = Http::withToken($this->token)
                    ->acceptJson()
                    ->timeout(30)
                    ->async()
                    ->get("{$this->baseUrl}/karyawan/{$id}");
            }
            
            foreach ($promises as $id => $promise) {
                try {
                    $response = $promise->wait();
                    
                    if ($response->successful()) {
                        $data = $response->json('data');
                        
                        if (!empty($data)) {
                            $fetched[] = $data;
                            
                            // Cache hasil yang success
                            if ($useCache) {
                                Cache::put(
                                    "karyawan_{$id}",
                                    ['success' => true, 'data' => $data],
                                    $this->detailCacheTime
                                );
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Bulk fetch karyawan error', [
                        'id' => $id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        $allResults = array_merge(array_values($cached), $fetched);
        
        return [
            'success' => true,
            'data' => $allResults,
            'requested' => count($ids),
            'found' => count($allResults),
            'from_cache' => count($cached),
            'fresh_fetch' => count($fetched)
        ];
    }
    
    /*
    |--------------------------------------------------------------------------
    | COMPANY METHODS
    |--------------------------------------------------------------------------
    */
    
    /**
     * GET COMPANIES WITH PAGINATION
     */
    public function getCompaniesPaginated($page = 1, $perPage = 50, $useCache = true)
    {
        $perPage = min($perPage, 100);
        
        if (!$useCache) {
            return $this->fetchCompaniesPaginated($page, $perPage);
        }
        
        $cacheKey = "companies_page_{$page}_per_{$perPage}";
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $result = $this->fetchCompaniesPaginated($page, $perPage);
        
        if ($result['success']) {
            Cache::put($cacheKey, $result, $this->listCacheTime);
        }
        
        return $result;
    }
    
    protected function fetchCompaniesPaginated($page, $perPage)
    {
        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(30)
                ->get("{$this->baseUrl}/companies", [
                    'page' => $page,
                    'per_page' => $perPage
                ]);
            
            if ($response->successful()) {
                $json = $response->json();
                
                return [
                    'success' => true,
                    'data' => $json['data'] ?? [],
                    'meta' => $json['meta'] ?? [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => 0,
                        'last_page' => 1,
                    ]
                ];
            }
            
            Log::warning('Failed fetch companies', [
                'page' => $page,
                'status' => $response->status()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to fetch companies'
            ];
            
        } catch (\Exception $e) {
            Log::error('API Error companies', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'API connection error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * SEARCH COMPANIES
     */
    public function searchCompanies($query, $page = 1, $perPage = 20)
    {
        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(30)
                ->get("{$this->baseUrl}/companies/search", [
                    'q' => $query,
                    'page' => $page,
                    'per_page' => $perPage
                ]);
            
            if ($response->successful()) {
                $json = $response->json();
                
                return [
                    'success' => true,
                    'data' => $json['data'] ?? [],
                    'meta' => $json['meta'] ?? []
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Search failed'
            ];
            
        } catch (\Exception $e) {
            Log::error('Search companies error', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * GET SINGLE COMPANY BY ID
     */
    public function getCompany($id, $useCache = true)
    {
        if (!$useCache) {
            return $this->fetchCompany($id);
        }
        
        $cacheKey = "company_{$id}";
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $result = $this->fetchCompany($id);
        
        if ($result['success']) {
            Cache::put($cacheKey, $result, $this->detailCacheTime);
        }
        
        return $result;
    }
    
    protected function fetchCompany($id)
    {
        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(30)
                ->get("{$this->baseUrl}/companies/{$id}");
            
            if ($response->successful()) {
                $data = $response->json('data');
                
                if (empty($data)) {
                    return [
                        'success' => false,
                        'message' => 'Company tidak ditemukan'
                    ];
                }
                
                return [
                    'success' => true,
                    'data' => $data
                ];
            }
            
            Log::warning('Company not found', [
                'id' => $id,
                'status' => $response->status()
            ]);
            
            return [
                'success' => false,
                'message' => 'Company tidak ditemukan'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error fetching company', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * GET COMPANY BY CODE
     */
    public function getCompanyByCode($code, $useCache = true)
    {
        if (!$useCache) {
            return $this->fetchCompanyByCode($code);
        }
        
        $cacheKey = "company_code_{$code}";
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $result = $this->fetchCompanyByCode($code);
        
        if ($result['success']) {
            Cache::put($cacheKey, $result, $this->detailCacheTime);
        }
        
        return $result;
    }
    
    protected function fetchCompanyByCode($code)
    {
        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(30)
                ->get("{$this->baseUrl}/companies/by-code/{$code}");
            
            if ($response->successful()) {
                $data = $response->json('data');
                
                if (empty($data)) {
                    return [
                        'success' => false,
                        'message' => 'Company tidak ditemukan'
                    ];
                }
                
                return [
                    'success' => true,
                    'data' => $data
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Company tidak ditemukan'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * GET COMPANIES MINIMAL - Untuk dropdown
     */
    public function getCompaniesMinimal($useCache = true)
    {
        if (!$useCache) {
            return $this->fetchCompaniesMinimal();
        }
        
        $cacheKey = 'companies_minimal';
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $result = $this->fetchCompaniesMinimal();
        
        if ($result['success']) {
            Cache::put($cacheKey, $result, $this->listCacheTime);
        }
        
        return $result;
    }
    
    protected function fetchCompaniesMinimal()
    {
        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(30)
                ->get("{$this->baseUrl}/companies/minimal");
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data') ?? []
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to fetch minimal data'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * BULK GET COMPANIES BY IDS
     */
    public function getBulkCompanies(array $ids, $useCache = true)
    {
        if (empty($ids)) {
            return [
                'success' => true,
                'data' => [],
                'requested' => 0,
                'found' => 0
            ];
        }
        
        $cached = [];
        $toFetch = [];
        
        if ($useCache) {
            foreach ($ids as $id) {
                $cacheKey = "company_{$id}";
                $cachedData = Cache::get($cacheKey);
                
                if ($cachedData !== null && $cachedData['success']) {
                    $cached[$id] = $cachedData['data'];
                } else {
                    $toFetch[] = $id;
                }
            }
        } else {
            $toFetch = $ids;
        }
        
        $fetched = [];
        
        if (!empty($toFetch)) {
            $promises = [];
            
            foreach ($toFetch as $id) {
                $promises[$id] = Http::withToken($this->token)
                    ->acceptJson()
                    ->timeout(30)
                    ->async()
                    ->get("{$this->baseUrl}/companies/{$id}");
            }
            
            foreach ($promises as $id => $promise) {
                try {
                    $response = $promise->wait();
                    
                    if ($response->successful()) {
                        $data = $response->json('data');
                        
                        if (!empty($data)) {
                            $fetched[] = $data;
                            
                            if ($useCache) {
                                Cache::put(
                                    "company_{$id}",
                                    ['success' => true, 'data' => $data],
                                    $this->detailCacheTime
                                );
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Bulk fetch company error', [
                        'id' => $id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        $allResults = array_merge(array_values($cached), $fetched);
        
        return [
            'success' => true,
            'data' => $allResults,
            'requested' => count($ids),
            'found' => count($allResults),
            'from_cache' => count($cached),
            'fresh_fetch' => count($fetched)
        ];
    }
    
    /*
    |--------------------------------------------------------------------------
    | FILE/MEDIA HELPERS
    |--------------------------------------------------------------------------
    */
    
    /**
     * GET FILE URL dengan token untuk karyawan
     */
    public function getKaryawanFileUrl($id, $fileType)
    {
        return "{$this->baseUrl}/files/karyawan/{$id}/{$fileType}?token={$this->token}";
    }
    
    /**
     * GET COMPANY LOGO URL
     */
    public function getCompanyLogoUrl($id)
    {
        return "{$this->baseUrl}/companies/{$id}/logo?token={$this->token}";
    }
    
    /*
    |--------------------------------------------------------------------------
    | CACHE MANAGEMENT
    |--------------------------------------------------------------------------
    */
    
    /**
     * Clear cache untuk karyawan tertentu
     */
    public function clearKaryawanCache($id)
    {
        Cache::forget("karyawan_{$id}");
        Log::info("Cache cleared", ['type' => 'karyawan', 'id' => $id]);
    }
    
    /**
     * Clear cache untuk company tertentu
     */
    public function clearCompanyCache($id)
    {
        Cache::forget("company_{$id}");
        Log::info("Cache cleared", ['type' => 'company', 'id' => $id]);
    }
    
    /**
     * Clear cache company by code
     */
    public function clearCompanyCodeCache($code)
    {
        Cache::forget("company_code_{$code}");
    }
    
    /**
     * Clear pagination cache
     */
    public function clearPaginationCache($type = 'karyawan', $page = null, $perPage = null)
    {
        if ($page && $perPage) {
            $cacheKey = "{$type}_page_{$page}_per_{$perPage}";
            Cache::forget($cacheKey);
        }
    }
    
    /**
     * Clear minimal/dropdown cache
     */
    public function clearMinimalCache()
    {
        Cache::forget('karyawan_active_minimal');
        Cache::forget('companies_minimal');
        Log::info("Minimal cache cleared");
    }
    
    /**
     * ⚠️ DANGER: Clear ALL cache - JANGAN pakai di production!
     */
    public function clearAllCache()
    {
        if (app()->environment('production')) {
            Log::warning('Attempted Cache::flush() in PRODUCTION - BLOCKED');
            
            // Di production, clear selective aja
            $this->clearSelectiveCache();
            return;
        }
        
        // Di dev/local boleh flush all
        Cache::flush();
        Log::info("All cache flushed (development only)");
    }
    
    /**
     * Clear cache selective (AMAN di production)
     */
    protected function clearSelectiveCache()
    {
        // Clear known minimal cache
        Cache::forget('karyawan_active_minimal');
        Cache::forget('companies_minimal');
        
        // Note: Pagination cache akan expire sendiri setelah 30 menit
        // Detail cache expire setelah 10 menit
        
        Log::info("Selective cache cleared");
    }

    
    /*
    |--------------------------------------------------------------------------
    | PTKP METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * GET PTKP WITH PAGINATION
     */
    public function getPtkpsPaginated($page = 1, $perPage = 50, $useCache = true)
    {
        $perPage = min($perPage, 100);
        
        if (!$useCache) {
            return $this->fetchPtkpsPaginated($page, $perPage);
        }
        
        $cacheKey = "ptkp_page_{$page}_per_{$perPage}";
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $result = $this->fetchPtkpsPaginated($page, $perPage);
        
        if ($result['success']) {
            Cache::put($cacheKey, $result, $this->listCacheTime);
        }
        
        return $result;
    }

    protected function fetchPtkpsPaginated($page, $perPage)
    {
        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(30)
                ->get("{$this->baseUrl}/ptkp", [
                    'page' => $page,
                    'per_page' => $perPage
                ]);
            
            if ($response->successful()) {
                $json = $response->json();
                
                return [
                    'success' => true,
                    'data' => $json['data'] ?? [],
                    'meta' => $json['meta'] ?? [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => 0,
                        'last_page' => 1,
                    ]
                ];
            }
            
            Log::warning('Failed fetch PTKP paginated', [
                'page' => $page,
                'status' => $response->status()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to fetch PTKP data'
            ];
            
        } catch (\Exception $e) {
            Log::error('API Error PTKP paginated', [
                'page' => $page,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'API connection error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * SEARCH PTKP
     */
    public function searchPtkp($query, $page = 1, $perPage = 20)
    {
        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(30)
                ->get("{$this->baseUrl}/ptkp/search", [
                    'q' => $query,
                    'page' => $page,
                    'per_page' => $perPage
                ]);
            
            if ($response->successful()) {
                $json = $response->json();
                
                return [
                    'success' => true,
                    'data' => $json['data'] ?? [],
                    'meta' => $json['meta'] ?? []
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Search failed'
            ];
            
        } catch (\Exception $e) {
            Log::error('Search PTKP error', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * GET SINGLE PTKP BY ID
     */
    public function getPtkp($id, $useCache = true)
    {
        if (!$useCache) {
            return $this->fetchPtkp($id);
        }
        
        $cacheKey = "ptkp_{$id}";
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $result = $this->fetchPtkp($id);
        
        if ($result['success']) {
            Cache::put($cacheKey, $result, $this->detailCacheTime);
        }
        
        return $result;
    }

    protected function fetchPtkp($id)
    {
        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(30)
                ->get("{$this->baseUrl}/ptkp/{$id}");
            
            if ($response->successful()) {
                $data = $response->json('data');
                
                if (empty($data)) {
                    Log::warning('Empty PTKP data', ['id' => $id]);
                    return [
                        'success' => false,
                        'message' => 'PTKP tidak ditemukan'
                    ];
                }
                
                return [
                    'success' => true,
                    'data' => $data
                ];
            }
            
            Log::warning('PTKP not found', [
                'id' => $id,
                'status' => $response->status()
            ]);
            
            return [
                'success' => false,
                'message' => 'PTKP tidak ditemukan'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error fetching PTKP', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * GET PTKP BY KRITERIA
     */
    public function getPtkpByKriteria($kriteria, $useCache = true)
    {
        if (!$useCache) {
            return $this->fetchPtkpByKriteria($kriteria);
        }
        
        $cacheKey = "ptkp_kriteria_{$kriteria}";
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $result = $this->fetchPtkpByKriteria($kriteria);
        
        if ($result['success']) {
            Cache::put($cacheKey, $result, $this->detailCacheTime);
        }
        
        return $result;
    }

    protected function fetchPtkpByKriteria($kriteria)
    {
        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(30)
                ->get("{$this->baseUrl}/ptkp/by-kriteria/{$kriteria}");
            
            if ($response->successful()) {
                $data = $response->json('data');
                
                if (empty($data)) {
                    return [
                        'success' => false,
                        'message' => 'PTKP tidak ditemukan'
                    ];
                }
                
                return [
                    'success' => true,
                    'data' => $data
                ];
            }
            
            return [
                'success' => false,
                'message' => 'PTKP tidak ditemukan'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * GET PTKP BY STATUS
     */
    public function getPtkpByStatus($status, $useCache = true)
    {
        if (!$useCache) {
            return $this->fetchPtkpByStatus($status);
        }
        
        $cacheKey = "ptkp_status_{$status}";
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $result = $this->fetchPtkpByStatus($status);
        
        if ($result['success']) {
            Cache::put($cacheKey, $result, $this->listCacheTime);
        }
        
        return $result;
    }

    protected function fetchPtkpByStatus($status)
    {
        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(30)
                ->get("{$this->baseUrl}/ptkp/by-status/{$status}");
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data') ?? []
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to fetch PTKP by status'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * GET PTKP MINIMAL - Untuk dropdown
     */
    public function getPtkpMinimal($useCache = true)
    {
        if (!$useCache) {
            return $this->fetchPtkpMinimal();
        }
        
        $cacheKey = 'ptkp_minimal';
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $result = $this->fetchPtkpMinimal();
        
        if ($result['success']) {
            Cache::put($cacheKey, $result, $this->listCacheTime);
        }
        
        return $result;
    }

    protected function fetchPtkpMinimal()
    {
        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(30)
                ->get("{$this->baseUrl}/ptkp/minimal");
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data') ?? []
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to fetch minimal data'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * BULK GET PTKP BY IDS
     */
    public function getBulkPtkp(array $ids, $useCache = true)
    {
        if (empty($ids)) {
            return [
                'success' => true,
                'data' => [],
                'requested' => 0,
                'found' => 0
            ];
        }
        
        $cached = [];
        $toFetch = [];
        
        if ($useCache) {
            foreach ($ids as $id) {
                $cacheKey = "ptkp_{$id}";
                $cachedData = Cache::get($cacheKey);
                
                if ($cachedData !== null && $cachedData['success']) {
                    $cached[$id] = $cachedData['data'];
                } else {
                    $toFetch[] = $id;
                }
            }
        } else {
            $toFetch = $ids;
        }
        
        $fetched = [];
        
        if (!empty($toFetch)) {
            $promises = [];
            
            foreach ($toFetch as $id) {
                $promises[$id] = Http::withToken($this->token)
                    ->acceptJson()
                    ->timeout(30)
                    ->async()
                    ->get("{$this->baseUrl}/ptkp/{$id}");
            }
            
            foreach ($promises as $id => $promise) {
                try {
                    $response = $promise->wait();
                    
                    if ($response->successful()) {
                        $data = $response->json('data');
                        
                        if (!empty($data)) {
                            $fetched[] = $data;
                            
                            if ($useCache) {
                                Cache::put(
                                    "ptkp_{$id}",
                                    ['success' => true, 'data' => $data],
                                    $this->detailCacheTime
                                );
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Bulk fetch PTKP error', [
                        'id' => $id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        $allResults = array_merge(array_values($cached), $fetched);
        
        return [
            'success' => true,
            'data' => $allResults,
            'requested' => count($ids),
            'found' => count($allResults),
            'from_cache' => count($cached),
            'fresh_fetch' => count($fetched)
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CACHE MANAGEMENT - UPDATE SECTION
    |--------------------------------------------------------------------------
    */

    /**
     * Clear cache untuk PTKP tertentu
     */
    public function clearPtkpCache($id)
    {
        Cache::forget("ptkp_{$id}");
        Log::info("Cache cleared", ['type' => 'ptkp', 'id' => $id]);
    }

    /**
     * Clear cache PTKP by kriteria
     */
    public function clearPtkpKriteriaCache($kriteria)
    {
        Cache::forget("ptkp_kriteria_{$kriteria}");
    }

    /**
     * Clear cache PTKP by status
     */
    public function clearPtkpStatusCache($status)
    {
        Cache::forget("ptkp_status_{$status}");
    }

}