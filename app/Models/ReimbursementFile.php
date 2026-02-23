<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReimbursementFile extends Model
{
    use HasFactory;


    protected $guarded = ['id'];

    protected $casts = [
        'year' => 'integer',
    ];

    /**
     * Relasi ke Karyawan menggunakan absen_karyawan_id
     */
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id', 'absen_karyawan_id');
    }

    /**
     * Scope - filter by year
     */
    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope - filter by karyawan
     */
    public function scopeByKaryawan($query, $karyawanId)
    {
        return $query->where('karyawan_id', $karyawanId);
    }

    /**
     * Get file URL
     */
    public function getFileUrlAttribute()
    {
        return asset('storage/' . $this->file);
    }
}
