<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import PlayerController from '@/actions/App/Http/Controllers/Players/PlayerController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import * as playerRoutes from '@/routes/players';
import type { Ranking, Team } from '@/types';

const props = defineProps<{
    rankings: Ranking[];
}>();

const page = usePage<{ currentTeam: Team }>();

defineOptions({
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Jugadores',
                href: playerRoutes.index(layoutProps.currentTeam!.slug),
            },
            {
                title: 'Exportar',
                href: PlayerController.exportForm(
                    layoutProps.currentTeam!.slug,
                ),
            },
        ],
    }),
});

const selectClass =
    'flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30 md:text-sm';
</script>

<template>
    <Head title="Exportar jugadores" />

    <div class="flex max-w-xl flex-col space-y-6">
        <Heading
            variant="small"
            title="Exportar jugadores"
            description="Descarga el listado de jugadores de un ranking en formato Excel"
        />

        <!--
          Plain GET navigation, not Inertia's <Form>: this needs to trigger
          a real file download in the browser, not an XHR/JSON round trip.
        -->
        <form
            method="get"
            :action="PlayerController.export.url(page.props.currentTeam.slug)"
            class="space-y-6"
        >
            <div class="grid gap-2">
                <Label for="ranking_id">Ranking</Label>
                <select
                    id="ranking_id"
                    name="ranking_id"
                    required
                    :class="selectClass"
                >
                    <option value="" disabled selected>Elegir ranking…</option>
                    <option
                        v-for="ranking in props.rankings"
                        :key="ranking.id"
                        :value="ranking.id"
                    >
                        {{ ranking.name }} ({{ ranking.acronym }})
                    </option>
                </select>
            </div>

            <div class="grid gap-2">
                <Label for="speed">Modalidad</Label>
                <select id="speed" name="speed" required :class="selectClass">
                    <option value="standard">Clásica</option>
                    <option value="rapid">Rápida</option>
                    <option value="blitz">Blitz</option>
                </select>
            </div>

            <Button type="submit">Descargar Excel</Button>
        </form>
    </div>
</template>
