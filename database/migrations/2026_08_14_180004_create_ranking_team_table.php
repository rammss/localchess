<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cross-federation adoption: more than one federation/club may recognize
// the same ranking (e.g. several clubs adopting a shared regional ranking).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranking_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ranking_id')->constrained('rankings')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['ranking_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranking_team');
    }
};
