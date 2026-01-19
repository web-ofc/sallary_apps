<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pph21TaxBracket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class Pph21TaxBracketController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Gate::denies('pph21-tax-brackets')) {
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
        return view('dashboard.dashboard-admin.pph21-tax-brackets.index');
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $data = Pph21TaxBracket::select('pph21_tax_brackets.*')
                ->orderBy('order_index');
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('pkp_range', function($row) {
                    $min = 'Rp ' . number_format($row->min_pkp, 0, ',', '.');
                    $max = $row->max_pkp ? 'Rp ' . number_format($row->max_pkp, 0, ',', '.') : 'Tidak Terbatas';
                    return $min . ' - ' . $max;
                })
                ->addColumn('rate_display', function($row) {
                    return $row->rate_percent . '%';
                })
                ->addColumn('effective_period', function($row) {
                    $start = \Carbon\Carbon::parse($row->effective_start_date)->format('d/m/Y');
                    $end = $row->effective_end_date 
                        ? \Carbon\Carbon::parse($row->effective_end_date)->format('d/m/Y') 
                        : 'Sekarang';
                    return $start . ' - ' . $end;
                })
                ->addColumn('status', function($row) {
                    $today = now()->toDateString();
                    $isActive = $row->effective_start_date <= $today && 
                               ($row->effective_end_date === null || $row->effective_end_date >= $today);
                    
                    if ($isActive) {
                        return '<span class="badge badge-light-success">Aktif</span>';
                    } else {
                        return '<span class="badge badge-light-danger">Tidak Aktif</span>';
                    }
                })
                ->addColumn('action', function($row) {
                    $editBtn = '<button type="button" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1 btn-edit" data-id="'.$row->id.'">
                                    <i class="ki-duotone ki-pencil fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </button>';
                    
                    $deleteBtn = '<button type="button" class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm btn-delete" data-id="'.$row->id.'">
                                    <i class="ki-duotone ki-trash fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                        <span class="path5"></span>
                                    </i>
                                </button>';
                    
                    return $editBtn . $deleteBtn;
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
        public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'min_pkp' => 'required|numeric|min:0',
            'max_pkp' => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value !== null && $value <= $request->min_pkp) {
                        $fail('PKP maksimal harus lebih besar dari PKP minimal');
                    }
                }
            ],
            'rate_percent' => 'required|numeric|min:0|max:100',
            'order_index' => 'required|integer|min:1',
            'description' => 'nullable|string|max:1000',
            'effective_start_date' => 'required|date',
            'effective_end_date' => 'nullable|date|after_or_equal:effective_start_date',
        ], [
            'min_pkp.required' => 'PKP minimal wajib diisi',
            'min_pkp.numeric' => 'PKP minimal harus berupa angka',
            'min_pkp.min' => 'PKP minimal tidak boleh kurang dari 0',
            'max_pkp.numeric' => 'PKP maksimal harus berupa angka',
            'rate_percent.required' => 'Tarif pajak wajib diisi',
            'rate_percent.numeric' => 'Tarif pajak harus berupa angka',
            'rate_percent.min' => 'Tarif pajak minimal 0%',
            'rate_percent.max' => 'Tarif pajak maksimal 100%',
            'order_index.required' => 'Urutan wajib diisi',
            'order_index.integer' => 'Urutan harus berupa angka',
            'order_index.min' => 'Urutan minimal 1',
            'description.max' => 'Deskripsi maksimal 1000 karakter',
            'effective_start_date.required' => 'Tanggal mulai berlaku wajib diisi',
            'effective_start_date.date' => 'Format tanggal mulai berlaku tidak valid',
            'effective_end_date.date' => 'Format tanggal akhir berlaku tidak valid',
            'effective_end_date.after_or_equal' => 'Tanggal akhir berlaku harus sama atau setelah tanggal mulai berlaku',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Pph21TaxBracket::create([
                'min_pkp' => $request->min_pkp,
                'max_pkp' => $request->max_pkp ?: null, // Pastikan empty string jadi null
                'rate_percent' => $request->rate_percent,
                'order_index' => $request->order_index,
                'description' => $request->description,
                'effective_start_date' => $request->effective_start_date,
                'effective_end_date' => $request->effective_end_date,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function show($id)
    {
       
    }

    public function edit($id)
    {
        try {
            $data = Pph21TaxBracket::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $data
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
     */
        public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'min_pkp' => 'required|numeric|min:0',
            'max_pkp' => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value !== null && $value <= $request->min_pkp) {
                        $fail('PKP maksimal harus lebih besar dari PKP minimal');
                    }
                }
            ],
            'rate_percent' => 'required|numeric|min:0|max:100',
            'order_index' => 'required|integer|min:1',
            'description' => 'nullable|string|max:1000',
            'effective_start_date' => 'required|date',
            'effective_end_date' => 'nullable|date|after_or_equal:effective_start_date',
        ], [
            'min_pkp.required' => 'PKP minimal wajib diisi',
            'min_pkp.numeric' => 'PKP minimal harus berupa angka',
            'min_pkp.min' => 'PKP minimal tidak boleh kurang dari 0',
            'max_pkp.numeric' => 'PKP maksimal harus berupa angka',
            'rate_percent.required' => 'Tarif pajak wajib diisi',
            'rate_percent.numeric' => 'Tarif pajak harus berupa angka',
            'rate_percent.min' => 'Tarif pajak minimal 0%',
            'rate_percent.max' => 'Tarif pajak maksimal 100%',
            'order_index.required' => 'Urutan wajib diisi',
            'order_index.integer' => 'Urutan harus berupa angka',
            'order_index.min' => 'Urutan minimal 1',
            'description.max' => 'Deskripsi maksimal 1000 karakter',
            'effective_start_date.required' => 'Tanggal mulai berlaku wajib diisi',
            'effective_start_date.date' => 'Format tanggal mulai berlaku tidak valid',
            'effective_end_date.date' => 'Format tanggal akhir berlaku tidak valid',
            'effective_end_date.after_or_equal' => 'Tanggal akhir berlaku harus sama atau setelah tanggal mulai berlaku',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $bracket = Pph21TaxBracket::findOrFail($id);

            $bracket->update([
                'min_pkp' => $request->min_pkp,
                'max_pkp' => $request->max_pkp ?: null, // Pastikan empty string jadi null
                'rate_percent' => $request->rate_percent,
                'order_index' => $request->order_index,
                'description' => $request->description,
                'effective_start_date' => $request->effective_start_date,
                'effective_end_date' => $request->effective_end_date,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diupdate'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $bracket = Pph21TaxBracket::findOrFail($id);
            $bracket->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}