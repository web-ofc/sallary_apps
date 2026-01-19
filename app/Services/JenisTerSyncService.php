<?php
// app/Services/JenisTerSyncService.php

namespace App\Services;

use App\Models\JenisTer;
use App\Services\AttendanceApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class JenisTerSyncService
{
    protected $apiService;
    
    public function __construct(AttendanceApiService $apiService)
    {
        $this->apiService = $apiService;
    }
    
    /**
     * 🔄 SYNC ALL JENIS TER
     */
    public function syncAll($forceRefresh = false)
    {
        Log::info('Starting Jenis TER sync', ['force' => $forceRefresh]);
        
        try {
            // ✅ Fetch dari API ABSEN
            $apiResult = $this->apiService->getAllJenisTers(!$forceRefresh);
            
            if (!$apiResult['success']) {
                Log::error('Failed to fetch jenis ter from API', [
                    'message' => $apiResult['message']
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to fetch data from API: ' . $apiResult['message']
                ];
            }
            
            $apiData = $apiResult['data'];
            
            if (empty($apiData)) {
                Log::warning('No jenis ter data received from API');
                
                return [
                    'success' => true,
                    'message' => 'No data to sync',
                    'stats' => [
                        'new_inserted' => 0,
                        'updated' => 0,
                        'deleted' => 0,
                        'unchanged' => 0,
                        'total_api' => 0,
                        'total_local' => JenisTer::count()
                    ]
                ];
            }
            
            // ✅ Process sync
            $stats = $this->processSyncData($apiData);
            
            Log::info('Jenis TER sync completed', $stats);
            
            return [
                'success' => true,
                'message' => 'Sync completed successfully',
                'stats' => $stats,
                'synced_at' => now()->toDateTimeString()
            ];
            
        } catch (\Exception $e) {
            Log::error('Jenis TER sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 🔄 PROCESS SYNC DATA
     */
    protected function processSyncData(array $apiData)
    {
        $stats = [
            'new_inserted' => 0,
            'updated' => 0,
            'deleted' => 0,
            'unchanged' => 0,
            'total_api' => count($apiData),
            'total_local' => 0
        ];
        
        DB::beginTransaction();
        
        try {
            $apiIds = collect($apiData)->pluck('id')->toArray();
            $syncedAt = now();
            
            // ✅ INSERT OR UPDATE
            foreach ($apiData as $item) {
                $absenId = $item['id'];
                $jenisTer = $item['jenis_ter'];
                
                $existing = JenisTer::withTrashed()
                    ->where('absen_jenis_ter_id', $absenId)
                    ->first();
                
                if (!$existing) {
                    // INSERT NEW
                    JenisTer::create([
                        'absen_jenis_ter_id' => $absenId,
                        'jenis_ter' => $jenisTer,
                        'last_synced_at' => $syncedAt,
                        'sync_metadata' => json_encode([
                            'created_at' => $item['created_at'] ?? null,
                            'updated_at' => $item['updated_at'] ?? null,
                        ])
                    ]);
                    
                    $stats['new_inserted']++;
                    
                    Log::info('New Jenis TER inserted', [
                        'absen_id' => $absenId,
                        'jenis_ter' => $jenisTer
                    ]);
                    
                } else {
                    // UPDATE EXISTING
                    $hasChanges = false;
                    
                    if ($existing->jenis_ter !== $jenisTer) {
                        $hasChanges = true;
                    }
                    
                    // Restore jika soft deleted
                    if ($existing->trashed()) {
                        $existing->restore();
                        $hasChanges = true;
                        
                        Log::info('Jenis TER restored from soft delete', [
                            'absen_id' => $absenId
                        ]);
                    }
                    
                    if ($hasChanges) {
                        $existing->update([
                            'jenis_ter' => $jenisTer,
                            'last_synced_at' => $syncedAt,
                            'sync_metadata' => json_encode([
                                'created_at' => $item['created_at'] ?? null,
                                'updated_at' => $item['updated_at'] ?? null,
                            ])
                        ]);
                        
                        $stats['updated']++;
                        
                        Log::info('Jenis TER updated', [
                            'absen_id' => $absenId,
                            'changes' => [
                                'old_jenis_ter' => $existing->getOriginal('jenis_ter'),
                                'new_jenis_ter' => $jenisTer
                            ]
                        ]);
                    } else {
                        // Update last_synced_at saja
                        $existing->update(['last_synced_at' => $syncedAt]);
                        $stats['unchanged']++;
                    }
                }
            }
            
            // ✅ SOFT DELETE yang tidak ada di API (dianggap dihapus)
            $deletedCount = JenisTer::whereNotIn('absen_jenis_ter_id', $apiIds)
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => $syncedAt,
                    'last_synced_at' => $syncedAt
                ]);
            
            $stats['deleted'] = $deletedCount;
            
            if ($deletedCount > 0) {
                Log::warning('Jenis TER soft deleted', [
                    'count' => $deletedCount,
                    'api_ids' => $apiIds
                ]);
            }
            
            $stats['total_local'] = JenisTer::count();
            
            DB::commit();
            
            return $stats;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Process sync data failed', [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * 🔄 SYNC BY ID (Single)
     */
    public function syncById($absenJenisTerId)
    {
        Log::info('Syncing single Jenis TER', ['absen_id' => $absenJenisTerId]);
        
        try {
            // Fetch single dari API
            $apiResult = $this->apiService->getJenisTer($absenJenisTerId, false);
            
            if (!$apiResult['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch from API: ' . $apiResult['message']
                ];
            }
            
            $item = $apiResult['data'];
            $syncedAt = now();
            
            DB::beginTransaction();
            
            try {
                $existing = JenisTer::withTrashed()
                    ->where('absen_jenis_ter_id', $absenJenisTerId)
                    ->first();
                
                if (!$existing) {
                    // INSERT
                    $jenisTer = JenisTer::create([
                        'absen_jenis_ter_id' => $item['id'],
                        'jenis_ter' => $item['jenis_ter'],
                        'last_synced_at' => $syncedAt,
                        'sync_metadata' => json_encode([
                            'created_at' => $item['created_at'] ?? null,
                            'updated_at' => $item['updated_at'] ?? null,
                        ])
                    ]);
                    
                    DB::commit();
                    
                    return [
                        'success' => true,
                        'message' => 'Jenis TER inserted',
                        'action' => 'inserted',
                        'data' => $jenisTer
                    ];
                } else {
                    // UPDATE
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    
                    $existing->update([
                        'jenis_ter' => $item['jenis_ter'],
                        'last_synced_at' => $syncedAt,
                        'sync_metadata' => json_encode([
                            'created_at' => $item['created_at'] ?? null,
                            'updated_at' => $item['updated_at'] ?? null,
                        ])
                    ]);
                    
                    DB::commit();
                    
                    return [
                        'success' => true,
                        'message' => 'Jenis TER updated',
                        'action' => 'updated',
                        'data' => $existing->fresh()
                    ];
                }
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Sync by ID failed', [
                'absen_id' => $absenJenisTerId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 📊 GET SYNC STATISTICS
     */
    public function getSyncStats()
    {
        $total = JenisTer::count();
        $active = JenisTer::whereNull('deleted_at')->count();
        $deleted = JenisTer::onlyTrashed()->count();
        $needsSync = JenisTer::needsSync(24)->count();
        
        $lastSync = JenisTer::max('last_synced_at');
        $neverSynced = JenisTer::whereNull('last_synced_at')->count();
        
        return [
            'total' => $total,
            'active' => $active,
            'deleted' => $deleted,
            'needs_sync' => $needsSync,
            'never_synced' => $neverSynced,
            'last_sync' => $lastSync ? Carbon::parse($lastSync)->format('d M Y H:i:s') : 'Never',
            'last_sync_human' => $lastSync ? Carbon::parse($lastSync)->diffForHumans() : 'Never',
        ];
    }
    
    /**
     * 🏥 CHECK SYNC HEALTH
     */
    public function checkSyncHealth($hoursThreshold = 24)
    {
        $needsSync = JenisTer::needsSync($hoursThreshold)->count();
        $total = JenisTer::count();
        
        $healthPercentage = $total > 0 ? (($total - $needsSync) / $total) * 100 : 100;
        
        $status = 'healthy';
        if ($healthPercentage < 50) {
            $status = 'critical';
        } elseif ($healthPercentage < 80) {
            $status = 'warning';
        }
        
        return [
            'status' => $status,
            'health_percentage' => round($healthPercentage, 2),
            'needs_sync_count' => $needsSync,
            'total_count' => $total,
            'threshold_hours' => $hoursThreshold,
            'message' => $this->getHealthMessage($status, $needsSync)
        ];
    }
    
    protected function getHealthMessage($status, $needsSync)
    {
        switch ($status) {
            case 'critical':
                return "CRITICAL: {$needsSync} Jenis TER need immediate sync!";
            case 'warning':
                return "WARNING: {$needsSync} Jenis TER need sync soon.";
            default:
                return "All Jenis TER are up to date.";
        }
    }
}