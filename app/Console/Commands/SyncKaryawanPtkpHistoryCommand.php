<?php
// app/Console/Commands/SyncKaryawanPtkpHistoryCommand.php (DI APLIKASI GAJI)

namespace App\Console\Commands;

use App\Services\KaryawanPtkpHistorySyncService;
use Illuminate\Console\Command;

class SyncKaryawanPtkpHistoryCommand extends Command
{
    protected $signature = 'ptkp-history:sync 
                            {--force : Force refresh cache}
                            {--id= : Sync specific PTKP History by absen_ptkp_history_id}
                            {--karyawan= : Sync all PTKP History for specific karyawan}
                            {--tahun= : Sync all PTKP History for specific tahun}
                            {--stats : Show sync statistics only}';
    
    protected $description = 'Sinkronisasi data Karyawan PTKP History dari aplikasi ABSEN ke aplikasi GAJI';
    
    protected $syncService;
    
    public function __construct(KaryawanPtkpHistorySyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }
    
    public function handle()
    {
        // Show stats only
        if ($this->option('stats')) {
            $this->showStats();
            return 0;
        }
        
        // Sync specific PTKP History by ID
        if ($this->option('id')) {
            return $this->syncSpecific($this->option('id'));
        }
        
        // Sync by karyawan
        if ($this->option('karyawan')) {
            return $this->syncByKaryawan($this->option('karyawan'));
        }
        
        // Sync by tahun
        if ($this->option('tahun')) {
            return $this->syncByTahun($this->option('tahun'));
        }
        
        // Full sync
        return $this->syncAll();
    }
    
    /**
     * FULL SYNC
     */
    protected function syncAll()
    {
        $this->info('🔄 Starting FULL SYNC PTKP History...');
        $this->newLine();
        
        $forceRefresh = $this->option('force');
        
        if ($forceRefresh) {
            $this->warn('⚠️  Force refresh enabled - akan fetch ulang semua data');
        }
        
        // Confirm di production
        if (app()->environment('production')) {
            if (!$this->confirm('Anda yakin ingin sync PTKP History di PRODUCTION?')) {
                $this->error('❌ Sync dibatalkan');
                return 1;
            }
        }
        
        $this->newLine();
        
        // Start sync dengan progress bar
        $bar = $this->output->createProgressBar();
        $bar->setFormat(' %current% [%bar%] %message%');
        $bar->setMessage('Memulai sync...');
        $bar->start();
        
        $result = $this->syncService->syncAll($forceRefresh);
        
        $bar->finish();
        $this->newLine(2);
        
        // Display results
        if ($result['success']) {
            $this->info('✅ SYNC PTKP HISTORY BERHASIL!');
            $this->newLine();
            
            $stats = $result['stats'];
            
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total dari API', $stats['total_from_api']],
                    ['Inserted baru', $stats['new_inserted']],
                    ['Updated', $stats['updated']],
                    ['Deleted', $stats['deleted']],
                    ['Errors', $stats['errors']],
                    ['Duration', $stats['duration_seconds'] . ' seconds'],
                ]
            );
            
            return 0;
            
        } else {
            $this->error('❌ SYNC GAGAL!');
            $this->error($result['message']);
            return 1;
        }
    }
    
    /**
     * SYNC SPECIFIC PTKP HISTORY BY ID
     */
    protected function syncSpecific($absenHistoryId)
    {
        $this->info("🔄 Syncing PTKP History ID: {$absenHistoryId}");
        
        $result = $this->syncService->syncById($absenHistoryId);
        
        $this->newLine();
        
        if ($result['success']) {
            $this->info('✅ Sync berhasil!');
            $this->info("Action: {$result['action']}");
            return 0;
        } else {
            $this->error('❌ Sync gagal!');
            $this->error($result['message']);
            return 1;
        }
    }
    
    /**
     * SYNC BY KARYAWAN ID
     */
    protected function syncByKaryawan($absenKaryawanId)
    {
        $this->info("🔄 Syncing PTKP History for Karyawan ID: {$absenKaryawanId}");
        
        $forceRefresh = $this->option('force');
        $result = $this->syncService->syncByKaryawan($absenKaryawanId, $forceRefresh);
        
        $this->newLine();
        
        if ($result['success']) {
            $this->info('✅ Sync berhasil!');
            $this->newLine();
            
            $stats = $result['stats'];
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total', $stats['total']],
                    ['Inserted', $stats['inserted']],
                    ['Updated', $stats['updated']],
                    ['Errors', $stats['errors']],
                ]
            );
            
            return 0;
        } else {
            $this->error('❌ Sync gagal!');
            $this->error($result['message']);
            return 1;
        }
    }
    
    /**
     * SYNC BY TAHUN
     */
    protected function syncByTahun($tahun)
    {
        $this->info("🔄 Syncing PTKP History for Tahun: {$tahun}");
        
        $forceRefresh = $this->option('force');
        $result = $this->syncService->syncByTahun($tahun, $forceRefresh);
        
        $this->newLine();
        
        if ($result['success']) {
            $this->info('✅ Sync berhasil!');
            $this->newLine();
            
            $stats = $result['stats'];
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total', $stats['total']],
                    ['Inserted', $stats['inserted']],
                    ['Updated', $stats['updated']],
                    ['Errors', $stats['errors']],
                ]
            );
            
            return 0;
        } else {
            $this->error('❌ Sync gagal!');
            $this->error($result['message']);
            return 1;
        }
    }
    
    /**
     * SHOW STATISTICS
     */
    protected function showStats()
    {
        $this->info('📊 PTKP HISTORY SYNC STATISTICS');
        $this->newLine();
        
        $stats = $this->syncService->getSyncStats();
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total PTKP History', $stats['total_histories']],
                ['Soft Deleted', $stats['soft_deleted']],
                ['Never Synced', $stats['never_synced']],
                ['Last Sync', $stats['last_sync_time'] ?? 'Never'],
                ['Oldest Sync', $stats['oldest_sync_time'] ?? 'Never'],
            ]
        );
        
        $this->newLine();
        $this->info('📅 Distribution by Tahun:');
        
        if (!empty($stats['by_tahun'])) {
            $tahunData = [];
            foreach ($stats['by_tahun'] as $tahun => $total) {
                $tahunData[] = [$tahun, $total];
            }
            $this->table(['Tahun', 'Total'], $tahunData);
        } else {
            $this->warn('No data available');
        }
        
        $this->newLine();
        
        // Check health
        $health = $this->syncService->checkSyncHealth(24);
        
        if ($health['healthy']) {
            $this->info('✅ Sync health: GOOD');
        } else {
            $this->warn("⚠️  {$health['needs_sync_count']} PTKP History needs sync");
        }
        
        $this->info("Sync coverage: {$health['percentage_synced']}%");
    }
}