<?php

namespace App\Console\Commands;

use App\Enums\EventResult;
use App\Enums\EventType;
use App\Models\Appel;
use App\Models\User;
use App\Services\AircallService;
use Illuminate\Console\Command;

class SyncAircallCalls extends Command
{
    protected $signature = 'aircall:sync
                            {--pages=5 : Nombre de pages à récupérer}
                            {--per-page=50 : Appels par page}
                            {--from= : Timestamp de début (optionnel)}';

    protected $description = 'Synchronise les appels Aircall vers la base de données';

    public function handle(AircallService $aircall): int
    {
        $pages = (int) $this->option('pages');
        $perPage = (int) $this->option('per-page');
        $from = $this->option('from');

        $this->info("Synchronisation Aircall — {$pages} pages x {$perPage} appels...");

        // Charger les users Aircall pour faire le lien avec User local
        $aircallUsers = collect($aircall->getUsers())
            ->keyBy('id'); // indexed par aircall user id

        $synced = 0;
        $skipped = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($pages * $perPage);
        $bar->start();

        for ($page = 1; $page <= $pages; $page++) {
            $filters = ['per_page' => $perPage, 'page' => $page, 'order' => 'desc'];

            if ($from) {
                $filters['from'] = $from;
            }

            $calls = $aircall->getCalls($filters);

            if (empty($calls)) {
                break; // Plus de données
            }

            foreach ($calls as $call) {
                try {
                    $result = $this->syncCall($call, $aircallUsers);
                    $result ? $synced++ : $skipped++;
                } catch (\Exception $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("Erreur call {$call['id']}: {$e->getMessage()}");
                }

                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->table(
            ['Synchronisés', 'Déjà existants', 'Erreurs'],
            [[$synced, $skipped, $errors]]
        );

        return self::SUCCESS;
    }

    private function syncCall(array $call, \Illuminate\Support\Collection $aircallUsers): bool
    {
        if (Appel::where('aircall_call_id', (string) $call['id'])->exists()) {
            return false;
        }

        $userId = null;
        $agentNom = null;

        if (!empty($call['user']['id'])) {
            // ✅ 1. Chercher par aircall_user_id (mapping explicite)
            $localUser = User::where('aircall_user_id', (string) $call['user']['id'])->first();

            if ($localUser) {
                $userId = $localUser->id;
                $agentNom = "{$localUser->prenom} {$localUser->nom}";
            } else {
                // ✅ 2. Pas de mapping → stocker le nom Aircall tel quel
                $aircallUser = $aircallUsers->get($call['user']['id']);
                $agentNom = $aircallUser['name'] ?? "Agent #{$call['user']['id']}";
                // user_id reste null — pas de fallback User::first()
            }
        }

        $resultat = $this->mapStatut($call['status'] ?? '');
        $type = ($call['direction'] ?? '') === 'inbound'
            ? EventType::Permanence
            : EventType::Appel;

        Appel::create([
            'aircall_call_id' => (string) $call['id'],
            'aircall_user_id' => $call['user']['id'] ?? null,
            'aircall_number_id' => $call['number']['id'] ?? null,
            'aircall_agent_nom' => $agentNom,             // ✅ Nom Aircall toujours stocké
            'user_id' => $userId,               // ✅ Null si pas de mapping local
            'type' => $type,
            'resultat' => $resultat,
            'date_heure' => \Carbon\Carbon::createFromTimestamp($call['started_at']),
            'duree_secondes' => $call['duration'] ?? null,
            'direction' => $call['direction'] ?? null,
            'numero_appelant' => $call['raw_digits'] ?? null,
            'enregistrement_audio' => $call['recording'] ?? null,
            'commentaire' => $call['comments'][0]['content'] ?? null,
        ]);

        return true;
    }

    private function mapStatut(string $aircallStatus): ?EventResult
    {
        return match ($aircallStatus) {
            'answered', 'done' => EventResult::Realise,
            'missed_customer' => EventResult::NonAbouti,
            'voicemail' => EventResult::Rappel,
            'blocked', 'abandoned' => EventResult::Annule,
            default => null,
        };
    }
}