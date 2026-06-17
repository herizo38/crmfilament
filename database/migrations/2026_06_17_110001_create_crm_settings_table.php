<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_settings', function (Blueprint $table) {
            $table->id();
            $table->string('groupe');
            $table->string('cle');
            $table->text('valeur')->nullable();
            $table->string('type')->default('string');
            $table->string('label');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();

            $table->unique(['groupe', 'cle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_settings');
    }
};
