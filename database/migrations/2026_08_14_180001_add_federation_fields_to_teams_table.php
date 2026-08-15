<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A "Team" (starter kit primitive: membership, roles, invitations) doubles as
// a Federation in this domain — a federation/club is the tenant boundary.
// These columns are the Federation-specific data on top of the base team.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('acronym')->nullable()->after('name');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('address')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('parent_team_id')->nullable()->after('id')
                ->constrained('teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_team_id');
            $table->dropColumn(['acronym', 'city', 'state', 'country', 'address', 'website', 'email', 'phone']);
        });
    }
};
