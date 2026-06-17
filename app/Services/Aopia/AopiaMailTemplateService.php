<?php

namespace App\Services\Aopia;

use App\Models\Prospect;
use App\Models\RendezVous;
use App\Models\User;
use Carbon\Carbon;

class AopiaMailTemplateService
{
    public function mailConfirmationCse(Prospect $prospect, RendezVous $rdv, User $teleprospecteur): array
    {
        $date = Carbon::parse($rdv->date_heure);

        return [
            'to' => $prospect->interlocuteur_email,
            'subject' => 'Confirmation de votre rendez-vous AOPIA Formation — ' . $date->format('d/m/Y') . ' à ' . $date->format('H:i'),
            'body' => "Bonjour {$prospect->interlocuteur_nom},\n\n" .
                "Comme convenu lors de notre échange, je vous confirme votre rendez-vous avec notre Responsable de Secteur :\n\n" .
                "Date : {$date->format('d/m/Y')} | Heure : {$date->format('H:i')} | Lieu : " . ($rdv->lieu ?: $rdv->adresse_lieu) . "\n" .
                "Votre interlocuteur : " . ($rdv->commercial?->nom_complet ?? $rdv->commercial?->name ?? 'Responsable de Secteur') . "\n\n" .
                "Notre Responsable de Secteur vous présentera les modalités de formation pour vos collègues ainsi que des exemples de communications déjà mises en place dans d'autres entreprises de votre département.\n\n" .
                "N'hésitez pas à me contacter si vous souhaitez modifier ce créneau.\n\n" .
                ($teleprospecteur->nom_complet ?? $teleprospecteur->name ?? $teleprospecteur->email) . " — AOPIA Formation",
        ];
    }

    public function mailInvitationResponsable(Prospect $prospect, RendezVous $rdv, User $teleprospecteur): array
    {
        $date = Carbon::parse($rdv->date_heure);

        return [
            'to' => $rdv->commercial?->email,
            'cc' => config('aopia.mail.mail2_locked_cc', []),
            'subject' => '[RDV AOPIA] ' . ($prospect->raison_sociale ?: $prospect->nom) . ' — ' . $date->format('d/m/Y') . ' à ' . $date->format('H:i') . ' — ' . $prospect->interlocuteur_nom,
            'body' => "Bonjour,\n\n" .
                "Tu trouveras ci-dessous tous les éléments pour ton rendez-vous. Merci d'accepter l'invitation agenda ci-jointe.\n\n" .
                "Date : {$date->format('d/m/Y')} à {$date->format('H:i')} — " . ($rdv->lieu ?: $rdv->adresse_lieu) . "\n" .
                "Contact CSE : {$prospect->interlocuteur_nom} — {$prospect->interlocuteur_fonction} — {$prospect->interlocuteur_email} — {$prospect->interlocuteur_telephone}\n" .
                "Entreprise : " . ($prospect->raison_sociale ?: $prospect->nom) . " — {$prospect->secteur_activite} — {$prospect->nb_salaries} salariés\n\n" .
                "Points clés :\n" . trim((string) $prospect->description) . "\n\n" .
                "Le RDV a été confirmé par email au CSE. Les pièces jointes doivent inclure la fiche récap et l'enregistrement audio.\n\n" .
                ($teleprospecteur->nom_complet ?? $teleprospecteur->name ?? $teleprospecteur->email) . " — AOPIA Formation",
        ];
    }
}
