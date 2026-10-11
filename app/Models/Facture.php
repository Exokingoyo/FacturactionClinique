<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    protected $fillable = ['numero', 'offline_uuid', 'patient_nom', 'patient_telephone', 'total', 'user_id'];

    public function lignes()
    {
        return $this->hasMany(FactureLigne::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
