<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The current ELO state of a player within a ranking, per game speed.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_ranking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ranking_id')->constrained('rankings')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->decimal('standard_elo', 6, 1)->default(0);
            $table->decimal('rapid_elo', 6, 1)->default(0);
            $table->decimal('blitz_elo', 6, 1)->default(0);
            $table->unsignedInteger('k_standard_elo')->default(0);
            $table->unsignedInteger('k_rapid_elo')->default(0);
            $table->unsignedInteger('k_blitz_elo')->default(0);
            $table->unsignedInteger('id_on_ranking');
            $table->timestamps();

            $table->unique(['ranking_id', 'player_id']);
            $table->unique(['ranking_id', 'id_on_ranking']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_ranking');
    }
};
