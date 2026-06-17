<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('role_name')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->json('panels')->nullable();
            $table->string('landing_path')->nullable();
            $table->string('couleur')->default('gray');
            $table->string('icone')->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('can_validate_qf')->default(false);
            $table->boolean('can_import')->default(false);
            $table->boolean('is_supervisor')->default(false);
            $table->boolean('is_system')->default(false);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_profiles');
    }
};
