<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A player's result summary for one specific tournament.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_tournament', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('player_id')->nullable()->constrained('players')->nullOnDelete();
            // Kept even when player_id is set: preserves the name exactly as
            // it appeared in the source file at import time.
            $table->string('player_name')->nullable();
            $table->unsignedInteger('source_rating_id')->nullable();
            $table->unsignedInteger('k_factor')->nullable()->default(0);
            $table->decimal('points', 4, 1)->nullable()->default(0);
            $table->decimal('performance', 10, 2)->nullable()->default(0);
            $table->decimal('elo_variation', 6, 1)->nullable()->default(0);
            $table->unsignedInteger('position')->nullable()->default(0);
            $table->unsignedInteger('starting_rank')->nullable()->default(0);
            $table->decimal('elo_from_file', 10, 2)->nullable()->default(0);
            $table->decimal('elo_start', 10, 2)->nullable()->default(0);
            $table->decimal('elo_end', 10, 2)->nullable()->default(0);
            $table->boolean('is_organizer')->default(false);
            $table->boolean('is_arbiter')->default(false);
            $table->boolean('is_director')->default(false);
            $table->timestamps();

            $table->unique(['tournament_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_tournament');
    }
};
