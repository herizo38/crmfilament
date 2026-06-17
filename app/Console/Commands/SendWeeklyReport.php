<?php

namespace App\Console\Commands;

use App\Jobs\SendWeeklyReportJob;
use App\Models\User;
use App\Services\Crm\WeeklyReportService;
use Illuminate\Console\Command;

class SendWeeklyReport extends Command
{
    protected $signature = 'crm:weekly-report
                            {--roles= : Rôles ciblés, séparés par des virgules (défaut: teleprospecteur,commercial)}
                            {--sync : Exécuter immédiatement sans passer par la file}';

    protected $description = 'Envoie le rapport hebdomadaire CRM aux téléprospecteurs et commerciaux';

    public function handle(): int
    {
        $roles = $this->option('roles')
            ? array_map('trim', explode(',', (string) $this->option('roles')))
            : [User::ROLE_TELEPROSPECTEUR, User::ROLE_COMMERCIAL];

        $job = new SendWeeklyReportJob($roles);

        if ($this->option('sync')) {
            $envoyes = $job->handle(app(WeeklyReportService::class));
            $this->info("Rapport hebdomadaire envoyé à {$envoyes} destinataire(s).");

            return self::SUCCESS;
        }

        dispatch($job);
        $this->info('Rapport hebdomadaire mis en file pour les rôles : '.implode(', ', $roles));

        return self::SUCCESS;
    }
}
