@php
    /** @var array $rapport */
    [$debut, $fin] = $rapport['periode'];
@endphp
<x-mail::message>
# Rapport hebdomadaire CRM

Bonjour {{ $rapport['user']->prenom }},

Voici votre récapitulatif pour la semaine du **{{ $debut->format('d/m/Y') }}** au **{{ $fin->format('d/m/Y') }}**.

@if ($rapport['role'] === 'teleprospecteur')
- **Appels passés cette semaine :** {{ $rapport['appels_semaine'] }}
- **Rappels planifiés à venir :** {{ $rapport['rappels_a_venir'] }}
- **RDV qualifiés (QF) :** {{ $rapport['qf'] }}

## Pipeline prospects
<x-mail::table>
| Statut | Nombre |
|:-------|:------:|
@foreach ($rapport['prospects_par_statut'] as $statut => $nombre)
| {{ $statut }} | {{ $nombre }} |
@endforeach
</x-mail::table>
@elseif ($rapport['role'] === 'commercial')
- **RDV réalisés/planifiés cette semaine :** {{ $rapport['rdv_semaine'] }}
- **RDV à venir :** {{ $rapport['rdv_a_venir'] }}
- **Partenaires actifs suivis :** {{ $rapport['partenaires_actifs'] }}
- **Opportunités actives :** {{ $rapport['opportunites_actives'] }}
@endif

Bonne semaine,<br>
{{ config('app.name') }}
</x-mail::message>
