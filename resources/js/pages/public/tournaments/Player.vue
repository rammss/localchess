<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import * as competitionTournamentRoutes from '@/routes/public/competitions/tournaments';
import * as tournamentRoutes from '@/routes/public/tournaments';
import type {
    Competition,
    Player,
    PlayerTournamentRow,
    PublicTeam,
    Tournament,
    TournamentGameRow,
} from '@/types';

const props = defineProps<{
    team: PublicTeam;
    tournament: Tournament & {
        ranking: { id: number; name: string; acronym: string } | null;
    };
    competition: Pick<Competition, 'id' | 'name'> | null;
    player: Player;
    summary: PlayerTournamentRow;
    games: TournamentGameRow[];
}>();

const backHref = computed(() =>
    props.competition
        ? competitionTournamentRoutes.show.url([
              props.team.slug,
              props.competition.id,
              props.tournament.id,
          ])
        : tournamentRoutes.show.url([props.team.slug, props.tournament.id]),
);

const formatNumber = (value: number | null) =>
    value === null
        ? '—'
        : value.toLocaleString('es-AR', { maximumFractionDigits: 1 });

const formatScore = (value: number) => (value === 0.5 ? '½' : value.toString());

const resultLabel = (game: TournamentGameRow) => {
    if (game.isBye) {
        return 'Bye';
    }

    if (game.score === null) {
        return '—';
    }

    const own = formatScore(game.score);
    const opponent = formatScore(1 - game.score);

    return game.color === 'black'
        ? `${opponent} - ${own}`
        : `${own} - ${opponent}`;
};
</script>

<template>
    <Head
        :title="`${props.player.last_name}, ${props.player.name} — ${props.tournament.name}`"
    />

    <div class="flex flex-col space-y-6">
        <div>
            <Link
                :href="backHref"
                class="text-sm text-muted-foreground hover:underline"
                >‹ {{ props.tournament.name }}</Link
            >
            <h1 class="flex items-center gap-2 text-xl font-semibold">
                {{ props.player.last_name }}, {{ props.player.name }}
                <Badge v-if="props.player.title" variant="secondary">{{
                    props.player.title
                }}</Badge>
            </h1>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-lg border p-4">
                <p class="text-sm text-muted-foreground">Posición</p>
                <p class="text-lg font-semibold">
                    {{ props.summary.position ?? '—' }}
                </p>
            </div>
            <div class="rounded-lg border p-4">
                <p class="text-sm text-muted-foreground">Puntos</p>
                <p class="text-lg font-semibold">
                    {{ formatNumber(props.summary.points) }}
                </p>
            </div>
            <div class="rounded-lg border p-4">
                <p class="text-sm text-muted-foreground">Performance</p>
                <p class="text-lg font-semibold">
                    {{ formatNumber(props.summary.performance) }}
                </p>
            </div>
            <div class="rounded-lg border p-4">
                <p class="text-sm text-muted-foreground">Variación ELO</p>
                <p
                    class="text-lg font-semibold"
                    :class="
                        props.summary.elo_variation >= 0
                            ? 'text-emerald-600'
                            : 'text-red-600'
                    "
                >
                    {{ props.summary.elo_variation >= 0 ? '+' : ''
                    }}{{ formatNumber(props.summary.elo_variation) }}
                </p>
            </div>
        </div>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-16">Ronda</TableHead>
                        <TableHead>Color</TableHead>
                        <TableHead>Rival</TableHead>
                        <TableHead>Resultado</TableHead>
                        <TableHead class="text-right">Variación</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="props.games.length === 0" :colspan="5">
                        No hay partidas registradas para este jugador.
                    </TableEmpty>
                    <TableRow v-for="game in props.games" :key="game.id">
                        <TableCell>{{ game.round ?? '—' }}</TableCell>
                        <TableCell class="capitalize">{{
                            game.color ?? '—'
                        }}</TableCell>
                        <TableCell>
                            <span v-if="game.opponent"
                                >{{ game.opponent.last_name }},
                                {{ game.opponent.name }}</span
                            >
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>
                        <TableCell>
                            {{ resultLabel(game) }}
                            <Badge
                                v-if="game.forfeit"
                                variant="outline"
                                class="ml-1"
                                >W.O.</Badge
                            >
                        </TableCell>
                        <TableCell class="text-right">
                            <span
                                v-if="game.variation !== null"
                                :class="
                                    game.variation >= 0
                                        ? 'text-emerald-600'
                                        : 'text-red-600'
                                "
                            >
                                {{ game.variation >= 0 ? '+' : ''
                                }}{{ formatNumber(game.variation) }}
                            </span>
                            <span v-else>—</span>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
