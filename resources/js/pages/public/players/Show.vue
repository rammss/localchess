<script setup lang="ts">
import { Deferred, Head, Link } from '@inertiajs/vue3';
import EloHistoryChart from '@/components/EloHistoryChart.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import * as playerRoutes from '@/routes/public/players';
import * as tournamentRoutes from '@/routes/public/tournaments';
import type {
    EloHistoryPoint,
    Player,
    PlayerRankingWithPivot,
    PlayerTournamentWithPivot,
    PublicTeam,
} from '@/types';

const props = defineProps<{
    team: PublicTeam;
    player: Player;
    rankings: PlayerRankingWithPivot[];
    tournaments: PlayerTournamentWithPivot[];
    eloHistory?: EloHistoryPoint[];
}>();

const formatNumber = (value: number) =>
    value.toLocaleString('es-AR', { maximumFractionDigits: 1 });
</script>

<template>
    <Head
        :title="`${props.player.last_name}, ${props.player.name} — ${props.team.name}`"
    />

    <div class="flex flex-col space-y-6">
        <div>
            <Link
                :href="playerRoutes.index.url(props.team.slug)"
                class="text-sm text-muted-foreground hover:underline"
            >
                ‹ Jugadores
            </Link>
            <h1 class="flex items-center gap-2 text-xl font-semibold">
                {{ props.player.last_name }}, {{ props.player.name }}
                <Badge v-if="props.player.title" variant="secondary">{{
                    props.player.title
                }}</Badge>
            </h1>
            <p
                v-if="props.player.club_name || props.player.country"
                class="text-sm text-muted-foreground"
            >
                {{
                    [props.player.club_name, props.player.country]
                        .filter(Boolean)
                        .join(' · ')
                }}
            </p>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">ELO por ranking</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div
                        v-for="ranking in props.rankings"
                        :key="ranking.id"
                        class="rounded-lg border p-4"
                    >
                        <p class="mb-2 font-medium">
                            {{ ranking.name }} ({{ ranking.acronym }})
                        </p>
                        <div class="grid grid-cols-3 gap-2 text-sm">
                            <div>
                                <span class="text-muted-foreground"
                                    >Clásica</span
                                >
                                <br />
                                {{ ranking.pivot.standard_elo }}
                            </div>
                            <div>
                                <span class="text-muted-foreground"
                                    >Rápida</span
                                >
                                <br />
                                {{ ranking.pivot.rapid_elo }}
                            </div>
                            <div>
                                <span class="text-muted-foreground">Blitz</span>
                                <br />
                                {{ ranking.pivot.blitz_elo }}
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Evolución del ELO</CardTitle>
            </CardHeader>
            <CardContent>
                <Deferred data="eloHistory">
                    <template #fallback>
                        <Skeleton class="h-64 w-full" />
                    </template>
                    <EloHistoryChart :history="props.eloHistory ?? []" />
                </Deferred>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Historial de torneos</CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Torneo</TableHead>
                            <TableHead class="text-right">Puntos</TableHead>
                            <TableHead class="text-right"
                                >Performance</TableHead
                            >
                            <TableHead class="text-right">Variación</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty
                            v-if="props.tournaments.length === 0"
                            :colspan="4"
                        >
                            Este jugador todavía no participó en torneos de esta
                            federación.
                        </TableEmpty>
                        <TableRow
                            v-for="tournament in props.tournaments"
                            :key="tournament.id"
                        >
                            <TableCell class="font-medium">
                                <Link
                                    :href="
                                        tournamentRoutes.player.url([
                                            props.team.slug,
                                            tournament.id,
                                            props.player.id,
                                        ])
                                    "
                                    class="hover:underline"
                                >
                                    {{ tournament.name }}
                                </Link>
                                <div class="text-sm text-muted-foreground">
                                    {{ tournament.begins }}
                                </div>
                            </TableCell>
                            <TableCell class="text-right">{{
                                formatNumber(tournament.pivot.points)
                            }}</TableCell>
                            <TableCell class="text-right">{{
                                formatNumber(tournament.pivot.performance)
                            }}</TableCell>
                            <TableCell class="text-right">
                                <span
                                    :class="
                                        tournament.pivot.elo_variation >= 0
                                            ? 'text-emerald-600'
                                            : 'text-red-600'
                                    "
                                >
                                    {{
                                        tournament.pivot.elo_variation >= 0
                                            ? '+'
                                            : ''
                                    }}{{
                                        formatNumber(
                                            tournament.pivot.elo_variation,
                                        )
                                    }}
                                </span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </div>
</template>
