<?php
// app/Models/JenisTer.php

namespace App\Models;

use App\Models\RangeBruto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JenisTer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'jenis_ters';
    protected $guarded = ['id'];
    
    protected $dates = ['deleted_at'];
    
    /**
     * Scope: Jenis TER yang perlu sync
     */
    public function scopeNeedsSync($query, $hoursAgo = 24)
    {
        return $query->where(function($q) use ($hoursAgo) {
            $q->whereNull('last_synced_at')
              ->orWhere('last_synced_at', '<', now()->subHours($hoursAgo));
        });
    }
    
    /**
     * Relasi ke Range Bruto
     */
    public function rangeBrutos()
    {
        return $this->hasMany(RangeBruto::class, 'jenis_ter_id', 'absen_jenis_ter_id');
    }
    
    /**
     * Check apakah Jenis TER punya range bruto
     */
    public function hasRangeBrutos()
    {
        return $this->rangeBrutos()->exists();
    }
}