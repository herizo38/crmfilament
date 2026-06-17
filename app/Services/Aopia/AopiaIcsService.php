<?php

namespace App\Services\Aopia;

use App\Models\Prospect;
use App\Models\RendezVous;
use Carbon\Carbon;

class AopiaIcsService
{
    public function generateForRendezVous(RendezVous $rdv): string
    {
        $prospect = $rdv->rdvable instanceof Prospect
            ? $rdv->rdvable
            : Prospect::find($rdv->rdvable_id);

        $start = Carbon::parse($rdv->date_heure);
        $end = (clone $start)->addHour();
        $summary = '[RDV AOPIA] ' . ($prospect?->raison_sociale ?: $prospect?->nom ?: 'Prospect');
        $location = trim((string) ($rdv->lieu ?: $rdv->adresse_lieu));
        $description = trim(strip_tags((string) $rdv->notes));

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//AOPIA LIKE FORMATION//CRMFilament//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'UID:' . $this->escape('aopia-rdv-' . $rdv->id . '@crmfilament'),
            'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:' . $start->utc()->format('Ymd\THis\Z'),
            'DTEND:' . $end->utc()->format('Ymd\THis\Z'),
            'SUMMARY:' . $this->escape($summary),
            'LOCATION:' . $this->escape($location),
            'DESCRIPTION:' . $this->escape($description),
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);
    }

    private function escape(string $value): string
    {
        return str_replace(["\\", ";", ",", "\n", "\r"], ["\\\\", "\\;", "\\,", "\\n", ''], $value);
    }
}
