<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Reimbursement extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'year_budget' => 'integer',
        'status'      => 'boolean',
        'approved_at' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id_recapan)) {
                $model->id_recapan = static::generateIdRecapan($model->company_id);
            }
        });
    }

    /**
     * Generate unique ID Recapan — race-condition safe
     * Format: RMB-{COMPANY_CODE}-{YYMM}-{XXXX}
     * Pakai DB::transaction + lockForUpdate supaya ga duplicate
     */
    public static function generateIdRecapan($companyId)
    {
        $company = \App\Models\Company::where('absen_company_id', $companyId)->first();

        if (!$company || empty($company->code)) {
            throw new \Exception("Company code tidak ditemukan untuk company_id: {$companyId}");
        }

        $companyCode = strtoupper($company->code);
        $yearMonth   = now()->format('ym'); // e.g. 2603
        $prefix      = "RMB-{$companyCode}-{$yearMonth}-";

        return DB::transaction(function () use ($prefix) {
            // Ambil angka tertinggi yang sudah ada (include soft deleted), lock row
            $lastNumber = static::withTrashed()
                ->where('id_recapan', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderByRaw('CAST(SUBSTRING_INDEX(id_recapan, "-", -1) AS UNSIGNED) DESC')
                ->value(DB::raw('CAST(SUBSTRING_INDEX(id_recapan, "-", -1) AS UNSIGNED)'));

            $newNumber = ($lastNumber ?? 0) + 1;
            $candidate = $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

            // Safety: kalau masih bentrok (edge case), terus increment
            while (static::withTrashed()->where('id_recapan', $candidate)->exists()) {
                $newNumber++;
                $candidate = $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            }

            return $candidate;
        });
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id', 'absen_karyawan_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'absen_company_id');
    }

    public function approver()
    {
        return $this->belongsTo(Karyawan::class, 'approved_id', 'absen_karyawan_id');
    }

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_by_id');
    }

    public function childs()
    {
        return $this->hasMany(ReimbursementChild::class, 'reimbursement_id', 'id');
    }

    public function getTotalAmountAttribute()
    {
        return $this->childs->sum(fn($c) =>
            ($c->tagihan_dokter   ?? 0) +
            ($c->tagihan_obat     ?? 0) +
            ($c->tagihan_kacamata ?? 0) +
            ($c->tagihan_gigi     ?? 0)
        );
    }

    public function scopeByYear($query, $year)       { return $query->where('year_budget', $year); }
    public function scopeByPeriode($query, $periode)  { return $query->where('periode_slip', $periode); }
    public function scopeByKaryawan($query, $id)      { return $query->where('karyawan_id', $id); }
    public function scopeApproved($query)             { return $query->where('status', true); }
    public function scopePending($query)              { return $query->where('status', false); }
}