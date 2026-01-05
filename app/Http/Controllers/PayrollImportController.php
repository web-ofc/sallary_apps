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

class PayrollImportController extends Controller
{
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
            $collection = $collection->filter(function($row) use ($search) {
                $searchLower = strtolower($search);
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
        $formattedData = $data->map(function($row) {
            // Calculate total penerimaan (sama seperti di VIEW)
            $totalPenerimaan = 
                ($row['gaji_pokok'] ?? 0) +
                ($row['monthly_kpi'] ?? 0) +
                ($row['overtime'] ?? 0) +
                ($row['medical_reimbursement'] ?? 0) +
                ($row['bpjs_tk_jht_3_7_percent'] ?? 0) +
                ($row['bpjs_tk_jkk_0_24_percent'] ?? 0) +
                ($row['bpjs_tk_jkm_0_3_percent'] ?? 0) +
                ($row['bpjs_tk_jp_2_percent'] ?? 0) +
                ($row['bpjs_tk_jht_2_percent'] ?? 0) +
                ($row['bpjs_tk_jp_1_percent'] ?? 0) +
                ($row['bpjs_tenaga_kerja'] ?? 0) +
                ($row['bpjs_kes_4_percent'] ?? 0) +
                ($row['bpjs_kes_1_percent'] ?? 0) +
                ($row['bpjs_kesehatan'] ?? 0) +
                ($row['insentif_sholat'] ?? 0) +
                ($row['monthly_bonus'] ?? 0) +
                ($row['rapel'] ?? 0) +
                ($row['tunjangan_pulsa'] ?? 0) +
                ($row['tunjangan_kehadiran'] ?? 0) +
                ($row['tunjangan_transport'] ?? 0) +
                ($row['tunjangan_lainnya'] ?? 0) +
                ($row['yearly_bonus'] ?? 0) +
                ($row['thr'] ?? 0) +
                ($row['other'] ?? 0);
            
            // Calculate total potongan
            $totalPotongan = 
                ($row['ca_corporate'] ?? 0) +
                ($row['ca_personal'] ?? 0) +
                ($row['ca_kehadiran'] ?? 0) +
                ($row['pph_21_deduction'] ?? 0) -
                ($row['bpjs_tk_jht_3_7_percent'] ?? 0) -
                ($row['bpjs_tk_jkk_0_24_percent'] ?? 0) -
                ($row['bpjs_tk_jkm_0_3_percent'] ?? 0) -
                ($row['bpjs_tk_jp_2_percent'] ?? 0) -
                ($row['bpjs_tk_jht_2_percent'] ?? 0) -
                ($row['bpjs_tk_jp_1_percent'] ?? 0) -
                ($row['bpjs_kes_4_percent'] ?? 0) -
                ($row['bpjs_kes_1_percent'] ?? 0);
            
            $gajiBersih = $totalPenerimaan + $totalPotongan;
            
            return [
                'periode' => $row['periode'],
                'karyawan_nama' => $row['karyawan_nama'] ?? '-',
                'karyawan_nik' => $row['karyawan_nik'] ?? '-',
                'absen_karyawan_id' => $row['absen_karyawan_id'] ?? '-',
                'company_name' => $row['company_name'] ?? '-',
                'company_code' => $row['company_code'] ?? '-',
                'absen_company_id' => $row['absen_company_id'] ?? '-',
                
                // Gaji Pokok
                'gaji_pokok' => $row['gaji_pokok'] ?? 0,
                
                // Monthly Income
                'monthly_kpi' => $row['monthly_kpi'] ?? 0,
                'overtime' => $row['overtime'] ?? 0,
                'medical_reimbursement' => $row['medical_reimbursement'] ?? 0,
                
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
                
                // BPJS TK Perusahaan
                'bpjs_tk_jht_3_7_percent' => $row['bpjs_tk_jht_3_7_percent'] ?? 0,
                'bpjs_tk_jkk_0_24_percent' => $row['bpjs_tk_jkk_0_24_percent'] ?? 0,
                'bpjs_tk_jkm_0_3_percent' => $row['bpjs_tk_jkm_0_3_percent'] ?? 0,
                'bpjs_tk_jp_2_percent' => $row['bpjs_tk_jp_2_percent'] ?? 0,
                
                // BPJS TK Pegawai
                'bpjs_tk_jht_2_percent' => $row['bpjs_tk_jht_2_percent'] ?? 0,
                'bpjs_tk_jp_1_percent' => $row['bpjs_tk_jp_1_percent'] ?? 0,
                'bpjs_tenaga_kerja' => $row['bpjs_tenaga_kerja'] ?? 0,
                
                // BPJS Kesehatan
                'bpjs_kes_4_percent' => $row['bpjs_kes_4_percent'] ?? 0,
                'bpjs_kes_1_percent' => $row['bpjs_kes_1_percent'] ?? 0,
                'bpjs_kesehatan' => $row['bpjs_kesehatan'] ?? 0,
                
                // Potongan
                'ca_corporate' => $row['ca_corporate'] ?? 0,
                'ca_personal' => $row['ca_personal'] ?? 0,
                'ca_kehadiran' => $row['ca_kehadiran'] ?? 0,
                'pph_21' => $row['pph_21'] ?? 0,
                'pph_21_deduction' => $row['pph_21_deduction'] ?? 0,

                // lainnya
                'glh' => $row['glh'] ?? 0,
                'lm' => $row['lm'] ?? 0,
                'lainnya' => $row['lainnya'] ?? 0,

                // Calculated
                'total_penerimaan' => $totalPenerimaan,
                'total_potongan' => $totalPotongan,
                'gaji_bersih' => $gajiBersih,
                
                'salary_type' => $row['salary_type'] ?? '-',
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
}