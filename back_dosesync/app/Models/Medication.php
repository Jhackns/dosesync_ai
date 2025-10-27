<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    use HasFactory;

    public $timestamps = false;

    // Permitir creación/actualización por asignación masiva
    protected $fillable = [
        'user_id',
        'name',
        'dosage_text',
        'frequency_type',
        'start_date',
        'end_date',
        'interaction_rule',
        'created_at',
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function doseLogs()
    {
        return $this->hasMany(DoseLog::class);
    }
}
