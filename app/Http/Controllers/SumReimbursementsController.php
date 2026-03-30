<?php

namespace App\Http\Controllers;

use App\Exports\ReimbursementChildSumExport;
use App\Models\ReimbursementChildSum;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Gate;
class SumReimbursementsController extends Controller
{
    
     public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Gate::denies('sum-reimburstment')) {
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        });
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Ambil distinct periode_slip untuk filter dropdown
        $periodeList = ReimbursementChildSum::select('periode_slip')
            ->distinct()
            ->orderBy('periode_slip', 'desc')
            ->pluck('periode_slip');

        return view('dashboard.dashboard-management.sum-reimburstment.index', compact('periodeList'));
    }

    /**
     * Server-side DataTables data endpoint.
     */
    public function getData(Request $request)
    {
        $query = ReimbursementChildSum::with('karyawan')
            ->select([
                'reimbursement_child_sum.karyawan_id',
                'reimbursement_child_sum.periode_slip',
                'reimbursement_child_sum.status',
                'reimbursement_child_sum.jumlah_reimbursement',
                'reimbursement_child_sum.total_harga',
            ]);

        // Filter periode_slip
        if ($request->filled('periode_slip')) {
            $query->where('reimbursement_child_sum.periode_slip', $request->periode_slip);
        }

        // Filter search (nama karyawan atau karyawan_id)
        if ($request->filled('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->whereHas('karyawan', function ($q) use ($search) {
                $q->where('nama_lengkap', 'LIKE', "%{$search}%")
                  ->orWhere('nik', 'LIKE', "%{$search}%");
            })->orWhere('reimbursement_child_sum.karyawan_id', 'LIKE', "%{$search}%")
              ->orWhere('reimbursement_child_sum.periode_slip', 'LIKE', "%{$search}%");
        }

        // Total records (sebelum filter search)
        $totalRecords = ReimbursementChildSum::count();

        // Total records setelah filter
        $filteredRecords = (clone $query)->count();

        // Ordering
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection   = $request->input('order.0.dir', 'asc');
        $columns = ['karyawan_id', 'periode_slip', 'jumlah_reimbursement', 'total_harga', 'status'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'periode_slip';
        $query->orderBy('reimbursement_child_sum.' . $orderColumn, $orderDirection);

        // Pagination
        $start  = $request->input('start', 0);
        $length = $request->input('length', 10);
        $data   = $query->skip($start)->take($length)->get();

        // Grand total semua data (sesuai filter periode jika ada)
        $grandTotalQuery = ReimbursementChildSum::query();
        if ($request->filled('periode_slip')) {
            $grandTotalQuery->where('periode_slip', $request->periode_slip);
        }
        $grandTotal = $grandTotalQuery->sum('total_harga');

        // Format rows
        $rows = $data->map(function ($item, $index) use ($start) {
            $karyawan     = $item->karyawan;
            $namaKaryawan = $karyawan ? $karyawan->nama_lengkap : 'Unknown';
            $nik          = $karyawan ? $karyawan->nik : '-';

            $statusBadge = $item->status
                ? '<span class="badge badge-light-success">Approved</span>'
                : '<span class="badge badge-light-warning">Pending</span>';

            $totalFormatted = 'Rp ' . number_format($item->total_harga, 0, ',', '.');

            return [
                'no'                   => $start + $index + 1,
                'karyawan'             => '<div class="d-flex flex-column">
                                            <span class="fw-bold">' . e($namaKaryawan) . '</span>
                                            <span class="text-muted fs-7">' . e($nik) . '</span>
                                          </div>',
                'periode_slip'         => '<span class="badge badge-light-primary fs-7">' . e($item->periode_slip) . '</span>',
                'jumlah_reimbursement' => '<span class="fw-semibold">' . $item->jumlah_reimbursement . '</span>',
                'total_harga'          => '<span class="fw-bold text-dark">' . $totalFormatted . '</span>',
                'status'               => $statusBadge,
            ];
        });

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $rows,
            'grand_total'     => 'Rp ' . number_format($grandTotal, 0, ',', '.'),
        ]);
    }

     /**
     * Export ke Excel — FromQuery (memory-efficient, streamed langsung ke browser)
     */
    public function export(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');
 
        $periode = $request->filled('periode_slip') ? $request->periode_slip : null;
 
        $filename = 'rekap-reimbursement'
            . ($periode ? "-{$periode}" : '')
            . '-' . now()->format('Ymd_His')
            . '.xlsx';
 
        return Excel::download(
            new ReimbursementChildSumExport($periode),
            $filename
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Detail per karyawan bisa dikembangkan
        abort(404);
    }

    // Resource methods lainnya tidak digunakan
    public function create() { abort(404); }
    public function store(Request $request) { abort(404); }
    public function edit(string $id) { abort(404); }
    public function update(Request $request, string $id) { abort(404); }
    public function destroy(string $id) { abort(404); }
}