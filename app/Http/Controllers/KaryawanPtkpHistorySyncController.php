<?php
// app/Http/Controllers/KaryawanPtkpHistorySyncController.php (DI APLIKASI GAJI)

namespace App\Http\Controllers;

use App\Services\KaryawanPtkpHistorySyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class KaryawanPtkpHistorySyncController extends Controller
{
    protected $syncService;
    
    public function __construct(KaryawanPtkpHistorySyncService $syncService)
    {
        $this->syncService = $syncService;

      
    }
    
    /**
     * 🔄 TRIGGER FULL SYNC (via API/UI button)
     * POST /api/ptkp-history/sync
     */
    public function syncAll(Request $request)
    {
        try {
            $forceRefresh = $request->boolean('force', false);
            
            Log::info('Manual PTKP History sync triggered', [
                'user' => auth()->user()->email ?? 'unknown',
                'force' => $forceRefresh
            ]);
            
            $result = $this->syncService->syncAll($forceRefresh);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Manual PTKP History sync failed', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 🔄 SYNC SPECIFIC PTKP HISTORY BY ID
     * POST /api/ptkp-history/sync/{absenHistoryId}
     */
    public function syncById($absenHistoryId)
    {
        try {
            $result = $this->syncService->syncById($absenHistoryId);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 🔄 SYNC BY KARYAWAN ID
     * POST /api/ptkp-history/sync/karyawan/{absenKaryawanId}
     */
    public function syncByKaryawan($absenKaryawanId, Request $request)
    {
        try {
            $forceRefresh = $request->boolean('force', false);
            $result = $this->syncService->syncByKaryawan($absenKaryawanId, $forceRefresh);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 🔄 SYNC BY TAHUN
     * POST /api/ptkp-history/sync/tahun/{tahun}
     */
    public function syncByTahun($tahun, Request $request)
    {
        try {
            $forceRefresh = $request->boolean('force', false);
            $result = $this->syncService->syncByTahun($tahun, $forceRefresh);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 📊 GET SYNC STATISTICS
     * GET /api/ptkp-history/sync/stats
     */
    public function stats()
    {
        $stats = $this->syncService->getSyncStats();
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
    
    /**
     * 📊 DASHBOARD SYNC (Web UI)
     * GET /ptkp-history/sync/dashboard
     */
    public function dashboard()
    {
        $stats = $this->syncService->getSyncStats();
        $health = $this->syncService->checkSyncHealth(24);
        
        return view('dashboard.dashboard-admin.ptkp-history.sync', compact('stats', 'health'));
    }
    
    /**
     * 📊 DATATABLE FOR PTKP HISTORY LIST
     * POST /ptkp-history/sync/datatable
     */
    public function datatable(Request $request)
    {
        $query = \App\Models\KaryawanPtkpHistory::with(['karyawan', 'ptkp']);

        // Search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('tahun', 'like', "%{$search}%")
                  ->orWhere('absen_karyawan_id', 'like', "%{$search}%")
                  ->orWhere('absen_ptkp_id', 'like', "%{$search}%")
                  ->orWhereHas('karyawan', function($q2) use ($search) {
                      $q2->where('nama_lengkap', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by tahun if provided
        if ($request->filled('tahun')) {
            $query->where('tahun', $request->tahun);
        }

        $totalRecords = \App\Models\KaryawanPtkpHistory::count();
        $filteredRecords = $query->count();

        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['id', 'absen_karyawan_id', 'absen_ptkp_id', 'tahun', 'last_synced_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';
        
        $query->orderBy($orderColumn, $orderDir);

        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $data = $query->skip($start)->take($length)->get();

        $formattedData = $data->map(function($history) {
            return [
                'id' => $history->id,
                'absen_history_id' => $history->absen_ptkp_history_id,
                'karyawan_nama' => $history->karyawan->nama_lengkap ?? '-',
                'karyawan_nik' => $history->karyawan->nik ?? '-',
                'ptkp_kriteria' => $history->ptkp->kriteria ?? '-',
                'ptkp_status' => $history->ptkp->status ?? '-',
                'tahun' => $history->tahun,
                'last_synced_at' => $history->last_synced_at 
                    ? \Carbon\Carbon::parse($history->last_synced_at)->format('d M Y H:i') 
                    : '<span class="badge bg-warning">Never</span>',
                'sync_status' => $history->last_synced_at && $history->last_synced_at->isAfter(now()->subHours(24))
                    ? '<span class="badge bg-success">Synced</span>'
                    : '<span class="badge bg-warning">Needs Sync</span>',
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $formattedData
        ]);
    }
    
    /**
     * 🔄 TRIGGER SYNC VIA WEB (Button Click)
     * POST /ptkp-history/sync/trigger
     */
    public function triggerSync(Request $request)
    {
        try {
            $forceRefresh = $request->boolean('force', false);
            
            Log::info('Manual PTKP History sync triggered via web', [
                'user' => auth()->user()->name ?? 'unknown',
                'force' => $forceRefresh,
                'ip' => $request->ip()
            ]);
            
            // Jalankan sync
            $result = $this->syncService->syncAll($forceRefresh);
            
            if ($result['success']) {
                return redirect()
                    ->route('ptkp.history.sync.dashboard')
                    ->with('success', 'Sinkronisasi PTKP History berhasil! ' . 
                        $result['stats']['new_inserted'] . ' inserted, ' .
                        $result['stats']['updated'] . ' updated, ' .
                        $result['stats']['deleted'] . ' deleted.');
            } else {
                return redirect()
                    ->route('ptkp-history.sync.dashboard')
                    ->with('error', 'Sinkronisasi gagal: ' . $result['message']);
            }
            
        } catch (\Exception $e) {
            Log::error('Manual PTKP History sync failed via web', [
                'error' => $e->getMessage()
            ]);
            
            return redirect()
                ->route('ptkp-history.sync.dashboard')
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }
    
    /**
     * 📡 GET SYNC STATUS (AJAX)
     * GET /ptkp-history/sync/status
     */
    public function getSyncStatus()
    {
        $stats = $this->syncService->getSyncStats();
        $health = $this->syncService->checkSyncHealth(24);
        
        return response()->json([
            'success' => true,
            'stats' => $stats,
            'health' => $health
        ]);
    }
}