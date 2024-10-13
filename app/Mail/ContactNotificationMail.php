<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contactedUser;
    public $infectedUser;
    public $sharedLocation;
    public $sharedCheckinTime;

    /**
     * Create a new message instance.
     *
     * @param $contactedUser
     * @param $infectedUser
     * @param $sharedLocation
     * @param $sharedCheckinTime
     */
    public function __construct($contactedUser, $infectedUser, $sharedLocation, $sharedCheckinTime)
    {
        $this->contactedUser = $contactedUser;
        $this->infectedUser = $infectedUser;
        $this->sharedLocation = $sharedLocation;
        $this->sharedCheckinTime = $sharedCheckinTime;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Contact Notification: COVID-19 Exposure')
            ->view('emails.contact_notification')
            ->with([
                'contactedUser' => $this->contactedUser,
                'infectedUser' => $this->infectedUser,
                'sharedLocation' => $this->sharedLocation,
                'sharedCheckinTime' => $this->sharedCheckinTime,
            ]);
    }
}
