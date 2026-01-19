<?php
// app/Services/RangeBrutoSyncService.php

namespace App\Services;

use App\Models\RangeBruto;
use App\Models\JenisTer;
use App\Services\AttendanceApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RangeBrutoSyncService
{
    protected $apiService;
    
    public function __construct(AttendanceApiService $apiService)
    {
        $this->apiService = $apiService;
    }
    
    /**
     * 🔄 SYNC ALL RANGE BRUTO
     */
    public function syncAll($forceRefresh = false)
    {
        Log::info('Starting Range Bruto sync', ['force' => $forceRefresh]);
        
        try {
            // ✅ Fetch dari API ABSEN
            $apiResult = $this->apiService->getAllRangeBrutos(!$forceRefresh);
            
            if (!$apiResult['success']) {
                Log::error('Failed to fetch range bruto from API', [
                    'message' => $apiResult['message']
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to fetch data from API: ' . $apiResult['message']
                ];
            }
            
            $apiData = $apiResult['data'];
            
            if (empty($apiData)) {
                Log::warning('No range bruto data received from API');
                
                return [
                    'success' => true,
                    'message' => 'No data to sync',
                    'stats' => [
                        'new_inserted' => 0,
                        'updated' => 0,
                        'deleted' => 0,
                        'unchanged' => 0,
                        'skipped_no_jenis_ter' => 0,
                        'total_api' => 0,
                        'total_local' => RangeBruto::count()
                    ]
                ];
            }
            
            // ✅ Process sync
            $stats = $this->processSyncData($apiData);
            
            Log::info('Range Bruto sync completed', $stats);
            
            return [
                'success' => true,
                'message' => 'Sync completed successfully',
                'stats' => $stats,
                'synced_at' => now()->toDateTimeString()
            ];
            
        } catch (\Exception $e) {
            Log::error('Range Bruto sync failed', [
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
            'skipped_no_jenis_ter' => 0,
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
                $absenJenisTerId = $item['jenis_ter_id'];
                
                // ✅ IMPORTANT: Cari local_jenis_ter_id dari absen_jenis_ter_id
                $localJenisTer = JenisTer::where('absen_jenis_ter_id', $absenJenisTerId)->first();
                
                if (!$localJenisTer) {
                    Log::warning('Jenis TER not found locally, skipping range bruto', [
                        'absen_range_bruto_id' => $absenId,
                        'absen_jenis_ter_id' => $absenJenisTerId
                    ]);
                    
                    $stats['skipped_no_jenis_ter']++;
                    continue;
                }
                
                $existing = RangeBruto::withTrashed()
                    ->where('absen_range_bruto_id', $absenId)
                    ->first();
                
                $dataToSync = [
                    'absen_jenis_ter_id' => $absenJenisTerId,
                    'local_jenis_ter_id' => $localJenisTer->id,
                    'min_bruto' => $item['min_bruto'],
                    'max_bruto' => $item['max_bruto'],
                    'ter' => $item['ter'],
                    'last_synced_at' => $syncedAt,
                    'sync_metadata' => json_encode([
                        'created_at' => $item['created_at'] ?? null,
                        'updated_at' => $item['updated_at'] ?? null,
                        'jenis_ter' => $item['jenis_ter']['jenis_ter'] ?? null,
                    ])
                ];
                
                if (!$existing) {
                    // INSERT NEW
                    RangeBruto::create(array_merge([
                        'absen_range_bruto_id' => $absenId,
                    ], $dataToSync));
                    
                    $stats['new_inserted']++;
                    
                    Log::info('New Range Bruto inserted', [
                        'absen_id' => $absenId,
                        'jenis_ter_id' => $absenJenisTerId,
                        'min_bruto' => $item['min_bruto'],
                        'ter' => $item['ter']
                    ]);
                    
                } else {
                    // UPDATE EXISTING
                    $hasChanges = false;
                    
                    // Check changes
                    if ($existing->absen_jenis_ter_id !== $absenJenisTerId ||
                        $existing->local_jenis_ter_id !== $localJenisTer->id ||
                        $existing->min_bruto != $item['min_bruto'] ||
                        $existing->max_bruto != $item['max_bruto'] ||
                        $existing->ter != $item['ter']) {
                        $hasChanges = true;
                    }
                    
                    // Restore jika soft deleted
                    if ($existing->trashed()) {
                        $existing->restore();
                        $hasChanges = true;
                        
                        Log::info('Range Bruto restored from soft delete', [
                            'absen_id' => $absenId
                        ]);
                    }
                    
                    if ($hasChanges) {
                        $existing->update($dataToSync);
                        
                        $stats['updated']++;
                        
                        Log::info('Range Bruto updated', [
                            'absen_id' => $absenId,
                            'changes' => [
                                'old_min_bruto' => $existing->getOriginal('min_bruto'),
                                'new_min_bruto' => $item['min_bruto'],
                                'old_ter' => $existing->getOriginal('ter'),
                                'new_ter' => $item['ter']
                            ]
                        ]);
                    } else {
                        // Update last_synced_at saja
                        $existing->update(['last_synced_at' => $syncedAt]);
                        $stats['unchanged']++;
                    }
                }
            }
            
            // ✅ SOFT DELETE yang tidak ada di API
            $deletedCount = RangeBruto::whereNotIn('absen_range_bruto_id', $apiIds)
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => $syncedAt,
                    'last_synced_at' => $syncedAt
                ]);
            
            $stats['deleted'] = $deletedCount;
            
            if ($deletedCount > 0) {
                Log::warning('Range Bruto soft deleted', [
                    'count' => $deletedCount
                ]);
            }
            
            $stats['total_local'] = RangeBruto::count();
            
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
    public function syncById($absenRangeBrutoId)
    {
        Log::info('Syncing single Range Bruto', ['absen_id' => $absenRangeBrutoId]);
        
        try {
            // Fetch single dari API
            $apiResult = $this->apiService->getRangeBruto($absenRangeBrutoId, false);
            
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
                $absenJenisTerId = $item['jenis_ter_id'];
                
                // Find local jenis ter
                $localJenisTer = JenisTer::where('absen_jenis_ter_id', $absenJenisTerId)->first();
                
                if (!$localJenisTer) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'message' => 'Jenis TER not found locally. Please sync Jenis TER first.'
                    ];
                }
                
                $existing = RangeBruto::withTrashed()
                    ->where('absen_range_bruto_id', $absenRangeBrutoId)
                    ->first();
                
                $dataToSync = [
                    'absen_jenis_ter_id' => $absenJenisTerId,
                    'local_jenis_ter_id' => $localJenisTer->id,
                    'min_bruto' => $item['min_bruto'],
                    'max_bruto' => $item['max_bruto'],
                    'ter' => $item['ter'],
                    'last_synced_at' => $syncedAt,
                    'sync_metadata' => json_encode([
                    'created_at' => $item['created_at'] ?? null,
                        'updated_at' => $item['updated_at'] ?? null,
                        'jenis_ter' => $item['jenis_ter']['jenis_ter'] ?? null,
                    ])
                ];
                
                if (!$existing) {
                    // INSERT
                    $rangeBruto = RangeBruto::create(array_merge([
                        'absen_range_bruto_id' => $item['id'],
                    ], $dataToSync));
                    
                    DB::commit();
                    
                    return [
                        'success' => true,
                        'message' => 'Range Bruto inserted',
                        'action' => 'inserted',
                        'data' => $rangeBruto
                    ];
                } else {
                    // UPDATE
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    
                    $existing->update($dataToSync);
                    
                    DB::commit();
                    
                    return [
                        'success' => true,
                        'message' => 'Range Bruto updated',
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
                'absen_id' => $absenRangeBrutoId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 🔄 SYNC BY JENIS TER
     */
    public function syncByJenisTer($absenJenisTerId)
    {
        Log::info('Syncing Range Bruto by Jenis TER', ['absen_jenis_ter_id' => $absenJenisTerId]);
        
        try {
            // Fetch by jenis ter dari API
            $apiResult = $this->apiService->getRangeBrutoByJenisTer($absenJenisTerId, false);
            
            if (!$apiResult['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch from API: ' . $apiResult['message']
                ];
            }
            
            $apiData = $apiResult['data'];
            
            if (empty($apiData)) {
                return [
                    'success' => true,
                    'message' => 'No range bruto found for this Jenis TER',
                    'stats' => [
                        'new_inserted' => 0,
                        'updated' => 0,
                        'total' => 0
                    ]
                ];
            }
            
            // Find local jenis ter
            $localJenisTer = JenisTer::where('absen_jenis_ter_id', $absenJenisTerId)->first();
            
            if (!$localJenisTer) {
                return [
                    'success' => false,
                    'message' => 'Jenis TER not found locally. Please sync Jenis TER first.'
                ];
            }
            
            $stats = [
                'new_inserted' => 0,
                'updated' => 0,
                'total' => count($apiData)
            ];
            
            DB::beginTransaction();
            
            try {
                $syncedAt = now();
                
                foreach ($apiData as $item) {
                    $absenId = $item['id'];
                    
                    $existing = RangeBruto::withTrashed()
                        ->where('absen_range_bruto_id', $absenId)
                        ->first();
                    
                    $dataToSync = [
                        'absen_jenis_ter_id' => $absenJenisTerId,
                        'local_jenis_ter_id' => $localJenisTer->id,
                        'min_bruto' => $item['min_bruto'],
                        'max_bruto' => $item['max_bruto'],
                        'ter' => $item['ter'],
                        'last_synced_at' => $syncedAt,
                        'sync_metadata' => json_encode([
                            'created_at' => $item['created_at'] ?? null,
                            'updated_at' => $item['updated_at'] ?? null,
                        ])
                    ];
                    
                    if (!$existing) {
                        RangeBruto::create(array_merge([
                            'absen_range_bruto_id' => $absenId,
                        ], $dataToSync));
                        
                        $stats['new_inserted']++;
                    } else {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }
                        
                        $existing->update($dataToSync);
                        $stats['updated']++;
                    }
                }
                
                DB::commit();
                
                return [
                    'success' => true,
                    'message' => 'Sync by Jenis TER completed',
                    'stats' => $stats
                ];
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Sync by Jenis TER failed', [
                'absen_jenis_ter_id' => $absenJenisTerId,
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
        $total = RangeBruto::count();
        $active = RangeBruto::whereNull('deleted_at')->count();
        $deleted = RangeBruto::onlyTrashed()->count();
        $needsSync = RangeBruto::needsSync(24)->count();
        
        $lastSync = RangeBruto::max('last_synced_at');
        $neverSynced = RangeBruto::whereNull('last_synced_at')->count();
       
        // TER Statistics
        $avgTer = RangeBruto::avg('ter');
        $minTer = RangeBruto::min('ter');
        $maxTer = RangeBruto::max('ter');
        
        return [
            'total' => $total,
            'active' => $active,
            'deleted' => $deleted,
            'needs_sync' => $needsSync,
            'never_synced' => $neverSynced,
            'last_sync' => $lastSync ? Carbon::parse($lastSync)->format('d M Y H:i:s') : 'Never',
            'last_sync_human' => $lastSync ? Carbon::parse($lastSync)->diffForHumans() : 'Never',
            'ter_stats' => [
                'avg' => round($avgTer, 2),
                'min' => $minTer,
                'max' => $maxTer,
            ]
        ];
    }
    
    /**
     * 🏥 CHECK SYNC HEALTH
     */
    public function checkSyncHealth($hoursThreshold = 24)
    {
        $needsSync = RangeBruto::needsSync($hoursThreshold)->count();
        $total = RangeBruto::count();
        
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
                return "CRITICAL: {$needsSync} Range Bruto need immediate sync!";
            case 'warning':
                return "WARNING: {$needsSync} Range Bruto need sync soon.";
            default:
                return "All Range Bruto are up to date.";
        }
    }
}