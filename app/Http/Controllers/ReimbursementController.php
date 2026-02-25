<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\Payroll;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use App\Models\Reimbursement;
use App\Models\ReimbursementChild;
use App\Services\KaryawanJabatanPerusahaanServices;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class ReimbursementController extends Controller
{
    protected KaryawanJabatanPerusahaanServices $syncService;

    public function __construct(KaryawanJabatanPerusahaanServices $syncService)
    {
        $this->syncService = $syncService;

        $this->middleware(function ($request, $next) {
            if (Gate::denies('manage-reimbursements')) {
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        });
    }

    // =========================================================================
    // INDEX
    // =========================================================================
    public function index()
    {
        return view('dashboard.dashboard-management.reimbursements.index');
    }

    // =========================================================================
    // DATATABLES
    // =========================================================================
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
                'reimbursements.created_at',
            ])
            ->with([
                'karyawan:absen_karyawan_id,nama_lengkap,nik',
                'company:absen_company_id,company_name',
                'childs:reimbursement_id,tagihan_dokter,tagihan_obat,tagihan_kacamata,tagihan_gigi',
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status === 'approved');
        }
        if ($request->filled('year')) {
            $query->where('year_budget', $request->year);
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('karyawan_info', fn($r) => $r->karyawan
                ? '<div class="d-flex flex-column"><span class="fw-bold">' . e($r->karyawan->nama_lengkap) . '</span></div>'
                : '<span class="text-muted">-</span>')
            ->addColumn('approver_info', fn($r) => $r->approver
                ? '<div class="d-flex flex-column"><span class="fw-bold">' . e($r->approver->nama_lengkap) . '</span></div>'
                : '<span class="text-muted">-</span>')
            ->addColumn('created_by', fn($r) => $r->userBy
                ? '<div class="d-flex flex-column"><span class="fw-bold">' . e($r->userBy->name) . '</span></div>'
                : '<span class="text-muted">-</span>')
            ->addColumn('company_info', fn($r) => $r->company
                ? '<div class="d-flex flex-column"><span class="fw-bold">' . e($r->company->company_name) . '</span></div>'
                : '<span class="text-muted">-</span>')
            ->editColumn('id_recapan', fn($r) => '<span class="badge badge-light-primary">' . e($r->id_recapan) . '</span>')
            ->editColumn('periode_slip', fn($r) => Carbon::parse($r->periode_slip . '-01')->format('M Y'))
            ->addColumn('total_amount', function ($r) {
                $total = $r->childs->sum(fn($c) =>
                    ($c->tagihan_dokter ?? 0) + ($c->tagihan_obat ?? 0) +
                    ($c->tagihan_kacamata ?? 0) + ($c->tagihan_gigi ?? 0)
                );
                return '<span class="fw-bold text-success">Rp ' . number_format($total, 0, ',', '.') . '</span>';
            })
            ->editColumn('status', fn($r) => $r->status
                ? '<span class="badge badge-light-success">Approved</span>'
                : '<span class="badge badge-light-warning">Pending</span>')
            ->editColumn('approved_at', fn($r) => $r->approved_at
                ? Carbon::parse($r->approved_at)->setTimezone('Asia/Jakarta')->translatedFormat('d M Y')
                : '<span class="text-muted">-</span>')
            ->editColumn('created_at', fn($r) =>
                Carbon::parse($r->created_at)->setTimezone('Asia/Jakarta')->translatedFormat('d M Y, H:i')
                . ' <span class="text-muted fs-9">WIB</span>')
            ->addColumn('action', function ($r) {
                $nama    = $r->karyawan ? e($r->karyawan->nama_lengkap) : 'Data';
                $buttons = '<div class="btn-group" role="group">';
                $buttons .= '<button type="button" class="btn btn-sm btn-light-info" onclick="viewReimbursement(' . $r->id . ')" title="Detail"><i class="fas fa-eye"></i></button>';
                if ($r->status) {
                    $buttons .= '<a href="' . route('manage-reimbursements.download-pdf', $r->id) . '" class="btn btn-sm btn-light-success" title="Download PDF" target="_blank"><i class="fas fa-file-pdf"></i></a>';
                }
                if (!$r->status) {
                    $buttons .= '<button type="button" class="btn btn-sm btn-light-warning" onclick="editReimbursement(' . $r->id . ')" title="Edit"><i class="fas fa-edit"></i></button>';
                    $buttons .= '<button type="button" class="btn btn-sm btn-light-danger" onclick="deleteReimbursement(' . $r->id . ', \'' . str_replace("'", "\\'", $nama) . '\')" title="Hapus"><i class="fas fa-trash"></i></button>';
                }
                $buttons .= '</div>';
                return $buttons;
            })
            ->rawColumns(['approved_at', 'created_at', 'karyawan_info', 'company_info', 'approver_info', 'created_by', 'id_recapan', 'total_amount', 'status', 'action'])
            ->make(true);
    }

    // =========================================================================
    // ✅ SYNC ENDPOINT — dipanggil AJAX saat user klik "Tambah Reimbursement"
    // POST /manage-reimbursements/sync-karyawan
    // =========================================================================
    public function syncKaryawan()
    {
        $result = $this->syncService->forceSync();

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    // =========================================================================
    // GET KARYAWAN LIST untuk Select2 — dari tabel lokal (hasil sync)
    // =========================================================================
    public function getKaryawanList(Request $request)
    {
        $search  = (string) $request->get('q', ''); // ✅ cast ke string, hindari null
        $page    = max(1, (int) $request->get('page', 1));
        $perPage = 15;

        return response()->json(
            $this->syncService->searchLocal($search, $page, $perPage)
        );
    }

    // =========================================================================
    // GET COMPANY LIST untuk Select2 — dari DB lokal
    // =========================================================================
    public function getCompanyList(Request $request)
    {
        $search  = $request->get('q', '');
        $page    = (int) $request->get('page', 1);
        $perPage = 15;

        $query = Company::select('absen_company_id', 'company_name')->orderBy('company_name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('absen_company_id', 'like', "%{$search}%");
            });
        }

        $total     = $query->count();
        $companies = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        return response()->json([
            'results' => $companies->map(fn($c) => [
                'id'   => $c->absen_company_id,
                'text' => $c->company_name . ' (ID: ' . $c->absen_company_id . ')',
            ]),
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }

    // =========================================================================
    // PRE-CREATE
    // =========================================================================
    public function preCreate()
    {
        return response()->json(['success' => true]);
    }

    public function validatePreCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'karyawan_id' => 'required|exists:karyawans,absen_karyawan_id',
            'company_id'  => 'required|exists:companies,absen_company_id',
        ], [
            'karyawan_id.required' => 'Karyawan wajib dipilih',
            'karyawan_id.exists'   => 'Karyawan tidak valid',
            'company_id.required'  => 'Company wajib dipilih',
            'company_id.exists'    => 'Company tidak valid',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $periodeSlip = now()->format('Y-m');

        $payrollExists = Payroll::where('karyawan_id', $request->karyawan_id)
            ->where('periode', $periodeSlip)->exists();

        if ($payrollExists) {
            return response()->json([
                'success' => false,
                'message' => 'Reimbursement tidak dapat dibuat! Payroll untuk periode ini sudah dibuat.',
            ], 422);
        }

        return response()->json([
            'success'      => true,
            'redirect_url' => route('manage-reimbursements.create', [
                'karyawan_id'  => $request->karyawan_id,
                'company_id'   => $request->company_id,
                'periode_slip' => $periodeSlip,
            ]),
        ]);
    }

    // =========================================================================
    // CREATE
    // =========================================================================
    public function create(Request $request)
    {
        $karyawanId  = $request->query('karyawan_id');
        $companyId   = $request->query('company_id');
        $periodeSlip = $request->query('periode_slip');

        if (!$karyawanId || !$companyId || !$periodeSlip) {
            return redirect()->route('manage-reimbursements.index')->with('error', 'Parameter tidak lengkap');
        }

        $karyawan = Karyawan::where('absen_karyawan_id', $karyawanId)
            ->select('absen_karyawan_id', 'nama_lengkap', 'nik')->firstOrFail();

        $company = Company::where('absen_company_id', $companyId)
            ->select('absen_company_id', 'company_name')->firstOrFail();

        $availableYears = $this->getAvailableYears($karyawanId);

        return view('dashboard.dashboard-management.reimbursements.create', compact(
            'karyawan', 'company', 'periodeSlip', 'availableYears'
        ));
    }

    // =========================================================================
    // STORE
    // =========================================================================
    public function store(Request $request)
    {
        $periodeSlip = now()->format('Y-m');

        $validator = Validator::make($request->all(), [
            'karyawan_id'                     => 'required|exists:karyawans,absen_karyawan_id',
            'company_id'                      => 'required|exists:companies,absen_company_id',
            'children'                        => 'required|array|min:1',
            'children.*.tanggal'              => 'nullable|date',
            'children.*.nama_reimbursement'   => 'nullable|string|max:255',
            'children.*.status_keluarga'      => 'nullable|string|max:100',
            'children.*.jenis_penyakit'       => 'nullable|string|max:255',
            'children.*.tagihan_dokter'       => 'nullable|integer|min:0',
            'children.*.tagihan_obat'         => 'nullable|integer|min:0',
            'children.*.tagihan_kacamata'     => 'nullable|integer|min:0',
            'children.*.tagihan_gigi'         => 'nullable|integer|min:0',
            'children.*.note'                 => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        DB::beginTransaction();
        try {
            $payrollExists = Payroll::where('karyawan_id', $request->karyawan_id)
                ->where('periode', $periodeSlip)->exists();

            if ($payrollExists) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Payroll periode ini sudah dibuat.'], 422);
            }

            $totalAmount = collect($request->children)->sum(fn($c) =>
                ($c['tagihan_dokter'] ?? 0) + ($c['tagihan_obat'] ?? 0) +
                ($c['tagihan_kacamata'] ?? 0) + ($c['tagihan_gigi'] ?? 0)
            );

            $balance = DB::table('balance_reimbursements')
                ->where('karyawan_id', $request->karyawan_id)
                ->where('year', $request->year_budget)->first();

            if (!$balance) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Data balance tidak ditemukan untuk tahun ini'], 404);
            }

            if ($totalAmount > $balance->sisa_budget) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Total (Rp ' . number_format($totalAmount, 0, ',', '.') . ') melebihi sisa budget (Rp ' . number_format($balance->sisa_budget, 0, ',', '.') . ')',
                ], 422);
            }

            $reimbursement = Reimbursement::create([
                'karyawan_id'  => $request->karyawan_id,
                'company_id'   => $request->company_id,
                'year_budget'  => $request->year_budget,
                'periode_slip' => $periodeSlip,
                'approved_id'  => 6,
                'user_by_id'   => auth()->id(),
                'status'       => false,
                'approved_at'  => null,
            ]);

            foreach ($request->children as $child) {
                $subtotal = ($child['tagihan_dokter'] ?? 0) + ($child['tagihan_obat'] ?? 0)
                          + ($child['tagihan_kacamata'] ?? 0) + ($child['tagihan_gigi'] ?? 0);
                if ($subtotal <= 0) continue;

                ReimbursementChild::create([
                    'reimbursement_id'   => $reimbursement->id,
                    'tanggal'            => $child['tanggal'] ?? null,
                    'nama_reimbursement' => $child['nama_reimbursement'] ?? null,
                    'status_keluarga'    => $child['status_keluarga'] ?? null,
                    'jenis_penyakit'     => $child['jenis_penyakit'] ?? null,
                    'tagihan_dokter'     => $child['tagihan_dokter'] ?? 0,
                    'tagihan_obat'       => $child['tagihan_obat'] ?? 0,
                    'tagihan_kacamata'   => $child['tagihan_kacamata'] ?? 0,
                    'tagihan_gigi'       => $child['tagihan_gigi'] ?? 0,
                    'note'               => $child['note'] ?? null,
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Reimbursement berhasil ditambahkan!',
                'data'    => ['id' => $reimbursement->id, 'id_recapan' => $reimbursement->id_recapan],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // EDIT
    // =========================================================================
    public function edit($id)
    {
        try {
            $reimbursement = Reimbursement::with([
                'karyawan:absen_karyawan_id,nama_lengkap,nik',
                'company:absen_company_id,company_name',
                'childs',
            ])->findOrFail($id);

            if ($reimbursement->status) {
                return redirect()->route('manage-reimbursements.index')
                    ->with('error', 'Reimbursement yang sudah approved tidak dapat diedit!');
            }

            $karyawan       = $reimbursement->karyawan;
            $availableYears = $this->getAvailableYears($reimbursement->karyawan_id);

            return view('dashboard.dashboard-management.reimbursements.edit', compact(
                'reimbursement', 'karyawan', 'availableYears'
            ));
        } catch (\Exception $e) {
            return redirect()->route('manage-reimbursements.index')->with('error', 'Data tidak ditemukan');
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'karyawan_id'                     => 'required|exists:karyawans,absen_karyawan_id',
            'company_id'                      => 'required|exists:companies,absen_company_id',
            'children'                        => 'required|array|min:1',
            'children.*.tanggal'              => 'nullable|date',
            'children.*.nama_reimbursement'   => 'nullable|string|max:255',
            'children.*.status_keluarga'      => 'nullable|string|max:100',
            'children.*.jenis_penyakit'       => 'nullable|string|max:255',
            'children.*.tagihan_dokter'       => 'nullable|integer|min:0',
            'children.*.tagihan_obat'         => 'nullable|integer|min:0',
            'children.*.tagihan_kacamata'     => 'nullable|integer|min:0',
            'children.*.tagihan_gigi'         => 'nullable|integer|min:0',
            'children.*.note'                 => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        DB::beginTransaction();
        try {
            $reimbursement = Reimbursement::findOrFail($id);

            if ($reimbursement->status) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Reimbursement approved tidak dapat diubah!'], 422);
            }

            $totalAmount = collect($request->children)->sum(fn($c) =>
                ($c['tagihan_dokter'] ?? 0) + ($c['tagihan_obat'] ?? 0) +
                ($c['tagihan_kacamata'] ?? 0) + ($c['tagihan_gigi'] ?? 0)
            );

            $balance = DB::table('balance_reimbursements')
                ->where('karyawan_id', $reimbursement->karyawan_id)
                ->where('year', $request->year_budget)->first();

            if (!$balance) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Data balance tidak ditemukan'], 404);
            }

            $currentTotal = $reimbursement->childs->sum(fn($c) =>
                ($c->tagihan_dokter ?? 0) + ($c->tagihan_obat ?? 0) +
                ($c->tagihan_kacamata ?? 0) + ($c->tagihan_gigi ?? 0)
            );

            $availableBudget = $balance->sisa_budget + $currentTotal;

            if ($totalAmount > $availableBudget) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Total (Rp ' . number_format($totalAmount, 0, ',', '.') . ') melebihi sisa budget (Rp ' . number_format($availableBudget, 0, ',', '.') . ')',
                ], 422);
            }

            $reimbursement->update(['year_budget' => $request->year_budget]);
            $reimbursement->childs()->delete();

            foreach ($request->children as $child) {
                $subtotal = ($child['tagihan_dokter'] ?? 0) + ($child['tagihan_obat'] ?? 0)
                          + ($child['tagihan_kacamata'] ?? 0) + ($child['tagihan_gigi'] ?? 0);
                if ($subtotal <= 0) continue;

                ReimbursementChild::create([
                    'reimbursement_id'   => $reimbursement->id,
                    'tanggal'            => $child['tanggal'] ?? null,
                    'nama_reimbursement' => $child['nama_reimbursement'] ?? null,
                    'status_keluarga'    => $child['status_keluarga'] ?? null,
                    'jenis_penyakit'     => $child['jenis_penyakit'] ?? null,
                    'tagihan_dokter'     => $child['tagihan_dokter'] ?? 0,
                    'tagihan_obat'       => $child['tagihan_obat'] ?? 0,
                    'tagihan_kacamata'   => $child['tagihan_kacamata'] ?? 0,
                    'tagihan_gigi'       => $child['tagihan_gigi'] ?? 0,
                    'note'               => $child['note'] ?? null,
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Reimbursement berhasil diperbarui!',
                'data'    => ['id' => $reimbursement->id, 'id_recapan' => $reimbursement->id_recapan],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // SHOW
    // =========================================================================
    public function show($id)
    {
        try {
            $reimbursement = Reimbursement::with([
                'karyawan:absen_karyawan_id,nama_lengkap,nik',
                'approver:absen_karyawan_id,nama_lengkap',
                'childs',
            ])->findOrFail($id);

            return response()->json(['success' => true, 'data' => $reimbursement]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
    }

    // =========================================================================
    // DESTROY
    // =========================================================================
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $reimbursement = Reimbursement::findOrFail($id);

            if ($reimbursement->status) {
                return response()->json(['success' => false, 'message' => 'Reimbursement approved tidak dapat dihapus!'], 422);
            }

            $reimbursement->childs()->delete();
            $reimbursement->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Reimbursement berhasil dihapus!']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // DOWNLOAD PDF
    // =========================================================================
    public function downloadPdf($id)
    {
        Log::info("DOWNLOAD PDF HIT", ['id' => $id, 'user_id' => auth()->id()]);

        try {
            $reimbursement = Reimbursement::with([
                'karyawan:absen_karyawan_id,nama_lengkap,nik,email_pribadi,telp_pribadi',
                'company:absen_company_id,company_name,logo,ttd,nama_ttd,jabatan_ttd',
                'approver:absen_karyawan_id,nama_lengkap',
                'userBy:id,name,email',
                'childs',
            ])->findOrFail($id);

            if (!$reimbursement->status) {
                return redirect()->back()->with('error', 'Hanya reimbursement approved yang dapat didownload!');
            }

            $balance = DB::table('balance_reimbursements')
                ->where('karyawan_id', $reimbursement->karyawan_id)
                ->where('year', $reimbursement->year_budget)->first();

            $totalAmount = $reimbursement->childs->sum(fn($c) =>
                ($c->tagihan_dokter ?? 0) + ($c->tagihan_obat ?? 0) +
                ($c->tagihan_kacamata ?? 0) + ($c->tagihan_gigi ?? 0)
            );

            if ($balance) {
                $balance = (object)[
                    'year'         => $balance->year,
                    'budget_claim' => $balance->budget_claim,
                    'total_used'   => ($balance->total_used ?? 0) - $totalAmount,
                    'sisa_budget'  => $balance->sisa_budget,
                ];
            }

            $companyData = null;
            if ($reimbursement->company) {
                $companyData = (object)[
                    'company_name' => $reimbursement->company->company_name,
                    'logo'         => $this->getImageBase64($reimbursement->company->logo, 'logos'),
                    'ttd'          => $this->getImageBase64($reimbursement->company->ttd, 'ttd'),
                    'nama_ttd'     => $reimbursement->company->nama_ttd,
                    'jabatan_ttd'  => $reimbursement->company->jabatan_ttd,
                ];
            }

            $data = [
                'reimbursement' => $reimbursement,
                'karyawan'      => $reimbursement->karyawan,
                'company'       => $companyData,
                'approver'      => $reimbursement->approver,
                'preparedBy'    => $reimbursement->userBy,
                'balance'       => $balance,
                'totalAmount'   => $totalAmount,
                'childs'        => $reimbursement->childs,
                'printDate'     => Carbon::now()->format('d F Y H:i'),
            ];

            $pdf = Pdf::loadView('dashboard.dashboard-management.reimbursements.pdf', $data);
            $pdf->setPaper('a4', 'portrait');

            $filename = 'Reimbursement_' . $reimbursement->id_recapan . '_' . $reimbursement->karyawan->nama_lengkap . '.pdf';
            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Medical Reimbursement PDF Error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Gagal generate PDF: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // GET BALANCE BY YEAR
    // =========================================================================
    public function getBalanceByYear(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'karyawan_id' => 'required|exists:karyawans,absen_karyawan_id',
            'year'        => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $balance = DB::table('balance_reimbursements')
            ->where('karyawan_id', $request->karyawan_id)
            ->where('year', $request->year)->first();

        if (!$balance) {
            return response()->json(['success' => false, 'message' => 'Data balance tidak ditemukan untuk tahun ini'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'year'         => $balance->year,
                'sisa_budget'  => $balance->sisa_budget  ?? 0,
                'total_budget' => $balance->budget_claim ?? 0,
                'terpakai'     => $balance->total_used   ?? 0,
            ],
        ]);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================
    private function getAvailableYears($karyawanId)
    {
        return DB::table('balance_reimbursements')
            ->where('karyawan_id', $karyawanId)
            ->where('sisa_budget', '>', 0)
            ->select('year', 'sisa_budget', 'budget_claim as total_budget')
            ->orderBy('year', 'desc')
            ->get();
    }

    private function getImageBase64($filename, $folder = 'logos')
    {
        if (empty($filename)) return null;

        try {
            $folderMap  = ['logo' => 'logos', 'logos' => 'logos', 'ttd' => 'ttds', 'ttds' => 'ttds'];
            $folderPath = $folderMap[$folder] ?? $folder;
            $url        = str_starts_with($filename, 'http')
                ? $filename
                : "https://haadhir.id/storage/{$folderPath}/" . basename($filename);

            $context      = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
            $imageContent = @file_get_contents($url, false, $context);

            if ($imageContent === false) return null;

            $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($imageContent);
            return "data:{$mimeType};base64," . base64_encode($imageContent);

        } catch (\Exception $e) {
            Log::error("Image conversion error", ['error' => $e->getMessage()]);
            return null;
        }
    }
}