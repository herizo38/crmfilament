<?php

namespace App\Jobs;

use App\Mail\WeeklyReportMail;
use App\Models\User;
use App\Services\Crm\WeeklyReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie le rapport hebdomadaire aux téléprospecteurs et commerciaux (CDC WF5 / WF6).
 */
class SendWeeklyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $roles  Rôles destinataires (par défaut télépros + commercial).
     */
    public function __construct(public array $roles = [
        User::ROLE_TELEPROSPECTEUR,
        User::ROLE_COMMERCIAL,
    ]) {}

    public function handle(WeeklyReportService $service): int
    {
        $envoyes = 0;

        foreach ($this->roles as $role) {
            foreach ($service->destinataires($role) as $user) {
                $rapport = $role === User::ROLE_TELEPROSPECTEUR
                    ? $service->pourTeleprospecteur($user)
                    : $service->pourCommercial($user);

                Mail::to($user->email)->send(new WeeklyReportMail($rapport));
                $envoyes++;
            }
        }

        Log::info("Rapport hebdomadaire CRM envoyé à {$envoyes} destinataire(s).");

        return $envoyes;
    }
}
