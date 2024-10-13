<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'infection_report_id', 'type', 'message', 'is_read'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function infectionReport(): BelongsTo
    {
        return $this->belongsTo(InfectionReport::class);
    }
}


