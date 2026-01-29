<?php
// app/Http/Controllers/PayrollFakeImportController.php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Karyawan;
use App\Models\PayrollsFake;
use Illuminate\Http\Request;
use App\Imports\PayrollFakeImport;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;
use App\Exports\PayrollFakeErrorsExport;
use App\Exports\PayrollFakeTemplateExport;

class PayrollFakeImportController extends Controller
{
    public function index()
    {
        return view('dashboard.dashboard-admin.payrolls-fake.import');
        
    }
    
    /**
     * ✅ Download Template
     */
    public function downloadTemplate()
    {
        try {
            set_time_limit(300);
            
            Log::info('Downloading payroll fake template');
            
            // ONLY ACTIVE karyawan
            $karyawans = Karyawan::where('status_resign', false)
                ->select([
                    'absen_karyawan_id',
                    'nik',
                    'nama_lengkap',
                    'email_pribadi',
                    'telp_pribadi',
                    'join_date'
                ])
                ->orderBy('nama_lengkap')
                ->limit(5000)
                ->get()
                ->toArray();
            
            $companies = Company::select([
                    'absen_company_id',
                    'code',
                    'company_name'
                ])
                ->orderBy('company_name')
                ->limit(5000)
                ->get()
                ->toArray();
            
            Log::info('Template data loaded', [
                'karyawans_count' => count($karyawans),
                'companies_count' => count($companies)
            ]);
            
            $filename = 'payroll_fake_import_template_' . date('Ymd_His') . '.xlsx';
            
            return Excel::download(
                new PayrollFakeTemplateExport($karyawans, $companies),
                $filename
            );
            
        } catch (\Exception $e) {
            Log::error('Error downloading template', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal download template: ' . $e->getMessage());
        }
    }
    
    /**
     * ✅ Validate Excel
     */
    public function validateExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ]);
        
        try {
            set_time_limit(300);
            ini_set('memory_limit', '512M');
            
            Log::info('Starting validation', [
                'filename' => $request->file('file')->getClientOriginalName()
            ]);
            
            $startTime = microtime(true);
            
            $import = new PayrollFakeImport();
            Excel::import($import, $request->file('file'));
            
            $errors = $import->getValidationErrors();
            $validRows = $import->getValidRows();
            $processedRows = $import->getProcessedRows();
            
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            
            Log::info('Validation completed', [
                'total_rows' => $processedRows,
                'valid_rows' => count($validRows),
                'error_rows' => count($errors),
                'duration_seconds' => $duration
            ]);
            
            // Store in session
            Session::put('payroll_fake_import_valid_rows', $validRows);
            Session::put('payroll_fake_import_errors', $errors);
            
            Log::info('Stored in session', [
                'valid_rows' => count($validRows),
                'errors' => count($errors)
            ]);
            
            return response()->json([
                'success' => true,
                'summary' => [
                    'total_rows' => $processedRows,
                    'valid_rows' => count($validRows),
                    'error_rows' => count($errors),
                    'duration' => "{$duration}s"
                ],
                'errors' => $errors,
                'preview' => $validRows
            ]);
            
        } catch (\Exception $e) {
            Log::error('Validation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error validasi file: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function validDataTable(Request $request)
{
    try {
        $validRows = Session::get('payroll_fake_import_valid_rows', []);

        if (empty($validRows)) {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
        }

        $collection = collect($validRows);

        // Search
        $search = $request->input('search.value');
        if (!empty($search)) {
            $collection = $collection->filter(function ($row) use ($search) {
                return stripos($row['periode'], $search) !== false ||
                    stripos($row['karyawan_nama'] ?? '', $search) !== false ||
                    stripos($row['karyawan_nik'] ?? '', $search) !== false ||
                    stripos($row['company_name'] ?? '', $search) !== false ||
                    stripos((string)($row['absen_karyawan_id'] ?? ''), $search) !== false ||
                    stripos((string)($row['absen_company_id'] ?? ''), $search) !== false;
            });
        }

        $recordsFiltered = $collection->count();
        $recordsTotal = count($validRows);

        // Ordering
        $orderColumnIndex = $request->input('order.0.column');
        $orderDir = $request->input('order.0.dir', 'asc');

        $columns = ['periode', 'karyawan_nama', 'company_name', 'gaji_pokok', 'total_penerimaan'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'periode';

        $collection = $collection->sortBy($orderColumn, SORT_REGULAR, $orderDir === 'desc');

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        $data = $collection->slice($start, $length)->values();

        // Calculate totals
        $formattedData = $data->map(function ($row) {

            $salaryType = $row['salary_type'] ?? 'gross';

            // =========================
            // TUNJANGAN (NETT ONLY)
            // =========================
            $tunjangan = ($salaryType === 'nett')
                ? ($row['pph_21'] ?? 0)
                : 0;

            // =========================
            // PPH21 (DISPLAY POSITIF)
            // =========================
            $pph21DeductionDisplay = ($salaryType === 'nett')
                ? ($row['pph_21'] ?? 0)
                : ($row['pph_21_deduction'] ?? 0);

            // =========================
            // BPJS PERUSAHAAN (INCOME)
            // =========================
            $bpjsTkPerusahaanIncome =
                ($row['bpjs_tk_jht_3_7_percent'] ?? 0) +
                ($row['bpjs_tk_jkk_0_24_percent'] ?? 0) +
                ($row['bpjs_tk_jkm_0_3_percent'] ?? 0) +
                ($row['bpjs_tk_jp_2_percent'] ?? 0);

            $bpjsKesPerusahaanIncome =
                ($row['bpjs_kes_4_percent'] ?? 0);

            // =========================
            // BPJS PEGAWAI (INCOME NETT ONLY)
            // NOTE: bpjs_tenaga_kerja & bpjs_kesehatan di tabel SUDAH MINUS -> ikut apa adanya
            // =========================
            $bpjsTkPegawaiIncome = ($salaryType === 'nett')
                ? (
                    ($row['bpjs_tk_jht_2_percent'] ?? 0) +
                    ($row['bpjs_tk_jp_1_percent'] ?? 0) +
                    ($row['bpjs_tenaga_kerja'] ?? 0)
                )
                : 0;

            $bpjsKesPegawaiIncome = ($salaryType === 'nett')
                ? (
                    ($row['bpjs_kes_1_percent'] ?? 0) +
                    ($row['bpjs_kesehatan'] ?? 0)
                )
                : 0;

            // =========================
            // TOTAL PENERIMAAN (FINAL)
            // =========================
            $totalPenerimaan =
                ($row['gaji_pokok'] ?? 0) +
                ($row['monthly_kpi'] ?? 0) +
                ($row['overtime'] ?? 0) +
                ($row['medical_reimbursement'] ?? 0) +
                $bpjsTkPerusahaanIncome +
                $bpjsKesPerusahaanIncome +
                $bpjsTkPegawaiIncome +
                $bpjsKesPegawaiIncome +
                ($row['insentif_sholat'] ?? 0) +
                ($row['monthly_bonus'] ?? 0) +
                ($row['rapel'] ?? 0) +
                ($row['tunjangan_pulsa'] ?? 0) +
                ($row['tunjangan_kehadiran'] ?? 0) +
                ($row['tunjangan_transport'] ?? 0) +
                ($row['tunjangan_lainnya'] ?? 0) +
                ($row['yearly_bonus'] ?? 0) +
                ($row['thr'] ?? 0) +
                ($row['other'] ?? 0) +
                $tunjangan;

            // =========================
            // PPH UNTUK POTONGAN (NEGATIF)
            // =========================
            $pphForPotongan = ($salaryType === 'nett')
                ? -($row['pph_21'] ?? 0)
                : -($row['pph_21_deduction'] ?? 0);

            /**
             * =========================
             * BPJS DEDUCTION (NEGATIF)
             * - perusahaan: minus komponen perusahaan
             * - pegawai: TETAP kepotong untuk gross & nett, tapi PAKAI PERSEN DOANG
             *   (JANGAN include bpjs_tenaga_kerja / bpjs_kesehatan, karena kolom itu SUDAH MINUS)
             * =========================
             */
            $bpjsTkPerusahaanDeduction = -$bpjsTkPerusahaanIncome;
            $bpjsKesPerusahaanDeduction = -($row['bpjs_kes_4_percent'] ?? 0);

            $bpjsTkPegawaiDeduction = -(
                ($row['bpjs_tk_jht_2_percent'] ?? 0) +
                ($row['bpjs_tk_jp_1_percent'] ?? 0)
            );

            $bpjsKesPegawaiDeduction = -($row['bpjs_kes_1_percent'] ?? 0);

            // =========================
            // TOTAL POTONGAN (FINAL)
            // =========================
            $totalPotongan =
                ($row['ca_corporate'] ?? 0) +
                ($row['ca_personal'] ?? 0) +
                ($row['ca_kehadiran'] ?? 0) +
                $pphForPotongan +
                $bpjsTkPerusahaanDeduction +
                $bpjsTkPegawaiDeduction +
                $bpjsKesPerusahaanDeduction +
                $bpjsKesPegawaiDeduction;

            // =========================
            // GAJI BERSIH
            // =========================
            $gajiBersih = $totalPenerimaan + $totalPotongan;

            return array_merge($row, [
                // tampil
                'tunjangan' => $tunjangan,
                'pph_21_deduction' => $pph21DeductionDisplay,

                // calculated
                'total_penerimaan' => $totalPenerimaan,
                'total_potongan' => $totalPotongan, // negatif
                'gaji_bersih' => $gajiBersih,
            ]);
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $formattedData
        ]);

    } catch (\Exception $e) {
        Log::error('Error in validDataTable', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => $e->getMessage()
        ]);
    }
}


    /**
     * ✅ DataTables untuk Error Data
     */
    public function errorDataTable(Request $request)
    {
        try {
            $errors = Session::get('payroll_fake_import_errors', []);
            
            if (empty($errors)) {
                return response()->json([
                    'draw' => intval($request->input('draw')),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => []
                ]);
            }
            
            $collection = collect($errors);
            
            // Search
            $search = $request->input('search.value');
            if (!empty($search)) {
                $collection = $collection->filter(function($error) use ($search) {
                    return stripos((string)$error['row'], $search) !== false ||
                        stripos($error['data']['periode'] ?? '', $search) !== false ||
                        stripos($error['data']['karyawan_id'] ?? '', $search) !== false ||
                        stripos($error['data']['company_id'] ?? '', $search) !== false ||
                        stripos(implode(' ', $error['errors']), $search) !== false;
                });
            }
            
            $recordsFiltered = $collection->count();
            $recordsTotal = count($errors);
            
            // Ordering
            $orderColumnIndex = $request->input('order.0.column');
            $orderDir = $request->input('order.0.dir', 'asc');
            
            if ($orderColumnIndex == 0) {
                $collection = $collection->sortBy('row', SORT_REGULAR, $orderDir === 'desc');
            }
            
            // Pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            
            $data = $collection->slice($start, $length)->values();
            
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in errorDataTable', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * ✅ Process Import - Insert data ke database
     */
    public function process(Request $request)
    {
        try {
            set_time_limit(300);
            
            Log::info('========== FAKE PAYROLL IMPORT PROCESS START ==========');
            
            $validRows = Session::get('payroll_fake_import_valid_rows');
            
            if (empty($validRows)) {
                Log::error('No valid rows in session');
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data untuk diimport'
                ], 400);
            }
            
            Log::info('Data retrieved from session', [
                'count' => count($validRows)
            ]);
            
            $startTime = microtime(true);
            $imported = 0;
            
            // Chunk insert
            $chunks = array_chunk($validRows, 100);
            
            foreach ($chunks as $chunkIndex => $chunk) {
                foreach ($chunk as $rowIndex => $validData) {
                    try {
                        // Remove display-only fields
                        $data = $validData;
                        unset($data['karyawan_nama']);
                        unset($data['karyawan_nik']);
                        unset($data['company_name']);
                        unset($data['company_code']);
                        unset($data['absen_karyawan_id']);
                        unset($data['absen_company_id']);
                        
                        // INSERT
                        PayrollsFake::create($data);
                        
                        $imported++;
                    } catch (\Exception $e) {
                        Log::error('Error importing row', [
                            'row_index' => $rowIndex,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            Session::forget('payroll_fake_import_valid_rows');
            
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            
            Log::info('========== FAKE PAYROLL IMPORT PROCESS END ==========', [
                'imported_count' => $imported,
                'duration_seconds' => $duration
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Berhasil import {$imported} data payroll fake dalam {$duration}s",
                'imported_count' => $imported,
                'duration' => "{$duration}s"
            ]);
            
        } catch (\Exception $e) {
            Log::error('Import process error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error saat import: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function downloadErrors(Request $request)
    {
        try {
            $errors = $request->input('errors', []);
            
            if (empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada error untuk didownload'
                ], 400);
            }
            
            $filename = 'payroll_fake_import_errors_' . date('Ymd_His') . '.xlsx';
            
            return Excel::download(
                new PayrollFakeErrorsExport($errors),
                $filename
            );
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error download errors: ' . $e->getMessage()
            ], 500);
        }
    }
}