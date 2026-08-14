<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'magasins')]
class Magasin extends Model
{
    protected $fillable = [
        'name',
        'type',
        'adresse',
        'telephone',
        'email',
        'active',
        'depot_central'
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
