<?php

namespace App\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Member $member,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your Password | MLM Book',
        );
    }

    public function content(): Content
    {
        $resetUrl = route('member.password.reset', [
            'token' => $this->token,
            'email' => $this->member->email,
        ]);

        return new Content(
            view: 'emails.member-reset-password',
            with: [
                'memberName' => $this->member->name,
                'resetUrl' => $resetUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
