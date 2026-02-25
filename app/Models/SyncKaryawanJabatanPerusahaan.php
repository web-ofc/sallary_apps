<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SyncKaryawanJabatanPerusahaan extends Model
{
    use SoftDeletes;

    protected $table = 'sync_karyawan_jabatan_perusahaans';

    protected $fillable = [
        'absen_karyawan_id',
        'nik',
        'nama_lengkap',
        'email',
        'join_date',

        'absen_jabatan_id',
        'kode_jabatan',
        'nama_jabatan',
        'tgl_mutasi_jabatan',

        'absen_company_id',
        'company_code',
        'company_name',
        'company_logo',
        'tgl_mutasi_perusahaan',

        'last_synced_at',
    ];

    protected $casts = [
        'join_date'              => 'date',
        'tgl_mutasi_jabatan'     => 'date',
        'tgl_mutasi_perusahaan'  => 'date',
        'last_synced_at'         => 'datetime',
    ];

    // ── Scope: hanya yang punya medical (ada di master_salaries) ──
    // Scope ini dipakai di getKaryawanList agar tetap terfilter
    public function scopeHasMedical($query)
    {
        return $query->whereExists(function ($sub) {
            $sub->selectRaw(1)
                ->from('master_salaries')
                ->whereColumn('master_salaries.karyawan_id', 'sync_karyawan_jabatan_perusahaans.absen_karyawan_id')
                ->where('master_salaries.status_medical', '1');
        });
    }
}