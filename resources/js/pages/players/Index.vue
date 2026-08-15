<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Download, Pencil, Plus, Trash2, Upload } from '@lucide/vue';
import { ref } from 'vue';
import PlayerController from '@/actions/App/Http/Controllers/Players/PlayerController';
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import * as playerRoutes from '@/routes/players';
import type { Paginated, Player, Team } from '@/types';

const props = defineProps<{
    players: Paginated<Player>;
    search: string | null;
}>();

const page = usePage<{ currentTeam: Team }>();
const { search } = useDebouncedSearch(
    playerRoutes.index.url(page.props.currentTeam.slug),
    props.search,
);

const deleting = ref<Player | null>(null);

defineOptions({
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Jugadores',
                href: playerRoutes.index(layoutProps.currentTeam!.slug),
            },
        ],
    }),
});
</script>

<template>
    <Head title="Jugadores" />

    <div class="flex flex-col space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Jugadores"
                description="Directorio de jugadores del sistema"
            />
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link
                        :href="
                            PlayerController.importForm.url(
                                page.props.currentTeam.slug,
                            )
                        "
                    >
                        <Upload class="h-4 w-4" /> Importar
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link
                        :href="
                            PlayerController.exportForm.url(
                                page.props.currentTeam.slug,
                            )
                        "
                    >
                        <Download class="h-4 w-4" /> Exportar
                    </Link>
                </Button>
                <Button as-child>
                    <Link
                        :href="playerRoutes.create(page.props.currentTeam.slug)"
                    >
                        <Plus /> Nuevo jugador
                    </Link>
                </Button>
            </div>
        </div>

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
                        <TableHead class="text-right">Acciones</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty
                        v-if="props.players.data.length === 0"
                        :colspan="5"
                    >
                        No hay jugadores todavía.
                    </TableEmpty>
                    <TableRow
                        v-for="player in props.players.data"
                        :key="player.id"
                    >
                        <TableCell class="font-medium">
                            {{ player.last_name }}, {{ player.name }}
                        </TableCell>
                        <TableCell>
                            <Badge v-if="player.title" variant="secondary">{{
                                player.title
                            }}</Badge>
                        </TableCell>
                        <TableCell>{{ player.club_name }}</TableCell>
                        <TableCell>{{ player.country }}</TableCell>
                        <TableCell class="text-right">
                            <Button as-child variant="ghost" size="sm">
                                <Link
                                    :href="
                                        playerRoutes.edit([
                                            page.props.currentTeam.slug,
                                            player.id,
                                        ])
                                    "
                                >
                                    <Pencil class="h-4 w-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="deleting = player"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :links="props.players.links" />
    </div>

    <ConfirmDeleteDialog
        v-if="deleting"
        :open="deleting !== null"
        title="¿Eliminar este jugador?"
        :description="`Se eliminará a '${deleting?.last_name}, ${deleting?.name}'. Esta acción no se puede deshacer.`"
        :form="
            playerRoutes.destroy.form([
                page.props.currentTeam.slug,
                deleting.id,
            ])
        "
        @update:open="
            (open) => {
                if (!open) deleting = null;
            }
        "
    />
</template>
