<?php

namespace App\Http\Controllers;

use App\Models\ReimbursementFile;
use Illuminate\Http\Request;
use App\Models\Karyawan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class ReimbursementFileController extends Controller
{
    
        public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Gate::denies('A1-files')) {
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
        return view('dashboard.dashboard-admin.reimbursement-files.index');
    }

    /**
     * Get data for DataTables
     */
    /**
 * Get data for DataTables
 */
public function getData(Request $request)
{
    $query = ReimbursementFile::query()
        ->select([
            'reimbursement_files.id',
            'reimbursement_files.karyawan_id',
            'reimbursement_files.year',
            'reimbursement_files.file',
            'reimbursement_files.created_at'
        ])
        ->with('karyawan:absen_karyawan_id,nama_lengkap,nik');

    // ✅ Filter by year
    if ($request->filled('year') && $request->year != '') {
        $query->where('reimbursement_files.year', $request->year);
    }

    // ✅ Search by karyawan name or NIK (from custom input)
    if ($request->filled('karyawan_search') && $request->karyawan_search != '') {
        $searchTerm = $request->karyawan_search;
        $query->whereHas('karyawan', function($q) use ($searchTerm) {
            $q->where('nama_lengkap', 'like', "%{$searchTerm}%")
              ->orWhere('nik', 'like', "%{$searchTerm}%");
        });
    }

    return DataTables::eloquent($query)
        ->addIndexColumn()
        ->addColumn('karyawan_info', function ($file) {
            if ($file->karyawan) {
                return '<div class="d-flex flex-column">'
                     . '<span class="fw-bold">' . e($file->karyawan->nama_lengkap) . '</span>'
                     . '<span class="text-muted fs-7">NIK: ' . e($file->karyawan->nik ?? '-') . '</span>'
                     . '</div>';
            }
            return '<span class="text-muted">-</span>';
        })
         ->editColumn('created_at', function ($file) {
            return Carbon::parse($file->created_at)
                ->timezone('Asia/Jakarta')
                ->format('d M Y H:i'); // contoh: 13 Feb 2026 15:20
        })
        ->addColumn('file_info', function ($file) {
            $filename = basename($file->file);
            $extension = strtoupper(pathinfo($file->file, PATHINFO_EXTENSION));
            
            $badgeClass = $extension === 'PDF' ? 'badge-light-danger' : 'badge-light-primary';
            
            return '<div class="d-flex align-items-center">'
                 . '<span class="badge ' . $badgeClass . ' me-2">' . $extension . '</span>'
                 . '<span class="text-gray-800">' . e($filename) . '</span>'
                 . '</div>';
        })
        ->addColumn('action', function ($file) {
            $buttons = '<div class="btn-group" role="group">';
            
            // Download button
            $buttons .= '<a href="' . route('reimbursement-files.download', $file->id) . '" class="btn btn-sm btn-light-success" title="Download">'
                    . '<i class="fas fa-download"></i></a>';
            
            // Delete button
            $buttons .= '<button type="button" class="btn btn-sm btn-light-danger" onclick="deleteFile(' . $file->id . ', \'' . str_replace("'", "\\'", basename($file->file)) . '\')" title="Hapus">'
                    . '<i class="fas fa-trash"></i></button>';
            
            $buttons .= '</div>';
            return $buttons;
        })
        ->filterColumn('karyawan.nama_lengkap', function($query, $keyword) {
            // This handles DataTables built-in column search (if needed)
            $query->whereHas('karyawan', function($q) use ($keyword) {
                $q->where('nama_lengkap', 'like', "%{$keyword}%")
                  ->orWhere('nik', 'like', "%{$keyword}%");
            });
        })
        ->rawColumns(['karyawan_info', 'file_info', 'action'])
        ->make(true);
}

    /**
     * STEP 2: Validate pre-create and redirect
     */
    public function validatePreCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'karyawan_ids' => 'required|array|min:1|max:20',
            'karyawan_ids.*' => 'required|exists:karyawans,absen_karyawan_id',
            'year' => 'required|integer|min:2000|max:2100',
        ], [
            'karyawan_ids.required' => 'Minimal 1 karyawan harus dipilih',
            'karyawan_ids.max' => 'Maksimal 20 karyawan',
            'karyawan_ids.*.exists' => 'Karyawan tidak valid',
            'year.required' => 'Tahun wajib dipilih',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Return redirect URL
        return response()->json([
            'success' => true,
            'redirect_url' => route('reimbursement-files.create', [
                'karyawan_ids' => implode(',', $request->karyawan_ids),
                'year' => $request->year
            ])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $karyawanIds = $request->query('karyawan_ids');
        $year = $request->query('year');

        if (!$karyawanIds || !$year) {
            return redirect()->route('reimbursement-files.index')
                ->with('error', 'Parameter tidak lengkap');
        }

        $karyawanIdsArray = explode(',', $karyawanIds);

        // Get karyawan data
        $karyawans = Karyawan::whereIn('absen_karyawan_id', $karyawanIdsArray)
            ->select('absen_karyawan_id', 'nama_lengkap', 'nik')
            ->orderBy('nama_lengkap')
            ->get();

        if ($karyawans->isEmpty()) {
            return redirect()->route('reimbursement-files.index')
                ->with('error', 'Karyawan tidak ditemukan');
        }

        return view('dashboard.dashboard-admin.reimbursement-files.create', compact(
            'karyawans',
            'year'
        ));
    }

    /**
     * ✅ NEW: Validate single file (AJAX real-time validation)
     */
    public function validateFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120', // 5MB
            'karyawan_id' => 'required|exists:karyawans,absen_karyawan_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Additional size check
        $file = $request->file('file');
        $fileSizeKB = $file->getSize() / 1024;
        
        if ($fileSizeKB > 5120) {
            $fileSizeMB = round($fileSizeKB / 1024, 2);
            return response()->json([
                'success' => false,
                'message' => "File terlalu besar ({$fileSizeMB}MB). Maksimal 5MB"
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'File valid',
            'data' => [
                'filename' => $file->getClientOriginalName(),
                'size' => round($fileSizeKB / 1024, 2) . ' MB'
            ]
        ]);
    }

    /**
     * ✅ IMPROVED: Store with better error handling
     */
    /**
 * ✅ IMPROVED: Store with Laravel random filename
 */
public function store(Request $request)
{
    // Initial validation
    $validator = Validator::make($request->all(), [
        'year' => 'required|integer',
        'karyawan_data' => 'required|array|min:1',
        'karyawan_data.*.karyawan_id' => 'required|exists:karyawans,absen_karyawan_id',
        'karyawan_data.*.files' => 'required|array|min:1',
    ], [
        'year.required' => 'Tahun wajib diisi',
        'karyawan_data.required' => 'Data karyawan tidak valid',
        'karyawan_data.*.files.required' => 'Minimal 1 file harus diupload per karyawan',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    DB::beginTransaction();
    
    try {
        $uploadedCount = 0;
        $errors = [];
        $uploadedPaths = [];

        // Process uploaded files
        foreach ($request->karyawan_data as $index => $karyawanData) {
            $karyawanId = $karyawanData['karyawan_id'];
            
            // Get karyawan info
            $karyawan = Karyawan::where('absen_karyawan_id', $karyawanId)->first();
            $karyawanName = $karyawan ? $karyawan->nama_lengkap : "Karyawan #{$karyawanId}";

            if (!isset($karyawanData['files']) || !is_array($karyawanData['files'])) {
                $errors[] = [
                    'karyawan_id' => $karyawanId,
                    'karyawan_name' => $karyawanName,
                    'message' => 'Tidak ada file yang diupload'
                ];
                continue;
            }

            foreach ($karyawanData['files'] as $fileData) {
                try {
                    // Decode base64 file
                    $fileContent = base64_decode($fileData['content']);
                    
                    // ✅ Generate random filename using Laravel (hashName style)
                    $extension = strtolower($fileData['extension']);
                    $filename = \Illuminate\Support\Str::random(40) . '.' . $extension; // 40 chars random
                    
                    // Store file
                    $path = "reimbursement-files/{$request->year}/{$filename}";
                    Storage::disk('public')->put($path, $fileContent);

                    // Save to database
                    ReimbursementFile::create([
                        'karyawan_id' => $karyawanId,
                        'year' => $request->year,
                        'file' => $path,
                    ]);

                    $uploadedPaths[] = $path;
                    $uploadedCount++;

                } catch (\Exception $e) {
                    $errors[] = [
                        'karyawan_id' => $karyawanId,
                        'karyawan_name' => $karyawanName,
                        'filename' => $fileData['name'] ?? 'unknown',
                        'message' => $e->getMessage()
                    ];
                }
            }
        }

        // If all failed, rollback
        if ($uploadedCount === 0) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Semua file gagal diupload',
                'errors' => $errors
            ], 422);
        }

        DB::commit();

        Log::info('Reimbursement Files Uploaded', [
            'uploaded_count' => $uploadedCount,
            'failed_count' => count($errors),
            'year' => $request->year,
            'user_id' => auth()->id()
        ]);

        // Return with partial success if some failed
        $response = [
            'success' => true,
            'message' => "Berhasil upload {$uploadedCount} file!",
            'data' => [
                'uploaded_count' => $uploadedCount,
                'failed_count' => count($errors)
            ]
        ];

        if (!empty($errors)) {
            $response['warnings'] = $errors;
            $response['message'] .= " ({count($errors)} file gagal)";
        }

        return response()->json($response);

    } catch (\Exception $e) {
        DB::rollBack();
        
        // Cleanup uploaded files
        foreach ($uploadedPaths as $path) {
            try {
                Storage::disk('public')->delete($path);
            } catch (\Exception $cleanupError) {
                Log::error('Cleanup Error', ['path' => $path, 'error' => $cleanupError->getMessage()]);
            }
        }

        Log::error('Reimbursement File Upload Failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Download file
     */
    public function download($id)
    {
        try {
            $file = ReimbursementFile::findOrFail($id);
            
            $filePath = storage_path('app/public/' . $file->file);
            
            if (!file_exists($filePath)) {
                return redirect()->back()->with('error', 'File tidak ditemukan di storage');
            }

            return response()->download($filePath);

        } catch (\Exception $e) {
            Log::error('File Download Error', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->with('error', 'File tidak ditemukan');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        
        try {
            $file = ReimbursementFile::findOrFail($id);

            // Delete file from storage
            if (Storage::disk('public')->exists($file->file)) {
                Storage::disk('public')->delete($file->file);
            }

            // Delete record
            $file->delete();

            DB::commit();

            Log::info('Reimbursement File Deleted', [
                'id' => $id,
                'file' => $file->file,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File berhasil dihapus!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('File Delete Error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get karyawan list for Select2
     */
    public function getKaryawanList(Request $request)
    {
        $search = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = 15;

        $query = Karyawan::active()
            ->select('absen_karyawan_id', 'nama_lengkap', 'nik')
            ->orderBy('nama_lengkap');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $karyawans = $query->skip(($page - 1) * $perPage)
                          ->take($perPage)
                          ->get();

        $data = $karyawans->map(function($karyawan) {
            return [
                'id' => $karyawan->absen_karyawan_id,
                'text' => $karyawan->nama_lengkap . ' (NIK: ' . ($karyawan->nik ?? '-') . ')',
            ];
        });

        return response()->json([
            'results' => $data,
            'pagination' => [
                'more' => ($page * $perPage) < $total
            ]
        ]);
    }
}