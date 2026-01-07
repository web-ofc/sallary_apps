<?php
// app/Http/Controllers/KaryawanPtkpHistorySyncController.php (DI APLIKASI GAJI)

namespace App\Http\Controllers;

use App\Services\KaryawanPtkpHistorySyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class KaryawanPtkpHistorySyncController extends Controller
{
    protected $syncService;
    
    public function __construct(KaryawanPtkpHistorySyncService $syncService)
    {
        $this->syncService = $syncService;

        $this->middleware(function ($request, $next) {
            if (Gate::denies('ptkp-history-sync')) {
                abort(403, 'Unauthorized action.');
            }

            return $next($request);
        });
    }

    /**
     * 📡 DATATABLE SERVER-SIDE (POST)
     */
    public function datatable(Request $request)
    {
        $query = \App\Models\KaryawanPtkpHistory::with(['karyawan:id,absen_karyawan_id,nama_lengkap,nik', 'ptkp:id,absen_ptkp_id,kriteria,status']);
        
        // Filter by tahun
        if ($request->filled('tahun')) {
            $query->where('tahun', $request->input('tahun'));
        }
        
        // Search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('absen_ptkp_history_id', 'like', "%{$search}%")
                  ->orWhere('absen_karyawan_id', 'like', "%{$search}%")
                  ->orWhere('tahun', 'like', "%{$search}%")
                  ->orWhereHas('karyawan', function($sq) use ($search) {
                      $sq->where('nama_lengkap', 'like', "%{$search}%")
                         ->orWhere('nik', 'like', "%{$search}%");
                  })
                  ->orWhereHas('ptkp', function($sq) use ($search) {
                      $sq->where('kriteria', 'like', "%{$search}%");
                  });
            });
        }

        // Total records
        $totalRecords = \App\Models\KaryawanPtkpHistory::count();
        $filteredRecords = $query->count();

        // Ordering
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['absen_ptkp_history_id', 'karyawan_nama', 'ptkp_kriteria', 'tahun', 'last_synced_at', 'created_at', 'status_badge'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'absen_ptkp_history_id';
        
        // Custom ordering untuk relasi
        if ($orderColumn === 'karyawan_nama') {
            $query->leftJoin('karyawans', 'karyawan_ptkp_histories.absen_karyawan_id', '=', 'karyawans.absen_karyawan_id')
                  ->orderBy('karyawans.nama_lengkap', $orderDir)
                  ->select('karyawan_ptkp_histories.*');
        } elseif ($orderColumn === 'ptkp_kriteria') {
            $query->leftJoin('list_ptkps', 'karyawan_ptkp_histories.absen_ptkp_id', '=', 'list_ptkps.absen_ptkp_id')
                  ->orderBy('list_ptkps.kriteria', $orderDir)
                  ->select('karyawan_ptkp_histories.*');
        } else {
            $query->orderBy($orderColumn, $orderDir);
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $data = $query->skip($start)->take($length)->get();

        // Format data
        $formattedData = $data->map(function($history) {
            return [
                'absen_ptkp_history_id' => $history->absen_ptkp_history_id,
                'karyawan_nama' => $history->karyawan 
                    ? $history->karyawan->nama_lengkap . ' (' . $history->karyawan->nik . ')' 
                    : 'Karyawan ID: ' . $history->absen_karyawan_id,
                'ptkp_kriteria' => $history->ptkp 
                    ? $history->ptkp->kriteria . ' - ' . $history->ptkp->status 
                    : 'PTKP ID: ' . $history->absen_ptkp_id,
                'tahun' => $history->tahun ?? '-',
                'last_synced_at' => $history->last_synced_at 
                    ? Carbon::parse($history->last_synced_at)->format('d M Y H:i') 
                    : '-',
                'created_at' => $history->created_at 
                    ? Carbon::parse($history->created_at)->format('d M Y H:i') 
                    : '-',
                'status_badge' => $history->deleted_at 
                    ? '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Deleted</span>' 
                    : '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>',
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
     * 🔄 TRIGGER FULL SYNC (via API)
     */
    public function syncAll(Request $request)
    {
        try {
            $forceRefresh = $request->boolean('force', false);
            $filters = [];
            
            // Optional filters
            if ($request->filled('tahun')) {
                $filters['tahun'] = $request->input('tahun');
            }
            if ($request->filled('search')) {
                $filters['search'] = $request->input('search');
            }
            
            Log::info('Manual PTKP History sync triggered', [
                'user' => auth()->user()->email ?? 'unknown',
                'force' => $forceRefresh,
                'filters' => $filters
            ]);
            
            $result = $this->syncService->syncAll($forceRefresh, $filters);
            
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
     * 🔄 SYNC SPECIFIC PTKP HISTORY
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
     */
    public function syncByKaryawan($absenKaryawanId)
    {
        try {
            $result = $this->syncService->syncByKaryawanId($absenKaryawanId);
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
     */
    public function syncByTahun($tahun)
    {
        try {
            $result = $this->syncService->syncByTahun($tahun);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 📊 GET STATISTICS
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
     */
    public function dashboard()
    {
        $stats = $this->syncService->getSyncStats();
        $health = $this->syncService->checkSyncHealth(24);
        
        // Get available years for filter
        $years = \App\Models\KaryawanPtkpHistory::distinct('tahun')
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();
        
        return view('dashboard.dashboard-admin.ptkp-history.sync', compact('stats', 'health', 'years'));
    }
    
    /**
     * 🔄 TRIGGER SYNC VIA WEB
     */
    public function triggerSync(Request $request)
    {
        try {
            $forceRefresh = $request->boolean('force', false);
            $filters = [];
            
            if ($request->filled('tahun')) {
                $filters['tahun'] = $request->input('tahun');
            }
            
            Log::info('Manual PTKP History sync triggered via web', [
                'user' => auth()->user()->name ?? 'unknown',
                'force' => $forceRefresh,
                'filters' => $filters,
                'ip' => $request->ip()
            ]);
            
            $result = $this->syncService->syncAll($forceRefresh, $filters);
            
            if ($result['success']) {
                return redirect()
                    ->route('ptkp.history.sync.dashboard')
                    ->with('success', 'Sinkronisasi berhasil! ' . 
                        $result['stats']['new_inserted'] . ' inserted, ' .
                        $result['stats']['updated'] . ' updated, ' .
                        $result['stats']['deleted'] . ' deleted.');
            } else {
                return redirect()
                    ->route('ptkp.history.sync.dashboard')
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
    
    /**
     * 📊 GET MISSING PTKP FOR YEAR
     */
    public function getMissingPtkp(Request $request)
    {
        $request->validate([
            'tahun' => 'required|integer|min:2000|max:2100'
        ]);
        
        $result = $this->syncService->getMissingPtkpForYear($request->input('tahun'));
        
        return response()->json($result);
    }
}