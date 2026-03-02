<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class SyncKaryawanWithoutUser extends Model
{
    use HasFactory,SoftDeletes;

     protected $table = 'sync_karyawan_without_users';

    protected $fillable = [
        'absen_karyawan_id',
        'nik',
        'nama_lengkap',
        'email_pribadi',
        'telp_pribadi',
        'join_date',
        'jenis_kelamin',
        'status_pernikahan',
        'tempat_tanggal_lahir',
        'alamat',
        'no_ktp',
        'status_resign',
        'last_synced_at',
    ];

    protected $casts = [
        'join_date'      => 'date',
        'status_resign'  => 'boolean',
        'last_synced_at' => 'datetime',
    ];
}
