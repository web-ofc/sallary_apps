<?php
// app/Http/Controllers/PayrollImportController.php - FIXED VERSION

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\PayrollImport;
use App\Exports\PayrollTemplateExport;
use App\Models\Karyawan;
use App\Models\Company;
use App\Models\Payroll;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Services\Pph21CalculatorService;

class PayrollImportController extends Controller
{
    protected $pph21Calculator;
    
    public function __construct(Pph21CalculatorService $pph21Calculator)
    {
        $this->pph21Calculator = $pph21Calculator;
    }


    public function index()
    {
        return view('dashboard.dashboard-admin.payrolls.import');
    }
    
    /**
     * ✅ Download Template
     */
    public function downloadTemplate()
    {
        try {
            set_time_limit(300);
            
            Log::info('Downloading payroll template');
            
            // ✅ ONLY ACTIVE karyawan
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
            
            $filename = 'payroll_import_template_' . date('Ymd_His') . '.xlsx';
            
            return Excel::download(
                new PayrollTemplateExport($karyawans, $companies),
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
            
            $import = new PayrollImport();
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
            
            // ✅ CRITICAL: Store BOTH in session
            Session::put('payroll_import_valid_rows', $validRows);
            Session::put('payroll_import_errors', $errors);
            
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
        $validRows = Session::get('payroll_import_valid_rows', []);

        if (empty($validRows)) {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
        }

        // Convert to collection for easier manipulation
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

        // Calculate totals untuk setiap row
        $formattedData = $data->map(function ($row) {

            $salaryType = $row['salary_type'] ?? 'gross';

            // ✅ tunjangan (nett only)
            $tunjangan = ($salaryType === 'nett') ? ($row['pph_21'] ?? 0) : 0;

            // ✅ PPh21 deduction OUTPUT (positif buat tampilan)
            // nett: tampil pph_21, gross: tampil pph_21_deduction asli
            $pph21DeductionDisplay = ($salaryType === 'nett')
                ? ($row['pph_21'] ?? 0)
                : ($row['pph_21_deduction'] ?? 0);

            // ✅ BPJS pegawai income (nett only, gross 0)
            // NOTE: bpjs_tenaga_kerja & bpjs_kesehatan di tabel SUDAH MINUS -> ikut apa adanya
            $bpjsTkPegawaiIncome = ($salaryType === 'nett')
                ? (($row['bpjs_tk_jht_2_percent'] ?? 0) + ($row['bpjs_tk_jp_1_percent'] ?? 0) + ($row['bpjs_tenaga_kerja'] ?? 0))
                : 0;

            $bpjsKesPegawaiIncome = ($salaryType === 'nett')
                ? (($row['bpjs_kes_1_percent'] ?? 0) + ($row['bpjs_kesehatan'] ?? 0))
                : 0;

            // ✅ BPJS perusahaan income (tetap)
            $bpjsTkPerusahaanIncome =
                ($row['bpjs_tk_jht_3_7_percent'] ?? 0) +
                ($row['bpjs_tk_jkk_0_24_percent'] ?? 0) +
                ($row['bpjs_tk_jkm_0_3_percent'] ?? 0) +
                ($row['bpjs_tk_jp_2_percent'] ?? 0);

            $bpjsKesPerusahaanIncome = ($row['bpjs_kes_4_percent'] ?? 0);

            // ✅ total penerimaan: pegawai-income BPJS hanya NETT + tunjangan NETT
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

            // ✅ PPh untuk perhitungan potongan (negatif biar ngurangin THP)
            $pphForPotongan = ($salaryType === 'nett')
                ? -($row['pph_21'] ?? 0)
                : -($row['pph_21_deduction'] ?? 0);

            /**
             * ✅ BPJS deduction (negatif)
             * - perusahaan: minus komponen perusahaan
             * - pegawai: TETAP kepotong untuk gross & nett, tapi PAKAI PERSEN DOANG
             *   (jangan include bpjs_tenaga_kerja / bpjs_kesehatan, karena kolom itu SUDAH MINUS)
             */
            $bpjsTkPerusahaanDeduction = -$bpjsTkPerusahaanIncome;
            $bpjsKesPerusahaanDeduction = -($row['bpjs_kes_4_percent'] ?? 0);

            $bpjsTkPegawaiDeduction = -(
                ($row['bpjs_tk_jht_2_percent'] ?? 0) +
                ($row['bpjs_tk_jp_1_percent'] ?? 0)
            );

            $bpjsKesPegawaiDeduction = -($row['bpjs_kes_1_percent'] ?? 0);

            // ✅ total potongan = negatif semua komponen potong
            $totalPotongan =
                ($row['ca_corporate'] ?? 0) +
                ($row['ca_personal'] ?? 0) +
                ($row['ca_kehadiran'] ?? 0) +
                $pphForPotongan +
                $bpjsTkPerusahaanDeduction +
                $bpjsTkPegawaiDeduction +
                $bpjsKesPerusahaanDeduction +
                $bpjsKesPegawaiDeduction;

            // ✅ THP
            $gajiBersih = $totalPenerimaan + $totalPotongan;

            return [
                'periode' => $row['periode'],
                'karyawan_nama' => $row['karyawan_nama'] ?? '-',
                'karyawan_nik' => $row['karyawan_nik'] ?? '-',
                'absen_karyawan_id' => $row['absen_karyawan_id'] ?? '-',
                'company_name' => $row['company_name'] ?? '-',
                'company_code' => $row['company_code'] ?? '-',
                'absen_company_id' => $row['absen_company_id'] ?? '-',

                'salary_type' => $salaryType,

                // Gaji Pokok + Monthly Income
                'gaji_pokok' => $row['gaji_pokok'] ?? 0,
                'monthly_kpi' => $row['monthly_kpi'] ?? 0,
                'overtime' => $row['overtime'] ?? 0,
                'medical_reimbursement' => $row['medical_reimbursement'] ?? 0,

                // ✅ Tunjangan
                'tunjangan' => $tunjangan,

                // Monthly Insentif
                'insentif_sholat' => $row['insentif_sholat'] ?? 0,
                'monthly_bonus' => $row['monthly_bonus'] ?? 0,
                'rapel' => $row['rapel'] ?? 0,

                // Monthly Allowance
                'tunjangan_pulsa' => $row['tunjangan_pulsa'] ?? 0,
                'tunjangan_kehadiran' => $row['tunjangan_kehadiran'] ?? 0,
                'tunjangan_transport' => $row['tunjangan_transport'] ?? 0,
                'tunjangan_lainnya' => $row['tunjangan_lainnya'] ?? 0,

                // Yearly Benefit
                'yearly_bonus' => $row['yearly_bonus'] ?? 0,
                'thr' => $row['thr'] ?? 0,
                'other' => $row['other'] ?? 0,

                // BPJS raw fields (buat table lu)
                'bpjs_tk_jht_3_7_percent' => $row['bpjs_tk_jht_3_7_percent'] ?? 0,
                'bpjs_tk_jkk_0_24_percent' => $row['bpjs_tk_jkk_0_24_percent'] ?? 0,
                'bpjs_tk_jkm_0_3_percent' => $row['bpjs_tk_jkm_0_3_percent'] ?? 0,
                'bpjs_tk_jp_2_percent' => $row['bpjs_tk_jp_2_percent'] ?? 0,
                'bpjs_tk_jht_2_percent' => $row['bpjs_tk_jht_2_percent'] ?? 0,
                'bpjs_tk_jp_1_percent' => $row['bpjs_tk_jp_1_percent'] ?? 0,
                'bpjs_tenaga_kerja' => $row['bpjs_tenaga_kerja'] ?? 0, // sudah minus dari tabel
                'bpjs_kes_4_percent' => $row['bpjs_kes_4_percent'] ?? 0,
                'bpjs_kes_1_percent' => $row['bpjs_kes_1_percent'] ?? 0,
                'bpjs_kesehatan' => $row['bpjs_kesehatan'] ?? 0,      // sudah minus dari tabel

                // Potongan + PPh
                'ca_corporate' => $row['ca_corporate'] ?? 0,
                'ca_personal' => $row['ca_personal'] ?? 0,
                'ca_kehadiran' => $row['ca_kehadiran'] ?? 0,
                'pph_21' => $row['pph_21'] ?? 0,

                // ✅ tampil pph_21_deduction versi rules (POSITIF)
                'pph_21_deduction' => $pph21DeductionDisplay,

                // lainnya
                'glh' => $row['glh'] ?? 0,
                'lm' => $row['lm'] ?? 0,
                'lainnya' => $row['lainnya'] ?? 0,

                // Calculated
                'total_penerimaan' => $totalPenerimaan,
                'total_potongan' => $totalPotongan, // negatif (biar konsisten)
                'gaji_bersih' => $gajiBersih,
            ];
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
            $errors = Session::get('payroll_import_errors', []);
            
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
            
            if ($orderColumnIndex == 0) { // Row number
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
            
            Log::info('========== IMPORT PROCESS START ==========');
            
            $validRows = Session::get('payroll_import_valid_rows');
            
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
                        // ✅ Remove display-only fields yang tidak ada di database
                        $data = $validData;
                        unset($data['karyawan_nama']);
                        unset($data['karyawan_nik']);
                        unset($data['company_name']);
                        unset($data['company_code']);
                        unset($data['absen_karyawan_id']); // Ini hanya untuk preview
                        unset($data['absen_company_id']); // Ini hanya untuk preview
                        
                        // ✅ INSERT - karyawan_id dan company_id sudah berisi absen_id
                        Payroll::create($data);
                        
                        $imported++;
                    } catch (\Exception $e) {
                        Log::error('Error importing row', [
                            'row_index' => $rowIndex,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            Session::forget('payroll_import_valid_rows');
            
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            
            Log::info('========== IMPORT PROCESS END ==========', [
                'imported_count' => $imported,
                'duration_seconds' => $duration
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Berhasil import {$imported} data payroll dalam {$duration}s",
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
            
            $filename = 'payroll_import_errors_' . date('Ymd_His') . '.xlsx';
            
            return Excel::download(
                new \App\Exports\PayrollErrorsExport($errors),
                $filename
            );
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error download errors: ' . $e->getMessage()
            ], 500);
        }
    }

    // -------------------------------------------------------------- PPH21 PERHITUNGAN --------------------------------------------------------------
    /**
     * Calculate PPh21 untuk data yang baru di-import (masih di session)
     * Dipanggil dari halaman import sebelum data disimpan ke DB
     */
    public function calculatePph21BeforeImport(Request $request)
    {
        try {
            $validRows = Session::get('payroll_import_valid_rows', []);
            
            if (empty($validRows)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data untuk dihitung'
                ], 400);
            }
            
            Log::info('Starting PPh21 calculation for import data', [
                'count' => count($validRows)
            ]);
            
            $results = [
                'success' => 0,
                'failed' => 0,
                'total' => count($validRows),
                'details' => []
            ];
            
            // Calculate untuk setiap row
            foreach ($validRows as $index => &$row) {
                try {
                    // Create temporary Payroll object (not saved to DB)
                    $tempPayroll = new Payroll();
                    foreach ($row as $key => $value) {
                        if ($key !== 'karyawan_nama' && $key !== 'karyawan_nik' && 
                            $key !== 'company_name' && $key !== 'company_code' &&
                            $key !== 'absen_karyawan_id' && $key !== 'absen_company_id') {
                            $tempPayroll->$key = $value;
                        }
                    }
                    
                    // Calculate PPh21
                    $calculation = $this->pph21Calculator->calculateSingle($tempPayroll);
                    
                    if ($calculation['success']) {
                        // ✅ Update row dengan PPh21 DAN pph21_deduction
                        $row['pph_21'] = $calculation['pph21'];
                        $row['pph_21_deduction'] = $calculation['pph21_deduction'];
                        $row['_calculation_details'] = $calculation['details'];
                        
                        $results['success']++;
                        $results['details'][] = [
                            'row_index' => $index,
                            'karyawan_id' => $row['karyawan_id'],
                            'periode' => $row['periode'],
                            'salary_type' => $row['salary_type'],
                            'status' => 'success',
                            'pph21' => $calculation['pph21'],
                            'pph21_deduction' => $calculation['pph21_deduction']
                        ];
                    } else {
                        $results['failed']++;
                        $results['details'][] = [
                            'row_index' => $index,
                            'karyawan_id' => $row['karyawan_id'],
                            'periode' => $row['periode'],
                            'status' => 'failed',
                            'message' => $calculation['message']
                        ];
                    }
                    
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['details'][] = [
                        'row_index' => $index,
                        'karyawan_id' => $row['karyawan_id'] ?? '-',
                        'periode' => $row['periode'] ?? '-',
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                }
            }
            
            // ✅ Update session dengan data yang sudah ada PPh21 DAN pph21_deduction
            Session::put('payroll_import_valid_rows', $validRows);
            
            Log::info('PPh21 calculation completed', [
                'success' => $results['success'],
                'failed' => $results['failed']
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Berhasil menghitung PPh21 untuk {$results['success']} dari {$results['total']} data",
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error calculating PPh21 before import', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Calculate PPh21 untuk data yang sudah ada di database
     * Dipanggil dari halaman payroll index
     */
    public function calculatePph21Batch(Request $request)
    {
        try {
            $request->validate([
                'payroll_ids' => 'required|array',
                'payroll_ids.*' => 'integer|exists:payrolls,id'
            ]);
            
            Log::info('Starting batch PPh21 calculation', [
                'payroll_ids' => $request->payroll_ids
            ]);
            
            $results = $this->pph21Calculator->calculateBatch($request->payroll_ids);
            
            Log::info('Batch PPh21 calculation completed', [
                'success' => $results['success'],
                'failed' => $results['failed'],
                'skipped' => $results['skipped']
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Berhasil: {$results['success']}, Gagal: {$results['failed']}, Dilewati: {$results['skipped']}",
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error batch calculating PPh21', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Calculate PPh21 berdasarkan periode
     */
    public function calculatePph21ByPeriode(Request $request)
    {
        try {
            $request->validate([
                'periode' => 'required|string|regex:/^\d{4}-(0[1-9]|1[0-2])$/'
            ]);
            
            Log::info('Starting PPh21 calculation by periode', [
                'periode' => $request->periode
            ]);
            
            $results = $this->pph21Calculator->calculateByPeriode($request->periode);
            
            Log::info('PPh21 calculation by periode completed', [
                'periode' => $request->periode,
                'success' => $results['success'],
                'failed' => $results['failed']
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Berhasil menghitung PPh21 untuk periode {$request->periode}",
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error calculating PPh21 by periode', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Recalculate PPh21 (force update)
     */
    public function recalculatePph21(Request $request)
    {
        try {
            $request->validate([
                'payroll_ids' => 'required|array',
                'payroll_ids.*' => 'integer|exists:payrolls,id'
            ]);
            
            Log::info('Starting PPh21 recalculation', [
                'payroll_ids' => $request->payroll_ids
            ]);
            
            $results = $this->pph21Calculator->recalculateBatch($request->payroll_ids);
            
            Log::info('PPh21 recalculation completed', [
                'success' => $results['success'],
                'failed' => $results['failed']
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Berhasil recalculate PPh21 untuk {$results['success']} data",
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error recalculating PPh21', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // --------------------------------------------------------------------PPH 21 TAHUNAN PERHITUNGAN -----------------------------------------


    /**
     * ✅ Process Import untuk PPh21 Tahunan (is_last_period = 1)
     * Insert ke database langsung, lalu redirect ke page calculate annual
     */
    // PayrollImportController.php

    public function processAnnual(Request $request)
{
    try {
        set_time_limit(300);
        
        Log::info('========== IMPORT ANNUAL PROCESS START ==========');
        
        $validRows = Session::get('payroll_import_valid_rows');
        
        if (empty($validRows)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data untuk diimport'
            ], 400);
        }
        
        // ✅ VALIDASI PTKP DAN BRACKET SEKALIGUS
        $ptkpValidation = $this->validatePtkpForAnnual($validRows);
        $bracketValidation = $this->validateBracketForAnnual($validRows);
        
        // ✅ CEK APAKAH ADA ERROR (PTKP, BRACKET, ATAU KEDUANYA)
        $hasPtkpErrors = $ptkpValidation['has_errors'];
        $hasBracketErrors = $bracketValidation['has_errors'];
        
        if ($hasPtkpErrors || $hasBracketErrors) {
            $response = [
                'success' => false,
                'message' => 'Validasi gagal',
                'has_ptkp_errors' => $hasPtkpErrors,
                'has_bracket_errors' => $hasBracketErrors,
            ];
            
            // Tambahkan data PTKP errors jika ada
            if ($hasPtkpErrors) {
                $response['ptkp_errors'] = $ptkpValidation['errors'];
                $response['ptkp_summary'] = [
                    'total' => count($validRows),
                    'valid' => $ptkpValidation['valid_count'],
                    'invalid_ptkp' => $ptkpValidation['error_count']
                ];
            }
            
            // Tambahkan data Bracket errors jika ada
            if ($hasBracketErrors) {
                $response['bracket_errors'] = $bracketValidation['errors'];
                $response['bracket_summary'] = [
                    'total' => count($validRows),
                    'years_checked' => $bracketValidation['years_checked'],
                    'years_missing_bracket' => $bracketValidation['years_missing']
                ];
            }
            
            return response()->json($response, 422);
        }
        
        Log::info('Data retrieved from session for annual import', [
            'count' => count($validRows)
        ]);
        
        $startTime = microtime(true);
        $imported = 0;
        
        // Chunk insert
        $chunks = array_chunk($validRows, 100);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            foreach ($chunk as $rowIndex => $validData) {
                try {
                    // ✅ Remove display-only fields
                    $data = $validData;
                    unset($data['karyawan_nama']);
                    unset($data['karyawan_nik']);
                    unset($data['company_name']);
                    unset($data['company_code']);
                    unset($data['absen_karyawan_id']);
                    unset($data['absen_company_id']);
                    
                    // 🔥 SET is_last_period = 1 untuk annual calculation
                    $data['is_last_period'] = 1;
                    
                    // ✅ INSERT
                    Payroll::create($data);
                    
                    $imported++;
                } catch (\Exception $e) {
                    Log::error('Error importing annual row', [
                        'row_index' => $rowIndex,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        // ✅ Clear session
        Session::forget('payroll_import_valid_rows');
        Session::forget('payroll_import_errors');
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        Log::info('========== IMPORT ANNUAL PROCESS END ==========', [
            'imported_count' => $imported,
            'duration_seconds' => $duration
        ]);
        
        return response()->json([
            'success' => true,
            'message' => "Berhasil import {$imported} data untuk perhitungan tahunan dalam {$duration}s",
            'imported_count' => $imported,
            'duration' => "{$duration}s"
        ]);
        
    } catch (\Exception $e) {
        Log::error('Import annual process error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Error saat import: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * ✅ Validasi PTKP untuk semua rows sebelum import annual
     */
    private function validatePtkpForAnnual(array $validRows)
    {
        $errors = [];
        $validCount = 0;
        
        foreach ($validRows as $index => $row) {
            $karyawanId = $row['karyawan_id'];
            $periode = $row['periode'];
            $year = (int) substr($periode, 0, 4);
            
            $ptkpHistory = \App\Models\KaryawanPtkpHistory::where('absen_karyawan_id', $karyawanId)
                ->where('tahun', $year)
                ->first();
            
            if (!$ptkpHistory) {
                $errors[] = [
                    'row_index' => $index + 2,
                    'karyawan_id' => $karyawanId,
                    'karyawan_nama' => $row['karyawan_nama'] ?? '-',
                    'periode' => $periode,
                    'year' => $year,
                    'message' => "PTKP history tidak ditemukan untuk tahun {$year}"
                ];
            } else {
                $ptkp = \App\Models\ListPtkp::where('absen_ptkp_id', $ptkpHistory->absen_ptkp_id)->first();
                
                if (!$ptkp) {
                    $errors[] = [
                        'row_index' => $index + 2,
                        'karyawan_id' => $karyawanId,
                        'karyawan_nama' => $row['karyawan_nama'] ?? '-',
                        'periode' => $periode,
                        'year' => $year,
                        'message' => "Data PTKP tidak valid"
                    ];
                } else {
                    $validCount++;
                }
            }
        }
        
        return [
            'has_errors' => count($errors) > 0,
            'errors' => $errors,
            'error_count' => count($errors),
            'valid_count' => $validCount,
            'total' => count($validRows)
        ];
    }

    /**
     * ✅ BARU: Validasi Bracket untuk semua tahun yang ada di import
     */
    private function validateBracketForAnnual(array $validRows)
    {
        // Kumpulkan semua tahun yang unik dari data import
        $years = [];
        foreach ($validRows as $row) {
            $year = (int) substr($row['periode'], 0, 4);
            $years[$year] = true;
        }
        $uniqueYears = array_keys($years);
        
        $errors = [];
        $yearsMissingBracket = [];
        
        foreach ($uniqueYears as $year) {
            // Cek bracket yang aktif di akhir tahun
            $endOfYear = \Carbon\Carbon::create($year, 12, 31);
            
            $activeBrackets = \App\Models\Pph21TaxBracket::where('effective_start_date', '<=', $endOfYear)
                ->where(function ($q) use ($endOfYear) {
                    $q->whereNull('effective_end_date')
                    ->orWhere('effective_end_date', '>=', $endOfYear);
                })
                ->orderBy('order_index')
                ->get();
            
            if ($activeBrackets->isEmpty()) {
                $yearsMissingBracket[] = $year;
                
                $errors[] = [
                    'year' => $year,
                    'date_checked' => $endOfYear->format('Y-m-d'),
                    'message' => "Tidak ada bracket PPh21 yang aktif untuk tahun {$year} (per 31 Desember {$year})"
                ];
            } else {
                // Optional: Validasi bahwa bracket lengkap (minimal 1 bracket)
                // Bisa ditambahkan validasi lebih detail jika perlu
                Log::info("Bracket validation passed for year {$year}", [
                    'brackets_count' => $activeBrackets->count(),
                    'date_checked' => $endOfYear->format('Y-m-d')
                ]);
            }
        }
        
        return [
            'has_errors' => count($errors) > 0,
            'errors' => $errors,
            'years_checked' => $uniqueYears,
            'years_missing' => $yearsMissingBracket,
            'total_years' => count($uniqueYears)
        ];
    }
}