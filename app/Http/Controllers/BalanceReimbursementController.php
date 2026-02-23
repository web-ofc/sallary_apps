<?php

namespace App\Http\Controllers;

use App\Models\BalanceReimbursement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\Facades\DataTables;
class BalanceReimbursementController extends Controller
{
    
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Gate::denies('balance-reimbursements')) {
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
        return view('dashboard.dashboard-management.balance-reimbursements.index');
    }

    /**
     * Get data for DataTables
     */
    public function getData()
    {
        // Query dari view dengan eager load karyawan
        $balances = BalanceReimbursement::with('karyawan:absen_karyawan_id,nama_lengkap')
            ->select([
                'karyawan_id',
                'budget_claim',
                'year',
                'total_used',
                'sisa_budget'
            ]);

        return DataTables::eloquent($balances)
            ->addIndexColumn()
            ->addColumn('nama_karyawan', function ($balance) {
                return $balance->karyawan ? $balance->karyawan->nama_lengkap : '-';
            })
            ->editColumn('budget_claim', function ($balance) {
                return 'Rp ' . number_format($balance->budget_claim, 0, ',', '.');
            })
            ->editColumn('total_used', function ($balance) {
                return 'Rp ' . number_format($balance->total_used, 0, ',', '.');
            })
            ->editColumn('sisa_budget', function ($balance) {
                return 'Rp ' . number_format($balance->sisa_budget, 0, ',', '.');
            })
            ->addColumn('persentase', function ($balance) {
                if ($balance->budget_claim > 0) {
                    $persentase = ($balance->total_used / $balance->budget_claim) * 100;
                    $color = $persentase >= 80 ? 'danger' : ($persentase >= 50 ? 'warning' : 'success');
                    
                    return '<div class="d-flex align-items-center">
                                <div class="progress w-100 me-2" style="height: 20px;">
                                    <div class="progress-bar bg-' . $color . '" role="progressbar" 
                                         style="width: ' . $persentase . '%;" 
                                         aria-valuenow="' . $persentase . '" 
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                                <span class="fw-bold">' . number_format($persentase, 1) . '%</span>
                            </div>';
                } else {
                    return '<span class="badge badge-secondary">0%</span>';
                }
            })
            ->rawColumns(['persentase'])
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
    public function show(BalanceReimbursement $balanceReimbursement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BalanceReimbursement $balanceReimbursement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BalanceReimbursement $balanceReimbursement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BalanceReimbursement $balanceReimbursement)
    {
        //
    }
}
