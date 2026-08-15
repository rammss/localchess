<?php

use App\Http\Controllers\Competitions\CompetitionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Players\PlayerController;
use App\Http\Controllers\Public\PublicCompetitionController;
use App\Http\Controllers\Public\PublicPlayerController;
use App\Http\Controllers\Public\PublicRankingController;
use App\Http\Controllers\Public\PublicTournamentController;
use App\Http\Controllers\Rankings\RankingController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Tournaments\TournamentController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::prefix('{team}/public')->name('public.')->group(function () {
    Route::get('rankings', [PublicRankingController::class, 'index'])->name('rankings.index');
    Route::get('rankings/{ranking}', [PublicRankingController::class, 'show'])->name('rankings.show');

    Route::get('players', [PublicPlayerController::class, 'index'])->name('players.index');
    Route::get('players/{player}', [PublicPlayerController::class, 'show'])->name('players.show');

    Route::get('tournaments', [PublicTournamentController::class, 'index'])->name('tournaments.index');
    Route::get('tournaments/{tournament}', [PublicTournamentController::class, 'show'])->name('tournaments.show');
    Route::get('tournaments/{tournament}/players/{player}', [PublicTournamentController::class, 'player'])->name('tournaments.player');

    Route::get('competitions', [PublicCompetitionController::class, 'index'])->name('competitions.index');
    Route::get('competitions/{competition}', [PublicCompetitionController::class, 'show'])->name('competitions.show');
    Route::get('competitions/{competition}/tournaments/{tournament}', [PublicTournamentController::class, 'showForCompetition'])->name('competitions.tournaments.show');
    Route::get('competitions/{competition}/tournaments/{tournament}/players/{player}', [PublicTournamentController::class, 'playerForCompetition'])->name('competitions.tournaments.player');
});

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::resource('rankings', RankingController::class)->except(['show']);

        Route::get('players/import', [PlayerController::class, 'importForm'])->name('players.import.form');
        Route::post('players/import', [PlayerController::class, 'import'])->name('players.import');
        Route::get('players/export', [PlayerController::class, 'exportForm'])->name('players.export.form');
        Route::get('players/export/download', [PlayerController::class, 'export'])->name('players.export');
        Route::resource('players', PlayerController::class)->except(['show']);

        Route::resource('competitions', CompetitionController::class);

        Route::get('tournaments/{tournament}/confirm', [TournamentController::class, 'confirm'])->name('tournaments.confirm');
        Route::post('tournaments/{tournament}/process', [TournamentController::class, 'process'])->name('tournaments.process');
        Route::resource('tournaments', TournamentController::class)->except(['edit', 'update']);
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
