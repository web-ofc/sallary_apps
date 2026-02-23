<?php
// routes/api.php (DI APLIKASI GAJI)

use App\Http\Controllers\Api\KaryawanController;
use App\Http\Controllers\Api\PayrollApiController;
use App\Http\Controllers\Api\ReimbursementApiController;
use App\Http\Controllers\Api\ReimbursementFileApiController;
use App\Http\Controllers\CompanyViewController;
use App\Http\Controllers\JenisTerSyncController;
use App\Http\Controllers\KaryawanPtkpHistorySyncController;
use App\Http\Controllers\KaryawanSyncController;
use App\Http\Controllers\KaryawanViewController;
use App\Http\Controllers\PtkpSyncController;
use App\Http\Controllers\RangeBrutoSyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('karyawan')->group(function () {
        
        // List & Search
        Route::get('/', [KaryawanViewController::class, 'index']);
        Route::get('/search', [KaryawanViewController::class, 'search']);
        
        // Minimal data untuk dropdown
        Route::get('/active-minimal', [KaryawanViewController::class, 'activeMinimal']);
        
        // Bulk operations
        Route::post('/bulk', [KaryawanViewController::class, 'bulk']);
        
        // Cache management
        Route::post('/clear-cache', [KaryawanViewController::class, 'clearMinimalCache']);
        Route::post('/clear-cache/{id}', [KaryawanViewController::class, 'clearSpecificCache']);
        
        // ⚠️ {id} route HARUS di paling bawah
        Route::get('/{id}', [KaryawanViewController::class, 'show']);
    });

    /*
    |--------------------------------------------------------------------------
    | COMPANY ROUTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('companies')->group(function () {
        
        // List & Search
        Route::get('/', [CompanyViewController::class, 'getCompanies']);
        Route::get('/search', [CompanyViewController::class, 'search']);
        
        // Minimal data untuk dropdown
        Route::get('/minimal', [CompanyViewController::class, 'minimal']);
        
        // Get by code
        Route::get('/by-code/{code}', [CompanyViewController::class, 'getByCode']);
        
        // Bulk operations
        Route::post('/bulk', [CompanyViewController::class, 'bulk']);
        
        // Cache management
        Route::post('/clear-cache', [CompanyViewController::class, 'clearMinimalCache']);
        Route::post('/clear-cache/{id}', [CompanyViewController::class, 'clearSpecificCache']);
        
        // ⚠️ {id} route HARUS di paling bawah
        Route::get('/{id}', [CompanyViewController::class, 'getCompany']);
    });

    Route::prefix('karyawan/sync')->group(function () {
        
        // Trigger full sync
        // POST /api/karyawan/sync
        Route::post('/', [KaryawanSyncController::class, 'syncAll']);
        
        // Get sync statistics
        // GET /api/karyawan/sync/stats
        Route::get('/stats', [KaryawanSyncController::class, 'stats']);
        
        // Check sync health
        // GET /api/karyawan/sync/health?hours=24
        Route::get('/health', [KaryawanSyncController::class, 'health']);
        
        // Sync specific karyawan
        // POST /api/karyawan/sync/{absenKaryawanId}
        Route::post('/{absenKaryawanId}', [KaryawanSyncController::class, 'syncById']);
        
    });

    Route::post('/all', [PtkpSyncController::class, 'syncAll'])->name('all');
    Route::post('/{id}', [PtkpSyncController::class, 'syncById'])->name('by-id');
    Route::get('/stats', [PtkpSyncController::class, 'stats'])->name('stats');

     Route::prefix('sync/jenis-ter')->name('api.sync.jenis-ter.')->group(function () {
        Route::post('/all', [JenisTerSyncController::class, 'syncAll'])->name('all');
        Route::post('/{id}', [JenisTerSyncController::class, 'syncById'])->name('by-id');
        Route::get('/stats', [JenisTerSyncController::class, 'stats'])->name('stats');
    });

    Route::prefix('sync/range-bruto')->name('api.sync.range-bruto.')->group(function () {
        Route::post('/all', [RangeBrutoSyncController::class, 'syncAll'])->name('all');
        Route::post('/{id}', [RangeBrutoSyncController::class, 'syncById'])->name('by-id');
        Route::post('/by-jenis-ter/{jenisTerId}', [RangeBrutoSyncController::class, 'syncByJenisTer'])->name('by-jenis-ter');
        Route::get('/stats', [RangeBrutoSyncController::class, 'stats'])->name('stats');
    });
    
    Route::prefix('sync/ptkp-history')->name('api.sync.ptkp-history.')->group(function () {
         // Full sync
    Route::post('/', [KaryawanPtkpHistorySyncController::class, 'syncAll'])
        ->name('api.ptkp-history.sync.all');
    
    // Sync by ID
    Route::post('/{absenHistoryId}', [KaryawanPtkpHistorySyncController::class, 'syncById'])
        ->name('api.ptkp-history.sync.by-id');
    
    // Sync by karyawan
    Route::post('/karyawan/{absenKaryawanId}', [KaryawanPtkpHistorySyncController::class, 'syncByKaryawan'])
        ->name('api.ptkp-history.sync.by-karyawan');
    
    // Sync by tahun
    Route::post('/tahun/{tahun}', [KaryawanPtkpHistorySyncController::class, 'syncByTahun'])
        ->name('api.ptkp-history.sync.by-tahun');
    
    // Get stats
    Route::get('/stats', [KaryawanPtkpHistorySyncController::class, 'stats'])
        ->name('api.ptkp-history.sync.stats');
    });
/*
|--------------------------------------------------------------------------
| PAYROLL ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('payrolls')->group(function () {
        
        // ✅ READ OPERATIONS ONLY
        Route::get('/', [PayrollApiController::class, 'index']);
        Route::get('/periodes', [PayrollApiController::class, 'getPeriodes']);
        Route::get('/check', [PayrollApiController::class, 'checkExists']);
        Route::get('/summary/{periode}', [PayrollApiController::class, 'summaryByPeriode']);
        Route::get('/by-periode/{periode}', [PayrollApiController::class, 'byPeriode']);
        Route::get('/by-karyawan/{karyawan_id}', [PayrollApiController::class, 'byKaryawan']);
         Route::get('/{source}/{id}', [PayrollApiController::class, 'showBySource']);
        Route::get('/{id}', [PayrollApiController::class, 'show']);
        
        
        
    });

    Route::prefix('reimbursements')->group(function () {
    
        // API 1: Get pending reimbursements (status = 0) dengan pagination
        // GET /api/reimbursements/pending
        // Query params: page, per_page, karyawan_id, company_id, periode_slip, year_budget
        Route::get('/pending', [ReimbursementApiController::class, 'getPendingReimbursements']);
        
        // API 2: Get summary/statistics
        // GET /api/reimbursements/summary
        // Query params: karyawan_id, company_id, periode_slip, year_budget
        Route::get('/summary', [ReimbursementApiController::class, 'getSummary']);
        
        // API 3: Get detail reimbursement by ID (untuk show icon mata)
        // GET /api/reimbursements/{id}
        Route::get('/{id}', [ReimbursementApiController::class, 'getReimbursementDetail']);
        
        // API 4: Approve reimbursement (update status jadi 1)
        // PUT /api/reimbursements/{id}/approve
        // Body: approved_id (optional, default 6 dari auth karyawan_id)
        Route::put('/{id}/approve', [ReimbursementApiController::class, 'approveReimbursement']);
        
    });

    Route::prefix('reimbursement-files')->name('api.reimbursement-files.')->group(function () {
        
        // ✅ FIX: Accept karyawan_id as path parameter
        Route::get('/by-karyawan/{karyawan_id}', [ReimbursementFileApiController::class, 'getByKaryawan'])
            ->name('by-karyawan');
        
        // ✅ FIX: Accept karyawan_id as path parameter
        Route::get('/summary/{karyawan_id}', [ReimbursementFileApiController::class, 'getSummary'])
            ->name('summary');
        
        // ✅ FIX: Accept karyawan_id as path parameter
        Route::get('/years/{karyawan_id}', [ReimbursementFileApiController::class, 'getAvailableYears'])
            ->name('years');
    });
});
