<?php

namespace App\Models;

use App\Enums\StatutDevis;
use App\Enums\TauxTVA;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * Devis
 *
 * Proposition commerciale émise par un artisan vers un client,
 * liée à une Affaire/Ticket. Peut évoluer vers un BonDeCommande si accepté.
 *
 * Chaîne documentaire : Devis → BonDeCommande → Facture
 *
 * @property int         $id
 * @property string      $numero                  Format DEV-AAAA-NNNN (auto)
 * @property int         $ticket_id               FK → Ticket (Affaire)
 * @property int         $artisan_id              FK → Artisan (émetteur)
 * @property int         $contact_particulier_id  FK → ContactParticulier (destinataire)
 * @property array       $lignes                  JSON : [{libelle, quantite, prix_unitaire_ht, taux_tva}]
 * @property float       $remise_montant          Remise en € (avant TVA)
 * @property float       $remise_pourcentage      Remise en % (avant TVA)
 * @property string      $conditions_paiement     Acompte / Solde à intervention / 30j
 * @property string|null $notes                   Conditions spécifiques
 * @property \Carbon\Carbon $date_validite         Par défaut J+30
 * @property StatutDevis $statut                  Brouillon / Envoyé / Accepté / Refusé / Expiré
 * @property string|null $mode_acceptation        Signature électronique / Appel / Email
 * @property \Carbon\Carbon|null $date_acceptation_refus  Horodatée à la signature
 * @property float       $total_ht                Calculé
 * @property float       $montant_tva             Calculé
 * @property float       $total_ttc               Calculé
 */
class Devis extends Model
{
    use SoftDeletes;

    protected $table = 'devis';

    protected $casts = [
        'statut'                  => StatutDevis::class,
        'lignes'                  => 'array',
        'date_validite'           => 'date',
        'date_acceptation_refus'  => 'datetime',
        'date_emission'           => 'date',
        'remise_montant'          => 'decimal:2',
        'remise_pourcentage'      => 'decimal:2',
        'total_ht'                => 'decimal:2',
        'montant_tva'             => 'decimal:2',
        'total_ttc'               => 'decimal:2',
    ];

    protected $fillable = [
        'numero',
        'ticket_id',
        'artisan_id',
        'contact_particulier_id',
        'lignes',
        'remise_montant',
        'remise_pourcentage',
        'conditions_paiement',
        'notes',
        'date_validite',
        'statut',
        'mode_acceptation',
        'date_acceptation_refus',
        'total_ht',
        'montant_tva',
        'total_ttc',
    ];

    // ── Constantes ──────────────────────────────────────────────────

    /** Durée de validité par défaut en jours */
    const VALIDITE_DEFAUT_JOURS = 30;

    const CONDITIONS_PAIEMENT = [
        'acompte'              => 'Acompte à la commande',
        'solde_intervention'   => 'Solde à l\'intervention',
        '30_jours'             => 'Paiement à 30 jours',
    ];

    const MODES_ACCEPTATION = [
        'signature_electronique' => 'Signature électronique',
        'appel'                  => 'Appel téléphonique',
        'email'                  => 'Email',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────

    public function getStatutLabelAttribute(): string
    {
        return $this->statut->label();
    }

    public function getStatutColorAttribute(): string
    {
        return $this->statut->color();
    }

    public function getStatutIconAttribute(): string
    {
        return $this->statut->icon();
    }

    public function getEstAccepteAttribute(): bool
    {
        return $this->statut === StatutDevis::Accepte;
    }

    public function getEstRefuseAttribute(): bool
    {
        return $this->statut === StatutDevis::Refuse;
    }

    public function getEstExpireAttribute(): bool
    {
        return $this->statut === StatutDevis::Expire ||
            ($this->statut === StatutDevis::Envoye && $this->date_validite->isPast());
    }

    public function getEstEnAttenteAttribute(): bool
    {
        return $this->statut === StatutDevis::Envoye && !$this->getEstExpireAttribute();
    }

    public function getJoursAvantExpirationAttribute(): int
    {
        if ($this->date_validite->isPast()) {
            return 0;
        }
        return now()->diffInDays($this->date_validite);
    }

    public function getConditionsPaiementLabelAttribute(): string
    {
        return self::CONDITIONS_PAIEMENT[$this->conditions_paiement] ?? $this->conditions_paiement;
    }

    public function getModeAcceptationLabelAttribute(): string
    {
        return self::MODES_ACCEPTATION[$this->mode_acceptation] ?? ($this->mode_acceptation ?? 'Non renseigné');
    }

    // ── Calculs financiers ───────────────────────────────────────────

    /**
     * Recalcule et persiste total_ht, montant_tva, total_ttc depuis les lignes.
     * Applique la remise avant TVA.
     */
    public function recalculerTotaux(): void
    {
        $totalHtBrut = 0.0;
        $totalTva    = 0.0;

        foreach ($this->lignes ?? [] as $ligne) {
            $ht = ($ligne['quantite'] ?? 1) * ($ligne['prix_unitaire_ht'] ?? 0);
            $totalHtBrut += $ht;
        }

        // Application de la remise
        $remiseMontant = $this->remise_montant ?? 0;
        if ($this->remise_pourcentage && $this->remise_pourcentage > 0) {
            $remiseMontant = max($remiseMontant, $totalHtBrut * ($this->remise_pourcentage / 100));
        }
        $totalHtNet = max(0, $totalHtBrut - $remiseMontant);

        // TVA par ligne (proportionnelle à la remise)
        $ratioRemise = $totalHtBrut > 0 ? ($totalHtNet / $totalHtBrut) : 1;
        foreach ($this->lignes ?? [] as $ligne) {
            $ht  = ($ligne['quantite'] ?? 1) * ($ligne['prix_unitaire_ht'] ?? 0) * $ratioRemise;
            $tva = $ht * (($ligne['taux_tva'] ?? 20) / 100);
            $totalTva += $tva;
        }

        $this->update([
            'total_ht'    => round($totalHtNet, 2),
            'montant_tva' => round($totalTva, 2),
            'total_ttc'   => round($totalHtNet + $totalTva, 2),
        ]);
    }

    // ── Méthodes métier ─────────────────────────────────────────────

    public function envoyer(): void
    {
        if ($this->statut !== StatutDevis::Brouillon) {
            throw new \Exception("Seul un devis en brouillon peut être envoyé.");
        }
        $this->update([
            'statut'         => StatutDevis::Envoye,
            'date_emission'  => now(),
        ]);
    }

    public function accepter(string $mode = 'appel'): BonDeCommande
    {
        if (!in_array($this->statut, [StatutDevis::Envoye, StatutDevis::Brouillon])) {
            throw new \Exception("Ce devis ne peut plus être accepté (statut : {$this->statut->value}).");
        }

        $this->update([
            'statut'                 => StatutDevis::Accepte,
            'mode_acceptation'       => $mode,
            'date_acceptation_refus' => now(),
        ]);

        // Génération automatique du bon de commande
        return $this->genererBonDeCommande();
    }

    public function refuser(string $motif = null): void
    {
        $this->update([
            'statut'                 => StatutDevis::Refuse,
            'date_acceptation_refus' => now(),
            'notes'                  => $motif
                ? ($this->notes ? $this->notes . "\n[Refus] {$motif}" : "[Refus] {$motif}")
                : $this->notes,
        ]);
    }

    public function marquerExpire(): void
    {
        if ($this->statut === StatutDevis::Envoye) {
            $this->update(['statut' => StatutDevis::Expire]);
        }
    }

    protected function genererBonDeCommande(): BonDeCommande
    {
        return BonDeCommande::create([
            'numero'                 => BonDeCommande::genererNumero(),
            'devis_id'               => $this->id,
            'ticket_id'              => $this->ticket_id,
            'artisan_id'             => $this->artisan_id,
            'contact_particulier_id' => $this->contact_particulier_id,
            'lignes'                 => $this->lignes,
            'montant_total_ttc'      => $this->total_ttc,
            'conditions_paiement'    => $this->conditions_paiement,
            'statut'                 => 'en_attente',
        ]);
    }

    public static function genererNumero(): string
    {
        $annee    = now()->year;
        $dernierN = static::whereYear('created_at', $annee)->count() + 1;
        return 'DEV-' . $annee . '-' . str_pad($dernierN, 4, '0', STR_PAD_LEFT);
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeBrouillons($query): Builder
    {
        return $query->where('statut', StatutDevis::Brouillon);
    }

    public function scopeEnvoyes($query): Builder
    {
        return $query->where('statut', StatutDevis::Envoye);
    }

    public function scopeAcceptes($query): Builder
    {
        return $query->where('statut', StatutDevis::Accepte);
    }

    public function scopeRefuses($query): Builder
    {
        return $query->where('statut', StatutDevis::Refuse);
    }

    public function scopeExpires($query): Builder
    {
        return $query->where('statut', StatutDevis::Expire);
    }

    public function scopeEnAttente($query): Builder
    {
        return $query->where('statut', StatutDevis::Envoye)
            ->where('date_validite', '>=', now());
    }

    public function scopeARelancer($query): Builder
    {
        // Devis envoyés, non expirés, sans réponse depuis > 3 jours
        return $query->enAttente()
            ->where('updated_at', '<', now()->subDays(3));
    }

    public function scopeExpiresBientot($query, int $jours = 7): Builder
    {
        return $query->enAttente()
            ->whereBetween('date_validite', [now(), now()->addDays($jours)]);
    }

    public function scopeDuMois($query): Builder
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    public function scopeParArtisan($query, int $artisanId): Builder
    {
        return $query->where('artisan_id', $artisanId);
    }

    // ── KPIs ────────────────────────────────────────────────────────

    public static function getKpis(): array
    {
        return [
            'total'             => static::count(),
            'en_attente'        => static::enAttente()->count(),
            'acceptes_mois'     => static::acceptes()->duMois()->count(),
            'refuses_mois'      => static::refuses()->duMois()->count(),
            'expires'           => static::expires()->count(),
            'taux_acceptation'  => static::getTauxAcceptation(),
            'montant_pipeline'  => static::enAttente()->sum('total_ttc'),
            'a_relancer'        => static::aRelancer()->count(),
        ];
    }

    public static function getTauxAcceptation(): float
    {
        $total = static::whereIn('statut', [
            StatutDevis::Accepte->value,
            StatutDevis::Refuse->value,
        ])->count();

        if ($total === 0) {
            return 0;
        }

        return round((static::acceptes()->count() / $total) * 100, 1);
    }

    // ── Boot ────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Devis $devis) {
            if (empty($devis->numero)) {
                $devis->numero = static::genererNumero();
            }
            if (empty($devis->statut)) {
                $devis->statut = StatutDevis::Brouillon;
            }
            if (empty($devis->date_validite)) {
                $devis->date_validite = now()->addDays(self::VALIDITE_DEFAUT_JOURS);
            }
        });

        static::created(function (Devis $devis) {
            $devis->recalculerTotaux();
        });

        static::updated(function (Devis $devis) {
            if ($devis->isDirty('lignes') || $devis->isDirty('remise_montant') || $devis->isDirty('remise_pourcentage')) {
                $devis->recalculerTotaux();
            }
            // Expiration automatique
            if (
                $devis->isDirty('statut') === false &&
                $devis->statut === StatutDevis::Envoye &&
                $devis->date_validite->isPast()
            ) {
                $devis->marquerExpire();
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function artisan()
    {
        return $this->belongsTo(Artisan::class);
    }

    public function contactParticulier()
    {
        return $this->belongsTo(ContactParticulier::class);
    }

    public function bonDeCommande()
    {
        return $this->hasOne(BonDeCommande::class);
    }
}
