<?php
// routes/api.php (DI APLIKASI GAJI)

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PtkpSyncController;
use App\Http\Controllers\CompanyViewController;
use App\Http\Controllers\Api\KaryawanController;
use App\Http\Controllers\KaryawanSyncController;
use App\Http\Controllers\KaryawanViewController;
use App\Http\Controllers\Api\PayrollApiController;
use App\Http\Controllers\KaryawanPtkpHistorySyncController;

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

    Route::prefix('sync/ptkp-history')->name('api.sync.ptkp-history.')->group(function () {
        // Full sync
        Route::post('/all', [KaryawanPtkpHistorySyncController::class, 'syncAll'])
            ->name('all');
        
        // Sync by specific ID
        Route::post('/{id}', [KaryawanPtkpHistorySyncController::class, 'syncById'])
            ->name('by-id');
        
        // Sync by karyawan
        Route::post('/karyawan/{karyawan_id}', [KaryawanPtkpHistorySyncController::class, 'syncByKaryawan'])
            ->name('by-karyawan');
        
        // Sync by tahun
        Route::post('/tahun/{tahun}', [KaryawanPtkpHistorySyncController::class, 'syncByTahun'])
            ->name('by-tahun');
        
        // Get stats
        Route::get('/stats', [KaryawanPtkpHistorySyncController::class, 'stats'])
            ->name('stats');
        
        // Get missing PTKP for year
        Route::get('/missing-ptkp', [KaryawanPtkpHistorySyncController::class, 'getMissingPtkp'])
            ->name('missing-ptkp');
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
        Route::get('/{id}', [PayrollApiController::class, 'show']);
        
        
        
    });
});
