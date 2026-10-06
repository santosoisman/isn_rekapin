<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invitation $invitation)
    {
    }

    public function build()
    {
        $url = route('invitations.accept', $this->invitation->token);

        return $this->subject('Undangan Kolaborasi Buku Keuangan: ' . $this->invitation->book->name)
            ->view('emails.invitation', [
                'invitation' => $this->invitation,
                'url' => $url,
            ]);
    }
}
