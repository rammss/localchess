<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            // Nullable, unique: a player may (optionally) claim their own
            // self-service account. Global entity — a player is not owned
            // by a single federation, they cross federations across their career.
            $table->foreignId('user_id')->nullable()->unique()
                ->constrained('users')->nullOnDelete();
            $table->foreignId('club_team_id')->nullable()
                ->constrained('teams')->nullOnDelete();
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->boolean('gender')->nullable();
            $table->enum('title', ['CM', 'FM', 'IM', 'GM', 'WCM', 'WFM', 'WIM', 'WGM'])->nullable();
            $table->string('arbiter_title')->nullable();
            $table->boolean('is_arbiter')->default(false);
            $table->boolean('is_organizer')->default(false);
            $table->string('country')->nullable();
            $table->string('club_name')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('image')->nullable();
            $table->text('details')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['name', 'last_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
