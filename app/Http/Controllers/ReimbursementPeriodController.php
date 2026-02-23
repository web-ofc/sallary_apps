<?php

namespace App\Http\Controllers;

use App\Models\ReimbursementPeriod;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ReimbursementPeriodController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Gate::denies('manage-reimbursementperiods')) {
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
        return view('dashboard.dashboard-management.reimbursement-periods.index');
    }

    /**
     * Get data for DataTables
     */
    public function getData()
    {
        $periods = ReimbursementPeriod::select([
            'id',
            'periode',
            'expired_reimburs_start',
            'end_reimburs_start',
            'created_at'
        ])->orderBy('expired_reimburs_start', 'desc');

        return DataTables::eloquent($periods)
            ->addIndexColumn()
            ->editColumn('expired_reimburs_start', function ($period) {
                return Carbon::parse($period->expired_reimburs_start)->format('d M Y');
            })
            ->editColumn('end_reimburs_start', function ($period) {
                return Carbon::parse($period->end_reimburs_start)->format('d M Y');
            })
            ->addColumn('duration', function ($period) {
                $start = Carbon::parse($period->expired_reimburs_start);
                $end = Carbon::parse($period->end_reimburs_start);
                $days = $start->diffInDays($end);
                return $days . ' hari';
            })
            ->addColumn('status', function ($period) {
                $now = Carbon::now();
                $start = Carbon::parse($period->expired_reimburs_start);
                $end = Carbon::parse($period->end_reimburs_start);
                
                if ($now->lt($start)) {
                    return '<span class="badge badge-info">Akan Datang</span>';
                } elseif ($now->between($start, $end)) {
                    return '<span class="badge badge-success">Aktif</span>';
                } else {
                    return '<span class="badge badge-secondary">Berakhir</span>';
                }
            })
            ->addColumn('action', function ($period) {
                return '<div class="btn-group" role="group">'
                     . '<button type="button" class="btn btn-sm btn-warning" onclick="editPeriod(' . $period->id . ')"><i class="fas fa-edit"></i></button>'
                     . '<button type="button" class="btn btn-sm btn-danger" onclick="deletePeriod(' . $period->id . ', \'' . addslashes($period->periode) . '\')"><i class="fas fa-trash"></i></button>'
                     . '</div>';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'periode' => 'required|string|max:50|unique:reimbursement_periods,periode',
            'expired_reimburs_start' => 'required|date',
            'end_reimburs_start' => 'required|date|after:expired_reimburs_start',
        ], [
            'periode.required' => 'Periode wajib diisi',
            'periode.unique' => 'Periode sudah ada',
            'expired_reimburs_start.required' => 'Tanggal mulai wajib diisi',
            'expired_reimburs_start.date' => 'Format tanggal mulai tidak valid',
            'end_reimburs_start.required' => 'Tanggal akhir wajib diisi',
            'end_reimburs_start.date' => 'Format tanggal akhir tidak valid',
            'end_reimburs_start.after' => 'Tanggal akhir harus setelah tanggal mulai',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
           

            ReimbursementPeriod::create([
                'periode' => $request->periode,
                'expired_reimburs_start' => $request->expired_reimburs_start,
                'end_reimburs_start' => $request->end_reimburs_start,
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'Periode reimbursement berhasil ditambahkan!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * PERBAIKAN: Ubah parameter dari $reimbursement_period menjadi $manage_reimbursementperiod
     * karena Laravel menggunakan nama route resource untuk binding
     */
    public function edit($id)
    {
        try {
            $period = ReimbursementPeriod::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $period
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     * PERBAIKAN: Ubah parameter dari $reimbursement_period menjadi $manage_reimbursementperiod
     */
    public function update(Request $request, $id)
    {
        try {
            $reimbursement_period = ReimbursementPeriod::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'periode' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('reimbursement_periods', 'periode')->ignore($id),
                ],
                'expired_reimburs_start' => 'required|date',
                'end_reimburs_start' => 'required|date|after:expired_reimburs_start',
            ], [
                'periode.required' => 'Periode wajib diisi',
                'periode.unique' => 'Periode sudah ada',
                'expired_reimburs_start.required' => 'Tanggal mulai wajib diisi',
                'expired_reimburs_start.date' => 'Format tanggal mulai tidak valid',
                'end_reimburs_start.required' => 'Tanggal akhir wajib diisi',
                'end_reimburs_start.date' => 'Format tanggal akhir tidak valid',
                'end_reimburs_start.after' => 'Tanggal akhir harus setelah tanggal mulai',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false, 
                    'message' => $validator->errors()->first()
                ], 422);
            }

           
            $reimbursement_period->update([
                'periode' => $request->periode,
                'expired_reimburs_start' => $request->expired_reimburs_start,
                'end_reimburs_start' => $request->end_reimburs_start,
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'Periode reimbursement berhasil diperbarui!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * PERBAIKAN: Ubah parameter
     */
    public function destroy($id)
    {
        try {
            $reimbursement_period = ReimbursementPeriod::findOrFail($id);
            
            // Optional: Cek apakah periode sedang digunakan di tabel lain
            // if ($reimbursement_period->reimbursements()->exists()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Periode tidak dapat dihapus karena sudah digunakan!'
            //     ], 422);
            // }

            $reimbursement_period->delete();
            
            return response()->json([
                'success' => true, 
                'message' => 'Periode reimbursement berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active period
     */
    public function getActivePeriod()
    {
        $activePeriod = ReimbursementPeriod::where('expired_reimburs_start', '<=', now())
            ->where('end_reimburs_start', '>=', now())
            ->first();

        return response()->json([
            'success' => true,
            'data' => $activePeriod
        ]);
    }
}