<?php
// app/Http/Controllers/RangeBrutoSyncController.php

namespace App\Http\Controllers;

use App\Services\RangeBrutoSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class RangeBrutoSyncController extends Controller
{
    protected $syncService;
    
    public function __construct(RangeBrutoSyncService $syncService)
    {
        $this->syncService = $syncService;

        $this->middleware(function ($request, $next) {
            if (Gate::denies('range-bruto-sync')) {
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
        $query = \App\Models\RangeBruto::with('jenisTer');
        
        // Search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('absen_range_bruto_id', 'like', "%{$search}%")
                  ->orWhere('min_bruto', 'like', "%{$search}%")
                  ->orWhere('max_bruto', 'like', "%{$search}%")
                  ->orWhere('ter', 'like', "%{$search}%")
                  ->orWhereHas('jenisTer', function($q) use ($search) {
                      $q->where('jenis_ter', 'like', "%{$search}%");
                  });
            });
        }
        
        // Filter by Jenis TER
        if ($request->filled('jenis_ter_id')) {
            $query->where('absen_jenis_ter_id', $request->input('jenis_ter_id'));
        }

        // Total records
        $totalRecords = \App\Models\RangeBruto::count();
        $filteredRecords = $query->count();

        // Ordering
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');
        $columns = ['absen_range_bruto_id', 'jenis_ter', 'min_bruto', 'max_bruto', 'ter', 'last_synced_at', 'created_at', 'status_badge'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'absen_range_bruto_id';
        
        if ($orderColumn === 'jenis_ter') {
            $query->join('jenis_ters', 'range_brutos.absen_jenis_ter_id', '=', 'jenis_ters.id')
                  ->orderBy('jenis_ters.jenis_ter', $orderDir)
                  ->select('range_brutos.*');
        } else {
            $query->orderBy($orderColumn, $orderDir);
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $data = $query->skip($start)->take($length)->get();

        // Format data
        $formattedData = $data->map(function($rangeBruto) {
            return [
                'absen_range_bruto_id' => $rangeBruto->absen_range_bruto_id,
                'jenis_ter' => $rangeBruto->jenisTer->jenis_ter ?? '-',
                'min_bruto' => 'Rp ' . number_format($rangeBruto->min_bruto, 0, ',', '.'),
                'max_bruto' => $rangeBruto->max_bruto 
                    ? 'Rp ' . number_format($rangeBruto->max_bruto, 0, ',', '.') 
                    : '∞',
                'ter' => $rangeBruto->ter . '%',
                'range_display' => $rangeBruto->range_display,
                'last_synced_at' => $rangeBruto->last_synced_at 
                    ? \Carbon\Carbon::parse($rangeBruto->last_synced_at)->format('d M Y H:i') 
                    : '-',
                'created_at' => $rangeBruto->created_at 
                    ? \Carbon\Carbon::parse($rangeBruto->created_at)->format('d M Y H:i') 
                    : '-',
                'status_badge' => $rangeBruto->deleted_at 
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
            
            Log::info('Manual Range Bruto sync triggered', [
                'user' => auth()->user()->email ?? 'unknown',
                'force' => $forceRefresh
            ]);
            
            $result = $this->syncService->syncAll($forceRefresh);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Manual Range Bruto sync failed', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 🔄 SYNC SPECIFIC RANGE BRUTO
     */
    public function syncById($absenRangeBrutoId)
    {
        try {
            $result = $this->syncService->syncById($absenRangeBrutoId);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 🔄 SYNC BY JENIS TER
     */
    public function syncByJenisTer($absenJenisTerId)
    {
        try {
            $result = $this->syncService->syncByJenisTer($absenJenisTerId);
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
        
        // Get Jenis TER list for filter
        $jenisTers = \App\Models\JenisTer::select('id', 'jenis_ter')
            ->orderBy('jenis_ter')
            ->get();
        
        return view('dashboard.dashboard-admin.range-bruto.sync', compact('stats', 'health', 'jenisTers'));
    }
    
    /**
     * 🔄 TRIGGER SYNC VIA WEB
     */
    public function triggerSync(Request $request)
    {
        try {
            $forceRefresh = $request->boolean('force', false);
            $jenisTerId = $request->input('jenis_ter_id');
            
            Log::info('Manual Range Bruto sync triggered via web', [
                'user' => auth()->user()->name ?? 'unknown',
                'force' => $forceRefresh,
                'jenis_ter_id' => $jenisTerId,
                'ip' => $request->ip()
            ]);
            
            if ($jenisTerId) {
                $result = $this->syncService->syncByJenisTer($jenisTerId);
            } else {
                $result = $this->syncService->syncAll($forceRefresh);
            }
            
            if ($result['success']) {
                $message = 'Sinkronisasi berhasil! ' . 
                    $result['stats']['new_inserted'] . ' inserted, ' .
                    $result['stats']['updated'] . ' updated';
                
                if (isset($result['stats']['deleted'])) {
                    $message .= ', ' . $result['stats']['deleted'] . ' deleted';
                }
                
                if (isset($result['stats']['skipped_no_jenis_ter']) && $result['stats']['skipped_no_jenis_ter'] > 0) {
                    $message .= '. ' . $result['stats']['skipped_no_jenis_ter'] . ' skipped (Jenis TER not synced)';
                }
                
                return redirect()
                    ->route('range-bruto.sync.dashboard')
                    ->with('success', $message);
            } else {
                return redirect()
                    ->route('range-bruto.sync.dashboard')
                    ->with('error', 'Sinkronisasi gagal: ' . $result['message']);
            }
            
        } catch (\Exception $e) {
            Log::error('Manual Range Bruto sync failed via web', [
                'error' => $e->getMessage()
            ]);
            
            return redirect()
                ->route('range-bruto.sync.dashboard')
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