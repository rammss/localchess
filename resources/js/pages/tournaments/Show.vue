<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TournamentController from '@/actions/App/Http/Controllers/Tournaments/TournamentController';
import Heading from '@/components/Heading.vue';
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
import type { Paginated, PlayerTournamentRow, Team, Tournament } from '@/types';

const props = defineProps<{
    tournament: Tournament & {
        competition: { id: number; name: string } | null;
        ranking: { id: number; name: string; acronym: string } | null;
    };
    standings: Paginated<PlayerTournamentRow>;
}>();

const speedLabels: Record<Tournament['speed'], string> = {
    standard: 'Clásica',
    rapid: 'Rápida',
    blitz: 'Blitz',
};

const formatNumber = (value: number | null) =>
    value === null
        ? '—'
        : value.toLocaleString('es-AR', { maximumFractionDigits: 1 });

defineOptions({
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Torneos',
                href: TournamentController.index(layoutProps.currentTeam!.slug),
            },
        ],
    }),
});
</script>

<template>
    <Head :title="props.tournament.name" />

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            :title="props.tournament.name"
            description="Resultados finales del torneo"
        />

        <div
            class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
        >
            <Badge variant="default">Procesado</Badge>
            <span>{{ speedLabels[props.tournament.speed] }}</span>
            <span v-if="props.tournament.ranking"
                >· Ranking: {{ props.tournament.ranking.name }}</span
            >
            <span v-if="props.tournament.competition"
                >· Competencia: {{ props.tournament.competition.name }}</span
            >
            <span v-if="props.tournament.rounds_count"
                >· {{ props.tournament.rounds_count }} rondas</span
            >
            <span v-if="props.tournament.amount_games !== null"
                >· {{ props.tournament.amount_games }} partidas</span
            >
        </div>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-16">Pos.</TableHead>
                        <TableHead>Jugador</TableHead>
                        <TableHead class="text-right">Puntos</TableHead>
                        <TableHead class="text-right">Performance</TableHead>
                        <TableHead class="text-right">ELO inicial</TableHead>
                        <TableHead class="text-right">ELO final</TableHead>
                        <TableHead class="text-right">Variación</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty
                        v-if="props.standings.data.length === 0"
                        :colspan="7"
                    >
                        Este torneo no tiene resultados cargados.
                    </TableEmpty>
                    <TableRow v-for="row in props.standings.data" :key="row.id">
                        <TableCell class="font-medium">{{
                            row.position ?? '—'
                        }}</TableCell>
                        <TableCell>
                            <span v-if="row.player"
                                >{{ row.player.last_name }},
                                {{ row.player.name }}</span
                            >
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
                        <TableCell class="text-right">{{
                            formatNumber(row.elo_start)
                        }}</TableCell>
                        <TableCell class="text-right">{{
                            formatNumber(row.elo_end)
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
