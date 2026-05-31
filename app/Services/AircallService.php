<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AircallService
{
    private string $baseUrl;
    private string $apiId;
    private string $apiToken;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('aircall.base_url');
        $this->apiId = config('aircall.api_id');
        $this->apiToken = config('aircall.api_token');
        $this->timeout = config('aircall.timeout');
    }

    // ── HTTP client ────────────────────────────────────────────────
    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withBasicAuth($this->apiId, $this->apiToken)
            ->timeout($this->timeout)
            ->baseUrl($this->baseUrl)
            ->acceptJson();
    }

    // ── Calls ──────────────────────────────────────────────────────
    public function getCalls(array $filters = []): array
    {
        return Cache::remember(
            'aircall_calls_' . md5(serialize($filters)),
            now()->addMinutes(2),
            fn() => $this->client()->get('/calls', $filters)->json('calls', [])
        );
    }

    public function getAllCalls(int $perPage = 50, int $page = 1): array
    {
        return Cache::remember(
            "aircall_all_calls_{$page}",
            now()->addMinutes(2),
            fn() => $this->client()->get('/calls', [
                'per_page' => $perPage,
                'page' => $page,
                'order' => 'desc', // du plus récent au plus ancien
            ])->json('calls', [])
        );
    }


    public function getCallsToday(): array
    {
        return $this->getCalls([
            'from' => now()->startOfDay()->timestamp,
            'to' => now()->endOfDay()->timestamp,
        ]);
    }

    public function getCall(string $callId): ?array
    {
        try {
            return $this->client()->get("/calls/{$callId}")->json('call');
        } catch (\Exception $e) {
            Log::error('Aircall getCall error', ['id' => $callId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    // ── Users ──────────────────────────────────────────────────────
    public function getUsers(): array
    {
        return Cache::remember('aircall_users', now()->addMinutes(10), function () {
            return $this->client()->get('/users')->json('users', []);
        });
    }

    // ── Stats ──────────────────────────────────────────────────────
    public function getStats(?int $from = null, ?int $to = null): array
    {
        // Récupérer TOUTES les pages pour avoir l'historique complet
        $allCalls = [];
        $page = 1;

        do {
            $filters = ['per_page' => 50, 'page' => $page, 'order' => 'desc'];
            if ($from)
                $filters['from'] = $from;
            if ($to)
                $filters['to'] = $to;

            $calls = Cache::remember(
                'aircall_stats_p' . $page . '_' . md5(serialize($filters)),
                now()->addMinutes(10),
                fn() => $this->client()->get('/calls', $filters)->json('calls', [])
            );

            $allCalls = array_merge($allCalls, $calls);
            $page++;

        } while (count($calls) === 50 && $page <= 20); // max 20 pages = 1000 appels

        $collection = collect($allCalls);

        $total = $collection->count();
        $entrants = $collection->where('direction', 'inbound')->count();
        $sortants = $collection->where('direction', 'outbound')->count();

        // ✅ Tous les appels répondus (entrants ET sortants)
        $repondus = $collection->whereIn('status', ['answered', 'done'])->count();

        // ✅ Manqués = missed_customer (entrant non décroché) + missed (sortant sans réponse)
        $manques = $collection->whereIn('status', ['missed_customer', 'missed'])->count();
        $manquesEntrants = $collection->where('direction', 'inbound')
            ->whereIn('status', ['missed_customer', 'missed'])->count();

        $dureeTotale = $collection->sum('duration');

        return [
            'total' => $total,
            'entrants' => $entrants,
            'sortants' => $sortants,
            'manques' => $manques,
            'manques_entrants' => $manquesEntrants,
            'repondus' => $repondus,
            'duree_totale' => $dureeTotale,
            'duree_moyenne' => $total > 0 ? (int) round($dureeTotale / $total) : 0,
            'taux_reponse' => $total > 0 ? round(($repondus / $total) * 100, 1) : 0,
        ];
    }

    // ── Santé de la connexion ──────────────────────────────────────
    public function testConnection(): bool
    {
        try {
            $response = $this->client()->get('/ping');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}