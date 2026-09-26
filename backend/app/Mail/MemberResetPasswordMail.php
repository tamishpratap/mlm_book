<?php

namespace App\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Route;

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
        $frontendUrl = rtrim((string) (config('app.frontend_url') ?: config('app.url', 'https://mlmbookai.com')), '/');
        $resetUrl = $frontendUrl . '/member/reset-password/' . $this->token . '?email=' . urlencode($this->member->email);

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
