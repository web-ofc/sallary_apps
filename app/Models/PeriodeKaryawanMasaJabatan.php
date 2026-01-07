<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodeKaryawanMasaJabatan extends Model
{
    use HasFactory;

     /**
     * Table name adalah VIEW
     */
    
    /**
     * Karena ini VIEW (read-only), disable timestamps
     */
    public $timestamps = false;
    
    /**
     * No primary key
     */
    public $incrementing = false;
    
    /**
     * Cast attributes
     */
    protected $casts = [
        'periode' => 'integer',
        'karyawan_id' => 'integer',
        'company_id' => 'integer',
        'salary' => 'integer',
        'overtime' => 'integer',
        'tunjangan' => 'integer',
        'natura' => 'integer',
        'tunj_pph_21' => 'integer',
        'tunjangan_asuransi' => 'integer',
        'bpjs_asuransi' => 'integer',
        'thr_bonus' => 'integer',
        'total_bruto' => 'integer',
        'masa_jabatan' => 'integer',
        'premi_asuransi' => 'integer',
        'biaya_jabatan' => 'integer',
        'besaran_ptkp' => 'integer',
        'pkp' => 'integer',
    ];
    
    /**
     * Relasi ke Karyawan
     */
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id', 'absen_karyawan_id');
    }
    
    /**
     * Relasi ke Company
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'absen_company_id');
    }
    
    /**
     * Get nama karyawan (accessor)
     */
    public function getKaryawanNamaAttribute()
    {
        return $this->karyawan->nama_lengkap ?? '-';
    }
    
    /**
     * Get company name (accessor)
     */
    public function getCompanyNameAttribute()
    {
        return $this->company->company_name ?? '-';
    }
    
    /**
     * Scope: Filter by periode (tahun)
     */
    public function scopeByPeriode($query, $periode)
    {
        return $query->where('periode', $periode);
    }
    
    /**
     * Scope: Filter by karyawan
     */
    public function scopeByKaryawan($query, $karyawanId)
    {
        return $query->where('karyawan_id', $karyawanId);
    }
    
    /**
     * Scope: Filter by company
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
    
    /**
     * Scope: Filter by salary type
     */
    public function scopeBySalaryType($query, $salaryType)
    {
        return $query->where('salary_type', $salaryType);
    }
}
