<?php

namespace App\Models;

use App\Enums\StatutBonDeCommande;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * BonDeCommande
 *
 * Généré automatiquement depuis un Devis accepté.
 * Déclenche la planification de l'intervention et l'émission ultérieure de la Facture.
 *
 * Chaîne documentaire : Devis → BonDeCommande → Facture
 *
 * @property int         $id
 * @property string      $numero                  Format BC-AAAA-NNNN (auto)
 * @property int         $devis_id                FK → Devis (origine)
 * @property int         $ticket_id               FK → Ticket (Affaire)
 * @property int         $artisan_id              FK → Artisan (exécutant)
 * @property int         $contact_particulier_id  FK → ContactParticulier (client)
 * @property array       $lignes                  Reprises du devis accepté
 * @property float       $montant_total_ttc        Repris du devis
 * @property float|null  $acompte_montant         Si conditions de paiement le prévoient
 * @property bool        $acompte_encaisse        True si acompte reçu
 * @property \Carbon\Carbon|null $date_intervention_prevue  Issue du RDV planifié en P3
 * @property int|null    $duree_estimee_heures
 * @property string|null $instructions_artisan    Accès, outils particuliers…
 * @property string      $conditions_paiement
 * @property StatutBonDeCommande $statut          En attente / Confirmé / En cours / Réalisé / Annulé
 * @property \Carbon\Carbon|null $date_confirmation  Quand artisan confirme (auto)
 */
class BonDeCommande extends Model
{
    use SoftDeletes;

    protected $table = 'bon_de_commandes';

    protected $casts = [
        'statut'                  => StatutBonDeCommande::class,
        'lignes'                  => 'array',
        'montant_total_ttc'       => 'decimal:2',
        'acompte_montant'         => 'decimal:2',
        'acompte_encaisse'        => 'boolean',
        'date_intervention_prevue'=> 'datetime',
        'date_confirmation'       => 'datetime',
    ];

    protected $fillable = [
        'numero',
        'devis_id',
        'ticket_id',
        'artisan_id',
        'contact_particulier_id',
        'lignes',
        'montant_total_ttc',
        'acompte_montant',
        'acompte_encaisse',
        'date_intervention_prevue',
        'duree_estimee_heures',
        'instructions_artisan',
        'conditions_paiement',
        'statut',
        'date_confirmation',
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

    public function getEstConfirmeAttribute(): bool
    {
        return $this->statut === StatutBonDeCommande::Confirme;
    }

    public function getEstRealiseAttribute(): bool
    {
        return $this->statut === StatutBonDeCommande::Realise;
    }

    public function getEstAnnuleAttribute(): bool
    {
        return $this->statut === StatutBonDeCommande::Annule;
    }

    public function getNecessiteAcompteAttribute(): bool
    {
        return $this->acompte_montant && $this->acompte_montant > 0;
    }

    public function getAcompteEnAttenteAttribute(): bool
    {
        return $this->necessite_acompte && !$this->acompte_encaisse;
    }

    public function getSoldeRestantAttribute(): float
    {
        if ($this->acompte_encaisse && $this->acompte_montant) {
            return $this->montant_total_ttc - $this->acompte_montant;
        }
        return $this->montant_total_ttc;
    }

    // ── Méthodes métier ─────────────────────────────────────────────

    /**
     * L'artisan confirme le bon de commande (accusé réception).
     */
    public function confirmerParArtisan(): void
    {
        if ($this->statut !== StatutBonDeCommande::EnAttente) {
            throw new \Exception("Le BC ne peut être confirmé que depuis le statut 'En attente'.");
        }

        $this->update([
            'statut'            => StatutBonDeCommande::Confirme,
            'date_confirmation' => now(),
        ]);

        // Synchronisation statut ticket
        if ($this->ticket) {
            $this->ticket->changerStatut(
                \App\Enums\TicketStatut::ArtisanConfirme,
                "BC #{$this->numero} confirmé par l'artisan"
            );
        }
    }

    /**
     * Planifier ou mettre à jour la date d'intervention.
     */
    public function planifierIntervention(\DateTime $date, ?int $dureeHeures = null): void
    {
        $data = [
            'date_intervention_prevue' => $date,
            'statut'                   => StatutBonDeCommande::Confirme,
        ];

        if ($dureeHeures !== null) {
            $data['duree_estimee_heures'] = $dureeHeures;
        }

        $this->update($data);
    }

    /**
     * Marquer l'intervention comme réalisée — génère la facture automatiquement.
     */
    public function marquerRealise(): Facture
    {
        if (!in_array($this->statut, [StatutBonDeCommande::Confirme, StatutBonDeCommande::EnCours])) {
            throw new \Exception("Le BC doit être confirmé ou en cours pour être marqué réalisé.");
        }

        $this->update(['statut' => StatutBonDeCommande::Realise]);

        // Clôture côté Ticket
        if ($this->ticket) {
            $this->ticket->changerStatut(
                \App\Enums\TicketStatut::InterventionRealisee,
                "Intervention BC #{$this->numero} réalisée"
            );
        }

        // Génération automatique de la facture
        return $this->genererFacture();
    }

    public function demarrerIntervention(): void
    {
        if ($this->statut !== StatutBonDeCommande::Confirme) {
            throw new \Exception("Le BC doit être confirmé pour démarrer.");
        }
        $this->update(['statut' => StatutBonDeCommande::EnCours]);
    }

    public function annuler(string $motif = null): void
    {
        if ($this->statut === StatutBonDeCommande::Realise) {
            throw new \Exception("Un BC réalisé ne peut pas être annulé.");
        }

        $this->update([
            'statut'               => StatutBonDeCommande::Annule,
            'instructions_artisan' => $motif
                ? ($this->instructions_artisan . "\n[Annulation] {$motif}")
                : $this->instructions_artisan,
        ]);
    }

    public function enregistrerAcompte(float $montant): void
    {
        $this->update([
            'acompte_montant'  => $montant,
            'acompte_encaisse' => true,
        ]);
    }

    protected function genererFacture(): Facture
    {
        return Facture::create([
            'numero'                 => Facture::genererNumero(),
            'bon_de_commande_id'     => $this->id,
            'ticket_id'              => $this->ticket_id,
            'artisan_id'             => $this->artisan_id,
            'contact_particulier_id' => $this->contact_particulier_id,
            'lignes'                 => $this->lignes,
            'acompte_deja_verse'     => $this->acompte_encaisse ? $this->acompte_montant : null,
            'conditions_paiement'    => $this->conditions_paiement,
            'statut_paiement'        => 'en_attente',
            'date_echeance'          => now()->addDays(30),
        ]);
    }

    public static function genererNumero(): string
    {
        $annee    = now()->year;
        $dernierN = static::whereYear('created_at', $annee)->count() + 1;
        return 'BC-' . $annee . '-' . str_pad($dernierN, 4, '0', STR_PAD_LEFT);
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeEnAttente($query): Builder
    {
        return $query->where('statut', StatutBonDeCommande::EnAttente);
    }

    public function scopeConfirmes($query): Builder
    {
        return $query->where('statut', StatutBonDeCommande::Confirme);
    }

    public function scopeEnCours($query): Builder
    {
        return $query->where('statut', StatutBonDeCommande::EnCours);
    }

    public function scopeRealises($query): Builder
    {
        return $query->where('statut', StatutBonDeCommande::Realise);
    }

    public function scopeAnnules($query): Builder
    {
        return $query->where('statut', StatutBonDeCommande::Annule);
    }

    public function scopeActifs($query): Builder
    {
        return $query->whereNotIn('statut', [
            StatutBonDeCommande::Realise->value,
            StatutBonDeCommande::Annule->value,
        ]);
    }

    public function scopeAvecAcompteEnAttente($query): Builder
    {
        return $query->whereNotNull('acompte_montant')
            ->where('acompte_encaisse', false)
            ->where('statut', '!=', StatutBonDeCommande::Annule->value);
    }

    public function scopeInterventionAVenir($query): Builder
    {
        return $query->whereNotNull('date_intervention_prevue')
            ->where('date_intervention_prevue', '>=', now())
            ->whereIn('statut', [
                StatutBonDeCommande::Confirme->value,
                StatutBonDeCommande::EnCours->value,
            ]);
    }

    public function scopeSansFacture($query): Builder
    {
        return $query->realises()->doesntHave('facture');
    }

    public function scopeDuMois($query): Builder
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    // ── KPIs ────────────────────────────────────────────────────────

    public static function getKpis(): array
    {
        return [
            'total_actifs'            => static::actifs()->count(),
            'en_attente_confirmation' => static::enAttente()->count(),
            'confirmes'               => static::confirmes()->count(),
            'realises_mois'           => static::realises()->duMois()->count(),
            'acomptes_en_attente'     => static::avecAcompteEnAttente()->count(),
            'interventions_a_venir'   => static::interventionAVenir()->count(),
            'sans_facture'            => static::sansFacture()->count(),
            'ca_realise_mois'         => static::realises()->duMois()->sum('montant_total_ttc'),
        ];
    }

    // ── Boot ────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (BonDeCommande $bc) {
            if (empty($bc->numero)) {
                $bc->numero = static::genererNumero();
            }
            if (empty($bc->statut)) {
                $bc->statut = StatutBonDeCommande::EnAttente;
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────

    public function devis()
    {
        return $this->belongsTo(Devis::class);
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

    public function facture()
    {
        return $this->hasOne(Facture::class);
    }
}
