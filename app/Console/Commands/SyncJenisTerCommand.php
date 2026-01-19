<?php
// app/Console/Commands/SyncJenisTerCommand.php

namespace App\Console\Commands;

use App\Services\JenisTerSyncService;
use Illuminate\Console\Command;

class SyncJenisTerCommand extends Command
{
    protected $signature = 'sync:jenis-ter {--force : Force refresh cache}';
    protected $description = 'Sync Jenis TER data from Attendance API';
    
    protected $syncService;
    
    public function __construct(JenisTerSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }
    
    public function handle()
    {
        $this->info('🔄 Starting Jenis TER sync...');
        $this->newLine();
        
        $forceRefresh = $this->option('force');
        
        if ($forceRefresh) {
            $this->warn('⚠️  Force refresh enabled - cache will be bypassed');
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
        $result = $this->syncService->syncAll($forceRefresh);
        
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
                    ['Deleted', $syncStats['deleted']],
                    ['Unchanged', $syncStats['unchanged']],
                    ['Total from API', $syncStats['total_api']],
                    ['Total Local', $syncStats['total_local']],
                ]
            );
            
            return 0;
        } else {
            $this->error('❌ Sync failed: ' . $result['message']);
            return 1;
        }
    }
}