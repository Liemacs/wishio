<?php

namespace App\Domain\Reminders\Mail;

use App\Domain\Reminders\DTOs\DigestEntry;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class WeeklyDigest extends Mailable
{
    use Queueable;

    /** @param Collection<int, DigestEntry> $entries */
    public function __construct(
        public readonly User $user,
        public readonly Collection $entries,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: trans_choice('wishio.digest.subject', $this->entries->count(),
                ['count' => $this->entries->count()], $this->user->locale),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.digest',
            with: [
                'locale' => $this->user->locale,
                // Link semnat: dezabonarea trebuie să funcționeze dintr-un
                // click, fără autentificare. Cerință de bun-simț și de lege.
                'unsubscribeUrl' => URL::signedRoute('digest.unsubscribe', ['user' => $this->user->id]),
            ],
        );
    }
}
