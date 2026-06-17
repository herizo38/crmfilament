<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->string('nom_interlocuteur_standard')->nullable()->after('interlocuteur_email');
            $table->string('creneaux_permanence_cse')->nullable()->after('nom_interlocuteur_standard');
            $table->string('email_general_standard')->nullable()->after('creneaux_permanence_cse');
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn(['nom_interlocuteur_standard', 'creneaux_permanence_cse', 'email_general_standard']);
        });
    }
};
