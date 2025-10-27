<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SymptomLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'dose_log_id',
        'symptom_name',
        'severity',
        'reported_at',
    ];

    public function doseLog()
    {
        return $this->belongsTo(DoseLog::class);
    }
}
