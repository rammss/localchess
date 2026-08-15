<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useDebouncedSearch } from '@/composables/useDebouncedSearch';
import * as playerRoutes from '@/routes/public/players';
import type { Paginated, Player, PublicTeam } from '@/types';

const props = defineProps<{
    team: PublicTeam;
    players: Paginated<Player>;
    search: string | null;
}>();

const { search } = useDebouncedSearch(
    playerRoutes.index.url(props.team.slug),
    props.search,
);
</script>

<template>
    <Head :title="`Jugadores — ${props.team.name}`" />

    <div class="flex flex-col space-y-6">
        <h1 class="text-xl font-semibold">
            Jugadores de {{ props.team.name }}
        </h1>

        <Input
            v-model="search"
            placeholder="Buscar por nombre o apellido…"
            class="max-w-sm"
        />

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Jugador</TableHead>
                        <TableHead>Título</TableHead>
                        <TableHead>Club</TableHead>
                        <TableHead>País</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty
                        v-if="props.players.data.length === 0"
                        :colspan="4"
                    >
                        No hay jugadores todavía.
                    </TableEmpty>
                    <TableRow
                        v-for="player in props.players.data"
                        :key="player.id"
                    >
                        <TableCell class="font-medium">
                            <Link
                                :href="
                                    playerRoutes.show.url([
                                        props.team.slug,
                                        player.id,
                                    ])
                                "
                                class="hover:underline"
                            >
                                {{ player.last_name }}, {{ player.name }}
                            </Link>
                        </TableCell>
                        <TableCell>
                            <Badge v-if="player.title" variant="secondary">{{
                                player.title
                            }}</Badge>
                        </TableCell>
                        <TableCell>{{ player.club_name }}</TableCell>
                        <TableCell>{{ player.country }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :links="props.players.links" />
    </div>
</template>
