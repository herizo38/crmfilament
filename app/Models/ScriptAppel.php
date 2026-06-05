<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ScriptAppel extends Model
{
    use SoftDeletes;

    protected $table = 'scripts_appel';

    protected $casts = [
        'actif'                  => 'boolean',
        'objections'             => 'array',
        'kpis'                   => 'array',
        'variables_disponibles'  => 'array',
    ];

    protected $fillable = [
        'titre',
        'slug',
        'type_contact',
        'onglet',
        'contenu',
        'conseil',
        'variables_disponibles',
        'objections',
        'kpis',
        'actif',
        'ordre',
    ];

    // ── Onglets disponibles ──────────────────────────────────────────
    public const ONGLETS = [
        'accroche'     => 'Accroche',
        'decouverte'   => 'Découverte',
        'argumentaire' => 'Argumentaire',
        'objections'   => 'Objections',
        'closing'      => 'Closing',
    ];

    // Types de contact supportés
    public const TYPES_CONTACT = [
        'artisan'     => 'Artisan',
        'partenaire'  => 'Partenaire',
        'particulier' => 'Particulier',
        'prospect'    => 'Prospect',
    ];

    // ── Accesseurs ───────────────────────────────────────────────────

    public function getOngletLabelAttribute(): string
    {
        return self::ONGLETS[$this->onglet] ?? $this->onglet;
    }

    public function getTypeContactLabelAttribute(): string
    {
        if (! $this->type_contact) return 'Universel';
        return self::TYPES_CONTACT[$this->type_contact] ?? $this->type_contact;
    }

    /**
     * Remplace les variables dynamiques dans le contenu.
     * Ex: {contact_nom} → 'Dupont'
     */
    public function interpoler(array $variables = []): string
    {
        $contenu = $this->contenu ?? '';

        foreach ($variables as $cle => $valeur) {
            $contenu = str_replace('{' . $cle . '}', $valeur ?? '', $contenu);
        }

        return $contenu;
    }

    /**
     * Interpolation du conseil
     */
    public function interpolerConseil(array $variables = []): ?string
    {
        if (! $this->conseil) return null;

        $conseil = $this->conseil;
        foreach ($variables as $cle => $valeur) {
            $conseil = str_replace('{' . $cle . '}', $valeur ?? '', $conseil);
        }

        return $conseil;
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function scopePourOnglet(Builder $query, string $onglet): Builder
    {
        return $query->where('onglet', $onglet);
    }

    /**
     * Retourne les scripts applicables pour un type de contact donné :
     * scripts universels (type_contact IS NULL) + scripts spécifiques au type.
     */
    public function scopePourTypeContact(Builder $query, ?string $typeContact): Builder
    {
        if (! $typeContact) {
            return $query->whereNull('type_contact');
        }

        return $query->where(function (Builder $q) use ($typeContact) {
            $q->whereNull('type_contact')
              ->orWhere('type_contact', $typeContact);
        });
    }

    public function scopeOrdonnes(Builder $query): Builder
    {
        return $query->orderBy('ordre')->orderBy('id');
    }

    // ── Méthode principale ───────────────────────────────────────────

    /**
     * Récupère tous les scripts organisés par onglet pour un type de contact.
     * Retourne: ['accroche' => ScriptAppel|null, 'decouverte' => ..., ...]
     *
     * Si plusieurs scripts matchent un onglet, on prend le plus spécifique
     * (type_contact non null > null), puis le premier par ordre.
     */
    public static function parOngletPourContact(?string $typeContact): array
    {
        $scripts = static::actif()
            ->pourTypeContact($typeContact)
            ->ordonnes()
            ->get();

        $result = [];

        foreach (array_keys(self::ONGLETS) as $onglet) {
            // Priorité : script spécifique au type > universel
            $scriptSpecifique = $scripts
                ->where('onglet', $onglet)
                ->where('type_contact', $typeContact)
                ->first();

            $scriptUniversel = $scripts
                ->where('onglet', $onglet)
                ->whereNull('type_contact')
                ->first();

            $result[$onglet] = $scriptSpecifique ?? $scriptUniversel;
        }

        return $result;
    }

    // ── Boot ─────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (ScriptAppel $script) {
            if (! $script->slug) {
                $script->slug = Str::slug($script->titre . '-' . $script->onglet . '-' . ($script->type_contact ?? 'universel'));
            }
        });
    }

    // ── Seeder helper ────────────────────────────────────────────────

    /**
     * Crée les scripts par défaut (données de démo).
     * Appelable depuis DatabaseSeeder ou un seeder dédié.
     */
    public static function seederDefaut(): void
    {
        $defaults = [
            // ── ACCROCHE ──────────────────────────────────────────────
            [
                'titre'         => 'Accroche universelle NS Conseil',
                'slug'          => 'accroche-universel',
                'type_contact'  => null,
                'onglet'        => 'accroche',
                'contenu'       => "Bonjour {contact_prenom} {contact_nom},\n\nJe suis {commercial_nom} de **NS CONSEIL**.\nEst-ce que vous disposez de quelques minutes pour un échange ?\nNotre équipe accompagne les professionnels comme vous à optimiser leur développement commercial.",
                'conseil'       => 'Sourire au téléphone, parler clairement, noter le prénom dès l\'annonce.',
                'actif'         => true,
                'ordre'         => 0,
            ],
            [
                'titre'         => 'Accroche Artisan',
                'slug'          => 'accroche-artisan',
                'type_contact'  => 'artisan',
                'onglet'        => 'accroche',
                'contenu'       => "Bonjour {contact_nom},\n\nJe suis {commercial_nom} de **NS CONSEIL**, spécialiste de l'accompagnement des artisans du BTP.\nNous aidons des professionnels comme vous à décrocher plus de chantiers et à mieux piloter leur activité.\nAvez-vous 5 minutes pour échanger ?",
                'conseil'       => 'Mentionner rapidement votre expertise BTP pour capter l\'attention.',
                'actif'         => true,
                'ordre'         => 1,
            ],
            [
                'titre'         => 'Accroche Prospect',
                'slug'          => 'accroche-prospect',
                'type_contact'  => 'prospect',
                'onglet'        => 'accroche',
                'contenu'       => "Bonjour, je cherche à joindre {contact_nom}.\n\nJe suis {commercial_nom} de **NS CONSEIL**.\nNous avons identifié votre entreprise comme pouvant bénéficier de notre accompagnement commercial.\nAvez-vous quelques minutes pour en discuter ?",
                'conseil'       => 'Si le standard répond, demandez à être transféré au dirigeant ou DRH.',
                'actif'         => true,
                'ordre'         => 1,
            ],

            // ── DÉCOUVERTE ────────────────────────────────────────────
            [
                'titre'         => 'Questions de découverte universelles',
                'slug'          => 'decouverte-universel',
                'type_contact'  => null,
                'onglet'        => 'decouverte',
                'contenu'       => "• \"Quels sont vos principaux objectifs pour cette année ?\"\n• \"Quels défis rencontrez-vous actuellement dans votre activité ?\"\n• \"Comment gérez-vous votre prospection commerciale ?\"\n• \"Qu'est-ce qui vous motive à chercher des solutions d'accompagnement ?\"",
                'conseil'       => 'Écoutez activement, prenez des notes, relancez avec "et en dehors de ça ?".',
                'actif'         => true,
                'ordre'         => 0,
            ],

            // ── ARGUMENTAIRE ──────────────────────────────────────────
            [
                'titre'         => 'Argumentaire NS Conseil',
                'slug'          => 'argumentaire-universel',
                'type_contact'  => null,
                'onglet'        => 'argumentaire',
                'contenu'       => "NS CONSEIL c'est **+40% de chiffre d'affaires** en moyenne pour nos clients, une équipe dédiée de 15 experts, et une approche 100% personnalisée.",
                'conseil'       => 'Adaptez les chiffres au secteur du prospect. Ne promettez que ce que vous pouvez tenir.',
                'kpis'          => [
                    ['valeur' => '94%', 'label' => 'de clients satisfaits', 'couleur' => 'purple'],
                    ['valeur' => '15j', 'label' => 'de déploiement',        'couleur' => 'purple'],
                    ['valeur' => '+40%', 'label' => 'de CA en moyenne',     'couleur' => 'green'],
                    ['valeur' => '3 ans', 'label' => 'd\'accompagnement',   'couleur' => 'blue'],
                ],
                'actif'         => true,
                'ordre'         => 0,
            ],

            // ── OBJECTIONS ────────────────────────────────────────────
            [
                'titre'         => 'Gestion des objections standard',
                'slug'          => 'objections-universel',
                'type_contact'  => null,
                'onglet'        => 'objections',
                'contenu'       => null,
                'conseil'       => 'Ne jamais contredire directement. Reformulez, validez, puis proposez une alternative.',
                'objections'    => [
                    [
                        'question' => "Je n'ai pas le temps",
                        'reponse'  => "Je comprends, je vous propose 5 minutes chrono pour voir si notre offre peut vous apporter de la valeur.",
                    ],
                    [
                        'question' => "Je ne suis pas intéressé",
                        'reponse'  => "Je comprends tout à fait. Puis-je vous envoyer une brochure pour que vous ayez toutes les informations si jamais votre situation change ?",
                    ],
                    [
                        'question' => "C'est trop cher",
                        'reponse'  => "Notre offre est modulable à partir de 499€/mois, et nos clients constatent un ROI dès le premier mois.",
                    ],
                    [
                        'question' => "J'ai déjà un prestataire",
                        'reponse'  => "C'est rassurant ! Justement, beaucoup de nos clients travaillaient déjà avec quelqu'un. On peut intervenir en complément ou pour un projet spécifique.",
                    ],
                ],
                'actif'         => true,
                'ordre'         => 0,
            ],

            // ── CLOSING ───────────────────────────────────────────────
            [
                'titre'         => 'Closing universel',
                'slug'          => 'closing-universel',
                'type_contact'  => null,
                'onglet'        => 'closing',
                'contenu'       => "\"Seriez-vous disponible **[jour de la semaine]** pour un **rendez-vous découverte de 15 minutes** ?\nNous pourrions échanger sur vos besoins et voir comment NS CONSEIL peut vous aider.\"",
                'conseil'       => 'Proposez toujours 2 créneaux alternatifs. Ne demandez pas "est-ce que vous voulez ?", demandez "plutôt mardi ou jeudi ?".',
                'actif'         => true,
                'ordre'         => 0,
            ],
        ];

        foreach ($defaults as $data) {
            static::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
