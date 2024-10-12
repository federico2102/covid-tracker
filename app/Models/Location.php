<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'address', 'geolocation', 'max_capacity', 'current_people',
        'optional_details', 'picture', 'qr_code'
    ];

    // Generate a QR code for the location and store it
    public function generateQrCode(): void
    {
        $qrCodeContent = route('locations.show', $this->id);  // Link to the location
        $qrCodePath = 'qrcodes/' . $this->id . '.png';

        // Generate and save the QR code
        QrCode::format('png')->size(300)->margin(1)->generate($qrCodeContent, storage_path('app/public/' . $qrCodePath));

        $this->qr_code = '/storage/' . $qrCodePath;
        $this->save();
    }

    // Handle picture upload
    public static function handlePictureUpload($request)
    {
        if ($request->hasFile('picture')) {
            return $request->file('picture')->store('pictures', 'public');
        }
        return null;
    }

    // Increment the current people count
    public function incrementPeople(): void
    {
        $this->increment('current_people');
    }

    // Decrement the current people count
    public function decrementPeople(): void
    {
        $this->decrement('current_people');
    }

    public function isFull(): bool
    {
        return $this->current_people >= $this->max_capacity;
    }
}

