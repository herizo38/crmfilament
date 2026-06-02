<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // NOUVEAU CHAMP SOURCE APPEL
            $table->string('source_appel', 50)->nullable()->after('aircall_call_id')
                ->comment('Source de l\'appel (via CTI/téléphonie) : web, mobile, partenaire, etc.');

            // Index pour la recherche par source d'appel
            $table->index('source_appel');

            // Optionnel : ajout d'un champ pour la date de début d'intervention réelle
            $table->timestamp('debut_intervention_at')->nullable()->after('rdv_planifie_at')
                ->comment('Date et heure réelle de début d\'intervention');

            // Optionnel : ajout d'un champ pour la date de fin d'intervention réelle
            $table->timestamp('fin_intervention_at')->nullable()->after('debut_intervention_at')
                ->comment('Date et heure réelle de fin d\'intervention');

            // Optionnel : durée réelle de l'intervention (calculée)
            $table->integer('duree_reelle_minutes')->nullable()->after('fin_intervention_at')
                ->comment('Durée réelle de l\'intervention en minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'source_appel',
                'debut_intervention_at',
                'fin_intervention_at',
                'duree_reelle_minutes',
            ]);
        });
    }
};
