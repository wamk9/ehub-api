<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrgInviteExistingUser extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $orgName,
        public string $orgRoute,
        public string $inviterName,
        public string $role,
        public string $inviteToken,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Você foi convidado para gerenciar {$this->orgName} — eHub",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.org-invite-existing-user',
            with: ['acceptUrl' => $this->acceptUrl()],
        );
    }

    public function acceptUrl(): string
    {
        $base = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');

        return $base . '/invite/accept/' . $this->inviteToken;
    }
}
