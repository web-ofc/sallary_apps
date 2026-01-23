<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollCalculationsMerge extends Model
{
    use HasFactory;

     /**
     * Table name adalah VIEW, bukan table biasa
     */
    protected $table = 'payroll_calculations_merge';
    
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
