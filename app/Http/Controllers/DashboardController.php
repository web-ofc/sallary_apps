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
                'data' => $periodes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading periodes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics (count only, ringan)
     */
    public function getStatistics(Request $request)
    {
        try {
            $periode = $request->input('periode');

            if (!$periode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Periode is required'
                ], 400);
            }

            // Ambil karyawan_id yang sudah punya payroll di periode ini
            $karyawanSudahInput = PayrollCalculation::where('periode', $periode)
                ->pluck('karyawan_id')
                ->unique();

            $totalPayroll    = $karyawanSudahInput->count();
            $releasedCount   = PayrollCalculation::where('periode', $periode)->where('is_released', true)->count();
            $releasedSlip    = PayrollCalculation::where('periode', $periode)->where('is_released_slip', true)->count();
            $draftCount      = $totalPayroll - $releasedCount;

            // Karyawan aktif yang belum punya payroll di periode ini
            $belumInput = Karyawan::where('status_resign', false)
                ->whereNull('deleted_at')
                ->whereNotIn('id', $karyawanSudahInput)
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_payroll'   => $totalPayroll,
                    'released_count'  => $releasedCount,
                    'released_slip'   => $releasedSlip,
                    'draft_count'     => $draftCount,
                    'belum_input'     => $belumInput,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}