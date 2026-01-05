<?php
// app/Imports/PayrollImport.php (FIXED - Simpan absen_id langsung ke kolom karyawan_id & company_id)

namespace App\Imports;

use App\Models\Payroll;
use App\Models\Karyawan;
use App\Models\Company;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PayrollImport implements WithMultipleSheets
{
    use Importable;
    
    protected $payrollSheet;
    
    public function sheets(): array
    {
        $this->payrollSheet = new PayrollDataSheet();
        
        return [
            0 => $this->payrollSheet,
        ];
    }
    
    public function getValidationErrors()
    {
        return $this->payrollSheet ? $this->payrollSheet->getValidationErrors() : [];
    }
    
    public function getValidRows()
    {
        return $this->payrollSheet ? $this->payrollSheet->getValidRows() : [];
    }
    
    public function getProcessedRows()
    {
        return $this->payrollSheet ? $this->payrollSheet->getProcessedRows() : 0;
    }
}

/**
 * ✅ FIXED: Simpan absen_karyawan_id & absen_company_id langsung ke kolom karyawan_id & company_id
 */
class PayrollDataSheet implements ToCollection, WithHeadingRow, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;
    
    protected $validationErrors = [];
    protected $validRows = [];
    protected $processedRows = 0;
    
    // Map untuk validasi existence
    protected $karyawanMap = [];  // Key: absen_karyawan_id
    protected $companyMap = [];   // Key: absen_company_id
    
    public function headingRow(): int
    {
        return 4;
    }
    
    public function collection(Collection $rows)
    {
        Log::info('Starting Payroll Import', ['total_rows' => $rows->count()]);
        
        // Collect unique absen IDs from Excel
        $absenKaryawanIds = [];
        $absenCompanyIds = [];
        
        // ✅ NEW: Track periode+karyawan combinations dalam Excel
        $excelCombinations = []; // Format: "periode|karyawan_id" => row_number
        
        foreach ($rows as $row) {
            if (!$this->isEmptyRow($row)) {
                if (!empty($row['karyawan_id']) && is_numeric($row['karyawan_id'])) {
                    $absenKaryawanIds[] = (int) $row['karyawan_id'];
                }
                if (!empty($row['company_id']) && is_numeric($row['company_id'])) {
                    $absenCompanyIds[] = (int) $row['company_id'];
                }
            }
        }
        
        $absenKaryawanIds = array_unique($absenKaryawanIds);
        $absenCompanyIds = array_unique($absenCompanyIds);
        
        Log::info('Preloading data for validation', [
            'absen_karyawan_ids_count' => count($absenKaryawanIds),
            'absen_company_ids_count' => count($absenCompanyIds)
        ]);
        
        // Preload untuk validasi existence & status
        $this->preloadKaryawan($absenKaryawanIds);
        $this->preloadCompanies($absenCompanyIds);
        
        Log::info('Data preloaded', [
            'karyawan_loaded' => count($this->karyawanMap),
            'companies_loaded' => count($this->companyMap)
        ]);
        
        // Validate each row
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 5; // Excel row number (header at row 4)
            
            if ($this->isEmptyRow($row)) {
                continue;
            }
            
            $this->processedRows++;
            
            // ✅ NEW: Check duplikasi dalam Excel
            $periode = trim($row['periode'] ?? '');
            $karyawanId = $row['karyawan_id'] ?? null;
            
            if (!empty($periode) && !empty($karyawanId) && is_numeric($karyawanId)) {
                $combinationKey = $periode . '|' . (int)$karyawanId;
                
                if (isset($excelCombinations[$combinationKey])) {
                    // Duplikasi terdetect!
                    $firstRowNumber = $excelCombinations[$combinationKey];
                    
                    $this->validationErrors[] = [
                        'row' => $rowNumber,
                        'data' => [
                            'periode' => $periode,
                            'karyawan_id' => $karyawanId,
                            'company_id' => $row['company_id'] ?? '',
                        ],
                        'errors' => [
                            "Duplikasi dalam Excel: Periode '{$periode}' untuk karyawan_id '{$karyawanId}' sudah ada di row {$firstRowNumber}"
                        ]
                    ];
                    
                    continue; // Skip validasi lainnya
                }
                
                // Simpan kombinasi ini
                $excelCombinations[$combinationKey] = $rowNumber;
            }
            
            $validation = $this->validateRow($row, $rowNumber);
            
            if (!empty($validation['errors'])) {
                $this->validationErrors[] = [
                    'row' => $rowNumber,
                    'data' => [
                        'periode' => $row['periode'] ?? '',
                        'karyawan_id' => $row['karyawan_id'] ?? '',
                        'company_id' => $row['company_id'] ?? '',
                    ],
                    'errors' => $validation['errors']
                ];
            } else {
                $this->validRows[] = $validation['data'];
            }
        }
        
        Log::info('Payroll Import Completed', [
            'processed_rows' => $this->processedRows,
            'valid_rows' => count($this->validRows),
            'error_rows' => count($this->validationErrors),
            'excel_duplicates_detected' => count($excelCombinations) < $this->processedRows
        ]);
    }
    
    /**
     * ✅ Preload karyawan untuk validasi
     */
    private function preloadKaryawan(array $absenKaryawanIds)
    {
        if (empty($absenKaryawanIds)) return;
        
        try {
            Log::info('Loading karyawan from database', ['count' => count($absenKaryawanIds)]);
            
            $karyawans = Karyawan::whereIn('absen_karyawan_id', $absenKaryawanIds)
                ->select([
                    'absen_karyawan_id',
                    'nik',
                    'nama_lengkap',
                    'email_pribadi',
                    'status_resign'
                ])
                ->get();
            
            foreach ($karyawans as $karyawan) {
                $this->karyawanMap[$karyawan->absen_karyawan_id] = [
                    'nik' => $karyawan->nik,
                    'nama_lengkap' => $karyawan->nama_lengkap,
                    'email_pribadi' => $karyawan->email_pribadi,
                    'status_resign' => $karyawan->status_resign,
                ];
            }
            
            Log::info('Karyawan preloaded', [
                'loaded' => count($this->karyawanMap),
                'requested' => count($absenKaryawanIds)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error preloading karyawan', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * ✅ Preload companies untuk validasi
     */
    private function preloadCompanies(array $absenCompanyIds)
    {
        if (empty($absenCompanyIds)) return;
        
        try {
            Log::info('Loading companies from database', ['count' => count($absenCompanyIds)]);
            
            $companies = Company::whereIn('absen_company_id', $absenCompanyIds)
                ->select([
                    'absen_company_id',
                    'code',
                    'company_name'
                ])
                ->get();
            
            foreach ($companies as $company) {
                $this->companyMap[$company->absen_company_id] = [
                    'code' => $company->code,
                    'company_name' => $company->company_name,
                ];
            }
            
            Log::info('Companies preloaded', [
                'loaded' => count($this->companyMap),
                'requested' => count($absenCompanyIds)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error preloading companies', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * ✅ Validate row - Simpan absen_id langsung ke kolom karyawan_id & company_id
     */
    private function validateRow($row, $rowNumber)
    {
        $errors = [];
        $data = [];
        
        // 1. Periode
        $periode = trim($row['periode'] ?? '');
        if (empty($periode)) {
            $errors[] = 'Periode wajib diisi';
        } elseif (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periode)) {
            $errors[] = 'Format periode harus YYYY-MM (contoh: 2025-01)';
        }
        $data['periode'] = $periode;
        
        // 2. Karyawan ID (absen_karyawan_id dari Excel)
        $absenKaryawanId = $row['karyawan_id'] ?? null;
        
        if (empty($absenKaryawanId) || !is_numeric($absenKaryawanId)) {
            $errors[] = 'karyawan_id wajib diisi dan harus berupa angka';
        } else {
            $absenKaryawanId = (int) $absenKaryawanId;
            
            if (!isset($this->karyawanMap[$absenKaryawanId])) {
                $errors[] = "karyawan_id '{$absenKaryawanId}' tidak ditemukan di database (belum disinkronisasi?)";
            } else {
                $karyawan = $this->karyawanMap[$absenKaryawanId];
                
                // Check resign status
                if ($karyawan['status_resign'] == true || $karyawan['status_resign'] == 1) {
                    $errors[] = "Karyawan '{$karyawan['nama_lengkap']}' (ID: {$absenKaryawanId}) sudah resign";
                } else {
                    // ✅ PENTING: Simpan absen_karyawan_id langsung ke kolom karyawan_id
                    $data['karyawan_id'] = $absenKaryawanId;
                    
                    // Display fields (untuk preview di frontend)
                    $data['karyawan_nama'] = $karyawan['nama_lengkap'];
                    $data['karyawan_nik'] = $karyawan['nik'] ?? '-';
                    $data['absen_karyawan_id'] = $absenKaryawanId; // Untuk ditampilkan di preview
                }
            }
        }
        
        // 3. Company ID (absen_company_id dari Excel)
        $absenCompanyId = $row['company_id'] ?? null;
        
        if (!empty($absenCompanyId)) {
            if (!is_numeric($absenCompanyId)) {
                $errors[] = 'company_id harus berupa angka';
            } else {
                $absenCompanyId = (int) $absenCompanyId;
                
                if (!isset($this->companyMap[$absenCompanyId])) {
                    $errors[] = "company_id '{$absenCompanyId}' tidak ditemukan di database (belum disinkronisasi?)";
                } else {
                    $company = $this->companyMap[$absenCompanyId];
                    
                    // ✅ PENTING: Simpan absen_company_id langsung ke kolom company_id
                    $data['company_id'] = $absenCompanyId;
                    
                    // Display fields
                    $data['company_name'] = $company['company_name'];
                    $data['company_code'] = $company['code'] ?? '-';
                    $data['absen_company_id'] = $absenCompanyId; // Untuk ditampilkan di preview
                }
            }
        } else {
            $data['company_id'] = null;
            $data['company_name'] = null;
            $data['company_code'] = null;
            $data['absen_company_id'] = null;
        }
        
        // 4. Duplicate check (berdasarkan absen_karyawan_id yang tersimpan di kolom karyawan_id)
        if (empty($errors) && !empty($data['periode']) && !empty($data['karyawan_id'])) {
            $exists = Payroll::where('periode', $data['periode'])
                            ->where('karyawan_id', $data['karyawan_id'])
                            ->exists();
            if ($exists) {
                $errors[] = "Payroll untuk periode {$data['periode']} dan karyawan ini sudah ada";
            }
        }
        
        // 5. Numeric fields
        $numericFields = [
            'gaji_pokok', 'monthly_kpi', 'overtime', 'medical_reimbursement',
            'insentif_sholat', 'monthly_bonus', 'rapel',
            'tunjangan_pulsa', 'tunjangan_kehadiran', 'tunjangan_transport', 'tunjangan_lainnya',
            'yearly_bonus', 'thr', 'other',
            'ca_corporate', 'ca_personal', 'ca_kehadiran', 'pph_21',
            'bpjs_tenaga_kerja', 'bpjs_kesehatan', 'pph_21_deduction',
            'bpjs_tk_jht_3_7_percent', 'bpjs_tk_jht_2_percent',
            'bpjs_tk_jkk_0_24_percent', 'bpjs_tk_jkm_0_3_percent',
            'bpjs_tk_jp_2_percent', 'bpjs_tk_jp_1_percent',
            'bpjs_kes_4_percent', 'bpjs_kes_1_percent', 'glh', 'lm', 'lainnya'
        ];
        
        foreach ($numericFields as $field) {
            $value = $row[$field] ?? null;
            
            if ($value === null || $value === '') {
                $data[$field] = null;
                continue;
            }
            
            if (!is_numeric($value)) {
                $errors[] = ucwords(str_replace('_', ' ', $field)) . " harus berupa angka";
            } else {
                $data[$field] = (int) $value;
            }
        }
        
        // 6. Salary type
        $salaryType = strtolower(trim($row['salary_type'] ?? ''));
        if (!empty($salaryType)) {
            if (!in_array($salaryType, ['gross', 'nett'])) {
                $errors[] = "Salary type harus 'gross' atau 'nett'";
            } else {
                $data['salary_type'] = $salaryType;
            }
        } else {
            $data['salary_type'] = null;
        }
        
        $data['is_released'] = false;
        
        return [
            'errors' => $errors,
            'data' => $data
        ];
    }
    
    private function isEmptyRow($row)
    {
        $filtered = array_filter($row->toArray(), function($value) {
            return !is_null($value) && $value !== '';
        });
        
        return empty($filtered);
    }
    
    public function getValidationErrors()
    {
        return $this->validationErrors;
    }
    
    public function getValidRows()
    {
        return $this->validRows;
    }
    
    public function getProcessedRows()
    {
        return $this->processedRows;
    }
}