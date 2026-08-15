<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A player's accumulated standing within a competition (circuit of tournaments).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_player', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->unsignedInteger('position')->nullable()->default(0);
            $table->unsignedInteger('tournament_amount')->nullable()->default(1);
            $table->unsignedInteger('join_event_stage')->nullable()->default(0);
            $table->unsignedInteger('played_events')->nullable()->default(0);
            $table->unsignedInteger('discarded_tournaments')->nullable()->default(0);
            $table->json('discarded_tournament_ids')->nullable();
            $table->json('points')->nullable();
            $table->json('discarded_points')->nullable();
            $table->json('counted_tournament_ids')->nullable();
            $table->json('played_tournament_ids')->nullable();
            $table->json('performances')->nullable();
            $table->json('elo_variations')->nullable();
            $table->decimal('scoreboard', 6, 1)->nullable()->default(0);
            $table->decimal('performance', 6, 1)->nullable()->default(0);
            $table->decimal('variation', 6, 1)->nullable()->default(0);
            $table->timestamps();

            $table->unique(['competition_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_player');
    }
};
