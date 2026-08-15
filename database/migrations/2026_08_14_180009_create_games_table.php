<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('round_id')->constrained('rounds')->cascadeOnDelete();
            $table->foreignId('ranking_id')->nullable()->constrained('rankings')->nullOnDelete();
            // Real, indexed FKs — in 1.0 these were the most-queried columns
            // in the whole schema and had no index at all.
            $table->foreignId('white_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->foreignId('black_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->foreignId('bye_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->unsignedInteger('white_elo')->nullable();
            $table->unsignedInteger('white_k')->nullable();
            $table->decimal('white_variation', 6, 2)->nullable();
            $table->decimal('white_result', 2, 1)->nullable();
            $table->unsignedInteger('black_elo')->nullable();
            $table->unsignedInteger('black_k')->nullable();
            $table->decimal('black_variation', 6, 2)->nullable();
            $table->decimal('black_result', 2, 1)->nullable();
            $table->boolean('forfeit')->default(false);
            $table->boolean('no_game')->default(false);
            $table->enum('bye_type', ['unpaired', 'half_point', 'full_point', 'zero_point'])->nullable();
            $table->enum('speed', ['standard', 'rapid', 'blitz'])->nullable();
            $table->text('pgn')->nullable();
            $table->dateTime('played_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
