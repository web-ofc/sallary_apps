<?php
// app/Http/Controllers/PtkpSyncController.php (DI APLIKASI GAJI)

namespace App\Http\Controllers;

use App\Services\PtkpSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class PtkpSyncController extends Controller
{
    protected $syncService;
    
    public function __construct(PtkpSyncService $syncService)
    {
        $this->syncService = $syncService;

        $this->middleware(function ($request, $next) {
            if (Gate::denies('ptkp-sync')) {
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
        $query = \App\Models\ListPtkp::query();
        
        // Search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('kriteria', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhere('absen_ptkp_id', 'like', "%{$search}%");
            });
        }

        // Total records
        $totalRecords = \App\Models\ListPtkp::count();
        $filteredRecords = $query->count();

        // Ordering
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['absen_ptkp_id', 'kriteria', 'status', 'besaran_ptkp', 'last_synced_at', 'created_at', 'status_badge'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'absen_ptkp_id';
        
        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $data = $query->skip($start)->take($length)->get();

        // Format data
        $formattedData = $data->map(function($ptkp) {
            return [
                'absen_ptkp_id' => $ptkp->absen_ptkp_id,
                'kriteria' => $ptkp->kriteria ?? '-',
                'status' => $ptkp->status ?? '-',
                'besaran_ptkp' => 'Rp ' . number_format($ptkp->besaran_ptkp ?? 0, 0, ',', '.'),
                'last_synced_at' => $ptkp->last_synced_at 
                    ? \Carbon\Carbon::parse($ptkp->last_synced_at)->format('d M Y H:i') 
                    : '-',
                'created_at' => $ptkp->created_at 
                    ? \Carbon\Carbon::parse($ptkp->created_at)->format('d M Y H:i') 
                    : '-',
                'status_badge' => $ptkp->deleted_at 
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
            
            Log::info('Manual PTKP sync triggered', [
                'user' => auth()->user()->email ?? 'unknown',
                'force' => $forceRefresh
            ]);
            
            $result = $this->syncService->syncAll($forceRefresh);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Manual PTKP sync failed', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 🔄 SYNC SPECIFIC PTKP
     */
    public function syncById($absenPtkpId)
    {
        try {
            $result = $this->syncService->syncById($absenPtkpId);
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
        
        return view('dashboard.dashboard-admin.ptkp.sync', compact('stats', 'health'));
    }
    
    /**
     * 🔄 TRIGGER SYNC VIA WEB
     */
    public function triggerSync(Request $request)
    {
        try {
            $forceRefresh = $request->boolean('force', false);
            
            Log::info('Manual PTKP sync triggered via web', [
                'user' => auth()->user()->name ?? 'unknown',
                'force' => $forceRefresh,
                'ip' => $request->ip()
            ]);
            
            $result = $this->syncService->syncAll($forceRefresh);
            
            if ($result['success']) {
                return redirect()
                    ->route('ptkp.sync.dashboard')
                    ->with('success', 'Sinkronisasi berhasil! ' . 
                        $result['stats']['new_inserted'] . ' inserted, ' .
                        $result['stats']['updated'] . ' updated, ' .
                        $result['stats']['deleted'] . ' deleted.');
            } else {
                return redirect()
                    ->route('ptkp.sync.dashboard')
                    ->with('error', 'Sinkronisasi gagal: ' . $result['message']);
            }
            
        } catch (\Exception $e) {
            Log::error('Manual PTKP sync failed via web', [
                'error' => $e->getMessage()
            ]);
            
            return redirect()
                ->route('ptkp.sync.dashboard')
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
}