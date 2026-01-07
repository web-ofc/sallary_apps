<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Karyawan extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = ['id'];

    protected $casts = [
        'status_resign' => 'boolean',
        'join_date' => 'date',
        'last_synced_at' => 'datetime',
        'sync_metadata' => 'array', // ✅ INI PENTING - otomatis convert JSON <-> array
    ];

      /**
     * Scope: Karyawan aktif (belum resign)
     */
    public function scopeActive($query)
    {
        return $query->where('status_resign', false);
    }
    
    /**
     * Scope: Karyawan yang resign
     */
    public function scopeResigned($query)
    {
        return $query->where('status_resign', true);
    }
    
    /**
     * Scope: Perlu sync (belum pernah sync atau sudah lama)
     */
    public function scopeNeedsSync($query, $hoursAgo = 24)
    {
        return $query->where(function($q) use ($hoursAgo) {
            $q->whereNull('last_synced_at')
              ->orWhere('last_synced_at', '<', now()->subHours($hoursAgo));
        });
    }
    
    /**
     * Relasi ke Payroll (jika ada)
     */
    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'karyawan_id');
    }

    
    /**
     * Check apakah karyawan punya payroll
     */
    public function hasPayrolls()
    {
        return $this->payrolls()->exists();
    }
}
