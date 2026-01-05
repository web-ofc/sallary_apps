<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PayrollCalculation;
use App\Models\Karyawan;
use App\Models\Company;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
class MutasiCompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     /**
     * Display matrix mutasi company
     */
    public function index(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));
        
        // Get available years from payroll data
        $availableYears = PayrollCalculation::selectRaw('DISTINCT YEAR(STR_TO_DATE(CONCAT(periode, "-01"), "%Y-%m-%d")) as year')
            ->orderByDesc('year')
            ->pluck('year');
        
        // Get months yang ada di tahun ini (dynamic)
        $months = PayrollCalculation::selectRaw('DISTINCT SUBSTRING(periode, 6, 2) as bulan')
            ->whereRaw("YEAR(STR_TO_DATE(CONCAT(periode, '-01'), '%Y-%m-%d')) = ?", [$tahun])
            ->orderBy('bulan')
            ->pluck('bulan')
            ->map(function($bulan) {
                $monthNames = [
                    '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
                    '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug',
                    '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'
                ];
                return [
                    'num' => $bulan,
                    'name' => $monthNames[$bulan] ?? $bulan
                ];
            });
        
        return view('dashboard.dashboard-admin.mutasi-company.index', compact('tahun', 'availableYears', 'months'));
    }

    /**
     * Get data untuk DataTables Matrix (SERVER SIDE - OPTIMIZED)
     */
    public function getData(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));
        
        // Get all payroll data untuk tahun ini dengan company info (OPTIMIZED dengan 1 query)
        $payrollData = DB::table('payroll_calculations as pc')
            ->join('karyawans as k', 'pc.karyawan_id', '=', 'k.absen_karyawan_id')
            ->join('companies as c', 'pc.company_id', '=', 'c.absen_company_id')
            ->select(
                'k.absen_karyawan_id',
                'k.nik',
                'k.nama_lengkap',
                'pc.periode',
                'c.code as company_code',
                'c.absen_company_id'
            )
            ->whereRaw("YEAR(STR_TO_DATE(CONCAT(pc.periode, '-01'), '%Y-%m-%d')) = ?", [$tahun])
            ->whereNotNull('pc.karyawan_id')
            ->whereNotNull('pc.company_id')
            ->orderBy('k.nama_lengkap')
            ->orderBy('pc.periode')
            ->get();
        
        // Group by karyawan dan pivot per bulan
        $matrixData = $payrollData->groupBy('absen_karyawan_id')->map(function($items) {
            $firstItem = $items->first();
            
            // Hitung jumlah mutasi (berapa kali ganti company)
            $uniqueCompanies = $items->pluck('absen_company_id')->unique();
            $totalMutasi = $uniqueCompanies->count() > 1 ? $uniqueCompanies->count() - 1 : 0;
            
            // Build row data
            $row = [
                'absen_karyawan_id' => $firstItem->absen_karyawan_id,
                'nik' => $firstItem->nik,
                'nama_lengkap' => $firstItem->nama_lengkap,
                'total_mutasi' => $totalMutasi,
                'months' => []
            ];
            
            // Pivot data per bulan
            foreach($items as $item) {
                $bulan = substr($item->periode, 5, 2); // Ambil bulan dari periode (YYYY-MM)
                $row['months'][$bulan] = [
                    'company_code' => $item->company_code,
                    'company_id' => $item->absen_company_id
                ];
            }
            
            return $row;
        })->values();

        return DataTables::of($matrixData)
            ->addColumn('total_mutasi_badge', function ($row) {
                $data = $row['total_mutasi'];
                if ($data == 0) {
                    return '<span class="badge badge-light-secondary fs-7">0</span>';
                }
                $badgeClass = $data > 3 ? 'badge-danger' : ($data > 2 ? 'badge-warning' : 'badge-info');
                return '<span class="badge ' . $badgeClass . ' fs-7">' . $data . '</span>';
            })
            ->addColumn('months_data', function ($row) {
                // Return months data as JSON for dynamic column rendering
                return $row['months'];
            })
            ->rawColumns(['total_mutasi_badge'])
            ->make(true);
    }


    /**
     * Show detail mutasi untuk specific karyawan
     */
     public function show($id)
    {
        $tahun = request()->get('tahun', date('Y'));
        
        $karyawan = Karyawan::where('absen_karyawan_id', $id)->firstOrFail();
        
        // Get detailed mutation history
        $mutasiHistory = DB::table('payroll_calculations as pc')
            ->join('companies as c', 'pc.company_id', '=', 'c.absen_company_id')
            ->select('pc.periode', 'c.code as company_code', 'c.company_name', 'c.absen_company_id')
            ->where('pc.karyawan_id', $id)
            ->whereRaw("YEAR(STR_TO_DATE(CONCAT(pc.periode, '-01'), '%Y-%m-%d')) = ?", [$tahun])
            ->orderBy('pc.periode')
            ->get();
        
        return response()->json([
            'karyawan' => $karyawan,
            'mutasi_history' => $mutasiHistory
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
