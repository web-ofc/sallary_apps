<?php
// app/Http/Controllers/CompanySyncController.php (DI APLIKASI GAJI)

namespace App\Http\Controllers;

use App\Services\CompanySyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Gate;
class CompanySyncController extends Controller
{
    protected $syncService;
    
    public function __construct(CompanySyncService $syncService)
    {
        $this->syncService = $syncService;

        $this->middleware(function ($request, $next) {
            if (Gate::denies('company-sync')) {
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
        $query = \App\Models\Company::query();
        // Search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('absen_company_id', 'like', "%{$search}%");
            });
        }

        // Total records
        $totalRecords = \App\Models\Company::count();
        $filteredRecords = $query->count();

        // Ordering
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['id', 'absen_company_id', 'code', 'company_name', 'last_synced_at', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';
        
        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $data = $query->skip($start)->take($length)->get();

        // Format data
        $formattedData = $data->map(function($company) {
            return [
                'id' => $company->id,
                'absen_company_id' => $company->absen_company_id,
                'code' => $company->code ?? '-',
                'company_name' => $company->company_name,
                'created_at' => $company->created_at->format('d M Y H:i'),
                'status' => $company->deleted_at ? '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Deleted</span>' : '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>',
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
            
            Log::info('Manual companies sync triggered', [
                'user' => auth()->user()->email ?? 'unknown',
                'force' => $forceRefresh
            ]);
            
            $result = $this->syncService->syncAll($forceRefresh);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Manual companies sync failed', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 🔄 SYNC SPECIFIC COMPANY
     */
    public function syncById($absenCompanyId)
    {
        try {
            $result = $this->syncService->syncById($absenCompanyId);
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
        
        return view('dashboard.dashboard-admin.companies.sync', compact('stats', 'health'));
    }
    
    /**
     * 🔄 TRIGGER SYNC VIA WEB
     */
    public function triggerSync(Request $request)
    {
        try {
            $forceRefresh = $request->boolean('force', false);
            
            Log::info('Manual companies sync triggered via web', [
                'user' => auth()->user()->name ?? 'unknown',
                'force' => $forceRefresh,
                'ip' => $request->ip()
            ]);
            
            $result = $this->syncService->syncAll($forceRefresh);
            
            if ($result['success']) {
                return redirect()
                    ->route('companies.sync.dashboard')
                    ->with('success', 'Sinkronisasi berhasil! ' . 
                        $result['stats']['new_inserted'] . ' inserted, ' .
                        $result['stats']['updated'] . ' updated, ' .
                        $result['stats']['deleted'] . ' deleted.');
            } else {
                return redirect()
                    ->route('companies.sync.dashboard')
                    ->with('error', 'Sinkronisasi gagal: ' . $result['message']);
            }
            
        } catch (\Exception $e) {
            Log::error('Manual companies sync failed via web', [
                'error' => $e->getMessage()
            ]);
            
            return redirect()
                ->route('companies.sync.dashboard')
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