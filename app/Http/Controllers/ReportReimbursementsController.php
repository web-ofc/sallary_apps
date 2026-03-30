<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Models\Karyawan;
use App\Models\Reimbursement;
use Carbon\Carbon;

class ReportReimbursementsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Gate::denies('report-reimbursements')) {
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        });
    }

    /**
     * Halaman utama laporan analisa jenis penyakit
     */
    public function index()
    {
        return view('dashboard.dashboard-admin.report-reimburstment.index');
    }

    /**
     * POST Datatable server-side — analisa jenis penyakit per karyawan
     * Kolom: Nama Penyakit, Jumlah Sakit (count), Total Tagihan
     */
    public function getData(Request $request)
    {
        $request->validate([
            'karyawan_id'  => 'required|integer',
            'filter_range' => 'required|string',
            'date_from'    => 'nullable|date',
            'date_to'      => 'nullable|date',
        ]);

        // Resolve date range
        [$dateFrom, $dateTo] = $this->resolveDateRange(
            $request->filter_range,
            $request->date_from,
            $request->date_to
        );

        // Query: group by jenis penyakit, hitung count & total tagihan
        $query = DB::table('reimbursement_childs as rc')
            ->join('reimbursements as r', 'r.id', '=', 'rc.reimbursement_id')
            ->join('jenis_penyakits as jp', 'jp.id', '=', 'rc.jenispenyakit_id')
            ->where('r.karyawan_id', $request->karyawan_id)
            ->whereBetween('r.created_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ])
            ->whereNull('r.deleted_at')
            ->whereNotNull('rc.jenispenyakit_id')
            ->select([
                'jp.id',
                'jp.kode',
                'jp.nama_penyakit',
                DB::raw('COUNT(rc.id) as jumlah_sakit'),
                DB::raw('SUM(COALESCE(rc.tagihan_dokter,0) + COALESCE(rc.tagihan_obat,0) + COALESCE(rc.tagihan_kacamata,0) + COALESCE(rc.tagihan_gigi,0)) as total_tagihan'),
            ])
            ->groupBy('jp.id', 'jp.kode', 'jp.nama_penyakit');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('nama_penyakit_display', function ($row) {
                return '
                    <div class="d-flex flex-column">
                        <span class="fw-bold text-dark">' . e($row->nama_penyakit) . '</span>
                        ' . ($row->kode ? '<span class="text-muted fs-7">' . e($row->kode) . '</span>' : '') . '
                    </div>
                ';
            })
            ->addColumn('jumlah_sakit_display', function ($row) {
                $badgeColor = $row->jumlah_sakit >= 5 ? 'danger' : ($row->jumlah_sakit >= 3 ? 'warning' : 'success');
                return '
                    <span class="badge badge-light-' . $badgeColor . ' fs-7 fw-bold px-3 py-2">
                        ' . $row->jumlah_sakit . ' kali
                    </span>
                ';
            })
            ->addColumn('total_tagihan_display', function ($row) {
                return '<span class="fw-semibold text-dark">Rp ' . number_format($row->total_tagihan, 0, ',', '.') . '</span>';
            })
            ->rawColumns(['nama_penyakit_display', 'jumlah_sakit_display', 'total_tagihan_display'])
            ->make(true);
    }

    /**
     * Endpoint Select2 — search karyawan dari DB lokal
     */
    public function searchKaryawan(Request $request)
    {
        $search = $request->get('q', '');

        $karyawans = Karyawan::where('status_resign', false)
            ->where(function ($query) use ($search) {
                $query->where('nama_lengkap', 'like', "%{$search}%")
                      ->orWhere('nik', 'like', "%{$search}%");
            })
            ->select('absen_karyawan_id as id', 'nama_lengkap as text', 'nik')
            ->limit(20)
            ->get()
            ->map(function ($k) {
                return [
                    'id'   => $k->id,
                    'text' => $k->text . ($k->nik ? ' — ' . $k->nik : ''),
                ];
            });

        return response()->json(['results' => $karyawans]);
    }

    /**
     * Resolve date range berdasarkan pilihan filter
     */
    private function resolveDateRange(string $filterRange, ?string $dateFrom, ?string $dateTo): array
    {
        return match ($filterRange) {
            'this_month'  => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month'  => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'last_7_days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'this_year'   => [now()->startOfYear(), now()->endOfYear()],
            'custom'      => [$dateFrom ?? now()->startOfMonth(), $dateTo ?? now()],
            default       => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    /**
     * Halaman pivot rekap penyakit
     */
    public function pivot()
    {
        return view('dashboard.dashboard-admin.rekap-reimburstment-all-karyawan.index');
    }

    /**
     * GET — Ambil semua jenis penyakit aktif untuk build kolom dinamis
     */
    public function getPivotColumns()
    {
        $columns = DB::table('jenis_penyakits')
            ->where('is_active', true)
            ->orderBy('nama_penyakit')
            ->select('id', 'kode', 'nama_penyakit')
            ->get();

        return response()->json(['columns' => $columns]);
    }

     /**
     * POST — Stats ringkas untuk dashboard cards pivot
     */
    public function getPivotStats(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange(
            $request->input('filter_range', 'this_year'),
            $request->input('date_from'),
            $request->input('date_to')
        );

        $base = DB::table('reimbursement_childs as rc')
            ->join('reimbursements as r', 'r.id', '=', 'rc.reimbursement_id')
            ->whereNull('r.deleted_at')
            ->whereNotNull('rc.jenispenyakit_id')
            ->whereBetween('r.created_at', [
                \Carbon\Carbon::parse($dateFrom)->startOfDay(),
                \Carbon\Carbon::parse($dateTo)->endOfDay(),
            ]);

        $totalKaryawan = (clone $base)->distinct()->count('r.karyawan_id');
        $totalKasus    = (clone $base)->count('rc.id');
        $totalTagihan  = (clone $base)->sum(DB::raw(
            'COALESCE(rc.tagihan_dokter,0) + COALESCE(rc.tagihan_obat,0) + COALESCE(rc.tagihan_kacamata,0) + COALESCE(rc.tagihan_gigi,0)'
        ));
        $totalPenyakit = DB::table('jenis_penyakits')->where('is_active', true)->count();

        return response()->json([
            'total_karyawan' => $totalKaryawan,
            'total_kasus'    => $totalKasus,
            'total_tagihan'  => (int) $totalTagihan,
            'total_penyakit' => $totalPenyakit,
        ]);
    }

    /**
     * POST — Top 5 penyakit berdasarkan jumlah kasus pada periode
     */
    public function getPivotTopPenyakit(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange(
            $request->input('filter_range', 'this_year'),
            $request->input('date_from'),
            $request->input('date_to')
        );

        $data = DB::table('reimbursement_childs as rc')
            ->join('reimbursements as r', 'r.id', '=', 'rc.reimbursement_id')
            ->join('jenis_penyakits as jp', 'jp.id', '=', 'rc.jenispenyakit_id')
            ->whereNull('r.deleted_at')
            ->whereNotNull('rc.jenispenyakit_id')
            ->whereBetween('r.created_at', [
                \Carbon\Carbon::parse($dateFrom)->startOfDay(),
                \Carbon\Carbon::parse($dateTo)->endOfDay(),
            ])
            ->select([
                'jp.id',
                'jp.nama_penyakit',
                DB::raw('COUNT(rc.id) as total_kasus'),
                DB::raw('SUM(COALESCE(rc.tagihan_dokter,0) + COALESCE(rc.tagihan_obat,0) + COALESCE(rc.tagihan_kacamata,0) + COALESCE(rc.tagihan_gigi,0)) as total_tagihan'),
            ])
            ->groupBy('jp.id', 'jp.nama_penyakit')
            ->orderByDesc('total_kasus')
            ->limit(5)
            ->get();

        return response()->json(['data' => $data]);
    }


    /**
     * POST — DataTables server-side pivot dengan filter date range
     */
    public function getPivotData(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange(
            $request->input('filter_range', 'this_year'),
            $request->input('date_from'),
            $request->input('date_to')
        );

        $dateFromParsed = \Carbon\Carbon::parse($dateFrom)->startOfDay();
        $dateToParsed   = \Carbon\Carbon::parse($dateTo)->endOfDay();

        // Ambil semua jenis penyakit aktif
        $penyakits = DB::table('jenis_penyakits')
            ->where('is_active', true)
            ->orderBy('nama_penyakit')
            ->select('id', 'nama_penyakit')
            ->get();

        // Base query: karyawan yang pernah ada di reimbursement_childs pada periode ini
        $query = DB::table('karyawans as k')
            ->whereExists(function ($sub) use ($dateFromParsed, $dateToParsed) {
                $sub->select(DB::raw(1))
                    ->from('reimbursements as r')
                    ->join('reimbursement_childs as rc', 'rc.reimbursement_id', '=', 'r.id')
                    ->whereColumn('r.karyawan_id', 'k.absen_karyawan_id')
                    ->whereNull('r.deleted_at')
                    ->whereNotNull('rc.jenispenyakit_id')
                    ->whereBetween('r.created_at', [$dateFromParsed, $dateToParsed]);
            })
            ->where('k.status_resign', false)
            ->select(['k.absen_karyawan_id', 'k.nama_lengkap', 'k.nik']);

        $totalRecords = (clone $query)->count();

        // Search
        $searchValue = $request->input('search.value', '');
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('k.nama_lengkap', 'like', "%{$searchValue}%")
                  ->orWhere('k.nik', 'like', "%{$searchValue}%");
            });
        }

        $filteredRecords = (clone $query)->count();

        // Order
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir         = $request->input('order.0.dir', 'asc');
        $orderableMap     = [0 => 'k.nama_lengkap', 1 => 'k.nik'];
        $orderColumn      = $orderableMap[$orderColumnIndex] ?? 'k.nama_lengkap';
        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $start     = (int) $request->input('start', 0);
        $length    = (int) $request->input('length', 10);
        $karyawans = $query->offset($start)->limit($length)->get();

        if ($karyawans->isEmpty()) {
            return response()->json([
                'draw'            => (int) $request->input('draw', 1),
                'recordsTotal'    => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data'            => [],
            ]);
        }

        // Pivot data untuk karyawan di halaman ini + filter date
        $karyawanIds = $karyawans->pluck('absen_karyawan_id')->toArray();

        $pivotRaw = DB::table('reimbursement_childs as rc')
            ->join('reimbursements as r', 'r.id', '=', 'rc.reimbursement_id')
            ->whereIn('r.karyawan_id', $karyawanIds)
            ->whereNull('r.deleted_at')
            ->whereNotNull('rc.jenispenyakit_id')
            ->whereBetween('r.created_at', [$dateFromParsed, $dateToParsed])
            ->select([
                'r.karyawan_id',
                'rc.jenispenyakit_id',
                DB::raw('COUNT(rc.id) as jumlah_sakit'),
                DB::raw('SUM(COALESCE(rc.tagihan_dokter,0) + COALESCE(rc.tagihan_obat,0) + COALESCE(rc.tagihan_kacamata,0) + COALESCE(rc.tagihan_gigi,0)) as total_tagihan'),
            ])
            ->groupBy('r.karyawan_id', 'rc.jenispenyakit_id')
            ->get();

        // Index pivot
        $pivotIndex = [];
        foreach ($pivotRaw as $row) {
            $pivotIndex[$row->karyawan_id][$row->jenispenyakit_id] = [
                'kali'    => (int) $row->jumlah_sakit,
                'tagihan' => (int) $row->total_tagihan,
            ];
        }

        // Build rows
        $data = [];
        $no   = $start + 1;

        foreach ($karyawans as $k) {
            $row        = ['no' => $no++, 'nama_lengkap' => $k->nama_lengkap, 'nik' => $k->nik ?? '-'];
            $grandTotal = 0;
            $grandKali  = 0;

            foreach ($penyakits as $p) {
                $cell = $pivotIndex[$k->absen_karyawan_id][$p->id] ?? null;
                if ($cell) {
                    $grandTotal += $cell['tagihan'];
                    $grandKali  += $cell['kali'];
                    $row['penyakit_' . $p->id] = $cell;
                } else {
                    $row['penyakit_' . $p->id] = null;
                }
            }

            $row['grand_total_kali']    = $grandKali;
            $row['grand_total_tagihan'] = $grandTotal;
            $data[] = $row;
        }

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }
}