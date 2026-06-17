<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statut_phonings', function (Blueprint $table) {
            $table->string('pipeline_statut')->nullable()->after('fiche_type');
            $table->boolean('compte_comme_tentative')->default(false)->after('pipeline_statut');
            $table->text('message_note_obligatoire')->nullable()->after('note_obligatoire');
        });
    }

    public function down(): void
    {
        Schema::table('statut_phonings', function (Blueprint $table) {
            $table->dropColumn(['pipeline_statut', 'compte_comme_tentative', 'message_note_obligatoire']);
        });
    }
};
