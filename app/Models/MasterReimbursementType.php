<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterReimbursementType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
     protected $guarded = ['id'];
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope untuk filter by group medical
     */
    public function scopeByGroup($query, $group)
    {
        return $query->where('group_medical', $group);
    }

    /**
     * Scope untuk search
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('medical_type', 'like', "%{$search}%")
              ->orWhere('group_medical', 'like', "%{$search}%");
        });
    }

    /**
     * Accessor untuk format code (uppercase)
     */
    public function getCodeAttribute($value)
    {
        return strtoupper($value);
    }

    /**
     * Mutator untuk set code (uppercase)
     */
    public function setCodeAttribute($value)
    {
        $this->attributes['code'] = strtoupper($value);
    }

      /**
     * Relasi ke Reimbursement Children
     */
    public function reimbursementChilds()
    {
        return $this->hasMany(ReimbursementChild::class, 'reimbursement_type_id', 'id');
    }

    /**
     * Scope - General types only
     */
    public function scopeGeneral($query)
    {
        return $query->where('group_medical', 'general');
    }

    /**
     * Scope - Other types only
     */
    public function scopeOther($query)
    {
        return $query->where('group_medical', 'other');
    }
}
