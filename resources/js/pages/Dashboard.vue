<script setup lang="ts">
import { Head, Link, usePage, usePoll } from '@inertiajs/vue3';
import {
    CalendarClock,
    TrendingDown,
    TrendingUp,
    Trophy,
    Users,
} from '@lucide/vue';
import TournamentController from '@/actions/App/Http/Controllers/Tournaments/TournamentController';
import PendingInvitationsModal from '@/components/PendingInvitationsModal.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import * as rankingRoutes from '@/routes/rankings';
import type { DashboardInvitation, DashboardStats, Team } from '@/types';

const props = defineProps<{
    pendingInvitations?: DashboardInvitation[];
    stats: DashboardStats | null;
}>();

const page = usePage<{ currentTeam: Team }>();

// Keep the metrics fresh without a full page reload while the dashboard
// is left open — cheap since it's a partial reload of just `stats`.
if (props.stats) {
    usePoll(30000, { only: ['stats'] });
}

defineOptions({
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: layoutProps.currentTeam
                    ? dashboard(layoutProps.currentTeam.slug)
                    : '/',
            },
        ],
    }),
});

const formatVariation = (value: number) =>
    `${value >= 0 ? '+' : ''}${value.toLocaleString('es-AR', { maximumFractionDigits: 1 })}`;
const playerLabel = (
    player: { name: string; last_name: string | null } | null,
) =>
    player
        ? `${player.last_name ?? ''}, ${player.name}`.replace(/^, /, '')
        : 'Jugador eliminado';
</script>

<template>
    <Head title="Dashboard" />

    <PendingInvitationsModal
        v-if="pendingInvitations && pendingInvitations.length > 0"
        :invitations="pendingInvitations"
    />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <template v-if="props.stats">
            <div class="grid auto-rows-min gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Rankings activos</CardTitle
                        >
                        <Trophy class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ props.stats.rankingsCount }}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Jugadores en ranking</CardTitle
                        >
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ props.stats.playersCount }}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Torneos este mes</CardTitle
                        >
                        <CalendarClock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ props.stats.tournamentsThisMonth }}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Próximos eventos</CardTitle
                        >
                        <CalendarClock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ props.stats.upcomingTournaments.length }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <TrendingUp class="h-4 w-4 text-emerald-600" />
                            Mayores subas de ELO
                        </CardTitle>
                        <CardDescription>Últimos 30 días</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p
                            v-if="props.stats.topGainers.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            Sin variaciones recientes.
                        </p>
                        <ul v-else class="space-y-2">
                            <li
                                v-for="mover in props.stats.topGainers"
                                :key="mover.id"
                                class="flex items-center justify-between text-sm"
                            >
                                <span>{{ playerLabel(mover.player) }}</span>
                                <Badge variant="default">{{
                                    formatVariation(mover.variation)
                                }}</Badge>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <TrendingDown class="h-4 w-4 text-red-600" />
                            Mayores bajas de ELO
                        </CardTitle>
                        <CardDescription>Últimos 30 días</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p
                            v-if="props.stats.topLosers.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            Sin variaciones recientes.
                        </p>
                        <ul v-else class="space-y-2">
                            <li
                                v-for="mover in props.stats.topLosers"
                                :key="mover.id"
                                class="flex items-center justify-between text-sm"
                            >
                                <span>{{ playerLabel(mover.player) }}</span>
                                <Badge variant="destructive">{{
                                    formatVariation(mover.variation)
                                }}</Badge>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Próximos torneos</CardTitle>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="props.stats.upcomingTournaments.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No hay torneos programados.
                    </p>
                    <ul v-else class="space-y-2">
                        <li
                            v-for="tournament in props.stats
                                .upcomingTournaments"
                            :key="tournament.id"
                            class="flex items-center justify-between text-sm"
                        >
                            <Link
                                :href="
                                    TournamentController.show.url([
                                        page.props.currentTeam.slug,
                                        tournament.id,
                                    ])
                                "
                                class="hover:underline"
                            >
                                {{ tournament.name }}
                            </Link>
                            <span class="text-muted-foreground">
                                {{ tournament.begins }} ·
                                {{ tournament.place ?? '—' }}
                            </span>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Accesos rápidos</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-wrap gap-2">
                    <Link
                        :href="
                            rankingRoutes.index.url(page.props.currentTeam.slug)
                        "
                        class="text-sm text-primary hover:underline"
                    >
                        Ver rankings
                    </Link>
                    <span class="text-muted-foreground">·</span>
                    <Link
                        :href="
                            TournamentController.create.url(
                                page.props.currentTeam.slug,
                            )
                        "
                        class="text-sm text-primary hover:underline"
                    >
                        Subir torneo
                    </Link>
                </CardContent>
            </Card>
        </template>

        <Card v-else>
            <CardHeader>
                <CardTitle class="text-base">Bienvenido a LocalChess</CardTitle>
                <CardDescription>
                    Esta es tu cuenta personal. Cambiá a una federación desde el
                    menú superior, o creá una nueva, para administrar jugadores,
                    torneos y rankings.
                </CardDescription>
            </CardHeader>
        </Card>
    </div>
</template>
