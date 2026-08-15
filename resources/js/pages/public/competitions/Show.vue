<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Pagination from '@/components/Pagination.vue';
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
import * as competitionRoutes from '@/routes/public/competitions';
import * as competitionTournamentRoutes from '@/routes/public/competitions/tournaments';
import type {
    Competition,
    CompetitionStanding,
    Paginated,
    PublicTeam,
} from '@/types';

const props = defineProps<{
    team: PublicTeam;
    competition: Competition & {
        ranking: { id: number; name: string; acronym: string } | null;
    };
    standings: Paginated<CompetitionStanding>;
    tournaments: { id: number; name: string; ends: string | null }[];
}>();

const formatNumber = (value: number | null) =>
    value === null
        ? '—'
        : value.toLocaleString('es-AR', { maximumFractionDigits: 1 });
</script>

<template>
    <Head :title="`${props.competition.name} — ${props.team.name}`" />

    <div class="flex flex-col space-y-6">
        <div>
            <Link
                :href="competitionRoutes.index.url(props.team.slug)"
                class="text-sm text-muted-foreground hover:underline"
            >
                ‹ Competencias
            </Link>
            <h1 class="text-xl font-semibold">{{ props.competition.name }}</h1>
            <div
                class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
            >
                <Badge
                    :variant="
                        props.competition.in_progress ? 'default' : 'outline'
                    "
                >
                    {{
                        props.competition.in_progress
                            ? 'En curso'
                            : 'Finalizada'
                    }}
                </Badge>
                <span v-if="props.competition.ranking"
                    >· Ranking: {{ props.competition.ranking.name }}</span
                >
            </div>
        </div>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-16">Pos.</TableHead>
                        <TableHead>Jugador</TableHead>
                        <TableHead class="text-right"
                            >Torneos jugados</TableHead
                        >
                        <TableHead class="text-right">Puntaje</TableHead>
                        <TableHead class="text-right">Performance</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty
                        v-if="props.standings.data.length === 0"
                        :colspan="5"
                    >
                        Todavía no hay resultados en esta competencia.
                    </TableEmpty>
                    <TableRow
                        v-for="standing in props.standings.data"
                        :key="standing.id"
                    >
                        <TableCell>{{ standing.position ?? '—' }}</TableCell>
                        <TableCell class="font-medium">
                            <span v-if="standing.player"
                                >{{ standing.player.last_name }},
                                {{ standing.player.name }}</span
                            >
                            <span v-else class="text-muted-foreground"
                                >Jugador eliminado</span
                            >
                        </TableCell>
                        <TableCell class="text-right">{{
                            standing.played_events ?? 0
                        }}</TableCell>
                        <TableCell class="text-right font-medium">{{
                            formatNumber(standing.scoreboard)
                        }}</TableCell>
                        <TableCell class="text-right">{{
                            formatNumber(standing.performance)
                        }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :links="props.standings.links" />

        <div v-if="props.tournaments.length > 0">
            <h2 class="mb-3 text-base font-semibold">Torneos del circuito</h2>
            <div class="flex flex-col gap-2">
                <Link
                    v-for="tournament in props.tournaments"
                    :key="tournament.id"
                    :href="
                        competitionTournamentRoutes.show.url([
                            props.team.slug,
                            props.competition.id,
                            tournament.id,
                        ])
                    "
                    class="rounded-lg border px-4 py-3 text-sm hover:bg-accent/50"
                >
                    {{ tournament.name }}
                    <span class="text-muted-foreground"
                        >— {{ tournament.ends }}</span
                    >
                </Link>
            </div>
        </div>
    </div>
</template>
