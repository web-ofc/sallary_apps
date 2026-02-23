<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingCompanyUser extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'setting_company_users';

     public function user()  
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Relationship ke Company
     * Menggunakan 'absen_company_id' (BUKAN primary key)
     */
    public function company()  
    {
        return $this->belongsTo(Company::class, 'absen_company_id', 'absen_company_id');
    }
}
