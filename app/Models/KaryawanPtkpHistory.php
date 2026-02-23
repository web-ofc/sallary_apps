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

}