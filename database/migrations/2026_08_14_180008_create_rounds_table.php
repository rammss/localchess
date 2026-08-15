<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->date('date')->nullable();
            $table->time('time')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rounds');
    }
};
