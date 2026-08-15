<?php

use App\Domain\Import\LegacyImporter;
use App\Enums\TeamRole;
use App\Models\Competition;
use App\Models\CompetitionPlayer;
use App\Models\Game;
use App\Models\PerformanceVariation;
use App\Models\Player;
use App\Models\PlayerRanking;
use App\Models\PlayerTournament;
use App\Models\Ranking;
use App\Models\Round;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentArbiter;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Builds a minimal copy of the LocalChess 1.0 schema (column-for-column, per
 * the real migrations in the 1.0 repo) on a second in-memory SQLite
 * connection named 'legacy', so the import command can be exercised end to
 * end without a real legacy database.
 */
function setUpLegacyDatabase(): void
{
    Config::set('database.connections.legacy', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => false,
    ]);
    DB::purge('legacy');

    $db = Schema::connection('legacy');

    $db->create('federations', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('acronym');
        $table->string('city')->nullable();
        $table->string('state')->nullable();
        $table->string('country')->nullable();
        $table->string('adress')->nullable();
        $table->string('website')->nullable();
        $table->string('email')->nullable();
        $table->string('phone')->nullable();
        $table->unsignedBigInteger('federation_id')->nullable();
        $table->timestamps();
    });

    $db->create('rankings', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('acronym');
        $table->boolean('is_default')->default(false);
        $table->boolean('live_ranking')->default(false);
        $table->integer('valuation_games_standar')->nullable()->default(5);
        $table->integer('valuation_games_rapid')->nullable()->default(5);
        $table->integer('valuation_games_blitz')->nullable()->default(5);
        $table->integer('id_base')->nullable();
        $table->integer('ranking_base')->nullable()->default(1400);
        $table->integer('ranking_restart')->nullable()->default(1000);
        $table->integer('ranking_max_entry')->nullable()->default(2200);
        $table->integer('last_used_id')->nullable();
        $table->boolean('state')->default(true);
        $table->integer('k_base')->nullable()->default(40);
        $table->decimal('k_blitz', 2, 1)->nullable()->default(0.6);
        $table->decimal('k_rapid', 2, 1)->nullable()->default(0.8);
        $table->integer('k_age')->nullable()->default(40);
        $table->integer('k_games_amount')->nullable()->default(30);
        $table->integer('k_downgrade')->nullable()->default(10);
        $table->text('k_rules')->nullable();
        $table->text('blitz')->nullable();
        $table->text('rapid')->nullable();
        $table->text('standar')->nullable();
        $table->timestamps();
    });

    $db->create('federation_ranking', function ($table) {
        $table->id();
        $table->unsignedBigInteger('federation_id')->nullable();
        $table->unsignedBigInteger('ranking_id')->nullable();
        $table->boolean('default')->default(true);
        $table->timestamps();
    });

    $db->create('players', function ($table) {
        $table->id();
        $table->string('image')->nullable();
        $table->string('name')->nullable();
        $table->string('last_name')->nullable();
        $table->boolean('gender')->nullable();
        $table->string('country')->nullable();
        $table->string('club_name')->nullable();
        $table->unsignedBigInteger('club_id')->nullable();
        $table->date('birth_date')->nullable();
        $table->string('title')->nullable();
        $table->string('arbiter_title')->nullable();
        $table->boolean('arbiter')->default(false);
        $table->boolean('organizer')->default(false);
        $table->string('details')->nullable();
        $table->timestamps();
    });

    $db->create('player_ranking', function ($table) {
        $table->id();
        $table->unsignedBigInteger('ranking_id')->nullable();
        $table->unsignedBigInteger('player_id')->nullable();
        $table->boolean('default')->default(false);
        $table->decimal('standar_elo', 6, 1)->default(0);
        $table->decimal('rapid_elo', 6, 1)->default(0);
        $table->decimal('blitz_elo', 6, 1)->default(0);
        $table->integer('k_standar_elo')->default(0);
        $table->integer('k_rapid_elo')->default(0);
        $table->integer('k_blitz_elo')->default(0);
        $table->integer('id_on_ranking')->unique();
        $table->timestamps();
    });

    $db->create('competitions', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('acronym')->nullable();
        $table->string('url')->nullable();
        $table->string('speed')->nullable();
        $table->integer('discarded_games')->nullable()->default(5);
        $table->integer('tournaments_amount')->nullable();
        $table->integer('players_amount')->nullable();
        $table->integer('games_amount')->nullable();
        $table->boolean('in_progress')->nullable()->default(0);
        $table->integer('event_stage')->nullable()->default(0);
        $table->text('rules')->nullable();
        $table->unsignedBigInteger('federation_id')->nullable();
        $table->unsignedBigInteger('ranking_id')->nullable();
        $table->date('begins')->nullable();
        $table->date('ends')->nullable();
        $table->timestamps();
    });

    $db->create('tournaments', function ($table) {
        $table->id();
        $table->string('tournament_sm_cr_id')->nullable();
        $table->string('name')->nullable();
        $table->string('place')->nullable();
        $table->string('city')->nullable();
        $table->string('state')->nullable();
        $table->string('country')->nullable();
        $table->string('details')->nullable();
        $table->string('more_details')->nullable();
        $table->string('rounds')->nullable();
        $table->string('time_control')->nullable();
        $table->string('type')->nullable();
        $table->string('organizer')->nullable();
        $table->string('paring_software')->nullable();
        $table->string('main_arbiter')->nullable();
        $table->string('other_arbiters')->nullable();
        $table->string('speed')->nullable()->default('blitz');
        $table->decimal('player_average', 5, 2)->nullable()->default(0);
        $table->integer('amount_games')->nullable();
        $table->integer('amount_players')->nullable();
        $table->integer('amount_players_rank')->nullable();
        $table->integer('amount_teams')->nullable();
        $table->boolean('teams')->default(false);
        $table->boolean('processed')->default(false);
        $table->date('begins')->nullable();
        $table->date('ends')->nullable();
        $table->text('data')->nullable();
        $table->text('awards')->nullable();
        $table->unsignedBigInteger('ranking_id')->nullable();
        $table->unsignedBigInteger('main_arbiter_id')->nullable();
        $table->unsignedBigInteger('organizer_id')->nullable();
        $table->unsignedBigInteger('competition_id')->nullable();
        $table->unsignedBigInteger('federation_id')->nullable();
        $table->unsignedBigInteger('director_id')->nullable();
        $table->timestamps();
    });

    $db->create('rounds', function ($table) {
        $table->id();
        $table->integer('number');
        $table->date('date')->nullable();
        $table->time('time')->nullable();
        $table->unsignedBigInteger('tournament_id')->nullable();
        $table->timestamps();
    });

    $db->create('games', function ($table) {
        $table->id();
        $table->unsignedBigInteger('white')->nullable();
        $table->integer('white_elo')->nullable();
        $table->integer('white_k')->nullable();
        $table->unsignedBigInteger('black')->nullable();
        $table->integer('black_elo')->nullable();
        $table->integer('black_k')->nullable();
        $table->unsignedBigInteger('player_id')->nullable();
        $table->unsignedBigInteger('ranking_id')->nullable();
        $table->unsignedBigInteger('round_id')->nullable();
        $table->unsignedBigInteger('tournament_id')->nullable();
        $table->decimal('white_variation', 5, 2)->nullable();
        $table->decimal('black_variation', 5, 2)->nullable();
        $table->string('result')->default('nogame')->nullable();
        $table->string('speed')->nullable();
        $table->string('png')->nullable();
        $table->string('details')->nullable();
        $table->dateTime('datetime')->nullable();
        $table->timestamps();
    });

    $db->create('player_tournament', function ($table) {
        $table->id();
        $table->unsignedBigInteger('player_id')->nullable();
        $table->unsignedBigInteger('tournament_id');
        $table->string('player_name')->nullable();
        $table->integer('rating_id')->nullable();
        $table->integer('k_factor')->nullable()->default(0);
        $table->decimal('points', 3, 1)->nullable()->default(0);
        $table->decimal('performance', 10, 2)->nullable()->default(0);
        $table->decimal('elo_variation', 5, 1)->nullable()->default(0);
        $table->integer('position')->nullable()->default(0);
        $table->integer('rank_start')->nullable()->default(0);
        $table->decimal('elo_file', 10, 2)->nullable()->default(0);
        $table->decimal('elo_start', 10, 2)->nullable()->default(0);
        $table->decimal('elo_end', 10, 2)->nullable()->default(0);
        $table->boolean('is_organizer')->default(false);
        $table->boolean('is_arbiter')->default(false);
        $table->boolean('is_director')->default(false);
        $table->timestamps();
    });

    $db->create('competition_player', function ($table) {
        $table->id();
        $table->unsignedBigInteger('competition_id')->nullable();
        $table->unsignedBigInteger('player_id');
        $table->integer('position')->nullable()->default(0);
        $table->integer('tournament_amount')->nullable()->default(1);
        $table->integer('join_event_stage')->nullable()->default(0);
        $table->integer('played_events')->nullable()->default(0);
        $table->integer('discarded_tournaments')->nullable()->default(0);
        $table->text('discarded_tournaments_id')->nullable();
        $table->text('points')->nullable();
        $table->text('discarded_points')->nullable();
        $table->text('counted_tournaments_id')->nullable();
        $table->text('played_tournaments_id')->nullable();
        $table->text('competition_performance')->nullable();
        $table->text('competition_elo_variation')->nullable();
        $table->decimal('scoreboards', 5, 1)->nullable()->default(0);
        $table->decimal('performance', 5, 1)->nullable()->default(0);
        $table->decimal('variation', 5, 1)->nullable()->default(0);
        $table->timestamps();
    });

    $db->create('performance_variations', function ($table) {
        $table->id();
        $table->unsignedBigInteger('player_ranking_id');
        $table->unsignedBigInteger('player_id');
        $table->unsignedBigInteger('game_id')->nullable();
        $table->string('type')->default('STANDAR');
        $table->decimal('valuation_rival_elo', 10, 2)->default(0);
        $table->decimal('valuation_score', 10, 2)->default(0);
        $table->decimal('valuation_performance', 10, 2)->default(0);
        $table->decimal('variation', 10, 2)->default(0);
        $table->decimal('elo_before', 10, 2)->default(0);
        $table->decimal('elo_after', 10, 2)->default(0);
        $table->timestamps();
    });

    $db->create('arbiter_tournament', function ($table) {
        $table->id();
        $table->unsignedBigInteger('tournament_id')->nullable();
        $table->unsignedBigInteger('player_id')->nullable();
        $table->string('position')->nullable();
        $table->timestamps();
    });
}

test('import:legacy migrates a full 1.0 dataset into the 2.0 schema with correct remapping', function () {
    setUpLegacyDatabase();
    $legacy = DB::connection('legacy');

    $owner = User::factory()->create(['email' => 'owner@example.com']);

    // Two federations, parent/child.
    $legacy->table('federations')->insert([
        ['id' => 1, 'name' => 'Federación Nacional', 'acronym' => 'FN', 'federation_id' => null, 'city' => null, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Club Regional', 'acronym' => 'CR', 'federation_id' => 1, 'city' => 'Mar del Plata', 'created_at' => now(), 'updated_at' => now()],
    ]);

    // A ranking adopted by both federations — federation 1 (lower link id) is the owner.
    $legacy->table('rankings')->insert(['id' => 1, 'name' => 'Ranking Regional', 'acronym' => 'RR', 'last_used_id' => 200, 'created_at' => now(), 'updated_at' => now()]);
    $legacy->table('federation_ranking')->insert([
        ['id' => 1, 'federation_id' => 1, 'ranking_id' => 1, 'default' => true, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'federation_id' => 2, 'ranking_id' => 1, 'default' => false, 'created_at' => now(), 'updated_at' => now()],
    ]);

    // A ranking with NO federation link at all — must be skipped (1.0 allowed this).
    $legacy->table('rankings')->insert(['id' => 2, 'name' => 'Huérfano', 'acronym' => 'HF', 'created_at' => now(), 'updated_at' => now()]);

    // Two players.
    $legacy->table('players')->insert([
        ['id' => 1, 'name' => 'Ana', 'last_name' => 'García', 'title' => 'wim', 'arbiter' => false, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Bruno', 'last_name' => 'López', 'title' => 'NoEsUnTitulo', 'arbiter' => true, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $legacy->table('player_ranking')->insert([
        ['id' => 1, 'ranking_id' => 1, 'player_id' => 1, 'standar_elo' => 1820.5, 'k_standar_elo' => 20, 'id_on_ranking' => 101, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'ranking_id' => 1, 'player_id' => 2, 'standar_elo' => 1700, 'k_standar_elo' => 20, 'id_on_ranking' => 102, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $legacy->table('competitions')->insert([
        'id' => 1, 'name' => 'Circuito 2024', 'url' => 'circuito-2024', 'speed' => 'STANDAR',
        'federation_id' => 1, 'ranking_id' => 1, 'discarded_games' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);

    // One tournament with a federation link (imported) …
    $legacy->table('tournaments')->insert([
        'id' => 1, 'name' => 'Torneo Legado', 'federation_id' => 2, 'ranking_id' => 1, 'competition_id' => 1,
        'rounds' => '1', 'speed' => 'STANDAR', 'processed' => true, 'teams' => false,
        'begins' => '2024-06-01', 'ends' => '2024-06-01', 'created_at' => now(), 'updated_at' => now(),
    ]);
    // … and one WITHOUT a federation link — must be skipped (1.0's known bug).
    $legacy->table('tournaments')->insert([
        'id' => 2, 'name' => 'Torneo Huérfano', 'federation_id' => null, 'speed' => 'blitz',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $legacy->table('rounds')->insert(['id' => 1, 'number' => 1, 'date' => '2024-06-01', 'tournament_id' => 1, 'created_at' => now(), 'updated_at' => now()]);

    // A decisive game (white wins) and a bye row, both round 1 of tournament 1.
    $legacy->table('games')->insert([
        'id' => 1, 'white' => 1, 'black' => 2, 'white_elo' => 1800, 'black_elo' => 1700,
        'white_k' => 20, 'black_k' => 20, 'ranking_id' => 1, 'round_id' => 1, 'tournament_id' => 1,
        'white_variation' => 4.5, 'black_variation' => -4.5, 'result' => '1/0', 'speed' => 'STANDAR', 'png' => '[Event "Torneo"]',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $legacy->table('games')->insert([
        'id' => 2, 'player_id' => 1, 'ranking_id' => 1, 'round_id' => 1, 'tournament_id' => 1,
        'result' => 'nogame', 'details' => 'Half point bye', 'speed' => 'STANDAR',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    // A game belonging to the skipped tournament — must be skipped too.
    $legacy->table('games')->insert([
        'id' => 3, 'white' => 1, 'black' => 2, 'round_id' => 999, 'tournament_id' => 2, 'result' => '1/0',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $legacy->table('player_tournament')->insert([
        ['id' => 1, 'player_id' => 1, 'tournament_id' => 1, 'player_name' => 'García, Ana', 'points' => 1.0, 'position' => 1, 'elo_start' => 1800, 'elo_end' => 1804.5, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'player_id' => 2, 'tournament_id' => 1, 'player_name' => 'López, Bruno', 'points' => 0.0, 'position' => 2, 'elo_start' => 1700, 'elo_end' => 1695.5, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $legacy->table('competition_player')->insert([
        'id' => 1, 'competition_id' => 1, 'player_id' => 1, 'position' => 1,
        'points' => json_encode([1.0]), 'scoreboards' => 1.0, 'performance' => 1900, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $legacy->table('performance_variations')->insert([
        'id' => 1, 'player_ranking_id' => 1, 'player_id' => 1, 'game_id' => 1, 'type' => 'STANDAR',
        'valuation_rival_elo' => 1700, 'valuation_score' => 1, 'valuation_performance' => 1900,
        'variation' => 4.5, 'elo_before' => 1800, 'elo_after' => 1804.5, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $legacy->table('arbiter_tournament')->insert([
        'id' => 1, 'tournament_id' => 1, 'player_id' => 2, 'position' => 'Árbitro principal', 'created_at' => now(), 'updated_at' => now(),
    ]);

    // --- Run the import ---
    $report = app(LegacyImporter::class)->import('owner@example.com');

    // --- Federations -> Teams, with hierarchy and ownership ---
    $national = Team::where('name', 'Federación Nacional')->sole();
    $regional = Team::where('name', 'Club Regional')->sole();
    expect($national->is_personal)->toBeFalse()
        ->and($regional->parent_team_id)->toBe($national->id)
        ->and($regional->city)->toBe('Mar del Plata')
        ->and($national->memberships()->where('user_id', $owner->id)->first()->role)->toBe(TeamRole::Owner);

    // --- Rankings: owner is the first-linked federation, both get the pivot ---
    $ranking = Ranking::where('name', 'Ranking Regional')->sole();
    expect($ranking->owner_team_id)->toBe($national->id)
        ->and($ranking->teams()->pluck('teams.id')->sort()->values()->all())->toBe(collect([$national->id, $regional->id])->sort()->values()->all())
        ->and(Ranking::where('name', 'Huérfano')->count())->toBe(0);

    // --- Players: title normalized/uppercased, invalid title dropped ---
    $garcia = Player::where('last_name', 'García')->sole();
    $lopez = Player::where('last_name', 'López')->sole();
    expect($garcia->title)->toBe('WIM')
        ->and($lopez->title)->toBeNull()
        ->and($lopez->is_arbiter)->toBeTrue();

    // --- player_ranking: typo'd column names remapped ---
    $garciaRanking = PlayerRanking::where('player_id', $garcia->id)->sole();
    expect((float) $garciaRanking->standard_elo)->toBe(1820.5)
        ->and($garciaRanking->id_on_ranking)->toBe(101);

    // --- Competitions: speed lowercased, orphan tournament excluded from counts ---
    $competition = Competition::where('name', 'Circuito 2024')->sole();
    expect($competition->speed)->toBe('standard')
        ->and($competition->team_id)->toBe($national->id);

    // --- Tournaments: the federation-less one is skipped ---
    expect(Tournament::count())->toBe(1);
    $tournament = Tournament::sole();
    expect($tournament->team_id)->toBe($regional->id)
        ->and($tournament->rounds_count)->toBe(1)
        ->and($tournament->speed)->toBe('standard')
        ->and($tournament->competition_id)->toBe($competition->id);

    // --- Rounds ---
    $round = Round::sole();
    expect($round->tournament_id)->toBe($tournament->id);

    // --- Games: decisive result codes -> decimals, bye row -> bye_player_id, skipped-tournament game excluded ---
    expect(Game::count())->toBe(2);
    $decisive = Game::where('no_game', false)->sole();
    expect((float) $decisive->white_result)->toBe(1.0)
        ->and((float) $decisive->black_result)->toBe(0.0)
        ->and($decisive->forfeit)->toBeFalse()
        ->and($decisive->white_player_id)->toBe($garcia->id)
        ->and($decisive->black_player_id)->toBe($lopez->id)
        ->and($decisive->pgn)->toBe('[Event "Torneo"]');

    $bye = Game::where('no_game', true)->sole();
    expect($bye->bye_player_id)->toBe($garcia->id)
        ->and($bye->bye_type)->toBe('half_point');

    // --- player_tournament, competition_player, performance_variations, arbiters ---
    expect(PlayerTournament::count())->toBe(2)
        ->and(CompetitionPlayer::where('player_id', $garcia->id)->exists())->toBeTrue()
        ->and(PerformanceVariation::where('player_id', $garcia->id)->sole()->type)->toBe('standard')
        ->and(TournamentArbiter::where('player_id', $lopez->id)->sole()->role)->toBe('main');

    // --- Report accounts for every source row ---
    $rows = collect($report->rows())->keyBy('table');
    expect($rows['tournaments']['source'])->toBe(2)
        ->and($rows['tournaments']['imported'])->toBe(1)
        ->and($rows['tournaments']['skipped'])->toBe(1)
        ->and($rows['rankings']['skipped'])->toBe(1)
        ->and($rows['games']['source'])->toBe(3)
        ->and($rows['games']['imported'])->toBe(2)
        ->and($rows['games']['skipped'])->toBe(1);
});
