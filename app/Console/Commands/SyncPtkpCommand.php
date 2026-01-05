<?php
// app/Console/Commands/SyncPtkpCommand.php (DI APLIKASI GAJI)

namespace App\Console\Commands;

use App\Services\PtkpSyncService;
use Illuminate\Console\Command;

class SyncPtkpCommand extends Command
{
    protected $signature = 'ptkp:sync 
                            {--force : Force refresh cache}
                            {--id= : Sync specific PTKP by absen_ptkp_id}
                            {--stats : Show sync statistics only}';
    
    protected $description = 'Sinkronisasi data PTKP dari aplikasi ABSEN ke aplikasi GAJI';
    
    protected $syncService;
    
    public function __construct(PtkpSyncService $syncService)
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
        
        // Sync specific PTKP
        if ($this->option('id')) {
            return $this->syncSpecific($this->option('id'));
        }
        
        // Full sync
        return $this->syncAll();
    }
    
    protected function syncAll()
    {
        $this->info('🔄 Starting FULL SYNC PTKP...');
        $this->newLine();
        
        $forceRefresh = $this->option('force');
        
        if ($forceRefresh) {
            $this->warn('⚠️  Force refresh enabled');
        }
        
        if (app()->environment('production')) {
            if (!$this->confirm('Sync di PRODUCTION?')) {
                $this->error('❌ Dibatalkan');
                return 1;
            }
        }
        
        $this->newLine();
        
        $bar = $this->output->createProgressBar();
        $bar->setFormat(' %current% [%bar%] %message%');
        $bar->setMessage('Memulai sync...');
        $bar->start();
        
        $result = $this->syncService->syncAll($forceRefresh);
        
        $bar->finish();
        $this->newLine(2);
        
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
    
    protected function syncSpecific($absenPtkpId)
    {
        $this->info("🔄 Syncing PTKP ID: {$absenPtkpId}");
        
        $result = $this->syncService->syncById($absenPtkpId);
        
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
    
    protected function showStats()
    {
        $this->info('📊 SYNC STATISTICS - PTKP');
        $this->newLine();
        
        $stats = $this->syncService->getSyncStats();
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total PTKP', $stats['total_ptkp']],
                ['Soft Deleted', $stats['soft_deleted']],
                ['Never Synced', $stats['never_synced']],
                ['Last Sync', $stats['last_sync_time'] ?? 'Never'],
                ['Oldest Sync', $stats['oldest_sync_time'] ?? 'Never'],
            ]
        );
        
        $this->newLine();
        
        $health = $this->syncService->checkSyncHealth(24);
        
        if ($health['healthy']) {
            $this->info('✅ Sync health: GOOD');
        } else {
            $this->warn("⚠️  {$health['needs_sync_count']} PTKP needs sync");
        }
        
        $this->info("Sync coverage: {$health['percentage_synced']}%");
    }
}