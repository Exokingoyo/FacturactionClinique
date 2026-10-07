<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactureLigne extends Model
{
    protected $fillable = ['facture_id', 'designation', 'prix', 'quantite'];
}
