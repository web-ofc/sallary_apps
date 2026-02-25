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
    public function getData(Request $request)
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

            // ── Filter Tahun ──────────────────────────────────────────────
            if ($request->filled('year')) {
                $balances->where('year', $request->year);
            }

            // ── Filter Status berdasarkan persentase sisa ─────────────────
            if ($request->filled('status')) {
                switch ($request->status) {
                    case 'aman':
                        // sisa > 70% dari budget
                        $balances->whereRaw('(sisa_budget / NULLIF(budget_claim, 0)) * 100 > 70');
                        break;
                    case 'normal':
                        // sisa 40–70%
                        $balances->whereRaw('(sisa_budget / NULLIF(budget_claim, 0)) * 100 > 40')
                                ->whereRaw('(sisa_budget / NULLIF(budget_claim, 0)) * 100 <= 70');
                        break;
                    case 'menipis':
                        // sisa 20–40%
                        $balances->whereRaw('(sisa_budget / NULLIF(budget_claim, 0)) * 100 > 20')
                                ->whereRaw('(sisa_budget / NULLIF(budget_claim, 0)) * 100 <= 40');
                        break;
                    case 'hampir_habis':
                        // sisa > 0 tapi ≤ 20%
                        $balances->whereRaw('(sisa_budget / NULLIF(budget_claim, 0)) * 100 > 0')
                                ->whereRaw('(sisa_budget / NULLIF(budget_claim, 0)) * 100 <= 20');
                        break;
                    case 'habis':
                        // sisa = 0
                        $balances->where('sisa_budget', '<=', 0);
                        break;
                }
            }

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
                $sisa = $balance->sisa_budget;
                $budget = $balance->budget_claim;

                if ($budget > 0) {
                    $persentase_sisa = ($sisa / $budget) * 100;

                    if ($persentase_sisa <= 0) {
                        $color = 'text-danger fw-bold';
                        $icon  = '<i class="fas fa-times-circle text-danger me-1"></i>';
                    } elseif ($persentase_sisa <= 20) {
                        $color = 'text-danger fw-bold';
                        $icon  = '<i class="fas fa-exclamation-circle text-danger me-1"></i>';
                    } elseif ($persentase_sisa <= 40) {
                        $color = 'text-warning fw-bold';
                        $icon  = '<i class="fas fa-exclamation-triangle text-warning me-1"></i>';
                    } elseif ($persentase_sisa <= 70) {
                        $color = 'text-info fw-bold';
                        $icon  = '<i class="fas fa-info-circle text-info me-1"></i>';
                    } else {
                        $color = 'text-success fw-bold';
                        $icon  = '<i class="fas fa-check-circle text-success me-1"></i>';
                    }
                } else {
                    $color = 'text-muted';
                    $icon  = '';
                }

                return '<span class="' . $color . '">' . $icon . 'Rp ' . number_format($sisa, 0, ',', '.') . '</span>';
            })
            ->addColumn('persentase', function ($balance) {
                if ($balance->budget_claim > 0) {
                    $persentase_used = ($balance->total_used / $balance->budget_claim) * 100;
                    $persentase_sisa = 100 - $persentase_used;

                    // Warna progress bar berdasarkan SISA budget
                    if ($persentase_sisa <= 0) {
                        $bar_color   = 'danger';      // Habis
                        $text_color  = 'text-danger';
                        $badge_color = 'badge-light-danger';
                        $label       = 'Habis';
                    } elseif ($persentase_sisa <= 20) {
                        $bar_color   = 'danger';      // Hampir habis (sisa ≤ 20%)
                        $text_color  = 'text-danger';
                        $badge_color = 'badge-light-danger';
                        $label       = 'Hampir Habis';
                    } elseif ($persentase_sisa <= 40) {
                        $bar_color   = 'warning';     // Menipis (sisa ≤ 40%)
                        $text_color  = 'text-warning';
                        $badge_color = 'badge-light-warning';
                        $label       = 'Menipis';
                    } elseif ($persentase_sisa <= 70) {
                        $bar_color   = 'info';        // Normal (sisa ≤ 70%)
                        $text_color  = 'text-info';
                        $badge_color = 'badge-light-info';
                        $label       = 'Normal';
                    } else {
                        $bar_color   = 'success';     // Aman (sisa > 70%)
                        $text_color  = 'text-success';
                        $badge_color = 'badge-light-success';
                        $label       = 'Aman';
                    }

                    $persentase_display = number_format($persentase_sisa, 1);

                    return '
                        <div class="d-flex flex-column gap-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge ' . $badge_color . ' fs-9 px-2 py-1">' . $label . '</span>
                                <span class="fw-bold fs-8 ' . $text_color . '">' . $persentase_display . '% sisa</span>
                            </div>
                            <div class="progress w-100" style="height: 10px; border-radius: 5px; background-color: #e9ecef;">
                                <div class="progress-bar bg-' . $bar_color . '" role="progressbar"
                                    style="width: ' . $persentase_sisa . '%; transition: width 0.6s ease;"
                                    aria-valuenow="' . $persentase_sisa . '"
                                    aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>';
                } else {
                    return '<span class="badge badge-light-secondary">Tidak ada budget</span>';
                }
            })
            ->rawColumns(['persentase','sisa_budget'])
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
