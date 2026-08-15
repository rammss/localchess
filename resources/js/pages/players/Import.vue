<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import PlayerController from '@/actions/App/Http/Controllers/Players/PlayerController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
                title: 'Importar',
                href: PlayerController.importForm(
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
    <Head title="Importar jugadores" />

    <div class="flex max-w-xl flex-col space-y-6">
        <Heading
            variant="small"
            title="Importar jugadores"
            description="Subí una planilla Excel (FIDE: ID_No, Name, Sex, Fed, ClubName, Birthday, Rtg_Nat, Fide_No, Rtg_Int, Title, K)"
        />

        <Form
            v-bind="PlayerController.import.form(page.props.currentTeam.slug)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="ranking_id">Ranking al que se incorporan</Label>
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
                <InputError :message="errors.ranking_id" />
            </div>

            <div class="grid gap-2">
                <Label for="file">Archivo Excel (.xlsx)</Label>
                <input
                    id="file"
                    name="file"
                    type="file"
                    accept=".xlsx,.xls"
                    required
                    class="text-sm"
                />
                <InputError :message="errors.file" />
            </div>

            <Button type="submit" :disabled="processing">Importar</Button>
        </Form>
    </div>
</template>
