<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BalanceReimbursement extends Model
{
    use HasFactory;

    use HasFactory;

    /**
     * The table associated with the model (this is a VIEW, not a table).
     *
     * @var string
     */
    protected $table = 'balance_reimbursements';

    /**
     * Indicates if the model should be timestamped.
     * Views don't have timestamps
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The primary key for the model.
     * Since this is a view, we don't have a real primary key
     *
     * @var string
     */
    protected $primaryKey = null;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'budget_claim' => 'decimal:2',
        'total_used' => 'decimal:2',
        'sisa_budget' => 'decimal:2',
        'year' => 'integer',
    ];

    /**
     * Relationship: Karyawan
     */
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id', 'absen_karyawan_id');
    }

    /**
     * Scope untuk filter by year
     */
    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope untuk karyawan dengan budget hampir habis (>= 80%)
     */
    public function scopeAlmostExhausted($query)
    {
        return $query->whereRaw('(total_used / budget_claim * 100) >= 80');
    }

    /**
     * Scope untuk karyawan dengan sisa budget
     */
    public function scopeHasRemainingBudget($query)
    {
        return $query->where('sisa_budget', '>', 0);
    }

    /**
     * Accessor untuk persentase penggunaan
     */
    public function getPercentageUsedAttribute()
    {
        if ($this->budget_claim > 0) {
            return ($this->total_used / $this->budget_claim) * 100;
        }
        return 0;
    }
}
