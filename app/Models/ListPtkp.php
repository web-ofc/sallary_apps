<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class ListPtkp extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'list_ptkps';
    protected $guarded = ['id'];
    
    protected $dates = ['deleted_at'];
    
    /**
     * Scope: PTKP yang perlu sync
     */
    public function scopeNeedsSync($query, $hoursAgo = 24)
    {
        return $query->where(function($q) use ($hoursAgo) {
            $q->whereNull('last_synced_at')
              ->orWhere('last_synced_at', '<', now()->subHours($hoursAgo));
        });
    }
    
    /**
     * Relasi ke Karyawan (jika ada FK ptkp_id di karyawan)
     */
    public function karyawan()
    {
        return $this->hasMany(Karyawan::class, 'ptkp_id');
    }
    
    /**
     * Check apakah PTKP punya karyawan
     */
    public function hasKaryawan()
    {
        return $this->karyawan()->exists();
    }
}
