<?php
// app/Services/KaryawanSyncService.php (DI APLIKASI GAJI)

namespace App\Services;

use App\Models\Karyawan;
use App\Services\AttendanceApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KaryawanSyncService
{
    protected $apiService;
    
    public function __construct(AttendanceApiService $apiService)
    {
        $this->apiService = $apiService;
    }
    
    /**
     * 🔄 SYNC SEMUA KARYAWAN (FULL SYNC)
     * Ambil semua data dari API ABSEN, sync ke local DB
     */
    public function syncAll($forceRefresh = false)
    {
        Log::info('🔄 Starting FULL SYNC karyawan...');
        
        $stats = [
            'total_from_api' => 0,
            'new_inserted' => 0,
            'updated' => 0,
            'deleted' => 0,
            'errors' => 0,
            'start_time' => now(),
        ];
        
        try {
            // Step 1: Ambil semua karyawan dari API (paginated)
            $allKaryawanFromApi = $this->fetchAllKaryawanFromApi();
            $stats['total_from_api'] = count($allKaryawanFromApi);
            
            if (empty($allKaryawanFromApi)) {
                Log::warning('⚠️ No karyawan data from API');
                return [
                    'success' => false,
                    'message' => 'No data from API',
                    'stats' => $stats
                ];
            }
            
            // Step 2: Ambil semua ID dari API
            $apiIds = collect($allKaryawanFromApi)->pluck('id')->toArray();
            
            DB::beginTransaction();
            
            // Step 3: Sync setiap karyawan
            foreach ($allKaryawanFromApi as $apiKaryawan) {
                try {
                    $result = $this->syncSingleKaryawan($apiKaryawan);
                    
                    if ($result['action'] === 'inserted') {
                        $stats['new_inserted']++;
                    } elseif ($result['action'] === 'updated') {
                        $stats['updated']++;
                    }
                    
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error('Error syncing karyawan', [
                        'id' => $apiKaryawan['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Step 4: Hapus karyawan yang tidak ada di API lagi (soft delete)
            $deletedCount = $this->deleteRemovedKaryawan($apiIds);
            $stats['deleted'] = $deletedCount;
            
            DB::commit();
            
            $stats['end_time'] = now();
            $stats['duration_seconds'] = $stats['start_time']->diffInSeconds($stats['end_time']);
            
            Log::info('✅ FULL SYNC completed', $stats);
            
            return [
                'success' => true,
                'message' => 'Sync completed successfully',
                'stats' => $stats
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('❌ FULL SYNC failed', [
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
     * 📥 FETCH ALL KARYAWAN FROM API (with pagination)
     */
    protected function fetchAllKaryawanFromApi()
    {
        $allKaryawan = [];
        $page = 1;
        $perPage = 100; // Max per request
        
        do {
            $response = $this->apiService->getKaryawanPaginated($page, $perPage, false);
            
            if (!$response['success']) {
                Log::error('Failed to fetch page', ['page' => $page]);
                break;
            }
            
            $data = $response['data'] ?? [];
            $allKaryawan = array_merge($allKaryawan, $data);
            
            $meta = $response['meta'] ?? [];
            $currentPage = $meta['current_page'] ?? $page;
            $lastPage = $meta['last_page'] ?? $page;
            
            Log::info("📄 Fetched page {$currentPage}/{$lastPage}", [
                'count' => count($data)
            ]);
            
            $page++;
            
        } while ($page <= ($meta['last_page'] ?? 1));
        
        return $allKaryawan;
    }
    
    /**
     * 🔄 SYNC SINGLE KARYAWAN
     * Insert baru atau update existing
     */
    protected function syncSingleKaryawan(array $apiData)
    {
        $absenKaryawanId = $apiData['id'];
        
        // Cari karyawan berdasarkan absen_karyawan_id
        $karyawan = Karyawan::withTrashed()
            ->where('absen_karyawan_id', $absenKaryawanId)
            ->first();
        
        $preparedData = $this->prepareKaryawanData($apiData);
        
        if ($karyawan) {
            // UPDATE existing
            
            // Jika sebelumnya soft deleted, restore dulu
            if ($karyawan->trashed()) {
                $karyawan->restore();
                Log::info("🔄 Restored karyawan", ['id' => $absenKaryawanId]);
            }
            
            $karyawan->update($preparedData);
            
            return [
                'action' => 'updated',
                'karyawan_id' => $karyawan->id,
                'absen_karyawan_id' => $absenKaryawanId
            ];
            
        } else {
            // INSERT new
            $karyawan = Karyawan::create($preparedData);
            
            Log::info("✅ Inserted new karyawan", [
                'local_id' => $karyawan->id,
                'absen_id' => $absenKaryawanId,
                'nama' => $karyawan->nama_lengkap
            ]);
            
            return [
                'action' => 'inserted',
                'karyawan_id' => $karyawan->id,
                'absen_karyawan_id' => $absenKaryawanId
            ];
        }
    }
    
    /**
     * 🗑️ DELETE KARYAWAN YANG TIDAK ADA DI API
     * Soft delete karyawan yang sudah tidak ada di API ABSEN
     */
    protected function deleteRemovedKaryawan(array $apiIds)
    {
        // Ambil semua ID local yang tidak ada di API lagi
        $toDelete = Karyawan::whereNotIn('absen_karyawan_id', $apiIds)
            ->whereNull('deleted_at')
            ->get();
        
        $deletedCount = 0;
        
        foreach ($toDelete as $karyawan) {
            // ⚠️ PENTING: Cek dulu apakah punya payroll
            if ($karyawan->hasPayrolls()) {
                // Jangan hapus jika punya payroll, hanya tandai resign
                $karyawan->update([
                    'status_resign' => true,
                    'last_synced_at' => now()
                ]);
                
                Log::warning("⚠️ Karyawan marked as resigned (has payroll)", [
                    'local_id' => $karyawan->id,
                    'absen_id' => $karyawan->absen_karyawan_id,
                    'nama' => $karyawan->nama_lengkap
                ]);
            } else {
                // Soft delete jika tidak punya payroll
                $karyawan->delete();
                $deletedCount++;
                
                Log::info("🗑️ Soft deleted karyawan", [
                    'local_id' => $karyawan->id,
                    'absen_id' => $karyawan->absen_karyawan_id,
                    'nama' => $karyawan->nama_lengkap
                ]);
            }
        }
        
        return $deletedCount;
    }
    
    /**
     * 📋 PREPARE DATA dari API untuk disimpan ke local DB
     */
    protected function prepareKaryawanData(array $apiData)
    {
        return [
            'absen_karyawan_id' => $apiData['id'],
            'nik' => $apiData['nik'] ?? null,
            'nama_lengkap' => $apiData['nama_lengkap'] ?? 'Unknown',
            'email_pribadi' => $apiData['email_pribadi'] ?? null,
            'telp_pribadi' => $apiData['telp_pribadi'] ?? null,
            'join_date' => $apiData['join_date'] ?? null,
            'tempat_tanggal_lahir' => $apiData['tempat_tanggal_lahir'] ?? null,
            'jenis_kelamin' => $apiData['jenis_kelamin'] ?? null,
            'status_pernikahan' => $apiData['status_pernikahan'] ?? null,
            'alamat' => $apiData['alamat'] ?? null,
            'no_ktp' => $apiData['no_ktp'] ?? null,
            'file_pas_foto' => $apiData['file_pas_foto'] ?? null,
            'file_ktp' => $apiData['file_ktp'] ?? null,
            'file_kk' => $apiData['file_kk'] ?? null,
            'file_ijazah' => $apiData['file_ijazah'] ?? null,
            'file_npwp' => $apiData['file_npwp'] ?? null,
            'file_skck' => $apiData['file_skck'] ?? null,
            'ptkp_id' => $apiData['ptkp_id'] ?? null,
            'status_resign' => $apiData['status_resign'] ?? false,
            'last_synced_at' => now(),
            'sync_metadata' => json_encode([
                'synced_from' => 'api',
                'api_id' => $apiData['id'],
                'synced_at' => now()->toISOString(),
            ])
        ];
    }
    
    /**
     * 🔄 SYNC SPECIFIC KARYAWAN BY ID
     * Sync satu karyawan aja by absen_karyawan_id
     */
    public function syncById($absenKaryawanId)
    {
        try {
            // Ambil data dari API
            $response = $this->apiService->getKaryawan($absenKaryawanId, false);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => 'Karyawan not found in API'
                ];
            }
            
            $apiData = $response['data'];
            
            DB::beginTransaction();
            $result = $this->syncSingleKaryawan($apiData);
            DB::commit();
            
            Log::info("✅ Synced single karyawan", $result);
            
            return [
                'success' => true,
                'message' => 'Karyawan synced successfully',
                'action' => $result['action'],
                'data' => $result
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error syncing single karyawan', [
                'absen_id' => $absenKaryawanId,
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
            'total_karyawan' => Karyawan::count(),
            'active_karyawan' => Karyawan::active()->count(),
            'resigned_karyawan' => Karyawan::resigned()->count(),
            'soft_deleted' => Karyawan::onlyTrashed()->count(),
            'never_synced' => Karyawan::whereNull('last_synced_at')->count(),
            'last_sync_time' => Karyawan::max('last_synced_at'),
            'oldest_sync_time' => Karyawan::min('last_synced_at'),
        ];
    }
    
    /**
     * 🔍 CHECK SYNC HEALTH
     * Cek apakah ada karyawan yang perlu di-sync ulang
     */
    public function checkSyncHealth($hoursThreshold = 24)
    {
        $needsSync = Karyawan::needsSync($hoursThreshold)->count();
        $total = Karyawan::count();
        
        return [
            'healthy' => $needsSync === 0,
            'needs_sync_count' => $needsSync,
            'total_count' => $total,
            'percentage_synced' => $total > 0 ? round((($total - $needsSync) / $total) * 100, 2) : 100,
            'threshold_hours' => $hoursThreshold
        ];
    }
}