<?php
// app/Console/Commands/SyncKaryawanCommand.php (DI APLIKASI GAJI)

namespace App\Console\Commands;

use App\Services\KaryawanSyncService;
use Illuminate\Console\Command;

class SyncKaryawanCommand extends Command
{
    protected $signature = 'karyawan:sync 
                            {--force : Force refresh cache}
                            {--id= : Sync specific karyawan by absen_karyawan_id}
                            {--stats : Show sync statistics only}';
    
    protected $description = 'Sinkronisasi data karyawan dari aplikasi ABSEN ke aplikasi GAJI';
    
    protected $syncService;
    
    public function __construct(KaryawanSyncService $syncService)
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
        
        // Sync specific karyawan
        if ($this->option('id')) {
            return $this->syncSpecific($this->option('id'));
        }
        
        // Full sync
        return $this->syncAll();
    }
    
    /**
     * FULL SYNC
     */
    protected function syncAll()
    {
        $this->info('🔄 Starting FULL SYNC...');
        $this->newLine();
        
        $forceRefresh = $this->option('force');
        
        if ($forceRefresh) {
            $this->warn('⚠️  Force refresh enabled - akan fetch ulang semua data');
        }
        
        // Confirm di production
        if (app()->environment('production')) {
            if (!$this->confirm('Anda yakin ingin sync di PRODUCTION?')) {
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
            $this->info('✅ SYNC BERHASIL!');
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
     * SYNC SPECIFIC KARYAWAN
     */
    protected function syncSpecific($absenKaryawanId)
    {
        $this->info("🔄 Syncing karyawan ID: {$absenKaryawanId}");
        
        $result = $this->syncService->syncById($absenKaryawanId);
        
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
     * SHOW STATISTICS
     */
    protected function showStats()
    {
        $this->info('📊 SYNC STATISTICS');
        $this->newLine();
        
        $stats = $this->syncService->getSyncStats();
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Karyawan', $stats['total_karyawan']],
                ['Active', $stats['active_karyawan']],
                ['Resigned', $stats['resigned_karyawan']],
                ['Soft Deleted', $stats['soft_deleted']],
                ['Never Synced', $stats['never_synced']],
                ['Last Sync', $stats['last_sync_time'] ?? 'Never'],
                ['Oldest Sync', $stats['oldest_sync_time'] ?? 'Never'],
            ]
        );
        
        $this->newLine();
        
        // Check health
        $health = $this->syncService->checkSyncHealth(24);
        
        if ($health['healthy']) {
            $this->info('✅ Sync health: GOOD');
        } else {
            $this->warn("⚠️  {$health['needs_sync_count']} karyawan needs sync");
        }
        
        $this->info("Sync coverage: {$health['percentage_synced']}%");
    }
}