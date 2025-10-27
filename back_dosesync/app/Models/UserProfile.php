<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'full_name',
        'date_of_birth',
        'phone_number',
        'address',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
