<?php

namespace App\Http\Controllers;

use App\Models\JenisPenyakit;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JenisPenyakitController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Gate::denies('jenis-penyakits')) {
                abort(403, 'Unauthorized action.');
            }

            return $next($request);
        });
    }

    public function index()
    {
        return view('dashboard.dashboard-management.jenis-penyakits.index');
    }

    public function getData()
    {
        $jenisPenyakits = JenisPenyakit::select([
            'id',
            'kode',
            'nama_penyakit',
            'is_active',
            'created_at'
        ])->orderBy('created_at', 'desc');

        return DataTables::eloquent($jenisPenyakits)
            ->addIndexColumn()
            ->addColumn('is_active_badge', function ($item) {
                return $item->is_active
                    ? '<span class="badge badge-light-success">Aktif</span>'
                    : '<span class="badge badge-light-danger">Nonaktif</span>';
            })
            ->addColumn('action', function ($item) {
                return '<div class="btn-group" role="group">'
                     . '<button type="button" class="btn btn-sm btn-warning" onclick="editJenisPenyakit(' . $item->id . ')"><i class="fas fa-edit"></i></button>'
                     . '<button type="button" class="btn btn-sm btn-danger" onclick="deleteJenisPenyakit(' . $item->id . ', \'' . addslashes($item->nama_penyakit) . '\')"><i class="fas fa-trash"></i></button>'
                     . '</div>';
            })
            ->rawColumns(['is_active_badge', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode'          => 'nullable|string|max:50|unique:jenis_penyakits,kode',
            'nama_penyakit' => 'required|string|max:150',
            'is_active'     => 'boolean',
        ], [
            'kode.unique'            => 'Kode sudah digunakan',
            'nama_penyakit.required' => 'Nama penyakit wajib diisi',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            JenisPenyakit::create([
                'kode'          => $request->kode ? strtoupper($request->kode) : null,
                'nama_penyakit' => $request->nama_penyakit,
                'is_active'     => $request->boolean('is_active', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jenis penyakit berhasil ditambahkan!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data.'
            ], 500);
        }
    }

    public function show($id)
    {
        $jenisPenyakit = JenisPenyakit::findOrFail($id); // ✅ fix

        return response()->json([
            'success' => true,
            'data'    => $jenisPenyakit
        ]);
    }

    public function edit($id)
    {
        $jenisPenyakit = JenisPenyakit::findOrFail($id); // ✅ fix

        return response()->json([
            'success' => true,
            'data'    => $jenisPenyakit
        ]);
    }

    public function update(Request $request, $id)
    {
        $jenisPenyakit = JenisPenyakit::findOrFail($id); // ✅ fix

        $validator = Validator::make($request->all(), [
            'kode'          => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('jenis_penyakits', 'kode')->ignore($jenisPenyakit->id), // ✅ fix
            ],
            'nama_penyakit' => 'required|string|max:150',
            'is_active'     => 'boolean',
        ], [
            'kode.unique'            => 'Kode sudah digunakan',
            'nama_penyakit.required' => 'Nama penyakit wajib diisi',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $jenisPenyakit->update([ // ✅ fix
                'kode'          => $request->kode ? strtoupper($request->kode) : null,
                'nama_penyakit' => $request->nama_penyakit,
                'is_active'     => $request->boolean('is_active', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jenis penyakit berhasil diperbarui!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data.'
            ], 500);
        }
    }

    public function destroy($id)
    {
        $jenisPenyakit = JenisPenyakit::findOrFail($id); // ✅ fix

        try {
            $jenisPenyakit->delete(); // ✅ fix

            return response()->json([
                'success' => true,
                'message' => 'Jenis penyakit berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus data.'
            ], 500);
        }
    }
}