<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caregiver extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'patient_id',
        'caregiver_user_id',
        'created_at',
    ];
}
