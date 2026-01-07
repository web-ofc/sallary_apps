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
     */
    public function syncAll($forceRefresh = false, $filters = [])
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
            // Step 1: Ambil semua PTKP History dari API
            $allHistoriesFromApi = $this->fetchAllHistoriesFromApi($filters);
            $stats['total_from_api'] = count($allHistoriesFromApi);
            
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
            
            DB::beginTransaction();
            
            // Step 3: Sync setiap PTKP History
            foreach ($allHistoriesFromApi as $apiHistory) {
                try {
                    $result = $this->syncSingleHistory($apiHistory);
                    
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
            
            // Step 4: Hapus PTKP History yang tidak ada di API lagi
            $deletedCount = $this->deleteRemovedHistories($apiIds);
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
     * 📥 FETCH ALL PTKP HISTORY FROM API
     */
    protected function fetchAllHistoriesFromApi($filters = [])
    {
        $allHistories = [];
        $page = 1;
        $perPage = 100;
        
        do {
            $response = $this->apiService->getPtkpHistoriesPaginated($page, $perPage, $filters, false);
            
            if (!$response['success']) {
                Log::error('Failed to fetch PTKP History page', ['page' => $page]);
                break;
            }
            
            $data = $response['data'] ?? [];
            $allHistories = array_merge($allHistories, $data);
            
            $meta = $response['meta'] ?? [];
            $currentPage = $meta['current_page'] ?? $page;
            $lastPage = $meta['last_page'] ?? $page;
            
            Log::info("📄 Fetched PTKP History page {$currentPage}/{$lastPage}", [
                'count' => count($data)
            ]);
            
            $page++;
            
        } while ($page <= ($meta['last_page'] ?? 1));
        
        return $allHistories;
    }
    
    /**
     * 🔄 SYNC SINGLE PTKP HISTORY
     */
    protected function syncSingleHistory(array $apiData)
    {
        $absenHistoryId = $apiData['id'];
        
        // Cari PTKP History berdasarkan absen_ptkp_history_id
        $history = KaryawanPtkpHistory::withTrashed()
            ->where('absen_ptkp_history_id', $absenHistoryId)
            ->first();
        
        $preparedData = $this->prepareHistoryData($apiData);
        
        if ($history) {
            // UPDATE existing
            
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
     */
    protected function deleteRemovedHistories(array $apiIds)
    {
        $toDelete = KaryawanPtkpHistory::whereNotIn('absen_ptkp_history_id', $apiIds)
            ->whereNull('deleted_at')
            ->get();
        
        $deletedCount = 0;
        
        foreach ($toDelete as $history) {
            // Soft delete langsung (karena history biasanya tidak punya relasi)
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
     * 📋 PREPARE DATA
     */
    protected function prepareHistoryData(array $apiData)
    {
        return [
            'absen_ptkp_history_id' => $apiData['id'],
            'absen_karyawan_id' => $apiData['karyawan_id'] ?? null,
            'absen_ptkp_id' => $apiData['ptkp_id'] ?? null,
            'tahun' => $apiData['tahun'] ?? null,
            'absen_updated_by_id' => $apiData['updated_by_id'] ?? null,
            'last_synced_at' => now(),
            'sync_metadata' => json_encode([
                'synced_from' => 'api',
                'api_id' => $apiData['id'],
                'synced_at' => now()->toISOString(),
                'karyawan_nama' => $apiData['karyawan']['nama_lengkap'] ?? null,
                'ptkp_kriteria' => $apiData['ptkp']['kriteria'] ?? null,
            ])
        ];
    }
    
    /**
     * 🔄 SYNC SPECIFIC PTKP HISTORY BY ID
     */
    public function syncById($absenHistoryId)
    {
        try {
            $response = $this->apiService->getPtkpHistory($absenHistoryId, false);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => 'PTKP History not found in API'
                ];
            }
            
            $apiData = $response['data'];
            
            DB::beginTransaction();
            $result = $this->syncSingleHistory($apiData);
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
     */
    public function syncByKaryawanId($absenKaryawanId)
    {
        try {
            $response = $this->apiService->getPtkpHistoryByKaryawan($absenKaryawanId, false);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => 'No PTKP History found for this karyawan'
                ];
            }
            
            $histories = $response['data'] ?? [];
            
            if (empty($histories)) {
                return [
                    'success' => true,
                    'message' => 'No histories to sync',
                    'synced' => 0
                ];
            }
            
            DB::beginTransaction();
            
            $synced = 0;
            foreach ($histories as $apiData) {
                $this->syncSingleHistory($apiData);
                $synced++;
            }
            
            DB::commit();
            
            Log::info("✅ Synced PTKP History for karyawan", [
                'karyawan_id' => $absenKaryawanId,
                'count' => $synced
            ]);
            
            return [
                'success' => true,
                'message' => 'PTKP History synced successfully',
                'synced' => $synced
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error syncing PTKP History by karyawan', [
                'karyawan_id' => $absenKaryawanId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 🔄 SYNC BY TAHUN
     */
    public function syncByTahun($tahun)
    {
        try {
            $response = $this->apiService->getPtkpHistoryByTahun($tahun, false);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => 'No PTKP History found for this year'
                ];
            }
            
            $histories = $response['data'] ?? [];
            
            if (empty($histories)) {
                return [
                    'success' => true,
                    'message' => 'No histories to sync',
                    'synced' => 0
                ];
            }
            
            DB::beginTransaction();
            
            $synced = 0;
            foreach ($histories as $apiData) {
                $this->syncSingleHistory($apiData);
                $synced++;
            }
            
            DB::commit();
            
            Log::info("✅ Synced PTKP History for tahun", [
                'tahun' => $tahun,
                'count' => $synced
            ]);
            
            return [
                'success' => true,
                'message' => 'PTKP History synced successfully',
                'synced' => $synced
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error syncing PTKP History by tahun', [
                'tahun' => $tahun,
                'error' => $e->getMessage()
            ]);
            
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
        // Get statistics by year with unique karyawan count
        $byYear = KaryawanPtkpHistory::select('tahun')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COUNT(DISTINCT absen_karyawan_id) as unique_karyawan')
            ->whereNotNull('tahun')
            ->groupBy('tahun')
            ->orderBy('tahun', 'desc')
            ->get()
            ->keyBy('tahun')
            ->map(function($item) {
                return [
                    'count' => $item->count,
                    'unique_karyawan' => $item->unique_karyawan
                ];
            })
            ->toArray();

        return [
            'total_histories' => KaryawanPtkpHistory::count(),
            'total_karyawan' => KaryawanPtkpHistory::distinct('absen_karyawan_id')->count('absen_karyawan_id'),
            'soft_deleted' => KaryawanPtkpHistory::onlyTrashed()->count(),
            'never_synced' => KaryawanPtkpHistory::whereNull('last_synced_at')->count(),
            'last_sync_time' => KaryawanPtkpHistory::max('last_synced_at'),
            'oldest_sync_time' => KaryawanPtkpHistory::whereNotNull('last_synced_at')->min('last_synced_at'),
            'unique_years' => KaryawanPtkpHistory::distinct('tahun')->whereNotNull('tahun')->count(),
            'by_year' => $byYear
        ];
    }
    
    /**
     * 🔍 CHECK SYNC HEALTH
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
    
    /**
     * 🔍 GET MISSING PTKP FOR YEAR
     */
    public function getMissingPtkpForYear($tahun)
    {
        try {
            $response = $this->apiService->getKaryawanMissingPtkp($tahun, false);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch missing PTKP data'
                ];
            }
            
            return [
                'success' => true,
                'data' => $response['data'] ?? [],
                'total' => $response['total'] ?? 0,
                'tahun' => $tahun
            ];
            
        } catch (\Exception $e) {
            Log::error('Error getting missing PTKP', [
                'tahun' => $tahun,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}