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
                            {--karyawan= : Sync by karyawan ID (absen_karyawan_id)}
                            {--tahun= : Sync by tahun or filter by tahun}
                            {--stats : Show sync statistics only}';
    
    protected $description = 'Sinkronisasi data PTKP History dari aplikasi ABSEN ke aplikasi GAJI';
    
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
        
        // Sync specific PTKP History
        if ($this->option('id')) {
            return $this->syncSpecific($this->option('id'));
        }
        
        // Sync by karyawan
        if ($this->option('karyawan')) {
            return $this->syncByKaryawan($this->option('karyawan'));
        }
        
        // Sync by tahun only
        if ($this->option('tahun') && !$this->option('force')) {
            return $this->syncByTahun($this->option('tahun'));
        }
        
        // Full sync (with optional tahun filter)
        return $this->syncAll();
    }
    
    protected function syncAll()
    {
        $this->info('🔄 Starting FULL SYNC PTKP History...');
        $this->newLine();
        
        $forceRefresh = $this->option('force');
        $filters = [];
        
        if ($this->option('tahun')) {
            $filters['tahun'] = $this->option('tahun');
            $this->info("📅 Filter: Tahun {$filters['tahun']}");
        }
        
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
        
        $result = $this->syncService->syncAll($forceRefresh, $filters);
        
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
    
    protected function syncByKaryawan($absenKaryawanId)
    {
        $this->info("🔄 Syncing PTKP History for Karyawan ID: {$absenKaryawanId}");
        
        $result = $this->syncService->syncByKaryawanId($absenKaryawanId);
        
        $this->newLine();
        
        if ($result['success']) {
            $this->info('✅ Sync berhasil!');
            $this->info("Synced: {$result['synced']} histories");
            return 0;
        } else {
            $this->error('❌ Sync gagal!');
            $this->error($result['message']);
            return 1;
        }
    }
    
    protected function syncByTahun($tahun)
    {
        $this->info("🔄 Syncing PTKP History for Tahun: {$tahun}");
        
        $result = $this->syncService->syncByTahun($tahun);
        
        $this->newLine();
        
        if ($result['success']) {
            $this->info('✅ Sync berhasil!');
            $this->info("Synced: {$result['synced']} histories");
            return 0;
        } else {
            $this->error('❌ Sync gagal!');
            $this->error($result['message']);
            return 1;
        }
    }
    
    protected function showStats()
    {
        $this->info('📊 SYNC STATISTICS - PTKP History');
        $this->newLine();
        
        $stats = $this->syncService->getSyncStats();
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Histories', $stats['total_histories']],
                ['Soft Deleted', $stats['soft_deleted']],
                ['Never Synced', $stats['never_synced']],
                ['Unique Karyawan', $stats['unique_karyawan']],
                ['Unique Years', $stats['unique_years']],
                ['Last Sync', $stats['last_sync_time'] ?? 'Never'],
                ['Oldest Sync', $stats['oldest_sync_time'] ?? 'Never'],
            ]
        );
        
        // Show breakdown by year
        if (!empty($stats['by_year'])) {
            $this->newLine();
            $this->info('📅 Breakdown by Year:');
            $this->table(
                ['Tahun', 'Total'],
                array_map(function($item) {
                    return [$item['tahun'], $item['total']];
                }, $stats['by_year'])
            );
        }
        
        $this->newLine();
        
        $health = $this->syncService->checkSyncHealth(24);
        
        if ($health['healthy']) {
            $this->info('✅ Sync health: GOOD');
        } else {
            $this->warn("⚠️  {$health['needs_sync_count']} histories need sync");
        }
        
        $this->info("Sync coverage: {$health['percentage_synced']}%");
    }
}