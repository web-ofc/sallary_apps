<?php
// app/Services/CompanySyncService.php (DI APLIKASI GAJI)

namespace App\Services;

use App\Models\Company;
use App\Services\AttendanceApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompanySyncService
{
    protected $apiService;
    
    public function __construct(AttendanceApiService $apiService)
    {
        $this->apiService = $apiService;
    }
    
    /**
     * 🔄 SYNC SEMUA COMPANIES (FULL SYNC)
     */
    public function syncAll($forceRefresh = false)
    {
        Log::info('🔄 Starting FULL SYNC companies...');
        
        $stats = [
            'total_from_api' => 0,
            'new_inserted' => 0,
            'updated' => 0,
            'deleted' => 0,
            'errors' => 0,
            'start_time' => now(),
        ];
        
        try {
            // Step 1: Ambil semua companies dari API
            $allCompaniesFromApi = $this->fetchAllCompaniesFromApi();
            $stats['total_from_api'] = count($allCompaniesFromApi);
            
            if (empty($allCompaniesFromApi)) {
                Log::warning('⚠️ No companies data from API');
                return [
                    'success' => false,
                    'message' => 'No data from API',
                    'stats' => $stats
                ];
            }
            
            // Step 2: Ambil semua ID dari API
            $apiIds = collect($allCompaniesFromApi)->pluck('id')->toArray();
            
            DB::beginTransaction();
            
            // Step 3: Sync setiap company
            foreach ($allCompaniesFromApi as $apiCompany) {
                try {
                    $result = $this->syncSingleCompany($apiCompany);
                    
                    if ($result['action'] === 'inserted') {
                        $stats['new_inserted']++;
                    } elseif ($result['action'] === 'updated') {
                        $stats['updated']++;
                    }
                    
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error('Error syncing company', [
                        'id' => $apiCompany['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Step 4: Hapus company yang tidak ada di API lagi
            $deletedCount = $this->deleteRemovedCompanies($apiIds);
            $stats['deleted'] = $deletedCount;
            
            DB::commit();
            
            $stats['end_time'] = now();
            $stats['duration_seconds'] = $stats['start_time']->diffInSeconds($stats['end_time']);
            
            Log::info('✅ FULL SYNC companies completed', $stats);
            
            return [
                'success' => true,
                'message' => 'Sync completed successfully',
                'stats' => $stats
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('❌ FULL SYNC companies failed', [
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
     * 📥 FETCH ALL COMPANIES FROM API
     */
    protected function fetchAllCompaniesFromApi()
    {
        $allCompanies = [];
        $page = 1;
        $perPage = 100;
        
        do {
            $response = $this->apiService->getCompaniesPaginated($page, $perPage, false);
            
            if (!$response['success']) {
                Log::error('Failed to fetch companies page', ['page' => $page]);
                break;
            }
            
            $data = $response['data'] ?? [];
            $allCompanies = array_merge($allCompanies, $data);
            
            $meta = $response['meta'] ?? [];
            $currentPage = $meta['current_page'] ?? $page;
            $lastPage = $meta['last_page'] ?? $page;
            
            Log::info("📄 Fetched companies page {$currentPage}/{$lastPage}", [
                'count' => count($data)
            ]);
            
            $page++;
            
        } while ($page <= ($meta['last_page'] ?? 1));
        
        return $allCompanies;
    }
    
    /**
     * 🔄 SYNC SINGLE COMPANY
     */
    protected function syncSingleCompany(array $apiData)
    {
        $absenCompanyId = $apiData['id'];
        
        // Cari company berdasarkan absen_company_id
        $company = Company::withTrashed()
            ->where('absen_company_id', $absenCompanyId)
            ->first();
        
        $preparedData = $this->prepareCompanyData($apiData);
        
        if ($company) {
            // UPDATE existing
            
            if ($company->trashed()) {
                $company->restore();
                Log::info("🔄 Restored company", ['id' => $absenCompanyId]);
            }
            
            $company->update($preparedData);
            
            return [
                'action' => 'updated',
                'company_id' => $company->id,
                'absen_company_id' => $absenCompanyId
            ];
            
        } else {
            // INSERT new
            $company = Company::create($preparedData);
            
            Log::info("✅ Inserted new company", [
                'local_id' => $company->id,
                'absen_id' => $absenCompanyId,
                'name' => $company->company_name
            ]);
            
            return [
                'action' => 'inserted',
                'company_id' => $company->id,
                'absen_company_id' => $absenCompanyId
            ];
        }
    }
    
    /**
     * 🗑️ DELETE COMPANIES YANG TIDAK ADA DI API
     */
    protected function deleteRemovedCompanies(array $apiIds)
    {
        $toDelete = Company::whereNotIn('absen_company_id', $apiIds)
            ->whereNull('deleted_at')
            ->get();
        
        $deletedCount = 0;
        
        foreach ($toDelete as $company) {
            // ⚠️ Cek apakah punya relasi (karyawan/payroll)
            if ($company->hasKaryawan() || $company->hasPayrolls()) {
                // Jangan hapus jika punya relasi, hanya log warning
                Log::warning("⚠️ Cannot delete company (has relations)", [
                    'local_id' => $company->id,
                    'absen_id' => $company->absen_company_id,
                    'name' => $company->company_name
                ]);
            } else {
                // Soft delete jika tidak punya relasi
                $company->delete();
                $deletedCount++;
                
                Log::info("🗑️ Soft deleted company", [
                    'local_id' => $company->id,
                    'absen_id' => $company->absen_company_id,
                    'name' => $company->company_name
                ]);
            }
        }
        
        return $deletedCount;
    }
    
    /**
     * 📋 PREPARE DATA
     */
    protected function prepareCompanyData(array $apiData)
    {
        return [
            'absen_company_id' => $apiData['id'],
            'user_id' => $apiData['user_id'] ?? null,
            'code' => $apiData['code'] ?? null,
            'company_name' => $apiData['company_name'] ?? 'Unknown',
            'logo' => $apiData['logo'] ?? null,
            'ttd' => $apiData['ttd'] ?? null,
            'last_synced_at' => now(),
            'sync_metadata' => json_encode([
                'synced_from' => 'api',
                'api_id' => $apiData['id'],
                'synced_at' => now()->toISOString(),
            ])
        ];
    }
    
    /**
     * 🔄 SYNC SPECIFIC COMPANY BY ID
     */
    public function syncById($absenCompanyId)
    {
        try {
            $response = $this->apiService->getCompany($absenCompanyId, false);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => 'Company not found in API'
                ];
            }
            
            $apiData = $response['data'];
            
            DB::beginTransaction();
            $result = $this->syncSingleCompany($apiData);
            DB::commit();
            
            Log::info("✅ Synced single company", $result);
            
            return [
                'success' => true,
                'message' => 'Company synced successfully',
                'action' => $result['action'],
                'data' => $result
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error syncing single company', [
                'absen_id' => $absenCompanyId,
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
            'total_companies' => Company::count(),
            'soft_deleted' => Company::onlyTrashed()->count(),
            'never_synced' => Company::whereNull('last_synced_at')->count(),
            'last_sync_time' => Company::max('last_synced_at'),
            'oldest_sync_time' => Company::min('last_synced_at'),
        ];
    }
    
    /**
     * 🔍 CHECK SYNC HEALTH
     */
    public function checkSyncHealth($hoursThreshold = 24)
    {
        $needsSync = Company::needsSync($hoursThreshold)->count();
        $total = Company::count();
        
        return [
            'healthy' => $needsSync === 0,
            'needs_sync_count' => $needsSync,
            'total_count' => $total,
            'percentage_synced' => $total > 0 ? round((($total - $needsSync) / $total) * 100, 2) : 100,
            'threshold_hours' => $hoursThreshold
        ];
    }
}