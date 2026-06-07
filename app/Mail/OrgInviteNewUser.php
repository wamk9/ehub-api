<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrgInviteNewUser extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $orgName,
        public string $orgRoute,
        public string $inviterName,
        public string $role,
        public string $inviteToken,
        public string $invitedEmail,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Você foi convidado para gerenciar {$this->orgName} — eHub",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.org-invite-new-user',
            with: ['registerUrl' => $this->registerUrl()],
        );
    }

    public function registerUrl(): string
    {
        $base = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');

        return $base.'/register?'.http_build_query([
            'invite' => $this->inviteToken,
            'email' => $this->invitedEmail,
            'org' => $this->orgRoute,
        ]);
    }
}
