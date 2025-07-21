<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MortgageRequest extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'house_id',
        'duration',
        'bank_name',
        'interest',
        'interest_id',
        'dp_total_amount',
        'loan_total_amount',
        'monthly_amount',
        'dp_percentage',
        'status',
        'document',
        'house_price',
        'loan_interest_total_amount',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }
    public function installments()
    {
        return $this->hasMany(Installment::class);
    }
}
