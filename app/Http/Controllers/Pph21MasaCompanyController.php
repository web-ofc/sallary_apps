<?php

namespace App\Http\Controllers;

use App\Models\Pph21MasaCompany;
use Illuminate\Http\Request;
use App\Models\Company;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
class Pph21MasaCompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get unique companies for filter dropdown
        $companies = Company::select('absen_company_id', 'company_name')
            ->orderBy('company_name')
            ->get();

        // Get unique periode for filter dropdown
        $periodes = DB::table('pph21_masa_company_periode')
            ->select('periode')
            ->distinct()
            ->orderBy('periode', 'desc')
            ->pluck('periode');

        return view('dashboard.dashboard-admin.hitung-pph21-masa-company-periode.index', compact('companies', 'periodes'));
    }

    /**
     * Get data for DataTables
     */
    public function getData(Request $request)
    {
        $query = Pph21MasaCompany::query()
            ->select([
                'company_id',
                'company_name',
                'periode',
                'total_karyawan',
                'total_pph21_masa'
            ]);

        // Apply filters
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('periode')) {
            $query->where('periode', $request->periode);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('periode', function ($row) {
                return \Carbon\Carbon::parse($row->periode . '-01')->format('F Y');
            })
            ->editColumn('total_karyawan', function ($row) {
                return number_format($row->total_karyawan, 0, ',', '.');
            })
            ->editColumn('total_pph21_masa', function ($row) {
                return 'Rp ' . number_format($row->total_pph21_masa, 0, ',', '.');
            })
            ->addColumn('action', function ($row) {
                return '
                    <div class="btn-group" role="group">
                        <a href="' . route('manage-pph21companyperiode.show', $row->company_id . '-' . $row->periode) . '" 
                           class="btn btn-sm btn-info btn-icon">
                            <i class="bi bi-eye"></i>
                        </a>
                    </div>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
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
    public function show(Pph21MasaCompany $pph21MasaCompany)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pph21MasaCompany $pph21MasaCompany)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pph21MasaCompany $pph21MasaCompany)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pph21MasaCompany $pph21MasaCompany)
    {
        //
    }
}
