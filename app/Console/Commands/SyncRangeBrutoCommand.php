<?php
// app/Console/Commands/SyncRangeBrutoCommand.php

namespace App\Console\Commands;

use App\Services\RangeBrutoSyncService;
use Illuminate\Console\Command;

class SyncRangeBrutoCommand extends Command
{
    protected $signature = 'sync:range-bruto 
                            {--force : Force refresh cache}
                            {--jenis-ter= : Sync only for specific Jenis TER ID}';
    protected $description = 'Sync Range Bruto data from Attendance API';
    
    protected $syncService;
    
    public function __construct(RangeBrutoSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }
    
    public function handle()
    {
        $this->info('🔄 Starting Range Bruto sync...');
        $this->newLine();
        
        $forceRefresh = $this->option('force');
        $jenisTerId = $this->option('jenis-ter');
        
        if ($forceRefresh) {
            $this->warn('⚠️  Force refresh enabled - cache will be bypassed');
        }
        
        if ($jenisTerId) {
            $this->info("🎯 Syncing only for Jenis TER ID: {$jenisTerId}");
        }
        
        // Show current stats
        $this->info('📊 Current Statistics:');
        $stats = $this->syncService->getSyncStats();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total', $stats['total']],
                ['Active', $stats['active']],
                ['Deleted', $stats['deleted']],
                ['Needs Sync', $stats['needs_sync']],
                ['Last Sync', $stats['last_sync_human']],
                ['Avg TER', $stats['ter_stats']['avg'] . '%'],
            ]
        );
        $this->newLine();
        
        // Confirm before proceed
        if (!$this->confirm('Do you want to proceed with sync?', true)) {
            $this->warn('Sync cancelled');
            return 0;
        }
        
        $this->newLine();
        $this->info('⏳ Syncing...');
        
        // Execute sync
        if ($jenisTerId) {
            $result = $this->syncService->syncByJenisTer($jenisTerId);
        } else {
            $result = $this->syncService->syncAll($forceRefresh);
        }
        
        $this->newLine();
        
        if ($result['success']) {
            $this->info('✅ Sync completed successfully!');
            $this->newLine();
            
            $syncStats = $result['stats'];
            $this->table(
                ['Action', 'Count'],
                [
                    ['New Inserted', $syncStats['new_inserted']],
                    ['Updated', $syncStats['updated']],
                    ['Deleted', $syncStats['deleted'] ?? 0],
                    ['Unchanged', $syncStats['unchanged'] ?? 0],
                    ['Skipped (No Jenis TER)', $syncStats['skipped_no_jenis_ter'] ?? 0],
                    ['Total from API', $syncStats['total_api'] ?? $syncStats['total'] ?? 0],
                    ['Total Local', $syncStats['total_local'] ?? $this->syncService->getSyncStats()['total']],
                ]
            );
            
            if (($syncStats['skipped_no_jenis_ter'] ?? 0) > 0) {
                $this->newLine();
                $this->warn('⚠️  Some Range Bruto were skipped because their Jenis TER is not synced yet.');
                $this->info('💡 Run: php artisan sync:jenis-ter first');
            }
            
            return 0;
        } else {
            $this->error('❌ Sync failed: ' . $result['message']);
            return 1;
        }
    }
}