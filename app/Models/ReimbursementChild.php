<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReimbursementChild extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    
    protected $table = 'reimbursement_childs';

    protected $casts = [
        'harga' => 'integer',
    ];

    /**
     * Relasi ke Reimbursement Header
     */
    public function reimbursement()
    {
        return $this->belongsTo(Reimbursement::class, 'reimbursement_id', 'id');
    }

    /**
     * Relasi ke Master Reimbursement Type
     */
    public function reimbursementType()
    {
        return $this->belongsTo(MasterReimbursementType::class, 'reimbursement_type_id', 'id');
    }

    /**
     * Accessor untuk format harga
     */
    public function getFormattedHargaAttribute()
    {
        return 'Rp ' . number_format($this->harga, 0, ',', '.');
    }

    /**
     * Scope - filter by type
     */
    public function scopeByType($query, $typeId)
    {
        return $query->where('reimbursement_type_id', $typeId);
    }

    /**
     * Scope - filter by group (general/other)
     */
    public function scopeByGroup($query, $group)
    {
        return $query->whereHas('reimbursementType', function($q) use ($group) {
            $q->where('group_medical', $group);
        });
    }
}
