<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Audit trail: one row per ELO variation a player experiences from a single game.
// This is the source of truth for "evolution of ELO over time" charts.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_ranking_id')->constrained('player_ranking')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('game_id')->nullable()->constrained('games')->nullOnDelete();
            $table->enum('type', ['standard', 'rapid', 'blitz'])->default('standard');
            $table->decimal('rival_elo', 10, 2)->default(0);
            $table->decimal('score', 10, 2)->default(0);
            $table->decimal('performance', 10, 2)->default(0);
            $table->decimal('variation', 10, 2)->default(0);
            $table->decimal('elo_before', 10, 2)->default(0);
            $table->decimal('elo_after', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['player_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_variations');
    }
};
