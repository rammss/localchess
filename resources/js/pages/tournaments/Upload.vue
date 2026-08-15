<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import TournamentController from '@/actions/App/Http/Controllers/Tournaments/TournamentController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { Ranking, Team } from '@/types';

const props = defineProps<{
    rankings: Pick<Ranking, 'id' | 'name' | 'acronym'>[];
}>();

const page = usePage<{ currentTeam: Team }>();

const selectClass =
    'flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30 md:text-sm';

defineOptions({
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Torneos',
                href: TournamentController.index(layoutProps.currentTeam!.slug),
            },
            {
                title: 'Subir torneo',
                href: TournamentController.create(
                    layoutProps.currentTeam!.slug,
                ),
            },
        ],
    }),
});
</script>

<template>
    <Head title="Subir torneo" />

    <div class="flex max-w-xl flex-col space-y-6">
        <Heading
            variant="small"
            title="Subir torneo"
            description="Subí el archivo TRF16 exportado por el software de emparejamiento (Swiss Manager, Chess Results, etc.)"
        />

        <Form
            v-bind="
                TournamentController.store.form(page.props.currentTeam.slug)
            "
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="ranking_id">Ranking al que pertenece</Label>
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
                <Label for="file">Archivo TRF16 (.trf)</Label>
                <input
                    id="file"
                    name="file"
                    type="file"
                    accept=".trf,.txt"
                    required
                    class="text-sm"
                />
                <InputError :message="errors.file" />
            </div>

            <Button type="submit" :disabled="processing">Subir torneo</Button>
        </Form>
    </div>
</template>
