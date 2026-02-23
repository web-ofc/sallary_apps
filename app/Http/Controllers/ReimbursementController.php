<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\Payroll;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use App\Models\Reimbursement;
use App\Models\ReimbursementChild;
use Illuminate\Support\Facades\DB;
use App\Models\ReimbursementPeriod;
use Illuminate\Support\Facades\Gate;
use App\Models\MasterReimbursementType;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf; // ✅ TAMBAH INI
use Illuminate\Support\Facades\Log;

class ReimbursementController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Gate::denies('manage-reimbursements')) {
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
        return view('dashboard.dashboard-management.reimbursements.index');
    }

    /**
     * Get data for DataTables
     */
    public function getData(Request $request)
    {
        $query = Reimbursement::query()
            ->select([
                'reimbursements.id',
                'reimbursements.id_recapan',
                'reimbursements.karyawan_id',
                'reimbursements.company_id',
                'reimbursements.year_budget',
                'reimbursements.periode_slip',
                'reimbursements.status',
                'reimbursements.user_by_id',
                'reimbursements.approved_id',
                'reimbursements.approved_at',
                'reimbursements.created_at'
            ])
            ->with([
                'karyawan:absen_karyawan_id,nama_lengkap,nik',
                'company:absen_company_id,company_name',
                'childs:reimbursement_id,harga'
            ]);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status === 'approved');
        }

        // Filter by year
        if ($request->filled('year')) {
            $query->where('year_budget', $request->year);
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('karyawan_info', function ($reimbursement) {
                if ($reimbursement->karyawan) {
                    return '<div class="d-flex flex-column">'
                         . '<span class="fw-bold">' . e($reimbursement->karyawan->nama_lengkap) . '</span>'
                         . '</div>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('approver_info', function ($reimbursement) {
                if ($reimbursement->approver) {
                    return '<div class="d-flex flex-column">'
                         . '<span class="fw-bold">' . e($reimbursement->approver->nama_lengkap) . '</span>'
                         . '</div>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('created_by', function ($reimbursement) {
                if ($reimbursement->userBy) {
                    return '<div class="d-flex flex-column">'
                         . '<span class="fw-bold">' . e($reimbursement->userBy->name) . '</span>'
                         . '</div>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('company_info', function ($reimbursement) {
                if ($reimbursement->company) {
                    return '<div class="d-flex flex-column">'
                         . '<span class="fw-bold">' . e($reimbursement->company->company_name) . '</span>'
                         . '</div>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->editColumn('id_recapan', function ($reimbursement) {
                return '<span class="badge badge-light-primary">' . e($reimbursement->id_recapan) . '</span>';
            })
            ->editColumn('periode_slip', function ($reimbursement) {
                return Carbon::parse($reimbursement->periode_slip . '-01')->format('M Y');
            })
            ->addColumn('total_amount', function ($reimbursement) {
                $total = $reimbursement->childs->sum('harga');
                return '<span class="fw-bold text-success">Rp ' . number_format($total, 0, ',', '.') . '</span>';
            })
            ->editColumn('status', function ($reimbursement) {
                if ($reimbursement->status) {
                    return '<span class="badge badge-light-success">Approved</span>';
                }
                return '<span class="badge badge-light-warning">Pending</span>';
            })
            ->addColumn('action', function ($reimbursement) {
                $nama = $reimbursement->karyawan ? e($reimbursement->karyawan->nama_lengkap) : 'Data';
                $buttons = '<div class="btn-group" role="group">';
                
                // View button
                $buttons .= '<button type="button" class="btn btn-sm btn-light-info" onclick="viewReimbursement(' . $reimbursement->id . ')" title="Detail">'
                        . '<i class="fas fa-eye"></i></button>';
                
                // ✅ TAMBAH: Download PDF button - only for approved
                if ($reimbursement->status) {
                    $buttons .= '<a href="' . route('manage-reimbursements.download-pdf', $reimbursement->id) . '" class="btn btn-sm btn-light-success" title="Download PDF" target="_blank">'
                            . '<i class="fas fa-file-pdf"></i></a>';
                }
                
                // Edit button - only for pending
                if (!$reimbursement->status) {
                    $buttons .= '<button type="button" class="btn btn-sm btn-light-warning" onclick="editReimbursement(' . $reimbursement->id . ')" title="Edit">'
                            . '<i class="fas fa-edit"></i></button>';
                }
                
                // Delete button - only for pending
                if (!$reimbursement->status) {
                    $buttons .= '<button type="button" class="btn btn-sm btn-light-danger" onclick="deleteReimbursement(' . $reimbursement->id . ', \'' . str_replace("'", "\\'", $nama) . '\')" title="Hapus">'
                            . '<i class="fas fa-trash"></i></button>';
                }
                
                $buttons .= '</div>';
                return $buttons;
            })
            ->rawColumns(['karyawan_info','company_info', 'approver_info', 'created_by', 'id_recapan', 'total_amount', 'status', 'action'])
            ->make(true);
    }

    /**
     * ✅ DOWNLOAD PDF REIMBURSEMENT
     * GET /manage-reimbursements/{id}/download-pdf
     */
        public function downloadPdf($id)
{
    Log::info("DOWNLOAD PDF HIT", ['id' => $id, 'user_id' => auth()->id()]);

    try {
        $reimbursement = Reimbursement::with([
            'karyawan:absen_karyawan_id,nama_lengkap,nik,email_pribadi,telp_pribadi',
            'company:absen_company_id,company_name,logo,ttd,nama_ttd,jabatan_ttd',
            'approver:absen_karyawan_id,nama_lengkap',
            'userBy:id,name,email',
            'childs.reimbursementType'
        ])->findOrFail($id);

        // ✅ Check if approved
        if (!$reimbursement->status) {
            return redirect()->back()->with('error', 'Hanya reimbursement yang sudah approved yang dapat didownload!');
        }

        // ✅ Get balance info for the year used
        $balance = DB::table('balance_reimbursements')
            ->where('karyawan_id', $reimbursement->karyawan_id)
            ->where('year', $reimbursement->year_budget)
            ->first();

        // ✅ Calculate total
        $totalAmount = $reimbursement->childs->sum('harga');

        // ✅✅ MANIPULASI BALANCE - Hanya kurangi total_used, remaining tetep
        if ($balance) {
            $balance = (object)[
                'year' => $balance->year,
                'budget_claim' => $balance->budget_claim,
                'total_used' => ($balance->total_used ?? 0) - $totalAmount, // ✅ DIKURANGI
                'sisa_budget' => $balance->sisa_budget, // ✅ TETAP dari view
            ];
        }

        // ✅ Group childs by group_medical
        $generalChilds = $reimbursement->childs->filter(function($child) {
            return $child->reimbursementType->group_medical === 'general';
        });

        $otherChilds = $reimbursement->childs->filter(function($child) {
            return $child->reimbursementType->group_medical === 'other';
        });

        // ✅ Convert company logo & ttd to base64
        $companyData = null;
        if ($reimbursement->company) {
            $companyData = (object)[
                'company_name' => $reimbursement->company->company_name,
                'logo' => $this->getImageBase64($reimbursement->company->logo, 'logos'),
                'ttd' => $this->getImageBase64($reimbursement->company->ttd, 'ttd'),
                'nama_ttd' => $reimbursement->company->nama_ttd,
                'jabatan_ttd' => $reimbursement->company->jabatan_ttd,
            ];
        }

        // ✅ Prepare data for PDF
        $data = [
            'reimbursement' => $reimbursement,
            'karyawan' => $reimbursement->karyawan,
            'company' => $companyData,
            'approver' => $reimbursement->approver,
            'preparedBy' => $reimbursement->userBy,
            'balance' => $balance, // ✅ Balance dengan total_used termodifikasi
            'totalAmount' => $totalAmount,
            'generalChilds' => $generalChilds,
            'otherChilds' => $otherChilds,
            'printDate' => Carbon::now()->format('d F Y H:i'),
        ];

        // ✅ Generate PDF
        $pdf = Pdf::loadView('dashboard.dashboard-management.reimbursements.pdf', $data);
        
        // ✅ Set paper size and orientation
        $pdf->setPaper('a4', 'portrait');

        // ✅ Download dengan nama file yang jelas
        $filename = 'Reimbursement_' . $reimbursement->id_recapan . '_' . $reimbursement->karyawan->nama_lengkap . '.pdf';
        
        return $pdf->download($filename);

    } catch (\Exception $e) {
        Log::error('Medical Reimbursement PDF Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return redirect()->back()->with('error', 'Gagal generate PDF: ' . $e->getMessage());
    }
}

    /**
     * Convert remote image to base64 for PDF
     */
    private function getImageBase64($filename, $folder = 'logos')
    {
        if (empty($filename)) {
            return null;
        }
        
        try {
            // Mapping folder names (singular -> plural)
            $folderMap = [
                'logo' => 'logos',
                'logos' => 'logos',
                'ttd' => 'ttds',
                'ttds' => 'ttds',
            ];
            
            $folderPath = $folderMap[$folder] ?? $folder;
            
            $url = str_starts_with($filename, 'http') 
                ? $filename 
                : "https://haadhir.id/storage/{$folderPath}/" . basename($filename);
            
            \Log::info("Fetching image from: {$url}");
            
            // Fetch image dengan error handling
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'ignore_errors' => true
                ]
            ]);
            
            $imageContent = @file_get_contents($url, false, $context);
            
            if ($imageContent === false) {
                \Log::warning("Failed to fetch image: {$url}");
                return null;
            }
            
            // Get mime type
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageContent);
            
            // Convert to base64
            $base64 = base64_encode($imageContent);
            
            \Log::info("Image converted successfully", [
                'url' => $url,
                'mime' => $mimeType,
                'size' => strlen($imageContent)
            ]);
            
            return "data:{$mimeType};base64,{$base64}";
            
        } catch (\Exception $e) {
            \Log::error("Image conversion error", [
                'error' => $e->getMessage(),
                'url' => $url ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * STEP 1: Show initial modal (pilih karyawan + periode)
     */
    public function preCreate()
    {
        return response()->json(['success' => true]);
    }

    /**
     * STEP 2: Validate and redirect to create form
     */
        public function validatePreCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'karyawan_id' => 'required|exists:karyawans,absen_karyawan_id',
            'company_id' => 'required|exists:companies,absen_company_id',  // ✅ TAMBAH INI
            'periode_slip' => 'required|date_format:Y-m',
        ], [
            'karyawan_id.required' => 'Karyawan wajib dipilih',
            'karyawan_id.exists' => 'Karyawan tidak valid',
            'company_id.required' => 'Company wajib dipilih',  // ✅ TAMBAH INI
            'company_id.exists' => 'Company tidak valid',      // ✅ TAMBAH INI
            'periode_slip.required' => 'Periode slip wajib diisi',
            'periode_slip.date_format' => 'Format periode tidak valid (YYYY-MM)',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // ✅ VALIDATION 1: Check payroll existence
        $payrollExists = Payroll::where('karyawan_id', $request->karyawan_id)
            ->where('periode', $request->periode_slip)
            ->exists();

        if ($payrollExists) {
            return response()->json([
                'success' => false,
                'message' => 'Reimbursement tidak dapat dibuat! Payroll untuk periode ini sudah dibuat. Silakan hubungi admin untuk perubahan.'
            ], 422);
        }

        // VALIDATION 2: Check if already exists for this periode
        $exists = Reimbursement::where('karyawan_id', $request->karyawan_id)
            ->where('periode_slip', $request->periode_slip)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Reimbursement untuk periode ini sudah ada!'
            ], 422);
        }

        // Return redirect URL with params - ✅ TAMBAHKAN company_id
        return response()->json([
            'success' => true,
            'redirect_url' => route('manage-reimbursements.create', [
                'karyawan_id' => $request->karyawan_id,
                'company_id' => $request->company_id,  // ✅ TAMBAH INI
                'periode_slip' => $request->periode_slip
            ])
        ]);
    }
    /**
     * Show the form for creating a new resource (STEP 2)
     */
        public function create(Request $request)
    {
        $karyawanId = $request->query('karyawan_id');
        $companyId = $request->query('company_id');  // ✅ TAMBAH INI
        $periodeSlip = $request->query('periode_slip');

        if (!$karyawanId || !$companyId || !$periodeSlip) {  // ✅ TAMBAH !$companyId
            return redirect()->route('manage-reimbursements.index')
                ->with('error', 'Parameter tidak lengkap');
        }

        // Get karyawan data
        $karyawan = Karyawan::where('absen_karyawan_id', $karyawanId)
            ->select('absen_karyawan_id', 'nama_lengkap', 'nik')
            ->firstOrFail();

        // ✅ TAMBAH: Get company data
        $company = Company::where('absen_company_id', $companyId)
            ->select('absen_company_id', 'company_name')
            ->firstOrFail();

        // Get available years from balance_reimbursements view
        $availableYears = $this->getAvailableYears($karyawanId);

        // Get reimbursement types
        $generalTypes = MasterReimbursementType::general()
            ->select('id', 'code', 'medical_type')
            ->orderBy('medical_type')
            ->get();

        $otherTypes = MasterReimbursementType::other()
            ->select('id', 'code', 'medical_type')
            ->orderBy('medical_type')
            ->get();

        return view('dashboard.dashboard-management.reimbursements.create', compact(
            'karyawan',
            'company',      // ✅ TAMBAH INI
            'periodeSlip',
            'availableYears',
            'generalTypes',
            'otherTypes'
        ));
    }

    /**
     * Get balance from view for specific year
     */
    public function getBalanceByYear(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'karyawan_id' => 'required|exists:karyawans,absen_karyawan_id',
            'year' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Query dari view balance_reimbursements
        $balance = DB::table('balance_reimbursements')
            ->where('karyawan_id', $request->karyawan_id)
            ->where('year', $request->year)
            ->first();

        if (!$balance) {
            return response()->json([
                'success' => false,
                'message' => 'Data balance tidak ditemukan untuk tahun ini'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $balance->year,
                'sisa_budget' => $balance->sisa_budget ?? 0,
                'total_budget' => $balance->budget_claim ?? 0,
                'terpakai' => $balance->total_used ?? 0,
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage (with race condition handling)
     */
    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'karyawan_id' => 'required|exists:karyawans,absen_karyawan_id',
        'year_budget' => 'required|integer|min:2000',
         'company_id' => 'required|exists:companies,absen_company_id',
        'periode_slip' => 'required|date_format:Y-m',
        
        // Children validation (array)
        'children' => 'required|array|min:1',
        'children.*.reimbursement_type_id' => 'required|exists:master_reimbursement_types,id',
        'children.*.harga' => 'required|integer|min:1',
        'children.*.jenis_penyakit' => 'nullable|string|max:255',
        'children.*.status_keluarga' => 'nullable|string|max:100',
        'children.*.note' => 'nullable|string|max:500',
    ], [
        'karyawan_id.required' => 'Karyawan wajib dipilih',
        'year_budget.required' => 'Tahun budget wajib dipilih',
        'periode_slip.required' => 'Periode slip wajib diisi',
        'company_id.required' => 'Company wajib dipilih',
        'children.required' => 'Minimal harus ada 1 item reimbursement',
        'children.min' => 'Minimal harus ada 1 item reimbursement',
        'children.*.reimbursement_type_id.required' => 'Tipe reimbursement wajib dipilih',
        'children.*.harga.required' => 'Harga wajib diisi',
        'children.*.harga.min' => 'Harga minimal Rp 1',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    // START TRANSACTION with LOCK to prevent race condition
    DB::beginTransaction();
    
    try {
        // VALIDATION 1: Check apakah periode + karyawan sudah ada di payrolls
        $payrollExists = Payroll::where('karyawan_id', $request->karyawan_id)
            ->where('periode', $request->periode_slip)
            ->exists();

        if ($payrollExists) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Reimbursement tidak dapat dibuat! Payroll untuk periode ini sudah dibuat. Silakan hubungi admin untuk perubahan.'
            ], 422);
        }

        // VALIDATION 2: Lock - Check duplicate reimbursement dengan FOR UPDATE
        $exists = Reimbursement::where('karyawan_id', $request->karyawan_id)
            ->where('periode_slip', $request->periode_slip)
            ->lockForUpdate() // IMPORTANT: Prevent race condition
            ->exists();

        if ($exists) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Reimbursement untuk periode ini sudah ada!'
            ], 422);
        }

        // Calculate total dari children
        $totalAmount = collect($request->children)->sum('harga');

        // VALIDATION 3: Validate against budget dari VIEW
        $balance = DB::table('balance_reimbursements')
            ->where('karyawan_id', $request->karyawan_id)
            ->where('year', $request->year_budget)
            ->first();

        if (!$balance) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Data balance tidak ditemukan untuk tahun ini'
            ], 404);
        }

        if ($totalAmount > $balance->sisa_budget) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Total reimbursement (Rp ' . number_format($totalAmount, 0, ',', '.') . ') melebihi sisa budget (Rp ' . number_format($balance->sisa_budget, 0, ',', '.') . ')'
            ], 422);
        }

        // Create reimbursement header
        $reimbursement = Reimbursement::create([
            'karyawan_id' => $request->karyawan_id,
             'company_id' => $request->company_id,
            'year_budget' => $request->year_budget,
            'periode_slip' => $request->periode_slip,
            'approved_id' => 6, // Default approver
            'user_by_id' => auth()->id(), // Track user yang membuat
            'status' => false,
            'approved_at' => null,
        ]);

        // Create children - hanya yang diisi
        foreach ($request->children as $child) {
            // Skip jika harga kosong atau 0
            if (empty($child['harga']) || $child['harga'] <= 0) {
                continue;
            }

            ReimbursementChild::create([
                'reimbursement_id' => $reimbursement->id,
                'reimbursement_type_id' => $child['reimbursement_type_id'],
                'harga' => $child['harga'],
                'jenis_penyakit' => $child['jenis_penyakit'] ?? null,
                'status_keluarga' => $child['status_keluarga'] ?? null,
                'note' => $child['note'] ?? null,
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Reimbursement berhasil ditambahkan!',
            'data' => [
                'id' => $reimbursement->id,
                'id_recapan' => $reimbursement->id_recapan,
            ]
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $reimbursement = Reimbursement::with([
                'karyawan:absen_karyawan_id,nama_lengkap,nik',
                'company:absen_company_id,company_name',  // ✅ TAMBAH INI
                'childs.reimbursementType'
            ])->findOrFail($id);

            // Only allow edit if not approved
            if ($reimbursement->status) {
                return redirect()->route('manage-reimbursements.index')
                    ->with('error', 'Reimbursement yang sudah approved tidak dapat diedit!');
            }

            // Get karyawan data
            $karyawan = $reimbursement->karyawan;

            // Get available years from balance_reimbursements view
            $availableYears = $this->getAvailableYears($reimbursement->karyawan_id);

            // Get reimbursement types
            $generalTypes = MasterReimbursementType::general()
                ->select('id', 'code', 'medical_type')
                ->orderBy('medical_type')
                ->get();

            $otherTypes = MasterReimbursementType::other()
                ->select('id', 'code', 'medical_type')
                ->orderBy('medical_type')
                ->get();

            return view('dashboard.dashboard-management.reimbursements.edit', compact(
                'reimbursement',
                'karyawan',
                'availableYears',
                'generalTypes',
                'otherTypes'
            ));
        } catch (\Exception $e) {
            return redirect()->route('manage-reimbursements.index')
                ->with('error', 'Data tidak ditemukan');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'year_budget' => 'required|integer|min:2000',
            
            // Children validation (array)
            'children' => 'required|array|min:1',
            'children.*.reimbursement_type_id' => 'required|exists:master_reimbursement_types,id',
            'children.*.harga' => 'required|integer|min:1',
            'children.*.jenis_penyakit' => 'nullable|string|max:255',
            'children.*.status_keluarga' => 'nullable|string|max:100',
            'children.*.note' => 'nullable|string|max:500',
        ], [
            'year_budget.required' => 'Tahun budget wajib dipilih',
            'children.required' => 'Minimal harus ada 1 item reimbursement',
            'children.min' => 'Minimal harus ada 1 item reimbursement',
            'children.*.reimbursement_type_id.required' => 'Tipe reimbursement wajib dipilih',
            'children.*.harga.required' => 'Harga wajib diisi',
            'children.*.harga.min' => 'Harga minimal Rp 1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $reimbursement = Reimbursement::findOrFail($id);

            // Only allow update if not approved
            if ($reimbursement->status) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Reimbursement yang sudah approved tidak dapat diubah!'
                ], 422);
            }

            // Calculate total dari children
            $totalAmount = collect($request->children)->sum('harga');

            // Validate against budget dari VIEW
            $balance = DB::table('balance_reimbursements')
                ->where('karyawan_id', $reimbursement->karyawan_id)
                ->where('year', $request->year_budget)
                ->first();

            if (!$balance) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Data balance tidak ditemukan untuk tahun ini'
                ], 404);
            }

                
            if ($totalAmount > $balance->sisa_budget) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Total reimbursement (Rp ' . number_format($totalAmount, 0, ',', '.') . ') melebihi sisa budget (Rp ' . number_format($balance->sisa_budget, 0, ',', '.') . ')'
                ], 422);
            }

            // Hitung sisa budget (exclude current reimbursement dari perhitungan)
            $currentTotal = $reimbursement->childs()->sum('harga');
            $availableBudget = $balance->sisa_budget + $currentTotal;

            if ($totalAmount > $availableBudget) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Total reimbursement (Rp ' . number_format($totalAmount, 0, ',', '.') . ') melebihi sisa budget yang tersedia (Rp ' . number_format($availableBudget, 0, ',', '.') . ')'
                ], 422);
            }

            // Update header
            $reimbursement->update([
                'year_budget' => $request->year_budget,
            ]);

            // Delete all existing children
            $reimbursement->childs()->delete();

            // Create new children
            foreach ($request->children as $child) {
                if (empty($child['harga']) || $child['harga'] <= 0) {
                    continue;
                }

                ReimbursementChild::create([
                    'reimbursement_id' => $reimbursement->id,
                    'reimbursement_type_id' => $child['reimbursement_type_id'],
                    'harga' => $child['harga'],
                    'jenis_penyakit' => $child['jenis_penyakit'] ?? null,
                    'status_keluarga' => $child['status_keluarga'] ?? null,
                    'note' => $child['note'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reimbursement berhasil diperbarui!',
                'data' => [
                    'id' => $reimbursement->id,
                    'id_recapan' => $reimbursement->id_recapan,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    public function show($id)
{
    try {
        $reimbursement = Reimbursement::with([
            'karyawan:absen_karyawan_id,nama_lengkap,nik',
            'approver:absen_karyawan_id,nama_lengkap',
            'childs.reimbursementType'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $reimbursement
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Data tidak ditemukan'
        ], 404);
    }
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        
        try {
            $reimbursement = Reimbursement::findOrFail($id);

            // Only allow delete if not approved
            if ($reimbursement->status) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reimbursement yang sudah approved tidak dapat dihapus!'
                ], 422);
            }

            // Delete children first (cascade will handle this, but explicit is better)
            $reimbursement->childs()->delete();
            
            // Delete header
            $reimbursement->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reimbursement berhasil dihapus!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available years from balance_reimbursements view
     */
    private function getAvailableYears($karyawanId)
    {
        return DB::table('balance_reimbursements')
            ->where('karyawan_id', $karyawanId)
            ->where('sisa_budget', '>', 0) // Only years with remaining budget
            ->select('year', 'sisa_budget', 'budget_claim as total_budget')
            ->orderBy('year', 'desc')
            ->get();
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
        ->whereHas('salaries', function ($q) {
            $q->where('status_medical', '1'); // karena di DB string
        })
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
                'id' => $karyawan->absen_karyawan_id,
                'text' => $karyawan->nama_lengkap . ' (NIK: ' . ($karyawan->nik ?? '-') . ', ID: ' . $karyawan->absen_karyawan_id . ')',
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
     * Get company list for Select2
     */
    public function getCompanyList(Request $request)
    {
        $search = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = 15;

        $query = Company::select('absen_company_id', 'company_name')
            ->orderBy('company_name');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                ->orWhere('absen_company_id', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $companies = $query->skip(($page - 1) * $perPage)
                        ->take($perPage)
                        ->get();

        $data = $companies->map(function($company) {
            return [
                'id' => $company->absen_company_id,
                'text' => $company->company_name . ' (ID: ' . $company->absen_company_id . ')',
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