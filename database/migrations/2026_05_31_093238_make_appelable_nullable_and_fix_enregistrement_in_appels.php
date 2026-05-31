<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            // ✅ Fix 1 : appelable_type et appelable_id nullable (pas toujours un contact local)
            $table->string('appelable_type')->nullable()->change();
            $table->unsignedBigInteger('appelable_id')->nullable()->change();

            // ✅ Fix 2 : enregistrement_audio en TEXT pour les longues URLs signées AWS
            $table->text('enregistrement_audio')->nullable()->change();

            // ✅ Fix 3 : user_id nullable (déjà fait dans ta migration précédente)
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            $table->string('appelable_type')->nullable(false)->change();
            $table->unsignedBigInteger('appelable_id')->nullable(false)->change();
            $table->string('enregistrement_audio')->nullable()->change();
        });
    }
};