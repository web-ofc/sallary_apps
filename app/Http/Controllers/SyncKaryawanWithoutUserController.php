<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SyncKaryawanWithoutUser;
use App\Services\KaryawanWithoutUserSyncService;

class SyncKaryawanWithoutUserController extends Controller
{
     public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * POST /sync-karyawan-without-user/sync
     * Dipanggil AJAX dari card dashboard → sync → redirect ke index
     */
    public function sync(KaryawanWithoutUserSyncService $syncService)
    {
        if (!in_array(Auth::user()->role, ['admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $result = $syncService->sync();

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * GET /sync-karyawan-without-user
     * Halaman list karyawan tanpa user (dari tabel sync)
     */
    public function index()
    {
        if (!in_array(Auth::user()->role, ['admin'])) {
            abort(403, 'Unauthorized action.');
        }

        $lastSync = SyncKaryawanWithoutUser::withTrashed()
            ->max('last_synced_at');

        return view('dashboard.dashboard-admin.sync-karyawan-without-user.index', [
            'title'    => 'Karyawan Belum Punya Akun',
            'lastSync' => $lastSync,
        ]);
    }

    /**
     * GET /sync-karyawan-without-user/data
     * DataTables server-side
     */
    public function data(Request $request)
    {
        if (!in_array(Auth::user()->role, ['admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = SyncKaryawanWithoutUser::query()
            ->select([
                'id', 'absen_karyawan_id', 'nik', 'nama_lengkap',
                'email_pribadi', 'telp_pribadi', 'join_date',
                'jenis_kelamin', 'status_pernikahan', 'last_synced_at',
            ]);

        $totalRecords = (clone $query)->count();

        $search = $request->input('search.value');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('email_pribadi', 'like', "%{$search}%");
            });
        }

        $filteredRecords = (clone $query)->count();

        $columns  = ['id', 'nik', 'nama_lengkap', 'email_pribadi', 'telp_pribadi', 'join_date', 'jenis_kelamin'];
        $orderCol = $columns[$request->input('order.0.column', 2)] ?? 'nama_lengkap';
        $orderDir = $request->input('order.0.dir', 'asc');
        $start    = (int) $request->input('start', 0);
        $length   = (int) $request->input('length', 25);

        $data = $query->orderBy($orderCol, $orderDir)
            ->skip($start)->take($length)->get();

        $rows = $data->map(function ($k, $idx) use ($start) {
            return [
                'no'                => $start + $idx + 1,
                'absen_karyawan_id' => $k->absen_karyawan_id,
                'nik'               => $k->nik ?? '-',
                'nama_lengkap'      => $k->nama_lengkap,
                'email_pribadi'     => $k->email_pribadi ?? '-',
                'telp_pribadi'      => $k->telp_pribadi ?? '-',
                'join_date'         => $k->join_date
                    ? $k->join_date->format('d M Y') : '-',
                'jenis_kelamin'     => $k->jenis_kelamin === 'L' ? 'Laki-laki'
                    : ($k->jenis_kelamin === 'P' ? 'Perempuan' : '-'),
                'status_pernikahan' => $k->status_pernikahan ?? '-',
                'last_synced_at'    => $k->last_synced_at
                    ? $k->last_synced_at->setTimezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB'
                    : '-',
            ];
        });

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $rows,
        ]);
    }
}
