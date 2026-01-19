<?php
// app/Services/PtkpSyncService.php (DI APLIKASI GAJI)

namespace App\Services;

use App\Models\ListPtkp;
use App\Services\AttendanceApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PtkpSyncService
{
    protected $apiService;
    
    public function __construct(AttendanceApiService $apiService)
    {
        $this->apiService = $apiService;
    }
    
    /**
     * 🔄 SYNC SEMUA PTKP (FULL SYNC)
     */
    public function syncAll($forceRefresh = false)
    {
        Log::info('🔄 Starting FULL SYNC PTKP...');
        
        $stats = [
            'total_from_api' => 0,
            'new_inserted' => 0,
            'updated' => 0,
            'deleted' => 0,
            'errors' => 0,
            'start_time' => now(),
        ];
        
        try {
            // Step 1: Ambil semua PTKP dari API
            $allPtkpsFromApi = $this->fetchAllPtkpsFromApi();
            $stats['total_from_api'] = count($allPtkpsFromApi);
            
            if (empty($allPtkpsFromApi)) {
                Log::warning('⚠️ No PTKP data from API');
                return [
                    'success' => false,
                    'message' => 'No data from API',
                    'stats' => $stats
                ];
            }
            
            // Step 2: Ambil semua ID dari API
            $apiIds = collect($allPtkpsFromApi)->pluck('id')->toArray();
            
            DB::beginTransaction();
            
            // Step 3: Sync setiap PTKP
            foreach ($allPtkpsFromApi as $apiPtkp) {
                try {
                    $result = $this->syncSinglePtkp($apiPtkp);
                    
                    if ($result['action'] === 'inserted') {
                        $stats['new_inserted']++;
                    } elseif ($result['action'] === 'updated') {
                        $stats['updated']++;
                    }
                    
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error('Error syncing PTKP', [
                        'id' => $apiPtkp['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Step 4: Hapus PTKP yang tidak ada di API lagi
            $deletedCount = $this->deleteRemovedPtkps($apiIds);
            $stats['deleted'] = $deletedCount;
            
            DB::commit();
            
            $stats['end_time'] = now();
            $stats['duration_seconds'] = $stats['start_time']->diffInSeconds($stats['end_time']);
            
            Log::info('✅ FULL SYNC PTKP completed', $stats);
            
            return [
                'success' => true,
                'message' => 'Sync completed successfully',
                'stats' => $stats
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('❌ FULL SYNC PTKP failed', [
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
     * 📥 FETCH ALL PTKP FROM API
     */
    protected function fetchAllPtkpsFromApi()
    {
        $allPtkps = [];
        $page = 1;
        $perPage = 100;
        
        do {
            $response = $this->apiService->getPtkpsPaginated($page, $perPage, false);
            
            if (!$response['success']) {
                Log::error('Failed to fetch PTKP page', ['page' => $page]);
                break;
            }
            
            $data = $response['data'] ?? [];
            $allPtkps = array_merge($allPtkps, $data);
            
            $meta = $response['meta'] ?? [];
            $currentPage = $meta['current_page'] ?? $page;
            $lastPage = $meta['last_page'] ?? $page;
            
            Log::info("📄 Fetched PTKP page {$currentPage}/{$lastPage}", [
                'count' => count($data)
            ]);
            
            $page++;
            
        } while ($page <= ($meta['last_page'] ?? 1));
        
        return $allPtkps;
    }
    
    /**
     * 🔄 SYNC SINGLE PTKP
     */
        protected function syncSinglePtkp(array $apiData)
    {
        $absenPtkpId = $apiData['id'];
        
        // ✅ Debug log
        Log::info('🔄 Starting sync single PTKP', [
            'absen_ptkp_id' => $absenPtkpId,
            'kriteria' => $apiData['kriteria'] ?? 'N/A'
        ]);
        
        // Cari PTKP berdasarkan absen_ptkp_id
        $ptkp = ListPtkp::withTrashed()
            ->where('absen_ptkp_id', $absenPtkpId)
            ->first();
        
        $preparedData = $this->preparePtkpData($apiData);
        
        // ✅ Validasi data sebelum save
        if (empty($preparedData['absen_ptkp_id'])) {
            Log::error('❌ Invalid data: absen_ptkp_id is empty', [
                'api_data' => $apiData,
                'prepared_data' => $preparedData
            ]);
            throw new \Exception('Invalid PTKP data: absen_ptkp_id is required');
        }
        
        try {
            if ($ptkp) {
                // UPDATE existing
                Log::info('📝 Updating existing PTKP', [
                    'local_id' => $ptkp->id,
                    'absen_id' => $absenPtkpId
                ]);
                
                if ($ptkp->trashed()) {
                    $ptkp->restore();
                    Log::info("🔄 Restored PTKP", ['id' => $absenPtkpId]);
                }
                
                $ptkp->update($preparedData);
                
                // ✅ Verify update
                $ptkp->refresh();
                Log::info('✅ PTKP updated successfully', [
                    'local_id' => $ptkp->id,
                    'kriteria' => $ptkp->kriteria,
                    'status' => $ptkp->status,
                    'besaran_ptkp' => $ptkp->besaran_ptkp,
                    'absen_jenis_ter_id' => $ptkp->absen_jenis_ter_id
                ]);
                
                return [
                    'action' => 'updated',
                    'ptkp_id' => $ptkp->id,
                    'absen_ptkp_id' => $absenPtkpId
                ];
                
            } else {
                // INSERT new
                Log::info('➕ Creating new PTKP', [
                    'absen_id' => $absenPtkpId,
                    'data' => $preparedData
                ]);
                
                $ptkp = ListPtkp::create($preparedData);
                
                // ✅ Verify insert
                Log::info("✅ Inserted new PTKP successfully", [
                    'local_id' => $ptkp->id,
                    'absen_id' => $absenPtkpId,
                    'kriteria' => $ptkp->kriteria,
                    'status' => $ptkp->status,
                    'besaran_ptkp' => $ptkp->besaran_ptkp,
                    'absen_jenis_ter_id' => $ptkp->absen_jenis_ter_id
                ]);
                
                return [
                    'action' => 'inserted',
                    'ptkp_id' => $ptkp->id,
                    'absen_ptkp_id' => $absenPtkpId
                ];
            }
        } catch (\Exception $e) {
            Log::error('❌ Error saving PTKP to database', [
                'absen_ptkp_id' => $absenPtkpId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'prepared_data' => $preparedData
            ]);
            throw $e;
        }
    }
    
    /**
     * 🗑️ DELETE PTKP YANG TIDAK ADA DI API
     */
    protected function deleteRemovedPtkps(array $apiIds)
    {
        $toDelete = ListPtkp::whereNotIn('absen_ptkp_id', $apiIds)
            ->whereNull('deleted_at')
            ->get();
        
        $deletedCount = 0;
        
        foreach ($toDelete as $ptkp) {
            // ⚠️ Cek apakah punya relasi (karyawan)
            if ($ptkp->hasKaryawan()) {
                // Jangan hapus jika punya relasi, hanya log warning
                Log::warning("⚠️ Cannot delete PTKP (has relations)", [
                    'local_id' => $ptkp->id,
                    'absen_id' => $ptkp->absen_ptkp_id,
                    'kriteria' => $ptkp->kriteria
                ]);
            } else {
                // Soft delete jika tidak punya relasi
                $ptkp->delete();
                $deletedCount++;
                
                Log::info("🗑️ Soft deleted PTKP", [
                    'local_id' => $ptkp->id,
                    'absen_id' => $ptkp->absen_ptkp_id,
                    'kriteria' => $ptkp->kriteria
                ]);
            }
        }
        
        return $deletedCount;
    }
    
    /**
     * 📋 PREPARE DATA
     */
        protected function preparePtkpData(array $apiData)
    {
        // ✅ Debug: Log data yang diterima dari API
        Log::info('📥 Preparing PTKP data from API', [
            'api_data' => $apiData
        ]);
        
        $preparedData = [
            'absen_ptkp_id' => $apiData['id'],
            'kriteria' => $apiData['kriteria'] ?? null,
            'status' => $apiData['status'] ?? null,
            'besaran_ptkp' => $apiData['besaran_ptkp'] ?? null,
            
            // ✅ PENTING: Pastikan field ini ada di response API
            // Bisa jadi 'jenis_ter_id' atau 'absen_jenis_ter_id'
            'absen_jenis_ter_id' => $apiData['absen_jenis_ter_id'] ?? $apiData['jenis_ter_id'] ?? null,
            
            'last_synced_at' => now(),
            'sync_metadata' => json_encode([
                'synced_from' => 'api',
                'api_id' => $apiData['id'],
                'synced_at' => now()->toISOString(),
                'raw_api_data' => $apiData // ✅ Simpan raw data untuk debug
            ])
        ];
        
        // ✅ Debug: Log data yang akan disimpan
        Log::info('💾 Prepared data for database', [
            'prepared_data' => $preparedData
        ]);
        
        return $preparedData;
    }

    
    /**
     * 🔄 SYNC SPECIFIC PTKP BY ID
     */
    public function syncById($absenPtkpId)
    {
        try {
            $response = $this->apiService->getPtkp($absenPtkpId, false);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => 'PTKP not found in API'
                ];
            }
            
            $apiData = $response['data'];
            
            DB::beginTransaction();
            $result = $this->syncSinglePtkp($apiData);
            DB::commit();
            
            Log::info("✅ Synced single PTKP", $result);
            
            return [
                'success' => true,
                'message' => 'PTKP synced successfully',
                'action' => $result['action'],
                'data' => $result
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error syncing single PTKP', [
                'absen_id' => $absenPtkpId,
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
        return [
            'total_ptkp' => ListPtkp::count(),
            'soft_deleted' => ListPtkp::onlyTrashed()->count(),
            'never_synced' => ListPtkp::whereNull('last_synced_at')->count(),
            'last_sync_time' => ListPtkp::max('last_synced_at'),
            'oldest_sync_time' => ListPtkp::min('last_synced_at'),
        ];
    }
    
    /**
     * 🔍 CHECK SYNC HEALTH
     */
    public function checkSyncHealth($hoursThreshold = 24)
    {
        $needsSync = ListPtkp::needsSync($hoursThreshold)->count();
        $total = ListPtkp::count();
        
        return [
            'healthy' => $needsSync === 0,
            'needs_sync_count' => $needsSync,
            'total_count' => $total,
            'percentage_synced' => $total > 0 ? round((($total - $needsSync) / $total) * 100, 2) : 100,
            'threshold_hours' => $hoursThreshold
        ];
    }
}