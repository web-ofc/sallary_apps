<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

     protected $table = 'companies';
     protected $guarded = ['id'];
    
    protected $dates = ['deleted_at'];
    
    /**
     * Scope: Company yang perlu sync
     */
    public function scopeNeedsSync($query, $hoursAgo = 24)
    {
        return $query->where(function($q) use ($hoursAgo) {
            $q->whereNull('last_synced_at')
              ->orWhere('last_synced_at', '<', now()->subHours($hoursAgo));
        });
    }
    
    /**
     * Relasi ke Karyawan (jika ada FK company_id di karyawan)
     */
    public function karyawan()
    {
        return $this->hasMany(Karyawan::class, 'company_id');
    }
    
    /**
     * Relasi ke Payroll (jika ada FK company_id di payroll)
     */
    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'company_id');
    }
    
    /**
     * Check apakah company punya karyawan
     */
    public function hasKaryawan()
    {
        return $this->karyawan()->exists();
    }
    
    /**
     * Check apakah company punya payroll
     */
    public function hasPayrolls()
    {
        return $this->payrolls()->exists();
    }
}
