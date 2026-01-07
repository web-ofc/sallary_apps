<?php
// app/Http/Controllers/KaryawanSyncController.php (DI APLIKASI GAJI)

namespace App\Http\Controllers;

use App\Services\KaryawanSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
class KaryawanSyncController extends Controller
{
    
    protected $syncService;
    
    public function __construct(KaryawanSyncService $syncService)
    {
        $this->syncService = $syncService;

        $this->middleware(function ($request, $next) {
            if (Gate::denies('karyawan-sync')) {
                abort(403, 'Unauthorized action.');
            }

            return $next($request);
        });
    }
    
    /**
     * 🔄 TRIGGER FULL SYNC (via API/UI button)
     * POST /api/karyawan/sync
     */
    public function syncAll(Request $request)
    {
        try {
            $forceRefresh = $request->boolean('force', false);
            
            Log::info('Manual sync triggered', [
                'user' => auth()->user()->email ?? 'unknown',
                'force' => $forceRefresh
            ]);
            
            $result = $this->syncService->syncAll($forceRefresh);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Manual sync failed', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 🔄 SYNC SPECIFIC KARYAWAN
     * POST /api/karyawan/sync/{absenKaryawanId}
     */
    public function syncById($absenKaryawanId)
    {
        try {
            $result = $this->syncService->syncById($absenKaryawanId);
            
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
     * GET /api/karyawan/sync/stats
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
     * GET /karyawan/sync/dashboard
     */
    public function dashboard()
    {
        $stats = $this->syncService->getSyncStats();
        $health = $this->syncService->checkSyncHealth(24);
        
        return view('dashboard.dashboard-admin.karyawan.sysnc', compact('stats', 'health'));
    }
    
    public function datatable(Request $request)
    {
        $query = \App\Models\Karyawan::query();

        // Search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                ->orWhere('nik', 'like', "%{$search}%")
                ->orWhere('email_pribadi', 'like', "%{$search}%")
                ->orWhere('telp_pribadi', 'like', "%{$search}%");
            });
        }

        $totalRecords = \App\Models\Karyawan::count();
        $filteredRecords = $query->count();

        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['id', 'absen_karyawan_id', 'nik', 'nama_lengkap', 'email_pribadi', 'telp_pribadi', 'join_date', 'tempat_tanggal_lahir', 'jenis_kelamin', 'status_resign'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';
        
        $query->orderBy($orderColumn, $orderDir);

        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $data = $query->skip($start)->take($length)->get();

        $formattedData = $data->map(function($karyawan) {
            return [
                'id' => $karyawan->id,
                'absen_karyawan_id' => $karyawan->absen_karyawan_id,
                'nik' => $karyawan->nik ?? '-',
                'nama_lengkap' => $karyawan->nama_lengkap,
                'email_pribadi' => $karyawan->email_pribadi ?? '-',
                'telp_pribadi' => $karyawan->telp_pribadi ?? '-',
                'join_date' => $karyawan->join_date ? \Carbon\Carbon::parse($karyawan->join_date)->format('d M Y') : '-',
                'tempat_tanggal_lahir' => $karyawan->tempat_tanggal_lahir ?? '-',
                'jenis_kelamin' => $karyawan->jenis_kelamin 
                    ? ($karyawan->jenis_kelamin === 'L' 
                        ? '<span class="badge bg-primary text-white">Laki-laki</span>' 
                        : '<span class="badge bg-danger text-white">Perempuan</span>')
                    : '-',
                'status_resign' => $karyawan->status_resign 
                    ? '<span class="badge bg-danger text-white">Resign</span>' 
                    : '<span class="badge bg-success text-white">Aktif</span>',
                
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
     * POST /karyawan/sync/trigger
     */
    public function triggerSync(Request $request)
    {
        try {
            $forceRefresh = $request->boolean('force', false);
            
            Log::info('Manual sync triggered via web', [
                'user' => auth()->user()->name ?? 'unknown',
                'force' => $forceRefresh,
                'ip' => $request->ip()
            ]);
            
            // Jalankan sync
            $result = $this->syncService->syncAll($forceRefresh);
            
            if ($result['success']) {
                return redirect()
                    ->route('karyawan.sync.dashboard')
                    ->with('success', 'Sinkronisasi berhasil! ' . 
                        $result['stats']['new_inserted'] . ' inserted, ' .
                        $result['stats']['updated'] . ' updated, ' .
                        $result['stats']['deleted'] . ' deleted.');
            } else {
                return redirect()
                    ->route('karyawan.sync.dashboard')
                    ->with('error', 'Sinkronisasi gagal: ' . $result['message']);
            }
            
        } catch (\Exception $e) {
            Log::error('Manual sync failed via web', [
                'error' => $e->getMessage()
            ]);
            
            return redirect()
                ->route('karyawan.sync.dashboard')
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }
    
    /**
     * 📡 GET SYNC STATUS (AJAX)
     * GET /karyawan/sync/status
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