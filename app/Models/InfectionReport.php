<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfectionReport extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'test_date', 'test_picture', 'notified_contacts'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

