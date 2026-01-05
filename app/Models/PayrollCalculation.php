<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollCalculation extends Model
{
    use HasFactory;
    
    /**
     * Table name adalah VIEW, bukan table biasa
     */
    protected $table = 'payroll_calculations';
    
    /**
     * Primary key
     */
    protected $primaryKey = 'id';
    
    /**
     * Karena ini VIEW (read-only), disable timestamps
     */
    public $timestamps = false;
    
    /**
     * Guarded attributes
     */
    protected $guarded = ['id'];
    
    /**
     * Cast attributes
     */
    protected $casts = [
        'is_released' => 'boolean',
        'gaji_pokok' => 'integer',
        'monthly_kpi' => 'integer',
        'overtime' => 'integer',
        'medical_reimbursement' => 'integer',
        'insentif_sholat' => 'integer',
        'monthly_bonus' => 'integer',
        'rapel' => 'integer',
        'tunjangan_pulsa' => 'integer',
        'tunjangan_kehadiran' => 'integer',
        'tunjangan_transport' => 'integer',
        'tunjangan_lainnya' => 'integer',
        'yearly_bonus' => 'integer',
        'thr' => 'integer',
        'other' => 'integer',
        'ca_corporate' => 'integer',
        'ca_personal' => 'integer',
        'ca_kehadiran' => 'integer',
        'pph_21' => 'integer',
        'pph_21_deduction' => 'integer',
        'bpjs_tenaga_kerja' => 'integer',
        'bpjs_kesehatan' => 'integer',
        'bpjs_tk_jht_3_7_percent' => 'integer',
        'bpjs_tk_jht_2_percent' => 'integer',
        'bpjs_tk_jkk_0_24_percent' => 'integer',
        'bpjs_tk_jkm_0_3_percent' => 'integer',
        'bpjs_tk_jp_2_percent' => 'integer',
        'bpjs_tk_jp_1_percent' => 'integer',
        'bpjs_kes_4_percent' => 'integer',
        'bpjs_kes_1_percent' => 'integer',
        // Calculated fields from VIEW
        'salary' => 'integer',
        'total_penerimaan' => 'integer',
        'total_potongan' => 'integer',
        'gaji_bersih' => 'integer',
    ];
    
    /**
     * ✅ RELASI KE KARYAWAN (fix untuk menghindari error)
     * Gunakan null-safe dan cek apakah relasi tersedia
     */
    public function karyawan()
    {
        // Cek dulu apakah kolom karyawan_id ada di view
        return $this->belongsTo(Karyawan::class, 'karyawan_id', 'absen_karyawan_id')
            ->withDefault(function () {
                // Return default object jika tidak ada
                return new \App\Models\Karyawan([
                    'nama_lengkap' => '-',
                    'nik' => '-',
                    'absen_karyawan_id' => null,
                ]);
            });
    }
    
    /**
     * ✅ RELASI KE COMPANY
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'absen_company_id')
            ->withDefault(function () {
                return new \App\Models\Company([
                    'company_name' => '-',
                    'absen_company_id' => null,
                ]);
            });
    }
    
    /**
     * Helper: Get Karyawan Nama (null-safe)
     */
    public function getKaryawanNamaAttribute()
    {
        try {
            return $this->karyawan->nama_lengkap ?? '-';
        } catch (\Exception $e) {
            return '-';
        }
    }
    
    /**
     * Helper: Get Karyawan NIK (null-safe)
     */
    public function getKaryawanNikAttribute()
    {
        try {
            return $this->karyawan->nik ?? '-';
        } catch (\Exception $e) {
            return '-';
        }
    }
    
    /**
     * Helper: Get Company Name (null-safe)
     */
    public function getCompanyNameAttribute()
    {
        try {
            return $this->company->company_name ?? '-';
        } catch (\Exception $e) {
            return '-';
        }
    }
    
    /**
     * Check if karyawan_id exists in view
     */
    public function hasKaryawanId()
    {
        return isset($this->attributes['karyawan_id']) && !is_null($this->attributes['karyawan_id']);
    }
}