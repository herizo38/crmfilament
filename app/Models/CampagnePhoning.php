<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampagnePhoning extends Model
{
    use SoftDeletes;

    protected $table = 'campagne_phonings';

    protected $fillable = [
        'nom',
        'description',
        'statut',
        'type_entite',
        'criteres',
        'date_debut',
        'date_fin',
        'user_id',
        'entite_id',
    ];

    protected $casts = [
        'criteres' => 'array',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    public const STATUTS = [
        'brouillon' => 'Brouillon',
        'active' => 'Active',
        'terminee' => 'Terminée',
    ];

    public const TYPES_ENTITE = [
        'prospects' => 'Prospects',
        'partenaires' => 'Partenaires',
        'clients' => 'Clients',
    ];

    // ── Relations ────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function entite()
    {
        return $this->belongsTo(EntiteCommerciale::class, 'entite_id');
    }

    public function appels()
    {
        return $this->hasMany(Appel::class, 'campagne_id');
    }

    public function scripts()
    {
        return $this->hasMany(ScriptAppel::class, 'campagne_id');
    }

    // ── Statistiques campagne ─────────────────────────────────────────

    public function getStats(): array
    {
        $totalContacts = $this->countContacts();
        $appels = $this->appels();
        $totalAppels = $appels->count();

        $contactsTraites = $appels->distinct('appelable_id')->count('appelable_id');

        $parStatut = $appels->selectRaw('phoning_status, COUNT(*) as total')
            ->groupBy('phoning_status')
            ->pluck('total', 'phoning_status')
            ->toArray();

        $progression = $totalContacts > 0
            ? round(($contactsTraites / $totalContacts) * 100, 1)
            : 0;

        return [
            'total_contacts' => $totalContacts,
            'contacts_traites' => $contactsTraites,
            'contacts_restants' => max(0, $totalContacts - $contactsTraites),
            'total_appels' => $totalAppels,
            'progression' => $progression,
            'par_statut' => $parStatut,
        ];
    }

    public function estTerminee(): bool
    {
        $totalContacts = $this->countContacts();
        if ($totalContacts === 0) {
            return false;
        }

        $contactsTraites = $this->appels()
            ->distinct('appelable_id')
            ->count('appelable_id');

        return $contactsTraites >= $totalContacts;
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('statut', 'active')
            ->where(fn ($q) => $q
                ->whereNull('date_debut')
                ->orWhere('date_debut', '<=', now()->toDateString())
            )
            ->where(fn ($q) => $q
                ->whereNull('date_fin')
                ->orWhere('date_fin', '>=', now()->toDateString())
            );
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        // user_id null = campagne ouverte à tous les agents
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)->orWhereNull('user_id');
        });
    }

    // ── Accesseurs ───────────────────────────────────────────────────

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getStatutColorAttribute(): string
    {
        return match ($this->statut) {
            'active' => 'success',
            'terminee' => 'gray',
            default => 'warning',
        };
    }

    public function getTypeEntiteLabelAttribute(): string
    {
        return self::TYPES_ENTITE[$this->type_entite] ?? $this->type_entite;
    }

    // ── Requête de contacts ──────────────────────────────────────────

    /**
     * Retourne la requête Eloquent filtrée selon les critères de la campagne.
     */
    public function buildQuery(): Builder
    {
        $criteres = $this->criteres ?? [];

        return match ($this->type_entite) {
            'prospects' => $this->buildProspectsQuery($criteres),
            'partenaires' => $this->buildPartenairesQuery($criteres),
            'clients' => $this->buildClientsQuery($criteres),
            default => throw new \InvalidArgumentException("type_entite inconnu : {$this->type_entite}"),
        };
    }

    /**
     * Retourne les IDs des contacts de la campagne sous forme de tableau
     * [['type' => '...', 'id' => ..., 'campagne_id' => ...], ...]
     */
    public function getContactsQueue(): array
    {
        $type = match ($this->type_entite) {
            'partenaires' => 'partenaire',
            'clients' => 'client',
            default => 'prospect',
        };

        return $this->buildQuery()
            ->pluck('id')
            ->map(fn ($id) => ['type' => $type, 'id' => $id, 'campagne_id' => $this->id])
            ->toArray();
    }

    public function countContacts(): int
    {
        return $this->buildQuery()->count();
    }

    // ── Constructeurs de requêtes par entité ─────────────────────────

    protected function buildProspectsQuery(array $c): Builder
    {
        $q = Prospect::query()->whereNull('deleted_at');

        if (is_array($c['statuts'] ?? null) && count($c['statuts']) > 0) {
            $q->whereIn('statut', $c['statuts']);
        }
        if (! empty($c['departement'])) {
            $q->where('departement', $c['departement']);
        }
        if (! empty($c['secteur_activite'])) {
            $q->where('secteur_activite', 'like', '%'.$c['secteur_activite'].'%');
        }
        if (isset($c['nb_salaries_min']) && $c['nb_salaries_min'] !== '') {
            $q->where('nb_salaries', '>=', (int) $c['nb_salaries_min']);
        }
        if (isset($c['nb_salaries_max']) && $c['nb_salaries_max'] !== '') {
            $q->where('nb_salaries', '<=', (int) $c['nb_salaries_max']);
        }
        if (! empty($c['type_pressenti'])) {
            $q->where('type_pressenti', $c['type_pressenti']);
        }

        return $q;
    }

    protected function buildPartenairesQuery(array $c): Builder
    {
        // On charge les ContactPartenaire dont le Partenaire parent correspond aux critères,
        // pour rester compatible avec le type 'partenaire' du PhoningWorkflow.
        return ContactPartenaire::query()
            ->whereNull('deleted_at')
            ->whereHas('partenaire', function ($q) use ($c) {
                $q->whereNull('deleted_at');
                if (is_array($c['statuts'] ?? null) && count($c['statuts']) > 0) {
                    $q->whereIn('statut', $c['statuts']);
                }
                if (! empty($c['departement'])) {
                    $q->where('departement', $c['departement']);
                }
                if (! empty($c['type'])) {
                    $q->where('type', $c['type']);
                }
                if (! empty($c['secteur_activite'])) {
                    $q->where('secteur_activite', 'like', '%'.$c['secteur_activite'].'%');
                }
            });
    }

    protected function buildClientsQuery(array $c): Builder
    {
        $q = Client::query()->whereNull('deleted_at');

        if (! empty($c['etat'])) {
            $q->where('etat', $c['etat']);
        }
        if (! empty($c['departement'])) {
            $q->where('departement', $c['departement']);
        }
        if (! empty($c['type_tiers'])) {
            $q->where('type_tiers', $c['type_tiers']);
        }
        // Toujours exclure les clients "ne plus contacter"
        $q->where(fn ($sub) => $sub->whereNull('ne_plus_contacter')->orWhere('ne_plus_contacter', false));

        return $q;
    }
}
