<?php

namespace App\Services;

use RuntimeException;
use Twilio\Rest\Client;

class SmsService
{
    private string $sid;

    private string $token;

    private string $fromNumber;

    public function __construct()
    {
        $this->sid = trim((string) config('services.twilio.sid'));
        $this->token = trim((string) config('services.twilio.token'));
        $this->fromNumber = trim((string) config('services.twilio.from'));
    }

    public function sendOtp(string $mobileNumber, string $otp): void
    {
        if ($this->sid === '' || $this->token === '' || $this->fromNumber === '') {
            throw new RuntimeException('Twilio SMS configuration is incomplete.');
        }

        $client = new Client($this->sid, $this->token);

        $client->messages->create($mobileNumber, [
            'from' => $this->fromNumber,
            'body' => "Your MLM Book verification code is {$otp}. It expires in 10 minutes.",
        ]);
    }
}
