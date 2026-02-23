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
     * API 1: Get Pending Reimbursements (status = 0) with pagination
     * Endpoint: GET /api/reimbursements/pending
     * 
     * Query params:
     * - page: int (default 1)
     * - per_page: int (default 15)
     * - karyawan_id: int (optional filter by absen_karyawan_id)
     * - company_id: int (optional filter by absen_company_id)
     * - periode_slip: string (optional, format: '2025-01')
     * - year_budget: int (optional)
     */
    public function getPendingReimbursements(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            
            // Query dengan eager loading untuk optimasi
            $query = Reimbursement::with([
                'karyawan:absen_karyawan_id,nama_lengkap,nik',
                'company:absen_company_id,company_name',
                'childs' => function($q) {
                    $q->select('id', 'reimbursement_id', 'reimbursement_type_id', 'harga', 'jenis_penyakit', 'status_keluarga', 'note')
                      ->with('reimbursementType:id,code,medical_type,group_medical');
                }
            ])
            ->select('id', 'id_recapan', 'karyawan_id', 'company_id', 'year_budget', 'periode_slip', 'approved_id', 'user_by_id', 'approved_at', 'status', 'created_at', 'updated_at')
            ->where('status', false) // Status 0 (pending)
            ->orderBy('created_at', 'desc');

            // Optional filters
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

            // Paginate
            $reimbursements = $query->paginate($perPage);

            // Add total_amount to each reimbursement
            $reimbursements->getCollection()->transform(function ($reimbursement) {
                $reimbursement->total_amount = $reimbursement->childs->sum('harga');
                return $reimbursement;
            });

            return response()->json([
                'success' => true,
                'message' => 'Data reimbursement pending berhasil diambil',
                'data' => $reimbursements->items(),
                'pagination' => [
                    'current_page' => $reimbursements->currentPage(),
                    'per_page' => $reimbursements->perPage(),
                    'total' => $reimbursements->total(),
                    'last_page' => $reimbursements->lastPage(),
                    'from' => $reimbursements->firstItem(),
                    'to' => $reimbursements->lastItem(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data reimbursement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API 2: Get Detail Reimbursement by ID (for showing detail with eye icon)
     * Endpoint: GET /api/reimbursements/{id}
     * 
     * ✅ INCLUDE BALANCE INFORMATION dari view berdasarkan year_budget yang dipakai
     */
    public function getReimbursementDetail($id)
    {
        try {
            $reimbursement = Reimbursement::with([
                'karyawan:absen_karyawan_id,nama_lengkap,nik,email_pribadi,telp_pribadi',
                'company:absen_company_id,company_name',
                'approver:absen_karyawan_id,nama_lengkap',
                'childs.reimbursementType'
            ])
            ->findOrFail($id);

            // Calculate total amount
            $totalAmount = $reimbursement->childs->sum('harga');

            // Group childs by group_medical
            $generalChilds = $reimbursement->childs->filter(function($child) {
                return $child->reimbursementType->group_medical === 'general';
            })->values();

            $otherChilds = $reimbursement->childs->filter(function($child) {
                return $child->reimbursementType->group_medical === 'other';
            })->values();

            // ✅ GET BALANCE INFORMATION dari view berdasarkan year_budget yang dipakai
            $balanceInfo = DB::table('balance_reimbursements')
                ->where('karyawan_id', $reimbursement->karyawan_id)
                ->where('year', $reimbursement->year_budget)
                ->first();

            // ✅ Format balance data
            $balance = null;
            if ($balanceInfo) {
                $balance = [
                    'year' => (int) $balanceInfo->year,
                    'budget_claim' => (int) $balanceInfo->budget_claim,
                    'total_used' => (int) $balanceInfo->total_used,
                    'sisa_budget' => (int) $balanceInfo->sisa_budget,
                    // Formatted versions
                    'formatted_budget_claim' => 'Rp ' . number_format($balanceInfo->budget_claim, 0, ',', '.'),
                    'formatted_total_used' => 'Rp ' . number_format($balanceInfo->total_used, 0, ',', '.'),
                    'formatted_sisa_budget' => 'Rp ' . number_format($balanceInfo->sisa_budget, 0, ',', '.'),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail reimbursement berhasil diambil',
                'data' => [
                    'header' => [
                        'id' => $reimbursement->id,
                        'id_recapan' => $reimbursement->id_recapan,
                        'karyawan' => $reimbursement->karyawan,
                        'company' => $reimbursement->company,
                        'year_budget' => $reimbursement->year_budget,
                        'periode_slip' => $reimbursement->periode_slip,
                        'approver' => $reimbursement->approver,
                        'approved_at' => $reimbursement->approved_at,
                        'status' => $reimbursement->status,
                        'total_amount' => $totalAmount,
                        'formatted_total' => 'Rp ' . number_format($totalAmount, 0, ',', '.'),
                        'created_at' => $reimbursement->created_at,
                        'updated_at' => $reimbursement->updated_at,
                    ],
                    'childs' => [
                        'general' => $generalChilds,
                        'other' => $otherChilds,
                        'all' => $reimbursement->childs
                    ],
                    // ✅ BALANCE INFO berdasarkan year_budget yang dipakai
                    'balance' => $balance
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reimbursement tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * API 3: Update Reimbursement Status to Approved (status = 1)
     * Endpoint: PUT /api/reimbursements/{id}/approve
     * 
     * Request body:
     * - approved_id: int (ID karyawan yang approve, default dari auth jika tidak dikirim)
     * 
     * NOTE: API ini akan dipanggil dari aplikasi absensi setelah user melihat detail
     */
    public function approveReimbursement(Request $request, $id)
    {
        DB::beginTransaction();
        
        try {
            // Validasi
            $validator = Validator::make($request->all(), [
                'approved_id' => 'nullable|integer|exists:karyawans,absen_karyawan_id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find reimbursement
            $reimbursement = Reimbursement::findOrFail($id);

            // Check if already approved
            if ($reimbursement->status == true) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reimbursement sudah di-approve sebelumnya'
                ], 400);
            }

            // Update status
            $reimbursement->status = true;
            $reimbursement->approved_at = now();
            
            // Update approved_id jika dikirim, kalau tidak pakai yang dari auth (default 6)
            if ($request->filled('approved_id')) {
                $reimbursement->approved_id = $request->approved_id;
            }
            // approved_id sudah ada default value 6 di database

            $reimbursement->save();

            DB::commit();

            // Load relationships untuk response
            $reimbursement->load([
                'karyawan:absen_karyawan_id,nama_lengkap,nik',
                'approver:absen_karyawan_id,nama_lengkap',
                'childs.reimbursementType'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reimbursement berhasil di-approve',
                'data' => [
                    'id' => $reimbursement->id,
                    'id_recapan' => $reimbursement->id_recapan,
                    'karyawan' => $reimbursement->karyawan,
                    'approver' => $reimbursement->approver,
                    'status' => $reimbursement->status,
                    'approved_at' => $reimbursement->approved_at,
                    'total_amount' => $reimbursement->childs->sum('harga'),
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal approve reimbursement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API 4: Get Summary/Statistics for Reimbursements
     * Endpoint: GET /api/reimbursements/summary
     * 
     * Query params:
     * - karyawan_id: int (optional)
     * - company_id: int (optional)
     * - year_budget: int (optional)
     * - periode_slip: string (optional)
     */
    public function getSummary(Request $request)
    {
        try {
            $query = Reimbursement::query();

            // Apply filters
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

            // Count pending and approved
            $totalPending = (clone $query)->where('status', false)->count();
            $totalApproved = (clone $query)->where('status', true)->count();

            // Sum amounts for pending
            $pendingReimbursements = (clone $query)
                ->where('status', false)
                ->with('childs:id,reimbursement_id,harga')
                ->get();
            
            $totalAmountPending = $pendingReimbursements->sum(function($r) {
                return $r->childs->sum('harga');
            });

            // Sum amounts for approved
            $approvedReimbursements = (clone $query)
                ->where('status', true)
                ->with('childs:id,reimbursement_id,harga')
                ->get();
            
            $totalAmountApproved = $approvedReimbursements->sum(function($r) {
                return $r->childs->sum('harga');
            });

            return response()->json([
                'success' => true,
                'message' => 'Summary reimbursement berhasil diambil',
                'data' => [
                    'pending' => [
                        'count' => $totalPending,
                        'total_amount' => $totalAmountPending,
                        'formatted_total' => 'Rp ' . number_format($totalAmountPending, 0, ',', '.')
                    ],
                    'approved' => [
                        'count' => $totalApproved,
                        'total_amount' => $totalAmountApproved,
                        'formatted_total' => 'Rp ' . number_format($totalAmountApproved, 0, ',', '.')
                    ],
                    'total' => [
                        'count' => $totalPending + $totalApproved,
                        'total_amount' => $totalAmountPending + $totalAmountApproved,
                        'formatted_total' => 'Rp ' . number_format($totalAmountPending + $totalAmountApproved, 0, ',', '.')
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
