<?php

namespace App\Services;

use App\Mail\WelcomeMail;
use App\Models\Agent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SmsService
{
    public function sendPassword(Agent $agent, string $password): void
    {
        $this->sendEmail($agent, $password);
        $this->sendSms($agent->telephone, $password);
    }

    private function sendEmail(Agent $agent, string $password): void
    {
        try {
            Mail::send(new WelcomeMail($agent, $password));
        } catch (\Throwable $e) {
            Log::warning("Envoi d'email echoue pour {$agent->email} : " . $e->getMessage());
        }
    }

    private function sendSms(string $telephone, string $password): void
    {
        if (empty($telephone)) return;

        Log::info("SMS à {$telephone} : Mot de passe = {$password}");
    }
}
