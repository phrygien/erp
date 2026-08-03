<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'fournisseurs')]
class Fournisseur extends Model
{
    protected $fillable = [
        'name',
        'code',
        'raison_social',
        'adresse_siege',
        'code_postal',
        'ville',
        'telephone',
        'fax',
        'email',
        'adresse_retour',
        'code_postal_retour',
        'ville_retour',
        'state',
    ];
}
