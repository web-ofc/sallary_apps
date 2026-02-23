<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterSalary extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'salary' => 'integer',
        'update_date' => 'date',
        'year' => 'integer',
    ];

    /**
     * Relasi ke Karyawan
     * PENTING: Menggunakan absen_karyawan_id sebagai foreign key
     * karyawan_id di master_salaries = absen_karyawan_id di karyawans
     */
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id', 'absen_karyawan_id');
    }

    /**
     * Accessor untuk format salary dengan Rupiah
     */
    public function getFormattedSalaryAttribute()
    {
        return 'Rp ' . number_format($this->salary, 0, ',', '.');
    }

    /**
     * Scope untuk filter berdasarkan tahun
     */
    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope untuk filter berdasarkan absen_karyawan_id
     */
    public function scopeByAbsenKaryawanId($query, $absenKaryawanId)
    {
        return $query->where('karyawan_id', $absenKaryawanId);
    }

    /**
     * Scope untuk salary terbaru
     */
    public function scopeLatestSalary($query)
    {
        return $query->orderBy('update_date', 'desc')
                     ->orderBy('year', 'desc');
    }
}