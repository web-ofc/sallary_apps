<?php

namespace App\Http\Controllers;

use App\Models\MasterSalary;
use App\Models\Karyawan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class MasterSalaryController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Gate::denies('master-salaries')) {
                abort(403, 'Unauthorized action.');
            }

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     * OPTIMASI: Cache years list
     */
    public function index()
    {
        // Cache years list for 1 hour
        $years = Cache::remember('master_salaries_years', 3600, function() {
            return MasterSalary::selectRaw('DISTINCT year')
                ->orderBy('year', 'desc')
                ->pluck('year');
        });
            
        return view('dashboard.dashboard-management.master-salaries.index', compact('years'));
    }

    /**
     * Get data for DataTables
     * OPTIMASI: Select only needed columns, eager load dengan select specific
     */
    public function getData(Request $request)
    {
        $query = MasterSalary::query()
            ->select([
                'master_salaries.id',
                'master_salaries.karyawan_id', // ini berisi absen_karyawan_id
                'master_salaries.salary',
                'master_salaries.update_date',
                'master_salaries.year',
                'master_salaries.status_medical'
            ])
            // OPTIMASI: Eager load hanya kolom yang diperlukan
            ->with(['karyawan:absen_karyawan_id,nama_lengkap,nik']);

        // Filter by year
        if ($request->filled('year')) {
            $query->where('master_salaries.year', $request->year);
        }

        // Filter by karyawan (by absen_karyawan_id)
        if ($request->filled('karyawan_id')) {
            $query->where('master_salaries.karyawan_id', $request->karyawan_id);
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('karyawan_info', function ($salary) {
                if ($salary->karyawan) {
                    return '<div class="d-flex flex-column">'
                         . '<span class="fw-bold">' . e($salary->karyawan->nama_lengkap) . '</span>'
                         . '</div>';
                }
                return '<span class="text-muted">Data karyawan tidak ditemukan</span>';
            })
            ->editColumn('salary', function ($salary) {
                return '<span class="fw-bold text-primary">Rp ' . number_format($salary->salary, 0, ',', '.') . '</span>';
            })
            ->editColumn('update_date', function ($salary) {
                return Carbon::parse($salary->update_date)->format('d M Y');
            })
            ->editColumn('year', function ($salary) {
                return '<span class="badge badge-light-info">' . $salary->year . '</span>';
            })
            ->editColumn('status_medical', function ($salary) {
                if ($salary->status_medical === '1') {
                    return '<span class="badge badge-light-success">Yes</span>';
                } elseif ($salary->status_medical === '0') {
                    return '<span class="badge badge-light-danger">No</span>';
                }
                return '<span class="badge badge-light-secondary">-</span>';
            })
            ->addColumn('action', function ($salary) {
                $nama = $salary->karyawan ? e($salary->karyawan->nama_lengkap) : 'Data';
                return '<div class="btn-group" role="group">'
                     . '<button type="button" class="btn btn-sm btn-light-warning" onclick="editSalary(' . $salary->id . ')" title="Edit">'
                     . '<i class="fas fa-edit"></i></button>'
                     . '<button type="button" class="btn btn-sm btn-light-danger" onclick="deleteSalary(' . $salary->id . ', \'' . str_replace("'", "\\'", $nama) . '\')" title="Hapus">'
                     . '<i class="fas fa-trash"></i></button>'
                     . '</div>';
            })
            ->rawColumns(['karyawan_info', 'salary', 'year', 'status_medical', 'action'])
            ->make(true);
    }

    /**
     * Get karyawan list for Select2
     * OPTIMASI: Select only needed columns, pagination
     */
    public function getKaryawanList(Request $request)
    {
        $search = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = 15; // Optimal pagination

        // OPTIMASI: Select hanya kolom yang diperlukan
        $query = Karyawan::active()
            ->select('absen_karyawan_id', 'nama_lengkap', 'nik')
            ->orderBy('nama_lengkap');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('absen_karyawan_id', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $karyawans = $query->skip(($page - 1) * $perPage)
                          ->take($perPage)
                          ->get();

        $data = $karyawans->map(function($karyawan) {
            return [
                'id' => $karyawan->absen_karyawan_id, // Return absen_karyawan_id as id
                'text' => $karyawan->nama_lengkap . ' (NIK: ' . ($karyawan->nik ?? '-') . ', ID: ' . $karyawan->absen_karyawan_id . ')',
                'absen_karyawan_id' => $karyawan->absen_karyawan_id,
                'nik' => $karyawan->nik,
            ];
        });

        return response()->json([
            'results' => $data,
            'pagination' => [
                'more' => ($page * $perPage) < $total
            ]
        ]);
    }

    /**
     * Get karyawan detail by absen_karyawan_id
     * OPTIMASI: Select specific columns, subquery untuk latest salary
     */
    public function getKaryawanDetail($absenKaryawanId)
    {
        $karyawan = Karyawan::where('absen_karyawan_id', $absenKaryawanId)
            ->select('absen_karyawan_id', 'nama_lengkap', 'nik')
            ->first();
        
        if (!$karyawan) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak ditemukan'
            ], 404);
        }

        // OPTIMASI: Get latest salary dengan query terpisah yang lebih efisien
        $latestSalary = MasterSalary::where('karyawan_id', $absenKaryawanId)
            ->select('salary', 'year', 'update_date')
            ->orderBy('year', 'desc')
            ->orderBy('update_date', 'desc')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'absen_karyawan_id' => $karyawan->absen_karyawan_id,
                'nama_lengkap' => $karyawan->nama_lengkap,
                'nik' => $karyawan->nik,
                'latest_salary' => $latestSalary ? [
                    'salary' => $latestSalary->salary,
                    'year' => $latestSalary->year,
                    'update_date' => Carbon::parse($latestSalary->update_date)->format('Y-m-d'),
                ] : null
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * Year auto-generate dari update_date
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'karyawan_id' => [
                'required',
                Rule::exists('karyawans', 'absen_karyawan_id')->where(function ($query) {
                    $query->where('status_resign', false);
                })
            ],
            'salary' => 'required|integer|min:0',
            'update_date' => 'required|date',
            'status_medical' => 'nullable|in:1,0',
        ], [
            'karyawan_id.required' => 'Karyawan wajib dipilih',
            'karyawan_id.exists' => 'Karyawan tidak valid atau sudah resign',
            'salary.required' => 'Gaji wajib diisi',
            'salary.integer' => 'Gaji harus berupa angka',
            'salary.min' => 'Gaji tidak boleh negatif',
            'update_date.required' => 'Tanggal update wajib diisi',
            'update_date.date' => 'Format tanggal tidak valid',
            'status_medical.in' => 'Status medical harus yes atau no',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            // Auto-generate year dari update_date
            $year = Carbon::parse($request->update_date)->year;


            MasterSalary::create([
                'karyawan_id' => $request->karyawan_id, // ini berisi absen_karyawan_id
                'salary' => $request->salary,
                'update_date' => $request->update_date,
                'year' => $year, // Auto-generate dari update_date
                'status_medical' => $request->status_medical,
            ]);

            // Clear cache
            Cache::forget('master_salaries_years');

            return response()->json([
                'success' => true,
                'message' => 'Data salary berhasil ditambahkan!'
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
     * OPTIMASI: Select only needed columns
     */
    public function edit($id)
    {
        try {
            $salary = MasterSalary::with(['karyawan:absen_karyawan_id,nama_lengkap,nik'])
                ->select([
                    'id', 
                    'karyawan_id', // absen_karyawan_id
                    'salary', 
                    'update_date', 
                    'year', 
                    'status_medical'
                ])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $salary->id,
                    'karyawan_id' => $salary->karyawan_id, // absen_karyawan_id
                    'karyawan' => $salary->karyawan ? [
                        'id' => $salary->karyawan->absen_karyawan_id,
                        'text' => $salary->karyawan->nama_lengkap . ' (NIK: ' . ($salary->karyawan->nik ?? '-') . ', ID: ' . $salary->karyawan->absen_karyawan_id . ')',
                        'absen_karyawan_id' => $salary->karyawan->absen_karyawan_id,
                        'nik' => $salary->karyawan->nik,
                    ] : null,
                    'salary' => $salary->salary,
                    'update_date' => Carbon::parse($salary->update_date)->format('Y-m-d'),
                    'year' => $salary->year,
                    'status_medical' => $salary->status_medical,
                ]
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
     * Year auto-generate dari update_date
     */
    public function update(Request $request, $id)
    {
        try {
            $salary = MasterSalary::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'karyawan_id' => [
                    'required',
                    Rule::exists('karyawans', 'absen_karyawan_id')->where(function ($query) {
                        $query->where('status_resign', false);
                    })
                ],
                'salary' => 'required|integer|min:0',
                'update_date' => 'required|date',
                'status_medical' => 'nullable|in:1,0',
            ], [
                'karyawan_id.required' => 'Karyawan wajib dipilih',
                'karyawan_id.exists' => 'Karyawan tidak valid atau sudah resign',
                'salary.required' => 'Gaji wajib diisi',
                'salary.integer' => 'Gaji harus berupa angka',
                'salary.min' => 'Gaji tidak boleh negatif',
                'update_date.required' => 'Tanggal update wajib diisi',
                'update_date.date' => 'Format tanggal tidak valid',
                'status_medical.in' => 'Status medical harus yes atau no',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            // Auto-generate year dari update_date
            $year = Carbon::parse($request->update_date)->year;

           

            $salary->update([
                'karyawan_id' => $request->karyawan_id, // absen_karyawan_id
                'salary' => $request->salary,
                'update_date' => $request->update_date,
                'year' => $year, // Auto-generate dari update_date
                'status_medical' => $request->status_medical,
            ]);

            // Clear cache
            Cache::forget('master_salaries_years');

            return response()->json([
                'success' => true,
                'message' => 'Data salary berhasil diperbarui!'
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
            $salary = MasterSalary::findOrFail($id);
            $salary->delete();

            // Clear cache
            Cache::forget('master_salaries_years');

            return response()->json([
                'success' => true,
                'message' => 'Data salary berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}