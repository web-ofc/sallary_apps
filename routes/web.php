<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\PtkpSyncController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileProxyController;
use App\Http\Controllers\CompanySyncController;
use App\Http\Controllers\CompanyViewController;
use App\Http\Controllers\KaryawanSyncController;
use App\Http\Controllers\MutasiCompanyController;
use App\Http\Controllers\PayrollImportController;
use App\Http\Controllers\Api\PayrollApiController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');



Route::middleware('auth')->group(function () {
    Route::get('/dashboard-admin', [DashboardController::class, 'adminDashboard'])->name('dashboard.admin')->middleware('admin');
     // Dashboard AJAX Routes
    Route::prefix('dashboard')->middleware('admin')->group(function () {
        Route::get('/data', [DashboardController::class, 'getDashboardData'])->name('dashboard.data');
        Route::get('/periodes', [DashboardController::class, 'getPeriodes'])->name('dashboard.periodes');
        Route::get('/statistics', [DashboardController::class, 'getStatistics'])->name('dashboard.statistics');
    });
});
Route::middleware(['auth', 'role:admin'])->group(function () {
    
    Route::resource('/manage-user', UserController::class);
    Route::get('users/data', [UserController::class, 'getData'])->name('users.data');

        
    Route::prefix('payrolls/import')->group(function () {
        Route::get('/', [PayrollImportController::class, 'index'])
            ->name('payrolls.import');
        
        Route::post('/validate', [PayrollImportController::class, 'validateExcel'])
            ->name('payrolls.import.validate');
        
        // ✅ NEW: DataTables routes
        Route::post('/datatablevalid', [PayrollImportController::class, 'validDataTable'])
            ->name('payrolls.import.datatable.valid');
        
        Route::post('/datatableerrors', [PayrollImportController::class, 'errorDataTable'])
            ->name('payrolls.import.datatable.errors');
        
        Route::post('/process', [PayrollImportController::class, 'process'])
            ->name('payrolls.import.process');
        
        Route::get('/template', [PayrollImportController::class, 'downloadTemplate'])
            ->name('payrolls.import.template');
        
        Route::post('/download-errors', [PayrollImportController::class, 'downloadErrors'])
            ->name('payrolls.import.download-errors');
    });
    

  
    // ========================================
    // PAYROLL ROUTES
    // ========================================
    Route::post('/payrollsdatatablepending', [PayrollController::class, 'datatablePending'])
        ->name('payrollsdatatablepending');
        
    Route::post('/payrollsdatatablereleased', [PayrollController::class, 'datatableReleased'])
        ->name('payrollsdatatablereleased');

    // ✅ Release batch action
    Route::post('/payrolls/release', [PayrollController::class, 'releaseData'])
        ->name('payrolls.release');

    // ✅ Summary route (sebelum resource agar tidak conflict)
    Route::get('/payrolls/summary/{periode}', [PayrollController::class, 'summary'])
        ->name('payrolls.summary');

    // Export payroll (harus sebelum resource route)
    Route::get('/payrolls/export', [PayrollController::class, 'export'])
        ->name('payrolls.export');
        
    // ✅ Resource routes
    Route::resource('payrolls', PayrollController::class);
    


      // Halaman Dashboard Sync
    Route::get('/karyawan/sync/dashboard', [KaryawanSyncController::class, 'dashboard'])
        ->name('karyawan.sync.dashboard');
    
    // Trigger sync via POST (untuk button)
    Route::post('/karyawan/sync/trigger', [KaryawanSyncController::class, 'triggerSync'])
        ->name('karyawan.sync.trigger');
    
    // Get sync status (AJAX)
    Route::get('/karyawan/sync/status', [KaryawanSyncController::class, 'getSyncStatus'])
        ->name('karyawan.sync.status');

    // DataTable endpoint untuk companies list
    Route::post('/karyawandatatable', [KaryawanSyncController::class, 'datatable'])
            ->name('karyawandatatable');
        

   
    Route::prefix('companies/sync')->group(function () {
        
        // Dashboard (sudah include datatables sekarang)
        Route::get('/dashboard', [CompanySyncController::class, 'dashboard'])
            ->name('companies.sync.dashboard');
        
        // DataTable endpoint untuk companies list
        Route::post('/datatable', [CompanySyncController::class, 'datatable'])
            ->name('companies.datatable');
        
        // Trigger sync
        Route::post('/trigger', [CompanySyncController::class, 'triggerSync'])
            ->name('companies.sync.trigger');
        
        // Get status (AJAX)
        Route::get('/status', [CompanySyncController::class, 'getSyncStatus'])
            ->name('companies.sync.status');
        
    });
    

    Route::resource('/manage-mutasicompany', MutasiCompanyController::class);
    Route::get('mutasicompanies/data', [MutasiCompanyController::class, 'getData'])->name('mutasicompanies.data');

    Route::prefix('ptkp/sync')->group(function () {
        // Dashboard
        Route::get('/dashboard', [PtkpSyncController::class, 'dashboard'])
            ->name('ptkp.sync.dashboard');
        
        // DataTable endpoint untuk PTKP list
        Route::post('/datatable', [PtkpSyncController::class, 'datatable'])
            ->name('ptkp.sync.datatable');
        
        // Trigger sync
        Route::post('/trigger', [PtkpSyncController::class, 'triggerSync'])
            ->name('ptkp.sync.trigger');
        
        // Get status (AJAX)
        Route::get('/status', [PtkpSyncController::class, 'getSyncStatus'])
            ->name('ptkp.sync.status');
    });

        
});


    Route::get('/generate-token', function () {
        $user = \App\Models\User::first(); 
        return $user->createToken('api-token')->plainTextToken;
    });

    