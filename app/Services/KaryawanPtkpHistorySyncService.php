<?php
// app/Services/KaryawanPtkpHistorySyncService.php (DI APLIKASI GAJI)

namespace App\Services;

use App\Models\KaryawanPtkpHistory;
use App\Services\AttendanceApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KaryawanPtkpHistorySyncService
{
    protected $apiService;
    
    public function __construct(AttendanceApiService $apiService)
    {
        $this->apiService = $apiService;
    }
    
    /**
     * 🔄 SYNC SEMUA PTKP HISTORY (FULL SYNC)
     * Ambil semua data dari API ABSEN, sync ke local DB
     */
    public function syncAll($forceRefresh = false)
    {
        Log::info('🔄 Starting FULL SYNC PTKP History...');
        
        $stats = [
            'total_from_api' => 0,
            'new_inserted' => 0,
            'updated' => 0,
            'deleted' => 0,
            'errors' => 0,
            'start_time' => now(),
        ];
        
        try {
            // Step 1: Ambil semua PTKP History dari API (paginated)
            $allHistoriesFromApi = $this->fetchAllPtkpHistoriesFromApi($forceRefresh);
            $stats['total_from_api'] = count($allHistoriesFromApi);
            $ids = collect($allHistoriesFromApi)->pluck('id');
$uniqueCount = $ids->unique()->count();
$dupIds = $ids->duplicates()->values()->all();

Log::info("🧾 API ID CHECK", [
    'total_items' => $ids->count(),
    'unique_ids' => $uniqueCount,
    'dup_count' => count($dupIds),
    'dup_sample' => array_slice($dupIds, 0, 20),
]);

            if (empty($allHistoriesFromApi)) {
                Log::warning('⚠️ No PTKP History data from API');
                return [
                    'success' => false,
                    'message' => 'No data from API',
                    'stats' => $stats
                ];
            }
            
            // Step 2: Ambil semua ID dari API
            $apiIds = collect($allHistoriesFromApi)->pluck('id')->toArray();
            // Audit missing from DB (active + trashed)
$localIds = KaryawanPtkpHistory::withTrashed()
    ->pluck('absen_ptkp_history_id')
    ->toArray();

$missingInDb = array_values(array_diff($apiIds, $localIds));
if (!empty($missingInDb)) {
    Log::warning("⚠️ Missing PTKP History in DB (will be inserted if fetched)", [
        'missing_count' => count($missingInDb),
        'missing_ids' => array_slice($missingInDb, 0, 50),
    ]);
}

            
            DB::beginTransaction();
            
            // Step 3: Sync setiap PTKP History
            foreach ($allHistoriesFromApi as $apiHistory) {
                try {
                    $result = $this->syncSinglePtkpHistory($apiHistory);
                    
                    if ($result['action'] === 'inserted') {
                        $stats['new_inserted']++;
                    } elseif ($result['action'] === 'updated') {
                        $stats['updated']++;
                    }
                    
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error('Error syncing PTKP History', [
                        'id' => $apiHistory['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }
            Log::info("🛢️ DB CHECK", [
    'connection' => DB::connection()->getName(),
    'database' => DB::connection()->getDatabaseName(),
    'db_host' => config('database.connections.' . DB::connection()->getName() . '.host'),
]);

            
            // Step 4: Hapus PTKP History yang tidak ada di API lagi (soft delete)
            $deletedCount = $this->deleteRemovedPtkpHistories($apiIds);
            $stats['deleted'] = $deletedCount;
            
            DB::commit();
            
            $stats['end_time'] = now();
            $stats['duration_seconds'] = $stats['start_time']->diffInSeconds($stats['end_time']);
            
            Log::info('✅ FULL SYNC PTKP History completed', $stats);
            
            return [
                'success' => true,
                'message' => 'Sync completed successfully',
                'stats' => $stats
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('❌ FULL SYNC PTKP History failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
                'stats' => $stats
            ];
        }
    }
    
    /**
 * 📥 FETCH ALL PTKP HISTORY FROM API (robust pagination)
 * - Selalu ambil page 1 TANPA cache untuk dapat meta terbaru
 * - Sisanya juga default tanpa cache biar gak ketahan data lama
 * - Loop sampai count >= meta.total (kalau meta ada)
 */
protected function fetchAllPtkpHistoriesFromApi($forceRefresh = false)
{
    $allHistories = [];
    $page = 1;
    $perPage = 100;

    $expectedTotal = null;
    $lastPage = null;

    while (true) {
        // ✅ Page 1 wajib fresh supaya meta (total/last_page) up-to-date
        $useCache = false;

        // Kalau lu mau tetap cache saat bukan forceRefresh, boleh:
        // $useCache = (!$forceRefresh && $page > 1);

        $response = $this->apiService->getPtkpHistoriesPaginated($page, $perPage, $useCache);

        if (empty($response) || !($response['success'] ?? false)) {
            Log::error('Failed to fetch PTKP History page', [
                'page' => $page,
                'response' => $response
            ]);
            break;
        }

        $data = $response['data'] ?? [];
        $meta = $response['meta'] ?? [];

        // meta dari API
        $expectedTotal = $expectedTotal ?? ($meta['total'] ?? null);
        $lastPage = $lastPage ?? ($meta['last_page'] ?? null);

        // merge
        $allHistories = array_merge($allHistories, $data);

        Log::info("📄 Fetched PTKP History page {$page}", [
            'count_page' => count($data),
            'count_total_collected' => count($allHistories),
            'expected_total' => $expectedTotal,
            'last_page' => $lastPage,
        ]);

        // stop conditions
        if (count($data) === 0) {
            // safety: API ngasih kosong -> stop
            break;
        }

        // Kalau meta total ada, stop saat sudah terkumpul semua
        if ($expectedTotal !== null && count($allHistories) >= $expectedTotal) {
            break;
        }

        // Kalau meta last_page ada, stop saat sudah lewat last_page
        if ($lastPage !== null && $page >= $lastPage) {
            break;
        }

        $page++;
        if ($page > 2000) { // safety guard
            Log::warning("Pagination guard hit, stopping.", ['page' => $page]);
            break;
        }
    }

    // ✅ hard check: kalau meta bilang 226 tapi terkumpul 223 -> log error jelas
    if ($expectedTotal !== null && count($allHistories) !== $expectedTotal) {
        Log::warning("⚠️ PTKP History fetched count mismatch", [
            'expected_total' => $expectedTotal,
            'fetched' => count($allHistories),
            'missing' => $expectedTotal - count($allHistories),
        ]);
    }

    return $allHistories;
}

    
    /**
     * 🔄 SYNC SINGLE PTKP HISTORY
     * Insert baru atau update existing
     */
    protected function syncSinglePtkpHistory(array $apiData)
    {
        $absenHistoryId = $apiData['id'];
        
        // Cari PTKP History berdasarkan absen_ptkp_history_id
        $history = KaryawanPtkpHistory::withTrashed()
            ->where('absen_ptkp_history_id', $absenHistoryId)
            ->first();
        
        $preparedData = $this->preparePtkpHistoryData($apiData);
        
        if ($history) {
            // UPDATE existing
            
            // Jika sebelumnya soft deleted, restore dulu
            if ($history->trashed()) {
                $history->restore();
                Log::info("🔄 Restored PTKP History", ['id' => $absenHistoryId]);
            }
            
            $history->update($preparedData);
            
            return [
                'action' => 'updated',
                'history_id' => $history->id,
                'absen_history_id' => $absenHistoryId
            ];
            
        } else {
            // INSERT new
            $history = KaryawanPtkpHistory::create($preparedData);
            
            Log::info("✅ Inserted new PTKP History", [
                'local_id' => $history->id,
                'absen_id' => $absenHistoryId,
                'karyawan_id' => $history->absen_karyawan_id,
                'tahun' => $history->tahun
            ]);
            
            return [
                'action' => 'inserted',
                'history_id' => $history->id,
                'absen_history_id' => $absenHistoryId
            ];
        }
    }
    
    /**
     * 🗑️ DELETE PTKP HISTORY YANG TIDAK ADA DI API
     * Soft delete PTKP History yang sudah tidak ada di API ABSEN
     */
    protected function deleteRemovedPtkpHistories(array $apiIds)
    {
        // Ambil semua ID local yang tidak ada di API lagi
        $toDelete = KaryawanPtkpHistory::whereNotIn('absen_ptkp_history_id', $apiIds)
            ->whereNull('deleted_at')
            ->get();
        
        $deletedCount = 0;
        
        foreach ($toDelete as $history) {
            // Soft delete
            $history->delete();
            $deletedCount++;
            
            Log::info("🗑️ Soft deleted PTKP History", [
                'local_id' => $history->id,
                'absen_id' => $history->absen_ptkp_history_id,
                'karyawan_id' => $history->absen_karyawan_id,
                'tahun' => $history->tahun
            ]);
        }
        
        return $deletedCount;
    }
    
    /**
     * 📋 PREPARE DATA dari API untuk disimpan ke local DB
     */
    protected function preparePtkpHistoryData(array $apiData)
    {
        return [
            'absen_ptkp_history_id' => $apiData['id'],
            'absen_karyawan_id' => $apiData['karyawan_id'],
            'absen_ptkp_id' => $apiData['ptkp_id'],
            'tahun' => $apiData['tahun'],
            'absen_updated_by_id' => $apiData['updated_by_id'] ?? null,
            'last_synced_at' => now(),
            'sync_metadata' => json_encode([
                'synced_from' => 'api',
                'api_id' => $apiData['id'],
                'synced_at' => now()->toISOString(),
                'karyawan_info' => $apiData['karyawan'] ?? null,
                'ptkp_info' => $apiData['ptkp'] ?? null,
            ])
        ];
    }
    
    /**
     * 🔄 SYNC SPECIFIC PTKP HISTORY BY ID
     * Sync satu PTKP History aja by absen_ptkp_history_id
     */
    public function syncById($absenHistoryId)
    {
        try {
            // Ambil data dari API
            $response = $this->apiService->getPtkpHistory($absenHistoryId, false);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => 'PTKP History not found in API'
                ];
            }
            
            $apiData = $response['data'];
            
            DB::beginTransaction();
            $result = $this->syncSinglePtkpHistory($apiData);
            DB::commit();
            
            Log::info("✅ Synced single PTKP History", $result);
            
            return [
                'success' => true,
                'message' => 'PTKP History synced successfully',
                'action' => $result['action'],
                'data' => $result
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error syncing single PTKP History', [
                'absen_id' => $absenHistoryId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 🔄 SYNC BY KARYAWAN ID
     * Sync semua PTKP History untuk karyawan tertentu
     */
    public function syncByKaryawan($absenKaryawanId, $forceRefresh = false)
    {
        try {
            $response = $this->apiService->getPtkpHistoryByKaryawan($absenKaryawanId, !$forceRefresh);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => 'No PTKP History found for this karyawan'
                ];
            }
            
            $histories = $response['data'] ?? [];
            
            $stats = [
                'total' => count($histories),
                'inserted' => 0,
                'updated' => 0,
                'errors' => 0
            ];
            
            DB::beginTransaction();
            
            foreach ($histories as $apiHistory) {
                try {
                    $result = $this->syncSinglePtkpHistory($apiHistory);
                    
                    if ($result['action'] === 'inserted') {
                        $stats['inserted']++;
                    } else {
                        $stats['updated']++;
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error('Error syncing PTKP History by karyawan', [
                        'karyawan_id' => $absenKaryawanId,
                        'history_id' => $apiHistory['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Karyawan PTKP History synced',
                'stats' => $stats
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 🔄 SYNC BY TAHUN
     * Sync semua PTKP History untuk tahun tertentu
     */
    public function syncByTahun($tahun, $forceRefresh = false)
    {
        try {
            $response = $this->apiService->getPtkpHistoryByTahun($tahun, !$forceRefresh);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => 'No PTKP History found for this tahun'
                ];
            }
            
            $histories = $response['data'] ?? [];
            
            $stats = [
                'total' => count($histories),
                'inserted' => 0,
                'updated' => 0,
                'errors' => 0
            ];
            
            DB::beginTransaction();
            
            foreach ($histories as $apiHistory) {
                try {
                    $result = $this->syncSinglePtkpHistory($apiHistory);
                    
                    if ($result['action'] === 'inserted') {
                        $stats['inserted']++;
                    } else {
                        $stats['updated']++;
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error('Error syncing PTKP History by tahun', [
                        'tahun' => $tahun,
                        'history_id' => $apiHistory['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => "PTKP History for tahun {$tahun} synced",
                'stats' => $stats
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 📊 GET SYNC STATISTICS
     */
    public function getSyncStats()
    {
        return [
            'total_histories' => KaryawanPtkpHistory::count(),
            'soft_deleted' => KaryawanPtkpHistory::onlyTrashed()->count(),
            'never_synced' => KaryawanPtkpHistory::whereNull('last_synced_at')->count(),
            'last_sync_time' => KaryawanPtkpHistory::max('last_synced_at'),
            'oldest_sync_time' => KaryawanPtkpHistory::min('last_synced_at'),
            'by_tahun' => KaryawanPtkpHistory::selectRaw('tahun, COUNT(*) as total')
                ->groupBy('tahun')
                ->orderBy('tahun', 'desc')
                ->get()
                ->pluck('total', 'tahun'),
        ];
    }
    
    /**
     * 🔍 CHECK SYNC HEALTH
     * Cek apakah ada PTKP History yang perlu di-sync ulang
     */
    public function checkSyncHealth($hoursThreshold = 24)
    {
        $needsSync = KaryawanPtkpHistory::where(function($q) use ($hoursThreshold) {
            $q->whereNull('last_synced_at')
              ->orWhere('last_synced_at', '<', now()->subHours($hoursThreshold));
        })->count();
        
        $total = KaryawanPtkpHistory::count();
        
        return [
            'healthy' => $needsSync === 0,
            'needs_sync_count' => $needsSync,
            'total_count' => $total,
            'percentage_synced' => $total > 0 ? round((($total - $needsSync) / $total) * 100, 2) : 100,
            'threshold_hours' => $hoursThreshold
        ];
    }

    
}