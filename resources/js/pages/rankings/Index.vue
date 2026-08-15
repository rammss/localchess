<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
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
import * as rankingRoutes from '@/routes/rankings';
import type { Paginated, Ranking, Team } from '@/types';

const props = defineProps<{
    rankings: Paginated<Ranking>;
    search: string | null;
}>();

const page = usePage<{ currentTeam: Team }>();
const { search } = useDebouncedSearch(
    rankingRoutes.index.url(page.props.currentTeam.slug),
    props.search,
);

const deleting = ref<Ranking | null>(null);

defineOptions({
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Rankings',
                href: rankingRoutes.index(layoutProps.currentTeam!.slug),
            },
        ],
    }),
});
</script>

<template>
    <Head title="Rankings" />

    <div class="flex flex-col space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Rankings"
                description="Sistemas de ELO administrados por esta federación"
            />
            <Button as-child>
                <Link :href="rankingRoutes.create(page.props.currentTeam.slug)">
                    <Plus /> Nuevo ranking
                </Link>
            </Button>
        </div>

        <Input
            v-model="search"
            placeholder="Buscar por nombre o sigla…"
            class="max-w-sm"
        />

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Nombre</TableHead>
                        <TableHead>Sigla</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead class="text-right">Acciones</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty
                        v-if="props.rankings.data.length === 0"
                        :colspan="4"
                    >
                        No hay rankings todavía.
                    </TableEmpty>
                    <TableRow
                        v-for="ranking in props.rankings.data"
                        :key="ranking.id"
                    >
                        <TableCell class="font-medium">
                            {{ ranking.name }}
                            <Badge
                                v-if="ranking.is_default"
                                variant="secondary"
                                class="ml-2"
                            >
                                Por defecto
                            </Badge>
                        </TableCell>
                        <TableCell>{{ ranking.acronym }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="ranking.state ? 'default' : 'outline'"
                            >
                                {{ ranking.state ? 'Activo' : 'Inactivo' }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right">
                            <Button as-child variant="ghost" size="sm">
                                <Link
                                    :href="
                                        rankingRoutes.edit([
                                            page.props.currentTeam.slug,
                                            ranking.id,
                                        ])
                                    "
                                >
                                    <Pencil class="h-4 w-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="deleting = ranking"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :links="props.rankings.links" />
    </div>

    <ConfirmDeleteDialog
        v-if="deleting"
        :open="deleting !== null"
        title="¿Eliminar este ranking?"
        :description="`Se eliminará '${deleting?.name}'. Esta acción no se puede deshacer.`"
        :form="
            rankingRoutes.destroy.form([
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
