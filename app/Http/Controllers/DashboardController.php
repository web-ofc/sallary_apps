<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\PayrollCalculation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function managementDashboard()
    {
        if (!in_array(Auth::user()->role, ['management'])) {
            abort(403, 'Unauthorized action.');
        }

        return view('dashboard.dashboard-management.dashboard', [
            'title' => 'Dashboard Management',
        ]);
    }

    /**
     * Get available periodes
     */
    public function getPeriodes()
    {
        try {
            $periodes = PayrollCalculation::select('periode')
                ->distinct()
                ->orderBy('periode', 'desc')
                ->pluck('periode');

            return response()->json([
                'success' => true,
                'data'    => $periodes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading periodes: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get dashboard statistics (count only)
     */
    public function getStatistics(Request $request)
    {
        try {
            $periode = $request->input('periode');

            if (!$periode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Periode is required',
                ], 400);
            }

            $counts = PayrollCalculation::where('periode', $periode)
                ->selectRaw('
                    COUNT(*) as total_payroll,
                    SUM(CASE WHEN is_released = 0 THEN 1 ELSE 0 END) as draft_count,
                    SUM(CASE WHEN is_released = 1 AND is_released_slip = 0 THEN 1 ELSE 0 END) as released_count,
                    SUM(CASE WHEN is_released = 1 AND is_released_slip = 1 THEN 1 ELSE 0 END) as released_slip
                ')
                ->first();

            $totalPayroll  = $counts->total_payroll ?? 0;
            $draftCount    = $counts->draft_count ?? 0;
            $releasedCount = $counts->released_count ?? 0;
            $releasedSlip  = $counts->released_slip ?? 0;

            $karyawanSudahInput = PayrollCalculation::where('periode', $periode)
                ->pluck('karyawan_id')
                ->unique();

            $belumInput = Karyawan::where('status_resign', false)
                ->whereNull('deleted_at')
                 ->whereNotIn('absen_karyawan_id', $karyawanSudahInput) // ← fix
                ->count();

            return response()->json([
                'success' => true,
                'data'    => [
                    'total_payroll'  => $totalPayroll,
                    'released_count' => $releasedCount,
                    'released_slip'  => $releasedSlip,
                    'draft_count'    => $draftCount,
                    'belum_input'    => $belumInput,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading statistics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Page: Karyawan belum diinput payroll
     */
    public function karyawanBelumInput(Request $request)
    {
        if (!in_array(Auth::user()->role, ['admin'])) {
            abort(403, 'Unauthorized action.');
        }

        // Ambil periode dari query string, fallback ke periode terbaru
        $periode = $request->input('periode');

        if (!$periode) {
            $periode = PayrollCalculation::select('periode')
                ->distinct()
                ->orderBy('periode', 'desc')
                ->value('periode');
        }

        return view('dashboard.dashboard-admin.karyawan-belum-ada-payrolls.index', [
            'title'   => 'Karyawan Belum Diinput Payroll',
            'periode' => $periode,
        ]);
    }

    /**
     * Serverside datatable: Karyawan belum diinput payroll
     */
    public function karyawanBelumInputData(Request $request)
    {
        try {
            $periode = $request->input('periode');

            if (!$periode) {
                return response()->json(['error' => 'Periode is required'], 400);
            }

            // Karyawan yang sudah punya payroll di periode ini
            $sudahInput = PayrollCalculation::where('periode', $periode)
            ->pluck('karyawan_id')
            ->unique()
            ->toArray();
            // Base query
            $query = Karyawan::where('status_resign', false)
                ->whereNull('deleted_at')
                ->whereNotIn('absen_karyawan_id', $sudahInput)
                ->select(['id', 'nik', 'nama_lengkap', 'email_pribadi', 'telp_pribadi', 'join_date', 'jenis_kelamin', 'absen_karyawan_id']);

            // Datatables manual serverside
            $totalRecords = (clone $query)->count();

            // Search
            $search = $request->input('search.value');
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nik', 'like', "%{$search}%")
                      ->orWhere('nama_lengkap', 'like', "%{$search}%")
                      ->orWhere('email_pribadi', 'like', "%{$search}%");
                });
            }

            $filteredRecords = (clone $query)->count();

            // Order
            $orderColumnIndex = $request->input('order.0.column', 1);
            $orderDir         = $request->input('order.0.dir', 'asc');
            $columns          = ['id', 'nik', 'nama_lengkap', 'email_pribadi', 'telp_pribadi', 'join_date', 'jenis_kelamin'];
            $orderColumn      = $columns[$orderColumnIndex] ?? 'nama_lengkap';
            $query->orderBy($orderColumn, $orderDir);

            // Paginate
            $start  = $request->input('start', 0);
            $length = $request->input('length', 25);
            $data   = $query->skip($start)->take($length)->get();

            $rows = $data->map(function ($k, $index) use ($start) {
                return [
                    'no'            => $start + $index + 1,
                    'nik'           => $k->nik ?? '-',
                    'nama_lengkap'  => $k->nama_lengkap,
                    'email_pribadi' => $k->email_pribadi ?? '-',
                    'telp_pribadi'  => $k->telp_pribadi ?? '-',
                    'join_date'     => $k->join_date
                        ? \Carbon\Carbon::parse($k->join_date)->format('d M Y')
                        : '-',
                    'jenis_kelamin' => $k->jenis_kelamin === 'L' ? 'Laki-laki' : ($k->jenis_kelamin === 'P' ? 'Perempuan' : '-'),
                ];
            });

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