<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\PayrollCalculation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\KaryawanWithoutUserSyncService;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function adminDashboard()
    {
        if (!in_array(Auth::user()->role, ['admin'])) {
            abort(403, 'Unauthorized action.');
        }

        return view('dashboard.dashboard-admin.dashboard', [
            'title' => 'Dashboard Admin',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // MANAGEMENT DASHBOARD — view only (no data passed, AJAX loads all)
    // ─────────────────────────────────────────────────────────────────
    public function managementDashboard(Request $request)
    {
        if (!in_array(Auth::user()->role, ['management'])) {
            abort(403, 'Unauthorized action.');
        }

        return view('dashboard.dashboard-management.dashboard', [
            'title' => 'Dashboard Management',
        ]);
    }

    public function syncKaryawanWithoutUser(KaryawanWithoutUserSyncService $syncService)
    {
        // Gate check — hanya admin
        if (!in_array(Auth::user()->role, ['admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $result = $syncService->sync();

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    // ─────────────────────────────────────────────────────────────────
    // AJAX ENDPOINTS — reimbursement
    // ─────────────────────────────────────────────────────────────────

    /** GET /dashboard-management/reimbursement/summary?year= */
    public function reimbursementSummary(Request $request)
    {
        try {
            $year = (int) $request->input('year', now()->year);

            $agg = DB::table('reimbursements')
                ->whereNull('deleted_at')
                ->where('year_budget', $year)
                ->selectRaw('
                    COUNT(*)                                                             AS total_pengajuan,
                    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END)                        AS total_approved,
                    SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END)                        AS total_pending,
                    SUM(CASE WHEN status = 1
                             AND MONTH(approved_at) = ?
                             AND YEAR(approved_at)  = ? THEN 1 ELSE 0 END)             AS approved_bulan_ini,
                    SUM(CASE WHEN status = 0
                             AND MONTH(created_at)  = ?
                             AND YEAR(created_at)   = ? THEN 1 ELSE 0 END)             AS pending_bulan_ini
                ', [now()->month, $year, now()->month, $year])
                ->first();

            $nilaiApprovedBulanIni = (int) DB::table('reimbursements AS r')
                ->join('reimbursement_childs AS rc', 'rc.reimbursement_id', '=', 'r.id')
                ->whereNull('r.deleted_at')
                ->where('r.year_budget', $year)
                ->where('r.status', 1)
                ->whereMonth('r.approved_at', now()->month)
                ->whereYear('r.approved_at', $year)
                ->selectRaw('
                    SUM(
                        COALESCE(rc.tagihan_dokter,   0) +
                        COALESCE(rc.tagihan_obat,     0) +
                        COALESCE(rc.tagihan_kacamata, 0) +
                        COALESCE(rc.tagihan_gigi,     0)
                    ) AS total
                ')
                ->value('total');

            $karyawanMedical = (int) DB::table('master_salaries')
                ->where('year', $year)
                ->where('status_medical', '1')
                ->distinct()
                ->count('karyawan_id');

            $karyawanSudahKlaim = (int) DB::table('reimbursements')
                ->whereNull('deleted_at')
                ->where('year_budget', $year)
                ->where('status', 1)
                ->distinct()
                ->count('karyawan_id');

            // Breakdown per jenis tagihan (digabung di satu response agar hemat request)
            $bd = DB::table('reimbursements AS r')
                ->join('reimbursement_childs AS rc', 'rc.reimbursement_id', '=', 'r.id')
                ->whereNull('r.deleted_at')
                ->where('r.year_budget', $year)
                ->where('r.status', 1)
                ->selectRaw('
                    SUM(COALESCE(rc.tagihan_dokter,   0)) AS dokter,
                    SUM(COALESCE(rc.tagihan_obat,     0)) AS obat,
                    SUM(COALESCE(rc.tagihan_kacamata, 0)) AS kacamata,
                    SUM(COALESCE(rc.tagihan_gigi,     0)) AS gigi
                ')
                ->first();

            return response()->json([
                'total_pengajuan'                => (int) ($agg->total_pengajuan    ?? 0),
                'total_approved'                 => (int) ($agg->total_approved     ?? 0),
                'total_pending'                  => (int) ($agg->total_pending      ?? 0),
                'approved_bulan_ini'             => (int) ($agg->approved_bulan_ini ?? 0),
                'pending_bulan_ini'              => (int) ($agg->pending_bulan_ini  ?? 0),
                'total_nilai_approved'           => $nilaiApprovedBulanIni,
                'total_karyawan_pengaju'         => $karyawanSudahKlaim,
                'karyawan_medical'               => $karyawanMedical,
                'breakdown' => [
                    'dokter'   => (int) ($bd->dokter   ?? 0),
                    'obat'     => (int) ($bd->obat     ?? 0),
                    'kacamata' => (int) ($bd->kacamata ?? 0),
                    'gigi'     => (int) ($bd->gigi     ?? 0),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /** GET /dashboard-management/reimbursement/tren?year= */
    public function reimbursementTren(Request $request)
    {
        try {
            $year = (int) $request->input('year', now()->year);

            $rows = DB::table('reimbursements AS r')
                ->join('reimbursement_childs AS rc', 'rc.reimbursement_id', '=', 'r.id')
                ->whereNull('r.deleted_at')
                ->where('r.year_budget', $year)
                ->where('r.status', 1)
                ->selectRaw('
                    MONTH(r.approved_at) AS month,
                    SUM(
                        COALESCE(rc.tagihan_dokter,   0) +
                        COALESCE(rc.tagihan_obat,     0) +
                        COALESCE(rc.tagihan_kacamata, 0) +
                        COALESCE(rc.tagihan_gigi,     0)
                    ) AS total_nominal,
                    COUNT(DISTINCT r.id) AS total_count
                ')
                ->groupByRaw('MONTH(r.approved_at)')
                ->orderByRaw('MONTH(r.approved_at)')
                ->get()
                ->keyBy('month');

            $result = [];
            for ($m = 1; $m <= 12; $m++) {
                $row      = $rows->get($m);
                $result[] = [
                    'month'         => $m,
                    'total_nominal' => (int) ($row->total_nominal ?? 0),
                    'total_count'   => (int) ($row->total_count  ?? 0),
                ];
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /** GET /dashboard-management/reimbursement/top-karyawan?year= */
    public function reimbursementTopKaryawan(Request $request)
    {
        try {
            $year = (int) $request->input('year', now()->year);

            $data = DB::table('reimbursements AS r')
                ->join('reimbursement_childs AS rc', 'rc.reimbursement_id', '=', 'r.id')
                ->leftJoin('sync_karyawan_jabatan_perusahaans AS s', 's.absen_karyawan_id', '=', 'r.karyawan_id')
                ->leftJoin('companies AS c', 'c.absen_company_id', '=', 'r.company_id')
                ->whereNull('r.deleted_at')
                ->where(fn($q) => $q->whereNull('s.deleted_at')->orWhereNull('s.absen_karyawan_id'))
                ->where('r.year_budget', $year)
                ->where('r.status', 1)
                ->selectRaw('
                    r.karyawan_id,
                    s.nama_lengkap,
                    s.nik,
                    c.company_name,
                    SUM(
                        COALESCE(rc.tagihan_dokter,   0) +
                        COALESCE(rc.tagihan_obat,     0) +
                        COALESCE(rc.tagihan_kacamata, 0) +
                        COALESCE(rc.tagihan_gigi,     0)
                    ) AS total_klaim,
                    COUNT(DISTINCT r.id) AS jumlah_klaim
                ')
                ->groupBy('r.karyawan_id', 's.nama_lengkap', 's.nik', 'c.company_name')
                ->orderByDesc('total_klaim')
                ->limit(10)
                ->get();

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /** GET /dashboard-management/reimbursement/per-bulan?year= */
    public function reimbursementPerBulan(Request $request)
    {
        try {
            $year = (int) $request->input('year', now()->year);

            $rows = DB::table('reimbursements')
                ->whereNull('deleted_at')
                ->where('year_budget', $year)
                ->selectRaw('
                    MONTH(created_at) AS month,
                    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS approved,
                    SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending
                ')
                ->groupByRaw('MONTH(created_at)')
                ->orderByRaw('MONTH(created_at)')
                ->get()
                ->keyBy('month');

            $result = [];
            for ($m = 1; $m <= 12; $m++) {
                $row      = $rows->get($m);
                $result[] = [
                    'month'    => $m,
                    'approved' => (int) ($row->approved ?? 0),
                    'pending'  => (int) ($row->pending  ?? 0),
                ];
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /** GET /dashboard-management/reimbursement/periode-aktif */
    public function reimbursementPeriodeAktif()
    {
        try {
            $periode = DB::table('reimbursement_periods')
                ->whereRaw('CURDATE() BETWEEN expired_reimburs_start AND end_reimburs_start')
                ->first();

            // null → JSON null, frontend sudah handle "tidak ada periode aktif"
            return response()->json($periode);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /** GET /dashboard-management/reimbursement/pengajuan?year=&page=&search=&status= */
    public function reimbursementPengajuan(Request $request)
    {
        try {
            $year    = (int)    $request->input('year',   now()->year);
            $search  = (string) $request->input('search', '');
            $status  = $request->input('status', ''); // '' | '0' | '1'
            $perPage = 50;

            $childSub = DB::table('reimbursement_childs')
                ->selectRaw('
                    reimbursement_id,
                    SUM(COALESCE(tagihan_dokter,   0)) AS tagihan_dokter,
                    SUM(COALESCE(tagihan_obat,     0)) AS tagihan_obat,
                    SUM(COALESCE(tagihan_kacamata, 0)) AS tagihan_kacamata,
                    SUM(COALESCE(tagihan_gigi,     0)) AS tagihan_gigi,
                    SUM(
                        COALESCE(tagihan_dokter,   0) +
                        COALESCE(tagihan_obat,     0) +
                        COALESCE(tagihan_kacamata, 0) +
                        COALESCE(tagihan_gigi,     0)
                    ) AS total_amount
                ')
                ->groupBy('reimbursement_id');

            $query = DB::table('reimbursements AS r')
                ->join('sync_karyawan_jabatan_perusahaans AS s', 's.absen_karyawan_id', '=', 'r.karyawan_id')
                ->leftJoinSub($childSub, 'rc', 'rc.reimbursement_id', '=', 'r.id')
                ->whereNull('r.deleted_at')
                ->whereNull('s.deleted_at')
                ->where('r.year_budget', $year);

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('s.nama_lengkap', 'like', "%{$search}%")
                      ->orWhere('s.nik',         'like', "%{$search}%")
                      ->orWhere('r.id_recapan',  'like', "%{$search}%");
                });
            }

            if ($status !== '') {
                $query->where('r.status', (int) $status);
            }

            $paginator = $query
                ->select(
                    'r.id',
                    'r.id_recapan',
                    'r.periode_slip',
                    'r.status',
                    'r.approved_at',
                    's.nama_lengkap',
                    's.nik',
                     DB::raw('COALESCE(s.nama_lengkap, "-") AS nama_lengkap'),  // ← tambah COALESCE
                    DB::raw('COALESCE(s.nik, "-") AS nik'), 
                    DB::raw('COALESCE(rc.tagihan_dokter,   0) AS tagihan_dokter'),
                    DB::raw('COALESCE(rc.tagihan_obat,     0) AS tagihan_obat'),
                    DB::raw('COALESCE(rc.tagihan_kacamata, 0) AS tagihan_kacamata'),
                    DB::raw('COALESCE(rc.tagihan_gigi,     0) AS tagihan_gigi'),
                    DB::raw('COALESCE(rc.total_amount,     0) AS total_amount')
                )
                ->orderByDesc('r.created_at')
                ->paginate($perPage);

            return response()->json([
                'data'         => $paginator->items(),
                'total'        => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem()  ?? 0,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // ADMIN DASHBOARD METHODS (tidak ada perubahan)
    // ─────────────────────────────────────────────────────────────────

    public function getPeriodes()
    {
        try {
            $periodes = PayrollCalculation::select('periode')
                ->distinct()->orderBy('periode', 'desc')->pluck('periode');
            return response()->json(['success' => true, 'data' => $periodes]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getStatistics(Request $request)
    {
        try {
            $periode = $request->input('periode');
            if (!$periode) {
                return response()->json(['success' => false, 'message' => 'Periode is required'], 400);
            }

            $counts = PayrollCalculation::where('periode', $periode)
                ->selectRaw('
                    COUNT(*) as total_payroll,
                    SUM(CASE WHEN is_released = 0 THEN 1 ELSE 0 END) as draft_count,
                    SUM(CASE WHEN is_released = 1 AND is_released_slip = 0 THEN 1 ELSE 0 END) as released_count,
                    SUM(CASE WHEN is_released = 1 AND is_released_slip = 1 THEN 1 ELSE 0 END) as released_slip
                ')
                ->first();

            $karyawanSudahInput = PayrollCalculation::where('periode', $periode)
                ->pluck('karyawan_id')->unique();

            $belumInput = Karyawan::where('status_resign', false)
                ->whereNull('deleted_at')
                ->whereNotIn('absen_karyawan_id', $karyawanSudahInput)
                ->count();
            
            $karyawanBelumPunyaAkun = \App\Models\SyncKaryawanWithoutUser::count();


            return response()->json([
                'success' => true,
                'data'    => [
                    'total_payroll'  => $counts->total_payroll  ?? 0,
                    'released_count' => $counts->released_count ?? 0,
                    'released_slip'  => $counts->released_slip  ?? 0,
                    'draft_count'    => $counts->draft_count    ?? 0,
                    'belum_input'    => $belumInput,
                    'belum_punya_akun'       => $karyawanBelumPunyaAkun, 
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function karyawanBelumInput(Request $request)
    {
        if (!in_array(Auth::user()->role, ['admin'])) {
            abort(403, 'Unauthorized action.');
        }

        $periode = $request->input('periode') ?: PayrollCalculation::select('periode')
            ->distinct()->orderBy('periode', 'desc')->value('periode');

        return view('dashboard.dashboard-admin.karyawan-belum-ada-payrolls.index', [
            'title'   => 'Karyawan Belum Diinput Payroll',
            'periode' => $periode,
        ]);
    }

    public function karyawanBelumInputData(Request $request)
    {
        try {
            $periode = $request->input('periode');
            if (!$periode) {
                return response()->json(['error' => 'Periode is required'], 400);
            }

            $sudahInput = PayrollCalculation::where('periode', $periode)
                ->pluck('karyawan_id')->unique()->toArray();

            $query = Karyawan::where('status_resign', false)
                ->whereNull('deleted_at')
                ->whereNotIn('absen_karyawan_id', $sudahInput)
                ->select(['id', 'nik', 'nama_lengkap', 'email_pribadi', 'telp_pribadi', 'join_date', 'jenis_kelamin', 'absen_karyawan_id']);

            $totalRecords = (clone $query)->count();
            $search       = $request->input('search.value');

            if ($search) {
                $query->where(fn($q) =>
                    $q->where('nik', 'like', "%{$search}%")
                      ->orWhere('nama_lengkap', 'like', "%{$search}%")
                      ->orWhere('email_pribadi', 'like', "%{$search}%")
                );
            }

            $filteredRecords = (clone $query)->count();
            $columns         = ['id', 'nik', 'nama_lengkap', 'email_pribadi', 'telp_pribadi', 'join_date', 'jenis_kelamin'];
            $orderCol        = $columns[$request->input('order.0.column', 1)] ?? 'nama_lengkap';
            $start           = $request->input('start', 0);
            $length          = $request->input('length', 25);

            $data = $query->orderBy($orderCol, $request->input('order.0.dir', 'asc'))
                ->skip($start)->take($length)->get();

            $rows = $data->map(fn($k, $idx) => [
                'no'            => $start + $idx + 1,
                'nik'           => $k->nik ?? '-',
                'nama_lengkap'  => $k->nama_lengkap,
                'email_pribadi' => $k->email_pribadi ?? '-',
                'telp_pribadi'  => $k->telp_pribadi ?? '-',
                'join_date'     => $k->join_date
                    ? \Carbon\Carbon::parse($k->join_date)->format('d M Y') : '-',
                'jenis_kelamin' => $k->jenis_kelamin === 'L' ? 'Laki-laki'
                    : ($k->jenis_kelamin === 'P' ? 'Perempuan' : '-'),
            ]);

            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data'            => $rows,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}