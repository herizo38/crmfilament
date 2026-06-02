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
        Schema::table('contact_particuliers', function (Blueprint $table) {
            // NOUVEAUX CHAMPS
            $table->string('canal_contact_preferentiel')->default('appel')->after('email')
                ->comment('Canal de contact préféré: appel / sms / email');

            $table->string('code_postal', 10)->nullable()->after('adresse_complete')
                ->comment('Code postal extrait de l\'adresse complète');

            $table->string('ville')->nullable()->after('code_postal')
                ->comment('Ville extraite de l\'adresse complète');

            // Index pour améliorer les performances des recherches
            $table->index('code_postal');
            $table->index('ville');
            $table->index('canal_contact_preferentiel');
            $table->index(['code_postal', 'ville']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_particuliers', function (Blueprint $table) {
            $table->dropColumn([
                'canal_contact_preferentiel',
                'code_postal',
                'ville',
            ]);
        });
    }
};
