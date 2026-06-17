<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            $table->string('fiche_type')->nullable()->after('campagne_id'); // bleue | jaune | verte
            $table->json('fiche_data')->nullable()->after('fiche_type');
        });
    }

    public function down(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            $table->dropColumn(['fiche_type', 'fiche_data']);
        });
    }
};
