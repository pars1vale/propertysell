<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HouseFacility extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'house_id',
        'facility_id',
    ];

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }
    public function facilities()
    {
        return $this->belongsTo(facilities::class, 'facility_id');
    }
}
