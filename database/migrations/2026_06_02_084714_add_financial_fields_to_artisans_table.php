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
        Schema::table('artisans', function (Blueprint $table) {


            $table->string('formule_souscrite')
                ->nullable()
                ->after('statut_compte');

            $table->string('mode_agenda')
                ->default('mode_a')
                ->after('formule_souscrite');

            $table->json('plages_disponibilite')
                ->nullable()
                ->after('mode_agenda');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('artisans', function (Blueprint $table) {
            $table->dropColumn([
                'siret',
                'formule_souscrite',
                'mode_agenda',
                'plages_disponibilite',
            ]);
        });
    }
};
