<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reimbursement;
use App\Models\ReimbursementChild;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class ReimbursementApiController extends Controller
{
    /**
     * Helper: hitung total dari 4 kolom tagihan
     */
    private function calcTotal($childs)
    {
        return $childs->sum(fn($c) =>
            ($c->tagihan_dokter   ?? 0) +
            ($c->tagihan_obat     ?? 0) +
            ($c->tagihan_kacamata ?? 0) +
            ($c->tagihan_gigi     ?? 0)
        );
    }

    /**
     * API 1: Get Pending Reimbursements (status = 0) with pagination
     * Endpoint: GET /api/reimbursements/pending
     */
    public function getPendingReimbursements(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);

            $query = Reimbursement::with([
                'karyawan:absen_karyawan_id,nama_lengkap,nik',
                'company:absen_company_id,company_name',
                'childs:id,reimbursement_id,tanggal,nama_reimbursement,status_keluarga,jenis_penyakit,tagihan_dokter,tagihan_obat,tagihan_kacamata,tagihan_gigi,note'
            ])
            ->select('id', 'id_recapan', 'karyawan_id', 'company_id', 'year_budget', 'periode_slip', 'approved_id', 'user_by_id', 'approved_at', 'status', 'created_at', 'updated_at')
            ->where('status', false)
            ->orderBy('created_at', 'desc');

            if ($request->filled('karyawan_id')) {
                $query->where('karyawan_id', $request->karyawan_id);
            }

            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }

            if ($request->filled('periode_slip')) {
                $query->where('periode_slip', $request->periode_slip);
            }

            if ($request->filled('year_budget')) {
                $query->where('year_budget', $request->year_budget);
            }

            $reimbursements = $query->paginate($perPage);

            // Tambah total_amount ke tiap reimbursement
            $reimbursements->getCollection()->transform(function ($reimbursement) {
                $reimbursement->total_amount = $this->calcTotal($reimbursement->childs);
                $reimbursement->formatted_total = 'Rp ' . number_format($reimbursement->total_amount, 0, ',', '.');
                return $reimbursement;
            });

            return response()->json([
                'success' => true,
                'message' => 'Data reimbursement pending berhasil diambil',
                'data' => $reimbursements->items(),
                'pagination' => [
                    'current_page' => $reimbursements->currentPage(),
                    'per_page'     => $reimbursements->perPage(),
                    'total'        => $reimbursements->total(),
                    'last_page'    => $reimbursements->lastPage(),
                    'from'         => $reimbursements->firstItem(),
                    'to'           => $reimbursements->lastItem(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data reimbursement',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API 2: Get Detail Reimbursement by ID
     * Endpoint: GET /api/reimbursements/{id}
     */
    public function getReimbursementDetail($id)
    {
        try {
            $reimbursement = Reimbursement::with([
                'karyawan:absen_karyawan_id,nama_lengkap,nik,email_pribadi,telp_pribadi',
                'company:absen_company_id,company_name',
                'approver:absen_karyawan_id,nama_lengkap',
                'childs:id,reimbursement_id,tanggal,nama_reimbursement,status_keluarga,jenis_penyakit,tagihan_dokter,tagihan_obat,tagihan_kacamata,tagihan_gigi,note'
            ])->findOrFail($id);

            $totalAmount = $this->calcTotal($reimbursement->childs);

            // Format childs dengan subtotal per item
            $childsFormatted = $reimbursement->childs->map(function ($child) {
                $subtotal = ($child->tagihan_dokter   ?? 0)
                          + ($child->tagihan_obat     ?? 0)
                          + ($child->tagihan_kacamata ?? 0)
                          + ($child->tagihan_gigi     ?? 0);

                return [
                    'id'                  => $child->id,
                    'reimbursement_id'    => $child->reimbursement_id,
                    'tanggal'             => $child->tanggal,
                    'nama_reimbursement'  => $child->nama_reimbursement,
                    'status_keluarga'     => $child->status_keluarga,
                    'jenis_penyakit'      => $child->jenis_penyakit,
                    'tagihan_dokter'      => $child->tagihan_dokter   ?? 0,
                    'tagihan_obat'        => $child->tagihan_obat     ?? 0,
                    'tagihan_kacamata'    => $child->tagihan_kacamata ?? 0,
                    'tagihan_gigi'        => $child->tagihan_gigi     ?? 0,
                    'note'                => $child->note,
                    'subtotal'            => $subtotal,
                    'formatted_subtotal'  => 'Rp ' . number_format($subtotal, 0, ',', '.'),
                ];
            })->values();

            // Balance info
            $balanceInfo = DB::table('balance_reimbursements')
                ->where('karyawan_id', $reimbursement->karyawan_id)
                ->where('year', $reimbursement->year_budget)
                ->first();

            $balance = null;
            if ($balanceInfo) {
                $balance = [
                    'year'                    => (int) $balanceInfo->year,
                    'budget_claim'            => (int) $balanceInfo->budget_claim,
                    'total_used'              => (int) $balanceInfo->total_used,
                    'sisa_budget'             => (int) $balanceInfo->sisa_budget,
                    'formatted_budget_claim'  => 'Rp ' . number_format($balanceInfo->budget_claim, 0, ',', '.'),
                    'formatted_total_used'    => 'Rp ' . number_format($balanceInfo->total_used,   0, ',', '.'),
                    'formatted_sisa_budget'   => 'Rp ' . number_format($balanceInfo->sisa_budget,  0, ',', '.'),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail reimbursement berhasil diambil',
                'data'    => [
                    'header' => [
                        'id'              => $reimbursement->id,
                        'id_recapan'      => $reimbursement->id_recapan,
                        'karyawan'        => $reimbursement->karyawan,
                        'company'         => $reimbursement->company,
                        'year_budget'     => $reimbursement->year_budget,
                        'periode_slip'    => $reimbursement->periode_slip,
                        'approver'        => $reimbursement->approver,
                        'approved_at'     => $reimbursement->approved_at,
                        'status'          => $reimbursement->status,
                        'total_amount'    => $totalAmount,
                        'formatted_total' => 'Rp ' . number_format($totalAmount, 0, ',', '.'),
                        'created_at'      => $reimbursement->created_at,
                        'updated_at'      => $reimbursement->updated_at,
                    ],
                    'childs'  => $childsFormatted,
                    'balance' => $balance,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reimbursement tidak ditemukan',
                'error'   => $e->getMessage()
            ], 404);
        }
    }

    /**
     * API 3: Approve Reimbursement
     * Endpoint: PUT /api/reimbursements/{id}/approve
     */
    public function approveReimbursement(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'approved_id' => 'nullable|integer|exists:karyawans,absen_karyawan_id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $reimbursement = Reimbursement::findOrFail($id);

            if ($reimbursement->status == true) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reimbursement sudah di-approve sebelumnya'
                ], 400);
            }

            $reimbursement->status      = true;
            $reimbursement->approved_at = now();

            if ($request->filled('approved_id')) {
                $reimbursement->approved_id = $request->approved_id;
            }

            $reimbursement->save();

            DB::commit();

            // Load untuk response
            $reimbursement->load([
                'karyawan:absen_karyawan_id,nama_lengkap,nik',
                'approver:absen_karyawan_id,nama_lengkap',
                'childs:id,reimbursement_id,tagihan_dokter,tagihan_obat,tagihan_kacamata,tagihan_gigi'
            ]);

            $totalAmount = $this->calcTotal($reimbursement->childs);

            return response()->json([
                'success' => true,
                'message' => 'Reimbursement berhasil di-approve',
                'data'    => [
                    'id'              => $reimbursement->id,
                    'id_recapan'      => $reimbursement->id_recapan,
                    'karyawan'        => $reimbursement->karyawan,
                    'approver'        => $reimbursement->approver,
                    'status'          => $reimbursement->status,
                    'approved_at'     => $reimbursement->approved_at,
                    'total_amount'    => $totalAmount,
                    'formatted_total' => 'Rp ' . number_format($totalAmount, 0, ',', '.'),
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal approve reimbursement',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API 4: Get Summary/Statistics
     * Endpoint: GET /api/reimbursements/summary
     */
    public function getSummary(Request $request)
    {
        try {
            $query = Reimbursement::query();

            if ($request->filled('karyawan_id')) {
                $query->where('karyawan_id', $request->karyawan_id);
            }

            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }

            if ($request->filled('year_budget')) {
                $query->where('year_budget', $request->year_budget);
            }

            if ($request->filled('periode_slip')) {
                $query->where('periode_slip', $request->periode_slip);
            }

            $totalPending  = (clone $query)->where('status', false)->count();
            $totalApproved = (clone $query)->where('status', true)->count();

            // Ambil kolom tagihan saja untuk efisiensi
            $childsSelect = 'id,reimbursement_id,tagihan_dokter,tagihan_obat,tagihan_kacamata,tagihan_gigi';

            $totalAmountPending = (clone $query)
                ->where('status', false)
                ->with(['childs' => fn($q) => $q->select(explode(',', $childsSelect))])
                ->get()
                ->sum(fn($r) => $this->calcTotal($r->childs));

            $totalAmountApproved = (clone $query)
                ->where('status', true)
                ->with(['childs' => fn($q) => $q->select(explode(',', $childsSelect))])
                ->get()
                ->sum(fn($r) => $this->calcTotal($r->childs));

            return response()->json([
                'success' => true,
                'message' => 'Summary reimbursement berhasil diambil',
                'data'    => [
                    'pending' => [
                        'count'           => $totalPending,
                        'total_amount'    => $totalAmountPending,
                        'formatted_total' => 'Rp ' . number_format($totalAmountPending, 0, ',', '.'),
                    ],
                    'approved' => [
                        'count'           => $totalApproved,
                        'total_amount'    => $totalAmountApproved,
                        'formatted_total' => 'Rp ' . number_format($totalAmountApproved, 0, ',', '.'),
                    ],
                    'total' => [
                        'count'           => $totalPending + $totalApproved,
                        'total_amount'    => $totalAmountPending + $totalAmountApproved,
                        'formatted_total' => 'Rp ' . number_format($totalAmountPending + $totalAmountApproved, 0, ',', '.'),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil summary',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}