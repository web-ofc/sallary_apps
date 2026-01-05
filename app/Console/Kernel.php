<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ✅ AUTO SYNC KARYAWAN SETIAP HARI JAM 2 PAGI (PRODUCTION)
        $schedule->command('karyawan:sync')
            ->dailyAt('02:00')
            ->environments(['production', 'staging'])
            ->emailOutputOnFailure('kandias203@gmail.com')
            ->onSuccess(function () {
                \Log::info('✅ Scheduled karyawan sync berhasil', [
                    'time' => now()->toDateTimeString()
                ]);
            })
            ->onFailure(function () {
                \Log::error('❌ Scheduled karyawan sync gagal', [
                    'time' => now()->toDateTimeString()
                ]);
            });
        
        // ✅ AUTO SYNC COMPANIES SETIAP HARI JAM 2:30 PAGI (PRODUCTION)
        $schedule->command('companies:sync')
            ->dailyAt('02:30')
            ->environments(['production', 'staging'])
            ->emailOutputOnFailure('kandias203@gmail.com')
            ->onSuccess(function () {
                \Log::info('✅ Scheduled companies sync berhasil', [
                    'time' => now()->toDateTimeString()
                ]);
            })
            ->onFailure(function () {
                \Log::error('❌ Scheduled companies sync gagal', [
                    'time' => now()->toDateTimeString()
                ]);
            });
        
        // ✅ TESTING: Sync setiap 5 menit di LOCAL (optional, hapus kalau ga perlu)
        if (app()->environment('local')) {
            $schedule->command('karyawan:sync')
                ->everyFiveMinutes()
                ->onSuccess(function () {
                    \Log::info('✅ [LOCAL] Scheduled karyawan sync berhasil');
                });
            
            $schedule->command('companies:sync')
                ->everyFiveMinutes()
                ->onSuccess(function () {
                    \Log::info('✅ [LOCAL] Scheduled companies sync berhasil');
                });
        }
        
        // ✅ Sync health check setiap 6 jam
        $schedule->command('karyawan:sync --stats')
            ->everySixHours()
            ->environments(['production', 'staging'])
            ->appendOutputTo(storage_path('logs/sync-health-karyawan.log'));
        
        $schedule->command('companies:sync --stats')
            ->everySixHours()
            ->environments(['production', 'staging'])
            ->appendOutputTo(storage_path('logs/sync-health-companies.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
