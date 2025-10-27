<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoseLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'medication_id',
        'scheduled_at',
        'taken_at',
        'status',
        'skip_reason',
        'gemini_classification',
        'created_at',
    ];

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }

    public function symptomLog()
    {
        return $this->hasOne(SymptomLog::class);
    }
}
