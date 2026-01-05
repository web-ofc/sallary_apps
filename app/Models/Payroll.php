<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Di Model Payroll
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id', 'absen_karyawan_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'absen_company_id');
    }
   
   
}
