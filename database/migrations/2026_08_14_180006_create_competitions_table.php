<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('ranking_id')->nullable()->constrained('rankings')->nullOnDelete();
            $table->string('name');
            $table->string('acronym')->nullable();
            $table->string('url')->unique();
            $table->enum('speed', ['standard', 'rapid', 'blitz'])->default('blitz');
            $table->unsignedInteger('discarded_games')->default(0);
            $table->unsignedInteger('tournaments_amount')->default(0);
            $table->unsignedInteger('players_amount')->default(0);
            $table->unsignedInteger('games_amount')->default(0);
            $table->boolean('in_progress')->default(false);
            $table->unsignedInteger('event_stage')->default(0);
            $table->json('rules')->nullable();
            $table->date('begins')->nullable();
            $table->date('ends')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
