<?php
// app/Models/RangeBruto.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RangeBruto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'range_brutos';
    protected $guarded = ['id'];
    
    protected $dates = ['deleted_at'];
    
    protected $casts = [
        'min_bruto' => 'integer',
        'max_bruto' => 'integer',
        'ter' => 'decimal:2',
    ];
    
    /**
     * Scope: Range Bruto yang perlu sync
     */
    public function scopeNeedsSync($query, $hoursAgo = 24)
    {
        return $query->where(function($q) use ($hoursAgo) {
            $q->whereNull('last_synced_at')
              ->orWhere('last_synced_at', '<', now()->subHours($hoursAgo));
        });
    }
    
    /**
     * Relasi ke Jenis TER (Local)
     */
     public function jenisTer()
    {
        return $this->belongsTo(JenisTer::class, 'absen_jenis_ter_id', 'absen_jenis_ter_id');
    }

    

    
    /**
     * Format min bruto
     */
    public function getFormattedMinBrutoAttribute()
    {
        return 'Rp ' . number_format($this->min_bruto, 0, ',', '.');
    }
    
    /**
     * Format max bruto
     */
    public function getFormattedMaxBrutoAttribute()
    {
        if ($this->max_bruto === null) {
            return '∞';
        }
        return 'Rp ' . number_format($this->max_bruto, 0, ',', '.');
    }
    
    /**
     * Format TER percentage
     */
    public function getFormattedTerAttribute()
    {
        return $this->ter . '%';
    }
    
    /**
     * Get range display
     */
    public function getRangeDisplayAttribute()
    {
        if ($this->max_bruto === null) {
            return $this->formatted_min_bruto . ' - ∞';
        }
        return $this->formatted_min_bruto . ' - ' . $this->formatted_max_bruto;
    }
}