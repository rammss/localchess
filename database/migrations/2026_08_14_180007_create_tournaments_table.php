<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('competition_id')->nullable()->constrained('competitions')->nullOnDelete();
            $table->foreignId('ranking_id')->nullable()->constrained('rankings')->nullOnDelete();
            $table->foreignId('main_arbiter_id')->nullable()->constrained('players')->nullOnDelete();
            $table->foreignId('organizer_id')->nullable()->constrained('players')->nullOnDelete();
            $table->foreignId('director_id')->nullable()->constrained('players')->nullOnDelete();
            // Dedup key: the id assigned by the pairing software (Swiss Manager,
            // Chess Results, ...) that produced the uploaded TRF16 file.
            $table->string('source_reference')->nullable();
            $table->string('name');
            $table->string('place')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('details')->nullable();
            $table->text('more_details')->nullable();
            $table->unsignedInteger('rounds_count')->nullable();
            $table->string('time_control')->nullable();
            $table->string('type')->nullable();
            $table->string('organizer_name')->nullable();
            $table->string('pairing_software')->nullable();
            $table->string('main_arbiter_name')->nullable();
            $table->text('other_arbiters')->nullable();
            $table->enum('speed', ['standard', 'rapid', 'blitz'])->default('blitz');
            $table->decimal('player_average', 6, 2)->nullable()->default(0);
            $table->unsignedInteger('amount_games')->nullable()->default(0);
            $table->unsignedInteger('amount_players')->nullable()->default(0);
            $table->unsignedInteger('amount_players_rank')->nullable()->default(0);
            // "Squads" = chess club teams competing in a team event — unrelated
            // to the tenant `Team` model, named distinctly to avoid confusion.
            $table->boolean('is_team_event')->default(false);
            $table->unsignedInteger('squads_count')->nullable();
            $table->boolean('processed')->default(false);
            $table->date('begins')->nullable();
            $table->date('ends')->nullable();
            $table->json('data')->nullable();
            $table->json('awards')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['team_id', 'source_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
