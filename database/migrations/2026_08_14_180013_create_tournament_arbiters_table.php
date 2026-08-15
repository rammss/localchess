<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A per-tournament arbiter assignment (not a standing team-wide role — see
// the plan's note on how "árbitro" maps to this schema instead of TeamRole).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_arbiters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->enum('role', ['main', 'assistant'])->default('assistant');
            $table->timestamps();

            $table->unique(['tournament_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_arbiters');
    }
};
