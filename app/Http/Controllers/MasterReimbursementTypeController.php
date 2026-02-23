<?php

namespace App\Http\Controllers;

use App\Models\MasterReimbursementType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MasterReimbursementTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Gate::denies('master-reimbursementtypes')) {
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
        return view('dashboard.dashboard-management.master-reimbursement-types.index');
    }

    /**
     * Get data for DataTables
     */
    public function getData()
    {
        $reimbursementTypes = MasterReimbursementType::select([
            'id',
            'code',
            'medical_type',
            'group_medical',
            'created_at'
        ])->orderBy('created_at', 'desc');

        return DataTables::eloquent($reimbursementTypes)
            ->addIndexColumn()
            ->addColumn('action', function ($type) {
                return '<div class="btn-group" role="group">'
                     . '<button type="button" class="btn btn-sm btn-warning" onclick="editReimbursementType(' . $type->id . ')"><i class="fas fa-edit"></i></button>'
                     . '<button type="button" class="btn btn-sm btn-danger" onclick="deleteReimbursementType(' . $type->id . ', \'' . addslashes($type->medical_type) . '\')"><i class="fas fa-trash"></i></button>'
                     . '</div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return response()->json(['success' => true]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:master_reimbursement_types,code',
            'medical_type' => 'required|string|max:150',
            'group_medical' => 'required|string|max:100',
        ], [
            'code.required' => 'Kode wajib diisi',
            'code.unique' => 'Kode sudah digunakan',
            'medical_type.required' => 'Nama jenis medical wajib diisi',
            'group_medical.required' => 'Group medical wajib diisi',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            MasterReimbursementType::create([
                'code' => strtoupper($request->code),
                'medical_type' => $request->medical_type,
                'group_medical' => $request->group_medical,
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'Jenis reimbursement berhasil ditambahkan!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Terjadi kesalahan saat menyimpan data.'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MasterReimbursementType $master_reimbursementtype)
    {
        return response()->json([
            'success' => true,
            'data' => $master_reimbursementtype
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MasterReimbursementType $master_reimbursementtype)
    {
        return response()->json([
            'success' => true,
            'data' => $master_reimbursementtype
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MasterReimbursementType $master_reimbursementtype)
    {
        $validator = Validator::make($request->all(), [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('master_reimbursement_types', 'code')->ignore($master_reimbursementtype->id),
            ],
            'medical_type' => 'required|string|max:150',
            'group_medical' => 'required|string|max:100',
        ], [
            'code.required' => 'Kode wajib diisi',
            'code.unique' => 'Kode sudah digunakan',
            'medical_type.required' => 'Nama jenis medical wajib diisi',
            'group_medical.required' => 'Group medical wajib diisi',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $master_reimbursementtype->update([
                'code' => strtoupper($request->code),
                'medical_type' => $request->medical_type,
                'group_medical' => $request->group_medical,
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'Jenis reimbursement berhasil diperbarui!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Terjadi kesalahan saat memperbarui data.'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MasterReimbursementType $master_reimbursementtype)
    {
        try {
            $master_reimbursementtype->delete();
            return response()->json([
                'success' => true, 
                'message' => 'Jenis reimbursement berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Terjadi kesalahan saat menghapus data.'
            ], 500);
        }
    }
}