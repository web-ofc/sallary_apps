<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reimbursement extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'year_budget' => 'integer',
        'status' => 'boolean',
        'approved_at' => 'date',
    ];

    /**
     * Boot model - auto generate id_recapan
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id_recapan)) {
                $model->id_recapan = static::generateIdRecapan($model->company_id);
            }
        });
    }

     /**
     * ✅ NEW: Generate unique ID Recapan per company per year
     * Format: RMB-{COMPANY_CODE}-{YYMM}-{XXXX}
     * Example: RMB-CEK-2602-0001
     * 
     * Rules:
     * - RMB = Reimbursement code (fixed)
     * - COMPANY_CODE = dari companies.code (via absen_company_id)
     * - YYMM = Year (2 digit) + Month (2 digit) dari now()
     * - XXXX = Counter per company per year (reset setiap ganti tahun)
     * 
     * @param int $companyId absen_company_id
     * @return string
     */
    public static function generateIdRecapan($companyId)
    {
        // Get company code
        $company = \App\Models\Company::where('absen_company_id', $companyId)->first();
        
        if (!$company || empty($company->code)) {
            throw new \Exception("Company code tidak ditemukan untuk company_id: {$companyId}");
        }

        $companyCode = strtoupper($company->code);
        
        // Format: YYMM (e.g., 2602 for Feb 2026)
        $yearMonth = now()->format('ym'); // lowercase 'y' = 2 digit year
        
        // Current year untuk reset counter
        $currentYear = now()->year;
        
        // Prefix: RMB-{COMPANY_CODE}-{YYMM}-
        $prefix = "RMB-{$companyCode}-{$yearMonth}-";
        
        // ✅ IMPORTANT: withTrashed() untuk include soft deleted records
        // Cari record terakhir untuk company ini di tahun ini
        $lastRecord = static::withTrashed()
            ->where('company_id', $companyId)
            ->where('year_budget', $currentYear)
            ->where('id_recapan', 'like', "RMB-{$companyCode}-{$yearMonth}-%")
            ->orderBy('id_recapan', 'desc')
            ->first();
        
        if ($lastRecord) {
            // Extract last 4 digits
            $lastNumber = (int) substr($lastRecord->id_recapan, -4);
            $newNumber = $lastNumber + 1;
        } else {
            // First record untuk company ini di tahun/bulan ini
            $newNumber = 1;
        }
        
        // Format: 0001, 0002, dst
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relasi ke Karyawan menggunakan absen_karyawan_id
     */
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id', 'absen_karyawan_id');
    }

    /**
     * Relasi ke Karyawan menggunakan absen_company_id
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'absen_company_id');
    }

    /**
     * Relasi ke Approver (juga karyawan)
     */
    public function approver()
    {
        return $this->belongsTo(Karyawan::class, 'approved_id', 'absen_karyawan_id');
    }

     /**
     * Relasi ke User yang membuat reimbursement (prepare)
     */
    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_by_id');
    }



    /**
     * Relasi ke Children
     */
    public function childs()
    {
        return $this->hasMany(ReimbursementChild::class, 'reimbursement_id', 'id');
    }

    /**
     * Relasi ke Children - General only
     */
    public function generalChilds()
    {
        return $this->hasMany(ReimbursementChild::class, 'reimbursement_id', 'id')
            ->whereHas('reimbursementType', function($q) {
                $q->where('group_medical', 'general');
            });
    }

    /**
     * Relasi ke Children - Other only
     */
    public function otherChilds()
    {
        return $this->hasMany(ReimbursementChild::class, 'reimbursement_id', 'id')
            ->whereHas('reimbursementType', function($q) {
                $q->where('group_medical', 'other');
            });
    }

    /**
     * Get total amount dari semua children
     */
    public function getTotalAmountAttribute()
    {
        return $this->childs()->sum('harga');
    }

    /**
     * Scope - filter by year
     */
    public function scopeByYear($query, $year)
    {
        return $query->where('year_budget', $year);
    }

    /**
     * Scope - filter by periode slip
     */
    public function scopeByPeriode($query, $periode)
    {
        return $query->where('periode_slip', $periode);
    }

    /**
     * Scope - filter by karyawan (absen_karyawan_id)
     */
    public function scopeByKaryawan($query, $absenKaryawanId)
    {
        return $query->where('karyawan_id', $absenKaryawanId);
    }

    /**
     * Scope - approved only
     */
    public function scopeApproved($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope - pending only
     */
    public function scopePending($query)
    {
        return $query->where('status', false);
    }
}
