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
use App\Http\Controllers\JenisTerSyncController;
use App\Http\Controllers\KaryawanSyncController;
use App\Http\Controllers\PayrollsFakeController;
use App\Http\Controllers\Pph21TahunanController;
use App\Http\Controllers\MutasiCompanyController;
use App\Http\Controllers\PayrollAnnualController;
use App\Http\Controllers\PayrollImportController;
use App\Http\Controllers\Api\PayrollApiController;
use App\Http\Controllers\RangeBrutoSyncController;
use App\Http\Controllers\Pph21TaxBracketController;
use App\Http\Controllers\Pph21MasaCompanyController;
use App\Http\Controllers\PayrollFakeImportController;
use App\Http\Controllers\KaryawanPtkpHistorySyncController;
use App\Http\Controllers\PeriodeKaryawanMasaJabatanController;

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

        
    // ========================================
    // PAYROLL IMPORT ROUTES
    // ========================================
    Route::prefix('payrolls/import')->group(function () {
        Route::get('/', [PayrollImportController::class, 'index'])
            ->name('payrolls.import');
        
        Route::post('/validate', [PayrollImportController::class, 'validateExcel'])
            ->name('payrolls.import.validate');
        
        // DataTables routes
        Route::post('/datatable/valid', [PayrollImportController::class, 'validDataTable'])
            ->name('payrolls.import.datatable.valid');
        
        Route::post('/datatable/errors', [PayrollImportController::class, 'errorDataTable'])
            ->name('payrolls.import.datatable.errors');
        
        // Import biasa (bulanan)
        Route::post('/process', [PayrollImportController::class, 'process'])
            ->name('payrolls.import.process');
        
        // 🆕 Import untuk PPh21 Tahunan
        Route::post('/process-annual', [PayrollImportController::class, 'processAnnual'])
            ->name('payrolls.import.process-annual');
        
        Route::get('/template', [PayrollImportController::class, 'downloadTemplate'])
            ->name('payrolls.import.template');
        
        Route::post('/download-errors', [PayrollImportController::class, 'downloadErrors'])
            ->name('payrolls.import.download-errors');

        // Calculate PPh21 bulanan (TER-based)
        Route::post('/calculate-pph21', [PayrollImportController::class, 'calculatePph21BeforeImport'])
            ->name('payrolls.import.calculate-pph21');
        
        // Calculate PPh21 untuk data yang sudah di database
        Route::post('/calculate-pph21/batch', [PayrollImportController::class, 'calculatePph21Batch'])
            ->name('payrolls.calculate-pph21.batch');
        
        // Calculate PPh21 berdasarkan periode
        Route::post('/calculate-pph21/by-periode', [PayrollImportController::class, 'calculatePph21ByPeriode'])
            ->name('payrolls.calculate-pph21.by-periode');
        
        // Recalculate PPh21 (force update)
        Route::post('/recalculate-pph21', [PayrollImportController::class, 'recalculatePph21'])
            ->name('payrolls.recalculate-pph21');
    });

    // ========================================
    // 🆕 PAYROLL ANNUAL CALCULATION ROUTES
    // ========================================
    Route::prefix('payrolls/calculate-annual')->group(function () {
        // Page untuk hitung PPh21 Tahunan
        Route::get('/', [PayrollAnnualController::class, 'index'])
            ->name('payrolls.calculate-annual.index');
        
            // ✅ BARU: Get bracket headers by year (AJAX)
        Route::get('/bracket-headers', [PayrollAnnualController::class, 'getBracketHeaders'])
        ->name('payrolls.calculate-annual.bracket-headers');
    
        // DataTable untuk data pending
        Route::post('/datatable', [PayrollAnnualController::class, 'datatable'])
            ->name('payrolls.calculate-annual.datatable');
        
        // Process perhitungan PPh21 Tahunan
        Route::post('/process', [PayrollAnnualController::class, 'process'])
            ->name('payrolls.calculate-annual.process');
        
        // Get detail untuk modal
        Route::get('/detail', [PayrollAnnualController::class, 'getDetail'])
            ->name('payrolls.calculate-annual.detail');
    });

    // ========================================
    // PAYROLL ROUTES (existing)
    // ========================================
    Route::post('/payrolls/datatable/pending', [PayrollController::class, 'datatablePending'])
    ->name('payrollsdatatablepending');
    
    Route::post('/payrolls/datatable/released', [PayrollController::class, 'datatableReleased'])
        ->name('payrollsdatatablereleased');

    // 🔥 TAMBAHKAN ROUTE INI (yang kurang)
    Route::post('/payrolls/datatable/released-slip', [PayrollController::class, 'datatableReleasedSlip'])
        ->name('payrollsdatatablereleasedslip');

    // Release batch action
    Route::post('/payrolls/release', [PayrollController::class, 'releaseData'])
        ->name('payrolls.release');

    // Summary route
    Route::get('/payrolls/summary/{periode}', [PayrollController::class, 'summary'])
        ->name('payrolls.summary');

    // Export payroll
    Route::get('/payrolls/export', [PayrollController::class, 'export'])
        ->name('payrolls.export');
        
    // Statistics
    Route::post('/payrolls/statistics', [PayrollController::class, 'getStatistics'])
        ->name('payrolls.statistics');

    // Resource routes
    Route::resource('payrolls', PayrollController::class);
        

    // -------------------------------------------------

    // PAYROLLS FAKE ROUTES
    Route::post('/payrolls-fake/datatable/pending', [PayrollsFakeController::class, 'datatablePending'])
        ->name('payrollsfakedatatablepending');
        
    Route::post('/payrolls-fake/datatable/released', [PayrollsFakeController::class, 'datatableReleased'])
        ->name('payrollsfakedatatablereleased');

    Route::post('/payrolls-fake/datatable/released-slip', [PayrollsFakeController::class, 'datatableReleasedSlip'])
        ->name('payrollsfakedatatablereleasedslip');

    // Release batch action
    Route::post('/payrolls-fake/release', [PayrollsFakeController::class, 'releaseData'])
        ->name('payrolls-fake.release');

    // Summary route
    Route::get('/payrolls-fake/summary/{periode}', [PayrollsFakeController::class, 'summary'])
        ->name('payrolls-fake.summary');

    // Export payroll fake
    Route::get('/payrolls-fake/export', [PayrollsFakeController::class, 'export'])
        ->name('payrolls-fake.export');
        
    // Statistics
    Route::post('/payrolls-fake/statistics', [PayrollsFakeController::class, 'getStatistics'])
        ->name('payrolls-fake.statistics');

    // Resource routes
    Route::resource('payrolls-fake', PayrollsFakeController::class);




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

    Route::prefix('ptkp-history/sync')->group(function () {
        // Dashboard
        Route::get('/dashboard', [KaryawanPtkpHistorySyncController::class, 'dashboard'])
            ->name('ptkp.history.sync.dashboard');
        
        // DataTable endpoint untuk PTKP History list
        Route::post('/datatable', [KaryawanPtkpHistorySyncController::class, 'datatable'])
            ->name('ptkp.history.sync.datatable');
        
        // Trigger sync
        Route::post('/trigger', [KaryawanPtkpHistorySyncController::class, 'triggerSync'])
            ->name('ptkp.history.sync.trigger');
        
        // Get status (AJAX)
        Route::get('/status', [KaryawanPtkpHistorySyncController::class, 'getSyncStatus'])
            ->name('ptkp.history.sync.status');
        
        // Get missing PTKP (AJAX)
        Route::get('/missing-ptkp', [KaryawanPtkpHistorySyncController::class, 'getMissingPtkp'])
            ->name('ptkp.history.sync.missing-ptkp');
    });

    Route::prefix('periode-karyawan')->name('periode-karyawan.')->group(function () {
        Route::get('/', [PeriodeKaryawanMasaJabatanController::class, 'index'])->name('index');
        Route::get('/datatables', [PeriodeKaryawanMasaJabatanController::class, 'datatables'])->name('datatables');
        Route::get('/export', [PeriodeKaryawanMasaJabatanController::class, 'export'])->name('export');
        Route::get('/{periode}/{karyawanId}/{companyId}/{salaryType}', [PeriodeKaryawanMasaJabatanController::class, 'show'])->name('show');
    });

    Route::prefix('jenis-ter/sync')->group(function () {
        // Dashboard
        Route::get('/dashboard', [JenisTerSyncController::class, 'dashboard'])
            ->name('jenis-ter.sync.dashboard');
        
        // DataTable endpoint untuk Jenis TER list
        Route::post('/datatable', [JenisTerSyncController::class, 'datatable'])
            ->name('jenis-ter.sync.datatable');
        
        // Trigger sync
        Route::post('/trigger', [JenisTerSyncController::class, 'triggerSync'])
            ->name('jenis-ter.sync.trigger');
        
        // Get status (AJAX)
        Route::get('/status', [JenisTerSyncController::class, 'getSyncStatus'])
            ->name('jenis-ter.sync.status');
    });

    Route::prefix('range-bruto/sync')->group(function () {
        // Dashboard
        Route::get('/dashboard', [RangeBrutoSyncController::class, 'dashboard'])
            ->name('range-bruto.sync.dashboard');
        
        // DataTable endpoint untuk Range Bruto list
        Route::post('/datatable', [RangeBrutoSyncController::class, 'datatable'])
            ->name('range-bruto.sync.datatable');
        
        // Trigger sync
        Route::post('/trigger', [RangeBrutoSyncController::class, 'triggerSync'])
            ->name('range-bruto.sync.trigger');
        
        // Get status (AJAX)
        Route::get('/status', [RangeBrutoSyncController::class, 'getSyncStatus'])
            ->name('range-bruto.sync.status');
    });

    Route::get('pph21taxbrackets/data', [Pph21TaxBracketController::class, 'getData'])->name('pph21taxbrackets.data');
    Route::resource('/pph21taxbrackets', Pph21TaxBracketController::class);

    Route::prefix('payroll')->name('pph21.tahunan.')->group(function () {
        Route::get('/pph21-tahunan', [Pph21TahunanController::class, 'index'])->name('index');
        Route::post('/pph21-tahunan/data', [Pph21TahunanController::class, 'getData'])->name('data');
        Route::get('/pph21-tahunan/bracket-headers', [Pph21TahunanController::class, 'getBracketHeaders'])->name('bracket-headers');
        Route::get('/pph21-tahunan/export', [Pph21TahunanController::class, 'export'])->name('export');
    });



    // IMPORT ROUTES - NESTED di bawah /payrollsfake
    Route::prefix('payrollsfake/import')->name('payrollsfake.import.')->group(function () {
        
        // Halaman import
        Route::get('/', [PayrollFakeImportController::class, 'index'])
            ->name('index');
        
        // Download template Excel
        Route::get('/template', [PayrollFakeImportController::class, 'downloadTemplate'])
            ->name('template');
        
        // Validate Excel file
        Route::post('/validate', [PayrollFakeImportController::class, 'validateExcel'])
            ->name('validate');
        
        // DataTables untuk valid data
        Route::post('/datatable/valid', [PayrollFakeImportController::class, 'validDataTable'])
            ->name('datatable.valid');
        
        // DataTables untuk error data
        Route::post('/datatable/errors', [PayrollFakeImportController::class, 'errorDataTable'])
            ->name('datatable.errors');
        
        // Process import (insert ke database)
        Route::post('/process', [PayrollFakeImportController::class, 'process'])
            ->name('process');
        
        // Download error report
        Route::post('/download-errors', [PayrollFakeImportController::class, 'downloadErrors'])
            ->name('download-errors');
    });

    Route::resource('/manage-pph21companyperiode', Pph21MasaCompanyController::class);
    Route::get('pph21companyperiode/data', [Pph21MasaCompanyController::class, 'getData'])->name('pph21companyperiode.data');

});


    Route::get('/generate-token', function () {
        $user = \App\Models\User::first(); 
        return $user->createToken('api-token')->plainTextToken;
    });

    