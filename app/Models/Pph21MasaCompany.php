<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pph21MasaCompany extends Model
{
    use HasFactory;
    protected $table = 'pph21_masa_company_periode';
    
   
    public $timestamps = false;
    
    /**
     * Guarded attributes
     */
    protected $guarded = ['id'];
    
}
