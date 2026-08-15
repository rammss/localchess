<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('name');
            $table->string('acronym');
            $table->boolean('is_default')->default(false);
            $table->boolean('live_ranking')->default(false);
            $table->unsignedInteger('valuation_games_standard')->default(5);
            $table->unsignedInteger('valuation_games_rapid')->default(5);
            $table->unsignedInteger('valuation_games_blitz')->default(5);
            $table->unsignedInteger('id_base')->nullable();
            $table->unsignedInteger('ranking_base')->default(1400);
            $table->unsignedInteger('ranking_restart')->default(1000);
            $table->unsignedInteger('ranking_max_entry')->default(2200);
            $table->unsignedInteger('last_used_id')->default(0);
            $table->boolean('state')->default(true);
            $table->unsignedInteger('k_base')->default(40);
            $table->decimal('k_blitz', 4, 2)->default(0.6);
            $table->decimal('k_rapid', 4, 2)->default(0.8);
            $table->unsignedInteger('k_age')->default(40);
            $table->unsignedInteger('k_games_amount')->default(30);
            $table->unsignedInteger('k_downgrade')->default(10);
            // Rules and per-category K-factor bands, keyed by speed.
            $table->json('k_rules')->nullable();
            $table->json('blitz_rules')->nullable();
            $table->json('rapid_rules')->nullable();
            $table->json('standard_rules')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['owner_team_id', 'acronym']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rankings');
    }
};
