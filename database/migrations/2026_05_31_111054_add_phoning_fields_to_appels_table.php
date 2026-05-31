<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('appels', function (Blueprint $table) {
            $table->string('phoning_status')->nullable()->index();
            $table->string('phoning_result')->nullable();
            $table->text('phoning_notes')->nullable();
            $table->timestamp('phoning_completed_at')->nullable();
            $table->timestamp('phoning_skipped_at')->nullable();
            $table->foreignId('phoning_agent_id')->nullable()->constrained('users');
        });
    }

    public function down()
    {
        Schema::table('appels', function (Blueprint $table) {
            $table->dropColumn([
                'phoning_status',
                'phoning_result',
                'phoning_notes',
                'phoning_completed_at',
                'phoning_skipped_at',
                'phoning_agent_id',
            ]);
        });
    }
};