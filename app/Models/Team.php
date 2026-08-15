<?php

namespace App\Models;

use App\Concerns\GeneratesUniqueTeamSlugs;
use App\Enums\TeamRole;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A Team doubles as a Federation/club: the tenant boundary for tournaments,
 * competitions and rankings. Kept as "Team" internally (the starter kit's
 * membership, roles and invitations are all built around that name); shown
 * to users as "Federación" in the UI only.
 *
 * @property int $id
 * @property int|null $parent_team_id
 * @property string $name
 * @property string $slug
 * @property string|null $acronym
 * @property string|null $city
 * @property string|null $state
 * @property string|null $country
 * @property string|null $address
 * @property string|null $website
 * @property string|null $email
 * @property string|null $phone
 * @property bool $is_personal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TeamInvitation> $invitations
 * @property-read Collection<int, Membership> $memberships
 * @property-read Collection<int, User> $members
 */
#[Fillable([
    'name', 'slug', 'is_personal', 'parent_team_id',
    'acronym', 'city', 'state', 'country', 'address', 'website', 'email', 'phone',
])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use GeneratesUniqueTeamSlugs, HasFactory, SoftDeletes;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Team $team) {
            if (empty($team->slug)) {
                $team->slug = static::generateUniqueTeamSlug($team->name);
            }
        });

        static::updating(function (Team $team) {
            if ($team->isDirty('name')) {
                $team->slug = static::generateUniqueTeamSlug($team->name, $team->id);
            }
        });
    }

    /**
     * Get the team owner.
     */
    public function owner(): ?Model
    {
        return $this->members()
            ->wherePivot('role', TeamRole::Owner->value)
            ->first();
    }

    /**
     * Get all members of this team.
     *
     * @return BelongsToMany<User, $this, Membership, 'pivot'>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->using(Membership::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get all memberships for this team.
     *
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get all invitations for this team.
     *
     * @return HasMany<TeamInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_personal' => 'boolean',
        ];
    }

    /**
     * The parent federation, if this one belongs to a larger body (e.g. a
     * regional federation belonging to the national one).
     *
     * @return BelongsTo<Team, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'parent_team_id');
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Team::class, 'parent_team_id');
    }

    /**
     * Players whose home club is this federation/club.
     *
     * @return HasMany<Player, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class, 'club_team_id');
    }

    /**
     * @return HasMany<Tournament, $this>
     */
    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    /**
     * @return HasMany<Competition, $this>
     */
    public function competitions(): HasMany
    {
        return $this->hasMany(Competition::class);
    }

    /**
     * Rankings this federation owns and administers.
     *
     * @return HasMany<Ranking, $this>
     */
    public function ownedRankings(): HasMany
    {
        return $this->hasMany(Ranking::class, 'owner_team_id');
    }

    /**
     * All rankings this federation recognizes/adopts, including ones it
     * doesn't own (see the ranking_team pivot).
     *
     * @return BelongsToMany<Ranking, $this>
     */
    public function rankings(): BelongsToMany
    {
        return $this->belongsToMany(Ranking::class, 'ranking_team')
            ->withPivot(['is_default'])
            ->withTimestamps();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
