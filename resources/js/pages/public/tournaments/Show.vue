<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Pagination from '@/components/Pagination.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import * as competitionRoutes from '@/routes/public/competitions';
import * as competitionTournamentRoutes from '@/routes/public/competitions/tournaments';
import * as tournamentRoutes from '@/routes/public/tournaments';
import type {
    Competition,
    Paginated,
    PlayerTournamentRow,
    PublicTeam,
    Tournament,
} from '@/types';

const props = defineProps<{
    team: PublicTeam;
    tournament: Tournament & {
        ranking: { id: number; name: string; acronym: string } | null;
    };
    competition: Pick<Competition, 'id' | 'name'> | null;
    standings: Paginated<PlayerTournamentRow>;
}>();

const backHref = computed(() =>
    props.competition
        ? competitionRoutes.show.url([props.team.slug, props.competition.id])
        : tournamentRoutes.index.url(props.team.slug),
);

const backLabel = computed(() =>
    props.competition ? `‹ ${props.competition.name}` : '‹ Torneos',
);

const playerHref = (playerId: number) =>
    props.competition
        ? competitionTournamentRoutes.player.url([
              props.team.slug,
              props.competition.id,
              props.tournament.id,
              playerId,
          ])
        : tournamentRoutes.player.url([
              props.team.slug,
              props.tournament.id,
              playerId,
          ]);

const formatNumber = (value: number | null) =>
    value === null
        ? '—'
        : value.toLocaleString('es-AR', { maximumFractionDigits: 1 });
</script>

<template>
    <Head :title="`${props.tournament.name} — ${props.team.name}`" />

    <div class="flex flex-col space-y-6">
        <div>
            <Link
                :href="backHref"
                class="text-sm text-muted-foreground hover:underline"
                >{{ backLabel }}</Link
            >
            <h1 class="text-xl font-semibold">{{ props.tournament.name }}</h1>
            <p class="text-sm text-muted-foreground">
                {{
                    [props.tournament.place, props.tournament.country]
                        .filter(Boolean)
                        .join(', ')
                }}
                <span v-if="props.tournament.ranking">
                    · Ranking: {{ props.tournament.ranking.name }}</span
                >
            </p>
        </div>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-16">Pos.</TableHead>
                        <TableHead>Jugador</TableHead>
                        <TableHead class="text-right">Puntos</TableHead>
                        <TableHead class="text-right">Performance</TableHead>
                        <TableHead class="text-right">Variación</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty
                        v-if="props.standings.data.length === 0"
                        :colspan="5"
                    >
                        Este torneo no tiene resultados cargados.
                    </TableEmpty>
                    <TableRow v-for="row in props.standings.data" :key="row.id">
                        <TableCell>{{ row.position ?? '—' }}</TableCell>
                        <TableCell class="font-medium">
                            <Link
                                v-if="row.player"
                                :href="playerHref(row.player.id)"
                                class="hover:underline"
                            >
                                {{ row.player.last_name }},
                                {{ row.player.name }}
                            </Link>
                            <span v-else>{{
                                row.player_name ?? 'Jugador eliminado'
                            }}</span>
                        </TableCell>
                        <TableCell class="text-right font-medium">{{
                            formatNumber(row.points)
                        }}</TableCell>
                        <TableCell class="text-right">{{
                            formatNumber(row.performance)
                        }}</TableCell>
                        <TableCell class="text-right">
                            <span
                                :class="
                                    (row.elo_variation ?? 0) >= 0
                                        ? 'text-emerald-600'
                                        : 'text-red-600'
                                "
                            >
                                {{ (row.elo_variation ?? 0) >= 0 ? '+' : ''
                                }}{{ formatNumber(row.elo_variation) }}
                            </span>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :links="props.standings.links" />
    </div>
</template>
