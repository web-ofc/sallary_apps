<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

      public function settingCompanyUsers()
    {
        return $this->hasMany(SettingCompanyUser::class, 'user_id', 'id');
    }
    
    /**
     * User punya banyak companies (through pivot table)
     * Ini yang lu mau pake
     */
    public function assignedCompanies()
    {
        return $this->belongsToMany(
            Company::class,              // Model target
            'setting_company_users',     // Pivot table name
            'user_id',                   // FK di pivot untuk User
            'absen_company_id',          // FK di pivot untuk Company
            'id',                        // PK di User table
            'absen_company_id'           // Reference key di Company table (bukan 'id')
        );
    }
}
