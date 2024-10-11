<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'name',
        'address',
        'geolocation',
        'max_capacity',
        'current_people',
        'qr_code',
        'optional_details',
        'picture'];

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }
}

