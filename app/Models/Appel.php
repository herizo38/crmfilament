<?php

namespace App\Models;

use App\Enums\EventType;
use App\Enums\EventResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Appel extends Model
{
    protected $table = 'appels';

    protected $casts = [
        'type' => EventType::class,
        'resultat' => EventResult::class,
        'date_heure' => 'datetime',
        'duree_secondes' => 'integer',
    ];

    // app/Models/Appel.php
    protected $fillable = [
        'appelable_type',
        'appelable_id',
        'user_id',
        'type',
        'resultat',
        'date_heure',
        'duree_secondes',
        'commentaire',
        'enregistrement_audio',
        'aircall_call_id',
        'aircall_user_id',
        'aircall_number_id',
        'direction',
        'numero_appelant',
        'aircall_user_id',
        'aircall_email',
        'aircall_agent_nom'

    ];

    // ── Accesseurs ──────────────────────────────────────────────────
    public function getTypeLabelAttribute(): string
    {
        return $this->type->label();
    }

    public function getTypeColorAttribute(): string
    {
        return $this->type->color();
    }

    public function getTypeIconAttribute(): string
    {
        return $this->type->icon();
    }

    public function getResultatLabelAttribute(): string
    {
        return $this->resultat?->label() ?? 'Non défini';
    }

    public function getResultatColorAttribute(): string
    {
        return $this->resultat?->color() ?? 'gray';
    }

    public function getResultatIconAttribute(): string
    {
        return $this->resultat?->icon() ?? 'heroicon-o-question-mark-circle';
    }

    public function getDureeFormateeAttribute(): string
    {
        if (!$this->duree_secondes) {
            return 'N/A';
        }

        $minutes = floor($this->duree_secondes / 60);
        $secondes = $this->duree_secondes % 60;

        if ($minutes > 0) {
            return "{$minutes}min {$secondes}s";
        }

        return "{$secondes}s";
    }

    public function getDateHeureFormateeAttribute(): string
    {
        return $this->date_heure->format('d/m/Y H:i');
    }

    public function getEstRealiseAttribute(): bool
    {
        return $this->resultat === EventResult::Realise;
    }

    public function getEstAnnuleAttribute(): bool
    {
        return $this->resultat === EventResult::Annule;
    }

    public function getEstDecaleAttribute(): bool
    {
        return $this->resultat === EventResult::Decale;
    }

    public function getEstRappelAttribute(): bool
    {
        return $this->resultat === EventResult::Rappel;
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function estAppel(): bool
    {
        return $this->type === EventType::Appel;
    }

    public function estPermanence(): bool
    {
        return $this->type === EventType::Permanence;
    }

    public function estPresentation(): bool
    {
        return $this->type === EventType::Presentation;
    }

    public function marquerRealise(?string $commentaire = null): void
    {
        $this->update([
            'resultat' => EventResult::Realise,
            'commentaire' => $commentaire ?? $this->commentaire,
        ]);
    }

    public function marquerAnnule(string $motif): void
    {
        $this->update([
            'resultat' => EventResult::Annule,
            'commentaire' => $this->commentaire
                ? $this->commentaire . "\n[Annulation] {$motif}"
                : "[Annulation] {$motif}",
        ]);
    }

    public function marquerDecale(\DateTime $nouvelleDate, string $motif = null): void
    {
        $data = [
            'resultat' => EventResult::Decale,
            'date_heure' => $nouvelleDate,
        ];

        if ($motif) {
            $data['commentaire'] = $this->commentaire
                ? $this->commentaire . "\n[Décalé] {$motif}"
                : "[Décalé] {$motif}";
        }

        $this->update($data);
    }

    public function programmerRappel(\DateTime $dateRappel, ?string $notes = null): void
    {
        $data = [
            'resultat' => EventResult::Rappel,
            'date_heure' => $dateRappel,
        ];

        if ($notes) {
            $data['commentaire'] = $this->commentaire
                ? $this->commentaire . "\n[Rappel] {$notes}"
                : "[Rappel] {$notes}";
        }

        $this->update($data);
    }

    public function enregistrerDuree(int $secondes): void
    {
        $this->update(['duree_secondes' => $secondes]);
    }

    public function associerEnregistrement(string $path): void
    {
        $this->update(['enregistrement_audio' => $path]);
    }

    public function associerAircall(string $callId): void
    {
        $this->update(['aircall_call_id' => $callId]);
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeRealises($query): Builder
    {
        return $query->where('resultat', EventResult::Realise);
    }

    public function scopeAnnules($query): Builder
    {
        return $query->where('resultat', EventResult::Annule);
    }

    public function scopeDecales($query): Builder
    {
        return $query->where('resultat', EventResult::Decale);
    }

    public function scopeARappeler($query): Builder
    {
        return $query->where('resultat', EventResult::Rappel);
    }

    public function scopeNonAboutis($query): Builder
    {
        return $query->where('resultat', EventResult::NonAbouti);
    }

    public function scopeAppelsSortants($query): Builder
    {
        return $query->where('type', EventType::Appel);
    }

    public function scopePermanences($query): Builder
    {
        return $query->where('type', EventType::Permanence);
    }

    public function scopePresentations($query): Builder
    {
        return $query->where('type', EventType::Presentation);
    }

    public function scopeDuJour($query): Builder
    {
        return $query->whereDate('date_heure', today());
    }

    public function scopeDeLaSemaine($query): Builder
    {
        return $query->whereBetween('date_heure', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeDuMois($query): Builder
    {
        return $query->whereMonth('date_heure', now()->month)
            ->whereYear('date_heure', now()->year);
    }

    public function scopeAVenir($query): Builder
    {
        return $query->where('date_heure', '>', now());
    }

    public function scopePasses($query): Builder
    {
        return $query->where('date_heure', '<', now());
    }

    public function scopeSansResultat($query): Builder
    {
        return $query->whereNull('resultat');
    }

    public function scopeAvecEnregistrement($query): Builder
    {
        return $query->whereNotNull('enregistrement_audio');
    }

    public function scopeDepuisAircall($query): Builder
    {
        return $query->whereNotNull('aircall_call_id');
    }

    public function scopeByUser($query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForEntity($query, Model $entity): Builder
    {
        return $query->where('appelable_type', get_class($entity))
            ->where('appelable_id', $entity->id);
    }

    // ── Méthodes statiques KPIs ─────────────────────────────────────
    public static function getKpis(?int $userId = null): array
    {
        $query = static::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return [
            'total_jour' => (clone $query)->duJour()->count(),
            'realises_jour' => (clone $query)->duJour()->realises()->count(),
            'duree_moyenne' => static::getDureeMoyenne($userId),
            'taux_reussite' => static::getTauxReussite($userId),
            'a_rappeler' => (clone $query)->aRappeler()->where('date_heure', '<', now())->count(),
            'total_mois' => (clone $query)->duMois()->count(),
            'par_type' => static::getRepartitionParType($userId),
        ];
    }

    public static function getDureeMoyenne(?int $userId = null): int
    {
        $query = static::whereNotNull('duree_secondes');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return (int) round($query->avg('duree_secondes') ?? 0);
    }

    public static function getDureeTotale(?int $userId = null): int
    {
        $query = static::whereNotNull('duree_secondes');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return (int) $query->sum('duree_secondes');
    }

    public static function getTauxReussite(?int $userId = null): float
    {
        $query = static::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $total = $query->count();
        if ($total === 0)
            return 0;

        $realises = (clone $query)->realises()->count();
        return round(($realises / $total) * 100, 1);
    }

    public static function getRepartitionParType(?int $userId = null): array
    {
        $query = static::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return collect(EventType::cases())
            ->mapWithKeys(function ($type) use ($query) {
                return [$type->value => (clone $query)->where('type', $type)->count()];
            })
            ->toArray();
    }

    // ── Boot ────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (Appel $appel) {
            if (!$appel->date_heure) {
                $appel->date_heure = now();
            }
            if (!$appel->type) {
                $appel->type = EventType::Appel;
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────
    public function appelable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Raccourcis pratiques pour les relations polymorphiques
     */
    public function partenaire()
    {
        return $this->belongsTo(Partenaire::class, 'appelable_id')
            ->where('appelable_type', Partenaire::class);
    }

    public function artisan()
    {
        return $this->belongsTo(Artisan::class, 'appelable_id')
            ->where('appelable_type', Artisan::class);
    }

    public function prospect()
    {
        return $this->belongsTo(Prospect::class, 'appelable_id')
            ->where('appelable_type', Prospect::class);
    }

    public function contactParticulier()
    {
        return $this->belongsTo(ContactParticulier::class, 'appelable_id')
            ->where('appelable_type', ContactParticulier::class);
    }
}
