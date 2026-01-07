<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KaryawanPtkpHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'karyawan_ptkp_histories';
    
    protected $guarded = ['id'];

    protected $casts = [
        'tahun' => 'integer',
        'last_synced_at' => 'datetime',
        'sync_metadata' => 'array', // ✅ Otomatis convert JSON <-> array
    ];

    protected $dates = ['deleted_at', 'last_synced_at'];

    /**
     * Relasi ke Karyawan (menggunakan absen_karyawan_id sebagai foreign key)
     * karyawan_ptkp_histories.absen_karyawan_id → karyawans.absen_karyawan_id
     */
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'absen_karyawan_id', 'absen_karyawan_id');
    }

    /**
     * Relasi ke PTKP (menggunakan absen_ptkp_id sebagai foreign key)
     * karyawan_ptkp_histories.absen_ptkp_id → list_ptkps.absen_ptkp_id
     */
    public function ptkp()
    {
        return $this->belongsTo(ListPtkp::class, 'absen_ptkp_id', 'absen_ptkp_id');
    }

    /**
     * Relasi ke User yang update (local user)
     * Ini optional, karena absen_updated_by_id adalah ID dari aplikasi ABSEN
     * Kalau mau track local user, bisa tambahin kolom 'local_updated_by_id'
     */
    public function localUpdatedBy()
    {
        return $this->belongsTo(User::class, 'absen_updated_by_id');
    }

    /**
     * Scope: History yang perlu sync
     */
    public function scopeNeedsSync($query, $hoursAgo = 24)
    {
        return $query->where(function($q) use ($hoursAgo) {
            $q->whereNull('last_synced_at')
              ->orWhere('last_synced_at', '<', now()->subHours($hoursAgo));
        });
    }

    /**
     * Scope: Filter by tahun
     */
    public function scopeByTahun($query, $tahun)
    {
        return $query->where('tahun', $tahun);
    }

    /**
     * Scope: Filter by absen karyawan id
     */
    public function scopeByAbsenKaryawan($query, $absenKaryawanId)
    {
        return $query->where('absen_karyawan_id', $absenKaryawanId);
    }

    /**
     * Scope: Filter by absen PTKP id
     */
    public function scopeByAbsenPtkp($query, $absenPtkpId)
    {
        return $query->where('absen_ptkp_id', $absenPtkpId);
    }

    /**
     * Get latest history per karyawan
     */
    public function scopeLatestPerKaryawan($query)
    {
        return $query->whereIn('id', function($subQuery) {
            $subQuery->selectRaw('MAX(id)')
                ->from('karyawan_ptkp_histories')
                ->groupBy('absen_karyawan_id');
        });
    }
}