export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type Ranking = {
    id: number;
    name: string;
    acronym: string;
    is_default: boolean;
    state: boolean;
    live_ranking: boolean;
    ranking_base: number;
    ranking_restart: number;
    ranking_max_entry: number;
    k_base: number;
    k_blitz: number;
    k_rapid: number;
    valuation_games_standard: number;
    valuation_games_rapid: number;
    valuation_games_blitz: number;
};

export type PlayerTitle =
    'CM' | 'FM' | 'IM' | 'GM' | 'WCM' | 'WFM' | 'WIM' | 'WGM' | null;

export type Player = {
    id: number;
    name: string;
    last_name: string | null;
    gender: boolean | null;
    title: PlayerTitle;
    country: string | null;
    club_name: string | null;
    club_team_id: number | null;
    birth_date: string | null;
    image: string | null;
    details: string | null;
};

export type Speed = 'standard' | 'rapid' | 'blitz';

export type Competition = {
    id: number;
    name: string;
    acronym: string | null;
    url: string;
    speed: Speed;
    discarded_games: number;
    in_progress: boolean;
    ranking_id: number | null;
    begins: string | null;
    ends: string | null;
};

export type Tournament = {
    id: number;
    name: string;
    place: string | null;
    country: string | null;
    speed: Speed;
    processed: boolean;
    begins: string | null;
    ends: string | null;
    rounds_count: number | null;
    amount_games: number | null;
    amount_players: number | null;
    player_average: number | null;
    ranking_id: number | null;
    competition_id: number | null;
};

export type PlayerTournamentRow = {
    id: number;
    player_id: number | null;
    player_name: string | null;
    points: number;
    performance: number;
    elo_variation: number;
    position: number;
    elo_start: number;
    elo_end: number;
    player: Player | null;
};

export type CompetitionStanding = {
    id: number;
    position: number | null;
    tournament_amount: number | null;
    played_events: number | null;
    discarded_tournaments: number | null;
    scoreboard: number | null;
    performance: number | null;
    variation: number | null;
    player: Player | null;
};

export type EloMover = {
    id: number;
    variation: number;
    created_at: string;
    player: Pick<Player, 'id' | 'name' | 'last_name'> | null;
};

export type DashboardStats = {
    rankingsCount: number;
    playersCount: number;
    tournamentsThisMonth: number;
    upcomingTournaments: Pick<Tournament, 'id' | 'name' | 'place' | 'begins'>[];
    topGainers: EloMover[];
    topLosers: EloMover[];
};

export type PublicTeam = {
    id: number;
    name: string;
    slug: string;
    acronym: string | null;
    city: string | null;
    country: string | null;
};

export type RankingStandingRow = {
    id: number;
    id_on_ranking: number;
    standard_elo: number;
    rapid_elo: number;
    blitz_elo: number;
    player: Pick<Player, 'id' | 'name' | 'last_name' | 'title' | 'country'>;
};

export type PlayerRankingWithPivot = Pick<
    Ranking,
    'id' | 'name' | 'acronym'
> & {
    pivot: {
        standard_elo: number;
        rapid_elo: number;
        blitz_elo: number;
        id_on_ranking: number;
    };
};

export type PlayerTournamentWithPivot = Pick<
    Tournament,
    'id' | 'name' | 'begins' | 'ends' | 'place'
> & {
    pivot: {
        points: number;
        performance: number;
        elo_variation: number;
        position: number;
        elo_start: number;
        elo_end: number;
    };
};

export type EloHistoryPoint = {
    date: string;
    elo: number;
    type: Speed;
    tournament: string | null;
};

export type TournamentGameRow = {
    id: number;
    round: number | null;
    color: 'white' | 'black' | null;
    opponent: Pick<Player, 'id' | 'name' | 'last_name'> | null;
    isBye: boolean;
    score: number | null;
    variation: number | null;
    forfeit: boolean;
};

export type TournamentMatch = {
    startingRank: number;
    name: string;
    lastName: string;
    rating: number;
    ratingId: number;
    matchedPlayerId: number | null;
    matchedPlayerName: string | null;
    similarity: number | null;
};
