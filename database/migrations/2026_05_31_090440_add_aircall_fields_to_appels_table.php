<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            // Déjà présent dans le modèle, vérifier s'ils existent
            if (!Schema::hasColumn('appels', 'aircall_call_id')) {
                $table->string('aircall_call_id')->nullable()->unique()->after('commentaire');
            }
            if (!Schema::hasColumn('appels', 'enregistrement_audio')) {
                $table->string('enregistrement_audio')->nullable()->after('aircall_call_id');
            }
            // Champs supplémentaires Aircall
            if (!Schema::hasColumn('appels', 'aircall_number_id')) {
                $table->string('aircall_number_id')->nullable()->after('aircall_call_id');
            }
            if (!Schema::hasColumn('appels', 'aircall_user_id')) {
                $table->string('aircall_user_id')->nullable()->after('aircall_number_id');
            }
            if (!Schema::hasColumn('appels', 'direction')) {
                $table->string('direction')->nullable()->after('aircall_user_id'); // inbound/outbound
            }
            if (!Schema::hasColumn('appels', 'numero_appelant')) {
                $table->string('numero_appelant')->nullable()->after('direction');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            $table->dropColumn([
                'aircall_number_id',
                'aircall_user_id',
                'direction',
                'numero_appelant',
            ]);
        });
    }
};