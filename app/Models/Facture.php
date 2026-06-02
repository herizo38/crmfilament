<?php

namespace App\Models;

use App\Enums\StatutPaiement;
use App\Enums\ModePaiement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

/**
 * Facture
 *
 * Document légal post-intervention. Émis par l'artisan après intervention réalisée.
 * Liée au BonDeCommande et à l'Affaire. Déclenche le suivi de paiement.
 * Le numéro est une séquence chronologique OBLIGATOIRE (conformité légale).
 *
 * Chaîne documentaire : Devis → BonDeCommande → Facture
 *
 * @property int         $id
 * @property string      $numero                   Format FAC-AAAA-NNNN — séquence chronologique (obligatoire légalement)
 * @property int         $bon_de_commande_id        FK → BonDeCommande
 * @property int         $ticket_id                FK → Ticket (Affaire)
 * @property int         $artisan_id               FK → Artisan (SIRET obligatoire)
 * @property int         $contact_particulier_id   FK → ContactParticulier (client facturé)
 * @property array       $lignes                   [{libelle, quantite, prix_unitaire_ht, taux_tva}]
 * @property float       $total_ht                 Calculé
 * @property float       $montant_tva              Calculé
 * @property float       $total_ttc                Calculé
 * @property float|null  $acompte_deja_verse       Déduit si applicable
 * @property float       $solde_restant_du          TTC − acompte versé (auto)
 * @property \Carbon\Carbon $date_echeance          Date limite de règlement (obligatoire)
 * @property ModePaiement $mode_paiement            Virement / CB / Chèque / Espèces
 * @property StatutPaiement $statut_paiement        En attente / Partiel / Payé / En retard / Litigieux
 * @property \Carbon\Carbon|null $date_paiement_effectif  À la réception du règlement (auto)
 * @property float       $penalites_retard          Calculées si dépassement échéance (auto)
 * @property int|null    $avoir_id                 FK → Facture (avoir) si remboursement partiel
 * @property string|null $fichier_pdf              Path vers le PDF généré (auto)
 */
class Facture extends Model
{
    use SoftDeletes;

    protected $table = 'factures';

    protected $casts = [
        'statut_paiement'        => StatutPaiement::class,
        'mode_paiement'          => ModePaiement::class,
        'lignes'                 => 'array',
        'date_emission'          => 'date',
        'date_echeance'          => 'date',
        'date_paiement_effectif' => 'date',
        'total_ht'               => 'decimal:2',
        'montant_tva'            => 'decimal:2',
        'total_ttc'              => 'decimal:2',
        'acompte_deja_verse'     => 'decimal:2',
        'solde_restant_du'       => 'decimal:2',
        'penalites_retard'       => 'decimal:2',
    ];

    protected $fillable = [
        'numero',
        'bon_de_commande_id',
        'ticket_id',
        'artisan_id',
        'contact_particulier_id',
        'lignes',
        'total_ht',
        'montant_tva',
        'total_ttc',
        'acompte_deja_verse',
        'solde_restant_du',
        'date_echeance',
        'mode_paiement',
        'statut_paiement',
        'date_paiement_effectif',
        'penalites_retard',
        'avoir_id',
        'fichier_pdf',
        'conditions_paiement',
        'notes',
    ];

    // ── Constantes ──────────────────────────────────────────────────

    /** Taux de pénalités de retard légal (3× taux BCE soit ~10%) */
    const TAUX_PENALITES_RETARD = 0.10;

    // ── Accesseurs ──────────────────────────────────────────────────

    public function getStatutPaiementLabelAttribute(): string
    {
        return $this->statut_paiement->label();
    }

    public function getStatutPaiementColorAttribute(): string
    {
        return $this->statut_paiement->color();
    }

    public function getStatutPaiementIconAttribute(): string
    {
        return $this->statut_paiement->icon();
    }

    public function getModePaiementLabelAttribute(): string
    {
        return $this->mode_paiement?->label() ?? 'Non renseigné';
    }

    public function getEstPayeeAttribute(): bool
    {
        return $this->statut_paiement === StatutPaiement::Paye;
    }

    public function getEstEnRetardAttribute(): bool
    {
        return $this->statut_paiement === StatutPaiement::EnRetard ||
            ($this->statut_paiement === StatutPaiement::EnAttente && $this->date_echeance->isPast());
    }

    public function getEstLitigieuxAttribute(): bool
    {
        return $this->statut_paiement === StatutPaiement::Litigieux;
    }

    public function getJoursRetardAttribute(): int
    {
        if (!$this->date_echeance->isPast() || $this->est_payee) {
            return 0;
        }
        return now()->diffInDays($this->date_echeance);
    }

    public function getUrlPdfAttribute(): ?string
    {
        if (!$this->fichier_pdf) {
            return null;
        }
        return Storage::url($this->fichier_pdf);
    }

    public function getAvoirAssocieAttribute(): bool
    {
        return !is_null($this->avoir_id);
    }

    // ── Calculs financiers ───────────────────────────────────────────

    /**
     * Recalcule totaux HT, TVA, TTC et solde restant dû depuis les lignes.
     */
    public function recalculerTotaux(): void
    {
        $totalHt  = 0.0;
        $totalTva = 0.0;

        foreach ($this->lignes ?? [] as $ligne) {
            $ht = ($ligne['quantite'] ?? 1) * ($ligne['prix_unitaire_ht'] ?? 0);
            $totalHt += $ht;
            $totalTva += $ht * (($ligne['taux_tva'] ?? 20) / 100);
        }

        $totalTtc        = $totalHt + $totalTva;
        $acompte         = $this->acompte_deja_verse ?? 0;
        $soldeRestantDu  = max(0, $totalTtc - $acompte);

        $this->update([
            'total_ht'        => round($totalHt, 2),
            'montant_tva'     => round($totalTva, 2),
            'total_ttc'       => round($totalTtc, 2),
            'solde_restant_du'=> round($soldeRestantDu, 2),
        ]);
    }

    /**
     * Calcule et met à jour les pénalités de retard.
     * Appelé automatiquement lors de la vérification de l'échéance.
     */
    public function calculerPenalites(): void
    {
        if (!$this->est_en_retard || $this->est_payee) {
            return;
        }

        $joursRetard = $this->jours_retard;
        if ($joursRetard <= 0) {
            return;
        }

        $penalites = $this->solde_restant_du
            * self::TAUX_PENALITES_RETARD
            * ($joursRetard / 365);

        $this->update([
            'penalites_retard' => round($penalites, 2),
            'statut_paiement'  => StatutPaiement::EnRetard,
        ]);
    }

    // ── Méthodes métier ─────────────────────────────────────────────

    /**
     * Enregistrer un paiement complet ou partiel.
     */
    public function enregistrerPaiement(
        float $montant,
        ModePaiement $mode,
        ?\DateTime $datePaiement = null
    ): void {
        $solde  = $this->solde_restant_du;
        $date   = $datePaiement ?? now();
        $estTotal = abs($montant - $solde) < 0.01;

        $this->update([
            'mode_paiement'          => $mode,
            'date_paiement_effectif' => $estTotal ? $date : $this->date_paiement_effectif,
            'statut_paiement'        => $estTotal
                ? StatutPaiement::Paye
                : StatutPaiement::Partiel,
            'solde_restant_du'       => $estTotal
                ? 0
                : round(max(0, $solde - $montant), 2),
        ]);
    }

    /**
     * Marquer comme litigieuse (impayé contesté).
     */
    public function marquerLitigieux(string $motif = null): void
    {
        $this->update([
            'statut_paiement' => StatutPaiement::Litigieux,
            'notes'           => $motif
                ? ($this->notes ? $this->notes . "\n[Litige] {$motif}" : "[Litige] {$motif}")
                : $this->notes,
        ]);
    }

    /**
     * Associer un avoir (remboursement partiel).
     */
    public function associerAvoir(Facture $avoir): void
    {
        if ($avoir->id === $this->id) {
            throw new \Exception("Une facture ne peut pas être son propre avoir.");
        }
        $this->update(['avoir_id' => $avoir->id]);
    }

    /**
     * Relance automatique impayé.
     */
    public function doitEtreRelancee(): bool
    {
        return $this->est_en_retard && !$this->est_payee && !$this->est_litigieux;
    }

    public static function genererNumero(): string
    {
        // Séquence chronologique OBLIGATOIRE pour conformité légale
        $annee    = now()->year;
        $dernierN = static::whereYear('created_at', $annee)->count() + 1;
        return 'FAC-' . $annee . '-' . str_pad($dernierN, 4, '0', STR_PAD_LEFT);
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeEnAttente($query): Builder
    {
        return $query->where('statut_paiement', StatutPaiement::EnAttente);
    }

    public function scopePartielles($query): Builder
    {
        return $query->where('statut_paiement', StatutPaiement::Partiel);
    }

    public function scopePayees($query): Builder
    {
        return $query->where('statut_paiement', StatutPaiement::Paye);
    }

    public function scopeEnRetard($query): Builder
    {
        return $query->where(function ($q) {
            $q->where('statut_paiement', StatutPaiement::EnRetard)
              ->orWhere(function ($q2) {
                  $q2->where('statut_paiement', StatutPaiement::EnAttente)
                     ->where('date_echeance', '<', now());
              });
        });
    }

    public function scopeLitigieuses($query): Builder
    {
        return $query->where('statut_paiement', StatutPaiement::Litigieux);
    }

    public function scopeNonPayees($query): Builder
    {
        return $query->whereNotIn('statut_paiement', [
            StatutPaiement::Paye->value,
        ]);
    }

    public function scopeARelancer($query): Builder
    {
        return $query->enRetard()->where('statut_paiement', '!=', StatutPaiement::Litigieux->value);
    }

    public function scopeParArtisan($query, int $artisanId): Builder
    {
        return $query->where('artisan_id', $artisanId);
    }

    public function scopeDuMois($query): Builder
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    public function scopeSansPdf($query): Builder
    {
        return $query->whereNull('fichier_pdf');
    }

    // ── KPIs ────────────────────────────────────────────────────────

    public static function getKpis(): array
    {
        return [
            'total_emises_mois'    => static::duMois()->count(),
            'en_attente_paiement'  => static::nonPayees()->count(),
            'en_retard'            => static::enRetard()->count(),
            'litigieuses'          => static::litigieuses()->count(),
            'a_relancer'           => static::aRelancer()->count(),
            'ca_encaisse_mois'     => static::payees()->duMois()->sum('total_ttc'),
            'encours_total'        => static::nonPayees()->sum('solde_restant_du'),
            'taux_recouvrement'    => static::getTauxRecouvrement(),
            'delai_moyen_paiement' => static::getDelaiMoyenPaiement(),
        ];
    }

    public static function getTauxRecouvrement(): float
    {
        $total = static::count();
        if ($total === 0) {
            return 0;
        }
        return round((static::payees()->count() / $total) * 100, 1);
    }

    public static function getDelaiMoyenPaiement(): float
    {
        return round(
            static::payees()
                ->whereNotNull('date_paiement_effectif')
                ->get()
                ->avg(function ($f) {
                    return $f->created_at->diffInDays($f->date_paiement_effectif);
                }) ?? 0,
            1
        );
    }

    // ── Boot ────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Facture $facture) {
            if (empty($facture->numero)) {
                $facture->numero = static::genererNumero();
            }
            if (empty($facture->statut_paiement)) {
                $facture->statut_paiement = StatutPaiement::EnAttente;
            }
            if (empty($facture->date_emission)) {
                $facture->date_emission = now();
            }
            if (empty($facture->date_echeance)) {
                $facture->date_echeance = now()->addDays(30);
            }
        });

        static::created(function (Facture $facture) {
            $facture->recalculerTotaux();
        });

        static::updated(function (Facture $facture) {
            if ($facture->isDirty('lignes') || $facture->isDirty('acompte_deja_verse')) {
                $facture->recalculerTotaux();
            }
            // Détection automatique du retard
            if (
                !$facture->isDirty('statut_paiement') &&
                $facture->statut_paiement === StatutPaiement::EnAttente &&
                $facture->date_echeance->isPast()
            ) {
                $facture->calculerPenalites();
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────

    public function bonDeCommande()
    {
        return $this->belongsTo(BonDeCommande::class);
    }

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

    /** Avoir associé (remboursement partiel) */
    public function avoir()
    {
        return $this->belongsTo(Facture::class, 'avoir_id');
    }

    /** Factures dont cette facture est l'avoir */
    public function factureOrigine()
    {
        return $this->hasOne(Facture::class, 'avoir_id');
    }
}
